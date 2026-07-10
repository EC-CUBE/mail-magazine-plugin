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

use Eccube\Form\Type\Front\EntryType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class EntryMailMagazineTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mailmaga_flg', ChoiceType::class, [
                'label' => 'admin.mailmagazine.customer.label_mailmagazine',
                'label_attr' => [
                    'class' => 'ec-label',
                ],
                'choices' => [
                    'admin.mailmagazine.customer.label_mailmagazine_yes' => '1',
                    'admin.mailmagazine.customer.label_mailmagazine_no' => '0',
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
                    'form_theme' => '@MailMagazine44/entry_add_mailmaga.twig',
                ],
            ])
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public static function getExtendedTypes(): iterable
    {
        yield EntryType::class;
    }
}
