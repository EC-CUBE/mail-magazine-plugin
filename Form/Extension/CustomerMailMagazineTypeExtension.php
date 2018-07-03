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

namespace Plugin\MailMagazine\Form\Extension;

use Eccube\Entity\Customer;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Eccube\Form\Type\Admin\CustomerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CustomerMailMagazineTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $mailmagaFlg = null;

        /** @var Customer $Customer */
        $Customer = isset($options['data']) ? $options['data'] : null;
        if ($Customer instanceof Customer) {
            $mailmagaFlg = $Customer->getMailmagaFlg();
        }

        $options = array(
            'label' => 'admin.plugin.mailmagazine.customer.label_mailmagazine',
            'choices' => array(
                'admin.plugin.mailmagazine.customer.label_mailmagazine_yes' => '1',
                'admin.plugin.mailmagazine.customer.label_mailmagazine_no' => '0',
            ),
            'expanded' => true,
            'multiple' => false,
            'required' => true,
            'constraints' => array(
                new Assert\NotBlank(),
            ),
            'mapped' => true,
            'eccube_form_options' => [
                'auto_render' => true,
                'form_theme' => '@MailMagazine/admin/mailmagazine.twig'
            ]
        );

        if (!is_null($mailmagaFlg)) {
            $optiopns['data'] = $mailmagaFlg;
        }

        $builder->add('mailmaga_flg', ChoiceType::class, $options);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getExtendedType()
    {
        return CustomerType::class;
    }
}
