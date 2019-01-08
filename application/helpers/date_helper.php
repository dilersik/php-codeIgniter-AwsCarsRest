<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


if (!function_exists('date_getDateTimeISO')) {

    function date_getDateTimeISO() {
        $now = new DateTime();
        $now->setTimezone(new DateTimezone('America/Sao_Paulo'));
        return $now->format('Y-m-d H:i:s');
    }

}