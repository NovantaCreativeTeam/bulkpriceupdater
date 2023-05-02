<?php

namespace Novanta\BulkPriceUpdater\Form\Admin\Export;

use Novanta\BulkPriceUpdater\Form\ChoiceProvider\SupplierByIdChoiceProvider;
use PrestaShop\PrestaShop\Adapter\Category\CategoryDataProvider;
use PrestaShopBundle\Form\Admin\Type\CategoryChoiceTreeType;
use PrestaShopBundle\Form\Admin\Type\Material\MaterialChoiceTableType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ExportType extends AbstractType {
    
    private $translator;
    private $categoryProvider;
    private $supplierChoiceProvider;
    private $languageId;

    public function __construct(
        TranslatorInterface $translator,
        CategoryDataProvider $categoryDataProvider,
        SupplierByIdChoiceProvider $supplierChoiceDataProvider,
        $languageId)
    {
        $this->translator = $translator;
        $this->categoryProvider = $categoryDataProvider;
        $this->supplierChoiceProvider = $supplierChoiceDataProvider;
        $this->$languageId = $languageId;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('category_ids', CategoryChoiceTreeType::class, [
            'label' => $this->translator->trans('Categories', [], 'Modules.Bulkpriceupdater.Admin'),
            'choices_tree' => $this->categoryProvider->getNestedCategories(null, $this->languageId, true),
            'multiple' => true
        ]);

        $builder->add('supplier_ids', MaterialChoiceTableType::class, [
            'label' => $this->translator->trans('Suppliers', [], 'Modules.Bulkpriceupdater.Admin'),
            'choices' => $this->supplierChoiceProvider->getChoices()
        ]);

        $builder->add('only_active', SwitchType::class, [
            'label' => $this->translator->trans('Export only active', [], 'Modules.Bulkpriceupdater.Admin'),
            'choices' => [
                'No' => "0",
                'Yes' => "1",
            ],
        ]);

        $builder->add('export_name', TextType::class, [
            'label' => $this->translator->trans('Export Name', [], 'Modules.Bulkpriceupdater.Admin'),
            'required' => false
        ]);
    }
}