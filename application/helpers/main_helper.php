<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

    function time_elapsed_string($datetime, $full = true) {
        date_default_timezone_set('Asia/Calcutta'); 
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }
     $orgDateTime  = strtotime($datetime);
     $nowDateTime  = strtotime(date('Y-m-d h:i:s'));
        if (!$full)
            $string = array_slice($string, 0, 1);
            if($nowDateTime > $orgDateTime){
        return $string ? implode(', ', $string) . ' ago' : 'just now';
            }else{
        return $string ? implode(', ', $string) . ' to go' : 'just now';
            }
    }

