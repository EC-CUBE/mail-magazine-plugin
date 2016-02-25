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

use Eccube\Common\Constant;
use Eccube\Entity\Master\CustomerStatus;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Id\SequenceGenerator;

class MailMagazine
{

    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    // ===========================================================
    // マイページ画面
    // ===========================================================
    /**
     * マイページ会員情報編集のrender before
     * メルマガ送付項目を表示する
     * @param FilterResponseEvent $event
     */
    public function onRenderMypageChangeBefore(FilterResponseEvent $event)
    {

        if (!$this->app->isGranted('IS_AUTHENTICATED_FULLY')) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // メールマガジンの送付についての項目を追加したHTMLを取得する
        $html = $this->getNewMypageChangeHtml($event, $request, $response);

        $response->setContent($html);
        $event->setResponse($response);
    }

    /**
     * マイページ会員情報編集 controll after
     * メルマガ送付情報を保存する.
     */
    public function onControllMypageChangeAfter()
    {

        if (!$this->app->isGranted('IS_AUTHENTICATED_FULLY')) {
            return;
        }

        $app = $this->app;
        $request = $this->app['request'];

        // POST以外では処理を行わない
        if ('POST' !== $request->getMethod()) {
            return;
        }

        // Controller側のvalidationでエラーの場合には処理を続行しない
        $Customer = $app->user();
        if (is_null($Customer)) {
            return;
        }

        // メルマガFormを取得する
        $builder = $app['form.factory']->createBuilder('entry');
        $form = $builder->getForm();

        $form->handleRequest($request);

        $data = $form->getData();

        // カスタマIDの取得
        $Customer = $app->user();
        $customerId = $Customer->getId();

        // メルマガ送付情報を保存する
        $mailmagaFlg = $form->get('mailmaga_flg')->getData();
        $this->saveMailmagaCustomer($customerId, $mailmagaFlg);
    }

    // ===========================================================
    // 新規会員登録画面
    // ===========================================================
    /**
     * 新規会員登録のBefore
     * @param FilterResponseEvent $event
     */
    public function onRenderEntryBefore(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // 登録完了画面の場合は終了
        if ('POST' === $request->getMethod() && $request->get('mode') == 'complete') {
            return;
        }

        // メールマガジンの送付についての項目を追加したHTMLを取得する
        $html = $this->getNewEntryHtml($event, $request, $response);

        $response->setContent($html);
        $event->setResponse($response);
    }

    /**
     * 新規会員登録確認画面の後処理.
     * メールマガジン送付情報を保存する.
     */
    public function onControllerEntryAfter()
    {
        $app = $this->app;
        $request = $this->app['request'];

        // POST以外では処理を行わない
        if ('POST' !== $request->getMethod()) {
            return;
        }
        $mode = $request->get('mode');
        if ($mode != 'complete') {
            return;
        }

        // 今が登録確認画面か確認する
        $confirmFlg = $this->isEntryConfirm($request);

        // メールマガジン送付フラグを取得する
        $builder = $app['form.factory']->createBuilder('entry');
        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($confirmFlg) {
            $builder->setAttribute('freeze', true);
            $form = $builder->getForm();
            $form->handleRequest($request);
        }

        if ($mode == 'complete') {

            $data = $form->getData();

            // カスタマーIDを取得する
            $customerId = $this->getEntryCustomerId($request);

            // メルマガ送付情報を保存する
            if (!is_null($customerId)) {
                $mailmagaFlg = $form->get('mailmaga_flg')->getData();
                $this->saveMailmagaCustomer($customerId, $mailmagaFlg);
            }
        }

    }


    public function onRenderAdminCustomerBefore(FilterResponseEvent $event)
    {
        if (!$this->app->isGranted('ROLE_ADMIN')) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        $crawler = new Crawler($response->getContent());
        $html = $this->getHtml($crawler);

        $form = $this->app['form.factory']->createBuilder('admin_customer')->getForm();


        if ('POST' === $request->getMethod()) {

            if ($request->attributes->get('id')) {
                $id = $request->attributes->get('id');
            } else {
                $location = explode('/', $response->headers->get('location'));
                $url = explode('/', $this->app->url('admin_customer_edit', array('id' => '0')));
                $diffs = array_values(array_diff($location, $url));
                $id = $diffs[0];
            }
            $Customer = $this->app['eccube.repository.customer']->find($id);

            // メルマガFormを取得する
            $builder = $this->app['form.factory']->createBuilder('admin_customer', $Customer);
            $form = $builder->getForm();

            $form->handleRequest($request);

            $data = $form->getData();

            // カスタマIDの取得
            $customerId = $Customer->getId();

            // // メルマガ送付情報を保存する
            $mailmagaFlg = $form->get('mailmaga_flg')->getData();
            $this->saveMailmagaCustomer($customerId, $mailmagaFlg);

        } else {


            $id = $request->get('id');
            if ($id) {
                $Customer = $this->app['orm.em']
                    ->getRepository('Eccube\Entity\Customer')
                    ->find($id);

                if (is_null($Customer)) {
                    return;
                }

                // DBからメルマガ送付情報を取得する
                $MailmagaCustomerRepository = $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_mailmaga_customer'];
                $MailmagaCustomer = $MailmagaCustomerRepository->findOneBy(array('customer_id' => $Customer->getId()));

                if (!is_null($MailmagaCustomer)) {
                    $form->get('mailmaga_flg')->setData($MailmagaCustomer->getMailmagaFlg());
                }
            }

            $form->handleRequest($request);

            $parts = $this->app->renderView('MailMagazine/View/admin/mailmagazine.twig', array(
                'form' => $form->createView()
            ));


            try {
                $oldHtml = $crawler->filter('.form-horizontal .form-group')->last()->parents()->html();

                $newHtml = $oldHtml.$parts;
                $html = str_replace($oldHtml, $newHtml, $html);

            } catch (\InvalidArgumentException $e) {
            }


            $response->setContent($html);


            $event->setResponse($response);
        }

    }

    // ===========================================================
    // クラス内メソッド
    // ===========================================================
    /**
     * 会員新規登録画面に「メールマガジン送付について」項目を追加したHTMLを取得する.
     *
     * @param FilterResponseEvent $event
     * @param Request             $request
     * @param Response            $response
     */
    protected function getNewEntryHtml($event, $request, $response)
    {
        $app = &$this->app;

        $crawler = new Crawler($response->getContent());
        $html = $this->getHtml($crawler);
        $mode = $request->get('mode');

        try {
            // 今が登録確認画面か確認する
            $confirmFlg = $this->isEntryConfirm($request);

            // POSTの場合はメールマガジン送付を入力不可にする
            $twigName = 'entry_add_mailmaga.twig';
            if ('POST' === $this->app['request']->getMethod() && $confirmFlg) {
                $twigName = 'entry_confirm_add_mailmaga.twig';
            }

            // Formの取得
            $builder = $app['form.factory']->createBuilder('entry');
            $form = $builder->getForm();

            $form->handleRequest($request);

            if ($confirmFlg) {
                $builder->setAttribute('freeze', true);
                $form = $builder->getForm();
                $form->handleRequest($request);
            }

            // 追加先のノードを取得
            $nodeHtml = $crawler->filter('.dl_table.not_required')->last()->html();

            // 追加する情報のHTMLを取得する.
            $parts = $this->app['twig']->render(
                'MailMagazine/View/'.$twigName,
                array('form' => $form->createView())
            );
            $newNodeHtml = $nodeHtml.$parts;

            $html = str_replace($nodeHtml, $newNodeHtml, $html);
        } catch (\InvalidArgumentException $e) {
            // no-op
        }
        return $html;
    }

    /**
     * マイページ画面に「メールマガジン送付について」項目を追加したHTMLを取得する.
     *
     * @param FilterResponseEvent $event
     * @param Request             $request
     * @param Response            $response
     */
    protected function getNewMypageChangeHtml($event, $request, $response)
    {
        $app = &$this->app;

        $crawler = new Crawler($response->getContent());
        $html = $this->getHtml($crawler);
        $mode = $request->get('mode');

        try {
            // カスタマIDの取得
            $Customer = $app->user();
            if (is_null($Customer)) {
                return $html;
            }

            // Formの取得
            $builder = $app['form.factory']->createBuilder('entry');
            $form = $builder->getForm();

            if ('POST' === $this->app['request']->getMethod()) {
                $form->handleRequest($request);
            } else {
                // DBからメルマガ送付情報を取得する
                $MailmagaCustomerRepository = $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_mailmaga_customer'];
                $MailmagaCustomer = $MailmagaCustomerRepository->findOneBy(array('customer_id' => $Customer->getId()));

                if (!is_null($MailmagaCustomer)) {
                    $form->get('mailmaga_flg')->setData($MailmagaCustomer->getMailmagaFlg());
                }
            }

            // 追加先のノードを取得
            if (!count($crawler->filter('.dl_table.not_required')->last())) {
                return $html;
            }
            $nodeHtml = $crawler->filter('.dl_table.not_required')->last()->html();

            // 追加する情報のHTMLを取得する.
            $parts = $this->app['twig']->render(
                'MailMagazine/View/entry_add_mailmaga.twig',
                array('form' => $form->createView())
            );
            $newNodeHtml = $nodeHtml.$parts;

            $html = str_replace($nodeHtml, $newNodeHtml, $html);
        } catch (\InvalidArgumentException $e) {
            // no-op
        }
        return $html;
    }

    /**
     * メール送付情報を保存する
     * @param unknown $customerId
     * @param unknown $mailmagaFlg
     */
    protected function saveMailmagaCustomer($customerId, $mailmagaFlg)
    {
        // メルマガ送付情報を取得する
        $MailmagaCustomerRepository = $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_mailmaga_customer'];
        $MailmagaCustomer = $MailmagaCustomerRepository->findOneBy(array('customer_id' => $customerId));

        // メルマガ送付情報がない場合は新規に作成する
        if (is_null($MailmagaCustomer)) {
            $MailmagaCustomer = new \Plugin\MailMagazine\Entity\MailmagaCustomer();
            $MailmagaCustomer->setCustomerId($customerId);
            $MailmagaCustomer->setDelFlg(Constant::DISABLED);
            $MailmagaCustomer->setCreateDate(new \DateTime());
        }
        $MailmagaCustomer->setMailmagaFlg($mailmagaFlg);
        $MailmagaCustomer->setUpdateDate(new \DateTime());

        $MailmagaCustomerRepository->save($MailmagaCustomer);
    }

    /**
     * 会員登録確認画面が確認する
     * @param unknown $request
     */
    protected function isEntryConfirm($request)
    {
        $mode = $request->get('mode');

        $Customer = $this->app['eccube.repository.customer']->newCustomer();
        $EntryForm = $this->app['form.factory']->createBuilder('entry', $Customer)->getForm();
        $EntryForm->handleRequest($request);
        // confirmの場合はメールマガジン送付を入力不可にする

        if ($mode == 'confirm' && $EntryForm->isValid()) {
            return true;
        }
        return false;
    }

    /**
     * カスタマーIDを取得する
     *
     * @param unknown $request
     */
    protected function getEntryCustomerId($request)
    {
        // eMailは入力で重複チェックを行っているため整合性を保つ可能性が高い

        // EMailを取得する.
        $form = $this->app['form.factory']->createBuilder('entry')->getForm();
        $form->handleRequest($request);
        $email = $form->get('email')->getData();

        // 仮会員のEntityを取得する.
        $CustomerStatus = $this->app['orm.em']
            ->getRepository('Eccube\Entity\Master\CustomerStatus')
            ->find(CustomerStatus::NONACTIVE);

        // customer_idを取得する.
        $dql = "SELECT MAX(e.id) AS currentid FROM \Eccube\Entity\Customer e
            WHERE e.del_flg = 0 AND e.email = :email AND e.Status = :status";
        $q = $this->app['orm.em']->createQuery($dql);
        $q->setParameter('email', $email);
        $q->setParameter('status', $CustomerStatus);

        return $q->getSingleScalarResult();
    }

    /**
     * 解析用HTMLを取得
     *
     * @param Crawler $crawler
     * @return string
     */
    private function getHtml(Crawler $crawler)
    {
        $html = '';
        foreach ($crawler as $domElement) {
            $domElement->ownerDocument->formatOutput = true;
            $html .= $domElement->ownerDocument->saveHTML();
        }
        return html_entity_decode($html, ENT_NOQUOTES, 'UTF-8');
    }

}
