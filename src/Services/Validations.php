<?php
declare(strict_types=1);

namespace Core\Services;
use InvalidArgumentException;

class Validations
{
    const CHARACTERS_INVALIDS = ["'", "\"", "\\", ";", "--", "#", "/*", "*/"];
    public static function StringSQL(string $string): bool
    {

        if (is_numeric($string)) {
            return true;
        }

        # Potentially dangerous characters list
        $caracteresPeligrosos = self::CHARACTERS_INVALIDS;
        # Verify if the string contains any of the dangerous characters
        foreach ($caracteresPeligrosos as $caracter) {
            if (strpos($string, $caracter) !== false) {
                // Si se encuentra un carácter peligroso, devuelve false
                return false;
            }
        }

        return true;
    }

    public static function IsJsonString(string $string): bool
    {
        if(is_array(json_decode($string, true))) return true;

        return false;
    }

    public static function validateIntegrityForm(array $dataForm):? InvalidArgumentException
    {
        $payloadJson = json_decode(file_get_contents("php://input"), true);
        $dataRequest = $_REQUEST;
        $data = array_merge($payloadJson, $dataRequest);
        foreach ($dataForm as $key) 
        {
            if(!isset($data[$key])) return throw new InvalidArgumentException("Key '$key' not found");
        }

        return null;
    }
}
