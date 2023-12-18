<?php
namespace Core\Services;
class Request {


    public static function On(string $key, $callback){
        $pathRequest = str_replace("/api/index.php", "", $_SERVER['REQUEST_URI']);
        if(array_key_exists($key, $_REQUEST) || $pathRequest == $key){
            $callback();
        }
    }

    public static function GET(string $key, $callback){

        if(array_key_exists($key, $_GET)){
            $callback();
        }
    }
    public static function POST(string $key, $callback){

        if(array_key_exists($key, $_POST)){
            $callback();
        }
    }

    public static function getValue($key, $default = false)
    {
        if(!isset($_REQUEST[$key])) return $default;
        
        if(!Validations::StringSQL($_REQUEST[$key])){
            $chars = implode(', ', Validations::CHARACTERS_INVALIDS);
            return Response::json([
                'message' => "Haz ingresado un caracter no apectado. Ej: $chars",
                'data' => []
            ], 400);
        }

        return $_REQUEST[$key];
    }

}