<?php

/**
 * Script de prueba para el sistema de comunicación entre módulos
 * Adaptado a la estructura real: Backoffice/src/Modules/
 */

// Incluir las dependencias del framework
require_once 'src/Communication/ModuleHelpers.php';
require_once 'src/Communication/ModuleManager.php';

echo "🚀 Inicializando sistema de comunicación entre módulos (estructura real)...\n\n";

try {
    // Inicializar el sistema de comunicación
    init_module_communication(true); // habilitar logging
    
    // Crear el module manager
    $moduleManager = new \GesPrender\Communication\ModuleManager(
        get_module_service_registry(),
        get_module_event_dispatcher(),
        get_module_hook_system(),
        true // logging habilitado
    );
    
    echo "✅ Sistema de comunicación inicializado\n\n";
    
    // Auto-descubrir y cargar módulos desde Backoffice/src/Modules/
    echo "🔍 Buscando módulos en Backoffice/src/Modules/...\n";
    $loadedModules = $moduleManager->discoverAndLoadModules();
    
    if (empty($loadedModules)) {
        echo "⚠️  No se encontraron módulos con comunicación implementada\n";
        echo "   Los módulos deben tener un archivo 'ModuleCommunication.php' en su carpeta Application/\n\n";
    } else {
        echo "✅ Módulos cargados: " . implode(', ', array_keys($loadedModules)) . "\n\n";
    }
    
    // Mostrar información de módulos cargados
    echo "📊 Información de módulos cargados:\n";
    echo "═══════════════════════════════════════\n";
    
    foreach ($loadedModules as $name => $data) {
        $info = $data['info'];
        echo "🔸 {$name}:\n";
        echo "   • Versión: {$info['version']}\n";
        echo "   • Servicios: " . count($info['services_exposed']) . "\n";
        echo "   • Eventos: " . count($info['events_dispatched']) . "\n";
        echo "   • Hooks: " . count($info['hooks_provided']) . "\n";
        echo "   • Dependencias: " . implode(', ', $info['dependencies']) . "\n";
        echo "   • Cargado: {$data['loaded_at']}\n\n";
    }
    
    // Test de comunicación si hay módulos cargados
    if (count($loadedModules) >= 2) {
        echo "🧪 Probando comunicación entre módulos...\n";
        echo "═══════════════════════════════════════════════\n\n";
        
        // Test 1: Verificar servicios disponibles
        echo "🔧 Servicios disponibles:\n";
        $registry = get_module_service_registry();
        $services = $registry->getAvailableServices();
        
        foreach ($services as $serviceName) {
            echo "   • {$serviceName}\n";
        }
        echo "\n";
        
        // Test 2: Disparar evento de prueba si existe el módulo Clients
        if ($moduleManager->isModuleLoaded('Clients')) {
            echo "📡 Disparando evento de cliente creado...\n";
            
            // Simular creación de cliente
            $clientData = [
                'name' => 'Cliente de Prueba',
                'email' => 'test@example.com',
                'phone' => '+34666123456',
                'company' => 'Empresa Test'
            ];
            
                         dispatch(new \Backoffice\Modules\Clients\Infrastructure\Events\ClientCreatedEvent('clients', ['client' => $clientData]));
            
            echo "✅ Evento ClientCreatedEvent disparado\n";
            echo "   Otros módulos deberían haber respondido automáticamente\n\n";
        }
        
        // Test 3: Probar hooks
        echo "🪝 Probando sistema de hooks...\n";
        
        // Agregar hook de prueba
        add_filter('test.message', function($message) {
            return "🎉 " . $message;
        });
        
        $originalMessage = "Mensaje de prueba";
        $filteredMessage = apply_filters('test.message', $originalMessage);
        
        echo "   Original: {$originalMessage}\n";
        echo "   Filtrado: {$filteredMessage}\n\n";
        
        // Test 4: Probar acciones
        echo "🎬 Probando acciones...\n";
        
        $actionExecuted = false;
        add_action('test.action', function() use (&$actionExecuted) {
            $actionExecuted = true;
            echo "   ✅ Acción de prueba ejecutada\n";
        });
        
        do_action('test.action');
        
        if (!$actionExecuted) {
            echo "   ❌ La acción no se ejecutó\n";
        }
        echo "\n";
    }
    
    // Estadísticas finales
    echo "📈 Estadísticas del sistema:\n";
    echo "═══════════════════════════════════\n";
    
    $stats = get_module_communication_stats();
    
    echo "🔧 Servicios:\n";
    echo "   • Total: {$stats['services']['total_services']}\n";
    echo "   • Módulos: {$stats['services']['total_modules']}\n";
    echo "   • Singleton: {$stats['services']['singleton_services']}\n";
    echo "   • Cacheados: {$stats['services']['cached_instances']}\n\n";
    
    echo "📡 Eventos:\n";
    echo "   • Registrados: {$stats['events']['total_events']}\n";
    echo "   • Listeners: {$stats['events']['total_listeners']}\n";
    echo "   • Async: " . ($stats['events']['async_enabled'] ? 'Sí' : 'No') . "\n\n";
    
    echo "🪝 Hooks:\n";
    echo "   • Total hooks: {$stats['hooks']['total_hooks']}\n";
    echo "   • Total filtros: {$stats['hooks']['total_filters']}\n";
    echo "   • Acciones: {$stats['hooks']['total_actions']}\n";
    echo "   • Filter callbacks: {$stats['hooks']['total_filter_callbacks']}\n\n";
    
    // Información del module manager
    $managerStats = $moduleManager->getStats();
    echo "📊 Module Manager:\n";
    echo "   • Módulos cargados: {$managerStats['total_modules']}\n\n";
    
    foreach ($managerStats['modules'] as $name => $moduleStats) {
        echo "   🔸 {$name}:\n";
        echo "      • Servicios: {$moduleStats['services_count']}\n";
        echo "      • Eventos: {$moduleStats['events_count']}\n";
        echo "      • Hooks: {$moduleStats['hooks_count']}\n";
        echo "      • Dependencias: " . implode(', ', $moduleStats['dependencies']) . "\n";
    }
    
    echo "\n🎉 ¡Prueba completada exitosamente!\n\n";
    
    echo "💡 Próximos pasos:\n";
    echo "═══════════════════\n";
    echo "1. Implementar los métodos faltantes en los repositorios\n";
    echo "2. Crear casos de uso reales en los módulos\n";
    echo "3. Integrar con el Kernel.php del framework\n";
    echo "4. Agregar más módulos con comunicación\n";
    echo "5. Implementar APIs reales de WhatsApp, pagos, etc.\n\n";
    
    if (count($loadedModules) === 0) {
        echo "📝 Para probar el sistema:\n";
        echo "   1. Los archivos ModuleCommunication.php ya están creados en:\n";
        echo "      • Backoffice/src/Modules/Clients/Infrastructure/Communication/ModuleCommunication.php\n";
        echo "      • Backoffice/src/Modules/Whatsapp/Infrastructure/Communication/ModuleCommunication.php\n\n";
        echo "   2. Ejecuta este script nuevamente para ver la comunicación en acción\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error durante la prueba: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} 