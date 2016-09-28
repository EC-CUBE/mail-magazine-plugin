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

namespace Plugin\MailMagazine\Tests\Web\Admin;

use Plugin\MailMagazine\Tests\Web\MailMagazineCommon;

class MailMagazineControllerTest extends MailMagazineCommon
{
    /**
     * Test routing
     *
     */
    public function testRoutingMailMagazine()
    {
        $this->client->request('GET',
            $this->app->url('admin_mail_magazine')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testMailMagazineSearch()
    {
        $MaiCustomer =  $this->createMailMagazineCustomer();
        $searchForm = $this->createSearchForm($MaiCustomer);
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_mail_magazine'),
            array('mail_magazine' => $searchForm)
        );

        $this->assertContains('が該当しました', $crawler->filter('h3.box-title')->text());
    }

    public function testSelect()
    {
        $MailTemplate = $this->createMagazineTemplate();

        $this->client->request(
            'POST',
            $this->app->url('admin_mail_magazine_select', array('id' => $MailTemplate->getId())),
            array('mail_magazine' => array(
                'template' => $MailTemplate->getId(),
                'subject'  => $MailTemplate->getSubject(),
                'body'     => $MailTemplate->getBody(),
                '_token'   => 'dummy',
            ))
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testSelect_NotPost()
    {
        $this->setExpectedException('\Symfony\Component\HttpKernel\Exception\BadRequestHttpException');

        $MailTemplate = $this->createMagazineTemplate();
        $this->client->request(
            'GET',
            $this->app->url('admin_mail_magazine_select', array('id' => $MailTemplate->getId())),
            array('mail_magazine' => array(
                'template' => $MailTemplate->getId(),
                'subject'  => $MailTemplate->getSubject(),
                'body'     => $MailTemplate->getBody(),
                '_token'   => 'dummy',
            ))
        );
    }

    public function testConfirm_InValid()
    {
        $MailTemplate = $this->createMagazineTemplate();

        $this->client->request(
            'POST',
            $this->app->url('admin_mail_magazine_confirm', array('id' => $MailTemplate->getId())),
            array('mail_magazine' => array(
                'template' => $MailTemplate->getId(),
                'subject'  => $MailTemplate->getSubject(),
                'body'     => $MailTemplate->getBody(),
                '_token'   => 'dummy',
            ))
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testConfirm()
    {
        $MailTemplate = $this->createMagazineTemplate();

        $this->client->request(
            'POST',
            $this->app->url('admin_mail_magazine_confirm', array('id' => $MailTemplate->getId())),
            array('mail_magazine' => array(
                'id'       => $MailTemplate->getId(),
                'template' => $MailTemplate->getId(),
                'subject'  => $MailTemplate->getSubject(),
                'body'     => $MailTemplate->getBody(),
                '_token'   => 'dummy',
            ))
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testCommit()
    {
        $this->initializeMailCatcher();
        $MailTemplate = $this->createMagazineTemplate();
        $MaiCustomer = $this->createMailMagazineCustomer();
        $searchForm = $this->createSearchForm($MaiCustomer);
        $searchForm['template'] = $MailTemplate->getId();
        $searchForm['subject']  = $MailTemplate->getSubject();
        $searchForm['body']     = $MailTemplate->getBody();

        $this->client->request(
            'POST',
            $this->app->url('admin_mail_magazine_commit', array('id' => $MailTemplate->getId())),
            array('mail_magazine' => $searchForm)
        );

        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_mail_magazine_history')));

//        $Messages = $this->getMailCatcherMessages();
//        $Message = $this->getMailCatcherMessage($Messages[0]->id);
//
//        $this->expected = $searchForm['subject'];
//        $this->actual = $Message->subject;
//        $this->verify();
        $this->cleanUpMailCatcherMessages();
    }
}