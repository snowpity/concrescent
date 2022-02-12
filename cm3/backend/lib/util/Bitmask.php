<?php

namespace CM3_Lib\util;

//TODO: This is a shim class until the source project lets you use bitmasked values
//https://github.com/cruxinator/php-bitmask/issues/17

abstract class Bitmask extends \Cruxinator\BitMask\BitMask
{
    /**
     * Required because ::from and __construct would use enums constructor, not allowing a combined mask to be stored
     * @param int $value
     * @return BitMask
     */
    public function setValue(int $value): BitMask
    {
        $this->value = $value;
        return $this;
    }
    public function getKey()
    {
        $value = $this->value;
        $f     = array_filter(static::toArray(), function ($key) use (&$value) {
            $isSet = $value & $key;
            //error_log(print_r(array('key'=> $key,'isSet' => $isSet, 'value' => $value), true));
            //$value = $value >> 1;
            return $isSet;
        });

        return array_keys($f);
    }
}
