<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Form\Type;

use Bouteg\PayfipPlugin\Payum\PayfipApi;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class SyliusGatewayConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client_number', TextType::class, [
                'label' => 'bouteg.payfip_plugin.form.payment_method.client_number',
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'bouteg.payfip_plugin.payment_method.client_number.not_blank',
                            'groups' => ['sylius'],
                        ]
                    ),
                ],
            ])
            ->add('environment', ChoiceType::class, [
                'choices' => [
                    'bouteg.payfip_plugin.form.payment_method.env_test' => PayfipApi::ENV_TEST,
                    'bouteg.payfip_plugin.form.payment_method.env_activation' => PayfipApi::ENV_ACTIVATION,
                    'bouteg.payfip_plugin.form.payment_method.env_production' => PayfipApi::ENV_PRODUCTION,
                ],
                'label' => 'bouteg.payfip_plugin.form.payment_method.environment',
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'bouteg.payfip_plugin.payment_method.environment.not_blank',
                            'groups' => ['sylius'],
                        ]
                    ),
                ],
            ]);
    }
}
