<?php

namespace PayGreenClimateKit\Form;

use PayGreenClimateKit\PayGreenClimateKit;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Form\BaseForm;
use Thelia\Core\Translation\Translator;

class ConfigForm extends BaseForm
{
    protected function buildForm()
    {
        $translator = Translator::getInstance();

        $this->formBuilder
            ->add(
                'accountName',
                'text',
                [
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'label' => $translator->trans('PayGreen account name', [], PayGreenClimateKit::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'accountName',
                        'help' => $translator->trans('You can find your account name in the profile part of your ClimateKit account', [], PayGreenClimateKit::DOMAIN_NAME)
                    ]
                ]
            )
            ->add(
                'userName',
                'text',
                [
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'label' => $translator->trans('PayGreen user name', [], PayGreenClimateKit::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'userName',
                        'help' => $translator->trans('You can find your user name in the profile part of your ClimateKit account', [], PayGreenClimateKit::DOMAIN_NAME)
                    ]
                ]
            )
            ->add(
                'password',
                'password',
                [
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'label' => $translator->trans('PayGreen password', [], PayGreenClimateKit::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'password',
                    ]
                ]
            )
            ->add(
                'download',
                'button',
                [
                    'label' => $translator->trans('download the catalog in csv format', [], PayGreenClimateKit::DOMAIN_NAME),

                ]
            )
            ->add(
                'mode',
                'choice',
                [
                    'constraints' =>  [
                        new NotBlank()
                    ],
                    'choices' => [
                        'TEST' => $translator->trans('Test', [], PayGreenClimateKit::DOMAIN_NAME),
                        'PRODUCTION' => $translator->trans('Production', [], PayGreenClimateKit::DOMAIN_NAME),
                    ],
                    'label' => $translator->trans('Operation Mode', [], PayGreenClimateKit::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'mode',
                        'help' => $translator->trans('Test or production mode', [], PayGreenClimateKit::DOMAIN_NAME)
                    ]
                ]
            );
    }

    /**
     * @return string the name of you form. This name must be unique
     */
    public function getName()
    {
        return 'ClimateKitConfig';
    }
}
