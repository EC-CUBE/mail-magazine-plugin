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

namespace Plugin\MailMagazine44\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Eccube\Common\Constant;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\BaseInfo;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CustomerRepository;
use Plugin\MailMagazine44\Entity\MailMagazineSendHistory;
use Plugin\MailMagazine44\Repository\MailMagazineSendHistoryRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * メルマガ配信処理のサービスクラス。
 *
 * メルマガ配信処理を行います。
 * 配信履歴はエンティティ`MailMagazineSendHistory`として永続化されますが、確実に大量のメールを配信するために配信ファイルと結果ファイルも使用します。
 * 配信ファイルと結果ファイルは同じフォーマットで、以下のようなCSV形式になります。
 * <ステータス>,<メールアドレス>,<会員名>
 *
 * ・配信ファイルについて
 * メルマガ配信前に作成され、すべてのメールを送信完了後に(送信失敗メールがあったかにかかわらず)削除されます。
 * 作成された配信ファイルのステータスはすべて`none`となります。
 *
 * ・結果ファイルについて
 * メールを1件送信するごとに1行づつ追記していきます。
 * 送信成功したメールは、ステータス`done`として結果ファイルに記録されます。
 * 何らかの理由で送信失敗したメールは、ステータスを'error'として結果ファイルに記録されます。
 *
 * ・メール配信処理が途中で止まったときの再送
 * 配信ファイルが残っているので、結果ファイルにある行以降の配信先に対してメールを送る。
 *
 * ・配信失敗したメールの再送
 * 結果ファイルのステータスが`error`になっている配信先に対してメールを送る。
 */
class MailMagazineService
{
    // ====================================
    // 定数宣言
    // ====================================

    // send_flagの定数
    /** メール未送信 */
    public const SEND_FLAG_NONE = 0;

    /** メール送信成功 */
    public const SEND_FLAG_SUCCESS = 1;

    /** メール送信失敗 */
    public const SEND_FLAG_FAILURE = 2;

    // ====================================
    // 変数宣言
    // ====================================
    /**
     * 最後の送信者に送信したメールの本文(テキスト形式).
     *
     * @var string
     */
    private string $lastSendMailBody = '';

    /**
     * 最後の送信者に送信したメールの本文(HTML形式).
     *
     * @var string
     */
    private string $lastSendMailHtmlBody = '';

    /**
     * @var string
     */
    private string $mailMagazineDir;

    /**
     * @var BaseInfo
     */
    public BaseInfo $BaseInfo;

    /**
     * MailMagazineService constructor.
     *
     * @param MailerInterface $mailer
     * @param BaseInfoRepository $baseInfoRepository
     * @param EccubeConfig $eccubeConfig
     * @param CustomerRepository $customerRepository
     * @param MailMagazineSendHistoryRepository $mailMagazineSendHistoryRepository
     * @param EntityManagerInterface $entityManager
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function __construct(
        protected MailerInterface $mailer,
        BaseInfoRepository $baseInfoRepository,
        protected EccubeConfig $eccubeConfig,
        protected CustomerRepository $customerRepository,
        protected MailMagazineSendHistoryRepository $mailMagazineSendHistoryRepository,
        protected EntityManagerInterface $entityManager,
    ) {
        $this->BaseInfo = $baseInfoRepository->get();
        $this->mailMagazineDir = $this->eccubeConfig['mail_magazine_dir'];
        if (!is_dir($this->mailMagazineDir) && !mkdir($this->mailMagazineDir, 0777, true) && !is_dir($this->mailMagazineDir)) {
            throw new \RuntimeException(sprintf('Could not create mail magazine directory "%s".', $this->mailMagazineDir));
        }
    }

    /**
     * Get mailMagazineDir
     *
     * @return string
     */
    public function getMailMagazineDir(): string
    {
        return $this->mailMagazineDir;
    }

    /**
     * Set mailMagazineDir
     *
     * @param $mailMagazineDir
     *
     * @return $this
     */
    public function setMailMagazineDir(string $mailMagazineDir): self
    {
        $this->mailMagazineDir = $mailMagazineDir;

        return $this;
    }

    /**
     * メールを送信する.
     *
     * @param array{email: string, subject: string, body: string, htmlBody?: string|null} $formData メルマガ情報
     *                  email: 送信先メールアドレス
     *                  subject: 件名
     *                  body：本文
     */
    public function sendMail(array $formData): void
    {
        // メール送信
        $message = (new Email())
            ->subject($formData['subject'])
            ->from(new Address($this->BaseInfo->getEmail01(), $this->BaseInfo->getShopName()))
            ->to($formData['email'])
            ->replyTo($this->BaseInfo->getEmail03())
            ->returnPath($this->BaseInfo->getEmail04())
            ->text($formData['body']);

        if ($formData['htmlBody']) {
            $message->html($formData['htmlBody'], 'text/html');
        }

        $this->mailer->send($message);
    }

    /**
     * 配信履歴の作成。
     *
     * 配信履歴データ(MailMagazineSendHistory)の作成と、配信履歴ファイルを作成します。
     *
     * @param array<string, mixed> $formData
     *
     * @return int 採番されたsend_id
     *             エラー時はfalseを返す
     */
    public function createMailMagazineHistory(array $formData): ?int
    {
        /* @var $qb QueryBuilder */
        $formData['plg_mailmagazine_flg'] = Constant::ENABLED;
        $qb = $this->customerRepository->getQueryBuilderBySearchData($formData);
        $customerList = $qb->getQuery()->getResult();

        $currentDatetime = new \DateTime();

        // -----------------------------
        // dtb_send_historyを登録する
        // -----------------------------
        $sendHistory = new MailMagazineSendHistory();

        // 登録値を設定する
        $sendHistory->setBody($formData['body']);
        if (isset($formData['htmlBody'])) {
            $sendHistory->setHtmlBody($formData['htmlBody']);
        }
        $sendHistory->setSubject($formData['subject']);
        $sendHistory->setSendCount(count($customerList));
        $sendHistory->setCompleteCount(0);
        $sendHistory->setErrorCount(0);

        $sendHistory->setEndDate(null);
        $sendHistory->setUpdateDate(null);

        $sendHistory->setCreateDate($currentDatetime);
        $sendHistory->setStartDate($currentDatetime);

        // Formから検索条件を取得し、シリアライズする(array)
        // 事前に不要な項目は削除する
        unset($formData['pageno']);
        unset($formData['pagemax']);
        unset($formData['id']);
        unset($formData['subject']);
        unset($formData['body']);

        if (isset($formData['sex']) && $formData['sex'] instanceof ArrayCollection) {
            $formData['sex'] = $formData['sex']->toArray();
        }
        if (isset($formData['customer_status']) && $formData['customer_status'] instanceof ArrayCollection) {
            $formData['customer_status'] = $formData['customer_status']->toArray();
        }

        $sendId = null;
        try {
            // serializeのみだとDB登録時にデータが欠損するのでBase64にする
            $sendHistory->setSearchData(base64_encode(serialize($formData)));
            $this->mailMagazineSendHistoryRepository->save($sendHistory);
            $this->entityManager->flush();

            $sendId = $sendHistory->getId();
            if (null === $sendId) {
                throw new \RuntimeException('Mail magazine history ID was not generated.');
            }
            $fp = fopen($this->getHistoryFileName($sendId), 'w');
            if (false === $fp) {
                throw new \RuntimeException('Could not open the mail magazine history file.');
            }
            foreach ($customerList as $customer) {
                if (false === fwrite($fp, self::SEND_FLAG_NONE.','.$customer->getId().','.$customer->getEmail().','.$customer->getName01().' '.$customer->getName02().PHP_EOL)) {
                    fclose($fp);
                    throw new \RuntimeException('Could not write the mail magazine history file.');
                }
            }
            fclose($fp);
        } catch (\Exception $e) {
            log_error(__METHOD__, [$e]);
        }

        return $sendId;
    }

    /**
     * 履歴ファイルを結果ファイルにマージする。
     * 例)
     * ・履歴ファイル
     * none,aaa@example.com,aaa
     * none,bbb@example.com,bbb
     * none,ccc@example.com,ccc.
     *
     * ・結果ファイル
     * done,aaa@example.com,aaa
     *
     * ・マージした結果ファイル
     * done,aaa@example.com,aaa
     * none,bbb@example.com,bbb
     * none,ccc@example.com,ccc
     *
     * @param string $fileHistory 履歴ファイル
     * @param string $fileResult 結果ファイル
     */
    private function mergeHistoryFile(string $fileHistory, string $fileResult): void
    {
        // 結果ファイルのバイト数
        $resultBytes = filesize($fileResult);
        if (false === $resultBytes) {
            throw new \RuntimeException('Could not read the mail magazine result file size.');
        }

        // 結果ファイルのバイト数分、履歴ファイルを読み飛ばす
        $fin = fopen($fileHistory, 'r');
        $fout = fopen($fileResult, 'a');
        if (false === $fin || false === $fout) {
            if (is_resource($fin)) {
                fclose($fin);
            }
            if (is_resource($fout)) {
                fclose($fout);
            }
            throw new \RuntimeException('Could not open mail magazine files.');
        }
        if (0 !== fseek($fin, $resultBytes)) {
            fclose($fin);
            fclose($fout);
            throw new \RuntimeException('Could not seek the mail magazine history file.');
        }

        // 残りの履歴ファイルの内容を結果ファイルに追記する
        while (false !== ($line = fgets($fin))) {
            if (false === fwrite($fout, $line)) {
                fclose($fin);
                fclose($fout);
                throw new \RuntimeException('Could not write the mail magazine result file.');
            }
        }

        fclose($fin);
        fclose($fout);
    }

    /**
     * Mark history to retry send
     *
     * @param $sendId
     */
    public function markRetry(int $sendId): void
    {
        // 再送時の前処理
        $fileHistory = $this->getHistoryFileName($sendId);
        $fileResult = $this->getHistoryFileName($sendId, false);

        // 履歴ファイルと結果ファイルが残っている場合 -> 未配信メールが残っている
        // 履歴ファイルを結果ファイルにマージしてマージしたファイルを履歴ファイルとしてメール配信する。
        if (file_exists($fileHistory) && file_exists($fileResult)) {
            $this->mergeHistoryFile($fileHistory, $fileResult);
            rename($fileResult, $fileHistory);
        }
        // 履歴ファイルは削除されていて、結果ファイルだけある場合 -> 送信失敗したメールを再送する
        // 結果ファイルを履歴ファイルとしてメール配信する。
        elseif (!file_exists($fileHistory) && file_exists($fileResult)) {
            rename($fileResult, $fileHistory);
        }
    }

    /**
     * Send mailmagazine.
     * メールマガジンを送信する.
     *
     * @param $sendId
     * @param int $offset
     * @param int $max
     *
     * @return MailMagazineSendHistory
     */
    public function sendrMailMagazine(int $sendId, int $offset = 0, int $max = 100): MailMagazineSendHistory
    {
        // send_historyを取得する
        $sendHistory = $this->mailMagazineSendHistoryRepository->find($sendId);

        if (is_null($sendHistory)) {
            // 削除されている場合は終了する
            throw new \RuntimeException(sprintf('Mail magazine history %d was not found.', $sendId));
        }

        if ($offset == 0) {
            $this->markRetry($sendId);
        }

        // エラー数
        $errorCount = $offset > 0 ? $sendHistory->getErrorCount() : 0;

        // 履歴ファイルと結果ファイル
        $fileHistory = $this->getHistoryFileName($sendId);
        $fileResult = $this->getHistoryFileName($sendId, false);
        $handleHistory = fopen($fileHistory, 'r');
        if (false === $handleHistory) {
            throw new \RuntimeException('Could not open the mail magazine history file.');
        }

        // スキップ数
        $skipCount = $offset;
        // 処理数
        $processCount = 0;

        while (false !== ($rawLine = fgets($handleHistory))) {
            $line = rtrim($rawLine, "\r\n");
            if ($skipCount-- > 0) {
                continue;
            }

            if ($processCount >= $max) {
                break;
            }

            [$status, $customerId, $email, $name] = explode(',', $line, 4);

            if ($status == self::SEND_FLAG_SUCCESS) {
                $handleResult = fopen($fileResult, 'a');
                if (false === $handleResult || false === fwrite($handleResult, $line.PHP_EOL)) {
                    if (is_resource($handleResult)) {
                        fclose($handleResult);
                    }
                    fclose($handleHistory);
                    throw new \RuntimeException('Could not write the mail magazine result file.');
                }
                fclose($handleResult);
                ++$processCount;
                continue;
            }

            $mailData = [
                'email' => $email,
                'subject' => $sendHistory->getSubject(),
                'body' => $sendHistory->getBody(),
                'htmlBody' => $sendHistory->getHtmlBody(),
            ];
            $this->replaceMailVars($mailData, $name);

            // 送信した本文を保持する
            $this->lastSendMailBody = $mailData['body'];
            $this->lastSendMailHtmlBody = $mailData['htmlBody'];

            $sendResult = true;
            try {
                $this->sendMail($mailData);
            } catch (\Exception $e) {
                log_error($e->getMessage());
                $sendResult = false;
            }

            // メール送信成功時
            $handleResult = fopen($fileResult, 'a');
            if (false === $handleResult) {
                fclose($handleHistory);
                throw new \RuntimeException('Could not open the mail magazine result file.');
            }
            if ($sendResult) {
                $written = fwrite($handleResult, self::SEND_FLAG_SUCCESS.','.$customerId.','.$email.','.$name.PHP_EOL);
            }
            // メール送信失敗時
            else {
                $written = fwrite($handleResult, self::SEND_FLAG_FAILURE.','.$customerId.','.$email.','.$name.PHP_EOL);
                ++$errorCount;
            }
            if (false === $written) {
                fclose($handleResult);
                fclose($handleHistory);
                throw new \RuntimeException('Could not write the mail magazine result file.');
            }
            fclose($handleResult);

            ++$processCount;
        }
        fclose($handleHistory);

        // 全部終わったら履歴ファイルを削除
        if ($offset + $processCount >= $sendHistory->getSendCount()) {
            $errorCount = 0;
            $handleResult = fopen($fileResult, 'r');
            if (false === $handleResult) {
                throw new \RuntimeException('Could not open the mail magazine result file.');
            }
            while (false !== ($line = fgets($handleResult))) {
                if (substr($line, 0, 1) == self::SEND_FLAG_FAILURE) {
                    ++$errorCount;
                }
            }
            fclose($handleResult);
            unlink($fileHistory);
        }

        // 送信結果情報を更新する
        $sendHistory->setEndDate(new \DateTime());
        $sendHistory->setCompleteCount($offset > 0 ? $sendHistory->getCompleteCount() + $processCount : $processCount);
        $sendHistory->setErrorCount($errorCount);
        $this->mailMagazineSendHistoryRepository->save($sendHistory);
        $this->entityManager->flush();

        return $sendHistory;
    }

    /**
     * 送信完了報告メールを送信する.
     *
     * @return bool
     */
    public function sendMailMagazineCompleateReportMail(): bool
    {
        $subject = date('Y年m月d日H時i分').'　下記メールの配信が完了しました。';

        $mailData = [
            'email' => $this->getAdminEmail(),
            'subject' => $subject,
            'body' => $this->lastSendMailBody,
            'htmlBody' => $this->lastSendMailHtmlBody,
        ];

        try {
            $this->sendMail($mailData);

            return true;
        } catch (\Exception $e) {
            log_error($e->getMessage());

            return false;
        }
    }

    public function getAdminEmail(): string
    {
        return $this->BaseInfo->getEmail03();
    }

    /**
     * Get history file name
     *
     * @param $historyId
     * @param bool $input
     *
     * @return string
     */
    public function getHistoryFileName(int $historyId, bool $input = true): string
    {
        return $this->mailMagazineDir.'/mail_magazine_'.($input ? 'in' : 'out').'_'.$historyId.'.txt';
    }

    /**
     * Delete history files
     *
     * @param $historyId
     */
    public function unlinkHistoryFiles(int $historyId): void
    {
        foreach ([$this->getHistoryFileName($historyId), $this->getHistoryFileName($historyId, false)] as $f) {
            if (file_exists($f)) {
                unlink($f);
            }
        }
    }

    /**
     * テストメール送信
     *
     * @param array{email: string, subject: string, body: string, htmlBody?: string|null, name: string} $mailData メールデータ
     */
    public function sendTestMail(array $mailData): void
    {
        $this->replaceMailVars($mailData, $mailData['name']);
        $this->sendMail($mailData);
    }

    /**
     * @param array{email?: string, subject?: string, body?: string, htmlBody?: string|null, name?: string} $mailData メールデータ
     * @param string $name 名前
     */
    public function replaceMailVars(array &$mailData, string $name): void
    {
        foreach (['subject', 'body', 'htmlBody'] as $key) {
            $value = (string) ($mailData[$key] ?? '');
            $mailData[$key] = preg_replace('/{name}/', $name, $value) ?? $value;
        }
    }
}
