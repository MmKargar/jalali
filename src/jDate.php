<?php

namespace MmKargar\Jalali;

/**
 * A LaravelPHP helper class for working w/ jalali dates.
 * by Sallar Kaboli <sallar.kaboli@gmail.com>
 *
 *
 * Based on Laravel-Date bundle
 * by Scott Travis <scott.w.travis@gmail.com>
 * http://github.com/swt83/laravel-date
 *
 *
 * @package     jDate
 * @author      Sallar Kaboli <sallar.kaboli@gmail.com>
 * @link        http://
 * @basedon     http://github.com/swt83/laravel-date
 * @license     MIT License
 */

class jDate
{
    protected $time;

    protected $formats = array(
        'datetime' => '%Y-%m-%d %H:%M:%S',
        'date' => '%Y-%m-%d',
        'time' => '%H:%M:%S',
    );

    public static function forge($str = null)
    {
        $class = __CLASS__;
        return new $class($str);
    }

    public function __construct($str = null)
    {
        if ($str === null) {
            $this->time = time();
        } else {
            if (is_numeric($str)) {
                $this->time = $str;
            } else {
                $time = strtotime($str);

                if (!$time) {
                    $this->time = false;
                } else {
                    $this->time = $time;
                }
            }
        }
    }

    public function time()
    {
        return $this->time;
    }

    public function format($str, $convertNumbersToPersian = false)
    {
        // convert alias string
        if (in_array($str, array_keys($this->formats))) {
            $str = $this->formats[$str];
        }

        // if valid unix timestamp...
        if ($this->time !== false) {
            return jDateTime::strftime($str, $this->time, $convertNumbersToPersian);
        } else {
            return false;
        }
    }

    public function reforge($str)
    {
        if ($this->time !== false) {
            // amend the time
            $time = strtotime($str, $this->time);

            // if conversion fails...
            if (!$time) {
                // set time as false
                $this->time = false;
            } else {
                // accept time value
                $this->time = $time;
            }
        }

        return $this;
    }

    public function ago()
    {
        $now = time();
        $time = $this->time();

        // catch error
        if (!$time) {
            return false;
        }

        // build period and length arrays
        $periods = array('ثانیه', 'دقیقه', 'ساعت', 'روز', 'هفته', 'ماه', 'سال', 'قرن');
        $lengths = array(60, 60, 24, 7, 4.35, 12, 10);

        // get difference
        $difference = $now - $time;

        // set descriptor
        if ($difference < 0) {
            $difference = abs($difference); // absolute value
            $negative = true;
        }

        // do math
        for ($j = 0; $difference >= $lengths[$j] and $j < count($lengths) - 1; $j++) {
            $difference /= $lengths[$j];
        }

        // round difference
        $difference = intval(round($difference));

        // return
        return number_format($difference) . ' ' . $periods[$j] . ' ' . (isset($negative) ? '' : 'پیش');
    }

    public function until()
    {
        return $this->ago();
    }

    /**
     * @param $format
     * @param $date
     * @return array
     */
    public function parseFromFormat($format, $date)
    {
        // reverse engineer date formats
        $keys = array(
            'Y' => array('year', '\d{4}'),
            'y' => array('year', '\d{2}'),
            'm' => array('month', '\d{2}'),
            'n' => array('month', '\d{1,2}'),
            'M' => array('month', '[A-Z][a-z]{3}'),
            'F' => array('month', '[A-Z][a-z]{2,8}'),
            'd' => array('day', '\d{2}'),
            'j' => array('day', '\d{1,2}'),
            'D' => array('day', '[A-Z][a-z]{2}'),
            'l' => array('day', '[A-Z][a-z]{6,9}'),
            'u' => array('hour', '\d{1,6}'),
            'h' => array('hour', '\d{2}'),
            'H' => array('hour', '\d{2}'),
            'g' => array('hour', '\d{1,2}'),
            'G' => array('hour', '\d{1,2}'),
            'i' => array('minute', '\d{2}'),
            's' => array('second', '\d{2}'),
        );

        // convert format string to regex
        $regex = '';
        $chars = str_split($format);
        foreach ($chars as $n => $char) {
            $lastChar = isset($chars[$n - 1]) ? $chars[$n - 1] : '';
            $skipCurrent = '\\' == $lastChar;
            if (!$skipCurrent && isset($keys[$char])) {
                $regex .= '(?P<' . $keys[$char][0] . '>' . $keys[$char][1] . ')';
            } else if ('\\' == $char) {
                $regex .= $char;
            } else {
                $regex .= preg_quote($char);
            }
        }

        $dt = array();
        $dt['error_count'] = 0;
        // now try to match it
        if (preg_match('#^' . $regex . '$#', $date, $dt)) {
            foreach ($dt as $k => $v) {
                if (is_int($k)) {
                    unset($dt[$k]);
                }
            }
            if (!jDateTime::checkdate($dt['month'], $dt['day'], $dt['year'], false)) {
                $dt['error_count'] = 1;
            }
        } else {
            $dt['error_count'] = 1;
        }
        $dt['errors'] = array();
        $dt['fraction'] = '';
        $dt['warning_count'] = 0;
        $dt['warnings'] = array();
        $dt['is_localtime'] = 0;
        $dt['zone_type'] = 0;
        $dt['zone'] = 0;
        $dt['is_dst'] = '';

        if (strlen($dt['year']) == 2) {
            $now = self::forge('now');
            $x = $now->format('Y') - $now->format('y');
            $dt['year'] += $x;
        }

        $dt['year'] = isset($dt['year']) ? (int)$dt['year'] : 0;
        $dt['month'] = isset($dt['month']) ? (int)$dt['month'] : 0;
        $dt['day'] = isset($dt['day']) ? (int)$dt['day'] : 0;
        $dt['hour'] = isset($dt['hour']) ? (int)$dt['hour'] : 0;
        $dt['minute'] = isset($dt['minute']) ? (int)$dt['minute'] : 0;
        $dt['second'] = isset($dt['second']) ? (int)$dt['second'] : 0;

        return $dt;
    }

    /**
     * @param $format
     * @param $str
     * @return \DateTime
     */
    public static function dateTimeFromFormat($format, $str)
    {
        $jd = new jDate();
        $pd = $jd->parseFromFormat($format, $str);
        $gd = jDateTime::toGregorian($pd['year'], $pd['month'], $pd['day']);
        $date = new \DateTime();
        $date->setDate($gd[0], $gd[1], $gd[2]);
        $date->setTime($pd['hour'], $pd['minute'], $pd['second']);
        return $date;
    }

    public function tr_num($str, $mod = 'en', $mf = '٫')
    {
        $num_a = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.');
        $key_a = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', $mf);
        return ($mod == 'fa') ? str_replace($num_a, $key_a, $str) : str_replace($key_a, $num_a, $str);
    }

    /* Please Enter the jalali date in this template = 1399/02/22 , it will return date('Y-m-d' , $date) */
    public function jalaliToGregorian($template, $spliter = '/', $mod = '')
    {
        $ex = explode($spliter, $template);
        $jy = $ex[0];
        $jm = $ex[1];
        $jd = $ex[2];
        list($jy, $jm, $jd) = explode('_', $this->tr_num($jy . '_' . $jm . '_' . $jd));/* <= Extra :اين سطر ، جزء تابع اصلي نيست */
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (((int) ($jy / 33)) * 8) + ((int) ((($jy % 33) + 3) / 4)) + $jd + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
        $gy = 400 * ((int) ($days / 146097));
        $days %= 146097;
        if ($days > 36524) {
            $gy += 100 * ((int) (--$days / 36524));
            $days %= 36524;
            if ($days >= 365) $days++;
        }
        $gy += 4 * ((int) ($days / 1461));
        $days %= 1461;
        if ($days > 365) {
            $gy += (int) (($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        $gd = $days + 1;
        $sal_a = array(0, 31, (($gy % 4 == 0 and $gy % 100 != 0) or ($gy % 400 == 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        for ($gm = 0; $gm < 13 and $gd > $sal_a[$gm]; $gm++) $gd -= $sal_a[$gm];
        $gdate = ($mod == '') ? array($gy, $gm, $gd) : $gy . $mod . $gm . $mod . $gd;
        return date('Y-m-d', strtotime($gdate[0] . '-' . $gdate[1] . '-' . $gdate[2]));
    }
}
