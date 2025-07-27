# Guía de Desarrollo: Extensión y Comunicación de Módulos

## 🎯 **Objetivo**

Esta guía te ayudará a **comunicar módulos** entre sí en el GesPrender Framework usando el sistema de comunicación inter-modular.

## 🔥 **Casos de Uso Comunes**

- **Enviar WhatsApp** cuando se crea un cliente
- **Cobrar cuotas automáticamente** con MercadoPago
- **Enviar emails** cuando hay eventos importantes
- **Sincronizar datos** entre módulos
- **Triggers automáticos** entre funcionalidades

---

## 📋 **Prerequisitos**

Antes de empezar, asegúrate de que:

1. ✅ **El framework está funcionando** correctamente
2. ✅ **Los módulos básicos existen** en `Backoffice/src/Modules/`
3. ✅ **El sistema de comunicación está inicializado** (ver integración con Kernel)

---

## 🚀 **Paso 1: Crear un Módulo Nuevo con Comunicación**

### 1.1 Crear la Estructura del Módulo

```bash
# Estructura básica DDD del módulo
mkdir -p Backoffice/src/Modules/MiModulo/Application
mkdir -p Backoffice/src/Modules/MiModulo/Domain  
mkdir -p Backoffice/src/Modules/MiModulo/Infrastructure
mkdir -p Backoffice/src/Modules/MiModulo/Design
```

### 1.2 Crear el Repository (Infrastructure)

```php
// Backoffice/src/Modules/MiModulo/Infrastructure/MiModuloRepository.php
<?php #GesPrender Core Framework
declare(strict_types=1);
namespace Backoffice\Modules\MiModulo\Infrastructure;

use Core\Contracts\RepositoryAbstract;

class MiModuloRepository extends RepositoryAbstract {
    
    public function findAll(): array
    {
        // Implementar lógica de consulta
        return [];
    }
    
    public function save(array $data): bool
    {
        // Implementar lógica de guardado
        return true;
    }
    
    public function findById(int $id): ?array
    {
        // Implementar lógica de búsqueda
        return null;
    }
}
```

### 1.3 Crear Casos de Uso (Application)

```php
// Backoffice/src/Modules/MiModulo/Application/SetElement.php
<?php #GesPrender Core Framework
declare(strict_types=1);
namespace Backoffice\Modules\MiModulo\Application;

use Backoffice\Modules\MiModulo\Infrastructure\MiModuloRepository;
use Core\Services\JsonResponse;
use Core\Contracts\Traits\TraitRequest;

final class SetElement extends MiModuloRepository
{
    use TraitRequest;

    public function __construct() {
        $this->run();
    }

    # [Route('/mimodulo/create', name: 'MiModuloCreate', methods: 'POST')]
    # useMiddleware
    public function run(): JsonResponse
    {
        try {
            // Tu lógica de negocio aquí
            $data = $this->getRequestData();
            $result = $this->save($data);
            
            return new JsonResponse([
                'status' => true,
                'message' => 'Element created successfully',
                'data' => $result
            ], 201);
            
        } catch (\Throwable $th) {
            return self::ExceptionResponse($th, 'SetElement:action');
        }
    }
    
    private function getRequestData(): array
    {
        return [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? ''
        ];
    }
}
```

### 1.4 Crear la Clase de Comunicación en Infrastructure (⭐ CLAVE)

```bash
# Crear directorio de comunicación
mkdir -p Backoffice/src/Modules/MiModulo/Infrastructure/Communication
```

```php
// Backoffice/src/Modules/MiModulo/Infrastructure/Communication/ModuleCommunication.php
<?php #GesPrender Core Framework
declare(strict_types=1);
namespace Backoffice\Modules\MiModulo\Infrastructure\Communication;

use GesPrender\Communication\ModuleCommunicationInterface;
use GesPrender\Communication\ModuleEventDispatcher;
use GesPrender\Communication\ModuleServiceRegistry;
use GesPrender\Communication\ModuleHookSystem;
use Backoffice\Modules\MiModulo\Infrastructure\MiModuloRepository;

class ModuleCommunication implements ModuleCommunicationInterface
{
    private MiModuloRepository $repository;
    
    public function __construct()
    {
        $this->repository = new MiModuloRepository();
    }
    
    // 🔧 SERVICIOS: Qué funcionalidades expone tu módulo
    public function registerServices(ModuleServiceRegistry $registry): void
    {
        // Exponer tu repositorio para que otros módulos lo usen
        $registry->register('mimodulo.repository', 'mimodulo', function($container) {
            return $this->repository;
        });
        
        // Exponer servicios específicos
        $registry->register('mimodulo.processor', 'mimodulo', function($container) {
            return new MiModuloProcessor($this->repository);
        });
    }
    
         // 📡 EVENTOS: Qué eventos escucha tu módulo
     public function registerEventListeners(ModuleEventDispatcher $dispatcher): void
     {
         // Escuchar cuando se crea un cliente (evento de otro módulo)
         $dispatcher->listen(\Backoffice\Modules\Clients\Infrastructure\Events\ClientCreatedEvent::class, [$this, 'handleClientCreated']);
         
         // Escuchar eventos de pago (eventos globales de src/Events/ si existen)
         $dispatcher->listen(\GesPrender\Events\PaymentConfirmedEvent::class, [$this, 'handlePaymentConfirmed']);
         
         // 💡 TIP: Para eventos de otros módulos específicos, usar sus namespaces completos
         // $dispatcher->listen(\Backoffice\Modules\Whatsapp\Infrastructure\Events\MessageSentEvent::class, [$this, 'handleMessageSent']);
     }
    
    // 🪝 HOOKS: Puntos de extensión para otros módulos
    public function registerHooks(ModuleHookSystem $hooks): void
    {
        // Permitir que otros modifiquen datos antes de procesar
        $hooks->addFilter('mimodulo.before_process', [$this, 'validateData'], 5);
        
        // Permitir acciones después de procesar
        $hooks->addAction('mimodulo.after_process', [$this, 'logActivity'], 10);
    }
    
    // 📤 EVENTOS: Qué eventos puede disparar tu módulo
    public function getDispatchableEvents(): array
    {
        return [
            'MiModuloCreatedEvent',
            'MiModuloUpdatedEvent',
            'MiModuloProcessedEvent'
        ];
    }
    
    // ℹ️  INFO: Información del módulo
    public function getModuleInfo(): array
    {
        return [
            'name' => 'MiModulo',
            'version' => '1.0.0',
            'description' => 'Descripción de qué hace tu módulo',
            'namespace' => 'Backoffice\Modules\MiModulo',
            'path' => 'Backoffice/src/Modules/MiModulo',
            'dependencies' => ['clients'], // Si depende de otros módulos
            'services_exposed' => [
                'mimodulo.repository',
                'mimodulo.processor'
            ]
        ];
    }
    
    // 🚀 INICIO: Se ejecuta cuando se carga el módulo
    public function boot(): void
    {
        // Inicialización del módulo
        do_action('mimodulo.module_loaded');
    }
    
    // 🔚 CIERRE: Se ejecuta cuando se desactiva el módulo
    public function shutdown(): void
    {
        // Limpieza cuando se desactiva el módulo
        do_action('mimodulo.module_shutdown');
    }
    
    // 🎯 HANDLERS: Métodos que responden a eventos
    
         /**
      * Responde cuando se crea un cliente
      */
     public function handleClientCreated(\Backoffice\Modules\Clients\Infrastructure\Events\ClientCreatedEvent $event): void
    {
        $client = $event->getClient();
        
        // Tu lógica personalizada aquí
        error_log("MiModulo: Nuevo cliente creado - " . $client['name']);
        
        // Ejemplo: crear un registro relacionado
        $this->repository->save([
            'client_id' => $client['id'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Responde cuando se confirma un pago
     */
    public function handlePaymentConfirmed(\GesPrender\Events\PaymentConfirmedEvent $event): void
    {
        $studentId = $event->getStudentId();
        $amount = $event->getAmount();
        
        // Tu lógica personalizada aquí
        error_log("MiModulo: Pago confirmado - Student: {$studentId}, Amount: {$amount}");
    }
    
    // 🎛️ FILTROS Y ACCIONES
    
    public function validateData(array $data): array
    {
        // Validar y modificar datos
        return $data;
    }
    
    public function logActivity(array $processedData): void
    {
        // Log de actividad
        error_log("MiModulo: Processed - " . json_encode($processedData));
    }
}

// 🔧 Clases de apoyo
class MiModuloProcessor
{
    private MiModuloRepository $repository;
    
    public function __construct(MiModuloRepository $repository)
    {
        $this->repository = $repository;
    }
    
    public function process(array $data): array
    {
        // Lógica de procesamiento
        return ['processed' => true, 'data' => $data];
    }
}
```

---

## 🚀 **Paso 2: Agregar Comunicación a un Módulo Existente**

### 2.1 Si tu módulo YA EXISTE, solo agrega comunicación:

```php
// En tu caso de uso existente (ej: SetElement.php)
final class SetElement extends MiModuloRepository
{
    use TraitRequest;

    public function __construct() {
        // 🔥 AGREGAR: Inicializar comunicación
        $this->initCommunicationSystem();
        $this->run();
    }

    public function run(): JsonResponse
    {
        try {
            $data = $this->getRequestData();
            
            // 🔥 AGREGAR: Aplicar filtros antes de procesar. Por si quiero que otro modulo pueda modificar $data antes de ejecutar la logica de este endpoint.
            if (function_exists('apply_filters')) {
                $data = apply_filters('mimodulo.before_process', $data);
            }
            
            // $this->save sería la acción de crear en DB. Está hecho así en este ejemplo para resumir el proceso, pero adaptar a como corresponda.
            $result = $this->save($data);
            
            // 🔥 AGREGAR: Disparar evento después de crear
            if (function_exists('dispatch')) {
                dispatch(new MiModuloCreatedEvent('mimodulo', [
                    'item' => $result,
                    'created_by' => $_SESSION['user_id'] ?? 'system'
                ]));
            }
            
            // 🔥 AGREGAR: Ejecutar acciones post-proceso
            if (function_exists('do_action')) {
                do_action('mimodulo.after_process', $result);
            }
            
            return new JsonResponse([
                'status' => true,
                'message' => 'Element created with communication',
                'data' => $result
            ], 201);
            
        } catch (\Throwable $th) {
            return self::ExceptionResponse($th, 'SetElement:action');
        }
    }
    
    // 🔥 AGREGAR: Método para inicializar comunicación
    private function initCommunicationSystem(): void
    {
        if (!function_exists('init_module_communication')) {
            $helpersPath = dirname(__DIR__, 4) . '/src/Communication/ModuleHelpers.php';
            if (file_exists($helpersPath)) {
                require_once $helpersPath;
                init_module_communication(true);
            }
        }
    }
}
```

---

## 🚀 **Paso 3: Crear Eventos Personalizados**

### 3.1 Crear tu archivo de eventos en Infrastructure:

```bash
# Crear directorio de eventos en tu módulo
mkdir -p Backoffice/src/Modules/MiModulo/Infrastructure/Events
```

```php
// Backoffice/src/Modules/MiModulo/Infrastructure/Events/MiModuloEvents.php
<?php #GesPrender Core Framework
declare(strict_types=1);
namespace Backoffice\Modules\MiModulo\Infrastructure\Events;

use GesPrender\Events\ModuleEvent;

class MiModuloCreatedEvent extends ModuleEvent
{
    public function getItem(): array
    {
        return $this->payload['item'];
    }
    
    public function getCreatedBy(): string
    {
        return $this->payload['created_by'] ?? 'system';
    }
}

class MiModuloUpdatedEvent extends ModuleEvent
{
    public function getItem(): array
    {
        return $this->payload['item'];
    }
    
    public function getUpdatedBy(): string
    {
        return $this->payload['updated_by'] ?? 'system';
    }
}

class MiModuloProcessedEvent extends ModuleEvent
{
    public function getProcessedData(): array
    {
        return $this->payload['processed_data'];
    }
    
    public function getProcessingTime(): float
    {
        return $this->payload['processing_time'] ?? 0.0;
    }
 }
 ```

### 3.2 Estructura Recomendada de Events en Infrastructure:

```
Backoffice/src/Modules/MiModulo/Infrastructure/
├── Communication/
│   └── ModuleCommunication.php # Comunicación inter-modular
├── Events/
│   ├── MiModuloEvents.php      # Todas las clases de eventos
│   ├── Events.php              # Archivo central de documentación y helpers
│   └── README.md               # Documentación específica de eventos (opcional)
└── MiModuloRepository.php      # Repository del módulo
```

### 3.3 Archivo Central de Events (recomendado):

```php
// Backoffice/src/Modules/MiModulo/Infrastructure/Events/Events.php
<?php #GesPrender Core Framework
declare(strict_types=1);
namespace Backoffice\Modules\MiModulo\Infrastructure\Events;

/**
 * Archivo central de eventos del módulo MiModulo
 * Facilita la importación y documentación de eventos
 */
class Events
{
    /**
     * Lista de todos los eventos disponibles en este módulo
     */
    public static function getAvailableEvents(): array
    {
        return [
            'MiModuloCreatedEvent' => MiModuloCreatedEvent::class,
            'MiModuloUpdatedEvent' => MiModuloUpdatedEvent::class,
            'MiModuloProcessedEvent' => MiModuloProcessedEvent::class,
        ];
    }
    
    /**
     * Obtener información de un evento específico
     */
    public static function getEventInfo(string $eventClass): array
    {
        $info = [
            'class' => $eventClass,
            'module' => 'mimodulo',
            'namespace' => 'Backoffice\Modules\MiModulo\Infrastructure\Events',
            'description' => '',
            'payload_fields' => []
        ];
        
        switch ($eventClass) {
            case MiModuloCreatedEvent::class:
                $info['description'] = 'Disparado cuando se crea un elemento en MiModulo';
                $info['payload_fields'] = [
                    'item' => 'array - Datos del elemento creado',
                    'created_by' => 'string - Usuario que lo creó'
                ];
                break;
        }
        
        return $info;
    }
}
```

### 3.4 Ventajas de Events en Infrastructure:

✅ **Autonomía del Módulo**: Cada módulo es completamente independiente  
✅ **Organización Clara**: Todos los eventos de un módulo están en su carpeta  
✅ **Namespaces Específicos**: No hay conflictos entre módulos  
✅ **Fácil Mantenimiento**: Eventos y lógica relacionada están juntos  
✅ **Autodocumentación**: Cada módulo documenta sus propios eventos  

---

## 🚀 **Paso 4: Probar la Comunicación**

### 4.1 Script de prueba rápida:

```php
// test_mi_modulo.php
<?php

require_once 'src/Communication/ModuleHelpers.php';
require_once 'src/Communication/ModuleManager.php';

echo "🧪 Probando comunicación de MiModulo...\n";

try {
    // Inicializar sistema
    init_module_communication(true);
    
    // Crear manager y cargar módulos
    $manager = new \GesPrender\Communication\ModuleManager(
        get_module_service_registry(),
        get_module_event_dispatcher(),
        get_module_hook_system(),
        true
    );
    
    $loadedModules = $manager->discoverAndLoadModules();
    
    echo "✅ Módulos cargados: " . implode(', ', array_keys($loadedModules)) . "\n";
    
    // Verificar si tu módulo está cargado
    if ($manager->isModuleLoaded('MiModulo')) {
        echo "✅ MiModulo cargado correctamente\n";
        
        // Probar servicio
        if (has_service('mimodulo.repository')) {
            $repo = module_service('mimodulo.repository');
            echo "✅ Servicio mimodulo.repository disponible\n";
        }
        
                 // Disparar evento de prueba
         dispatch(new \Backoffice\Modules\MiModulo\Infrastructure\Events\MiModuloCreatedEvent('mimodulo', [
            'item' => ['id' => 'test123', 'name' => 'Test Item'],
            'created_by' => 'test_user'
        ]));
        
        echo "✅ Evento MiModuloCreatedEvent disparado\n";
        
    } else {
        echo "❌ MiModulo NO cargado\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
```

### 4.2 Ejecutar prueba:

```bash
php test_mi_modulo.php
```

---

## 🚀 **Paso 5: Usar Servicios de Otros Módulos**

### 5.1 En cualquier caso de uso, usar servicios externos:

```php
public function run(): JsonResponse
{
    try {
        // 🔥 USAR SERVICIO DE WHATSAPP
        if (has_service('whatsapp.sender')) {
            $whatsappSender = module_service('whatsapp.sender');
            $whatsappSender->sendMessage('+34666123456', 'Hola desde MiModulo!');
        }
        
        // 🔥 USAR SERVICIO DE CLIENTS
        if (has_service('clients.repository')) {
            $clientsRepo = module_service('clients.repository');
            // $clients = $clientsRepo->findAll();
        }
        
                 // 🔥 DISPARAR EVENTO QUE OTROS ESCUCHEN
         dispatch(new \Backoffice\Modules\MiModulo\Infrastructure\Events\MiModuloProcessedEvent('mimodulo', [
             'processed_data' => ['status' => 'completed'],
             'processing_time' => 2.5
         ]));
        
        return new JsonResponse(['status' => true]);
        
    } catch (\Throwable $th) {
        return self::ExceptionResponse($th, 'MiModulo:action');
    }
}
```

---

## 🚀 **Paso 6: Usar Hooks para Extensibilidad**

### 6.1 Permitir que otros módulos modifiquen tu comportamiento:

```php
public function processItem(array $data): array
{
    // 🪝 Permitir que otros módulos modifiquen los datos
    $data = apply_filters('mimodulo.before_process', $data);
    
    // Tu lógica de procesamiento
    $result = $this->doProcessing($data);
    
    // 🪝 Permitir que otros módulos modifiquen el resultado
    $result = apply_filters('mimodulo.after_process', $result, $data);
    
    // 🎬 Ejecutar acciones que otros módulos pueden escuchar
    do_action('mimodulo.item_processed', $result, $data);
    
    return $result;
}
```

### 6.2 Desde otros módulos, conectarse a tus hooks:

```php
// En otro módulo
public function registerHooks(ModuleHookSystem $hooks): void
{
    // Modificar datos antes del procesamiento de MiModulo
    $hooks->addFilter('mimodulo.before_process', function($data) {
        $data['enhanced'] = true;
        return $data;
    });
    
    // Reaccionar cuando MiModulo procesa algo
    $hooks->addAction('mimodulo.item_processed', function($result, $originalData) {
        error_log("OtroModulo: MiModulo procesó algo - " . json_encode($result));
    });
}
```

---

## 🔗 **Comunicación Entre Módulos - Importación de Eventos**

### 🎯 **Regla de Oro: Eventos en Infrastructure**

**✅ Estructura correcta:**
```
Backoffice/src/Modules/[Modulo]/Infrastructure/Events/
```

**❌ NO usar src/Events/ para eventos específicos de módulos**

### 💡 **Cómo Importar Eventos de Otros Módulos**

#### Opción 1: Import directo (recomendado)

```php
// En tu Infrastructure/Communication/ModuleCommunication.php
use Backoffice\Modules\Clients\Infrastructure\Events\ClientCreatedEvent;
use Backoffice\Modules\Whatsapp\Infrastructure\Events\MessageSentEvent;

public function registerEventListeners(ModuleEventDispatcher $dispatcher): void
{
    // Escuchar eventos de otros módulos
    $dispatcher->listen(ClientCreatedEvent::class, [$this, 'handleClientCreated']);
    $dispatcher->listen(MessageSentEvent::class, [$this, 'handleMessageSent']);
}
```

#### Opción 2: Namespace completo (para casos específicos)

```php
public function registerEventListeners(ModuleEventDispatcher $dispatcher): void
{
    // Usar namespace completo sin import
    $dispatcher->listen(\Backoffice\Modules\Clients\Infrastructure\Events\ClientCreatedEvent::class, [$this, 'handler']);
}
```

### 📚 **Descubrimiento de Eventos de Otros Módulos**

```php
// Obtener eventos disponibles de un módulo específico
$clientEvents = \Backoffice\Modules\Clients\Infrastructure\Events\Events::getAvailableEvents();
$whatsappEvents = \Backoffice\Modules\Whatsapp\Infrastructure\Events\Events::getAvailableEvents();

foreach ($clientEvents as $name => $class) {
    echo "Evento disponible: {$name} -> {$class}\n";
}

// Obtener información detallada de un evento
$eventInfo = \Backoffice\Modules\Clients\Infrastructure\Events\Events::getEventInfo(
    \Backoffice\Modules\Clients\Infrastructure\Events\ClientCreatedEvent::class
);

echo "Descripción: " . $eventInfo['description'] . "\n";
echo "Payload: " . json_encode($eventInfo['payload_fields']) . "\n";
```

### ⚡ **Buenas Prácticas para Eventos entre Módulos**

#### ✅ **DO - Hacer:**

```php
// 1. Usar namespaces específicos del módulo
use Backoffice\Modules\Clients\Infrastructure\Events\ClientCreatedEvent;

// 2. Verificar si el evento existe antes de usarlo
if (class_exists('\Backoffice\Modules\Clients\Infrastructure\Events\ClientCreatedEvent')) {
    $dispatcher->listen(ClientCreatedEvent::class, [$this, 'handler']);
}

// 3. Manejar errores si el módulo origen no está disponible
public function handleClientCreated($event): void
{
    if (!$event instanceof ClientCreatedEvent) {
        error_log("Evento inesperado recibido");
        return;
    }
    
    $client = $event->getClient();
    // Tu lógica aquí
}
```

#### ❌ **DON'T - No hacer:**

```php
// ❌ No usar eventos de src/Events/ para lógica específica de módulos
use GesPrender\Events\ClientCreatedEvent; // MALO

// ❌ No asumir que otros módulos están disponibles
dispatch(new SomeOtherModuleEvent()); // Sin verificar

// ❌ No hardcodear dependencias de módulos
$this->requiresModules = ['clients', 'whatsapp']; // MALO, usar verificación dinámica
```

### 🔍 **Verificación Dinámica de Módulos y Eventos**

```php
// En tu ModuleCommunication.php
public function registerEventListeners(ModuleEventDispatcher $dispatcher): void
{
    // Verificar si el módulo Clients está disponible
    if (class_exists('\Backoffice\Modules\Clients\Infrastructure\Events\ClientCreatedEvent')) {
        $dispatcher->listen(
            \Backoffice\Modules\Clients\Infrastructure\Events\ClientCreatedEvent::class, 
            [$this, 'handleClientCreated']
        );
        
        error_log("MiModulo: Registrado listener para ClientCreatedEvent");
    } else {
        error_log("MiModulo: Módulo Clients no disponible, saltando registro de eventos");
    }
    
    // Lo mismo para WhatsApp
    if (class_exists('\Backoffice\Modules\Whatsapp\Infrastructure\Events\MessageSentEvent')) {
        $dispatcher->listen(
            \Backoffice\Modules\Whatsapp\Infrastructure\Events\MessageSentEvent::class,
            [$this, 'handleMessageSent']
        );
    }
}
```

### 📖 **Documentar Dependencias de tu Módulo**

```php
public function getModuleInfo(): array
{
    return [
        'name' => 'MiModulo',
        'version' => '1.0.0',
        'description' => 'Descripción de tu módulo',
        
        // 🔥 IMPORTANTE: Documentar qué módulos/eventos usa
        'event_dependencies' => [
            'clients' => [
                'events' => ['ClientCreatedEvent', 'ClientUpdatedEvent'],
                'required' => false // o true si es obligatorio
            ],
            'whatsapp' => [
                'events' => ['MessageSentEvent'],
                'required' => false
            ]
        ],
        
        // Información para el auto-discovery
        'listens_to' => [
            \Backoffice\Modules\Clients\Infrastructure\Events\ClientCreatedEvent::class,
            \Backoffice\Modules\Whatsapp\Infrastructure\Events\MessageSentEvent::class,
        ],
        
        'dispatches' => [
            \Backoffice\Modules\MiModulo\Infrastructure\Events\MiModuloCreatedEvent::class,
        ]
    ];
}
```

---

## 🔧 **Troubleshooting**

### ❌ "No se encuentra el módulo"

**Problema:** El auto-discovery no encuentra tu módulo.

**Solución:**
1. ✅ Verifica que `ModuleCommunication.php` esté en `Application/`
2. ✅ Verifica el namespace: `Backoffice\Modules\[TuModulo]\Application`
3. ✅ Ejecuta test: `php test_module_communication.php`

### ❌ "Function dispatch not found"

**Problema:** El sistema de comunicación no está inicializado.

**Solución:**
```php
// Agregar al inicio de tu caso de uso
private function initCommunicationSystem(): void
{
    if (!function_exists('init_module_communication')) {
        $helpersPath = dirname(__DIR__, 4) . '/src/Communication/ModuleHelpers.php';
        if (file_exists($helpersPath)) {
            require_once $helpersPath;
            init_module_communication(true);
        }
    }
}
```

### ❌ "Service not found"

**Problema:** El servicio no está registrado.

**Solución:**
1. ✅ Verifica que `registerServices()` registre el servicio
2. ✅ Usa `has_service('nombre.servicio')` antes de `module_service()`
3. ✅ Verifica que el módulo esté cargado

### ❌ "Event not received"

**Problema:** El evento se dispara pero no llega.

**Solución:**
1. ✅ Verifica que `registerEventListeners()` registre el listener
2. ✅ Verifica que el namespace del evento sea correcto
3. ✅ Usa logging: `error_log("Event received: " . get_class($event))`

---

## 📚 **Ejemplos Prácticos Completos**

### 🎯 **Ejemplo 1: Módulo de Inventario que notifica por WhatsApp**

```php
// Cuando el stock es bajo, enviar WhatsApp automático
public function handleStockUpdate($productId, $newStock): void
{
    if ($newStock < 10) {
        // Disparar evento
        dispatch(new LowStockEvent('inventory', [
            'product_id' => $productId,
            'current_stock' => $newStock,
            'threshold' => 10
        ]));
        
        // O enviar directamente
        if (has_service('whatsapp.sender')) {
            $whatsapp = module_service('whatsapp.sender');
            $whatsapp->sendMessage('+34666123456', "⚠️ Stock bajo: Producto {$productId} solo tiene {$newStock} unidades");
        }
    }
}
```

### 🎯 **Ejemplo 2: Módulo de Auditoría que escucha todo**

```php
// Módulo que escucha todos los eventos para auditoría
public function registerEventListeners(ModuleEventDispatcher $dispatcher): void
{
    $dispatcher->listen(\GesPrender\Events\ClientCreatedEvent::class, [$this, 'auditClientCreated']);
    $dispatcher->listen(\GesPrender\Events\PaymentConfirmedEvent::class, [$this, 'auditPayment']);
    $dispatcher->listen(\GesPrender\Events\MessageSentEvent::class, [$this, 'auditMessage']);
    // ... escuchar todos los eventos importantes
}

public function auditClientCreated(\GesPrender\Events\ClientCreatedEvent $event): void
{
    $this->repository->logAudit([
        'event_type' => 'client_created',
        'module_origin' => $event->getOriginModule(),
        'client_data' => $event->getClient(),
        'timestamp' => $event->timestamp->format('Y-m-d H:i:s'),
        'user_id' => $_SESSION['user_id'] ?? 'system'
    ]);
}
```

### 🎯 **Ejemplo 3: Módulo de Reportes que consume múltiples servicios**

```php
public function generateMonthlyReport(): array
{
    $report = [];
    
    // Obtener datos de clientes
    if (has_service('clients.repository')) {
        $clientsRepo = module_service('clients.repository');
        $report['total_clients'] = count($clientsRepo->findAll());
    }
    
    // Obtener datos de pagos
    if (has_service('payments.repository')) {
        $paymentsRepo = module_service('payments.repository');
        $report['total_revenue'] = $paymentsRepo->getTotalRevenue();
    }
    
    // Obtener estadísticas de WhatsApp
    if (has_service('whatsapp.repository')) {
        $whatsappRepo = module_service('whatsapp.repository');
        $report['messages_sent'] = $whatsappRepo->getMessageCount();
    }
    
    return $report;
}
```

---

## 🎉 **¡Listo para Desarrollar!**

Con esta guía puedes:

✅ **Crear módulos nuevos** con comunicación  
✅ **Conectar módulos existentes** entre sí  
✅ **Usar servicios** de otros módulos  
✅ **Escuchar y disparar eventos**  
✅ **Crear puntos de extensión** con hooks  
✅ **Debuggear problemas** comunes  

**🚀 ¡Tu módulo ya puede comunicarse con todo el ecosistema del framework!**

---

## 📞 **Soporte**

Si tienes problemas:

1. **Ejecuta el test:** `php test_module_communication.php`
2. **Revisa logs:** Busca mensajes en error_log
3. **Verifica estructura:** Los archivos deben estar en las rutas correctas
4. **Consulta ejemplos:** Mira `Clients` y `Whatsapp` como referencia

---

## 🚀 CLI Integrado - Generación Automática

### **Comando Simplificado**

El CLI de GesPrender ahora incluye **automáticamente** el sistema de comunicación en todos los módulos:

```bash
php coreshell make:module MiModulo  # ✅ Incluye comunicación por defecto
```

### **Estructura Generada**

Cada `make:module` crea automáticamente:
- ✅ **Infrastructure/Communication/ModuleCommunication.php** - Configuración completa
- ✅ **Infrastructure/Events/** - Eventos específicos + registro central
- ✅ **Application/SetElementWithCommunication.php** - Ejemplo funcional
- ✅ **Estructura DDD completa** - Application, Domain, Infrastructure, Design

### **Auto-Discovery Compatible**

Los módulos generados son automáticamente detectados por el `ModuleManager` sin configuración adicional.

**Documentación completa del CLI:** Ver `Docs/cli-integration.md`

**¡Happy Coding!** 🎯 