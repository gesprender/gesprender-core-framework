<?php
declare(strict_types=1);

namespace Core\Services;

use Core\Contracts\CoreAbstract;

class Request extends CoreAbstract
{

    public static function On(string $key, $callback): void
    {
        $pathRequest = str_replace("/api/index.php", "", $_SERVER['REQUEST_URI']);
        if(array_key_exists($key, $_REQUEST) || $pathRequest == $key){
            $callback();
        }
    }

    public static function GET(string $key, $callback): void
    {
        if(array_key_exists($key, $_GET)){
            $callback();
        }
    }

    public static function POST(string $key, $callback): void
    {
        if(array_key_exists($key, $_POST)){
            $callback();
        }
    }

    public static function getValue($key, $default = false): string|bool
    {
        if(!isset($_REQUEST[$key])) return $default;
        
        if(!Validations::StringSQL($_REQUEST[$key])){
            $chars = implode(', ', Validations::CHARACTERS_INVALIDS);
            return Response::json([
                'message' => "Haz ingresado un caracter no aceptado. Ej: $chars",
                'data' => []
            ], 400);
        }

        return $_REQUEST[$key];
    }

    public static function getHeader($key, $default = false): string|bool
    {
        $Headers = getallheaders();
        if(!isset($Headers[$key])) return $default;

        return $_REQUEST[$key];
    }

}