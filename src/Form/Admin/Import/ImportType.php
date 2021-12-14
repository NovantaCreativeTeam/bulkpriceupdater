<?php

namespace Novanta\BulkPriceUpdater\Form\Admin\Import;

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
                'required' => true
            ])
            ->add('skip', SwitchType::class, [
                'label' => $this->trans('First Row Heading', 'Modules.Bulkpriceupdater.Admin'),
                'required' => true,
                'empty_data' => 1
            ])
            ->add('separator', TextType::class, [
                'label' => $this->trans('Column separator', 'Modules.Bulkpriceupdater.Admin'),
                'required' => true,
                'empty_data' => ImportSettings::DEFAULT_SEPARATOR
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
