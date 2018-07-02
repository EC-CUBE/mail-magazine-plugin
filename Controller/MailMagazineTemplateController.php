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
use Eccube\Controller\AbstractController;
use Plugin\MailMagazine\Entity\MailMagazineTemplate;
use Plugin\MailMagazine\Repository\MailMagazineTemplateRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Plugin\MailMagazine\Form\Type\MailMagazineTemplateEditType;

class MailMagazineTemplateController extends AbstractController
{
    /**
     * @var MailMagazineTemplateRepository
     */
    protected $mailMagazineTemplateRepository;

    /**
     * MailMagazineTemplateController constructor.
     *
     * @param MailMagazineTemplateRepository $mailMagazineTemplateRepository
     */
    public function __construct(
        MailMagazineTemplateRepository $mailMagazineTemplateRepository
    ) {
        $this->mailMagazineTemplateRepository = $mailMagazineTemplateRepository;
    }

    /**
     * 一覧表示.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template", name="plugin_mail_magazine_template")
     * @Template("@MailMagazine/admin/template_list.twig")
     *
     */
    public function index()
    {
        $templateList = $this->mailMagazineTemplateRepository->findAll();
        return [
            'TemplateList' => $templateList,
        ];
    }

    /**
     * preview画面表示.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template/{id}/preview",
     *     requirements={"id":"\d+|"},
     *     name="plugin_mail_magazine_template_preview"
     * )
     * @Template("@MailMagazine/admin/preview.twig")
     *
     * @param Application $app
     * @param Request     $request
     * @param unknown     $id
     *
     * @return void|\Symfony\Component\HttpFoundation\Response
     */
    public function preview(Application $app, Request $request, $id)
    {
        die(var_dump(__METHOD__));
        // id の存在確認
        // nullであれば一覧に戻る
        if (is_null($id) || strlen($id) == 0) {
            $app->addError('admin.plugin.mailmagazine.template.data.illegalaccess', 'admin');

            // メルマガテンプレート一覧へリダイレクト
            return $app->redirect($app->url('plugin_mail_magazine_template'));
        }

        // パラメータ$idにマッチするデータが存在するか判定
        // あれば、subject/bodyを取得
        $template = $app['eccube.plugin.mail_magazine.repository.mail_magazine']->find($id);
        if (is_null($template)) {
            // データが存在しない場合はメルマガテンプレート一覧へリダイレクト
            $app->addError('admin.plugin.mailmagazine.template.data.notfound', 'admin');

            return $app->redirect($app->url('plugin_mail_magazine_template'));
        }

        // プレビューページ表示
        return [
            'Template' => $template,
        ];
    }

    /**
     * メルマガテンプレートを論理削除.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template/{id}/delete",
     *     requirements={"id":"\d+|"},
     *     name="plugin_mail_magazine_template_delete"
     * )
     *
     * @param Application $app
     * @param Request     $request
     * @param unknown     $id
     */
    public function delete(Application $app, Request $request, $id)
    {
        die(var_dump(__METHOD__));
        // POSTかどうか判定
        // パラメータ$idにマッチするデータが存在するか判定
        // POSTかつ$idに対応するdtb_mailmagazine_templateのレコードがあれば、del_flg = 1に設定して更新
        if ('POST' === $request->getMethod()) {
            // idがからの場合はメルマガテンプレート一覧へリダイレクト
            if (is_null($id) || strlen($id) == 0) {
                $app->addError('admin.plugin.mailmagazine.template.data.illegalaccess', 'admin');

                return $app->redirect($app->url('plugin_mail_magazine_template'));
            }

            // メルマガテンプレートを取得する
            $template = $app['eccube.plugin.mail_magazine.repository.mail_magazine']->find($id);

            if (is_null($template)) {
                // データが存在しない場合はメルマガテンプレート一覧へリダイレクト
                $app->addError('admin.plugin.mailmagazine.template.data.notfound', 'admin');

                return $app->redirect($app->url('plugin_mail_magazine_template'));
            }

            // メルマガテンプレートを削除する
            $app['eccube.plugin.mail_magazine.repository.mail_magazine']->delete($template);
        }

        // メルマガテンプレート一覧へリダイレクト
        return $app->redirect($app->url('plugin_mail_magazine_template'));
    }

    /**
     * テンプレート編集画面表示.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template/{id}/edit",
     *     requirements={"id":"\d+|"},
     *     name="plugin_mail_magazine_template_edit"
     * )
     * @Template("@MailMagazine/admin/template_edit.twig")
     *
     * @param Application $app
     * @param Request     $request
     * @param unknown     $id
     */
    public function edit(Application $app, Request $request, $id)
    {
        die(var_dump(__METHOD__));
        // POST以外はエラーにする
        if ('POST' !== $request->getMethod()) {
            throw new BadRequestHttpException();
        }
        // id の存在確認
        // nullであれば一覧に戻る
        if (is_null($id) || strlen($id) == 0) {
            $app->addError('admin.plugin.mailmagazine.template.data.illegalaccess', 'admin');

            // メルマガテンプレート一覧へリダイレクト
            return $app->redirect($app->url('plugin_mail_magazine_template'));
        }

        // 選択したメルマガテンプレートを検索
        // 存在しなければメッセージを表示
        $Template = $app['eccube.plugin.mail_magazine.repository.mail_magazine']->find($id);

        if (is_null($Template)) {
            // データが存在しない場合はメルマガテンプレート一覧へリダイレクト
            $app->addError('admin.plugin.mailmagazine.template.data.notfound', 'admin');

            return $app->redirect($app->url('plugin_mail_magazine_template'));
        }

        // formの作成
        $form = $app['form.factory']
            ->createBuilder('mail_magazine_template_edit', $Template)
            ->getForm();

        return [
            'form' => $form->createView(),
            'Template' => $Template,
        ];
    }

    /**
     * テンプレート編集確定処理.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template/commit/{id}",
     *     requirements={"id":"\d+|"},
     *     name="plugin_mail_magazine_template_commit"
     * )
     * @Template("@MailMagazine/admin/template_edit.twig")
     *
     * @param Application $app
     * @param Request     $request
     * @param int     $id
     */
    public function commit(Request $request, $id = null)
    {
        $Template = $id ? $this->mailMagazineTemplateRepository->find($id) : new MailMagazineTemplate();

        // データが存在しない場合はメルマガテンプレート一覧へリダイレクト
        if (is_null($Template)) {
            $this->addError('admin.plugin.mailmagazine.template.data.notfound', 'admin');

            return $this->redirect($this->generateUrl('plugin_mail_magazine_template'));
        }

        // Formを取得
        $builder = $this->formFactory->createBuilder(MailMagazineTemplateEditType::class, $Template);
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // 入力項目確認処理を行う.
            // エラーであれば元の画面を表示する
            if (!$form->isValid()) {
                $this->addError('admin.flash.register_failed', 'admin');

                return [
                    'form' => $form->createView(),
                    'Template' => $Template,
                ];
            }

            try {
                $this->mailMagazineTemplateRepository->save($Template);
                $this->entityManager->flush();
                // 成功時のメッセージを登録する
                $this->addSuccess('admin.plugin.mailmagazine.template.save.complete', 'admin');
            } catch (\Exception $e) {
                $this->addError('admin.plugin.mailmagazine.template.save.failure', 'admin');

                return [
                    'form' => $form->createView(),
                    'Template' => $Template,
                ];
            }
        }

        // メルマガテンプレート一覧へリダイレクト
        return $this->redirect($this->generateUrl('plugin_mail_magazine_template'));
    }

    /**
     * メルマガテンプレート登録画面を表示する.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template/regist", name="plugin_mail_magazine_template_regist")
     * @Template("@MailMagazine/admin/template_edit.twig")
     *
     * @return array
     */
    public function regist()
    {
        $Template = new MailMagazineTemplate();

        // formの作成
        $form = $this->formFactory
            ->createBuilder(MailMagazineTemplateEditType::class, $Template)
            ->getForm();

        return [
            'form' => $form->createView(),
            'Template' => $Template,
        ];
    }
}
