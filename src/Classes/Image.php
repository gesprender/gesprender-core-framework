<?php

namespace Core\Classes;

use Exception;

class Image
{

    /**
     * Method for upload image 
     */
    public static function Upload(string $keyFile, string $pathUpload, string &$URL_IMG): bool
    {
        try {
            $business = $_SESSION['Business']->info['business'];
            $fileIMG = $_FILES[$keyFile];
            $file = $fileIMG['tmp_name'];
            $file_type = $fileIMG['type'];
            $file_type = explode('/', $file_type);
            $file_type = $file_type[1];
            $rand = rand(0, 99999999);
            $new_name = $business . '_' . hash('ripemd160', "$rand") . '.webp';
            // $ruta_up = "../upload/$business/products/$new_name";
            # Creamos un recurso de imagen 
            switch ($file_type) {
                case 'png':
                    $recursoImage = imagecreatefrompng($file);
                    break;
                case 'jpg':
                case 'jpeg':
                    $recursoImage = imagecreatefromjpeg($file);
                    break;
            }
            $image = $recursoImage;
            if (!$image) {
                $image = imagecreatefromstring(file_get_contents($file));
                $recursoImage = $image;
            }

            if (!file_exists("../upload/GesPrender/Market/News/")) {
                if (!mkdir("../upload/GesPrender/Market/News/", 0777, true)) throw new Exception("Al querer cargar la imagen de un New no se pudo crear la carpeta de Upload");
            }

            # Valor entre 0 y 100. Mayor calidad, mayor peso
            $calidad = 20;
            $status = imagewebp($recursoImage, "$pathUpload/$new_name", $calidad);
            if ($status) {
                $URL_IMG = PATH_UPLOAD . "GesPrender/Market/News/$new_name";
                return true;
            }
            return false;
        } catch (Exception $e) {
            Logger::error('Products', 'Error in upload_img -> ' . $e->getMessage());
            return false;
        }
    }
}
