# Sistema de Logging y Debugging - GesPrender Framework

## Visión General

El nuevo sistema de logging de GesPrender Framework es una implementación moderna que cumple con los estándares PSR-3, ofreciendo funcionalidades avanzadas de logging y debugging para mejorar significativamente la experiencia de desarrollo y el monitoreo en producción.

### Características Principales

- ✅ **PSR-3 Compatible**: Implementa `LoggerInterface` estándar
- ✅ **Múltiples Canales**: `app`, `error`, `access`, `performance`, `security`
- ✅ **Structured Logging**: Contexto rico con metadata
- ✅ **Whoops Integration**: Pretty error pages en desarrollo
- ✅ **Rotating Logs**: Rotación automática por fecha
- ✅ **Environment-Aware**: Configuración automática según dev/prod
- ✅ **Backward Compatible**: Mantiene API legacy
- ✅ **Performance Profiling**: Medición automática de rendimiento
- ✅ **Auto-Initialization**: Integración transparente con Kernel

---

## Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────┐
│                    LOGGING ARCHITECTURE                     │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐    ┌─────────────────────────────────┐ │
│  │   PSR-3 Logger  │    │        Whoops Debugger         │ │
│  │   (Monolog)     │    │      (Pretty Errors)           │ │
│  └─────────────────┘    └─────────────────────────────────┘ │
│           │                            │                    │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │              LoggerService                              │ │
│  │          (Framework Integration)                        │ │
│  └─────────────────────────────────────────────────────────┘ │
│           │                                                 │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │                Multiple Channels                        │ │
│  │   [error] [info] [debug] [access] [performance]        │ │
│  └─────────────────────────────────────────────────────────┘ │
│           │                                                 │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │                  Output Handlers                        │ │
│  │     [File] [Stream] [Rotating] [Console] [Context]     │ │
│  └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

---

## Componentes del Sistema

### 1. LoggerService (`Core\Services\LoggerService`)

**Propósito**: Logger principal que implementa PSR-3 con Monolog
**Ubicación**: `src/Services/LoggerService.php`

#### Características:
- Implementa `Psr\Log\LoggerInterface`
- Soporte para múltiples canales
- Configuración automática por ambiente
- Formatters personalizados
- Rotating files para logs críticos

#### Canales Disponibles:

| Canal | Propósito | Handlers | Rotación |
|-------|-----------|----------|----------|
| `app` | Log principal de aplicación | File + Console | No |
| `error` | Errores críticos | Rotating File | 30 días |
| `access` | Requests HTTP | File | No |
| `performance` | Métricas de rendimiento | File | No |
| `security` | Eventos de seguridad | Rotating File | 90 días |

### 2. DebugService (`Core\Services\DebugService`)

**Propósito**: Sistema de debugging avanzado con Whoops
**Ubicación**: `src/Services/DebugService.php`

#### Características:
- Whoops pretty error pages
- Context-aware handlers (Web/API/Console)
- Performance profiling
- Security filtering de variables sensibles
- Debugging helpers (`dump()`, `trace()`)

### 3. LoggerServiceProvider (`Core\Services\LoggerServiceProvider`)

**Propósito**: Proveedor de servicios para integración con Kernel
**Ubicación**: `src/Services/LoggerServiceProvider.php`

#### Características:
- Inicialización automática
- Error handling personalizado para PHP
- Configuration por ambiente
- Helpers para logging rápido
- Performance monitoring de requests

### 4. LoggerCompatibilityWrapper (`Core\Classes\LoggerCompatibilityWrapper`)

**Propósito**: Mantiene compatibilidad con sistema legacy
**Ubicación**: `src/Classes/LoggerCompatibilityWrapper.php`

#### Características:
- Wrapper para `Logger::error()` anterior
- Fallback automático al sistema legacy
- Migración transparente
- Marcado como deprecated

---

## Guía de Uso

### Uso Básico (PSR-3 Recomendado)

```php
use Core\Services\LoggerService;

// Obtener instancia del logger
$logger = LoggerService::getInstance();

// Logging básico por niveles
$logger->debug('Variable value', ['var' => $value]);
$logger->info('User logged in', ['user_id' => 123]);
$logger->warning('Deprecated function used');
$logger->error('Database connection failed', ['host' => 'localhost']);
$logger->critical('System out of memory');

// Structured logging con contexto rico
$logger->info('Order processed', [
    'order_id' => 12345,
    'user_id' => 67890,
    'amount' => 99.99,
    'payment_method' => 'credit_card',
    'processing_time_ms' => 150
]);
```

### Uso por Canales

```php
// Canal específico de errores
$logger->channel('error')->error('Critical database error', [
    'table' => 'users',
    'operation' => 'select',
    'error_code' => 1045
]);

// Canal de seguridad
$logger->channel('security')->warning('Failed login attempt', [
    'ip' => '192.168.1.100',
    'username' => 'admin',
    'attempts' => 3
]);

// Canal de performance
$logger->channel('performance')->info('Slow query detected', [
    'query' => 'SELECT * FROM large_table',
    'duration_ms' => 2500,
    'records' => 50000
]);
```

### Métodos Auxiliares Especializados

```php
// Log de errores por módulo (compatible con sistema anterior)
$logger->moduleError('UserModule', 'Error creating user', [
    'user_data' => $userData,
    'validation_errors' => $errors
]);

// Log de performance con métricas automáticas
$logger->performance('database_query', 0.125, [
    'query_type' => 'SELECT',
    'table' => 'products',
    'records_returned' => 250
]);

// Log de acceso HTTP
$logger->access('POST', '/api/users', 201, 0.045);

// Log de eventos de seguridad
$logger->security('suspicious_activity', [
    'event' => 'multiple_failed_logins',
    'ip' => '192.168.1.100',
    'user_agent' => 'curl/7.68.0'
]);
```

### Debugging con DebugService

```php
use Core\Services\DebugService;

$debug = DebugService::getInstance();

// Debugging básico (solo en dev)
$debug->dump($variable, $array, $object);

// Trace de stack
$debug->trace('Checkpoint reached');

// Performance profiling
$result = $debug->profile('expensive_operation', function() {
    // Código a medir
    return processLargeDataset();
});

// Log de excepciones con contexto rico
try {
    riskyOperation();
} catch (Exception $e) {
    $debug->logError($e, ['operation' => 'user_creation']);
    throw $e;
}
```

### Uso del ServiceProvider

```php
use Core\Services\LoggerServiceProvider;

$provider = LoggerServiceProvider::getInstance();

// Helpers rápidos
$provider->logSecurityEvent('failed_login', ['ip' => '192.168.1.1']);
$provider->logRequest('GET', '/api/users', 200, microtime(true) - $startTime);

// Debug dump rápido (solo en dev)
$provider->dd($variable); // Die and dump

// Obtener estadísticas del sistema
$stats = $provider->getStats();
```

### Compatibilidad con Sistema Legacy

```php
use Core\Classes\Logger;

// Mantiene funcionamiento original, pero usa nuevo sistema internamente
Logger::error('UserModule', 'Something went wrong');
Logger::error('OrderModule', ['error' => 'Validation failed', 'code' => 400]);

// El wrapper automáticamente usa LoggerService si está disponible
// Fallback al sistema antiguo si hay problemas
```

---

## Configuración

### Variables de Entorno

```env
# Modo de aplicación (afecta logging)
MODE=dev|prod

# Multi-tenant (afecta contexto de logs)
MULTI_TENANT_MODE=true|false
```

### Configuración Automática por Ambiente

#### Desarrollo (`MODE=dev`)
- **Log Level**: DEBUG (todos los niveles)
- **Handlers**: File + Console
- **Whoops**: Activado
- **Pretty Errors**: Enabled
- **Performance Logging**: Detallado

#### Producción (`MODE=prod`)
- **Log Level**: INFO (sin debug)
- **Handlers**: Solo File + Rotating
- **Whoops**: Desactivado
- **Error Pages**: Genéricas
- **Performance Logging**: Básico

### Archivos de Log Generados

```
Logs/
├── app.log                    # Log principal de aplicación
├── errors-YYYY-MM-DD.log      # Errores con rotación diaria (30 días)
├── access.log                 # Logs de acceso HTTP
├── performance.log            # Métricas de rendimiento
├── security-YYYY-MM-DD.log    # Eventos de seguridad (90 días)
└── php_errors.log             # Errores nativos de PHP
```

---

## Integración con Kernel

El sistema se inicializa automáticamente en el Kernel:

```php
// config/Kernel.php
public function run(): void
{
    Response::setHeaders();
    $this->getDotenv();
    
    # Initialize modern logging and debugging system
    $this->initializeLoggingSystem(); // <- Inicialización automática
    
    // ... resto de la lógica
}
```

### Funcionalidades Automáticas

1. **Initialization**: Se activa automáticamente con el framework
2. **Error Handling**: Captura errores PHP automáticamente
3. **Request Logging**: Log automático de requests lentos (>2s)
4. **Exception Handling**: Pretty pages en dev, generic en prod
5. **Shutdown Logging**: Performance metrics al finalizar request

---

## Migración del Sistema Anterior

### Estrategia de Migración

1. **Fase 1**: Sistema nuevo funcionando en paralelo ✅
2. **Fase 2**: Wrapper de compatibilidad activo ✅
3. **Fase 3**: Migración gradual de llamadas legacy
4. **Fase 4**: Eliminación del sistema anterior

### Identificar Uso Legacy

Buscar en el código:
```bash
# Encontrar usos del sistema anterior
grep -r "Logger::error" src/
grep -r "Logger::registerLog" src/
```

### Reemplazar Gradualmente

```php
// Antes (Legacy)
Logger::error('ModuleName', 'Error message');

// Después (Recomendado)
$logger = LoggerService::getInstance();
$logger->moduleError('ModuleName', 'Error message');

// O mejor aún (PSR-3)
$logger->error('Error message', ['module' => 'ModuleName']);
```

---

## Performance y Optimización

### Características de Performance

- **Lazy Loading**: Solo se inicializa cuando se usa
- **Channel Caching**: Loggers por canal se cachean
- **Batch Processing**: Escritura eficiente a archivos
- **Memory Tracking**: Monitoreo automático de memoria
- **Fast Fallback**: Fallback rápido si falla inicialización

### Métricas Incluidas

```php
// Automáticamente incluye en cada log:
[
    'memory_usage' => memory_get_usage(true),
    'peak_memory' => memory_get_peak_usage(true),
    'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
    'request_uri' => $_SERVER['REQUEST_URI'],
    'request_method' => $_SERVER['REQUEST_METHOD']
]
```

---

## Testing

### Ejecutar Tests

```bash
# Ejecutar test completo del sistema
php test_logging_system.php
```

### Tests Incluidos

1. ✅ LoggerServiceProvider Initialization
2. ✅ PSR-3 LoggerService Methods
3. ✅ Multiple Channels
4. ✅ Backward Compatibility
5. ✅ DebugService Functionality
6. ✅ File Logging Verification
7. ✅ Exception Handling

### Verificar Logs

```bash
# Ver logs generados
ls -la Logs/
tail -f Logs/app.log
tail -f Logs/errors-$(date +%Y-%m-%d).log
```

---

## Troubleshooting

### Problemas Comunes

#### 1. "No se pueden escribir logs"
**Causa**: Permisos de directorio
**Solución**: 
```bash
chmod -R 755 Logs/
chown -R www-data:www-data Logs/
```

#### 2. "Whoops no funciona"
**Causa**: Modo producción activado
**Solución**: Verificar `MODE=dev` en `.env`

#### 3. "Fallback al sistema legacy"
**Causa**: Error en inicialización de Monolog
**Solución**: Verificar que las dependencias estén instaladas:
```bash
composer install
```

#### 4. "Logs muy grandes"
**Causa**: Log level muy bajo en producción
**Solución**: Usar `MODE=prod` para reducir verbosidad

### Debug del Sistema

```php
// Obtener información de estado
$provider = LoggerServiceProvider::getInstance();
$stats = $provider->getStats();
var_dump($stats);

// Verificar compatibilidad
$compat = LoggerCompatibilityWrapper::getCompatibilityStats();
var_dump($compat);

// Info del debug service
$debug = DebugService::getInstance();
$debugInfo = $debug->getDebugInfo();
var_dump($debugInfo);
```

---

## Próximos Pasos

### Funcionalidades Pendientes

1. **Email Notifications**: Notificaciones automáticas de errores críticos
2. **Log Aggregation**: Centralización de logs en multi-tenant
3. **Metrics Dashboard**: Panel web para visualizar métricas
4. **Log Search**: Búsqueda y filtrado avanzado de logs
5. **External Integrations**: Sentry, Elasticsearch, etc.

### Mejoras Planificadas

- **Async Logging**: Escritura asíncrona para mejor performance
- **Log Compression**: Compresión automática de archivos antiguos
- **Custom Formatters**: Formatters específicos por canal
- **Real-time Monitoring**: Streaming de logs en tiempo real

---

## Conclusión

El nuevo sistema de logging de GesPrender Framework proporciona:

✅ **Modernización Completa**: De logging básico a sistema PSR-3 profesional
✅ **Debugging Avanzado**: Whoops integration para desarrollo eficiente  
✅ **Compatibilidad Total**: Sin breaking changes, migración gradual
✅ **Performance Monitoring**: Métricas automáticas de rendimiento
✅ **Production Ready**: Configuración automática según ambiente
✅ **Extensibilidad**: Base sólida para futuras mejoras

El sistema está **listo para usar** y mejorará significativamente la experiencia de desarrollo y el monitoreo de aplicaciones en producción.

---

*Documentación del Sistema de Logging - GesPrender Framework v0.0.1*  
*Última actualización: Diciembre 2024* 