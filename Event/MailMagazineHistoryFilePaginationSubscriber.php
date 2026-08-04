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

namespace Plugin\MailMagazine44\Event;

use Knp\Component\Pager\Event\ItemsEvent;
use Plugin\MailMagazine44\Service\MailMagazineService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailMagazineHistoryFilePaginationSubscriber implements EventSubscriberInterface
{
    /**
     * MailMagazineHistoryFilePaginationSubscriber constructor.
     *
     * @param MailMagazineService $mailMagazineService
     */
    public function __construct(protected MailMagazineService $mailMagazineService)
    {
    }

    public function items(ItemsEvent $event): void
    {
        $mailMagazineDir = $this->mailMagazineService->getMailMagazineDir();
        if (!is_string($event->target) || !str_starts_with($event->target, $mailMagazineDir)) {
            return;
        }

        $event->stopPropagation();
        $file = $event->target;
        if (!file_exists($file)) {
            $event->count = 0;
            $event->items = [];

            return;
        }

        $skip = $event->getOffset();
        $fp = fopen($file, 'r');
        if (false === $fp) {
            $event->count = 0;
            $event->items = [];

            return;
        }
        $count = $event->getLimit();
        $total = 0;

        $event->items = [];
        while (false !== ($line = fgets($fp))) {
            $line = rtrim($line, "\r\n");
            if ('' === $line) {
                continue;
            }
            $total++;
            if ($skip-- > 0) {
                continue;
            }
            if ($count > 0) {
                [$status, $customerId, $email, $name] = explode(',', $line, 4);
                $event->items[] = [
                    'status' => $status,
                    'customerId' => $customerId,
                    'email' => $email,
                    'name' => $name,
                ];
            }
            --$count;
        }
        fclose($fp);
        $event->count = $total;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 1],
        ];
    }
}
