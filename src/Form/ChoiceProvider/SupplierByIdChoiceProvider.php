<?php

namespace Novanta\BulkPriceUpdater\Form\ChoiceProvider;

use PrestaShop\PrestaShop\Adapter\Supplier\SupplierDataProvider;
use PrestaShop\PrestaShop\Core\Form\FormChoiceProviderInterface;

class SupplierByIdChoiceProvider implements FormChoiceProviderInterface {
    
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