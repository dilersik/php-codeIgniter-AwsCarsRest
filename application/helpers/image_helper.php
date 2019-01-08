<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


if (!function_exists('image_getValBase64ImgExt')) {

    // antes de dar o 64_decode é IMPORTANTE
    // remover "data:image/jpg;base64," da string

    function image_getValBase64ImgExt($base64) {
        $array = explode(';', $base64);

        if (isset($array[1])) {
            $img = str_replace('base64,', '', $array[1]);
            $img = str_replace(' ', '+', $img);
            $base64decoded = base64_decode($img);
            $ext = str_replace('data:image/', '', $array[0]);

        } else {
            $base64decoded = base64_decode($base64);
            $finfo = finfo_open();
            $mime = finfo_buffer($finfo, $base64decoded, FILEINFO_MIME_TYPE);
            $arrayMime = explode("/", $mime);
            $ext = $arrayMime[1];
            finfo_close($finfo);
        }

        if (!in_array($ext, ['jpg', 'jpeg', 'gif', 'png'])) {
            return false;
        }

        return [$base64decoded, $ext];
    }

}