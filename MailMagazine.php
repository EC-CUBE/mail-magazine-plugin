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

namespace Plugin\MailMagazine;

use Eccube\Event\RenderEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\DomCrawler\Crawler;

class MailMagazine
{

    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function onRenderAdminProductNewBefore(FilterResponseEvent $event)
    {
        $app = $this->app;
        $request = $event->getRequest();
        $response = $event->getResponse();

		// メーカーマスタから有効なメーカー情報を取得
    	$repos = $app['eccube.plugin.maker.repository.mail_magazine'];
        $MailMagazines = $repos->findAll();

        if (is_null($MailMagazines)) {
            $MailMagazines = new \Plugin\MailMagazine\Entity\MailMagazine();
        }

		// 商品登録・編集画面のHTMLを取得し、DOM化
        $crawler = new Crawler($response->getContent());

        $form = $app['form.factory']
            ->createBuilder('admin_product_mail_magazine')
            ->getForm();

        $form->get('mail_magazine')->setData($MailMagazines);
        $form->handleRequest($request);

        $parts = $app->renderView(
            'MailMagazine/View/admin/product_mail_magazine.twig',
            array('form' => $form->createView())
        );
        
        // form1の最終項目に追加(レイアウトに依存（時間無いのでベタ）)
        $html  = $crawler->html();
        $form  = $crawler->filter('#form1 .accordion')->last()->html();
        $new_form = $form . $parts;
        $html = str_replace($form, $new_form, $html);

        $response->setContent($html);
        $event->setResponse($response);
    }

    public function onRenderAdminProductEditBefore(FilterResponseEvent $event)
    {
        $app = $this->app;
        $request = $event->getRequest();
        $response = $event->getResponse();

		// メーカーマスタから有効なメーカー情報を取得
    	$repos = $app['eccube.plugin.mail_magazine.repository.mail_magazine'];
        $MailMagazines = $repos->findAll();

        if (is_null($MailMagazines)) {
            $MailMagazines = new \Plugin\MailMagazine\Entity\MailMagazine();
        }

		// 商品登録・編集画面のHTMLを取得し、DOM化
        $crawler = new Crawler($response->getContent());

        $form = $app['form.factory']
            ->createBuilder('admin_product_mail_magazine')
            ->getForm();

        $form->get('mail_magazine')->setData($MailMagazines);
        $form->handleRequest($request);

        $parts = $app->renderView(
            'MailMagazine/View/admin/product_mail_magazine.twig',
            array('form' => $form->createView())
        );
        
        // form1の最終項目に追加(レイアウトに依存（時間無いのでベタ）)
        $html  = $crawler->html();
        $form  = $crawler->filter('#form1 .accordion')->last()->html();
        $new_form = $form . $parts;
        $html = str_replace($form, $new_form, $html);

        $response->setContent($html);
        $event->setResponse($response);
	}

	private function render(FilterResponseEvent $event)
	{

	}

    public function onAdminProductEditAfter()
    {
        $app = $this->app;
        $id = $app['request']->attributes->get('id');

        $form = $app['form.factory']
            ->createBuilder('admin_product_mail_magazine')
            ->getForm();

        $ProductMailMagazine = $app['eccube.plugin.mail_magazine.repository.product_mail_magazine']->find($id);
        
        if (is_null($CategoryContent)) {
            $ProductMailMagazine = new \Plugin\MailMagazine\Entity\ProductMailMagazine();
        }
        
        
        die($ProductMailMagazine->getMailMagazineId());
/*        
        $form->get('product_mail_magazine')->setData($ProductMailMagazine->getMailMagazineId());

        $form->handleRequest($app['request']);

        if ('POST' === $app['request']->getMethod()) {
            if ($form->isValid()) {
                $content = $form->get('content')->getData();

                $Category = $app['eccube.repository.category']->find($id);

                $CategoryContent
                    ->setCategoryId($Category->getId())
                    ->setCategory($Category)
                    ->setContent($content);

                $app['orm.em']->persist($CategoryContent);
                $app['orm.em']->flush();
            }
        }
*/        
    }

}
