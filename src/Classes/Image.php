<?php

declare(strict_types=1);

namespace Core\Classes;

use Core\Contracts\CoreAbstract;
use Exception;

class Image extends CoreAbstract
{

    /**
     * Method for upload image 
     */
    public static function Upload(string $keyFile, string $pathUpload, string &$URL_IMG): bool
    {
        try {
            $business = $_SESSION['Business']->info['business'];
            $fileIMG = $_FILES[$keyFile];
            $file_type = $fileIMG['type'];
            $file_type = explode('/', $file_type);
            $file_type = $file_type[1];
            // Generar un nuevo nombre de archivo único
            $imageFileType = strtolower(pathinfo($fileIMG["name"], PATHINFO_EXTENSION));
            $newFileName = uniqid($business."_", true) . '.' . $imageFileType;
            $target_dir = "../upload/$pathUpload/"; // Directorio donde se guardarán los archivos
            $target_file = $target_dir . $newFileName;
            # Creamos un recurso de imagen 
            $allowedTypes = ['gif', 'png', 'jpg', 'jpeg', 'webp', 'bmp'];
            if (!in_array($file_type, $allowedTypes)) {
                return false;
            }

            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Verificar el tamaño del archivo
            if ($fileIMG["size"] > 500000) { // 500 KB como límite
                return false;
            }

            if (move_uploaded_file($fileIMG["tmp_name"], $target_file)) {
                $URL_IMG = PATH_UPLOAD . "$pathUpload/$newFileName";
                return true;
            }

            return false;
        } catch (Exception $e) {
            self::ExceptionCapture($e, 'Image::Upload');
            return false;
        }
    }

    public static function deleteImage(string $file): bool
    {
        try {
            if (file_exists($file)) {
                return unlink($file);
            }
            return false;
        } catch (\Throwable $th) {
            self::ExceptionCapture($th, 'Image::deleteImage');
            return false;
        }
    }
}
