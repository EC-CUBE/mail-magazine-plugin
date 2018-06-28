<?php
namespace Plugin\MailMagazine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation as Eccube;
/**
 * @Eccube\EntityExtension("Eccube\Entity\Customer")
 */
trait CustomerTrait
{
    /**
     * @ORM\Column(name="plg_mailmagazine_flg", type="smallint", length=1, options={"default":0, "unsigned": true})
     *
     * @var int
     */
    protected $mailmaga_flg;

    /**
     * Set mailmaga_flg
     *
     * @param $mailmagaFlg
     *
     * @return $this
     *
     */
    public function setMailmagaFlg($mailmagaFlg)
    {
        $this->mailmaga_flg = $mailmagaFlg;

        return $this;
    }

    /**
     * Get mailmaga_flg
     *
     * @return int
     */
    public function getMailmagaFlg()
    {
        return $this->mailmaga_flg;
    }
}
