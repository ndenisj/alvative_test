<?php

namespace App\Actions;

use InvalidArgumentException;

class ValidatePaymentInitAction
{

    public function execute(array $data): void
    {
        $validator = validator($data, [
            'email' => 'required|email',
            'amount' => 'required|integer'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException(json_encode($validator->errors()->all()));
        }
    }
}
