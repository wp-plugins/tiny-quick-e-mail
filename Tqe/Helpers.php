<?php

//Disable direct view.
if (!defined('IN_PLUGIN'))
    die('You can not access this file directly.');

/**
 * Helpers class.
 *
 * @author Marijan Šuflaj <msufflaj32@gmail.com>
 * @category Tqe
 * @package Tqe_Helpers
 */
class Tqe_Helpers
{

    /**
     * To fields helper.
     *
     * @var int
     */
    const TO_FIELD          = 0;

    /**
     * Cc field helper.
     *
     * @var int
     */
    const CC_FIELD          = 1;

    /**
     * Bcc fields helper.
     *
     * @var int
     */
    const BCC_FIELD         = 2;



    /**
     * Function makes url from given array.
     *
     * @param string $base Base string
     * @param array $params Params array
     * @param bool $questionMark If true appends `?` to $base
     * @param bool $and If true appends `&` to $base
     * @return string Generated url
     */
    public static function makeUrl($base, $params, $questionMark = false, $and = true)
    {
        $base .= $questionMark ? '?' : '';
        $base .= $and ? '&' : '';

        foreach ($params as $k => $v)
        {
            if (is_array($v)) {
                foreach ($v as $sk => $sv)
                    $base .= urlencode($k) . '[' . urlencode($sk) . ']=' . urlencode($sv) . '&';
            }
            else
                $base .= urlencode($k) . '=' . urlencode($v) . '&';
        }

        return substr($base, 0, -1);
    }

    /**
     * Returns shor text.
     *
     * @param string $text String
     * @param int $len Maximum size
     * @param string $sep Separator that is used to cut string
     * @return string Short string
     */
    public static function getShort($text, $len = 20, $sep = ' ')
    {
        $text = substr($text, 0, $len);
        $pos = strrpos($text, $sep);
        return ($pos === false) ? $text : substr($text, 0, $pos);
    }

}