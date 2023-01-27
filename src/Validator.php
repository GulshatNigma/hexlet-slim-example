<?php

namespace Slim\Example;

class Validator
{
    public function validate(array $user)
    {
        $errors = [];

        if (empty($user['nickname'])) {
            $errors['nickname'] = "Can't be blank";
        }
    
        if (empty($user['email'])) {
            $errors['email'] = "Can't be blank";
        }


        if (!empty($user['nickname']) && strlen($user['nickname']) <= 4) {
            $errors['nickname'] = "Nickname must be greater than 4 characters";
        }

        if (!empty($user['nickname']) && strlen($user['nickname']) >= 30) {
            $errors['nickname'] = "Nickname must be less than 30 characters";
        }

        return $errors;
    }
}