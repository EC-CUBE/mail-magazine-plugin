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
use Eccube\Attribute\EntityExtension;
use Eccube\Entity\Customer;

#[EntityExtension(Customer::class)]
trait CustomerTrait
{
    #[ORM\Column(name: 'plg_mailmagazine_flg', type: Types::SMALLINT, nullable: true, options: ['default' => 0, 'unsigned' => true])]
    protected ?int $mailmaga_flg = null;

    /**
     * Set mailmaga_flg
     *
     * @param $mailmagaFlg
     *
     * @return $this
     */
    public function setMailmagaFlg(?int $mailmagaFlg): self
    {
        $this->mailmaga_flg = $mailmagaFlg;

        return $this;
    }

    /**
     * Get mailmaga_flg
     *
     * @return int
     */
    public function getMailmagaFlg(): ?int
    {
        return $this->mailmaga_flg;
    }
}
