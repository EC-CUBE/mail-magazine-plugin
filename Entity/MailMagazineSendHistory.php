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

namespace Plugin\MailMagazine44\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Member;
use Plugin\MailMagazine44\Repository\MailMagazineSendHistoryRepository;

#[ORM\Table(name: 'plg_mailmaga_send_history')]
#[ORM\Entity(repositoryClass: MailMagazineSendHistoryRepository::class)]
class MailMagazineSendHistory extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'send_id', type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'mail_method', type: Types::SMALLINT, nullable: true, options: ['unsigned' => false])]
    private ?int $mail_method = null;

    #[ORM\Column(name: 'subject', type: Types::STRING, length: 255, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(name: 'body', type: Types::TEXT, nullable: true)]
    private ?string $body = null;

    #[ORM\Column(name: 'html_body', type: Types::TEXT, nullable: true)]
    private ?string $html_body = null;

    #[ORM\Column(name: 'send_count', type: Types::INTEGER, nullable: true, options: ['unsigned' => true])]
    private ?int $send_count = null;

    #[ORM\Column(name: 'complete_count', type: Types::INTEGER, nullable: true, options: ['unsigned' => true, 'default' => 0])]
    private ?int $complete_count = null;

    #[ORM\Column(name: 'error_count', type: Types::INTEGER, nullable: true, options: ['unsigned' => true, 'default' => 0])]
    private ?int $error_count = null;

    #[ORM\Column(name: 'start_date', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $start_date = null;

    #[ORM\Column(name: 'end_date', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $end_date = null;

    #[ORM\Column(name: 'search_data', type: Types::TEXT, nullable: true)]
    private ?string $search_data = null;

    #[ORM\Column(name: 'create_date', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $create_date = null;

    #[ORM\Column(name: 'update_date', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $update_date = null;

    #[ORM\ManyToOne(targetEntity: Member::class)]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'id')]
    private ?Member $Creator = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set mail_method.
     *
     * @param int $mailMethod
     *
     * @return MailMagazineSendHistory
     */
    public function setMailMethod(?int $mailMethod): self
    {
        $this->mail_method = $mailMethod;

        return $this;
    }

    /**
     * Get mail_method.
     *
     * @return int
     */
    public function getMailMethod(): ?int
    {
        return $this->mail_method;
    }

    /**
     * Set subject.
     *
     * @param string $subject
     *
     * @return MailMagazineSendHistory
     */
    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject.
     *
     * @return string
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * Set body.
     *
     * @param string $body
     *
     * @return MailMagazineSendHistory
     */
    public function setBody(?string $body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body.
     *
     * @return string
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * @return string
     */
    public function getHtmlBody(): ?string
    {
        return $this->html_body;
    }

    /**
     * @param string $html_body
     *
     * @return MailMagazineSendHistory
     */
    public function setHtmlBody(?string $html_body): self
    {
        $this->html_body = $html_body;

        return $this;
    }

    /**
     * Set send_count.
     *
     * @param int $sendCount
     *
     * @return MailMagazineSendHistory
     */
    public function setSendCount(?int $sendCount): self
    {
        $this->send_count = $sendCount;

        return $this;
    }

    /**
     * Get send_count.
     *
     * @return int
     */
    public function getSendCount(): ?int
    {
        return $this->send_count;
    }

    /**
     * Set complete_count.
     *
     * @param int $completeCount
     *
     * @return MailMagazineSendHistory
     */
    public function setCompleteCount(?int $completeCount): self
    {
        $this->complete_count = $completeCount;

        return $this;
    }

    /**
     * Get complete_count.
     *
     * @return int
     */
    public function getCompleteCount(): ?int
    {
        return $this->complete_count;
    }

    /**
     * @return int
     */
    public function getErrorCount(): ?int
    {
        return $this->error_count;
    }

    /**
     * @param int $errorCount
     *
     * @return MailMagazineSendHistory
     */
    public function setErrorCount(?int $errorCount): self
    {
        $this->error_count = $errorCount;

        return $this;
    }

    /**
     * Set start_date.
     *
     * @param \DateTime $startDate
     *
     * @return MailMagazineSendHistory
     */
    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->start_date = $startDate;

        return $this;
    }

    /**
     * Get start_date.
     *
     * @return \DateTime
     */
    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    /**
     * Set end_date.
     *
     * @param \DateTime $endDate
     *
     * @return MailMagazineSendHistory
     */
    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->end_date = $endDate;

        return $this;
    }

    /**
     * Get end_date.
     *
     * @return \DateTime
     */
    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    /**
     * Set search_data.
     *
     * @param string $searchData
     *
     * @return MailMagazineSendHistory
     */
    public function setSearchData(?string $searchData): self
    {
        $this->search_data = $searchData;

        return $this;
    }

    /**
     * Get search_data.
     *
     * @return string
     */
    public function getSearchData(): ?string
    {
        return $this->search_data;
    }

    /**
     * Set create_date.
     *
     * @param \DateTime $createDate
     *
     * @return MailMagazineSendHistory
     */
    public function setCreateDate(?\DateTimeInterface $createDate): self
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get create_date.
     *
     * @return \DateTime
     */
    public function getCreateDate(): ?\DateTimeInterface
    {
        return $this->create_date;
    }

    /**
     * Set update_date.
     *
     * @param \DateTime $updateDate
     *
     * @return MailMagazineSendHistory
     */
    public function setUpdateDate(?\DateTimeInterface $updateDate): self
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get update_date.
     *
     * @return \DateTime
     */
    public function getUpdateDate(): ?\DateTimeInterface
    {
        return $this->update_date;
    }

    /**
     * Set Creator.
     *
     * @param Member $creator
     *
     * @return MailMagazineSendHistory
     */
    public function setCreator(?Member $creator = null): self
    {
        $this->Creator = $creator;

        return $this;
    }

    /**
     * Get Creator.
     *
     * @return Member
     */
    public function getCreator(): ?Member
    {
        return $this->Creator;
    }

    /**
     * 配信エラーの有無にかかわらず、すべて送信したかどうかの判定.
     *
     * @return bool 配信完了した場合はtrue
     */
    public function isComplete(): bool
    {
        return $this->getCompleteCount() == $this->getSendCount();
    }
}
