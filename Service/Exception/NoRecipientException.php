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

namespace Plugin\MailMagazine44\Service\Exception;

/**
 * 配信条件に一致する会員が 1 件もないため、配信履歴を作成できないことを表す。
 */
class NoRecipientException extends \RuntimeException
{
}
