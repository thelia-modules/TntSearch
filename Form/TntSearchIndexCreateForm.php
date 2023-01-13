<?php

namespace TntSearch\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;
use TntSearch\TntSearch;

class TntSearchIndexCreateForm extends BaseForm
{

    protected function buildForm()
    {
        $this->formBuilder
            ->add(
                'Index',
                TextType::class,
                array(
                    'required' => true,
                    'label' => Translator::getInstance()->trans('Index', array(), TntSearch::DOMAIN_NAME),
                    'label_attr' => array(
                        'for' => 'index'
                    ),
                    'constraints' => array(
                        new NotBlank(),
                    )
                )
            )
            ->add(
                'Translatable',
                CheckboxType::class,
                [
                    'label' => Translator::getInstance()->trans('Translatable', array(), TntSearch::DOMAIN_NAME),
                    'label_attr' => array(
                        'for' => 'is_translatable'
                    ),
                ]
            )
            ->add(
                'Active',
                CheckboxType::class,
                [
                    'label' => Translator::getInstance()->trans('Active', array(), TntSearch::DOMAIN_NAME),
                    'label_attr' => array(
                        'for' => 'is_active'
                    ),
                ]
            )
        ;
    }

    /**
     * @return string the name of you form. This name must be unique
     */
    public function getName()
    {
        return 'tnt-search-index-create';
    }
}