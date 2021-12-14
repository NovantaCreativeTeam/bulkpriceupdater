<?php

namespace Novanta\BulkPriceUpdater\Search\Filters;

use Novanta\BulkPriceUpdater\Grid\Definition\Factory\PriceImportLogDefinitionFactory;
use PrestaShop\PrestaShop\Core\Search\Filters;

/**
 * Class BackupFilters defines filters for 'Configure > Advanced Parameters > Database > Backup' listing.
 */
final class PriceImportLogFilters extends Filters
{
    protected $filterId = PriceImportLogDefinitionFactory::GRID_ID;

    /**
     * {@inheritdoc}
     */
    public static function getDefaults()
    {
        return [
            'limit' => 20,
            'offset' => 0,
            'orderBy' => 'date_add',
            'sortOrder' => 'DESC',
            'filters' => [],
        ];
    }
}
