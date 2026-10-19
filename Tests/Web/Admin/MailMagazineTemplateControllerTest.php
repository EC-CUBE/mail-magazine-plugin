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

use PHPUnit\Framework\Attributes\DataProvider;
use Plugin\MailMagazine44\Entity\MailMagazineTemplate;
use Plugin\MailMagazine44\Repository\MailMagazineTemplateRepository;
use Plugin\MailMagazine44\Tests\Web\MailMagazineCommon;

class MailMagazineTemplateControllerTest extends MailMagazineCommon
{
    /**
     * @var MailMagazineTemplateRepository
     */
    protected $mailMagaTemplateRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->mailMagaTemplateRepository = $this->entityManager->getRepository(MailMagazineTemplate::class);
    }

    /**
     * @return array<string, string>
     */
    protected function createFormData(): array
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

    /**
     * @return iterable<string, array{string}>
     */
    public static function requiredFieldProvider(): iterable
    {
        yield '件名' => ['subject'];
        yield '本文' => ['body'];
    }

    #[DataProvider('requiredFieldProvider')]
    public function testCommitFormInvalid(string $field): void
    {
        $form = $this->createFormData();
        unset($form[$field]);
        $count = $this->mailMagaTemplateRepository->count([]);

        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_template_commit'),
            ['mail_magazine_template_edit' => $form]
        );

        // 必須項目が無ければ 500 にせず、入力エラーとして登録画面を表示する
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame($count, $this->mailMagaTemplateRepository->count([]));
    }

    #[DataProvider('requiredFieldProvider')]
    public function testCommitEdit必須項目が無ければ更新しない(string $field): void
    {
        $MailTemplate = $this->createMagazineTemplate();
        $expected = $MailTemplate->getSubject();
        $form = $this->createFormData();
        unset($form[$field]);

        $this->client->request('POST',
            $this->generateUrl('plugin_mail_magazine_template_commit', ['id' => $MailTemplate->getId()]),
            ['mail_magazine_template_edit' => $form]
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->entityManager->clear();
        $Saved = $this->mailMagaTemplateRepository->find($MailTemplate->getId());
        self::assertInstanceOf(MailMagazineTemplate::class, $Saved);
        $this->assertSame($expected, $Saved->getSubject());
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
        self::assertInstanceOf(MailMagazineTemplate::class, $MailTemplate);
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
