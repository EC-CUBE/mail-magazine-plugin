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

use Plugin\MailMagazine44\Entity\MailMagazineTemplate;
use Plugin\MailMagazine44\Tests\Web\MailMagazineCommon;

class MailMagazineTemplateControllerTest extends MailMagazineCommon
{
    /**
     * @var MailMagazineTemplate
     */
    protected $mailMagaTemplateRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->mailMagaTemplateRepository = $this->entityManager->getRepository(MailMagazineTemplate::class);
    }

    protected function createFormData()
    {
        $fake = $this->getFaker();

        return [
            'subject' => $fake->word,
            'body' => $fake->word,
            'htmlBody' => $fake->word,
            '_token' => 'dummy',
        ];
    }

    /**
     * Test routing.
     */
    public function testRoutingMailMagazineTemplate(): void
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_template')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testRegist(): void
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_template_regist')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testEdit(): void
    {
        $MailTemplate = $this->createMagazineTemplate();

        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_template_edit', ['id' => $MailTemplate->getId()])
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testEditIdIsNull(): void
    {
        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_template_edit', ['id' => null])
        );

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testEditIdIncorrect(): void
    {
        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_template_edit', ['id' => 9999999])
        );

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testEditNotPost(): void
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_template_edit', ['id' => null])
        );
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testCommitFormInvalid(): void
    {
        $form = $this->createFormData();
        unset($form['subject']);

        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_template_commit'),
            ['mail_magazine_template_edit' => $form]
        );
        $this->assertTrue(true);
    }

    public function testCommitEditIdIncorrect(): void
    {
        $form = $this->createFormData();

        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_template_commit', ['id' => 9999999]),
            ['mail_magazine_template_edit' => $form]
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('plugin_mail_magazine_template')));
    }

    public function testCommitEditIdIsZero(): void
    {
        $form = $this->createFormData();

        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_template_commit', ['id' => 0]),
            ['mail_magazine_template_edit' => $form]
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('plugin_mail_magazine_template')));
    }

    public function testCommitRegist(): void
    {
        $form = $this->createFormData();

        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_template_commit'),
            ['mail_magazine_template_edit' => $form]
        );

        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('plugin_mail_magazine_template')));
        $MailTemplate = $this->mailMagaTemplateRepository->findOneBy(['subject' => $form['subject']]);
        $this->actual = $MailTemplate->getBody();
        $this->expected = $form['body'];
        $this->verify();
    }

    public function testCommitEdit(): void
    {
        $MailTemplate = $this->createMagazineTemplate();

        $form = $this->createFormData();

        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_template_commit', ['id' => $MailTemplate->getId()]),
            ['mail_magazine_template_edit' => $form]
        );

        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('plugin_mail_magazine_template')));

        $this->actual = $MailTemplate->getSubject();
        $this->expected = $form['subject'];
        $this->verify();
    }

    public function testPreview(): void
    {
        $MailTemplate = $this->createMagazineTemplate();

        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_template_preview', ['id' => $MailTemplate->getId()])
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testPreviewIdIsNull(): void
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_template_preview', ['id' => null])
        );

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testPreviewIdIncorrect(): void
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine_template_preview', ['id' => 9999999])
        );

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testDelete(): void
    {
        $MailTemplate = $this->createMagazineTemplate();

        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_template_delete', ['id' => $MailTemplate->getId()])
        );

        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('plugin_mail_magazine_template')));
    }

    public function testDeleteIdIsNull(): void
    {
        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_template_delete', ['id' => null])
        );

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testDeleteIdIncorrect(): void
    {
        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_template_delete', ['id' => 9999999])
        );

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testDeleteIdIsZero(): void
    {
        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_template_delete', ['id' => 0])
        );

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }
}
