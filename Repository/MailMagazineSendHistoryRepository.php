<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * https://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\MailMagazine44\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\MailMagazine44\Entity\MailMagazineSendHistory;
use Eccube\Doctrine\Query\Queries;
use Doctrine\ORM\QueryBuilder;

/**
 * SendHistoryRepository.
 *
 * @extends AbstractRepository<MailMagazineSendHistory>
 */
class MailMagazineSendHistoryRepository extends AbstractRepository
{
    /**
     * @var Queries
     */
    protected Queries $queries;

    /**
     * MailMagazineSendHistoryRepository constructor.
     *
     * @param Queries $queries
     * @param ManagerRegistry $registry
     * @param string $entityClass
     */
    public function __construct(
        Queries $queries,
        ManagerRegistry $registry,
        string $entityClass = MailMagazineSendHistory::class
    ) {
        parent::__construct($registry, $entityClass);
        $this->queries = $queries;
    }

    /**
     * @param array $searchData
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderBySearchData(array $searchData = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('sh')
            ->select('sh');

        // Order By
        $qb->addOrderBy('sh.start_date', 'DESC');

        return $this->queries->customize($this->getQueryKey(), $qb, $searchData);
    }

    /**
     * Get query key
     *
     * @return string
     */
    public function getQueryKey(): string
    {
        return 'MailMagazineSendHistory.getQueryBuilderBySearchData';
    }
}
