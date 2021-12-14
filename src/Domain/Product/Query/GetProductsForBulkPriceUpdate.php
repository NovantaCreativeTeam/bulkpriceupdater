<?php 

namespace Novanta\BulkPriceUpdater\Domain\Product\Query;

class GetProductsForBulkPriceUpdate {
    
    private $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function getFilters() 
    {
        return $this->filters;
    }
}