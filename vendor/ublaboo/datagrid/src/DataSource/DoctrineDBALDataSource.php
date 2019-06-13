<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\DataSource;

use Doctrine\DBAL\Query;
use Doctrine\DBAL\Query\QueryBuilder;
use Nette\SmartObject;
use Nette\Utils\Strings;
use Ublaboo\DataGrid\AggregationFunction\IAggregatable;
use Ublaboo\DataGrid\AggregationFunction\IAggregationFunction;
use Ublaboo\DataGrid\Exception\DataGridDateTimeHelperException;
use Ublaboo\DataGrid\Filter\FilterDate;
use Ublaboo\DataGrid\Filter\FilterDateRange;
use Ublaboo\DataGrid\Filter\FilterMultiSelect;
use Ublaboo\DataGrid\Filter\FilterRange;
use Ublaboo\DataGrid\Filter\FilterSelect;
use Ublaboo\DataGrid\Filter\FilterText;
use Ublaboo\DataGrid\Utils\DateTimeHelper;
use Ublaboo\DataGrid\Utils\Sorting;

/**
 * @method void onDataLoaded(array $result)
 */
class DoctrineDBALDataSource extends FilterableDataSource implements IDataSource, IAggregatable
{

    use SmartObject;

    /**
     * Event called when datagrid data is loaded.
     *
     * @var array|callable[]
     */
    public $onDataLoaded;

    /**
     * @var QueryBuilder
     */
    protected $dataSource;

    /**
     * @var string
     */
    protected $primaryKey;

    /**
     * @var string
     */
    protected $rootAlias;

    /**
     * @var int
     */
    protected $placeholder;


    public function __construct(QueryBuilder $dataSource, string $primaryKey)
    {
        $this->placeholder = count($dataSource->getParameters());
        $this->dataSource = $dataSource;
        $this->primaryKey = $primaryKey;
    }


    public function getQuery(): Query
    {
        return $this->dataSource->getSQL();
    }


    /********************************************************************************
     *                          IDataSource implementation                          *
     ********************************************************************************/

    public function getCount(): int
    {
        $dataSource = clone $this->dataSource;
        $dataSource->resetQueryPart('select');
        $dataSource->select('COUNT(*) AS COUNT_ROWS');
        $dataSource->resetQueryPart('orderBy');
        $res = $dataSource->execute()->fetch();

        return (int)$res['COUNT_ROWS'];
    }


    /**
     * {@inheritDoc}
     */
    public function getData(): array
    {

        $data = $this->dataSource->execute()->fetchAll(\PDO::FETCH_CLASS);

        $this->onDataLoaded($data);

        return $data;
    }


    /**
     * {@inheritDoc}
     */
    public function filterOne(array $condition): IDataSource
    {
        $p = $this->getPlaceholder();

        foreach ($condition as $column => $value) {

            $this->dataSource->andWhere("$column = :$p")
                ->setParameter($p, $value);
        }

        return $this;
    }


    public function limit(int $offset, int $limit): IDataSource
    {
        $this->dataSource->setFirstResult($offset)->setMaxResults($limit);

        return $this;
    }


    public function sort(Sorting $sorting): IDataSource
    {
        if (is_callable($sorting->getSortCallback())) {
            call_user_func(
                $sorting->getSortCallback(),
                $this->dataSource,
                $sorting->getSort()
            );

            return $this;
        }

        $sort = $sorting->getSort();

        if ($sort !== []) {
            foreach ($sort as $column => $order) {
                $this->dataSource->addOrderBy((string)$column, $order);
            }
        } else {
            /**
             * Has the statement already a order by clause?
             */
            if (!(bool)$this->dataSource->getQueryPart('orderBy')) {
                $this->dataSource->orderBy($this->primaryKey);
            }
        }

        return $this;
    }


    /**
     * Get unique int value for each instance class (self)
     */
    public function getPlaceholder(): string
    {
        $return = 'param' . (string)($this->placeholder + 1);

        $this->placeholder++;

        return $return;
    }


    public function processAggregation(IAggregationFunction $function): void
    {
        $function->processDataSource(clone $this->dataSource);
    }


    protected function applyFilterDate(FilterDate $filter): void
    {
        $p1 = $this->getPlaceholder();
        $p2 = $this->getPlaceholder();

        foreach ($filter->getCondition() as $column => $value) {
            try {
                $date = DateTimeHelper::tryConvertToDateTime($value, [$filter->getPhpFormat()]);
                $this->dataSource->andWhere("$column >= :$p1 AND $column <= :$p2")
                    ->setParameter($p1, $date->format('Y-m-d 00:00:00'))
                    ->setParameter($p2, $date->format('Y-m-d 23:59:59'));
            } catch (DataGridDateTimeHelperException $ex) {
                // ignore the invalid filter value
            }
        }
    }


    protected function applyFilterDateRange(FilterDateRange $filter): void
    {
        $conditions = $filter->getCondition();
        $c = $filter->getColumn();

        $valueFrom = $conditions[$filter->getColumn()]['from'];
        $valueTo = $conditions[$filter->getColumn()]['to'];

        if ($valueFrom) {
            try {
                $dateFrom = DateTimeHelper::tryConvertToDate($valueFrom, [$filter->getPhpFormat()]);
                $dateFrom->setTime(0, 0, 0);

                $p = $this->getPlaceholder();

                $this->dataSource->andWhere("$c >= :$p")->setParameter(
                    $p,
                    $dateFrom->format('Y-m-d H:i:s')
                );
            } catch (DataGridDateTimeHelperException $ex) {
                // ignore the invalid filter value
            }
        }

        if ($valueTo) {
            try {
                $dateTo = DateTimeHelper::tryConvertToDate($valueTo, [$filter->getPhpFormat()]);
                $dateTo->setTime(23, 59, 59);

                $p = $this->getPlaceholder();

                $this->dataSource->andWhere("$c <= :$p")->setParameter(
                    $p,
                    $dateTo->format('Y-m-d H:i:s')
                );
            } catch (DataGridDateTimeHelperException $ex) {
                // ignore the invalid filter value
            }
        }
    }


    protected function applyFilterRange(FilterRange $filter): void
    {
        $conditions = $filter->getCondition();
        $c = $filter->getColumn();

        $valueFrom = $conditions[$filter->getColumn()]['from'];
        $valueTo = $conditions[$filter->getColumn()]['to'];

        if ($valueFrom) {
            $p = $this->getPlaceholder();
            $this->dataSource->andWhere("$c >= :$p")->setParameter($p, $valueFrom);
        }

        if ($valueTo) {
            $p = $this->getPlaceholder();
            $this->dataSource->andWhere("$c <= :$p")->setParameter($p, $valueTo);
        }
    }


    protected function applyFilterText(FilterText $filter): void
    {
        $condition = $filter->getCondition();
        $exprs = [];

        foreach ($condition as $column => $value) {

            if ($filter->isExactSearch()) {
                $exprs[] = $this->dataSource->expr()->eq(
                    $column,
                    $this->dataSource->expr()->literal($value)
                );

                continue;
            }

            $words = $filter->hasSplitWordsSearch() === false ? [$value] : explode(' ', $value);

            foreach ($words as $word) {
                $exprs[] = $this->dataSource->expr()->like(
                    $column,
                    $this->dataSource->expr()->literal("%$word%")
                );
            }
        }

        $or = call_user_func_array([$this->dataSource->expr(), 'orX'], $exprs);

        $this->dataSource->andWhere($or);
    }


    protected function applyFilterMultiSelect(FilterMultiSelect $filter): void
    {
        $c = $filter->getColumn();
        $p = $this->getPlaceholder();

        $values = $filter->getCondition()[$filter->getColumn()];
        $expr = $this->dataSource->expr()->in($c, ':' . $p);

        $this->dataSource->andWhere($expr)->setParameter($p, $values);
    }


    protected function applyFilterSelect(FilterSelect $filter): void
    {
        $p = $this->getPlaceholder();

        foreach ($filter->getCondition() as $column => $value) {
            $this->dataSource->andWhere("$column = :$p")
                ->setParameter($p, $value);
        }
    }


    /**
     * {@inheritDoc}
     */
    protected function getDataSource()
    {
        return $this->dataSource;
    }
}
