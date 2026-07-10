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

/*
 * メルマガテンプレート選択コンボボックス用に作成
 */

namespace Plugin\MailMagazine44\Form\Type;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Plugin\MailMagazine44\Entity\MailMagazineTemplate;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MailMagazineTemplateType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => MailMagazineTemplate::class,
            'choice_label' => 'subject',
            'label' => false,
            'multiple' => false,
            'expanded' => false,
            'required' => false,
            'placeholder' => '-',
            'query_builder' => fn (EntityRepository $er): QueryBuilder => $er->createQueryBuilder('mt')
                ->orderBy('mt.id', 'ASC'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'mail_magazine_template';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return EntityType::class;
    }
}
