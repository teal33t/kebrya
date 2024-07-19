<?php

namespace Jyotish;

class DateConvertor
{
    public static function GregorianToJalali($date, $format = 'YYYY-MM-DD', $asArray = false)
    {
        // Assume $date is in the format 'YYYY-MM-DD'
        $dateParts = explode('-', $date);
        list($gYear, $gMonth, $gDay) = $dateParts;

        // Perform the conversion using the provided function
        list($jYear, $jMonth, $jDay) = self::gregorian_to_jalali($gYear, $gMonth, $gDay);

        if ($asArray) {
            return [$jYear, $jMonth, $jDay];
        }

        return str_replace(['YYYY', 'MM', 'DD'], [$jYear, $jMonth, $jDay], $format);
    }

    public static function JalaliToGregorian($date, $format = 'YYYY-MM-DD', $asArray = false)
    {
        // Assume $date is in the format 'YYYY-MM-DD'
        $dateParts = explode('-', $date);
        list($jYear, $jMonth, $jDay) = $dateParts;

        // Perform the conversion using the provided function
        list($gYear, $gMonth, $gDay) = self::jalali_to_gregorian($jYear, $jMonth, $jDay);

        if ($asArray) {
            return [$gYear, $gMonth, $gDay];
        }

        return str_replace(['YYYY', 'MM', 'DD'], [$gYear, $gMonth, $gDay], $format);
    }

    public static function GregorianToHijri($date, $format = 'YYYY-MM-DD', $asArray = false)
    {
        // Assume $date is in the format 'YYYY-MM-DD'
        $dateParts = explode('-', $date);
        list($gYear, $gMonth, $gDay) = $dateParts;

        // Perform the conversion
        list($hYear, $hMonth, $hDay) = self::gregorianToHijriHelper($gYear, $gMonth, $gDay);

        if ($asArray) {
            return [$hYear, $hMonth, $hDay];
        }

        return str_replace(['YYYY', 'MM', 'DD'], [$hYear, $hMonth, $hDay], $format);
    }

    public static function HijriToGregorian($date, $format = 'YYYY-MM-DD', $asArray = false)
    {
        // Assume $date is in the format 'YYYY-MM-DD'
        $dateParts = explode('-', $date);
        list($hYear, $hMonth, $hDay) = $dateParts;

        // Perform the conversion
        list($gYear, $gMonth, $gDay) = self::hijriToGregorianHelper($hYear, $hMonth, $hDay);

        if ($asArray) {
            return [$gYear, $gMonth, $gDay];
        }

        return str_replace(['YYYY', 'MM', 'DD'], [$gYear, $gMonth, $gDay], $format);
    }

    public static function Carbonize($dateTimeString)
    {
        return new \Carbon\Carbon($dateTimeString);
    }

    private static function gregorian_to_jalali($gy, $gm, $gd)
    {
        $g_d_m = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + ((int) (($gy2 + 3) / 4)) - ((int) (($gy2 + 99) / 100)) + ((int) (($gy2 + 399) / 400)) + $gd + $g_d_m[$gm - 1];
        $jy = -1595 + (33 * ((int) ($days / 12053)));
        $days %= 12053;
        $jy += 4 * ((int) ($days / 1461));
        $days %= 1461;
        if ($days > 365) {
            $jy += (int) (($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        if ($days < 186) {
            $jm = 1 + (int) ($days / 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + (int) (($days - 186) / 30);
            $jd = 1 + (($days - 186) % 30);
        }
        return array($jy, $jm, $jd);
    }

    private static function jalali_to_gregorian($jy, $jm, $jd)
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (((int) ($jy / 33)) * 8) + ((int) ((($jy % 33) + 3) / 4)) + $jd + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
        $gy = 400 * ((int) ($days / 146097));
        $days %= 146097;
        if ($days > 36524) {
            $gy += 100 * ((int) (--$days / 36524));
            $days %= 36524;
            if ($days >= 365)
                $days++;
        }
        $gy += 4 * ((int) ($days / 1461));
        $days %= 1461;
        if ($days > 365) {
            $gy += (int) (($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        $gd = $days + 1;
        $sal_a = array(0, 31, (($gy % 4 == 0 and $gy % 100 != 0) or ($gy % 400 == 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        for ($gm = 0; $gm < 13 and $gd > $sal_a[$gm]; $gm++)
            $gd -= $sal_a[$gm];
        return array($gy, $gm, $gd);
    }


    private static function gregorianToHijriHelper($gy, $gm, $gd)
    {
        // Hijri date starts from 16 July 622 Julian calendar
        $julianDay = gregoriantojd($gm, $gd, $gy);
        $hijriDay = $julianDay - 1948440 + 10632;
        $n = (int) (($hijriDay - 1) / 10631);
        $hijriDay = $hijriDay - 10631 * $n + 354;
        $j = ((int) ((10985 - $hijriDay) / 5316)) * ((int) (50 * $hijriDay / 17719)) + ((int) ($hijriDay / 5670)) * ((int) (43 * $hijriDay / 15238));
        $hijriDay = $hijriDay - ((int) ((30 - $j) / 15)) * ((int) ((17719 * $j / 50))) - ((int) ($j / 16)) * ((int) ((15238 * $j / 43))) + 29;
        $m = (int) (24 * $hijriDay / 709);
        $d = $hijriDay - (int) (709 * $m / 24);
        $y = 30 * $n + $j - 30;

        return [$y, $m, $d];
    }

    private static function hijriToGregorianHelper($hy, $hm, $hd)
    {
        $jd = (int) ((11 * $hy + 3) / 30) + 354 * $hy + 30 * $hm - (int) (($hm - 1) / 2) + $hd + 1948440 - 385;

        if ($jd > 2299160) {
            $l = $jd + 68569;
            $n = (int) ((4 * $l) / 146097);
            $l = $l - (int) ((146097 * $n + 3) / 4);
            $i = (int) ((4000 * ($l + 1)) / 1461001);
            $l = $l - (int) ((1461 * $i) / 4) + 31;
            $j = (int) ((80 * $l) / 2447);
            $d = $l - (int) ((2447 * $j) / 80);
            $l = (int) ($j / 11);
            $m = $j + 2 - 12 * $l;
            $y = 100 * ($n - 49) + $i + $l;
        } else {
            $j = $jd + 1402;
            $k = (int) (($j - 1) / 1461);
            $l = $j - 1461 * $k;
            $n = (int) (($l - 1) / 365) - (int) ($l / 1461);
            $i = $l - 365 * $n + 30;
            $j = (int) ((80 * $i) / 2447);
            $d = $i - (int) ((2447 * $j) / 80);
            $i = (int) ($j / 11);
            $m = $j + 2 - 12 * $i;
            $y = 4 * $k + $n + $i - 4716;
        }

        return [$y, $m, $d];
    }
}
