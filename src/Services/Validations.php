<?php
declare(strict_types=1);

namespace Core\Services;

class Validations
{
    const CHARACTERS_INVALIDS = ["'", "\"", "\\", ";", "--", "#", "/*", "*/"];
    public static function StringSQL(string $string): bool
    {

        if (is_numeric($string)) {
            return (bool) $string;
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
}
