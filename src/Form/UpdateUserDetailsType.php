<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Form;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueEntity;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class UpdateUserDetailsType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['allowEmailChange'] === true) {
            $builder->add('email', EmailType::class, [
                'label' => 'user.email',
                'constraints' => [
                    new NotNull(),
                    new Email(),
                    new NotBlank(),
                ]
            ]);
        }

        $builder->add('firstName', TextType::class, [
            'label' => 'user.firstName',
            'required' => false,
        ])->add('lastName', TextType::class, [
            'label' => 'user.lastName',
            'required' => false,
        ])->add('phone', PhoneNumberType::class, [
            'label' => 'user_verify.phone',
            'required' => false,
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

        $builder->get('phone')->addModelTransformer(new CallbackTransformer(function (?string $phone) {
            try {
                $phoneUtils = PhoneNumberUtil::getInstance();
                return $phoneUtils->parse($phone ?? '');
            } catch (NumberParseException $exception) {
                return null;
            }
        }, function (?\libphonenumber\PhoneNumber $phoneNumber) {
            if (null !== $phoneNumber) {
                $phoneUtils = PhoneNumberUtil::getInstance();
                return $phoneUtils->format($phoneNumber, PhoneNumberFormat::E164);
            }
            return null;
        }));
    }

    public function getBlockPrefix()
    {
        return 'update_user';
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'data_class' => User::class,
            'allowEmailChange' => false,
        ]);

        $resolver->setNormalizer('constraints', function (Options $options) {
            if ($options['allowEmailChange'] === true) {
                return [
                    new UniqueEntity([
                        'fields' => [
                            'email'
                        ]
                    ]),
                    new UniqueEntity([
                        'fields' => [
                            'username',
                        ]
                    ])
                ];
            }
            return [];
        });

        $resolver->setAllowedTypes('allowEmailChange', 'boolean');
    }
}
