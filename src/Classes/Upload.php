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
            $businessInfo = self::getBusinessFromToken();
            $business = $businessInfo['business'] ?? 'default';
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

    /**
     * Obtiene información del business sin usar sesiones
     * Reemplaza $_SESSION['Business']
     */
    private static function getBusinessFromToken(): ?array
    {
        try {
            // Opción 1: Desde Security service si está disponible
            if (class_exists('Backoffice\Modules\User\Infrastructure\Services\Security')) {
                $business = \Backoffice\Modules\User\Infrastructure\Services\Security::getBusiness();
                if ($business && isset($business->info)) {
                    return $business->info;
                }
            }
            
            // Opción 2: Desde header Authorization
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? '';
            
            if (strpos($authHeader, 'Bearer ') === 0) {
                $token = substr($authHeader, 7);
                // Aquí podrías decodificar JWT si usas JWT
                // Por ahora, usar un fallback básico
            }
            
            // Opción 3: Fallback con business por defecto
            return [
                'business' => 'default',
                'id' => 1
            ];
            
        } catch (\Throwable $e) {
            error_log("Error getting business info: " . $e->getMessage());
            return null;
        }
    }
}