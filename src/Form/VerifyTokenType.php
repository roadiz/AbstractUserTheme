<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VerifyTokenType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('token', TextType::class, [
            'label' => 'user_verify.enter_token_you_received',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'token_verify';
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'currentValue' => '',
            'attr' => [
                'class' => 'form token-verify-form',
            ],
        ]);
    }
}
