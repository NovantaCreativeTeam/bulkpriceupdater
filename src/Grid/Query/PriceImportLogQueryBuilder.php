<?php 

namespace Novanta\BulkPriceUpdater\Grid\Query;

use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Query\DoctrineSearchCriteriaApplicator;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

final class PriceImportLogQueryBuilder extends AbstractDoctrineQueryBuilder {

    private $searchCriteriaApplicator;

    public function __construct(
        Connection $connection,
        $dbPrefix,
        DoctrineSearchCriteriaApplicator $criteriaApplicator
    )
    {
        parent::__construct($connection, $dbPrefix);

        $this->searchCriteriaApplicator = $criteriaApplicator;
    }

    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria = null)
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
            $qb->setParameter($filterName, '%'.$filterValue.'%');
        }

        return $qb;
    }
}