<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Form;

use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserVerifyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('phone', PhoneNumberType::class, [
            'label' => 'user_verify.phone',
            'widget' => PhoneNumberType::WIDGET_COUNTRY_CHOICE,
            'preferred_country_choices' => [
                'FR',
                'GB',
                'US',
                'DE',
                'CH',
                'ES',
            ],
            'constraints' => [
                new PhoneNumber([
                    'type' => PhoneNumber::MOBILE
                ])
            ]
        ]);
    }

    public function getName()
    {
        return 'user_verify';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'currentValue' => '',
            'attr' => [
                'class' => 'form verify-form',
            ],
        ]);
    }
}
