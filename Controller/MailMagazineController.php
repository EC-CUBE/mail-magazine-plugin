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

use Doctrine\ORM\QueryBuilder;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\MailMagazine44\Entity\MailMagazineSendHistory;
use Plugin\MailMagazine44\Entity\MailMagazineTemplate;
use Plugin\MailMagazine44\Form\Type\MailMagazineType;
use Plugin\MailMagazine44\Repository\MailMagazineTemplateRepository;
use Plugin\MailMagazine44\Service\MailMagazineService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class MailMagazineController
 */
class MailMagazineController extends AbstractController
{
    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var MailMagazineTemplateRepository
     */
    protected $mailMagazineTemplateRepository;

    /**
     * @var MailMagazineService
     */
    protected $mailMagazineService;

    /**
     * MailMagazineController constructor.
     *
     * @param PageMaxRepository $pageMaxRepository
     * @param CustomerRepository $customerRepository
     * @param MailMagazineTemplateRepository $magazineTemplateRepository
     * @param MailMagazineService $mailMagazineService
     */
    public function __construct(
        PageMaxRepository $pageMaxRepository,
        CustomerRepository $customerRepository,
        MailMagazineTemplateRepository $magazineTemplateRepository,
        MailMagazineService $mailMagazineService,
        private readonly PaginatorInterface $paginator,
    ) {
        $this->pageMaxRepository = $pageMaxRepository;
        $this->customerRepository = $customerRepository;
        $this->mailMagazineTemplateRepository = $magazineTemplateRepository;
        $this->mailMagazineService = $mailMagazineService;
    }

    /**
     * 配信内容設定検索画面を表示する.
     * 左ナビゲーションの選択はGETで遷移する.
     *
     * @return Response|array<string, mixed>
     */
    #[Route('/%eccube_admin_route%/plugin/mail_magazine', name: 'plugin_mail_magazine')]
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/{page_no}', name: 'plugin_mail_magazine_page', requirements: ['page_no' => '\d+'])]
    #[Template('@MailMagazine44/admin/index.twig')]
    public function index(Request $request, ?int $page_no = null): Response|array
    {
        $session = $request->getSession();
        $pageNo = $page_no;
        $pageMaxis = $this->pageMaxRepository->findAll();
        $pageCount = $session->get('mailmagazine.search.page_count', $this->eccubeConfig['eccube_default_page_count']);
        $pageCountParam = $request->get('page_count');
        if ($pageCountParam && is_numeric($pageCountParam)) {
            foreach ($pageMaxis as $pageMax) {
                if ($pageCountParam == $pageMax->getName()) {
                    $pageCount = $pageMax->getName();
                    $session->set('mailmagazine.search.page_count', $pageCount);
                    break;
                }
            }
        }
        $pageMax = $this->eccubeConfig['eccube_default_page_count'];

        $pagination = null;
        $searchForm = $this->formFactory
            ->createBuilder(MailMagazineType::class)
            ->getForm();

        $searchForm->remove('id');
        $searchForm->remove('subject');
        $searchForm->remove('body');
        $searchForm->remove('htmlBody');

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
            if ($searchForm->isValid()) {
                $searchData = $searchForm->getData();
                $pageNo = 1;
                $session->set('mailmagazine.search', FormUtil::getViewData($searchForm));
                $session->set('mailmagazine.search.page_no', $pageNo);
            } else {
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $pageNo ?: 1,
                    'page_count' => $pageCount,
                    'has_errors' => true,
                ];
            }
        } else {
            if (null !== $pageNo || $request->get('resume')) {
                if ($pageNo) {
                    $session->set('mailmagazine.search.page_no', (int) $pageNo);
                } else {
                    $pageNo = $session->get('mailmagazine.search.page_no', 1);
                }
                $viewData = $session->get('mailmagazine.search', []);
            } else {
                $pageNo = 1;
                $viewData = FormUtil::getViewData($searchForm);
                $session->set('mailmagazine.search', $viewData);
                $session->set('mailmagazine.search.page_no', $pageNo);
            }
            $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
        }

        $searchData['plg_mailmagazine_flg'] = Constant::ENABLED;
        /** @var QueryBuilder $qb */
        $qb = $this->customerRepository->getQueryBuilderBySearchData($searchData);
        $pagination = $this->paginator->paginate(
            $qb,
            $pageNo,
            $pageCount
        );

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_count' => $pageCount,
            'has_errors' => false,
        ];
    }

    /**
     * テンプレート選択
     * RequestがPOST以外の場合はBadRequestHttpExceptionを発生させる.
     *
     * @return Response|array<string, mixed>
     */
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/select/{id}', name: 'plugin_mail_magazine_select', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[Template('@MailMagazine44/admin/template_select.twig')]
    public function select(Request $request, ?int $id = null): Response|array
    {
        /** @var MailMagazineTemplate $Template */
        $Template = null;

        // テンプレート選択によるPOSTの場合はテンプレートからデータを取得する
        if ($request->get('mode') == 'select') {
            // Formの取得
            $form = $this->formFactory
                ->createBuilder(MailMagazineType::class)
                ->getForm();
            $form->handleRequest($request);
            $data = FormUtil::getViewData($form);
            $form = $this->formFactory->createBuilder(MailMagazineType::class, null)->getForm();

            if ($id) {
                // テンプレート「無し」が選択された場合は、選択されたテンプレートのデータを取得する
                $Template = $this->mailMagazineTemplateRepository->find($id);

                if (is_null($Template)) {
                    throw new NotFoundHttpException();
                }

                // テンプレートを表示する
                $newSubject = $Template->getSubject();
                $newBody = $Template->getBody();
                $newHtmlBody = $Template->getHtmlBody();

                $data['template'] = $Template->getId();
                $data['subject'] = $newSubject;
                $data['body'] = $newBody;
                $data['htmlBody'] = $newHtmlBody;
            } else {
                // テンプレート「無し」が選択された場合は、フォームをクリアする
                $data['subject'] = '';
                $data['body'] = '';
                $data['htmlBody'] = '';
            }

            $form->submit($data);
        } elseif ($request->get('mode') == 'confirm') {
            $form = $this->formFactory
                ->createBuilder(MailMagazineType::class)
                ->getForm();
            $form->handleRequest($request);
            if ($form->isValid()) {
                return $this->render('@MailMagazine44/admin/confirm.twig', [
                    'form' => $form->createView(),
                    'subject_itm' => $form['subject']->getData(),
                    'body_itm' => $form['body']->getData(),
                    'htmlBody_itm' => $form['htmlBody']->getData(),
                    'id' => $id,
                    'testMailTo' => $this->mailMagazineService->getAdminEmail(),
                ]);
            }
        } else {
            $form = $this->formFactory
                ->createBuilder(MailMagazineType::class, null, [
                    'eccube_form_options' => [
                        'constraints' => false,
                    ],
                ])
                ->getForm();
            $form->handleRequest($request);
        }

        return [
            'form' => $form->createView(),
            'id' => $id,
        ];
    }

    /**
     * 配信前処理
     * 配信履歴データを作成する.
     */
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/prepare', name: 'plugin_mail_magazine_prepare', methods: ['POST'])]
    public function prepare(Request $request): RedirectResponse
    {
        log_info('メルマガ配信前処理開始');

        // Formを取得する
        $form = $this->formFactory
            ->createBuilder(MailMagazineType::class, null)
            ->getForm();
        $form->handleRequest($request);
        $data = $form->getData();

        if (!$form->isValid()) {
            throw new BadRequestHttpException();
        }

        // タイムアウトしないようにする
        set_time_limit(0);

        /** @var MailMagazineService $service */
        $service = $this->mailMagazineService;

        // 配信履歴を登録する
        $sendId = $service->createMailMagazineHistory($data);
        if (is_null($sendId)) {
            $this->addError('admin.mailmagazine.send.register.failure', 'admin');
        }

        // フラッシュスコープにIDを保持してリダイレクト後に送信処理を開始できるようにする
        $this->session->getFlashBag()->add('eccube.mailmagazine.history', $sendId);

        log_info('メルマガ配信前処理完了', ['sendId' => $sendId]);

        // 配信履歴画面に遷移する
        return $this->redirectToRoute('plugin_mail_magazine_history');
    }

    /**
     * 配信処理
     * 配信終了後配信履歴に遷移する
     * RequestがAjaxかつPOSTでなければBadRequestHttpExceptionを発生させる.
     */
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/commit', name: 'plugin_mail_magazine_commit', methods: ['POST'])]
    public function commit(Request $request): JsonResponse
    {
        // Ajax/POSTでない場合は終了する
        if (!$request->isXmlHttpRequest() || 'POST' !== $request->getMethod()) {
            throw new BadRequestHttpException();
        }

        // タイムアウトしないようにする
        set_time_limit(0);

        // デフォルトの設定ではメールをスプールしてからレスポンス後にメールを一括で送信する。
        // レスポンス後に一括送信した場合、メールのエラーをハンドリングできないのでスプールしないように設定。

        $id = (int) $request->get('id');
        $offset = (int) $request->get('offset', 0);
        $max = (int) $request->get('max', 100);

        log_info('メルマガ配信処理開始', ['id' => $id, 'offset' => $offset, 'max' => $max]);

        /** @var MailMagazineSendHistory $sendHistory */
        $sendHistory = $this->mailMagazineService->sendrMailMagazine($id, $offset, $max);

        if ($sendHistory->isComplete()) {
            $this->mailMagazineService->sendMailMagazineCompleateReportMail();
        }

        log_info('メルマガ配信処理完了', ['id' => $id, 'offset' => $offset, 'max' => $max]);

        return $this->json([
            'status' => true,
            'id' => $id,
            'total' => $sendHistory->getSendCount(),
            'count' => $sendHistory->getCompleteCount(),
        ]);
    }

    /**
     * テストメール送信
     */
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/test', name: 'plugin_mail_magazine_test', methods: ['POST'])]
    public function sendTest(Request $request): JsonResponse
    {
        // Ajax/POSTでない場合は終了する
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        log_info('テストメール配信処理開始');

        $data = $request->request->all();
        $this->mailMagazineService->sendTestMail($data);

        log_info('テストメール配信処理完了');

        return $this->json(['status' => true]);
    }
}
