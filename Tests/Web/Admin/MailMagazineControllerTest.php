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
use Plugin\MailMagazine44\Entity\MailMagazineSendHistory;
use Plugin\MailMagazine44\Service\MailMagazineService;
use Plugin\MailMagazine44\Tests\Web\MailMagazineCommon;
use Symfony\Component\HttpFoundation\Session\Session;

class MailMagazineControllerTest extends MailMagazineCommon
{
    /**
     * Test routing.
     */
    public function testRoutingMailMagazine(): void
    {
        $this->client->request('GET',
            $this->generateUrl('plugin_mail_magazine')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testMailMagazineSearchWithBirthmonthLowOctorber(): void
    {
        $MaiCustomer = $this->createMailMagazineCustomer();
        // test search with birth month < 10
        $MaiCustomer->setBirth(new \DateTime('2016-09-19 23:59:59'));
        $this->entityManager->persist($MaiCustomer);
        $this->entityManager->flush();
        $birth_month = $MaiCustomer->getBirth()->format('n');
        $searchForm = $this->createSearchForm($MaiCustomer, $birth_month);
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine'),
            ['mail_magazine' => $searchForm]
        );
        $this->assertStringContainsString('検索結果：1件が該当しました', $crawler->filter('.c-outsideBlock__contents.mb-5 > span')->text());
    }

    public function testMailMagazineSearchWithBirthmonthHightOctorber(): void
    {
        $MaiCustomer = $this->createMailMagazineCustomer();
        // test search with birth month > 10
        $MaiCustomer->setBirth(new \DateTime('2016-11-19 23:59:59'));
        $this->entityManager->persist($MaiCustomer);
        $this->entityManager->flush();
        // because 誕生月 select box value start from 0. We need minus 1
        $birth_month = $MaiCustomer->getBirth()->format('n');
        $searchForm = $this->createSearchForm($MaiCustomer, $birth_month);
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine'),
            ['mail_magazine' => $searchForm]
        );
        $this->assertStringContainsString('検索結果：1件が該当しました', $crawler->filter('.c-outsideBlock__contents.mb-5 > span')->text());
    }

    public function testMailMagazineSearchWithBirthmonthNull(): void
    {
        $MaiCustomer = $this->createMailMagazineCustomer();
        $searchForm = $this->createSearchForm($MaiCustomer);
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine'),
            ['mail_magazine' => $searchForm]
        );
        $this->assertStringContainsString('検索結果：1件が該当しました', $crawler->filter('.c-outsideBlock__contents.mb-5 > span')->text());
    }

    public function testSelect(): void
    {
        $MailTemplate = $this->createMagazineTemplate();

        $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine_select', ['id' => $MailTemplate->getId()]),
            ['mail_magazine' => [
                'template' => $MailTemplate->getId(),
                'subject' => $MailTemplate->getSubject(),
                'body' => $MailTemplate->getBody(),
                '_token' => 'dummy',
            ]]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testSelectNotPost(): void
    {
        $MailTemplate = $this->createMagazineTemplate();
        $this->client->request(
            'GET',
            $this->generateUrl('plugin_mail_magazine_select', ['id' => $MailTemplate->getId()]),
            ['mail_magazine' => [
                'template' => $MailTemplate->getId(),
                'subject' => $MailTemplate->getSubject(),
                'body' => $MailTemplate->getBody(),
                '_token' => 'dummy',
            ]]
        );
        $this->assertEquals(405, $this->client->getResponse()->getStatusCode());
    }

    public function testConfirmInValid(): void
    {
        $MailTemplate = $this->createMagazineTemplate();

        $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine_select', ['id' => $MailTemplate->getId()]),
            ['mail_magazine' => [
                'template' => $MailTemplate->getId(),
                'subject' => $MailTemplate->getSubject(),
                'body' => $MailTemplate->getBody(),
                'mode' => 'confirm',
                '_token' => 'dummy',
            ]]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testConfirm(): void
    {
        $MailTemplate = $this->createMagazineTemplate();

        $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine_select', ['id' => $MailTemplate->getId()]),
            ['mail_magazine' => [
                'id' => $MailTemplate->getId(),
                'template' => $MailTemplate->getId(),
                'subject' => $MailTemplate->getSubject(),
                'body' => $MailTemplate->getBody(),
                'mode' => 'confirm',
                '_token' => 'dummy',
            ]]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testPrepare(): void
    {
        //        $this->initializeMailCatcher();
        $MailTemplate = $this->createMagazineTemplate();
        $MaiCustomer = $this->createMailMagazineCustomer();
        $searchForm = $this->createSearchForm($MaiCustomer);
        $searchForm['template'] = $MailTemplate->getId();
        $searchForm['subject'] = $MailTemplate->getSubject();
        $searchForm['body'] = $MailTemplate->getBody();

        $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine_prepare', ['id' => $MailTemplate->getId()]),
            ['mail_magazine' => $searchForm]
        );

        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('plugin_mail_magazine_history')));

        //        $Messages = $this->getMailCatcherMessages();
        //        $Message = $this->getMailCatcherMessage($Messages[0]->id);
        //
        //        $this->expected = $searchForm['subject'];
        //        $this->actual = $Message->subject;
        //        $this->verify();
        //        $this->cleanUpMailCatcherMessages();
    }

    public function testPagination(): void
    {
        for ($i = 0; $i < 30; ++$i) {
            $this->createMailMagazineCustomer();
        }
        $searchForm = [
            '_token' => 'dummy',
            'sex' => ['1'],
            'multi' => '',
            'customer_status' => [],
            'birth_month' => '',
            'birth_start' => '',
            'birth_end' => '',
            'pref' => '',
            'phone_number' => '',
            'create_date_start' => '',
            'create_date_end' => '',
            'update_date_start' => '',
            'update_date_end' => '',
            'buy_total_start' => '',
            'buy_total_end' => '',
            'buy_times_start' => '',
            'buy_times_end' => '',
            'buy_product_name' => '',
            'last_buy_start' => '',
            'last_buy_end' => '',
        ];
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine'),
            ['mail_magazine' => $searchForm]
        );

        $pageNumber = $crawler->filter('.c-outsideBlock__contents.mb-5 > span')->html();
        $this->assertMatchesRegularExpression('/件/', $pageNumber);

        // pagination
        $crawler = $this->client->request(
            'GET',
            $this->generateUrl('plugin_mail_magazine_page', ['page_no' => '2'])
        );

        // check result
        $pageNumber = $crawler->filter('.c-outsideBlock__contents.mb-5 > span')->html();
        $this->assertMatchesRegularExpression('/件/', $pageNumber);

        // check search condition
        $sexCheckbox = $crawler->filter('#mail_magazine_sex_1:checked')->count();
        $this->assertEquals(1, $sexCheckbox);
    }

    public function testPrepare配信対象が0件なら配信履歴を作らない(): void
    {
        $MailTemplate = $this->createMagazineTemplate();
        $searchForm = [
            '_token' => 'dummy',
            'multi' => 'no-such-customer-'.uniqid().'@example.com',
            'template' => $MailTemplate->getId(),
            'subject' => $MailTemplate->getSubject(),
            'body' => $MailTemplate->getBody(),
        ];
        $historyRepository = $this->entityManager->getRepository(MailMagazineSendHistory::class);
        $before = $historyRepository->count([]);

        $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine_prepare'),
            ['mail_magazine' => $searchForm]
        );

        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('plugin_mail_magazine_history')));
        $this->assertSame($before, $historyRepository->count([]));

        $session = $this->client->getRequest()->getSession();
        $this->assertInstanceOf(Session::class, $session);
        $flashBag = $session->getFlashBag();
        $this->assertFalse($flashBag->has('eccube.mailmagazine.history'));
        $this->assertContains('admin.mailmagazine.send.register.no_recipient', $flashBag->get('eccube.admin.error'));
    }

    public function testCommit配信対象が0件の配信履歴でも完了する(): void
    {
        // 配信準備で 0 件を弾く前に作られた配信履歴を想定する
        $SendHistory = new MailMagazineSendHistory();
        $SendHistory->setSubject('subject');
        $SendHistory->setBody('body');
        $SendHistory->setSendCount(0);
        $SendHistory->setCompleteCount(0);
        $SendHistory->setErrorCount(0);
        $SendHistory->setCreateDate(new \DateTime());
        $SendHistory->setStartDate(new \DateTime());
        $SendHistory->setSearchData(base64_encode(serialize([])));
        $this->entityManager->persist($SendHistory);
        $this->entityManager->flush();

        $service = static::getContainer()->get(MailMagazineService::class);
        $service->unlinkHistoryFiles((int) $SendHistory->getId());

        $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine_commit'),
            ['id' => $SendHistory->getId(), 'offset' => 0, '_token' => 'dummy'],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $json = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame(0, $json['total']);
        $this->assertSame(0, $json['count']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function commitHistoryNotFoundProvider(): iterable
    {
        yield 'id なし' => [['offset' => 0]];
        yield 'id が 0' => [['id' => 0, 'offset' => 0]];
        yield '存在しない id' => [['id' => 99999999, 'offset' => 0]];
        yield 'integer の範囲を超える id' => [['id' => str_repeat('9', 19), 'offset' => 0]];
        yield '負の id' => [['id' => -1, 'offset' => 0]];
        yield '数値でない id' => [['id' => 'abc', 'offset' => 0]];
    }

    /**
     * @param array<string, mixed> $params
     */
    #[DataProvider('commitHistoryNotFoundProvider')]
    public function testCommit配信履歴が無ければ404(array $params): void
    {
        $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine_commit'),
            $params + ['_token' => 'dummy'],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function commitInvalidOffsetProvider(): iterable
    {
        yield '負の offset' => [-1];
        yield 'integer の範囲を超える offset' => [str_repeat('9', 19)];
        yield '数値でない offset' => ['abc'];
        yield '配列の offset' => [[0]];
    }

    #[DataProvider('commitInvalidOffsetProvider')]
    public function testCommit不正なoffsetは400(mixed $offset): void
    {
        $SendHistory = $this->createSendHistoy($this->createMailMagazineCustomer());

        $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine_commit'),
            ['id' => $SendHistory->getId(), 'offset' => $offset, '_token' => 'dummy'],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testCommit配信済みの配信履歴に小さいoffsetを送ると409で打ち切る(): void
    {
        $Customer1 = $this->createMailMagazineCustomer();
        $Customer2 = $this->createMailMagazineCustomer();
        $service = static::getContainer()->get(MailMagazineService::class);
        $historyId = $service->createMailMagazineHistory([
            'subject' => 'subject',
            'body' => 'body',
            'multi' => $Customer1->getEmail(),
        ]);
        $this->assertNotNull($historyId);
        // 2 件目の会員も配信先に加えた配信ファイルにする
        file_put_contents($service->getHistoryFileName($historyId), '0,'.$Customer2->getId().','.$Customer2->getEmail().',name'.PHP_EOL, FILE_APPEND);
        $SendHistory = $this->entityManager->find(MailMagazineSendHistory::class, $historyId);
        $this->assertNotNull($SendHistory);
        $SendHistory->setSendCount(2);
        $this->entityManager->flush();

        try {
            // 配信を完了させる (配信ファイルは削除される)
            $this->client->request(
                'POST',
                $this->generateUrl('plugin_mail_magazine_commit'),
                ['id' => $historyId, 'offset' => 0, '_token' => 'dummy'],
                [],
                ['HTTP_X-Requested-With' => 'XMLHttpRequest']
            );
            $this->assertSame(200, $this->client->getResponse()->getStatusCode());
            $this->assertFalse(file_exists($service->getHistoryFileName($historyId)));

            // 配信総数より小さい offset で続きを送ろうとする
            $this->client->request(
                'POST',
                $this->generateUrl('plugin_mail_magazine_commit'),
                ['id' => $historyId, 'offset' => 1, '_token' => 'dummy'],
                [],
                ['HTTP_X-Requested-With' => 'XMLHttpRequest']
            );

            $this->assertSame(409, $this->client->getResponse()->getStatusCode());
            $json = json_decode((string) $this->client->getResponse()->getContent(), true);
            $this->assertFalse($json['status']);
        } finally {
            $service->unlinkHistoryFiles($historyId);
        }
    }

    public function testSendTest正常なメールアドレスなら送信する(): void
    {
        $this->client->enableProfiler();
        $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine_test'),
            self::validTestMailParams(),
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame(['status' => true], json_decode((string) $this->client->getResponse()->getContent(), true));
        $this->assertEmailCount(1);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidTestMailParamsProvider(): iterable
    {
        yield 'メールアドレスでない文字列' => [['email' => 'not-an-email'] + self::validTestMailParams()];
        yield 'メールアドレスが空' => [['email' => ''] + self::validTestMailParams()];
        yield 'メールアドレスが無い' => [array_diff_key(self::validTestMailParams(), ['email' => true])];
        yield 'メールアドレスに改行' => [['email' => "admin@example.com\r\nBcc: x@example.com"] + self::validTestMailParams()];
        yield 'ドメインが長すぎる' => [['email' => 'admin@'.str_repeat('a', 300).'.com'] + self::validTestMailParams()];
        yield 'Mailer の RFC 検証で拒否される' => [['email' => 'a..b@example.com'] + self::validTestMailParams()];
        yield 'メールアドレスが配列' => [['email' => ['admin@example.com']] + self::validTestMailParams()];
        yield '名前が配列' => [['name' => ['name']] + self::validTestMailParams()];
        yield '件名が配列' => [['subject' => ['subject']] + self::validTestMailParams()];
    }

    /**
     * @param array<string, mixed> $params
     */
    #[DataProvider('invalidTestMailParamsProvider')]
    public function testSendTest不正な入力は400(array $params): void
    {
        $this->client->enableProfiler();
        $this->client->request(
            'POST',
            $this->generateUrl('plugin_mail_magazine_test'),
            $params,
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
        $this->assertEmailCount(0);
    }

    /**
     * @return array<string, string>
     */
    private static function validTestMailParams(): array
    {
        return [
            'email' => 'admin@example.com',
            'name' => 'name',
            'subject' => 'test',
            'body' => 'body',
            'htmlBody' => '<b>body</b>',
        ];
    }
}
