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
use Plugin\MailMagazine44\Repository\MailMagazineTemplateRepository;

#[ORM\Table(name: 'plg_mailmaga_template')]
#[ORM\Entity(repositoryClass: MailMagazineTemplateRepository::class)]
class MailMagazineTemplate extends AbstractEntity implements \Stringable
{
    public function __toString(): string
    {
        return $this->getSubject();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'template_id', type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'subject', type: Types::STRING, length: 255)]
    private ?string $subject = null;

    #[ORM\Column(name: 'body', type: Types::TEXT)]
    private ?string $body = null;

    #[ORM\Column(name: 'html_body', type: Types::TEXT, nullable: true)]
    private ?string $html_body = null;

    #[ORM\Column(name: 'create_date', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $create_date = null;

    #[ORM\Column(name: 'update_date', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $update_date = null;

    /**
     * Set template id.
     *
     * @param int $id
     *
     * @return MailMagazineTemplate
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get template_id.
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get subject.
     *
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject ?? '';
    }

    /**
     * Set subject.
     *
     * @param string $subject
     *
     * @return MailMagazineTemplate
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set create_date.
     *
     * @param \DateTime $createDate
     *
     * @return MailMagazineTemplate
     */
    public function setCreateDate(?\DateTimeInterface $createDate): self
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get create_date.
     *
     * @return \DateTimeInterface|null
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
     * @return MailMagazineTemplate
     */
    public function setUpdateDate(?\DateTimeInterface $updateDate): self
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get update_date.
     *
     * @return \DateTimeInterface|null
     */
    public function getUpdateDate(): ?\DateTimeInterface
    {
        return $this->update_date;
    }

    /**
     * Set body.
     *
     * @param string $body
     *
     * @return MailMagazineTemplate
     */
    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body ?? '';
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
     * @return MailMagazineTemplate
     */
    public function setHtmlBody(?string $html_body): self
    {
        $this->html_body = $html_body;

        return $this;
    }
}
