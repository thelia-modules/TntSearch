<?php

namespace TntSearch\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;
use Symfony\Component\Validator\Constraints\NotBlank;
use TntSearch\TntSearch;

class SynonymForm extends BaseForm
{
    protected function buildForm(): void
    {
        $this->formBuilder
            ->add(
                'terms',
                TextType::class,
                [
                    'constraints' => [
                        new NotBlank(['message' => 'Synonyms cannot be empty'])
                    ],
                    'label' => Translator::getInstance()->trans('Synonyms', [], TntSearch::DOMAIN_NAME),
                    'label_attr' => ['for' => 'terms'],
                    'attr' => [
                        'placeholder' => Translator::getInstance()->trans('e.g., red, rouge, crimson, scarlet', [], TntSearch::DOMAIN_NAME),
                        'class' => 'form-control'
                    ]
                ])
            ->add(
                'enabled',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => Translator::getInstance()->trans('Enable this synonym group', [], TntSearch::DOMAIN_NAME),
                    'label_attr' => ['for' => 'enabled']
                ]
            )
            ->add(
                'group_id',
                IntegerType::class,
                [
                    'required' => false
                ]
            );
    }

    public static function getName(): string
    {
        return 'synonym_form';
    }
}