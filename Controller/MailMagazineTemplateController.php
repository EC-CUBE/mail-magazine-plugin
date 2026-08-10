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

namespace Plugin\MailMagazine44\Controller;

use Eccube\Controller\AbstractController;
use Plugin\MailMagazine44\Entity\MailMagazineTemplate;
use Plugin\MailMagazine44\Form\Type\MailMagazineTemplateEditType;
use Plugin\MailMagazine44\Repository\MailMagazineTemplateRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        MailMagazineTemplateRepository $mailMagazineTemplateRepository,
    ) {
        $this->mailMagazineTemplateRepository = $mailMagazineTemplateRepository;
    }

    /**
     * 一覧表示.
     *
     * @return array<string, mixed>
     */
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/template', name: 'plugin_mail_magazine_template')]
    #[Template('@MailMagazine44/admin/template_list.twig')]
    public function index(): array
    {
        $templateList = $this->mailMagazineTemplateRepository->findAll();

        return [
            'TemplateList' => $templateList,
        ];
    }

    /**
     * preview画面表示.
     *
     * @return array<string, MailMagazineTemplate>
     */
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/template/{id}/preview', name: 'plugin_mail_magazine_template_preview', requirements: ['id' => '\d+'])]
    #[Template('@MailMagazine44/admin/preview.twig')]
    public function preview(#[MapEntity(id: 'id')] MailMagazineTemplate $mailMagazineTemplate): array
    {
        // プレビューページ表示
        return [
            'Template' => $mailMagazineTemplate,
        ];
    }

    /**
     * メルマガテンプレートを論理削除.
     */
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/template/{id}/delete', name: 'plugin_mail_magazine_template_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(#[MapEntity(id: 'id')] MailMagazineTemplate $mailMagazineTemplate): RedirectResponse
    {
        // POSTかどうか判定
        // パラメータ$idにマッチするデータが存在するか判定
        // POSTかつ$idに対応するdtb_mailmagazine_templateのレコードがあれば、del_flg = 1に設定して更新
        try {
            $this->isTokenValid();
            $this->mailMagazineTemplateRepository->delete($mailMagazineTemplate);
            $this->entityManager->flush();
            $this->addSuccess('admin.mailmagazine.template.delete.complete', 'admin');
        } catch (\Exception) {
            $this->addError('admin.mailmagazine.template.delete.failure', 'admin');
        }

        // メルマガテンプレート一覧へリダイレクト
        return $this->redirectToRoute('plugin_mail_magazine_template');
    }

    /**
     * テンプレート編集画面表示.
     *
     * @return array<string, mixed>
     */
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/template/{id}/edit', name: 'plugin_mail_magazine_template_edit', requirements: ['id' => '\d+'])]
    #[Template('@MailMagazine44/admin/template_edit.twig')]
    public function edit(#[MapEntity(id: 'id')] MailMagazineTemplate $mailMagazineTemplate): array
    {
        // formの作成
        $form = $this->formFactory
            ->createBuilder(MailMagazineTemplateEditType::class, $mailMagazineTemplate)
            ->getForm();

        return [
            'form' => $form->createView(),
            'Template' => $mailMagazineTemplate,
        ];
    }

    /**
     * テンプレート編集確定処理.
     *
     * @return Response|array<string, mixed>
     */
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/template/commit/{id}', name: 'plugin_mail_magazine_template_commit', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[Template('@MailMagazine44/admin/template_edit.twig')]
    public function commit(Request $request, ?int $id = null): Response|array
    {
        $Template = $id ? $this->mailMagazineTemplateRepository->find($id) : new MailMagazineTemplate();

        // データが存在しない場合はメルマガテンプレート一覧へリダイレクト
        if (is_null($Template)) {
            $this->addError('admin.mailmagazine.template.data.notfound', 'admin');

            return $this->redirectToRoute('plugin_mail_magazine_template');
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
                $this->addSuccess('admin.mailmagazine.template.save.complete', 'admin');
            } catch (\Exception) {
                $this->addError('admin.mailmagazine.template.save.failure', 'admin');

                return [
                    'form' => $form->createView(),
                    'Template' => $Template,
                ];
            }
        }

        // メルマガテンプレート一覧へリダイレクト
        return $this->redirectToRoute('plugin_mail_magazine_template');
    }

    /**
     * メルマガテンプレート登録画面を表示する.
     *
     * @return array<string, mixed>
     */
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/template/regist', name: 'plugin_mail_magazine_template_regist')]
    #[Template('@MailMagazine44/admin/template_edit.twig')]
    public function regist(): array
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
