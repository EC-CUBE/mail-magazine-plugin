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

namespace Plugin\MailMagazine44\Tests\Service;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * 送信結果を制御するテスト用のメーラースタブ。
 *
 * profiler(MailerAssertionsTrait) では送信失敗を再現できないため、
 * 送信失敗系のテストでは本スタブを MailMagazineService に差し替えて使用する。
 * コンストラクタに与えた真偽値の順番どおりに送信の成功/失敗を返す。
 * 例) [false, true] なら 1 通目は失敗(例外)、2 通目は成功。
 */
class MailerStub implements MailerInterface
{
    /**
     * @var list<bool>
     */
    private array $results;

    /**
     * 送信したメールの宛先アドレスを送信順に記録する。
     *
     * @var list<string>
     */
    private array $sentAddresses = [];

    /**
     * @param list<bool> $results 送信結果(true:成功, false:失敗)を送信順に並べた配列
     */
    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        if ($message instanceof Email) {
            $to = $message->getTo();
            if ([] !== $to) {
                $this->sentAddresses[] = $to[0]->getAddress();
            }
        }

        // 送信失敗を例外で表現する。MailMagazineService 側は \Exception を捕捉する。
        if (false === array_shift($this->results)) {
            throw new \RuntimeException('Mail send failed (stub).');
        }
    }

    /**
     * @return list<string>
     */
    public function getSentAddresses(): array
    {
        return $this->sentAddresses;
    }
}
