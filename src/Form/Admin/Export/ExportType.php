<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace Novanta\BulkPriceUpdater\Form\Admin\Export;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Novanta\BulkPriceUpdater\Form\ChoiceProvider\SupplierByIdChoiceProvider;
use PrestaShop\PrestaShop\Adapter\Category\CategoryDataProvider;
use PrestaShopBundle\Form\Admin\Type\CategoryChoiceTreeType;
use PrestaShopBundle\Form\Admin\Type\Material\MaterialChoiceTableType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ExportType extends AbstractType
{
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
            'multiple' => true,
        ]);

        $builder->add('supplier_ids', MaterialChoiceTableType::class, [
            'label' => $this->translator->trans('Suppliers', [], 'Modules.Bulkpriceupdater.Admin'),
            'choices' => $this->supplierChoiceProvider->getChoices(),
        ]);

        $builder->add('only_active', SwitchType::class, [
            'label' => $this->translator->trans('Export only active', [], 'Modules.Bulkpriceupdater.Admin'),
            'choices' => [
                'No' => '0',
                'Yes' => '1',
            ],
        ]);

        $builder->add('export_name', TextType::class, [
            'label' => $this->translator->trans('Export Name', [], 'Modules.Bulkpriceupdater.Admin'),
            'required' => false,
        ]);
    }
}
