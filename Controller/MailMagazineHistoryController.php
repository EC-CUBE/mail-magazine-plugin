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
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\MailMagazine44\Entity\MailMagazineSendHistory;
use Plugin\MailMagazine44\Repository\MailMagazineSendHistoryRepository;
use Plugin\MailMagazine44\Service\MailMagazineService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Eccube\Repository\Master\PageMaxRepository;
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
     * @return array<string, mixed>
     */
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/history', name: 'plugin_mail_magazine_history')]
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/history/{page_no}', name: 'plugin_mail_magazine_history_page', requirements: ['page_no' => '\d+'])]
    #[Template('@MailMagazine44/admin/history_list.twig')]
    public function index(Request $request, PaginatorInterface $paginator, int $page_no = 1): array
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
        $searchData = $searchForm->getData() ?? [];

        $qb = $this->mailMagazineSendHistoryRepository->getQueryBuilderBySearchData($searchData);

        $pagination = $paginator->paginate($qb, $pageNo, $pageCount);

        return [
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_count' => $pageCount,
        ];
    }

    /**
     * プレビュー
     *
     * @return array<string, MailMagazineSendHistory>
     */
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/history/{id}/preview', name: 'plugin_mail_magazine_history_preview', requirements: ['id' => '\d+'])]
    #[Template('@MailMagazine44/admin/history_preview.twig')]
    public function preview(#[MapEntity(id: 'id')] MailMagazineSendHistory $mailMagazineSendHistory): array
    {
        // 配信履歴を取得する
        return [
            'history' => $mailMagazineSendHistory,
        ];
    }

    /**
     * 配信条件を表示する.
     *
     * @return array<string, mixed>
     */
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/history/{id}/condition', name: 'plugin_mail_magazine_history_condition', requirements: ['id' => '\d+'])]
    #[Template('@MailMagazine44/admin/history_condition.twig')]
    public function condition(#[MapEntity(id: 'id')] MailMagazineSendHistory $mailMagazineSendHistory): array
    {
        // 検索条件をアンシリアライズする
        // base64,serializeされているので注意すること
        $encodedSearchData = $mailMagazineSendHistory->getSearchData();
        $decodedSearchData = null !== $encodedSearchData ? base64_decode($encodedSearchData, true) : false;
        if (false === $decodedSearchData) {
            throw new BadRequestHttpException('Invalid search data.');
        }
        $searchData = unserialize($decodedSearchData);
        if (!is_array($searchData)) {
            throw new BadRequestHttpException('Invalid search data.');
        }

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
    protected function searchDataToDisplayData(array $searchData): array
    {
        $data = $searchData;

        // 会員種別
        $val = [];
        if (isset($searchData['customer_status']) && is_array($searchData['customer_status'])) {
            array_map(function ($CustomerStatus) use (&$val): void {
                /* @var \Eccube\Entity\Master\CustomerStatus $CustomerStatus */
                $val[] = $CustomerStatus->getName();
            }, $searchData['customer_status']);
        }
        $data['customer_status'] = implode(', ', $val);

        // 性別
        $val = [];
        if (isset($searchData['sex']) && is_array($searchData['sex'])) {
            array_map(function (Sex $Sex) use (&$val): void {
                /* @var Sex $Sex */
                $val[] = $Sex->getName();
            }, $searchData['sex']);
        }
        $data['sex'] = implode(', ', $val);

        return $data;
    }

    /**
     * 配信履歴を論理削除する.
     */
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/history/{id}/delete', name: 'plugin_mail_magazine_history_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(#[MapEntity(id: 'id')] MailMagazineSendHistory $mailMagazineSendHistory): RedirectResponse
    {
        try {
            $this->isTokenValid();
            $id = $mailMagazineSendHistory->getId();
            $this->mailMagazineSendHistoryRepository->delete($mailMagazineSendHistory);
            $this->entityManager->flush();

            $this->mailMagazineService->unlinkHistoryFiles($id);

            $this->addSuccess('admin.mailmagazine.history.delete.sucesss', 'admin');
        } catch (\Exception $e) {
            $this->addError('admin.mailmagazine.history.delete.failure', 'admin');
        }

        // メルマガテンプレート一覧へリダイレクト
        return $this->redirect($this->generateUrl('plugin_mail_magazine_history'));
    }

    #[Route('/%eccube_admin_route%/plugin/mail_magazine/history/{id}/retry', name: 'plugin_mail_magazine_history_retry', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function retry(Request $request, #[MapEntity(id: 'id')] MailMagazineSendHistory $mailMagazineSendHistory): JsonResponse
    {
        // Ajax/POSTでない場合は終了する
        if (!$request->isXmlHttpRequest() || 'POST' !== $request->getMethod()) {
            throw new BadRequestHttpException();
        }

        try {
            log_info('メルマガ再試行前処理開始', ['id' => $mailMagazineSendHistory->getId()]);

            $this->mailMagazineService->markRetry($mailMagazineSendHistory->getId());

            log_info('メルマガ再試行前処理完了', ['id' => $mailMagazineSendHistory->getId()]);

            $status = true;
        } catch (\Exception $e) {
            log_error(__METHOD__, [$e]);
            $status = false;
        }

        return $this->json(['status' => $status]);
    }

    /**
     * @return array<string, mixed>
     */
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/history/result/{id}', name: 'plugin_mail_magazine_history_result', requirements: ['id' => '\d+'])]
    #[Route('/%eccube_admin_route%/plugin/mail_magazine/history/result/{id}/{page_no}', name: 'plugin_mail_magazine_history_result_page', requirements: ['id' => '\d+', 'page_no' => '\d+'])]
    #[Template('@MailMagazine44/admin/history_result.twig')]
    public function result(Request $request, #[MapEntity(id: 'id')] MailMagazineSendHistory $mailMagazineSendHistory, PaginatorInterface $paginator, int $page_no = 1): array
    {
        $resultFile = $this->mailMagazineService->getHistoryFileName($mailMagazineSendHistory->getId(), false);
        $pageMaxis = $this->pageMaxRepository->findAll();
        $pageCount = (int) ($request->get('page_count') ?: $this->eccubeConfig['eccube_default_page_count']);

        $pagination = $paginator->paginate($resultFile,
            $page_no,
            $pageCount
        );

        return [
            'historyId' => $mailMagazineSendHistory->getId(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_count' => $pageCount,
        ];
    }
}
