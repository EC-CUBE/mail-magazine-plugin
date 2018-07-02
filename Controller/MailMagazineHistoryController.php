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
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Knp\Component\Pager\Paginator;
use Plugin\MailMagazine\Entity\MailMagazineSendHistory;
use Plugin\MailMagazine\Repository\MailMagazineSendHistoryRepository;
use Plugin\MailMagazine\Service\MailMagazineService;
use Plugin\MailMagazine\Util\MailMagazineHistoryFilePaginationSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Entity\Master\Sex;

class MailMagazineHistoryController extends AbstractController
{
    /**
     * @var MailMagazineSendHistoryRepository
     */
    protected $mailMagazineSendHistoryRepository;

    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var MailMagazineService
     */
    protected $mailMagazineService;

    /**
     * MailMagazineHistoryController constructor.
     *
     * @param MailMagazineService $mailMagazineService
     * @param MailMagazineSendHistoryRepository $mailMagazineSendHistoryRepository
     * @param PageMaxRepository $pageMaxRepository
     */
    public function __construct(
        MailMagazineService $mailMagazineService,
        MailMagazineSendHistoryRepository $mailMagazineSendHistoryRepository,
        PageMaxRepository $pageMaxRepository
    ) {
        $this->mailMagazineService = $mailMagazineService;
        $this->mailMagazineSendHistoryRepository = $mailMagazineSendHistoryRepository;
        $this->pageMaxRepository = $pageMaxRepository;
    }

    /**
     * 配信履歴一覧.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/history", name="plugin_mail_magazine_history")
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/history/{page_no}",
     *     requirements={"page_no" = "\d+"},
     *     name="plugin_mail_magazine_history_page"
     * )
     * @Template("@MailMagazine/admin/history_list.twig")
     *
     * @param Request $request
     * @param Paginator $paginator
     * @param int $page_no
     *
     * @return array
     */
    public function index(Request $request, Paginator $paginator, $page_no = 1)
    {
        $pageNo = $page_no;
        $pageMaxis = $this->pageMaxRepository->findAll();
        $pageCount = $this->eccubeConfig['eccube_default_page_count'];
        $pageCountParam = $request->get('page_count');
        if ($pageCountParam && is_numeric($pageCountParam)) {
            foreach ($pageMaxis as $pageMax) {
                if ($pageCountParam == $pageMax->getName()) {
                    $pageCount = $pageMax->getName();
                    break;
                }
            }
        }

        // リストをView変数に突っ込む
        $pagination = null;
        $searchForm = $this->formFactory
            ->createBuilder()
            ->getForm();
        $searchForm->handleRequest($request);
        $searchData = $searchForm->getData();

        $qb = $this->mailMagazineSendHistoryRepository->getQueryBuilderBySearchData($searchData);

        $pagination = $paginator->paginate($qb, $pageNo, $pageCount);

        return [
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_count' => $pageCount
        ];
    }

    /**
     * プレビュー
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/history/{id}/preview",
     *     requirements={"id":"\d+|"},
     *     name="plugin_mail_magazine_history_preview"
     * )
     * @Template("@MailMagazine/admin/history_preview.twig")
     *
     * @param MailMagazineSendHistory $mailMagazineSendHistory
     *
     * @return array
     */
    public function preview(MailMagazineSendHistory $mailMagazineSendHistory)
    {
        // 配信履歴を取得する
        return [
            'history' => $mailMagazineSendHistory,
        ];
    }

    /**
     * 配信条件を表示する.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/history/{id}/condition",
     *      requirements={"id":"\d+|"},
     *      name="plugin_mail_magazine_history_condition",
     * )
     * @Template("@MailMagazine/admin/history_condition.twig")
     *
     * @param MailMagazineSendHistory $mailMagazineSendHistory
     *
     * @throws BadRequestHttpException
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response|array
     */
    public function condition(MailMagazineSendHistory $mailMagazineSendHistory)
    {
        // 検索条件をアンシリアライズする
        // base64,serializeされているので注意すること
        $searchData = unserialize(base64_decode($mailMagazineSendHistory->getSearchData()));

        // 区分値を文字列に変更する
        // 必要な項目のみ
        $displayData = $this->searchDataToDisplayData($searchData);

        return [
            'search_data' => $displayData,
        ];
    }

    /**
     * search_dataの配列を表示用に変換する.
     *
     * @param array $searchData
     *
     * @return array
     */
    protected function searchDataToDisplayData($searchData)
    {
        $data = $searchData;

        // 会員種別
        $val = [];
        if (isset($searchData['customer_status']) && is_array($searchData['customer_status'])) {
            array_map(function ($CustomerStatus) use (&$val) {
                /** @var \Eccube\Entity\Master\CustomerStatus $CustomerStatus */
                $val[] = $CustomerStatus->getName();
            }, $searchData['customer_status']);
        }
        $data['customer_status'] = implode(', ', $val);

        // 性別
        $val = [];
        if (isset($searchData['sex']) && is_array($searchData['sex'])) {
            array_map(function ($Sex) use (&$val) {
                /** @var Sex $Sex */
                $val[] = $Sex->getName();
            }, $searchData['sex']);
        }
        $data['sex'] = implode(', ', $val);

        // 誕生月
        $val = null;
        if (!is_null($searchData['birth_month'])) {
            $val = $searchData['birth_month'] + 1;
        }
        $data['birth_month'] = $val;

        return $data;
    }

    /**
     * 配信履歴を論理削除する
     * RequestがPOST以外の場合はBadRequestHttpExceptionを発生させる.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/history/{id}/delete",
     *     requirements={"id":"\d+|"},
     *     name="plugin_mail_magazine_history_delete"
     * )
     *
     * @param Application $app
     * @param Request     $request
     * @param unknown     $id
     *
     * @throws BadRequestHttpException
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Application $app, Request $request, $id)
    {
        die(var_dump(__METHOD__));
        // POSTかどうか判定
        if ('POST' !== $request->getMethod()) {
            throw new BadRequestHttpException();
        }

        // パラメータ$idにマッチするデータが存在するか判定
        if (!$id) {
            throw new BadRequestHttpException();
        }

        // 配信履歴を取得する
        $sendHistory = $this->getMailMagazineSendHistoryRepository($app)->find($id);

        // 配信履歴がない場合はエラーメッセージを表示する
        if (is_null($sendHistory)) {
            $app->addError('admin.plugin.mailmagazine.history.datanotfound', 'admin');

            return $app->redirect($app->url('plugin_mail_magazine_history'));
        }

        // POSTかつ$idに対応するdtb_send_historyのレコードがあれば、del_flg = 1に設定して更新
        $sendHistory->setDelFlg(Constant::ENABLED);

        $app['orm.em']->persist($sendHistory);
        $app['orm.em']->flush($sendHistory);

        $service = $this->getMailMagazineService($app);
        $service->unlinkHistoryFiles($id);

        $app->addSuccess('admin.plugin.mailmagazine.history.delete.sucesss', 'admin');

        // メルマガテンプレート一覧へリダイレクト
        return $app->redirect($app->url('plugin_mail_magazine_history'));
    }

    /**
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/history/retry", name="plugin_mail_magazine_history_retry")
     *
     * @param Application $app
     * @param Request $request
     * @return mixed
     */
    public function retry(Application $app, Request $request)
    {
        die(var_dump(__METHOD__));
        // Ajax/POSTでない場合は終了する
        if (!$request->isXmlHttpRequest() || 'POST' !== $request->getMethod()) {
            throw new BadRequestHttpException();
        }

        $id = $request->get('id');

        log_info('メルマガ再試行前処理開始', array('id' => $id));

        $service = $this->getMailMagazineService($app);
        $service->markRetry($id);

        log_info('メルマガ再試行前処理完了', array('id' => $id));

        return $app->json(array('status' => true));
    }

    /**
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/history/result/{id}",
     *     requirements={"id":"\d+|"},
     *     name="plugin_mail_magazine_history_result"
     * )
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/history/result/{id}/{page_no}",
     *     requirements={"id":"\d+|", "page_no" = "\d+"},
     *     name="plugin_mail_magazine_history_result_page"
     * )
     * @Template("@MailMagazine/admin/history_result.twig")
     *
     * @param Request $request
     * @param MailMagazineSendHistory $mailMagazineSendHistory
     * @param Paginator $paginator
     * @param int $page_no
     *
     * @return mixed
     */
    public function result(Request $request, MailMagazineSendHistory $mailMagazineSendHistory, Paginator $paginator, $page_no = 1)
    {
        $resultFile = $this->mailMagazineService->getHistoryFileName($mailMagazineSendHistory->getId(), false);
        $pageMaxis = $this->pageMaxRepository->findAll();
        $pageCount = $request->get('page_count');
        $pageCount = $pageCount ? $pageCount : $this->eccubeConfig['eccube_default_page_count'];

        $pageNo = $page_no;
        $paginator->subscribe(new MailMagazineHistoryFilePaginationSubscriber());
        $pagination = $paginator->paginate($resultFile,
            empty($pageNo) ? 1 : $pageNo,
            $pageCount,
            array('total' => 1)
        );

        return [
            'historyId' => $mailMagazineSendHistory->getId(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_count' => $pageCount,
        ];
    }

    /**
     * @param Application $app
     *
     * @return MailMagazineService
     */
    private function getMailMagazineService(Application $app)
    {
        return $app['eccube.plugin.mail_magazine.service.mail'];
    }

    /**
     * @param Application $app
     *
     * @return MailMagazineSendHistoryRepository
     */
    private function getMailMagazineSendHistoryRepository(Application $app)
    {
        return $app['eccube.plugin.mail_magazine.repository.mail_magazine_history'];
    }
}
