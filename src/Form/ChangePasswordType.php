<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Form;

use RZ\Roadiz\CMS\Forms\CreatePasswordType;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('plainPassword', CreatePasswordType::class, [
            'invalid_message' => 'password.must.match',
            'required' => true,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'change_password';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'data_class' => User::class,
        ]);
    }
}
