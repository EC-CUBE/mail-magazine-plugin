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

namespace Plugin\MailMagazine44\Tests\Web\Front;

use Eccube\Common\Constant;
use Eccube\Tests\Web\AbstractWebTestCase;

class MailMagazineEventOnRenderEntryTest extends AbstractWebTestCase
{
    /**
     * @return array<string, mixed>
     */
    protected function createFormData(): array
    {
        $faker = $this->getFaker();
        $tel = explode('-', $faker->phoneNumber);

        $email = $faker->safeEmail;
        $password = $faker->lexify('????????');
        $birth = $faker->dateTimeBetween;

        $form = [
            'name' => [
                'name01' => $faker->lastName,
                'name02' => $faker->firstName,
            ],
            'kana' => [
                'kana01' => 'テスト',
                'kana02' => 'タロウ',
            ],
            'company_name' => $faker->company,
            'postal_code' => '1234567',
            'address' => [
                'pref' => '5',
                'addr01' => $faker->city,
                'addr02' => $faker->streetAddress,
            ],
            'phone_number' => $tel[0].$tel[1].$tel[2],
            'email' => [
                'first' => $email,
                'second' => $email,
            ],
            'password' => [
                'first' => $password,
                'second' => $password,
            ],
            'birth' => [
                'year' => $birth->format('Y'),
                'month' => $birth->format('n'),
                'day' => $birth->format('j'),
            ],
            'sex' => 1,
            'job' => 1,
            '_token' => 'dummy',
        ];

        return $form;
    }

    public function testOnRenderEntry(): void
    {
        $crawler = $this->client->request('GET',
            $this->generateUrl('entry')
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertEquals(1, $crawler->filter('#entry_mailmaga_flg')->count());
    }

    public function testOnRenderEntryPost(): void
    {
        $formData = $this->createFormData();
        $updateFlg = Constant::ENABLED;

        $formData['mailmaga_flg'] = $updateFlg;

        $this->client->request('POST',
            $this->generateUrl('entry'),
            [
                'entry' => $formData,
                'mode' => 'confirm',
            ]
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testOnRenderEntryPostComplete(): void
    {
        $formData = $this->createFormData();
        $updateFlg = Constant::ENABLED;

        $formData['mailmaga_flg'] = $updateFlg;

        $this->client->request('POST',
            $this->generateUrl('entry'),
            [
                'entry' => $formData,
                'mode' => 'complete',
            ]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }
}
