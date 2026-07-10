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

namespace Plugin\MailMagazine44\Tests\Web\Admin;

use Plugin\MailMagazine44\Tests\Web\MailMagazineCommon;

class MailMagazineHistoryControllerTest extends MailMagazineCommon
{
    public function testIndex(): void
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_history')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testPreview(): void
    {
        $MailCustomer = $this->createMailMagazineCustomer();
        $SendHistory = $this->createSendHistoy($MailCustomer);

        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_history_preview', ['id' => $SendHistory->getId()])
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testPreviewIdIncorrect(): void
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_history_preview', ['id' => 9999999])
        );

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testPreviewIdIsNull(): void
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_history_preview', ['id' => null])
        );

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testCondition(): void
    {
        $MailCustomer = $this->createMailMagazineCustomer();
        $SendHistory = $this->createSendHistoy($MailCustomer);

        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_history_condition', ['id' => $SendHistory->getId()])
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testConditionIdIncorrect(): void
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_history_condition', ['id' => 9999999])
        );

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testConditionIdIsNull(): void
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_history_condition', ['id' => null])
        );
        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testDelete(): void
    {
        $MailCustomer = $this->createMailMagazineCustomer();
        $SendHistory = $this->createSendHistoy($MailCustomer);

        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_history_delete', ['id' => $SendHistory->getId()])
        );

        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('plugin_mail_magazine_history')));
    }

    public function testDeleteIdIncorrect(): void
    {
        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_history_delete', ['id' => 9999999])
        );

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testDeleteIdIsNull(): void
    {
        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_history_delete', ['id' => null])
        );
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteNotPost(): void
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_history_delete', ['id' => null])
        );
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }
}
