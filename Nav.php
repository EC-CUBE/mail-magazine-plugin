<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\MailMagazine;

use Eccube\Common\EccubeNav;

class Nav implements EccubeNav
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getNav()
    {
        return [
            'customer' => [
                'id' => 'mailmagazine',
                'name' => 'plugin.mailmagazine.title',
                'has_child' => true,
                'icon' => 'cb-comment',
                'child' => [
                    [
                        'id' => 'mailmagazine',
                        'name' => 'plugin.mailmagazine.index.title',
                        'url' => 'plugin_mail_magazine',
                    ],
                    [
                        'id' => 'mailmagazine_template',
                        'name' => 'plugin.mailmagazine.template.title',
                        'url' => 'plugin_mail_magazine_template',
                    ],
                    [
                        'id' => 'mailmagazine_history',
                        'name' => 'plugin.mailmagazine.history.title',
                        'url' => 'plugin_mail_magazine_history',
                    ],
                ],
            ],
        ];
    }
}
