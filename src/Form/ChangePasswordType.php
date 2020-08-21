<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Form;

use RZ\Roadiz\CMS\Forms\CreatePasswordType;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class ChangePasswordType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('plainPassword', CreatePasswordType::class, [
            'invalid_message' => 'password.must.match',
            'required' => true,
            'constraints' => [
                new NotNull(),
                new NotBlank(),
            ],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'change_password';
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
        ]);
    }
}
