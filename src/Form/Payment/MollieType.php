<?php


namespace CoreShop\Payum\MollieBundle\Form\Payment;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class MollieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('test', CheckboxType::class)
            ->add('apiKey', TextType::class)
            ->add('method', ChoiceType::class, [
                'multiple' => true,
                'choices' => [
                    'applepay',
                    'bancontact',
                    'banktransfer',
                    'belfius',
                    'creditcard',
                    'directdebit',
                    'eps',
                    'giftcard',
                    'giropay',
                    'ideal',
                    'inghomepay',
                    'kbc',
                    'mybank',
                    'paypal',
                    'paysafecard',
                    'przelewy24',
                    'sofort',
                    'klarnapaylater',
                    'klarnasliceit'
                ]
            ]);
    }
}
