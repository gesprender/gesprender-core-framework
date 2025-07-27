# Sistema de Comunicación Inter-Modular - GesPrender Framework

## Visión General

Diseño de arquitectura para permitir comunicación fluida y desacoplada entre módulos del framework, habilitando funcionalidades como:

- **Clients + WhatsApp**: Envío masivo de mensajes
- **Students + MercadoPago**: Cobros automáticos de cuotas
- **Inventory + Email**: Notificaciones de stock bajo
- **Users + Audit**: Logging automático de acciones

## Arquitectura Propuesta

### 1. Event System (Comunicación Asíncrona)

Sistema de eventos que permite a los módulos comunicarse sin conocerse directamente.

```php
// Event Base
abstract class ModuleEvent
{
    public function __construct(
        public readonly string $moduleOrigin,
        public readonly array $payload,
        public readonly DateTime $timestamp
    ) {}
}

// Eventos específicos
class ClientCreatedEvent extends ModuleEvent
{
    public function getClient(): array 
    {
        return $this->payload['client'];
    }
}

class PaymentDueEvent extends ModuleEvent 
{
    public function getStudent(): array 
    {
        return $this->payload['student'];
    }
    
    public function getAmount(): float 
    {
        return $this->payload['amount'];
    }
}
```

### 2. Event Dispatcher

```php
class ModuleEventDispatcher
{
    private array $listeners = [];
    private LoggerInterface $logger;
    
    public function listen(string $eventClass, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventClass][] = [
            'callback' => $listener,
            'priority' => $priority
        ];
        
        // Ordenar por prioridad
        usort($this->listeners[$eventClass], fn($a, $b) => $b['priority'] <=> $a['priority']);
    }
    
    public function dispatch(ModuleEvent $event): void
    {
        $eventClass = get_class($event);
        
        if (!isset($this->listeners[$eventClass])) {
            return;
        }
        
        foreach ($this->listeners[$eventClass] as $listener) {
            try {
                call_user_func($listener['callback'], $event);
            } catch (Exception $e) {
                $this->logger->error("Event listener failed", [
                    'event' => $eventClass,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
```

### 3. Service Registry (Exposición de Servicios)

Permite que módulos expongan servicios para uso de otros módulos.

```php
class ModuleServiceRegistry
{
    private array $services = [];
    private ServiceContainer $container;
    
    public function register(string $serviceName, string $moduleOrigin, callable $factory): void
    {
        $this->services[$serviceName] = [
            'module' => $moduleOrigin,
            'factory' => $factory,
            'instance' => null
        ];
    }
    
    public function get(string $serviceName): ?object
    {
        if (!isset($this->services[$serviceName])) {
            throw new ServiceNotFoundException($serviceName);
        }
        
        $service = &$this->services[$serviceName];
        
        if ($service['instance'] === null) {
            $service['instance'] = call_user_func($service['factory'], $this->container);
        }
        
        return $service['instance'];
    }
    
    public function has(string $serviceName): bool
    {
        return isset($this->services[$serviceName]);
    }
    
    public function getAvailableServices(): array
    {
        return array_keys($this->services);
    }
}
```

### 4. Hooks System (Extensibilidad)

Sistema de hooks similar a WordPress para permitir que módulos se "enganchen" a puntos específicos.

```php
class ModuleHookSystem
{
    private array $hooks = [];
    
    public function addAction(string $hookName, callable $callback, int $priority = 10): void
    {
        $this->hooks[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
        
        usort($this->hooks[$hookName], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }
    
    public function doAction(string $hookName, ...$args): void
    {
        if (!isset($this->hooks[$hookName])) {
            return;
        }
        
        foreach ($this->hooks[$hookName] as $hook) {
            call_user_func_array($hook['callback'], $args);
        }
    }
    
    public function addFilter(string $filterName, callable $callback, int $priority = 10): void
    {
        $this->addAction($filterName, $callback, $priority);
    }
    
    public function applyFilters(string $filterName, $value, ...$args)
    {
        if (!isset($this->hooks[$filterName])) {
            return $value;
        }
        
        foreach ($this->hooks[$filterName] as $hook) {
            $value = call_user_func_array($hook['callback'], array_merge([$value], $args));
        }
        
        return $value;
    }
}
```

### 5. Module Communication Interface

Interfaz estándar que deben implementar los módulos para comunicarse.

```php
interface ModuleCommunicationInterface
{
    /**
     * Registra los servicios que expone el módulo
     */
    public function registerServices(ModuleServiceRegistry $registry): void;
    
    /**
     * Registra los event listeners del módulo
     */
    public function registerEventListeners(ModuleEventDispatcher $dispatcher): void;
    
    /**
     * Registra hooks y filters del módulo
     */
    public function registerHooks(ModuleHookSystem $hooks): void;
    
    /**
     * Retorna los eventos que puede dispatchar el módulo
     */
    public function getDispatchableEvents(): array;
}
```

## Ejemplos Prácticos

### Ejemplo 1: Clients + WhatsApp

#### Módulo Clients

```php
class ClientsModule implements ModuleCommunicationInterface
{
    public function registerServices(ModuleServiceRegistry $registry): void
    {
        $registry->register('clients.repository', 'clients', function($container) {
            return $container->get(ClientRepository::class);
        });
        
        $registry->register('clients.bulk_operations', 'clients', function($container) {
            return new ClientBulkOperations($container->get(ClientRepository::class));
        });
    }
    
    public function registerEventListeners(ModuleEventDispatcher $dispatcher): void
    {
        // Este módulo no escucha eventos, solo los emite
    }
    
    public function registerHooks(ModuleHookSystem $hooks): void
    {
        // Permite que otros módulos modifiquen la lista de clientes
        $hooks->addFilter('clients.before_bulk_operation', function($clients, $operation) {
            // Otros módulos pueden filtrar la lista
            return $clients;
        });
    }
    
    public function getDispatchableEvents(): array
    {
        return [
            ClientCreatedEvent::class,
            ClientUpdatedEvent::class,
            ClientDeletedEvent::class,
            BulkOperationRequestedEvent::class
        ];
    }
}

// Servicio de operaciones masivas
class ClientBulkOperations
{
    public function __construct(
        private ClientRepository $repository,
        private ModuleEventDispatcher $dispatcher
    ) {}
    
    public function sendBulkMessage(array $filters, string $message): void
    {
        $clients = $this->repository->findByFilters($filters);
        
        // Aplicar filtros de otros módulos
        $clients = apply_filters('clients.before_bulk_operation', $clients, 'message');
        
        // Disparar evento para que WhatsApp module lo capture
        $this->dispatcher->dispatch(new BulkMessageRequestedEvent(
            'clients',
            [
                'recipients' => $clients,
                'message' => $message,
                'type' => 'whatsapp'
            ]
        ));
    }
}
```

#### Módulo WhatsApp

```php
class WhatsAppModule implements ModuleCommunicationInterface
{
    public function registerServices(ModuleServiceRegistry $registry): void
    {
        $registry->register('whatsapp.sender', 'whatsapp', function($container) {
            return new WhatsAppSender($container->get('whatsapp.api_client'));
        });
    }
    
    public function registerEventListeners(ModuleEventDispatcher $dispatcher): void
    {
        // Escucha eventos de otros módulos
        $dispatcher->listen(BulkMessageRequestedEvent::class, [$this, 'handleBulkMessage']);
        $dispatcher->listen(ClientCreatedEvent::class, [$this, 'sendWelcomeMessage']);
    }
    
    public function registerHooks(ModuleHookSystem $hooks): void
    {
        // Permite personalizar mensajes
        $hooks->addFilter('whatsapp.message_template', function($template, $type) {
            return $template;
        });
    }
    
    public function handleBulkMessage(BulkMessageRequestedEvent $event): void
    {
        if ($event->payload['type'] !== 'whatsapp') {
            return; // No es para nosotros
        }
        
        $recipients = $event->payload['recipients'];
        $message = $event->payload['message'];
        
        // Aplicar filtros de personalización
        $message = apply_filters('whatsapp.message_template', $message, 'bulk');
        
        $whatsappSender = app('whatsapp.sender');
        
        foreach ($recipients as $client) {
            if (!empty($client['phone'])) {
                $whatsappSender->sendMessage($client['phone'], $message);
            }
        }
        
        // Disparar evento de completado
        dispatch(new BulkMessageSentEvent('whatsapp', [
            'total_sent' => count($recipients),
            'message' => $message
        ]));
    }
    
    public function getDispatchableEvents(): array
    {
        return [
            MessageSentEvent::class,
            BulkMessageSentEvent::class,
            MessageFailedEvent::class
        ];
    }
}
```

#### Uso desde Controller

```php
class ClientsController
{
    public function __construct(
        private ClientBulkOperations $bulkOps,
        private ModuleEventDispatcher $dispatcher
    ) {}
    
    #[Route('/api/clients/bulk-message', methods: ['POST'])]
    public function sendBulkMessage(): JsonResponse
    {
        $data = request()->all();
        
        // El módulo Clients maneja la lógica, WhatsApp escucha el evento
        $this->bulkOps->sendBulkMessage(
            $data['filters'], 
            $data['message']
        );
        
        return response()->json(['status' => 'Mensajes enviándose en segundo plano']);
    }
}
```

### Ejemplo 2: Students + MercadoPago

#### Módulo Students

```php
class StudentsModule implements ModuleCommunicationInterface
{
    public function registerServices(ModuleServiceRegistry $registry): void
    {
        $registry->register('students.payment_manager', 'students', function($container) {
            return new StudentPaymentManager($container->get(StudentRepository::class));
        });
    }
    
    public function registerEventListeners(ModuleEventDispatcher $dispatcher): void
    {
        // Escucha cuando se confirma un pago
        $dispatcher->listen(PaymentConfirmedEvent::class, [$this, 'handlePaymentConfirmed']);
    }
    
    public function registerHooks(ModuleHookSystem $hooks): void
    {
        // Hook para generar cobros automáticos mensuales
        $hooks->addAction('cron.monthly', [$this, 'generateMonthlyPayments']);
    }
    
    public function generateMonthlyPayments(): void
    {
        $paymentManager = app('students.payment_manager');
        $students = $paymentManager->getStudentsForMonthlyPayment();
        
        foreach ($students as $student) {
            // Disparar evento para que MercadoPago lo procese
            dispatch(new PaymentDueEvent('students', [
                'student' => $student,
                'amount' => $student['monthly_fee'],
                'concept' => 'Cuota mensual ' . date('F Y'),
                'due_date' => date('Y-m-d', strtotime('+15 days'))
            ]));
        }
    }
    
    public function handlePaymentConfirmed(PaymentConfirmedEvent $event): void
    {
        if ($event->payload['module_origin'] !== 'students') {
            return;
        }
        
        $studentId = $event->payload['student_id'];
        $paymentManager = app('students.payment_manager');
        $paymentManager->markPaymentAsCompleted($studentId, $event->payload['payment_id']);
    }
    
    public function getDispatchableEvents(): array
    {
        return [
            PaymentDueEvent::class,
            StudentEnrolledEvent::class,
            StudentDroppedEvent::class
        ];
    }
}
```

#### Módulo MercadoPago

```php
class MercadoPagoModule implements ModuleCommunicationInterface
{
    public function registerServices(ModuleServiceRegistry $registry): void
    {
        $registry->register('mercadopago.api', 'mercadopago', function($container) {
            return new MercadoPagoAPI($container->get('config.mercadopago.access_token'));
        });
        
        $registry->register('mercadopago.payment_processor', 'mercadopago', function($container) {
            return new PaymentProcessor($container->get('mercadopago.api'));
        });
    }
    
    public function registerEventListeners(ModuleEventDispatcher $dispatcher): void
    {
        $dispatcher->listen(PaymentDueEvent::class, [$this, 'processPayment']);
    }
    
    public function registerHooks(ModuleHookSystem $hooks): void
    {
        // Permite personalizar la configuración de pagos
        $hooks->addFilter('mercadopago.payment_config', function($config, $moduleOrigin) {
            return $config;
        });
    }
    
    public function processPayment(PaymentDueEvent $event): void
    {
        $processor = app('mercadopago.payment_processor');
        
        $paymentConfig = apply_filters('mercadopago.payment_config', [
            'amount' => $event->getAmount(),
            'description' => $event->payload['concept'],
            'external_reference' => $event->moduleOrigin . '_' . $event->payload['student']['id'],
            'notification_url' => url('/api/mercadopago/webhook'),
            'success_url' => url('/payment/success'),
            'failure_url' => url('/payment/failure')
        ], $event->moduleOrigin);
        
        try {
            $payment = $processor->createPayment($paymentConfig);
            
            // Enviar email con link de pago (via Email module)
            dispatch(new SendEmailEvent('mercadopago', [
                'to' => $event->payload['student']['email'],
                'template' => 'payment_due',
                'data' => [
                    'student' => $event->payload['student'],
                    'amount' => $event->getAmount(),
                    'payment_link' => $payment['payment_url'],
                    'due_date' => $event->payload['due_date']
                ]
            ]));
            
        } catch (Exception $e) {
            dispatch(new PaymentFailedEvent('mercadopago', [
                'error' => $e->getMessage(),
                'student_id' => $event->payload['student']['id']
            ]));
        }
    }
    
    #[Route('/api/mercadopago/webhook', methods: ['POST'])]
    public function handleWebhook(): JsonResponse
    {
        $data = request()->all();
        
        if ($data['action'] === 'payment.updated' && $data['status'] === 'approved') {
            // Confirmar pago
            dispatch(new PaymentConfirmedEvent('mercadopago', [
                'payment_id' => $data['payment_id'],
                'student_id' => explode('_', $data['external_reference'])[1],
                'amount' => $data['amount'],
                'module_origin' => explode('_', $data['external_reference'])[0]
            ]));
        }
        
        return response()->json(['status' => 'ok']);
    }
    
    public function getDispatchableEvents(): array
    {
        return [
            PaymentConfirmedEvent::class,
            PaymentFailedEvent::class,
            PaymentPendingEvent::class
        ];
    }
}
```

## Configuración del Sistema

### 1. Registro de Módulos

```php
// config/modules.php
return [
    'clients' => [
        'class' => ClientsModule::class,
        'enabled' => true,
        'dependencies' => []
    ],
    'whatsapp' => [
        'class' => WhatsAppModule::class,
        'enabled' => true,
        'dependencies' => ['clients']
    ],
    'students' => [
        'class' => StudentsModule::class,
        'enabled' => true,
        'dependencies' => []
    ],
    'mercadopago' => [
        'class' => MercadoPagoModule::class,
        'enabled' => true,
        'dependencies' => ['students']
    ]
];
```

### 2. Module Manager

```php
class ModuleManager
{
    private ModuleServiceRegistry $serviceRegistry;
    private ModuleEventDispatcher $eventDispatcher;
    private ModuleHookSystem $hookSystem;
    
    public function __construct(
        ModuleServiceRegistry $serviceRegistry,
        ModuleEventDispatcher $eventDispatcher,
        ModuleHookSystem $hookSystem
    ) {
        $this->serviceRegistry = $serviceRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->hookSystem = $hookSystem;
    }
    
    public function loadModules(array $moduleConfig): void
    {
        // Cargar en orden de dependencias
        $loadOrder = $this->resolveDependencies($moduleConfig);
        
        foreach ($loadOrder as $moduleName) {
            $config = $moduleConfig[$moduleName];
            
            if (!$config['enabled']) {
                continue;
            }
            
            $module = new $config['class'];
            
            if (!$module instanceof ModuleCommunicationInterface) {
                throw new InvalidModuleException($moduleName);
            }
            
            // Registrar servicios, listeners y hooks
            $module->registerServices($this->serviceRegistry);
            $module->registerEventListeners($this->eventDispatcher);
            $module->registerHooks($this->hookSystem);
        }
    }
    
    private function resolveDependencies(array $moduleConfig): array
    {
        // Algoritmo topológico para ordenar por dependencias
        $resolved = [];
        $unresolved = [];
        
        foreach ($moduleConfig as $name => $config) {
            $this->resolveDependency($name, $moduleConfig, $resolved, $unresolved);
        }
        
        return $resolved;
    }
}
```

### 3. Helpers Globales

```php
// Helpers para usar en cualquier parte del código

function dispatch(ModuleEvent $event): void
{
    app(ModuleEventDispatcher::class)->dispatch($event);
}

function listen(string $eventClass, callable $listener, int $priority = 0): void
{
    app(ModuleEventDispatcher::class)->listen($eventClass, $listener, $priority);
}

function add_action(string $hook, callable $callback, int $priority = 10): void
{
    app(ModuleHookSystem::class)->addAction($hook, $callback, $priority);
}

function do_action(string $hook, ...$args): void
{
    app(ModuleHookSystem::class)->doAction($hook, $args);
}

function apply_filters(string $filter, $value, ...$args)
{
    return app(ModuleHookSystem::class)->applyFilters($filter, $value, ...$args);
}

function module_service(string $serviceName): ?object
{
    return app(ModuleServiceRegistry::class)->get($serviceName);
}
```

## Beneficios de la Arquitectura

### 1. **Desacoplamiento Completo**
- Módulos no necesitan conocerse entre sí
- Comunicación a través de eventos y servicios
- Fácil agregar/quitar módulos sin afectar otros

### 2. **Extensibilidad**
- Sistema de hooks permite modificar comportamiento
- Nuevos módulos pueden escuchar eventos existentes
- Filters permiten transformar datos

### 3. **Compatibilidad Symfony**
- Events compatibles con Symfony EventDispatcher
- Service Registry similar a Symfony Container
- Migración gradual posible

### 4. **Testing**
- Eventos se pueden mockear fácilmente
- Servicios inyectables para testing
- Hooks permiten testing de integraciones

### 5. **Escalabilidad**
- Eventos pueden procesarse en background
- Servicios lazy-loaded
- Módulos independientes

## Implementación Gradual

### Fase 1: Event System Base
1. Implementar ModuleEvent base
2. Crear ModuleEventDispatcher
3. Helpers básicos (dispatch, listen)

### Fase 2: Service Registry
1. Implementar ModuleServiceRegistry
2. Integrar con Container existente
3. Helper module_service()

### Fase 3: Hooks System
1. Implementar ModuleHookSystem
2. Helpers add_action, do_action, apply_filters
3. Integración con eventos

### Fase 4: Module Manager
1. Crear ModuleManager
2. Sistema de dependencias
3. Auto-discovery de módulos

### Fase 5: Ejemplos Prácticos
1. Implementar Clients + WhatsApp
2. Implementar Students + MercadoPago
3. Documentación y testing

Esta arquitectura proporciona una base sólida para comunicación modular manteniendo la compatibilidad con Symfony y permitiendo escalabilidad futura. 