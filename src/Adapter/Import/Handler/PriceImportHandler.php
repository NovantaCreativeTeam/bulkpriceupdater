<?php

namespace Novanta\BulkPriceUpdater\Adapter\Import\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Novanta\BulkPriceUpdater\Entity\PriceImportLog;
use NumberFormatter;
use ObjectModel;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\Database;
use PrestaShop\PrestaShop\Adapter\Entity\Combination;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use PrestaShop\PrestaShop\Adapter\Import\Handler\AbstractImportHandler;
use PrestaShop\PrestaShop\Adapter\Import\ImportDataFormatter;
use PrestaShop\PrestaShop\Adapter\Validate;
use PrestaShop\PrestaShop\Core\Cache\Clearer\CacheClearerInterface;
use PrestaShop\PrestaShop\Core\Import\Configuration\ImportConfigInterface;
use PrestaShop\PrestaShop\Core\Import\Configuration\ImportRuntimeConfigInterface;
use PrestaShop\PrestaShop\Core\Import\Exception\InvalidDataRowException;
use PrestaShop\PrestaShop\Core\Import\Exception\SkippedIterationException;
use PrestaShop\PrestaShop\Core\Import\File\DataRow\DataRowInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PriceImportHandler extends AbstractImportHandler {
    
    protected $entityManager;

    public function __construct(
        ImportDataFormatter $dataFormatter,
        array $allShopIds,
        array $contextShopIds,
        $currentContextShopId,
        $isMultistoreEnabled,
        $contextLanguageId,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        $employeeId,
        Database $legacyDatabase,
        CacheClearerInterface $cacheClearer,
        Configuration $configuration,
        Validate $validate,
        EntityManagerInterface $entityManager
    )
    {
        parent::__construct(
            $dataFormatter,
            $allShopIds,
            $contextShopIds,
            $currentContextShopId,
            $isMultistoreEnabled,
            $contextLanguageId,
            $translator,
            $logger,
            $employeeId,
            $legacyDatabase,
            $cacheClearer,
            $configuration,
            $validate
        );

        $this->entityManager = $entityManager;
    }
    
    public function importRow(
        ImportConfigInterface $importConfig, 
        ImportRuntimeConfigInterface $runtimeConfig, 
        DataRowInterface $dataRow)
    {
        parent::importRow($importConfig, $runtimeConfig, $dataRow);
        
        /** @var ObjectModel $entity */
        $entity = $this->getEntity($dataRow, $runtimeConfig);

        if($entity && $entity->id) {
            //$entityFields = $runtimeConfig->getEntityFields();

            // Carico solo il prezzo, non devo modificare nessun altro dato
            // $this->fillEntityData($entity, $entityFields, $dataRow, $this->languageId);
            $entity->price_tex = $this->fetchDataValueByKey($dataRow, $runtimeConfig->getEntityFields(), 'price_tex');
            $entity->price_tex = $this->parsePrice($entity->price_tex);

            if(\is_a($entity, Product::class)) {
                $this->loadPrice($entity);
            }

            if(\is_a($entity, Combination::class)) {
                $this->loadImpactOnPrice($entity);
            }

            $unfriendlyError = $this->configuration->getBoolean('UNFRIENDLY_ERROR');
            $fieldsError = $entity->validateFields($unfriendlyError, true);
            $langFieldsError = $entity->validateFieldsLang($unfriendlyError, true);
            $isValid = true === $fieldsError && true === $langFieldsError;

            if($isValid && !$runtimeConfig->shouldValidateData()) {
                $entity->update();
            } else if (!$isValid) {
                $error = true !== $fieldsError ? $fieldsError : '';
                $error .= true !== $langFieldsError ? $langFieldsError : '';

                $this->error($error);
            }
        }
    }

    public function supports($importEntityType) {
        return $importEntityType === 'price';
    }

    /**
     * Funzione che ritorna l'entità che deve essere aggiornata
     * può essere di tipo Product o combiantion
     *
     * @param DataRowInterface $dataRow
     * @return ObjectModel
     */
    private function getEntity($dataRow, $runtimeConfig)
    {
        $entity = null;
        $productId = $this->fetchDataValueByKey($dataRow, $runtimeConfig->getEntityFields(), 'id');
        $productAttributeId = $this->fetchDataValueByKey($dataRow, $runtimeConfig->getEntityFields(), 'id_product_attribute');
        
        if($productAttributeId) {
            $combination = new Combination($productAttributeId, $this->languageId, $this->currentContextShopId);
            if(!$combination->id) {
                $this->error($this->translator->trans('Combination %1$s (ID: %2$s) cannot be saved: Combination do not exists', 
                    [
                        $this->fetchDataValueByKey($dataRow, $runtimeConfig->getEntityFields(), 'name'), 
                        $this->fetchDataValueByKey($dataRow, $runtimeConfig->getEntityFields(), 'id_product_attribute')
                    ], 
                    'Modules.Bulkpriceupdater.Notification'));
                throw new InvalidDataRowException();
            }

            if($combination->id_product != $productId) {
                $this->error($this->translator->trans('Combination %1$s (ID: %2$s) cannot be saved: Combination is linked to product with ID: (%3$s) and not with (%4$s)', [
                    $this->fetchDataValueByKey($dataRow, $runtimeConfig->getEntityFields(), 'name') . $this->fetchDataValueByKey($dataRow, $runtimeConfig->getEntityFields(), 'combination'), 
                    $this->fetchDataValueByKey($dataRow, $runtimeConfig->getEntityFields(), 'id_product_attribute'),
                    $combination->id_product,
                    $productId
                ], 
                'Modules.Bulkpriceupdater.Notification'));
                throw new InvalidDataRowException();
            }

            $entity = $combination;
        } else if($productId) {
            $entity = new Product($productId);
            if(!$entity->id) {
                $this->error($this->translator->trans('%1$s (ID: %2$s) cannot be saved: Product doesn\'t exists', [
                    $this->fetchDataValueByKey($dataRow, $runtimeConfig->getEntityFields(), 'name'), 
                    $this->fetchDataValueByKey($dataRow, $runtimeConfig->getEntityFields(), 'id'),
                ], 
                'Modules.Bulkpriceupdater.Notification')); 
                throw new InvalidDataRowException();
            }
        } else {
            $this->error($this->translator->trans('At least one of id or id_product_attribute must be specified for ', [], 'Modules.Bulkpriceupdater.Notification')); 
            throw new SkippedIterationException();
        }

        return $entity;
    }

    /**
     * Load prices into product object.
     *
     * @param Product $product
     */
    private function loadPrice(Product $product)
    {
        if (isset($product->price_tex) && !isset($product->price_tin)) {
            $product->price = $product->price_tex;
        } elseif (isset($product->price_tin) && !isset($product->price_tex)) {
            $product->price = $product->price_tin;
            // If a tax is already included in price, withdraw it from price
            if ($product->tax_rate) {
                $product->price = (float) number_format($product->price / (1 + $product->tax_rate / 100), 6, '.', '');
            }
        } elseif (isset($product->price_tin) && isset($product->price_tex)) {
            $product->price = $product->price_tex;
        }
    }

    private function loadImpactOnPrice(Combination $combination) {
        if(ObjectModel::existsInDatabase($combination->id_product, 'product')) {
            $product = new Product($combination->id_product);

            if (isset($combination->price_tex) && !isset($combination->price_tin)) {
                $combination->price = $combination->price_tex;
            } elseif (isset($combination->price_tin) && !isset($combination->price_tex)) {
                $combination->price = $combination->price_tin;
                // If a tax is already included in price, withdraw it from price
                if ($combination->tax_rate) {
                    $combination->price = (float) number_format($combination->price / (1 + $combination->tax_rate / 100), 6, '.', '');
                }
            } elseif (isset($combination->price_tin) && isset($combination->price_tex)) {
                $combination->price = $combination->price_tex;
            }

            $combination->price =  $combination->price - (float) $product->price;
        } else {
            throw new InvalidDataRowException();
        }
    }
    
    public function tearDown(ImportConfigInterface $importConfig, ImportRuntimeConfigInterface $runtimeConfig)
    {
        //parent::tearDown($importConfig, $runtimeConfig);

        if ($runtimeConfig->isFinished() && !$runtimeConfig->shouldValidateData()) {
            $this->addPriceImportLog($importConfig);
        }
    }

    /**
     * Function that add a new import Log
     *
     * @param ImportConfigInterface $importConfig
     * @return void
     */
    private function addPriceImportLog($importConfig)
    {        
        if(!empty($this->getErrors())) {
            $status = 0;
        } elseif (!empty($this->getWarnings())) {
            $status = 2;
        } else {
            $status = 1;
        }

        $import = new PriceImportLog();
        $import->setFile($importConfig->getFileName());
        $import->setSkipRows($importConfig->getNumberOfRowsToSkip());
        $import->setColumnSeparator($importConfig->getSeparator());
        $import->setStatus($status);
        $import->updatedTimestamps();

        $this->entityManager->persist($import);
        $this->entityManager->flush();
    }
    
    /**
     * Undocumented function
     *
     * @param string $price
     * @return void
     */
    private function parsePrice($price)
    {
        $priceParsed = (new NumberFormatter($this->translator->getLocale(), NumberFormatter::DECIMAL))->parse($price);
        return $priceParsed ? $priceParsed : $price;
    }
}