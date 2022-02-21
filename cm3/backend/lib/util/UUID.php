<?php

//Stolen from  https://raw.githubusercontent.com/Jazzee/Foundation/master/src/Foundation/UUID.php

namespace CM3_Lib\util;

/**
 * Generate UUIDs
 * Attempts to use the PECL UUID library
 * Stolen from comments in PHP manual: http://www.php.net/manual/en/function.uniqid.php
 *
 * @package Foundation
 */
class UUID
{
    /**
     * Generates version 4 UUID: random
     */
    public static function v4()
    {
        //try and use the PECL UUID library
        if (function_exists('uuid_create') and defined('UUID_TYPE_RANDOM')) {
            return \uuid_create(UUID_TYPE_RANDOM);
        }
        //if not we have to do it ourselfex becuase the PECL implementaiton is worthless
        $prBits = null;
        $fp = @fopen('/dev/urandom', 'rb');
        if ($fp !== false) {
            $prBits .= @fread($fp, 16);
            @fclose($fp);
        } else {
            // If /dev/urandom isn't available (eg: in non-unix systems), use mt_rand().
            $prBits = "";
            for ($cnt = 0; $cnt < 16; $cnt++) {
                $prBits .= chr(mt_rand(0, 255));
            }
        }

        $timeLow = bin2hex(substr($prBits, 0, 4));
        $timeMid = bin2hex(substr($prBits, 4, 2));
        $timeHiAndVersion = bin2hex(substr($prBits, 6, 2));
        $clockSeqHiAndReserved = bin2hex(substr($prBits, 8, 2));
        $node = bin2hex(substr($prBits, 10, 6));

        /**
         * Set the four most significant bits (bits 12 through 15) of the
         * $timeHiAndVersion field to the 4-bit version number from
         * Section 4.1.3.
         * @see http://tools.ietf.org/html/rfc4122#section-4.1.3
         */
        $timeHiAndVersion = hexdec($timeHiAndVersion);
        $timeHiAndVersion = $timeHiAndVersion >> 4;
        $timeHiAndVersion = $timeHiAndVersion | 0x4000;

        /**
         * Set the two most significant bits (bits 6 and 7) of the
         * $clockSeqHiAndReserved to zero and one, respectively.
         */
        $clockSeqHiAndReserved = hexdec($clockSeqHiAndReserved);
        $clockSeqHiAndReserved = $clockSeqHiAndReserved >> 2;
        $clockSeqHiAndReserved = $clockSeqHiAndReserved | 0x8000;

        return sprintf(
            '%08s-%04s-%04x-%04x-%012s',
            $timeLow,
            $timeMid,
            $timeHiAndVersion,
            $clockSeqHiAndReserved,
            $node
        );
    }
}
