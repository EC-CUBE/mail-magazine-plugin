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
use Eccube\Common\Constant;
use Doctrine\ORM\Id\SequenceGenerator;
use Symfony\Component\Validator\Constraints as Assert;

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
        $app = $this->app;
        $request = $this->app['request'];

        // POST以外では処理を行わない
        if ('POST' !== $request->getMethod()) {
            return;
        }

        // Controller側のvalidationでエラーの場合には処理を続行しない
        $Customer = $app->user();
        if(is_null($Customer)) {
            return;
        }
        $mode = $request->get('mode');
        $EntryForm = $this->app['form.factory']->createBuilder('entry', $Customer)->getForm();
        $EntryForm->handleRequest($request);
        if($request->get('mode') != 'complete' || !$EntryForm->isValid()) {
            return;
        }

        // メルマガFormを取得する
        $form = $this->getEntryMailmagaForm(false);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            // カスタマIDの取得
            $Customer = $app->user();
            $customerId = $Customer->getId();

            // メルマガ送付情報を保存する
            $this->saveMailmagaCustomer($customerId, $data['mailmaga_flg']);
        }
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
        if($mode != 'complete') {
            return;
        }

        // 今が登録確認画面か確認する
        $confirmFlg = $this->isEntryConfirm($request);

        // メールマガジン送付フラグを取得する
        $form = $this->getEntryMailmagaForm($confirmFlg);
        $form->handleRequest($request);

        if ($form->isValid()) {

            $data = $form->getData();

            // カスタマーIDを取得する
            $customerId = $this->getEntryCustomerId($request);

            // メルマガ送付情報を保存する
            if(!is_null($customerId)) {
                $this->saveMailmagaCustomer($customerId, $data['mailmaga_flg']);
            }
        }
    }


    // ===========================================================
    // クラス内メソッド
    // ===========================================================
    /**
     * 会員新規登録画面に「メールマガジン送付について」項目を追加したHTMLを取得する.
     *
     * @param FilterResponseEvent $event
     * @param Request $request
     * @param Response $response
     */
    protected function getNewEntryHtml($event, $request, $response) {
        $app = &$this->app;

        $crawler = new Crawler($response->getContent());
        $html  = $crawler->html();
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
            $form = $this->getEntryMailmagaForm($confirmFlg);
            $form->handleRequest($request);

            // 追加先のノードを取得
            $nodeHtml  = $crawler->filter('.dl_table.not_required')->last()->html();

            // 追加する情報のHTMLを取得する.
            $parts = $this->app['twig']->render(
                'MailMagazine/View/' . $twigName,
                array('form' => $form->createView())
            );
            $newNodeHtml = $nodeHtml . $parts;

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
     * @param Request $request
     * @param Response $response
     */
    protected function getNewMypageChangeHtml($event, $request, $response) {
        $app = &$this->app;

        $crawler = new Crawler($response->getContent());
        $html  = $crawler->html();
        $mode = $request->get('mode');

        try {
            // Formの取得
            $form = $this->getEntryMailmagaForm(false);

            // カスタマIDの取得
            $Customer = $app->user();
            if(is_null($Customer)) {
                return $html;
            }

            if ('POST' === $this->app['request']->getMethod()) {
                $form->handleRequest($request);
            } else {
                // DBからメルマガ送付情報を取得する
                $MailmagaCustomerRepository = $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_mailmaga_customer'];
                $MailmagaCustomer = $MailmagaCustomerRepository->findOneBy(array('customer_id' => $Customer->getId()));

                if(!is_null($MailmagaCustomer)) {
                    $form->setData(array('mailmaga_flg' => $MailmagaCustomer->getMailmagaFlg()));
                }
            }

            // 追加先のノードを取得
            if(!count($crawler->filter('.dl_table.not_required')->last())) {
                return $html;
            }
            $nodeHtml  = $crawler->filter('.dl_table.not_required')->last()->html();

            // 追加する情報のHTMLを取得する.
            $parts = $this->app['twig']->render(
                'MailMagazine/View/entry_add_mailmaga.twig',
                array('form' => $form->createView())
            );
            $newNodeHtml = $nodeHtml . $parts;

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
    protected function saveMailmagaCustomer($customerId, $mailmagaFlg) {
        // メルマガ送付情報を取得する
        $MailmagaCustomerRepository = $this->app['eccube.plugin.mail_magazine.repository.mail_magazine_mailmaga_customer'];
        $MailmagaCustomer = $MailmagaCustomerRepository->findOneBy(array('customer_id' => $customerId));

        // メルマガ送付情報がない場合は新規に作成する
        if(is_null($MailmagaCustomer)) {
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
     * メルマガ送付Formを取得する
     * @param unknown $confirmFlg
     */
    protected function getEntryMailmagaForm($confirmFlg) {
        $builder = $this->app['form.factory']->createNamedBuilder('mail_magazine_mailmaga_customer');
        $options = array(
            'label' => 'メールマガジン送付について',
            'choices'   => array(1 => '受け取る', 0 => '受け取らない'),
            'mapped' => true,
            'expanded' => true,
            'multiple' => false,
            'required' => true,
            'empty_value' => false,
            'constraints' => array(
                new Assert\NotBlank()
            )
        );

        // confirmの場合はメールマガジン送付を入力不可にする
        if($confirmFlg) {
            $builder->setAttribute('freeze', true);
            $options['expanded'] = false;
        }

        $builder->add('mailmaga_flg', 'choice', $options);
        $form = $builder->getForm();

        return $form;
    }

    /**
     * 会員登録確認画面が確認する
     * @param unknown $request
     */
    protected function isEntryConfirm($request) {
        $mode = $request->get('mode');

        $Customer = $this->app['eccube.repository.customer']->newCustomer();
        $EntryForm = $this->app['form.factory']->createBuilder('entry', $Customer)->getForm();
        $EntryForm->handleRequest($request);
        // confirmの場合はメールマガジン送付を入力不可にする
        if($mode == 'confirm' && $EntryForm->isValid()) {
            return true;
        }
        return false;
    }

    /**
     * カスタマーIDを取得する
     *
     * @param unknown $request
     */
    protected function getEntryCustomerId($request) {
        // eMailは入力で重複チェックを行っているため整合性を保つ可能性が高い

        // EMailを取得する.
        $form = $this->app['form.factory']->createBuilder('entry')->getForm();
        $form->handleRequest($request);
        $email = $form->get('email')->getData();

        // 仮会員のEntityを取得する.
        $CustomerStatus = $this->app['orm.em']
            ->getRepository('Eccube\Entity\Master\CustomerStatus')
            ->find(1);

        // customer_idを取得する.
        $dql = "SELECT MAX(e.id) AS currentid FROM \Eccube\Entity\Customer e
            WHERE e.del_flg = 0 AND e.email = :email AND e.Status = :status";
        $q = $this->app['orm.em']->createQuery($dql);
        $q->setParameter('email', $email);
        $q->setParameter('status', $CustomerStatus);

        return $q->getSingleScalarResult();
    }

}
