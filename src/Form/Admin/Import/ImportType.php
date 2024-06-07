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

namespace Novanta\BulkPriceUpdater\Form\Admin\Import;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Import\ImportSettings;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ImportType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file', FileType::class, [
                'label' => $this->trans('Prices File', 'Modules.Bulkpriceupdater.Admin'),
                'required' => true,
            ])
            ->add('skip', SwitchType::class, [
                'label' => $this->trans('First Row Heading', 'Modules.Bulkpriceupdater.Admin'),
                'required' => true,
                'empty_data' => 1,
            ])
            ->add('separator', TextType::class, [
                'label' => $this->trans('Column separator', 'Modules.Bulkpriceupdater.Admin'),
                'required' => true,
                'empty_data' => ImportSettings::DEFAULT_SEPARATOR,
            ])

            ->add('entity', HiddenType::class)
            ->add('iso_lang', HiddenType::class)
            ->add('multiple_value_separator', HiddenType::class)
            ->add('truncate', HiddenType::class)
            ->add('regenerate', HiddenType::class)
            ->add('match_ref', HiddenType::class)
            ->add('forceIDs', HiddenType::class)
            ->add('sendemail', HiddenType::class)
            ->add('csv', HiddenType::class);
    }
}
