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
 * 送信結果を制御するテスト用のメーラー。
 *
 * services_test.yaml で MailMagazineService の mailer として注入する。
 * setResults(null) のときは実メーラーへ委譲し、profiler による検証を可能にする。
 * setResults([...]) で送信失敗を含む結果を任意に制御する。
 * 例) [false, true] なら 1 通目は失敗(例外)、2 通目は成功。
 */
class MailerStub implements MailerInterface
{
    /**
     * null のときは $inner へ委譲する。
     *
     * @var list<bool>|null
     */
    private ?array $results = null;

    /**
     * スタブモード時に送信したメールの宛先アドレスを送信順に記録する。
     *
     * @var list<string>
     */
    private array $sentAddresses = [];

    public function __construct(private MailerInterface $inner)
    {
    }

    /**
     * @param list<bool>|null $results 送信結果(true:成功, false:失敗)。null で実メーラーへ委譲
     */
    public function setResults(?array $results): void
    {
        $this->results = $results;
        $this->sentAddresses = [];
    }

    /**
     * 送信結果を制御中かどうか（実メーラーへ委譲していないか）。
     */
    public function isControlling(): bool
    {
        return null !== $this->results;
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        if (null === $this->results) {
            $this->inner->send($message, $envelope);

            return;
        }

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
