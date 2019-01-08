<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


if (!function_exists('pagination_calculateIni')) {

    function pagination_calculateIni(int $quantidade, int $pagina) : int {
        $pagina = $pagina > 0 ? $pagina : 1;
        return $quantidade * $pagina - $quantidade;
    }

}
