<?php

namespace Novanta\BulkPriceUpdater\Controller\Admin;

use Doctrine\ORM\EntityRepository;
use Novanta\BulkPriceUpdater\Entity\PriceImportLog;
use Novanta\BulkPriceUpdater\Form\Admin\Import\ImportType;
use Novanta\BulkPriceUpdater\Grid\Definition\Factory\PriceImportLogDefinitionFactory;
use Novanta\BulkPriceUpdater\Search\Filters\PriceImportLogFilters;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteria;
use PrestaShop\PrestaShop\Core\Import\Configuration\ImportConfig;
use PrestaShop\PrestaShop\Core\Import\Exception\ImportException;
use PrestaShop\PrestaShop\Core\Import\Exception\UnavailableImportFileException;
use PrestaShop\PrestaShop\Core\Import\Exception\UnreadableFileException;
use PrestaShop\PrestaShop\Core\Import\ImportDirectory;
use PrestaShop\PrestaShop\Core\Import\Importer;
use PrestaShop\PrestaShop\Core\Import\ImportSettings;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Exception\FileUploadException;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class ImportController extends FrameworkBundleAdminController
{

    public function indexAction(Request $request, PriceImportLogFilters $filters)
    {
        /**
         * Utilizzo questo modo e non this->createForm perchè non voglio che i campi
         * sia rinominati in import[nome_campo], questo perchè così possi riutilizzare
         * la logcia del componente di importazione che crea la form con prestashop.admin.import.form_builder
         */
        $importForm = $this->get('form.factory')->createNamed(
            '',
            ImportType::class,
            $this->getImportDefaults(),
            [
                'attr' => [
                    'id' => 'import-prices-form'
                ]
            ]
        );

        $importForm->handleRequest($request);

        if ($importForm->isSubmitted() && $importForm->isValid()) {
            $this->addFlash('success', $this->trans('Products Imported correctly', 'Modules.Bulkpriceupdater.Admin'));
        }

        $priceImportLogGridFactory = $this->get('novanta.bulkpriceupdater.grid.priceimportlog_factory');
        $priceImportLogGrid = $priceImportLogGridFactory->getGrid($filters);

        return $this->render(
            '@Modules/bulkpriceupdater/views/templates/admin/import/import.html.twig',
            [
                'layoutTitle' => $this->trans('Import Products', 'Modules.Bulkpriceupdater.Admin'),
                'importForm' => $importForm->createView(),
                'import_url'  => $this->generateUrl('admin_bulkpriceupdater_import_process'),
                'importFileUploadUrl' => $this->generateUrl('admin_bulkpriceupdater_import_upload'),
                'maxFileUploadSize' => $this->get('prestashop.core.configuration.ini_configuration')->getPostMaxSizeInBytes(),
                'priceImportLogGrid' => $this->presentGrid($priceImportLogGrid),
            ]
        );
    }

    /**
     * Funzione che effettua l'upload del file di aggiornamento
     * Viene invocata tramite Js
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadAction(Request $request)
    {
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile instanceof UploadedFile) {
            return $this->json([
                'error' => $this->trans('No file was uploaded.', 'Admin.Advparameters.Notification'),
            ]);
        }

        try {
            $fileUploader = $this->get('prestashop.core.import.file_uploader');
            $file = $fileUploader->upload($uploadedFile);
        } catch (FileUploadException $e) {
            return $this->json(['error' => $e->getMessage()]);
        }

        $response['file'] = [
            'name' => $file->getFilename(),
            'size' => $file->getSize(),
        ];

        return $this->json($response);
    }

    /**
     * Funzione che effettua l'importazione dei nuovi prezzi
     * Viene invocata tramite Ajax
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function processImportAction(Request $request)
    {
        $errors = [];
        $requestValidator = $this->get('prestashop.core.import.request_validator');

        try {
            $requestValidator->validate($request);
        } catch (UnavailableImportFileException $e) {
            $errors[] = $this->trans('To proceed, please upload a file first.', 'Admin.Advparameters.Notification');
        }

        if (!empty($errors)) {
            return $this->json([
                'errors' => $errors,
                'isFinished' => true,
            ]);
        }

        /** @var Importer $importer */
        $importer = $this->get('prestashop.core.import.importer');
        $importConfigFactory = $this->get('prestashop.core.import.config_factory');
        $runtimeConfigFactory = $this->get('prestashop.core.import.runtime_config_factory');

        // Setto il mapping del csv manualmente, il file deve avere questi campi
        $request->request->set('type_value', array('id', 'id_product_attribute', 'reference', 'name', 'combination', 'no', 'price_tex'));

        $importConfig = $importConfigFactory->buildFromRequest($request);
        $runtimeConfig = $runtimeConfigFactory->buildFromRequest($request);

        $importer->import(
            $importConfig,
            $runtimeConfig,
            $this->get('novanta.bulkpriceupdater.adapter.import.handler.price')
        );

        return $this->json($runtimeConfig->toArray());
    }

    /**
     * Funzione che effettua il revert di una importazione
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function processRevertAction(Request $request)
    {
        $errors = [];
        $entityManager = $this->get('doctrine.orm.entity_manager');

        /** @var EntityRepository $logRepository */
        $logRepository = $entityManager->getRepository(PriceImportLog::class);
        /** @var PriceImportLog $logToRevert */
        $logToRevert = $logRepository->findOneBy(['id' => $request->get('id')]);
        if (!$logToRevert) {
            $errors[] = $this->trans('', 'Modules.Bulkpriceupdater.Notification');
        }

        if (!empty($errors)) {
            return $this->json([
                'errors' => $errors,
                'isFinished' => true,
            ]);
        }

        // Costruisco la richiesta di importazione sulla base del log e dei dati di runtime
        $defaults = $this->getImportDefaults();
        $request->request->set('type_value', array('id', 'id_product_attribute', 'reference', 'name', 'combination', 'price_tex', 'no'));

        $importer = $this->get('prestashop.core.import.importer');
        $runtimeConfigFactory = $this->get('prestashop.core.import.runtime_config_factory');

        // Non uso la classe factory poichè quella crea dalla richiesta, in questo caso la devo creare a partire dall'entità
        $importConfig = new ImportConfig(
            $logToRevert->getFile(),
            $defaults['entity'],
            $defaults['iso_lang'],
            $logToRevert->getColumnSeparator(),
            $defaults['multiple_value_separator'],
            $defaults['truncate'],
            $defaults['regenerate'],
            $defaults['match_ref'],
            $defaults['forceIDs'],
            $defaults['sendemail'],
            $logToRevert->getSkipRows()
        );

        $runtimeConfig = $runtimeConfigFactory->buildFromRequest($request);

        try {
            $importer->import(
                $importConfig,
                $runtimeConfig,
                $this->get('novanta.bulkpriceupdater.adapter.import.handler.price')
            );
    
            return $this->json($runtimeConfig->toArray());
        } catch (UnreadableFileException $e) {
            $errors[] = $this->trans('Impossible to read this file, revert aborted', 'Modules.Bulkpriceupdater.Notification');
            return $this->json([
                'errors' => $errors,
                'isFinished' => true,
            ]);
        } catch (ImportException $e) {
            $errors[] = $this->trans('Impossible to revert:' . $e->getMessage(), 'Modules.Bulkpriceupdater.Notification');
            return $this->json([
                'errors' => $errors,
                'isFinished' => true,
            ]);
        }
        
    }

    /**
     * Funzione che si occupa di gestire l'azione di ricerca della grid
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function searchAction(Request $request)
    {
        $responseBuilder = $this->get('prestashop.bundle.grid.response_builder');

        return $responseBuilder->buildSearchResponse(
            $this->get('novanta.bulkpriceupdater.grid.definition.factory.priceimportlog'),
            $request,
            PriceImportLogDefinitionFactory::GRID_ID,
            'admin_bulkpriceupdater_import_index'
        );
    }

    /**
     * Funzione che effettua il download del file di importazione di un log
     *
     * @param Request $request
     * @return Response
     */
    public function downloadAction(Request $request)
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');

        /** @var EntityRepository $logRepository */
        $logRepository = $entityManager->getRepository(PriceImportLog::class);
        /** @var PriceImportLog $logToRevert */
        $log = $logRepository->findOneBy(['id' => $request->get('id')]);
        /** @var ImportDirectory $importDir */
        $importDir = $this->get('prestashop.core.import.dir');

        try {
            $file = new File($importDir->getDir() . $log->getFile());
            return $this->file($file);
        } catch (FileNotFoundException $e) {
            $this->addFlash('error', $this->trans('Impossible to download the file, it doesn\'t exists', 'Modules.Bulkpriceupdater.Admin'));
            return $this->redirectToRoute('admin_bulkpriceupdater_import_index');
        }
    }


    /**
     * Funzione che ritorna i valori di Default per la ImportConfig
     *
     * @return array
     */
    private function getImportDefaults()
    {
        return [
            'skip' => 1,
            'sendemail' => false,
            'forceIDs' => false,
            'match_ref' => false,
            'regenerate' => false,
            'truncate' => false,
            'multiple_value_separator' => ImportSettings::DEFAULT_MULTIVALUE_SEPARATOR,
            'separator' => ImportSettings::DEFAULT_SEPARATOR,
            'iso_lang' => 'it',
            'entity' => 1,
            'price_tin' => false
        ];
    }
}
