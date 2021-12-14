<?php

namespace Novanta\BulkPriceUpdater\Domain\Product\QueryHandler;

use Novanta\BulkPriceUpdater\Domain\Product\Query\GetProductsForBulkPriceUpdate;

interface GetProductsForBulkPriceUpdateHandlerInterface {    
    public function handle(GetProductsForBulkPriceUpdate $query);
}