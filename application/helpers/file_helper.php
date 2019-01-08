<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


if (!function_exists('file_delAllFilesFromFolder')) {

    /**
     * @param string $pasta deve ter / no final
     * @param array or string $ignoredFiles arquivos ignorados
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    function file_delAllFilesFromFolder($pasta, $ignoredFiles = null) {
        if (!is_dir($pasta)) {
            return false;
        } else {
            $ignoredFiles = !is_array($ignoredFiles) ? array($ignoredFiles) : $ignoredFiles;
            $diretorio = dir($pasta);
            while ($arquivo = $diretorio->read()) {
                if (!in_array($arquivo, $ignoredFiles) && ($arquivo != '.') && ($arquivo != '..')) {
                    unlink($pasta . $arquivo);
                }
            }
            $diretorio->close();
        }

        return true;
    }

}

if (!function_exists('file_delFileFromFolder')) {

    /**
     * @param string $pasta deve ter / no final
     * @param array or string $ignoredFiles arquivos ignorados
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    function file_delFileFromFolder($file) {
        if (file_exists($file)) {
            unlink($file);
        }

        return true;
    }

}

if (!function_exists('file_getMimeTypeFromFile')) {

    function file_getMimeTypeFromFile($filename) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filename);
        finfo_close($finfo);

        return $mime;
    }

}