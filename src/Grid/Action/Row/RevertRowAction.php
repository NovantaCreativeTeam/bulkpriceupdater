<?php

namespace Novanta\BulkPriceUpdater\Grid\Action\Row;

use PrestaShop\PrestaShop\Core\Grid\Action\Row\AbstractRowAction;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\AccessibilityChecker\AccessibilityCheckerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RevertRowAction extends AbstractRowAction {
    public function getType()
    {
        return 'revert_import';    
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired([
                'route',
                'route_param_name',
                'route_param_field',
            ])
            ->setDefaults([
                'method' => 'POST',
                'confirm_message' => '',
                'accessibility_checker' => null,
                'use_inline_display' => true
            ])
            ->setAllowedTypes('route', 'string')
            ->setAllowedTypes('route_param_name', 'string')
            ->setAllowedTypes('route_param_field', 'string')
            ->setAllowedTypes('method', 'string')
            ->setAllowedTypes('confirm_message', 'string')
            ->setAllowedTypes('accessibility_checker', [AccessibilityCheckerInterface::class, 'callable', 'null']);
    }
}