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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Eccube\Controller\AbstractController;
use Plugin\MailMagazine\Entity\MailMagazineSendHistory;
use Plugin\MailMagazine\Entity\MailMagazineTemplate;
use Plugin\MailMagazine\Service\MailMagazineService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Util\FormUtil;
use Eccube\Repository\CustomerRepository;
use Knp\Component\Pager\Paginator;
use Plugin\MailMagazine\Form\Type\MailMagazineType;
use Doctrine\ORM\QueryBuilder;
use Eccube\Common\Constant;
use Plugin\MailMagazine\Repository\MailMagazineTemplateRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        MailMagazineService $mailMagazineService
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
     * @Route("/%eccube_admin_route%/plugin/mail_magazine", name="plugin_mail_magazine")
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/{page_no}", requirements={"page_no" = "\d+"}, name="plugin_mail_magazine_page")
     * @Template("@MailMagazine/admin/index.twig")
     *
     * @param Request $request
     * @param Paginator $paginator
     * @param integer $page_no
     *
     * @return \Symfony\Component\HttpFoundation\Response|array
     */
    public function index(Request $request, Paginator $paginator, $page_no = 1, \Swift_Mailer $mailer)
    {
        $session = $request->getSession();
        $pageNo = $page_no;
        $pageMaxis = $this->pageMaxRepository->findAll();
        $pageCount = $session->get('plugin.mailmagazine.search.page_count', $this->eccubeConfig['eccube_default_page_count']);
        $pageCountParam = $request->get('page_count');
        if ($pageCountParam && is_numeric($pageCountParam)) {
            foreach ($pageMaxis as $pageMax) {
                if ($pageCountParam == $pageMax->getName()) {
                    $pageCount = $pageMax->getName();
                    $session->set('plugin.mailmagazine.search.page_count', $pageCount);
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
                $session->set('plugin.mailmagazine.search', FormUtil::getViewData($searchForm));
                $session->set('plugin.mailmagazine.search.page_no', $pageNo);
            } else {
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $pageNo,
                    'page_count' => $pageCount,
                    'has_errors' => true,
                ];
            }
        } else {
            if (null !== $pageNo || $request->get('resume')) {
                if ($pageNo) {
                    $session->set('plugin.mailmagazine.search.page_no', (int) $pageNo);
                } else {
                    $pageNo = $session->get('plugin.mailmagazine.search.page_no', 1);
                }
                $viewData = $session->get('plugin.mailmagazine.search', []);
            } else {
                $pageNo = 1;
                $viewData = FormUtil::getViewData($searchForm);
                $session->set('plugin.mailmagazine.search', $viewData);
                $session->set('plugin.mailmagazine.search.page_no', $pageNo);
            }
            $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
        }

        $searchData['plg_mailmagazine_flg'] = Constant::ENABLED;
        /** @var QueryBuilder $qb */
        $qb = $this->customerRepository->getQueryBuilderBySearchData($searchData);
        $pagination = $paginator->paginate(
            $qb,
            $pageNo,
            $pageCount
        );

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_count' => $pageMax,
            'has_errors' => false
        ];
    }

    /**
     * テンプレート選択
     * RequestがPOST以外の場合はBadRequestHttpExceptionを発生させる.
     *
     * @Method("POST")
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/select/{id}",
     *     requirements={"id":"\d+|"},
     *     name="plugin_mail_magazine_select"
     * )
     * @Template("@MailMagazine/admin/template_select.twig")
     *
     * @param Request     $request
     * @param string      $id
     *
     * @return \Symfony\Component\HttpFoundation\Response|array
     */
    public function select(Request $request, $id = null)
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
            $newTemplate = $form->get('template')->getData();
            $data = $form->getData();
            $form = $this->formFactory->createBuilder(MailMagazineType::class, null)->getForm();
            $form->setData($data);

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

                $form->get('template')->setData($newTemplate);
                $form->get('subject')->setData($newSubject);
                $form->get('body')->setData($newBody);
                $form->get('htmlBody')->setData($newHtmlBody);
            } else {
                // テンプレート「無し」が選択された場合は、フォームをクリアする
                $form->get('subject')->setData('');
                $form->get('body')->setData('');
                $form->get('htmlBody')->setData('');
            }
        } elseif ($request->get('mode') == 'confirm') {
            $form = $this->formFactory
                ->createBuilder(MailMagazineType::class)
                ->getForm();
            $form->handleRequest($request);
            if ($form->isValid()) {
                return $this->render('@MailMagazine/admin/confirm.twig', [
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
                        'constraints' => false
                    ]
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
     *
     * @Method("POST")
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/prepare", name="plugin_mail_magazine_prepare")
     *
     * @param Request     $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function prepare( Request $request)
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
            $this->addError('admin.plugin.mailmagazine.send.register.failure', 'admin');
        }

        // フラッシュスコープにIDを保持してリダイレクト後に送信処理を開始できるようにする
        $this->session->getFlashBag()->add('eccube.plugin.mailmagazine.history', $sendId);

        log_info('メルマガ配信前処理完了', array('sendId' => $sendId));

        // 配信履歴画面に遷移する
        return $this->redirect($this->generateUrl('plugin_mail_magazine_history'));
    }

    /**
     * 配信処理
     * 配信終了後配信履歴に遷移する
     * RequestがAjaxかつPOSTでなければBadRequestHttpExceptionを発生させる.
     *
     * @Method("POST")
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/commit", name="plugin_mail_magazine_commit")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function commit(Request $request)
    {
        // Ajax/POSTでない場合は終了する
        if (!$request->isXmlHttpRequest() || 'POST' !== $request->getMethod()) {
            throw new BadRequestHttpException();
        }

        // タイムアウトしないようにする
        set_time_limit(0);

        // デフォルトの設定ではメールをスプールしてからレスポンス後にメールを一括で送信する。
        // レスポンス後に一括送信した場合、メールのエラーをハンドリングできないのでスプールしないように設定。

        $id = $request->get('id');
        $offset = (int) $request->get('offset', 0);
        $max = (int) $request->get('max', 100);

        log_info('メルマガ配信処理開始', array('id' => $id, 'offset' => $offset, 'max' => $max));

        /** @var MailMagazineSendHistory $sendHistory */
        $sendHistory = $this->mailMagazineService->sendMailMagazine($id, $offset, $max);

        if ($sendHistory->isComplete()) {
            $this->mailMagazineService->sendMailMagazineCompleateReportMail();
        }

        log_info('メルマガ配信処理完了', array('id' => $id, 'offset' => $offset, 'max' => $max));

        return $this->json([
            'status' => true,
            'id' => $id,
            'total' => $sendHistory->getSendCount(),
            'count' => $sendHistory->getCompleteCount(),
        ]);
    }

    /**
     * テストメール送信
     *
     * @Method("POST")
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/test", name="plugin_mail_magazine_test")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function sendTest(Request $request)
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
