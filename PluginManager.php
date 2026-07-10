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

namespace Plugin\MailMagazine44;

use Eccube\Common\EccubeConfig;
use Eccube\Plugin\AbstractPluginManager;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class PluginManager extends AbstractPluginManager
{
    public function uninstall(array $meta, ContainerInterface $container): void
    {
        $file = new Filesystem();

        $file->remove($container->get(EccubeConfig::class)->get('mail_magazine_dir'));
    }
}
