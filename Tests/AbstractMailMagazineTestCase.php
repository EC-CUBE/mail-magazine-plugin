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

namespace Plugin\MailMagazine44\Tests;

use Eccube\Entity\Customer;
use Eccube\Tests\Service\AbstractServiceTestCase;
use Plugin\MailMagazine44\Entity\MailMagazineSendHistory;
use Plugin\MailMagazine44\Repository\MailMagazineSendHistoryRepository;
use Plugin\MailMagazine44\Service\MailMagazineService;

abstract class AbstractMailMagazineTestCase extends AbstractServiceTestCase
{
    /**
     * @var MailMagazineService
     */
    protected $mailMagazineService;

    /**
     * @var MailMagazineSendHistoryRepository
     */
    protected $mailMagazineSendHistoryRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->mailMagazineSendHistoryRepository = $this->entityManager->getRepository(MailMagazineSendHistory::class);
    }

    /**
     * Create customer + mail magazine flag
     *
     * @param string $email
     * @param string $name01
     * @param string $name02
     *
     * @return Customer
     */
    protected function createMailmagaCustomer(string $email = 'mail_magazine_service_test@example.com', string $name01 = 'name01', string $name02 = 'name02'): Customer
    {
        $c = $this->createCustomer($email);
        if ($name01) {
            $c->setName01($name01);
        }
        if ($name02) {
            $c->setName02($name02);
        }
        $c->setMailmagaFlg(1);

        $this->entityManager->persist($c);
        $this->entityManager->flush();

        return $c;
    }
}
