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
/*
 * [メルマガ配信]-[配信内容設定]用Form
 */

namespace Plugin\MailMagazine\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Eccube\Form\Type\Admin\SearchCustomerType;

class MailMagazineType extends SearchCustomerType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        // 以降テンプレート選択で使用する項目
        $builder->add('id', HiddenType::class)
            ->add('template', MailMagazineTemplateType::class, array(
                'label' => 'テンプレート',
                'required' => false,
                'mapped' => false,
            ))
            ->add('subject', TextType::class, array(
                'label' => '件名',
                'required' => true,
            ))
            ->add('body', TextareaType::class, array(
                'label' => '本文 (テキスト形式)',
                'required' => true,
            ))
            ->add('htmlBody', TextareaType::class, array(
                'label' => '本文 (HTML形式)',
                'required' => false,
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mail_magazine';
    }
}
