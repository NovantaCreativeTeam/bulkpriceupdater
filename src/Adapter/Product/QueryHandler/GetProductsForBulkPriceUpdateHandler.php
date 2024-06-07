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

namespace Novanta\BulkPriceUpdater\Adapter\Product\QueryHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Novanta\BulkPriceUpdater\Domain\Product\Query\GetProductsForBulkPriceUpdate;
use Novanta\BulkPriceUpdater\Domain\Product\QueryHandler\GetProductsForBulkPriceUpdateHandlerInterface;
use PrestaShop\PrestaShop\Adapter\LegacyContext;

class GetProductsForBulkPriceUpdateHandler implements GetProductsForBulkPriceUpdateHandlerInterface
{
    private $connection;
    private $databasePrefix;
    private $context;

    public function __construct(
        Connection $connection,
        $databasePrefix,
        LegacyContext $context
    ) {
        $this->connection = $connection;
        $this->databasePrefix = $databasePrefix;
        $this->context = $context;
    }

    public function handle(GetProductsForBulkPriceUpdate $query)
    {
        // 0. Recupero i filtri
        $filters = $query->getFilters();

        // 1. Creo la query
        $language_id = $this->context->getLanguage()->id;

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select("
                p.id_product, 
                IFNULL(pa.id_product_attribute, '0') as id_product_attribute,
                IF(pa.reference IS NULL OR pa.reference = '', p.reference, pa.reference) as reference,
                pl.name,
                IFNULL(GROUP_CONCAT(DISTINCT CONCAT(agl.public_name, ':', al.name) ORDER BY ag.position), '') as combination,
                ROUND(ROUND(p.price, 2) + ifnull(pa.price, 0), 2) as price")
            ->from($this->databasePrefix . 'product', 'p')
            ->innerJoin('p', $this->databasePrefix . 'product_lang', 'pl', 'p.id_product = pl.id_product and pl.id_lang = :idLang')
            ->leftJoin('p', $this->databasePrefix . 'product_attribute', 'pa', 'p.id_product = pa.id_product')
            ->leftJoin('pa', $this->databasePrefix . 'product_attribute_combination', 'pac', 'pa.id_product_attribute = pac.id_product_attribute')
            ->leftJoin('pac', $this->databasePrefix . 'attribute', 'a', 'pac.id_attribute = a.id_attribute')
            ->leftJoin('a', $this->databasePrefix . 'attribute_lang', 'al', 'a.id_attribute = al.id_attribute AND al.id_lang = :idLang')
            ->leftJoin('a', $this->databasePrefix . 'attribute_group', 'ag', 'a.id_attribute_group = ag.id_attribute_group')
            ->leftJoin('ag', $this->databasePrefix . 'attribute_group_lang', 'agl', 'ag.id_attribute_group = agl.id_attribute_group AND agl.id_lang = :idLang')
            ->leftJoin('p', $this->databasePrefix . 'category_product', 'cp', 'cp.id_product = p.id_product')
            ->leftJoin('p', $this->databasePrefix . 'product_supplier', 'ps', 'p.id_product = ps.id_product and IFNULL(pa.id_product_attribute, 0) = ps.id_product_attribute')
            ->groupBy('p.id_product, pa.id_product_attribute');

        if (\array_key_exists('id_supplier', $filters) && !empty($filters['id_supplier'])) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('ps.id_supplier', $filters['id_supplier']));
        }

        if (\array_key_exists('id_category', $filters) && !empty($filters['id_category'])) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('cp.id_category', $filters['id_category']));
        }

        if (\array_key_exists('only_active', $filters) && $filters['only_active']) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('p.active', 1));
        }

        $queryBuilder->setParameter('idLang', $language_id);

        // 2. Restituisco i risultati
        return $queryBuilder->execute()->fetchAll();
    }
}
