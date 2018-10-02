<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\MailMagazine4\Repository;

use Eccube\Repository\AbstractRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Plugin\MailMagazine4\Entity\MailMagazineTemplate;

/**
 * MailMagazine.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MailMagazineTemplateRepository extends AbstractRepository
{
    /**
     * MailMagazineTemplateRepository constructor.
     *
     * @param \Doctrine\Common\Persistence\ManagerRegistry $registry
     * @param string $entityClass
     */
    public function __construct(ManagerRegistry $registry, $entityClass = MailMagazineTemplate::class)
    {
        parent::__construct($registry, $entityClass);
    }

    /**
     * find all.
     *
     * @return array
     */
    public function findAll()
    {
        $query = $this
            ->getEntityManager()
            ->createQuery('SELECT m FROM Plugin\MailMagazine4\Entity\MailMagazineTemplate m ORDER BY m.id DESC');
        $result = $query
            ->getResult(Query::HYDRATE_ARRAY);

        return $result;
    }
}
