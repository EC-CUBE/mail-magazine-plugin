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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MailMagazineHistoryController
{
    private $main_title;
    private $sub_title;

    public function __construct()
    {
    }

    /**
     * 配信履歴一覧
     */
    public function index(Application $app, Request $request)
    {
        // dtb_send_historyからdel_flg = 0のデータを抽出
        // リストをView変数に突っ込む
        $pagination = null;
        $searchForm = $app['form.factory']
            ->createBuilder()
            ->getForm();
        $searchForm->handleRequest($request);
        $searchData = $searchForm->getData();

        $pageNo = $request->get('page_no');

        $qb = $app['orm.em']->createQueryBuilder();
        $qb->select("d")
            ->from("\Plugin\MailMagazine\Entity\MailMagazineSendHistory", "d")
            ->where("d.del_flg = :delFlg")
            ->setParameter('delFlg', Constant::DISABLED)
            ->orderBy("d.start_date", "DESC");

        $pagination = $app['paginator']()->paginate(
                $qb,
                empty($pageNo) ? 1 : $pageNo,
                empty($searchData['pagemax']) ? 10 : $searchData['pagemax']->getId()
        );

        return $app->render('MailMagazine/View/admin/history_list.twig', array(
            'pagination' => $pagination
        ));
    }

    /**
    * プレビュー
    */
    public function preview(Application $app, Request $request, $id)
    {
        // dtb_send_historyから対象レコード抽出
        // subject/bodyを抽出し、以下のViewへ渡す
        // パラメータ$idにマッチするデータが存在するか判定
        if (is_null($id)) {
            throw new BadRequestHttpException();
        }

        // 配信履歴を取得する
        $sendHistory = $app['eccube.plugin.mail_magazine.repository.mail_magazine_history']->find($id);

        if(is_null($sendHistory)) {
            $app->addError('admin.mailmagazine.history.datanotfound', 'admin');
            return $app->redirect($app->url('admin_mail_magazine_history'));
        }

        return $app->render('MailMagazine/View/admin/hitsory_preview.twig', array(
            'history' => $sendHistory
        ));
    }

    /**
     * 配信条件を表示する
     *
     * @param Application $app
     * @param Request $request
     * @param unknown $id
     * @throws BadRequestHttpException
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function condition(Application $app, Request $request, $id)
    {
        // dtb_send_historyから対象レコード抽出
        // dtb_send_history.search_dataを逆シリアライズした上で、各変数をViewに渡す

        if (is_null($id)) {
            throw new BadRequestHttpException();
        }

        // 配信履歴を取得する
        $sendHistory = $app['eccube.plugin.mail_magazine.repository.mail_magazine_history']->find($id);

        if(is_null($sendHistory)) {
            $app->addError('admin.mailmagazine.history.datanotfound', 'admin');
            return $app->redirect($app->url('admin_mail_magazine_history'));
        }

        // 検索条件をアンシリアライズする
        // base64,serializeされているので注意すること
        $searchData = unserialize(base64_decode($sendHistory->getSearchData()));

        // 区分値を文字列に変更する
        // 必要な項目のみ
        $displayData = $this->searchDataToDisplayData($searchData);

        return $app->render('MailMagazine/View/admin/hitsory_condition.twig', array(
            'search_data' => $displayData
        ));
    }

    /**
     * search_dataの配列を表示用に変換する.
     *
     * @param unknown $searchData
     */
    protected function searchDataToDisplayData($searchData) {
        $data = $searchData;

        // 会員種別
        $val = null;
        if(!is_null($searchData['customer_status'])) {
            if(count($searchData['customer_status']->toArray()) > 0) {
                $val = implode(" ", $searchData['customer_status']->toArray());
            }
        }
        $data['customer_status'] = $val;

        // 性別
        $val = null;
        if(!is_null($searchData['sex'])) {
            if(count($searchData['sex']->toArray()) > 0) {
                $val = implode(" ", $searchData['sex']->toArray());
            }
        }
        $data['sex'] = $val;

        return $data;
    }

    /**
     * 配信履歴を論理削除する
     * RequestがPOST以外の場合はBadRequestHttpExceptionを発生させる
     *
     * @param Application $app
     * @param Request $request
     * @param unknown $id
     * @throws BadRequestHttpException
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Application $app, Request $request, $id)
    {

        // POSTかどうか判定
        if ('POST' !== $request->getMethod()) {
            throw new BadRequestHttpException();
        }

        // パラメータ$idにマッチするデータが存在するか判定
        if (!$id) {
            throw new BadRequestHttpException();
        }

        // 配信履歴を取得する
        $sendHistory = $app['eccube.plugin.mail_magazine.repository.mail_magazine_history']->find($id);

        // 配信履歴がない場合はエラーメッセージを表示する
        if(is_null($sendHistory)) {
            $app->addError('admin.mailmagazine.history.datanotfound', 'admin');
            return $app->redirect($app->url('admin_mail_magazine_history'));
        }

        // POSTかつ$idに対応するdtb_send_historyのレコードがあれば、del_flg = 1に設定して更新
        $sendHistory->setDelFlg(Constant::ENABLED);

        $app['orm.em']->persist($sendHistory);
        $app['orm.em']->flush();

        $app->addSuccess('admin.mailmagazine.history.delete.sucesss', 'admin');

        // メルマガテンプレート一覧へリダイレクト
        return $app->redirect($app->url('admin_mail_magazine_history'));
    }
}
