<?php
declare(strict_types=1);

namespace Core\Classes;
use Core\Contracts\CoreAbstract;

class Upload extends CoreAbstract
{

    public static function img($file, $ruta_up, $name_file = ''): string|bool
    {
        try {
            // Capturamos datos de la imagen
            $business = $_SESSION['Business']->info['business'];
            $basename = $file['name'];
            $image = $file['tmp_name'];
            $extencion = pathinfo($basename, PATHINFO_EXTENSION);
            // Movemos el temporal a la ruta de productos
            $ruta = $ruta_up . "/$basename";
            move_uploaded_file($image, $ruta);
            // Cambiamos el nombre del archivo
            $old_name = $ruta . "/$basename";
            // chmod($ruta_up, 777);
            if($name_file != ''){
                $new_name = $name_file.'.'.$extencion;
                $status = rename($ruta , $ruta_up . "/$new_name");
            }else{
                $rand = rand(0, 99999999);
                $new_name = $business .'_'.hash('ripemd160', "$rand" ).'.'.$extencion;
                $status = rename($ruta . "/$basename", $ruta_up . "/$new_name");
            }
            return $status ? $new_name : false;
        } catch (\Throwable $th) {
            self::ExceptionCapture($th, 'Upload::img');
            return false;
        }
    }

}