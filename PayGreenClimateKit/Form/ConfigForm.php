<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PayGreenClimateKit\Form;

use PayGreenClimateKit\PayGreenClimateKit;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class ConfigForm extends BaseForm
{
    protected function buildForm(): void
    {
        $translator = Translator::getInstance();

        $this->formBuilder
            ->add(
                'accountName',
                TextType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'label' => $translator->trans('PayGreen account name', [], PayGreenClimateKit::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'accountName',
                        'help' => $translator->trans('You can find your account name in the profile part of your ClimateKit account', [], PayGreenClimateKit::DOMAIN_NAME),
                    ],
                ]
            )
            ->add(
                'userName',
                TextType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'label' => $translator->trans('PayGreen user name', [], PayGreenClimateKit::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'userName',
                        'help' => $translator->trans('You can find your user name in the profile part of your ClimateKit account', [], PayGreenClimateKit::DOMAIN_NAME),
                    ],
                ]
            )
            ->add(
                'password',
                PasswordType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'label' => $translator->trans('PayGreen password', [], PayGreenClimateKit::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'password',
                    ],
                ]
            )->add(
                'clientId',
                TextType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'label' => $translator->trans('PayGreen client ID', [], PayGreenClimateKit::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'clientId',
                        'help' => $translator->trans('You can request your Client ID from Paygreen', [], PayGreenClimateKit::DOMAIN_NAME),
                    ],
                ]
            )
            ->add(
                'download',
                ButtonType::class,
                [
                    'label' => $translator->trans('download the catalog in csv format', [], PayGreenClimateKit::DOMAIN_NAME),
                ]
            )
            ->add(
                'mode',
                ChoiceType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'choices' => [
                        'TEST' => $translator->trans('Test', [], PayGreenClimateKit::DOMAIN_NAME),
                        'PRODUCTION' => $translator->trans('Production', [], PayGreenClimateKit::DOMAIN_NAME),
                    ],
                    'label' => $translator->trans('Operation Mode', [], PayGreenClimateKit::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'mode',
                        'help' => $translator->trans('Test or production mode', [], PayGreenClimateKit::DOMAIN_NAME),
                    ],
                ]
            );
    }

    /**
     * @return string the name of you form. This name must be unique
     */
    public static function getName(): string
    {
        return 'ClimateKitConfig';
    }
}
