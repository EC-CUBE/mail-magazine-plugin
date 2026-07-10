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

namespace Plugin\MailMagazine44\Form\Extension;

use Eccube\Common\Constant;
use Eccube\Form\Type\Admin\CustomerType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CustomerMailMagazineTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('mailmaga_flg', ChoiceType::class, [
            'label' => 'admin.mailmagazine.customer.label_mailmagazine',
            'choices' => [
                'admin.mailmagazine.customer.label_mailmagazine_yes' => Constant::ENABLED,
                'admin.mailmagazine.customer.label_mailmagazine_no' => Constant::DISABLED,
            ],
            'expanded' => true,
            'multiple' => false,
            'required' => true,
            'constraints' => [
                new Assert\NotBlank(),
            ],
            'mapped' => true,
            'eccube_form_options' => [
                'auto_render' => true,
                'form_theme' => '@MailMagazine44/admin/mailmagazine.twig',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public static function getExtendedTypes(): iterable
    {
        yield CustomerType::class;
    }
}
