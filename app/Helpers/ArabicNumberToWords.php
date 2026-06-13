<?php

declare(strict_types=1);

namespace App\Helpers;

class ArabicNumberToWords
{
    private static array $ones = [
        0 => '', 1 => 'واحد', 2 => 'اثنان', 3 => 'ثلاثة', 4 => 'أربعة', 5 => 'خمسة', 
        6 => 'ستة', 7 => 'سبعة', 8 => 'ثمانية', 9 => 'تسعة', 10 => 'عشرة'
    ];

    private static array $tens = [
        10 => 'عشرة', 20 => 'عشرون', 30 => 'ثلاثون', 40 => 'أربعون', 50 => 'خمسون', 
        60 => 'ستون', 70 => 'سبعون', 80 => 'ثمانون', 90 => 'تسعون'
    ];

    private static array $hundreds = [
        0 => '', 100 => 'مئة', 200 => 'مئتان', 300 => 'ثلاثمئة', 400 => 'أربعمئة', 
        500 => 'خمسمئة', 600 => 'ستمئة', 700 => 'سبعمئة', 800 => 'ثمانمئة', 900 => 'تسعمئة'
    ];

    public static function convert(float $number, string $currency = 'جنيه'): string
    {
        $number = round($number, 2);
        $parts = explode('.', (string) $number);
        
        $integerPart = (int) $parts[0];
        $fractionPart = isset($parts[1]) ? (int) str_pad($parts[1], 2, '0', STR_PAD_RIGHT) : 0;

        $words = self::convertNumber($integerPart);
        
        if (empty($words)) {
            $words = 'صفر';
        }

        $result = $words . ' ' . $currency;

        if ($fractionPart > 0) {
            $fractionWords = self::convertNumber($fractionPart);
            $result .= ' و ' . $fractionWords . ' ' . ($currency === 'جنيه' ? 'قرش' : 'سنت');
        }

        return $result;
    }

    private static function convertNumber(int $number): string
    {
        if ($number === 0) {
            return '';
        }

        if ($number < 0) {
            return 'سالب ' . self::convertNumber(abs($number));
        }

        $result = '';

        // Millions
        if ($number >= 1000000) {
            $millions = (int) ($number / 1000000);
            $number %= 1000000;
            
            if ($millions === 1) {
                $result .= 'مليون';
            } elseif ($millions === 2) {
                $result .= 'مليونان';
            } elseif ($millions >= 3 && $millions <= 10) {
                $result .= self::convertNumber($millions) . ' ملايين';
            } else {
                $result .= self::convertNumber($millions) . ' مليون';
            }
        }

        // Thousands
        if ($number >= 1000) {
            if (!empty($result)) {
                $result .= ' و ';
            }
            $thousands = (int) ($number / 1000);
            $number %= 1000;

            if ($thousands === 1) {
                $result .= 'ألف';
            } elseif ($thousands === 2) {
                $result .= 'ألفان';
            } elseif ($thousands >= 3 && $thousands <= 10) {
                $result .= self::convertNumber($thousands) . ' آلاف';
            } else {
                $result .= self::convertNumber($thousands) . ' ألف';
            }
        }

        // Hundreds
        if ($number >= 100) {
            if (!empty($result)) {
                $result .= ' و ';
            }
            $hundredIndex = (int) ($number / 100) * 100;
            $result .= self::$hundreds[$hundredIndex];
            $number %= 100;
        }

        // Tens and Ones
        if ($number > 0) {
            if (!empty($result)) {
                $result .= ' و ';
            }

            if ($number <= 10) {
                $result .= self::$ones[$number];
            } elseif ($number < 20) {
                $onesIndex = $number % 10;
                if ($onesIndex === 1) {
                    $result .= 'أحد عشر';
                } elseif ($onesIndex === 2) {
                    $result .= 'اثنا عشر';
                } else {
                    $result .= self::$ones[$onesIndex] . ' عشر';
                }
            } else {
                $onesIndex = $number % 10;
                $tensIndex = (int) ($number / 10) * 10;

                if ($onesIndex > 0) {
                    $result .= self::$ones[$onesIndex] . ' و ' . self::$tens[$tensIndex];
                } else {
                    $result .= self::$tens[$tensIndex];
                }
            }
        }

        return $result;
    }
}
