<?php

namespace CM3_Lib\util;

// Lifted from https://stackoverflow.com/a/959986
class BaseIntEncoder
{
    //const codeset = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    //readable character set excluded (0,O,1,l)
    //const codeset = "23456789abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ";
    //Shuffled
    public const codeset = "XFAYRgEejGwJv23QrTzW8ZaUkHCx4bDfyi7qmKnLBp5INV9coMPSud6tsh";

    public static function encode($n)
    {
        $base = strlen(self::codeset);
        $converted = '';

        while ($n > 0) {
            $converted = substr(self::codeset, bcmod($n, $base), 1) . $converted;
            $n = self::bcFloor(bcdiv($n, $base));
        }

        return $converted ;
    }

    public static function decode($code)
    {
        $base = strlen(self::codeset);
        $c = '0';
        for ($i = strlen($code); $i; $i--) {
            $c = bcadd($c, bcmul(strpos(self::codeset, substr($code, (-1 * ($i - strlen($code))), 1)), bcpow($base, $i-1)));
        }

        return bcmul($c, 1, 0);
    }

    private static function bcFloor($x)
    {
        return bcmul($x, '1', 0);
    }

    private static function bcCeil($x)
    {
        $floor = bcFloor($x);
        return bcadd($floor, ceil(bcsub($x, $floor)));
    }

    private static function bcRound($x)
    {
        $floor = bcFloor($x);
        return bcadd($floor, round(bcsub($x, $floor)));
    }
}
