<?php
/**
 * blackfin-tech.com - UpdateUserDetailsType.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-06-05
 */
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Form;

use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueEmail;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueUsername;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;

class UpdateUserDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['allowEmailChange'] === true) {
            $builder->add('email', EmailType::class, [
                'label' => 'user.email',
                'constraints' => [
                    new Email(),
                    new UniqueEmail([
                        'currentValue' => $builder->getData()->getEmail(),
                        'entityManager' => $options['em'],
                    ]),
                    new UniqueUsername([
                        'currentValue' => $builder->getData()->getEmail(),
                        'entityManager' => $options['em'],
                    ])
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
                return $phoneUtils->parse($phone);
            } catch (NumberParseException $exception) {
                return null;
            }
        }, function (\libphonenumber\PhoneNumber $phoneNumber) {
            $phoneUtils = PhoneNumberUtil::getInstance();
            return $phoneUtils->format($phoneNumber, PhoneNumberFormat::E164);
        }));
    }

    public function getBlockPrefix()
    {
        return 'update_user';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'data_class' => User::class,
            'allowEmailChange' => false,
        ]);

        $resolver->setRequired([
            'em'
        ]);

        $resolver->setAllowedTypes('em', EntityManagerInterface::class);
        $resolver->setAllowedTypes('allowEmailChange', 'boolean');
    }
}
