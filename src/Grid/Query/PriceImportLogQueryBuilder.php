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

namespace Novanta\BulkPriceUpdater\Grid\Query;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Query\DoctrineSearchCriteriaApplicator;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

final class PriceImportLogQueryBuilder extends AbstractDoctrineQueryBuilder
{
    private $searchCriteriaApplicator;

    public function __construct(
        Connection $connection,
        $dbPrefix,
        DoctrineSearchCriteriaApplicator $criteriaApplicator
    ) {
        parent::__construct($connection, $dbPrefix);

        $this->searchCriteriaApplicator = $criteriaApplicator;
    }

    public function getSearchQueryBuilder(?SearchCriteriaInterface $searchCriteria = null)
    {
        $qb = $this->getQueryBuilder($searchCriteria->getFilters());
        $qb->select(
            'pil.id_price_import',
            'pil.file',
            'pil.status',
            'pil.date_add',
            'pil.status as severity');

        $this->searchCriteriaApplicator->applyPagination($searchCriteria, $qb);
        $this->searchCriteriaApplicator->applySorting($searchCriteria, $qb);

        return $qb;
    }

    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $qb = $this->getQueryBuilder($searchCriteria->getFilters());
        $qb->select('COUNT(pil.id_price_import)');

        return $qb;
    }

    private function getQueryBuilder(array $filters)
    {
        $qb = $this->connection
            ->createQueryBuilder()
            ->from($this->dbPrefix . 'price_import_log', 'pil');

        foreach ($filters as $filterName => $filterValue) {
            if ('id_price_import' === $filterName) {
                $qb->andWhere("pil.id_price_import = :$filterName");
                $qb->setParameter($filterName, $filterValue);

                continue;
            }

            if ('date_add' === $filterName) {
                if (isset($filterValue['from'])) {
                    $qb->andWhere('pil.date_add >= :date_from');
                    $qb->setParameter('date_from', sprintf('%s %s', $filterValue['from'], '0:0:0'));
                }

                if (isset($filterValue['to'])) {
                    $qb->andWhere('pil.date_add <= :date_to');
                    $qb->setParameter('date_to', sprintf('%s %s', $filterValue['to'], '23:59:59'));
                }

                continue;
            }

            $qb->andWhere("$filterName LIKE :$filterName");
            $qb->setParameter($filterName, '%' . $filterValue . '%');
        }

        return $qb;
    }
}
