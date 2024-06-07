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

namespace Novanta\BulkPriceUpdater\Form\ChoiceProvider;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Adapter\Supplier\SupplierDataProvider;
use PrestaShop\PrestaShop\Core\Form\FormChoiceProviderInterface;

class SupplierByIdChoiceProvider implements FormChoiceProviderInterface
{
    private $supplierProvider;
    private $languageId;

    public function __construct(SupplierDataProvider $supplierDataProvider, int $contextLangId)
    {
        $this->supplierProvider = $supplierDataProvider;
        $this->languageId = $contextLangId;
    }

    public function getChoices()
    {
        $choices = [];
        $suppliers = $this->supplierProvider->getSuppliers(false, $this->languageId);

        foreach ($suppliers as $supplier) {
            $choices[$supplier['name']] = $supplier['id_supplier'];
        }

        return $choices;
    }
}
