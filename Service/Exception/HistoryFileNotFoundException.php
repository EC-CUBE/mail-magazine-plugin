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
 * 未配信が残っているのに配信ファイルが無く、配信を続けられないことを表す。
 *
 * 配信済みの配信履歴に、配信総数より小さい offset を指定した場合などに発生する。
 */
class HistoryFileNotFoundException extends \RuntimeException
{
}
