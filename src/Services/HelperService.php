<?php

declare(strict_types=1);

namespace Core\Services;

/**
 * HelperService - Servicio de utilidades con Dependency Injection
 * 
 * Versión NO estática del servicio Helper que mantiene toda la funcionalidad
 * pero permite dependency injection y testing.
 */
class HelperService
{
    private LoggerService $logger;
    private ConfigService $config;

    public function __construct(LoggerService $logger = null, ConfigService $config = null)
    {
        $this->logger = $logger ?? ServiceContainer::resolve(LoggerService::class);
        $this->config = $config ?? ServiceContainer::resolve('config');
    }

    /**
     * Valida si hay caracteres inválidos en string
     */
    public function characterInvalid(string $string): bool
    {
        $invalid_chars = ['<', '>', '"', "'", '&', '\r', '\n', '\t'];
        
        foreach ($invalid_chars as $char) {
            if (strpos($string, $char) !== false) {
                $this->logger->debug('Invalid character detected', [
                    'string' => substr($string, 0, 50) . '...',
                    'invalid_char' => $char
                ]);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Valida input de formularios
     */
    public function validateInput(array $input): bool
    {
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                if ($this->characterInvalid($value)) {
                    $this->logger->warning('Invalid input detected', [
                        'field' => $key,
                        'value_preview' => substr($value, 0, 30) . '...'
                    ]);
                    return false;
                }
            }
        }
        
        $this->logger->debug('Input validation passed', [
            'fields_count' => count($input)
        ]);
        
        return true;
    }

    /**
     * Valida input que solo contenga letras y ciertos caracteres
     */
    public function validateInputOnlyLettersCharacters(array $input): bool
    {
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                // Solo permitir letras, números, espacios y algunos caracteres especiales
                if (!preg_match('/^[a-zA-Z0-9\s\-_.,@]+$/', $value)) {
                    $this->logger->warning('Invalid characters in input', [
                        'field' => $key,
                        'value_preview' => substr($value, 0, 30) . '...'
                    ]);
                    return false;
                }
            }
        }
        
        $this->logger->debug('Letters-only validation passed', [
            'fields_count' => count($input)
        ]);
        
        return true;
    }

    /**
     * Obtiene el tamaño de un directorio de forma segura (optimizado para memoria)
     */
    public function getDirectorySize(string $dir, int $maxFiles = 10000): string
    {
        if (!is_dir($dir)) {
            $this->logger->warning('Directory not found', ['dir' => $dir]);
            return '0 B';
        }

        $size = 0;
        $fileCount = 0;
        $startMemory = memory_get_usage(true);
        
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                // PROTECCIÓN: Limitar número de archivos procesados
                if (++$fileCount > $maxFiles) {
                    $this->logger->warning('Directory scan truncated due to file limit', [
                        'dir' => $dir,
                        'files_processed' => $fileCount,
                        'max_files' => $maxFiles
                    ]);
                    break;
                }
                
                // PROTECCIÓN: Monitorear uso de memoria
                if ($fileCount % 1000 === 0) {
                    $currentMemory = memory_get_usage(true);
                    $memoryIncrease = $currentMemory - $startMemory;
                    
                    // Si el uso de memoria aumentó más de 50MB, detener
                    if ($memoryIncrease > 50 * 1024 * 1024) {
                        $this->logger->warning('Directory scan stopped due to high memory usage', [
                            'dir' => $dir,
                            'files_processed' => $fileCount,
                            'memory_increase' => $this->formatBytes($memoryIncrease)
                        ]);
                        break;
                    }
                }

                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Error calculating directory size', [
                'dir' => $dir,
                'error' => $e->getMessage(),
                'files_processed' => $fileCount
            ]);
            return 'Error';
        }

        $endMemory = memory_get_usage(true);
        $formatted = $this->formatBytes($size);
        
        $this->logger->debug('Directory size calculated', [
            'dir' => $dir,
            'size' => $formatted,
            'files_processed' => $fileCount,
            'memory_used' => $this->formatBytes($endMemory - $startMemory)
        ]);

        return $fileCount >= $maxFiles ? $formatted . ' (truncated)' : $formatted;
    }

    /**
     * Formatea bytes en formato legible
     */
    public function formatBytes(int $size, int $precision = 2): string
    {
        if ($size == 0) return '0 B';

        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];

        $result = round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
        
        $this->logger->debug('Bytes formatted', [
            'size_bytes' => $size,
            'formatted' => $result
        ]);

        return $result;
    }

    /**
     * Cuenta archivos en un directorio
     */
    public function countFilesInDirectory(string $directorio): int
    {
        if (!is_dir($directorio)) {
            $this->logger->warning('Directory not found for file count', ['dir' => $directorio]);
            return 0;
        }

        $count = 0;
        $iterator = new \DirectoryIterator($directorio);
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }

        $this->logger->debug('Files counted in directory', [
            'dir' => $directorio,
            'count' => $count
        ]);

        return $count;
    }

    /**
     * Hace unset de todos los valores de un array
     */
    public function allUnset(array &$values): void
    {
        $keysCount = count($values);
        
        foreach ($values as $key => &$value) {
            unset($values[$key]);
        }
        
        $this->logger->debug('Array values unset', [
            'original_count' => $keysCount,
            'final_count' => count($values)
        ]);
    }

    /**
     * Limpia caracteres especiales de un string
     */
    public function clearSpecialCharacters(string $character): string
    {
        $original = $character;
        
        // Eliminar caracteres especiales comunes
        $character = preg_replace('/[^\w\s\-_.]/', '', $character);
        
        // Eliminar espacios múltiples
        $character = preg_replace('/\s+/', ' ', $character);
        
        // Trim
        $character = trim($character);
        
        $this->logger->debug('Special characters cleared', [
            'original' => substr($original, 0, 50) . '...',
            'cleaned' => substr($character, 0, 50) . '...',
            'length_diff' => strlen($original) - strlen($character)
        ]);

        return $character;
    }

    /**
     * Obtiene nombre del mes en español
     */
    public function getMonthName(int $mes): string|bool
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        if (isset($meses[$mes])) {
            $monthName = $meses[$mes];
            $this->logger->debug('Month name obtained', [
                'month_number' => $mes,
                'month_name' => $monthName
            ]);
            return $monthName;
        }

        $this->logger->warning('Invalid month number', ['month' => $mes]);
        return false;
    }

    /**
     * Obtiene nombre del día en español
     */
    public function getDayName(int $num): string|bool
    {
        $dias = [
            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
            5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
        ];

        if (isset($dias[$num])) {
            $dayName = $dias[$num];
            $this->logger->debug('Day name obtained', [
                'day_number' => $num,
                'day_name' => $dayName
            ]);
            return $dayName;
        }

        $this->logger->warning('Invalid day number', ['day' => $num]);
        return false;
    }

    /**
     * Convierte letra a número
     */
    public function letterWithNumber(string $let): int
    {
        $letter = strtoupper(trim($let));
        $number = ord($letter) - ord('A') + 1;
        
        $this->logger->debug('Letter converted to number', [
            'letter' => $letter,
            'number' => $number
        ]);

        return $number;
    }

    /**
     * Genera un identificador único
     */
    public function generateUniqueId(string $prefix = ''): string
    {
        $id = $prefix . uniqid() . '_' . time();
        
        $this->logger->debug('Unique ID generated', [
            'prefix' => $prefix,
            'id' => $id
        ]);

        return $id;
    }

    /**
     * Valida formato de email
     */
    public function validateEmail(string $email): bool
    {
        $isValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        
        $this->logger->debug('Email validation', [
            'email' => $email,
            'is_valid' => $isValid
        ]);

        return $isValid;
    }

    /**
     * Sanitiza string para uso en URLs
     */
    public function sanitizeForUrl(string $string): string
    {
        $original = $string;
        
        // Convertir a minúsculas
        $string = strtolower($string);
        
        // Reemplazar espacios y caracteres especiales con guiones
        $string = preg_replace('/[^\w\-_.]/', '-', $string);
        
        // Eliminar guiones múltiples
        $string = preg_replace('/-+/', '-', $string);
        
        // Trim guiones del inicio y final
        $string = trim($string, '-');
        
        $this->logger->debug('String sanitized for URL', [
            'original' => $original,
            'sanitized' => $string
        ]);

        return $string;
    }

    /**
     * Obtiene información de debugging del servicio
     */
    public function getDebugInfo(): array
    {
        return [
            'service' => 'HelperService',
            'methods_count' => 15,
            'logger_available' => $this->logger !== null,
            'config_available' => $this->config !== null,
            'memory_usage' => memory_get_usage(true)
        ];
    }
} 