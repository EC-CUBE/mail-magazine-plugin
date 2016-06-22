<?php
/**
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\MailMagazine\Tests\Repository;


use Eccube\Entity\Master\CustomerStatus;
use Plugin\MailMagazine\Tests\Web\MailMagazineCommon;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class MailMagazineCustomerRepositoryTest extends MailMagazineCommon
{
    public function testLoadUserByUsername()
    {
//        $this->setExpectedException('\Symfony\Component\Security\Core\Exception\UsernameNotFoundException');
        try {
            $username = 'a@a.com';
            $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_customer']->loadUserByUsername($username);
        } catch (UsernameNotFoundException $e) {
            $this->assertEquals($e->getMessage(), sprintf('Username "%s" does not exist.', $username));
            return;
        }
        $this->fail(sprintf('Username "%s" does not exist.', $username));
    }

    public function testLoadUserByUsername_User()
    {
        $Customer = $this->createMailMagazineCustomer();
        $username = $Customer->getEmail();
        $Result = $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_customer']->loadUserByUsername($username);

        $this->expected = $username;
        $this->actual = $Result->getEmail();
        $this->verify();
    }

    public function testGetNonActiveCustomerBySecretKey()
    {
        $Customer = $this->createMailMagazineCustomer();
        $Status = $this->app['orm.em']->getRepository('Eccube\Entity\Master\CustomerStatus')->find(CustomerStatus::NONACTIVE);
        $secretNewKey = $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_customer']->getUniqueSecretKey($this->app);
        $Customer->setStatus($Status)
            ->setSecretKey($secretNewKey);

        $this->app['orm.em']->persist($Customer);
        $this->app['orm.em']->flush();

        $secretKey = $Customer->getSecretKey();
        $result = $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_customer']->getNonActiveCustomerBySecretKey($secretKey);

        $this->actual = $result->getId();
        $this->expected = $Customer->getId();
        $this->verify();
    }

    public function testGetActiveCustomerByEmail()
    {
        $Customer = $this->createMailMagazineCustomer();

        $result = $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_customer']->getActiveCustomerByEmail($Customer->getEmail());

        $this->actual = $result->getId();
        $this->expected = $Customer->getId();
        $this->verify();
    }

    public function testGetActiveCustomerByResetKey()
    {
        $Customer = $this->createMailMagazineCustomer();
        $now = new \DateTime();
        $reset_key = $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_customer']->getUniqueResetKey($this->app);
        $Customer->setResetKey($reset_key)
            ->setResetExpire($now->modify('+10 days'));

        $this->app['orm.em']->persist($Customer);
        $this->app['orm.em']->flush();

        $result = $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_customer']->getActiveCustomerByResetKey($reset_key);

        $this->actual = $result->getId();
        $this->expected = $Customer->getId();
        $this->verify();
    }

    public function testGetResetPassword()
    {
        $result = $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_customer']->getResetPassword();
        $this->assertNotEmpty($result);
    }

}