<?php
namespace App\Validations;

use App\Data\TradeData;
// require_once __DIR__ . '/../Data/TradeData.php';

class TradeValidator
{
    public static function validate(TradeData $data): array
    {
        $errors = [];

        $date = $data->date ?? '';
        if ($date !== '') {
            $pattern = '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/';  // '2024-02-29'
            if (preg_match($pattern, $date, $matches, PREG_UNMATCHED_AS_NULL)) {  // $matches(検索結果の戻り値)：[1]=年, [2]=月, [3]=日
                if (!checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1])) {
                    $errors['date'] = '日付が正しくありません';
                } 
            } else {
                 $errors['date'] = '日付が正しくありません';
            }
        }

        $content = $data->content;
        if (mb_strlen($content) > 1000) {
            $errors['content'] = 'コメントは1000文字以内で入力してください';
        }

        return $errors;
    }
}