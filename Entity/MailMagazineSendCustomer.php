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

namespace Plugin\MailMagazine\Entity;

/**
 * SendCustomer
 * Plugin MailMagazine
 */
class MailMagazineSendCustomer extends \Eccube\Entity\AbstractEntity
{
	/**
	 * @var integer
	 */
	private $send_id;

	/**
	 * @var integer
	 */
	private $customer_id;

	/**
	 * @var string
	 */
	private $email;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var integer
	 */
	private $send_flag;

	/**
	 * Set send_id
	 *
	 * @param  integer      $sendId
	 * @return SendCustomer
	 */
	public function setSendId($sendId)
	{
		$this->send_id = $sendId;

		return $this;
	}

	/**
	 * Get send_id
	 *
	 * @return integer
	 */
	public function getSendId()
	{
		return $this->send_id;
	}

	/**
	 * Set customer_id
	 *
	 * @param  integer      $customerId
	 * @return SendCustomer
	 */
	public function setCustomerId($customerId)
	{
		$this->customer_id = $customerId;

		return $this;
	}

	/**
	 * Get customer_id
	 *
	 * @return integer
	 */
	public function getCustomerId()
	{
		return $this->customer_id;
	}

	/**
	 * Set email
	 *
	 * @param  string       $email
	 * @return SendCustomer
	 */
	public function setEmail($email)
	{
		$this->email = $email;

		return $this;
	}

	/**
	 * Get email
	 *
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * Set name
	 *
	 * @param  string       $name
	 * @return SendCustomer
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * Get name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set send_flag
	 *
	 * @param  integer      $sendFlag
	 * @return SendCustomer
	 */
	public function setSendFlag($sendFlag)
	{
		$this->send_flag = $sendFlag;

		return $this;
	}

	/**
	 * Get send_flag
	 *
	 * @return integer
	 */
	public function getSendFlag()
	{
		return $this->send_flag;
	}
}
