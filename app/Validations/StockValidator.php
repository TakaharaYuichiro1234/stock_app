<?php
namespace App\Validations;
class StockValidator
{
    public static function validate(array $data): array
    {
        $errors = [];

        $name = $data['name'];
        if ($name === '') {
            $errors['name'] = '銘柄名は必須です';
        } elseif (mb_strlen($name) > 255) {
            $errors['name'] = '銘柄名は255文字以内で入力してください';
        }

        $digit = $data['digit'];
        if (filter_var($digit, FILTER_VALIDATE_INT) === false) {
            $errors['digit'] = '桁数は整数で入力してください';
        }

        return $errors;
    }
}