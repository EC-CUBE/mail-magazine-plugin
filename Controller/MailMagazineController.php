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

namespace Plugin\MailMagazine\Controller;

use Eccube\Application;
use Plugin\MailMagazine\Entity\MailMagazineSendHistory;
use Plugin\MailMagazine\Service\MailMagazineService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints\NotBlank;

class MailMagazineController
{
    private $main_title;
    private $sub_title;

    public function __construct()
    {
    }

    /**
     * 配信内容設定検索画面を表示する.
     * 左ナビゲーションの選択はGETで遷移する.
     *
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Application $app, Request $request)
    {
        $session = $request->getSession();
        $page_no = $request->get('page_no');
        $pageMaxis = $app['eccube.repository.master.page_max']->findAll();
        $page_max = $app['config']['default_page_count'];

        $pagination = null;
        $searchForm = $app['form.factory']
            ->createBuilder('mail_magazine')
            ->getForm();

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
            $searchData = array();
            if ($searchForm->isValid()) {
                $searchData = $searchForm->getData();
            }
//            dump($request->get('mail_magazine'));exit;
            // sessionのデータ保持
            $session->set('plugin.mailmagazine.search', $searchData);

            // 検索ボタンクリック時の処理
            $app['eccube.plugin.mail_magazine.repository.mail_magazine_customer']->setApplication($app);
            $qb = $app['eccube.plugin.mail_magazine.repository.mail_magazine_customer']
                ->getQueryBuilderBySearchData($searchData);

            $pagination = $app['paginator']()->paginate(
                $qb,
                empty($searchData['pageno']) ? 1 : $searchData['pageno'],
                $page_max
            );
        } else {
            if (is_null($page_no)) {
                // sessionを削除
                $session->remove('plugin.mailmagazine.search');
            } else {
                // pagingなどの処理
                $searchData = $session->get('plugin.mailmagazine.search');
                if (!is_null($searchData)) {

                    $pcount = $request->get('page_max');
                    $page_max = empty($pcount) ? $page_max : $pcount;

                    $app['eccube.plugin.mail_magazine.repository.mail_magazine_customer']->setApplication($app);
                    $qb = $app['eccube.plugin.mail_magazine.repository.mail_magazine_customer']
                        ->getQueryBuilderBySearchData($searchData);

                    $pagination = $app['paginator']()->paginate(
                        $qb,
                        $page_no,
                        $page_max
                    );

                    if (isset($searchData['sex'])&&(count($searchData['sex']) > 0)) {
                        $sex_ids = array();
                        foreach ($searchData['sex'] as $Sex) {
                            $sex_ids[] = $Sex->getId();
                        }
                        $searchData['sex'] = $app['eccube.repository.master.sex']
                            ->findBy(array('id' => $sex_ids));
                    }

                    if (isset($searchData['pref'])) {
                        $searchData['pref'] = $app['eccube.repository.master.pref']
                            ->find($searchData['pref']->getId());
                    }

                    if (isset($searchData['pagemax'])) {
                        $searchData['pagemax'] = $app['eccube.repository.master.page_max']
                            ->find($searchData['pagemax']->getId());
                    }

                    $searchForm->setData($searchData);
                }
            }
        }

        return $app->render(
            'MailMagazine/View/admin/index.twig',
            array('searchForm' => $searchForm->createView(),
                'pagination' => $pagination,
                'pageMaxis' => $pageMaxis,
                'page_count' => $page_max,
            )
        );
    }

    /**
     * テンプレート選択
     * RequestがPOST以外の場合はBadRequestHttpExceptionを発生させる
     * @param Application $app
     * @param Request $request
     * @param string $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function select(Application $app, Request $request, $id = null) {

        $Mail = null;

        // POSTでない場合は終了する
        if ('POST' !== $request->getMethod()) {
            throw new BadRequestHttpException();
        }

        // Formの取得
        $form = $app['form.factory']
            ->createBuilder('mail_magazine', null)
            ->getForm();

        $form->handleRequest($request);

        $newSubject = "";
        $newBody = "";

        // テンプレートが選択されている場合はテンプレートデータを取得する
        if($id) {
            // テンプレート選択から遷移した場合の処理
            // 選択されたテンプレートのデータを取得する
            $Mail = $app['eccube.plugin.mail_magazine.repository.mail_magazine']->find($id);

            if (is_null($Mail)) {
                throw new NotFoundHttpException();
            }

            // テンプレートを表示する
            $newSubject = $Mail->getSubject();
            $newBody = $Mail->getBody();
        }

        return $app->render('MailMagazine/View/admin/template_select.twig', array(
                'form' => $form->createView(),
                'new_subject' => $newSubject,
                'new_body' => $newBody,
                'id' => $id,
        ));
    }

    /**
     * 確認画面の表示
     * RequestがPOST以外の場合はBadRequestHttpExceptionを発生させる
     * @param Application $app
     * @param Request $request
     * @param string $id
     */
    public function confirm(Application $app, Request $request, $id = null) {

        // POSTでない場合は終了する
        if ('POST' !== $request->getMethod()) {
            throw new BadRequestHttpException();
        }

        // Formの作成
        $builder = $app['form.factory']->createBuilder('mail_magazine', null);

        // ------------------------------------------------
        // メルマガテンプレート用にvalidationを付与するため
        // 項目を削除、追加する
        // ------------------------------------------------
        // Subject
        $builder->remove('subject');
        $builder->add('subject', 'text', array(
                'label' => 'Subject',
                'required' => true,
                'constraints' => array(
                        new NotBlank()
                )
        ));

        // 本文
        $builder->remove('body');
        $builder->add('body', 'textarea', array(
                'label' => '本文',
                'required' => true,
                'constraints' => array(
                        new NotBlank()
                )
        ));

        $form = $builder->getForm();
        $form->handleRequest($request);

        // Formのデータを取得する
        $formData = $form->getData();

        // validationを実行する
        if(!$form->isValid()) {
            // エラーの場合はテンプレート選択画面に遷移する
            return $app->render('MailMagazine/View/admin/template_select.twig', array(
                    'form' => $form->createView(),
                    'new_subject' => $formData['subject'],
                    'new_body' =>  $formData['body'],
                    'id' =>  $id,
            ));

        }

        return $app->render('MailMagazine/View/admin/confirm.twig', array(
                'form' => $form->createView(),
                'subject_itm' => $form['subject']->getData(),
                'body_itm' => $form['body']->getData(),
                'id' => $id,
        ));
    }

    public function prepare(Application $app, Request $request)
    {
        if ('POST' != $request->getMethod()) {
            throw new BadRequestHttpException();
        }

        // Formを取得する
        $form = $app['form.factory']
            ->createBuilder('mail_magazine', null)
            ->getForm();
        $form->handleRequest($request);
        $data = $form->getData();

        // 送信対象者をdtb_customerから取得する
        if (!$form->isValid()) {
            throw new BadRequestHttpException();
        }

        // タイムアウトしないようにする
        set_time_limit(0);

        // サービスの取得
        /** @var MailMagazineService $service */
        $service = $app['eccube.plugin.mail_magazine.service.mail'];

        // 配信履歴を登録する
        $sendId = $service->createMailMagazineHistory($data);
        if(is_null($sendId)) {
            $app->addError('admin.mailmagazine.send.regist.failure', 'admin');
        }

        $app['session']->getFlashBag()->add('eccube.plugin.mailmagazine.history', $sendId);

        // 配信管理画面に遷移する
        return $app->redirect($app->url('admin_mail_magazine_history'));
    }

    /**
     * 配信処理
     * 配信終了後配信履歴に遷移する
     * RequestがPOST以外の場合はBadRequestHttpExceptionを発生させる
     * @param Application $app
     * @param Request $request
     * @param string $id
     */
    public function commit(Application $app, Request $request) {

        // Ajax/POSTでない場合は終了する
        if (!$request->isXmlHttpRequest() || 'POST' !== $request->getMethod()) {
            throw new BadRequestHttpException();
        }


        // タイムアウトしないようにする
        set_time_limit(0);


        // デフォルトの設定ではメールをスプールしてからレスポンス後にメールを一括で送信する。
        // レスポンス後に一括送信した場合、メールのエラーをハンドリングできないのでスプールしないように設定。
        $app['swiftmailer.use_spool'] = false;

        $id = $request->get('id');
        $offset = (int) $request->get('offset', 0);
        $max = (int) $request->get('max', 100);

        /** @var MailMagazineService $service */
        $service = $app['eccube.plugin.mail_magazine.service.mail'];
        $service->sendrMailMagazine($id, $offset, $max);

        /** @var MailMagazineSendHistory $sendHistory */
        $sendHistory = $app[MailMagazineService::REPOSITORY_SEND_HISTORY]->find($id);

        return $app->json(array(
            'status' => true,
            'id' => $id,
            'total' => $sendHistory->getSendCount(),
            'count' => $offset + 100,
        ));

//        // Formを取得する
//        $form = $app['form.factory']
//            ->createBuilder('mail_magazine', null)
//            ->getForm();
//        $form->handleRequest($request);
//        $data = $form->getData();
//
//        // 送信対象者をdtb_customerから取得する
//        if (!$form->isValid()) {
//            throw new BadRequestHttpException();
//        }
//
//        // タイムアウトしないようにする
//        set_time_limit(0);
//
//        // デフォルトの設定ではメールをスプールしてからレスポンス後にメールを一括で送信する。
//        // レスポンス後に一括送信した場合、メールのエラーをハンドリングできないのでスプールしないように設定。
//        $app['swiftmailer.use_spool'] = false;
//
//        // サービスの取得
//        /** @var MailMagazineService $service */
//        $service = $app['eccube.plugin.mail_magazine.service.mail'];
//
//        // 配信履歴を登録する
//        $sendId = $service->createMailMagazineHistory($data);
//        if(is_null($sendId)) {
//            $app->addError('admin.mailmagazine.send.regist.failure', 'admin');
//        } else {
//
//            // 登録した配信履歴からメールを送信する
//            $service->sendrMailMagazine($sendId);
//
//            // 送信完了メールを送信する
//            $service->sendMailMagazineCompleateReportMail();
//            $app->addSuccess('admin.mailmagazine.send.complete', 'admin');
//        }
//
//
//        // 配信管理画面に遷移する
//        return $app->redirect($app->url('admin_mail_magazine_history'));
    }


    /**
    *
    * @param Application $app
    * @param Request $request
    * @param unknown $id
    * @throws NotFoundHttpException
    * @return \Symfony\Component\HttpFoundation\RedirectResponse
    */
    public function up(Application $app, Request $request, $id)
    {
        $repos = $app['eccube.plugin.mail_magazine.repository.maker'];

        $TargetMailMagazine = $repos->find($id);
        if (!$TargetMailMagazine) {
            throw new NotFoundHttpException();
        }

        $form = $app['form.factory']
            ->createNamedBuilder('admin_mail_magazine', 'form', null, array(
                'allow_extra_fields' => true,
            ))
            ->getForm();

        $status = false;
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $status = $repos->up($TargetMailMagazine);
            }
        }

        if ($status === true) {
            $app->addSuccess('admin.maker.down.complete', 'admin');
        } else {
            $app->addError('admin.maker.down.error', 'admin');
        }

        return $app->redirect($app->url('admin_mail_magazine'));
    }

    /**
    *
    * @param Application $app
    * @param Request $request
    * @param unknown $id
    * @throws NotFoundHttpException
    */
    public function down(Application $app, Request $request, $id)
    {
        $repos = $app['eccube.plugin.mail_magazine.repository.maker'];

        $TargetMailMagazine = $repos->find($id);
        if (!$TargetMailMagazine) {
            throw new NotFoundHttpException();
        }

        $form = $app['form.factory']
            ->createNamedBuilder('admin_mail_magazine', 'form', null, array(
                'allow_extra_fields' => true,
            ))
            ->getForm();

        $status = false;
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $status = $repos->down($TargetMailMagazine);
            }
        }

        if ($status === true) {
            $app->addSuccess('admin.mail.down.complete', 'admin');
        } else {
            $app->addError('admin.mail.down.error', 'admin');
        }

        return $app->redirect($app->url('admin_mail_magazine'));
    }

}
