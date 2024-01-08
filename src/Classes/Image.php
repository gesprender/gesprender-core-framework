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
    public static function Upload(string $keyFile, string $pathUpload, string $nameAssigned, string &$URL_IMG): bool
    {
        try {
            $fileIMG = $_FILES[$keyFile];

            // Extension file
            $imageFileType = strtolower(pathinfo($fileIMG["name"], PATHINFO_EXTENSION));

            $allowedTypes = ['gif', 'png', 'jpg', 'jpeg', 'webp', 'bmp'];
            if (!in_array($imageFileType, $allowedTypes)) {
                return false;
            }
            
            $newFileName = $nameAssigned . '.' . $imageFileType;
            $target_dir = "../upload/$pathUpload/"; // Directorio donde se guardarán los archivos
            $target_file = $target_dir . $newFileName;
            
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
