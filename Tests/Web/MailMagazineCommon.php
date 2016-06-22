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

namespace Plugin\MailMagazine\Tests\Web;

use Eccube\Common\Constant;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\MailMagazine\Entity\MailmagaCustomer;
use Plugin\MailMagazine\Entity\MailMagazineSendCustomer;
use Plugin\MailMagazine\Entity\MailMagazineSendHistory;
use Plugin\MailMagazine\Entity\MailMagazineTemplate;

class MailMagazineCommon extends AbstractAdminWebTestCase
{
    protected function createMagazineTemplate()
    {
        $fake = $this->getFaker();
        $MailTemplate = new MailMagazineTemplate();

        $MailTemplate
            ->setSubject($fake->word)
            ->setBody($fake->word);
        $MailTemplate->setDelFlg(Constant::DISABLED);
        $this->app['orm.em']->persist($MailTemplate);
        $this->app['orm.em']->flush();

        return $MailTemplate;
    }

    protected function createMailMagazineCustomer()
    {
        $fake = $this->getFaker();
        $current_date = new \DateTime();

        $Sex = $this->app['eccube.repository.master.sex']->find(1);

        $Customer = $this->createCustomer();
        $Customer
            ->setSex($Sex)
            ->setBirth($current_date->modify('-20 years'))
            ->setTel01($fake->randomNumber(3))
            ->setTel02($fake->randomNumber(3))
            ->setTel03($fake->randomNumber(3))
            ->setCreateDate($current_date->modify('-20 days'))
            ->setUpdateDate($current_date->modify('-1 days'))
            ->setLastBuyDate($current_date->modify('-1 days'))
        ;
        $this->app['orm.em']->persist($Customer);
        $this->app['orm.em']->flush();

        // create mail customer
        $MailmagaCustomer = new MailmagaCustomer();
        $MailmagaCustomer
            ->setCustomerId($Customer->getId())
            ->setMailmagaFlg(Constant::ENABLED)
            ->setDelFlg(Constant::DISABLED)
            ->setCreateDate($current_date)
            ->setUpdateDate($current_date);
        $this->app['orm.em']->persist($MailmagaCustomer);
        $this->app['orm.em']->flush();

        return $Customer;

    }

    protected function createSearchForm(\Eccube\Entity\Customer $MailCustomer)
    {
        // create order
        $Order = $this->createOrder($MailCustomer);
        $order_detail = $Order->getOrderDetails();
        $old_date = new \DateTime('1980-01-01');

        return array(
            '_token'            => 'dummy',
            'multi'             => $MailCustomer->getId(),
            'pref'              => $MailCustomer->getPref()->getId(),
            'sex'               => array($MailCustomer->getSex()->getId()),
            'birth_start'       => $old_date->format('Y-m-d'),
            'birth_end'         => $MailCustomer->getBirth()->format('Y-m-d'),
            'tel'               => array('tel01' => $MailCustomer->getTel01(),
                'tel02' => $MailCustomer->getTel02(),
                'tel03' => $MailCustomer->getTel03()),
            'buy_total_start'   => 0,
            'buy_total_end'     => $MailCustomer->getBuyTotal(),
            'buy_times_start'   => 0,
            'buy_times_end'     => $MailCustomer->getBuyTimes(),
            'create_date_start' => $old_date->format('Y-m-d'),
            'create_date_end'   => $MailCustomer->getCreateDate()->format('Y-m-d'),
            'update_date_start' => $old_date->format('Y-m-d'),
            'update_date_end'   => $MailCustomer->getUpdateDate()->format('Y-m-d'),
            'last_buy_start'    => $old_date->format('Y-m-d'),
            'last_buy_end'      => $MailCustomer->getLastBuyDate()->format('Y-m-d'),
            'customer_status'   => array($MailCustomer->getStatus()->getId()),
            'buy_product_code'  => $order_detail[0]->getProductName(),
            'birth_month'       => null,
        );
    }

    protected function createSendHistoy(\Eccube\Entity\Customer $MailCustomer)
    {
        $currentDatetime = new \DateTime();
        $MailTemplate = $this->createMagazineTemplate();
        $formData = $this->createSearchForm($MailCustomer);
        $formData['customer_status'] = $MailCustomer->getStatus();
        $formData['sex'] = $MailCustomer->getSex();
        $formData = array_merge($formData, $formData['tel']);
        unset($formData['tel']);

        // -----------------------------
        // plg_send_history
        // -----------------------------
        $SendHistory = new MailMagazineSendHistory();

        // data
        $SendHistory->setBody($MailTemplate->getBody());
        $SendHistory->setSubject($MailTemplate->getSubject());
        $SendHistory->setSendCount(1);
        $SendHistory->setCompleteCount(1);
        $SendHistory->setDelFlg(Constant::DISABLED);

        $SendHistory->setEndDate(null);
        $SendHistory->setUpdateDate(null);

        $SendHistory->setCreateDate($currentDatetime);
        $SendHistory->setStartDate($currentDatetime);

        // serialize
        $SendHistory->setSearchData(base64_encode(serialize($formData)));

        $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_send_history']->createSendHistory($SendHistory);

        // send customer
        $this->createSendCustomer($SendHistory, $MailCustomer);

        return $SendHistory;
    }

    protected function createSendCustomer(\Plugin\MailMagazine\Entity\MailMagazineSendHistory $SendHistory, \Eccube\Entity\Customer $MailCustomer)
    {
        // -----------------------------
        // plg_send_customer
        // -----------------------------
        $sendId = $SendHistory->getId();

        // Entity
        $SendCustomer = new MailMagazineSendCustomer();

        // data
        $SendCustomer->setSendId($sendId);
        $SendCustomer->setCustomerId($MailCustomer->getId());
        $SendCustomer->setEmail($MailCustomer->getEmail());
        $SendCustomer->setName($MailCustomer->getName01() . " " . $MailCustomer->getName02());

        $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_send_customer']->updateSendCustomer($SendCustomer);
        return $SendCustomer;
    }
}