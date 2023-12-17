<?php

namespace Core\Services;

class Validations
{
    CONST CHARACTERS_INVALIDS = ["'", "\"", "\\", ";", "--", "#", "/*", "*/"];
    public static function StringSQL($string)
    {

        if (is_numeric($string)) {
            return (int) $string;
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
}
