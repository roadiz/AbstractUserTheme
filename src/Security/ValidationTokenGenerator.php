<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Security;

use RZ\Roadiz\Random\PasswordGeneratorInterface;
use RZ\Roadiz\Random\RandomGenerator;

class ValidationTokenGenerator extends RandomGenerator implements PasswordGeneratorInterface
{
    /**
     * Generates a strong password of N length containing at least one lower case letter,
     * one uppercase letter, one digit, and one special character. The remaining characters
     * in the password are chosen at random from those four sets.
     *
     * The available characters in each set are user friendly - there are no ambiguous
     * characters such as i, l, 1, o, 0, etc.
     *
     * @param  integer $length
     *
     * @return string
     *
     * @see https://gist.github.com/tylerhall/521810
     */
    public function generatePassword(int $length = 6)
    {
        $sets = [];
        $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        $sets[] = '23456789';

        $all = '';
        $password = '';
        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }

        $all = str_split($all);
        for ($i = 0; $i < $length - count($sets); $i++) {
            $password .= $all[array_rand($all)];
        }

        $password = str_shuffle($password);

        return $password;
    }
}
