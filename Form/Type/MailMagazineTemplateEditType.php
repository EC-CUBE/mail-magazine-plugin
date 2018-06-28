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
 * メルマガテンプレート設定用
 */

namespace Plugin\MailMagazine\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class MailMagazineTemplateEditType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('subject', 'text', array(
                'label' => '件名',
                'required' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('body', 'textarea', array(
                'label' => '本文 (テキスト形式)',
                'required' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('htmlBody', 'textarea', array(
                'label' => '本文 (HTML形式)',
                'required' => false,
            ));
    }

    /**
     * {@inheritdoc}
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Plugin\MailMagazine\Entity\MailMagazineTemplate',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mail_magazine_template_edit';
    }
}
