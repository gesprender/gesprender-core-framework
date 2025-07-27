<?php
declare(strict_types=1);

namespace Core\Services;

/**
 * Helper - Facade estático para HelperService
 * 
 * Mantiene compatibilidad 100% con código existente mientras
 * delega internamente al nuevo HelperService con DI.
 * 
 * @deprecated Los métodos estáticos serán eliminados en v2.0
 * @see HelperService Para el nuevo sistema con DI
 */
class Helper 
{
    private static ?HelperService $service = null;

    /**
     * Obtiene instancia del HelperService
     */
    private static function getService(): HelperService
    {
        if (self::$service === null) {
            try {
                // Intentar resolver desde ServiceContainer
                self::$service = ServiceContainer::resolve(HelperService::class);
            } catch (\Throwable $e) {
                // Fallback: crear instancia directamente
                self::$service = new HelperService();
            }
        }
        return self::$service;
    }

    /**
     * Valida si hay caracteres inválidos en string
     * 
     * @param string $string String a validar
     * @return bool True si hay caracteres inválidos
     * @deprecated Use HelperService::characterInvalid() instead
     */
    public static function character_invalid(string $string): bool
    {
        return self::getService()->characterInvalid($string);
    }

    /**
     * Valida input de formularios
     * 
     * @param array $input Array de inputs a validar
     * @return bool True si el input es válido
     * @deprecated Use HelperService::validateInput() instead
     */
    public static function validate_input(array $input): bool
    {
        return self::getService()->validateInput($input);
    }

    /**
     * Valida input que solo contenga letras y ciertos caracteres
     * 
     * @param array $input Array de inputs a validar
     * @return bool True si solo contiene letras y caracteres permitidos
     * @deprecated Use HelperService::validateInputOnlyLettersCharacters() instead
     */
    public static function validate_input_only_letters_characters(array $input): bool
    {
        return self::getService()->validateInputOnlyLettersCharacters($input);
    }

    /**
     * Obtiene el tamaño de un directorio
     * 
     * @param string $dir Directorio a medir
     * @return string Tamaño formateado
     * @deprecated Use HelperService::getDirectorySize() instead
     */
    public static function Fsize($dir): string
    {
        return self::getService()->getDirectorySize($dir);
    }

    /**
     * Formatea bytes en formato legible
     * 
     * @param int $size Tamaño en bytes
     * @param int $precision Precisión decimal
     * @return string Tamaño formateado
     * @deprecated Use HelperService::formatBytes() instead
     */
    public static function formatBytes($size, $precision = 2): string
    {
        return self::getService()->formatBytes($size, $precision);
    }

    /**
     * Cuenta archivos en un directorio
     * 
     * @param string $directorio Directorio a contar
     * @return int Número de archivos
     * @deprecated Use HelperService::countFilesInDirectory() instead
     */
    public static function countFilesDir($directorio): int
    {
        return self::getService()->countFilesInDirectory($directorio);
    }

    /**
     * Hace unset de todos los valores de un array
     * 
     * @param array $values Array por referencia
     * @deprecated Use HelperService::allUnset() instead
     */
    public static function all_unset(array &$values): void
    {
        self::getService()->allUnset($values);
    }

    /**
     * Limpia caracteres especiales de un string
     * 
     * @param string $character String a limpiar
     * @return string String limpio
     * @deprecated Use HelperService::clearSpecialCharacters() instead
     */
    public static function clearSpecialsCharacters(string $character): string 
    {
        return self::getService()->clearSpecialCharacters($character);
    }

    /**
     * Obtiene nombre del mes en español
     * 
     * @param mixed $mes Número del mes
     * @return string|bool Nombre del mes o false
     * @deprecated Use HelperService::getMonthName() instead
     */
    public static function getMonthName($mes): string|bool
    {
        // Convertir string a int para compatibilidad
        if (is_string($mes)) {
            $mes = (int) $mes;
        }
        return self::getService()->getMonthName($mes);
    }

    /**
     * Obtiene nombre del día en español
     * 
     * @param int $num Número del día
     * @return string|bool Nombre del día o false
     * @deprecated Use HelperService::getDayName() instead
     */
    public static function nombreDia($num): string|bool
    {
        return self::getService()->getDayName($num);
    }

    /**
     * Convierte letra a número
     * 
     * @param string $let Letra a convertir
     * @return int Número correspondiente
     * @deprecated Use HelperService::letterWithNumber() instead
     */
    public static function letterWithNumber($let): int
    {
        return self::getService()->letterWithNumber($let);
    }

    /**
     * Obtiene estadísticas del facade
     */
    public static function getFacadeStats(): array
    {
        return [
            'service_instance' => self::$service !== null,
            'using_service_container' => ServiceContainer::getInstance()->bound(HelperService::class),
            'legacy_mode' => self::$service === null,
            'memory_usage' => memory_get_usage(true)
        ];
    }

    /**
     * Resetea el facade (útil para testing)
     */
    public static function resetFacade(): void
    {
        self::$service = null;
    }

    /**
     * Fuerza el uso de una instancia específica (útil para testing)
     */
    public static function setService(HelperService $service): void
    {
        self::$service = $service;
    }
}