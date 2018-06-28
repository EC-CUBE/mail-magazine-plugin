<?php
/*
* This file is part of EC-CUBE
*
* Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
* http://www.lockon.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\MailMagazine\ServiceProvider;

use Eccube\Common\Constant;
use Plugin\MailMagazine\Event\MailMagazine;
use Plugin\MailMagazine\Event\MailMagazineLegacy;
use Plugin\MailMagazine\Form\Extension\CustomerMailMagazineTypeExtension;
use Plugin\MailMagazine\Form\Extension\EntryMailMagazineTypeExtension;
use Plugin\MailMagazine\Repository\MailMagazineCustomerRepository;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;

// include log functions (for 3.0.0 - 3.0.11)
require_once __DIR__.'/../log.php';

class MailMagazineServiceProvider implements ServiceProviderInterface
{
    public function register(BaseApplication $app)
    {
        // メルマガテンプレート用リポジトリ
        $app['eccube.plugin.mail_magazine.repository.mail_magazine'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\MailMagazine\Entity\MailMagazineTemplate');
        });

        // 配信履歴用リポジトリ
        $app['eccube.plugin.mail_magazine.repository.mail_magazine_history'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\MailMagazine\Entity\MailMagazineSendHistory');
        });

        // Customer用リポジトリ
        $app['eccube.plugin.mail_magazine.repository.mail_magazine_customer'] = $app->share(function () use ($app) {
            return new MailMagazineCustomerRepository($app['orm.em'], $app['orm.em']->getMetadataFactory()->getMetadataFor('Eccube\Entity\Customer'));
        });

        // 新規会員登録/Myページ
        $app['eccube.plugin.mail_magazine.repository.mail_magazine_mailmaga_customer'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\MailMagazine\Entity\MailmagaCustomer');
        });

        // イベント
        $app['eccube.plugin.mail_magazine.event.mail_magazine'] = $app->share(function () use ($app) {
            return new MailMagazine($app);
        });
        $app['eccube.plugin.mail_magazine.event.mail_magazine_legacy'] = $app->share(function () use ($app) {
            return new MailMagazineLegacy($app);
        });



        // 型登録
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            // テンプレート設定
            $types[] = new \Plugin\MailMagazine\Form\Type\MailMagazineTemplateEditType($app);
            $types[] = new \Plugin\MailMagazine\Form\Type\MailMagazineTemplateType($app);

            // 配信内容設定
            $types[] = new \Plugin\MailMagazine\Form\Type\MailMagazineType($app);

            return $types;
        }));

        // Form Extension
        $app['form.type.extensions'] = $app->share($app->extend('form.type.extensions', function ($extensions) use ($app) {
            $extensions[] = new EntryMailMagazineTypeExtension($app);
            $extensions[] = new CustomerMailMagazineTypeExtension($app);

            return $extensions;
        }));

        // -----------------------------
        // サービス
        // -----------------------------
        $app['eccube.plugin.mail_magazine.service.mail'] = $app->share(function () use ($app) {
            return new \Plugin\MailMagazine\Service\MailMagazineService($app);
        });



    }

    public function boot(BaseApplication $app)
    {
    }
}
