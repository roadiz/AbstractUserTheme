<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Form;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\CMS\Forms\Constraints\Recaptcha;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueEmail;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueUsername;
use RZ\Roadiz\CMS\Forms\CreatePasswordType;
use RZ\Roadiz\CMS\Forms\RecaptchaType;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class SignUpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', EmailType::class, [
                'label' => 'user.email',
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                    new UniqueUsername([
                        'entityManager' => $options['em'],
                        'currentValue' => $options['email'],
                    ]),
                    new UniqueEmail([
                        'entityManager' => $options['em'],
                        'currentValue' => $options['email'],
                    ]),
                ],
            ])
            ->add('plainPassword', CreatePasswordType::class, [
                'invalid_message' => 'password.must.match',
                'required' => true,
                'constraints' => [
                    new NotNull(),
                    new NotBlank(),
                ],
            ])
        ;
        if (!empty($options['privateKey']) && !empty($options['publicKey'])) {
            $builder->add('verify', RecaptchaType::class, [
                'label' => false,
                'mapped' => false,
                'configs' => [
                    'publicKey' => $options['publicKey'],
                ],
                'constraints' => [
                    new Recaptcha($options['request'], [
                        'privateKey' => $options['privateKey'],
                        'verifyUrl' => $options['verifyUrl'],
                    ]),
                ],
            ]);
        }
    }

    public function getBlockPrefix()
    {
        return 'sing_up';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'email' => '',
            'data_class' => User::class,
            'attr' => [
                'class' => 'uk-form user-form',
            ],
            'verifyUrl' => 'https://www.google.com/recaptcha/api/siteverify',
            'publicKey' => null,
            'privateKey' => null,
        ]);

        $resolver->setRequired([
            'em',
            'request'
        ]);

        $resolver->setAllowedTypes('em', EntityManagerInterface::class);
        $resolver->setAllowedTypes('email', 'string');
        $resolver->setAllowedTypes('request', Request::class);
        $resolver->setAllowedTypes('publicKey', 'string');
        $resolver->setAllowedTypes('privateKey', 'string');
    }
}
