<?php

class ViewHelper {
    public static function formatDiff($value, $digit) {
        return ($value > 0 ? "+" : "") . number_format($value, $digit);
    }

    public static function formatDecimalPart($value, $digit) {
        if ($digit == 0) return "";

        $decimalPart = $value - floor($value);
        $multi = pow(10, $digit);

        return '.' . str_pad(
            floor($decimalPart * $multi),
            $digit,
            0,
            STR_PAD_LEFT
        );
    }

    public static function diffClass($d) {
        if ($d > 0) return 'diff-plus';
        if ($d < 0) return 'diff-minus';
        return 'diff-zero';
    }
}
