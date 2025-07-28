# GesPrender Core Framework - Documentación Completa

## Tabla de Contenido

1. [Visión General](#visión-general)
2. [Arquitectura del Framework](#arquitectura-del-framework)
3. [Estructura de Directorios](#estructura-de-directorios)
4. [Componentes Principales](#componentes-principales)
5. [Sistema de Ruteo](#sistema-de-ruteo)
6. [Capa de Datos](#capa-de-datos)
7. [Frontend - Backoffice](#frontend---backoffice)
8. [Configuración y Variables de Entorno](#configuración-y-variables-de-entorno)
9. [Docker y Despliegue](#docker-y-despliegue)
10. [CLI - CoreShell](#cli---coreshell)
11. [Patrones de Diseño Implementados](#patrones-de-diseño-implementados)
12. [Sistema Multi-Tenant](#sistema-multi-tenant)
13. [Seguridad y Middlewares](#seguridad-y-middlewares)
14. [Testing](#testing)
15. [Áreas de Mejora Identificadas](#áreas-de-mejora-identificadas)
16. [Propuestas de Evolución](#propuestas-de-evolución)

---

## Visión General

El GesPrender Core Framework es un **framework PHP minimalista** diseñado para desarrollo rápido de aplicaciones web. Su filosofía principal es proporcionar las herramientas esenciales sin la sobrecarga de frameworks pesados como Symfony o Laravel.

### Características Principales

- **Desarrollo Rápido**: Enfoque en productividad inmediata
- **Multi-Tenant**: Soporte nativo para múltiples clientes/dominios
- **Backend + Frontend Integrado**: Incluye Backoffice en Astro
- **Arquitectura Modular**: Componentes independientes y reutilizables
- **Zero-Configuration**: Funciona sin configuración compleja inicial
- **Docker Ready**: Entorno de desarrollo containerizado

### Tecnologías Core

- **Backend**: PHP 7.4+/8.1+
- **Frontend**: Astro + React
- **Base de Datos**: MySQL, SQLite, Redis
- **Contenedores**: Docker + Docker Compose
- **CLI**: CoreShell (CLI personalizado)

---

## Arquitectura del Framework

### Filosofía Arquitectónica

El framework sigue una **arquitectura modular híbrida** que combina:

```
┌─────────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                       │
│  ┌─────────────────┐    ┌─────────────────────────────────┐ │
│  │   Backoffice    │    │        API Endpoints           │ │
│  │   (Astro+React) │    │     (JSON Responses)           │ │
│  └─────────────────┘    └─────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│                   APPLICATION LAYER                         │
│  ┌─────────────────┐    ┌─────────────────────────────────┐ │
│  │   Controllers   │    │        Services                │ │
│  │   (Modules)     │    │  (Helper, Validation, etc.)   │ │
│  └─────────────────┘    └─────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│                    DOMAIN LAYER                             │
│  ┌─────────────────┐    ┌─────────────────────────────────┐ │
│  │   Repositories  │    │        Entities/Models         │ │
│  │   (Data Access) │    │      (Business Logic)         │ │
│  └─────────────────┘    └─────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│                INFRASTRUCTURE LAYER                         │
│  ┌─────────────────┐    ┌─────────────────────────────────┐ │
│  │   Storage       │    │        External Services       │ │
│  │ (MySQL/Redis)   │    │    (Email, PDF, Upload)       │ │
│  └─────────────────┘    └─────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Flujo de Ejecución

1. **Inicialización**: Kernel bootstrap
2. **Configuración**: Carga .env y configuraciones
3. **Enrutado**: Sistema de rutas basado en anotaciones
4. **Procesamiento**: Controladores y servicios
5. **Respuesta**: JSON o renderizado

---

## Estructura de Directorios

```
gesprender-core-framework/
├── api/                          # Punto de entrada API
│   └── index.php                 # Bootstrap de la API
├── Backoffice/                   # Frontend Astro
│   ├── src/
│   │   ├── Modules/             # Módulos del Backoffice
│   │   ├── components/          # Componentes React
│   │   ├── layouts/             # Layouts Astro
│   │   └── pages/               # Páginas Astro
│   ├── public/                  # Assets estáticos
│   ├── package.json             # Dependencias Node.js
│   └── astro.config.mjs         # Configuración Astro
├── config/                       # Configuración del Framework
│   ├── Kernel.php               # Núcleo del framework
│   ├── defines.php              # Constantes globales
│   ├── alias.php                # Alias de clases
│   └── scripts/                 # Scripts de instalación
├── src/                          # Core del Framework
│   ├── Classes/                 # Clases utilitarias
│   │   ├── Context.php          # Manejo de contexto
│   │   ├── Email.php            # Servicio de email
│   │   ├── Image.php            # Procesamiento de imágenes
│   │   ├── Logger.php           # Sistema de logging
│   │   ├── PDF.php              # Generación de PDFs
│   │   └── Upload.php           # Subida de archivos
│   ├── Contracts/               # Interfaces y contratos
│   │   ├── Exceptions/          # Excepciones personalizadas
│   │   ├── Traits/              # Traits reutilizables
│   │   ├── CoreAbstract.php     # Clase base abstracta
│   │   ├── RepositoryAbstract.php
│   │   ├── RepositoryInterface.php
│   │   └── RequestControllerInterface.php
│   ├── Services/                # Servicios del framework
│   │   ├── Helper.php           # Funciones auxiliares
│   │   ├── JsonResponse.php     # Respuestas JSON
│   │   ├── Request.php          # Manejo de requests
│   │   ├── Response.php         # Manejo de responses
│   │   └── Validations.php      # Validaciones
│   ├── Storage/                 # Capa de persistencia
│   │   ├── MySQL.php            # Adaptador MySQL
│   │   ├── SQLite.php           # Adaptador SQLite
│   │   └── Redis.php            # Adaptador Redis
│   └── Cron/                    # Tareas programadas
├── Sites/                        # Sitios multi-tenant
├── Docker/                       # Configuración Docker
│   ├── Dockerfile               # Imagen PHP
│   ├── nginx.conf               # Configuración Nginx
│   └── php.ini                  # Configuración PHP
├── Logs/                         # Archivos de log
├── upload/                       # Archivos subidos
├── vendor/                       # Dependencias Composer
├── docker-compose.yml            # Orquestación Docker
├── composer.json                 # Dependencias PHP
├── package.json                  # Scripts NPM
├── coreshell                     # CLI del framework
└── Docs/                         # Documentación
```

---

## Componentes Principales

### 1. Kernel (`config/Kernel.php`)

El **Kernel** es el corazón del framework. Maneja:

- **Bootstrap de la aplicación**
- **Carga de configuraciones (.env)**
- **Inicialización de sesiones**
- **Autodescubrimiento de controladores**
- **Sistema de enrutado**
- **Manejo de errores 404**

```php
// Flujo principal del Kernel
public function run(): void
{
    Response::setHeaders();           // Configurar headers HTTP
    $this->getDotenv();              // Cargar variables de entorno
    // Control de errores en producción
    if ($_ENV['MODE'] == 'prod') error_reporting(E_ALL & ~E_WARNING);
    
    // Inicializar sesión
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    require 'defines.php';           // Cargar constantes
    $this->leadFiles();              // Autocargar controladores
    $this->endpointNotFound();       // Manejar 404s
    $this->Welcome();                // Respuesta por defecto
}
```

### 2. Sistema de Request (`src/Services/Request.php`)

Maneja todas las solicitudes HTTP con:

- **Extracción automática de headers**
- **Procesamiento de query params y body**
- **Sistema de enrutado con `Route()` y `On()`**
- **Soporte para middleware de seguridad**

```php
// Ejemplo de uso del sistema de ruteo
Request::Route('/users', function() {
    // Lógica del endpoint
}, true); // true = usar middleware de seguridad
```

### 3. Capa de Datos (`src/Storage/`)

Implementa el **patrón Repository** con adaptadores para:

#### MySQL (`MySQL.php`)
- **Conexión con soporte multi-tenant**
- **Query builder básico**
- **Manejo de transacciones**
- **Prepared statements automáticos**

#### Redis (`Redis.php`)
- **Conexión y configuración**
- **Operaciones básicas de cache**
- **TTL automático**

#### SQLite (`SQLite.php`)
- **Base de datos embebida**
- **Ideal para desarrollo y testing**

### 4. Servicios Centrales (`src/Services/`)

#### JsonResponse
- **Respuestas JSON estandarizadas**
- **Códigos de estado HTTP**
- **Formato consistente**

#### Helper
- **Funciones utilitarias globales**
- **Formateo de datos**
- **Validaciones comunes**

#### Validations
- **Validación de formularios**
- **Reglas de negocio**
- **Sanitización de datos**

---

## Sistema de Ruteo

### Autodescubrimiento de Rutas

El framework implementa un sistema **innovador de autodescubrimiento** basado en **anotaciones en comentarios PHP**:

```php
# [Route('/api/users', name: 'get_users', methods: 'GET')]
# useMiddleware
class UserController {
    public function __construct() {
        // Lógica del controlador
    }
}
```

### Proceso de Descubrimiento

1. **Escaneo**: El Kernel escanea `Backoffice/src/Modules`
2. **Parsing**: Extrae rutas usando regex: `/# \[Route\('([^']+)',\s*name: *'([^']+)',\s*methods: *'([^']+)'\)]/`
3. **Registro**: Registra automáticamente las rutas
4. **Middleware**: Detecta `# useMiddleware` para seguridad

### Ventajas del Sistema

- **Zero Configuration**: No archivos de rutas manuales
- **Autodocumentación**: Las rutas están junto al código
- **Flexibilidad**: Soporte para múltiples métodos HTTP
- **Seguridad**: Middleware opcional por ruta

### 🎯 **Migración hacia Symfony Compatibility**

**Objetivo Estratégico**: Hacer los módulos compatibles con Symfony para que puedan reconocer automáticamente los endpoints.

#### Estado Actual vs. Objetivo Symfony

**Actual (Comentarios PHP):**
```php
# [Route('/api/users', name: 'get_users', methods: 'GET')]
# useMiddleware
class UserController {
    public function __construct() {
        // Lógica del controlador
    }
}
```

**Objetivo (Atributos PHP 8 / Annotations):**
```php
use Symfony\Component\Routing\Annotation\Route;

class UserController {
    #[Route('/api/users', name: 'get_users', methods: ['GET'])]
    public function getUsers(): JsonResponse {
        // Lógica del controlador
    }
}
```

#### Plan de Migración de Anotaciones

1. **Fase 1**: Soporte dual (comentarios + atributos)
2. **Fase 2**: Migración gradual a atributos PHP 8
3. **Fase 3**: Compatibilidad total con Symfony Router
4. **Fase 4**: Módulos intercambiables entre frameworks

#### Beneficios de la Compatibilidad

- **Portabilidad**: Módulos funcionan en ambos frameworks
- **Ecosystem**: Acceso al ecosistema Symfony
- **Standards**: Seguimiento de estándares PSR
- **Future-proof**: Preparación para migración completa

---

## Capa de Datos

### 🚀 Sistema Moderno de Base de Datos (RECOMENDADO)

El framework incluye un nuevo sistema de base de datos con **Dependency Injection** y **Repository Pattern**:

```php
use Core\Services\DatabaseService;
use Core\Contracts\RepositoryAbstract;

// Repository Pattern
class UserRepository extends RepositoryAbstract 
{
    public function getActiveUsers(): array 
    {
        return $this->findBy('users', ['active' => 1]);
    }
    
    public function createUser(array $data): bool
    {
        return $this->insert('users', $data);
    }
}

// Uso en controladores
$userRepo = new UserRepository();
$users = $userRepo->getActiveUsers();
```

### Métodos Disponibles del DatabaseService

```php
// Query con prepared statements automáticos
$users = $database->query("SELECT * FROM users WHERE active = ?", true, 'fetch_all', [1]);

// Métodos de conveniencia
$user = $database->findById('users', 123);
$admins = $database->findBy('users', ['role' => 'admin']);
$results = $database->search('products', ['name', 'description'], 'smartphone');

// CRUD operations
$database->insert('users', ['name' => 'Juan', 'email' => 'juan@email.com']);
$database->update('users', ['last_login' => now()], ['id' => 123]);
$database->deleteById('users', 123);
```

### ⚠️ Sistema Legacy (MySQL Class)

**Mantenido para compatibilidad, pero deprecado:**

```php
use Core\Storage\MySQL;

// Método anterior (evitar en código nuevo)
$users = MySQL::query("SELECT * FROM users WHERE active = 1", true);
```

### Características de la Capa de Datos

- **Dependency Injection**: Servicios inyectables y testeable
- **Repository Pattern**: Abstracción de acceso a datos
- **Prepared Statements**: Prevención automática de SQL injection
- **Multi-tenant automático**: Cambia BD según dominio
- **Exception handling**: Manejo robusto de errores
- **Multiple adapters**: MySQL, SQLite, Redis
- **Logging integrado**: Todas las consultas se loggean automáticamente

> 📖 **Para guía completa:** Ver `Docs/database-guide.md`

---

## Frontend - Backoffice

### Tecnologías

- **Astro**: Framework web moderno
- **React**: Componentes interactivos
- **Zustand**: Estado global
- **SweetAlert2**: Notificaciones
- **Axios**: Cliente HTTP
- **SunEditor**: Editor WYSIWYG

### Características del Frontend

- **Hot reload**: Desarrollo en tiempo real
- **Componentes modulares**: Cada módulo tiene su interfaz
- **Estado global**: Zustand para sincronización
- **Hooks system**: Extensibilidad similar a WordPress
- **Responsive**: Mobile-first design
- **PWA ready**: Service workers configurados
- **Error Boundary**: Manejo robusto de errores
- **Null Safety**: Validaciones automáticas en CoreHooks

### 🔧 Problema Común Resuelto: Error de toLowerCase

**Error anterior:** `Cannot read properties of null (reading 'toLowerCase')`

**Causa:** En `CoreHooks.jsx`, algunos módulos tenían propiedades `null`

**Solución aplicada:**
```javascript
// ❌ Antes (causaba error si module_name era null)
const isActive = ModulesActive?.allModulesActiveInPlatform.find(
  t => t.module_name.toLowerCase() == folder.toLowerCase()
);

// ✅ Después (validación de null)
const isActive = ModulesActive?.allModulesActiveInPlatform.find(
  t => t.module_name && t.module_name.toLowerCase() == folder.toLowerCase()
);
```

Esta corrección previene errores cuando los datos de módulos vienen incompletos del backend.

### Estructura del Backoffice

```
Backoffice/
├── src/
│   ├── Modules/                 # Módulos funcionales
│   │   └── [Module]/
│   │       ├── Application/     # Casos de uso / endpoints
│   │       ├── Design/          # Componentes React para el frontend modular
│   │       │   ├── Components/  # Componentes React específicos del módulo
│   │       │   ├── CoreHooks/   # Sistema de hooks similar a PrestaShop
│   │       │   │               # Inyección automática: "HomePage.jsx" → página home
│   │       │   ├── Store/       # Store Zustand que conecta con backend
│   │       │   ├── [Module].jsx # Punto de entrada del módulo
│   │       │   ├── [Module].scss# Estilos (clases: [Module]_custom_class)
│   │       │   ├── routes.jsx   # Rutas frontend del módulo
│   │       │   └── Sidebar.jsx  # Componente para sidebar del dashboard
│   │       ├── Domain/          # Lógica de negocio
│   │       └── Infrastructure/  # Repositorios, migraciones, etc.
│   │           ├── Migrations/  # Migraciones de base de datos
│   │           └── ModuleRepository.php # Repository pattern
├── public/                      # Assets estáticos
└── configuration/               # Configuración Astro
```

### Arquitectura por Módulos

Cada módulo sigue **Domain Driven Design (DDD)** con frontend modular integrado:

```
UserModule/
├── Application/                  # Casos de uso y endpoints
│   ├── CreateUserUseCase.php
│   ├── GetUserUseCase.php
│   └── UserController.php        # Endpoints API
├── Design/                       # Frontend modular
│   ├── Components/               # Componentes React específicos
│   │   ├── UserForm.jsx
│   │   └── UserList.jsx
│   ├── CoreHooks/               # Sistema de hooks
│   │   ├── HomePage.jsx         # Se inyecta automáticamente en home
│   │   └── UserDashboard.jsx
│   ├── Store/                   # Estado Zustand
│   │   └── userStore.js
│   ├── User.jsx                 # Punto de entrada del módulo
│   ├── User.scss               # Estilos (User_custom_class)
│   ├── routes.jsx              # Rutas frontend
│   └── Sidebar.jsx             # Componente del sidebar
├── Domain/                      # Lógica de negocio
│   ├── User.php                # Entidad
│   ├── UserService.php         # Servicios de dominio
│   └── UserRepositoryInterface.php
└── Infrastructure/             # Capa de datos
    ├── Migrations/             # Migraciones de BD
    │   └── 001_create_users_table.php
    └── UserRepository.php      # Implementación del repository
```

#### Sistema de CoreHooks

Similar al sistema de hooks de **PrestaShop**, permite inyección automática de componentes:

- **HomePage.jsx** → Se inyecta automáticamente en la página de inicio
- **UserDashboard.jsx** → Se inyecta en el dashboard cuando corresponde
- **ProductList.jsx** → Se inyecta en listados de productos

#### Convenciones de Naming

- **Estilos CSS**: Prefijo obligatorio `[Module]_custom_class`
- **Componentes**: PascalCase con nombre del módulo
- **Hooks**: Nombre exacto de la página donde se inyectan
- **Store**: camelCase terminado en `Store`

---

## Configuración y Variables de Entorno

### Archivo .env Principal

```env
# Configuración de base de datos
DDBB_HOST=localhost
DDBB_USER=root
DDBB_PASSWORD=password
DDBB_DBNAME=coreframework

# Modo multi-tenant
MULTI_TENANT_MODE=false

# Configuración específica por dominio (si multi-tenant=true)
example.com=client1_db
client1_db_password=client1_password

# Modo de aplicación
MODE=dev
```

### Sistema de Configuración

1. **Prioridad**: `Backoffice/.env` > `.env`
2. **Carga automática**: Via `vlucas/phpdotenv`
3. **Validación**: Verificación de variables requeridas
4. **Multi-tenant**: Configuración dinámica por dominio

---

## Docker y Despliegue

### Servicios Docker

```yaml
# docker-compose.yml
services:
  nginx:
    image: nginx:alpine
    ports: ["80:80", "443:443"]
    
  php:
    build: ./Docker
    volumes: [".:/var/www/html"]
    
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: coreframework
      
  redis:
    image: redis:alpine
```

### Comandos NPM Disponibles

```json
{
  "install": "Setup completo + BD + migraciones",
  "dev": "Modo desarrollo con hot reload",
  "db": "Reset y migración de BD",
  "apache": "Setup para Apache/XAMPP",
  "apache-dev": "Desarrollo sin Docker",
  "build": "Build de producción",
  "test": "Ejecutar tests PHPUnit"
}
```

---

## CLI - CoreShell

### Comandos Disponibles

```bash
# Crear módulo
php coreshell make:module ModuleName

# Ejecutar migraciones
php coreshell migrations:migrate
```

### Características del CLI

- **Generación de código**: Scaffolding automático
- **Migraciones**: Sistema de versionado de BD
- **Basado en**: `gesprender/cli` package
- **Extensible**: Fácil agregar nuevos comandos

---

## Patrones de Diseño Implementados

### 1. Singleton Pattern
- **Ubicación**: `Kernel.php`
- **Propósito**: Una sola instancia de aplicación
- **Implementación**: Constructor privado + método getInstance

### 2. Factory Pattern
- **Ubicación**: `Services/JsonResponse.php`
- **Propósito**: Creación de respuestas estandarizadas
- **Beneficio**: Consistencia en formato de salida

### 3. Repository Pattern
- **Ubicación**: `Contracts/RepositoryAbstract.php`
- **Propósito**: Abstracción de acceso a datos
- **Beneficio**: Intercambiabilidad de storages

### 4. Service Layer Pattern
- **Ubicación**: `Services/`
- **Propósito**: Lógica de negocio centralizada
- **Beneficio**: Reutilización y testing

### 5. Middleware Pattern
- **Ubicación**: Sistema de ruteo
- **Propósito**: Procesamiento de requests
- **Implementación**: Middleware de seguridad

---

## Sistema Multi-Tenant

### Funcionamiento

1. **Detección de dominio**: `$_SERVER['HTTP_HOST']`
2. **Mapeo de BD**: `$_ENV[$domain]`
3. **Conexión dinámica**: Cambio automático de BD
4. **Aislamiento**: Datos completamente separados

### Configuración Multi-Tenant

```env
MULTI_TENANT_MODE=true

# Mapeo dominio -> base de datos
client1.com=client1_database
client2.com=client2_database

# Credenciales específicas
client1_database_password=pass1
client2_database_password=pass2
```

### Ventajas

- **Escalabilidad**: Un deployment, múltiples clientes
- **Aislamiento**: Datos completamente separados
- **Mantenimiento**: Actualizaciones centralizadas
- **Costos**: Reducción de infraestructura

---

## Seguridad y Middlewares

### Middleware de Seguridad

```php
// En controladores
# [Route('/api/secure-endpoint', name: 'secure', methods: 'POST')]
# useMiddleware
class SecureController {
    // Automáticamente valida JWT
}
```

### Características de Seguridad

- **JWT Tokens**: Autenticación stateless
- **Prepared Statements**: Anti SQL injection
- **CORS Headers**: Control de acceso
- **Input Sanitization**: Limpieza de datos
- **Session Management**: Manejo seguro de sesiones

---

## Testing

### Framework de Testing

- **PHPUnit**: Testing unitario
- **Mockery**: Mocking de dependencias
- **Configuración**: `phpunit.xml`

### Comandos de Testing

```bash
# Ejecutar todos los tests
npm run test

# O directamente con PHPUnit
vendor/bin/phpunit
```

---

## Áreas de Mejora Identificadas

### ✅ **COMPLETADO: Debugging y Error Handling**

**✅ Problemas Resueltos:**
- ✅ Logger muy básico → **Mejorado con DebugService avanzado**
- ✅ Sin debugging tools → **Whoops integrado con detección automática**
- ✅ Manejo de errores inconsistente → **Manejo gracioso cuando Whoops no disponible**
- ✅ Dependencias faltantes → **filp/whoops movido a require**

**✅ Mejoras Implementadas:**
- ✅ Whoops para debugging con handlers dinámicos
- ✅ Structured logging con contexto del framework
- ✅ Error pages personalizadas con información de contexto
- ✅ Compatibility layer para entornos sin Whoops

### ✅ **COMPLETADO: Context y Security Issues**

**✅ Problemas Resueltos:**
- ✅ "Typed property Context::$Business must not be accessed before initialization"
- ✅ User y Business objetos devolviendo null en frontend
- ✅ Security::setContext() con llamadas duplicadas
- ✅ RequestService llamando Security::middleware() inexistente
- ✅ Security::getUser() y getBusiness() sin return en catch blocks

**✅ Mejoras Implementadas:**
- ✅ Propiedades nullable en Context.php
- ✅ Refactoring de Security::setContext() para eficiencia
- ✅ Corrección de method call: Security::validateToken()
- ✅ Return statements completos en todos los métodos

### ✅ **COMPLETADO: Cleanup y Maintenance**

**✅ Archivos Eliminados:**
- ✅ `LoggerService_Backup.php`
- ✅ `LoggerService_Complex.php` 
- ✅ `Helper_Original_Backup.php`
- ✅ `Request_Original_Backup.php`

---

### 🔥 **PRÓXIMO: Sistema de Dependencias (CRÍTICO)**

**Problemas Actuales:**
- Sin contenedor de dependencias
- Acoplamiento alto entre clases
- Singleton pattern overused
- **🚫 Métodos estáticos excesivos** → **BLOQUEANTE para Symfony**

**Mejoras Propuestas:**
- Implementar PSR-11 Container
- Dependency injection automático
- Service providers
- **🎯 Eliminar métodos estáticos** → **PRE-REQUISITO obligatorio**

### 1. **Sistema de Ruteo**

**Problemas Actuales:**
- Parsing regex complejo y frágil
- No soporte para parámetros dinámicos `/users/{id}`
- Cache de rutas inexistente
- Documentación automática limitada

**Mejoras Propuestas:**
- Implementar router con PSR-15
- Soporte para route parameters
- Cache compilado de rutas
- Generación automática de documentación OpenAPI

### 2. **Capa de Datos**

**Problemas Actuales:**
- Query builder muy básico
- Sin lazy loading
- No hay pool de conexiones
- Migraciones manuales

**Mejoras Propuestas:**
- ORM ligero estilo Active Record
- Connection pooling
- Sistema de migraciones automático
- Lazy loading de relaciones

### 3. **Configuración**

**Problemas Actuales:**
- Solo archivos .env
- Sin validación de configuración
- Merge complejo de configs

**Mejoras Propuestas:**
- Soporte YAML/JSON configs
- Validation schema
- Environment-specific configs

### 4. **Performance**

**Problemas Actuales:**
- Sin sistema de cache
- Autodiscovery en cada request
- No optimización de autoload

**Mejoras Propuestas:**
- Cache layer (Redis/Memcached)
- Route caching
- Optimized autoloader

### 5. **Security**

**Problemas Actuales:**
- JWT sin refresh tokens
- Sin rate limiting
- CSRF protection básico

**Mejoras Propuestas:**
- OAuth2 integration
- Rate limiting middleware
- CSRF tokens automáticos

### 6. **Testing**

**Problemas Actuales:**
- Testing infrastructure limitada
- Sin integration tests
- Coverage bajo

**Mejoras Propuestas:**
- Test utilities
- Database testing helpers
- CI/CD integration

---

## 🔥 Refactoring Crítico: Eliminación de Métodos Estáticos

### Problema Actual

El framework tiene **uso excesivo de métodos estáticos** que representa un **bloqueante crítico** para:

- **Dependency Injection**: Imposible inyectar dependencias en métodos estáticos
- **Testing**: No se pueden mockear métodos estáticos fácilmente  
- **Symfony Compatibility**: Symfony está diseñado alrededor de DI
- **Mantenibilidad**: Acoplamiento fuerte entre clases
- **Extensibilidad**: Difícil override o extending de comportamiento

### Análisis de Métodos Estáticos Identificados

#### 1. **Services Layer (Crítico)**
```php
// ❌ PROBLEMÁTICO - Estado actual
Request::Route('/api/users', $callback);
Request::getValue('email');
Helper::validate_input($data);
JsonResponse::View($data);
Validations::StringSQL($string);
Response::setHeaders();
```

#### 2. **Storage Layer (Crítico)**
```php
// ❌ PROBLEMÁTICO - Estado actual  
MySQL::query('SELECT * FROM users');
MySQL::Connection();
Redis::getInstance();
```

#### 3. **Utility Classes (Medio)**
```php
// ❌ PROBLEMÁTICO - Estado actual
Logger::error('module', 'message');
Image::Upload($file, $path, $name);
Upload::img($file, $path);
Context::getContext();
```

#### 4. **Abstract/Traits (Alto)**
```php
// ❌ PROBLEMÁTICO - Estado actual
TraitResponseVariants::ExceptionResponse($e);
CoreAbstract::ExceptionCapture($e);
```

### Estrategia de Refactoring

#### Fase 1: Service Container + Core Services

**Objetivo**: Crear container DI y refactorizar servicios principales

```php
// ✅ SOLUCIÓN - Nuevo diseño

// 1. Service Container
class ServiceContainer implements ContainerInterface {
    private array $services = [];
    private array $resolved = [];
    
    public function bind(string $abstract, $concrete): void {
        $this->services[$abstract] = $concrete;
    }
    
    public function get(string $id) {
        if (isset($this->resolved[$id])) {
            return $this->resolved[$id];
        }
        
        if (!isset($this->services[$id])) {
            throw new ServiceNotFoundException($id);
        }
        
        return $this->resolved[$id] = $this->resolve($this->services[$id]);
    }
    
    private function resolve($concrete) {
        if (is_callable($concrete)) {
            return $concrete($this);
        }
        
        // Auto-wire constructor dependencies
        $reflector = new ReflectionClass($concrete);
        $constructor = $reflector->getConstructor();
        
        if (is_null($constructor)) {
            return new $concrete;
        }
        
        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $dependencies[] = $this->get($parameter->getType()->getName());
        }
        
        return $reflector->newInstanceArgs($dependencies);
    }
}

// 2. Request Service (NO estático)
class RequestService {
    private array $headers;
    private array $queryParams;
    private string $body;
    
    public function __construct() {
        $this->headers = $this->getHeaders();
        $this->queryParams = $_GET;
        $this->body = file_get_contents('php://input');
    }
    
    public function getValue(string $key, $default = null) {
        // Lógica de extracción
    }
    
    public function route(string $path, callable $callback, bool $useMiddleware = false): void {
        // Lógica de routing
    }
}

// 3. Helper Service (NO estático)  
class HelperService {
    public function validateInput(array $input): bool {
        // Lógica de validación
    }
    
    public function formatBytes(int $size, int $precision = 2): string {
        // Lógica de formateo
    }
}

// 4. Logger Service (NO estático)
class LoggerService implements LoggerInterface {
    private string $logPath;
    
    public function __construct(string $logPath) {
        $this->logPath = $logPath;
    }
    
    public function error(string $module, string $message): void {
        // Lógica de logging
    }
}
```

#### Fase 2: Storage Layer Refactoring

```php
// ✅ SOLUCIÓN - Repository pattern con DI

interface DatabaseConnectionInterface {
    public function query(string $sql, array $params = []): array;
    public function insert(string $table, array $data): bool;
    public function update(string $table, array $data, array $where): bool;
    public function delete(string $table, array $where): bool;
}

class MySQLConnection implements DatabaseConnectionInterface {
    private mysqli $connection;
    private ConfigService $config;
    
    public function __construct(ConfigService $config) {
        $this->config = $config;
        $this->connect();
    }
    
    private function connect(): void {
        $this->connection = new mysqli(
            $this->config->get('database.host'),
            $this->config->get('database.user'),
            $this->config->get('database.password'),
            $this->config->get('database.name')
        );
    }
    
    public function query(string $sql, array $params = []): array {
        $stmt = $this->connection->prepare($sql);
        if ($params) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Repository con dependency injection
class UserRepository {
    private DatabaseConnectionInterface $db;
    
    public function __construct(DatabaseConnectionInterface $db) {
        $this->db = $db;
    }
    
    public function findById(int $id): ?User {
        $result = $this->db->query('SELECT * FROM users WHERE id = ?', [$id]);
        return $result ? User::fromArray($result[0]) : null;
    }
    
    public function save(User $user): bool {
        return $this->db->insert('users', $user->toArray());
    }
}
```

#### Fase 3: Controller Refactoring

```php
// ✅ SOLUCIÓN - Controllers con DI

class UserController {
    private UserRepository $userRepository;
    private RequestService $request;
    private LoggerService $logger;
    
    public function __construct(
        UserRepository $userRepository,
        RequestService $request,
        LoggerService $logger
    ) {
        $this->userRepository = $userRepository;
        $this->request = $request;
        $this->logger = $logger;
    }
    
    #[Route('/api/users/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse {
        try {
            $user = $this->userRepository->findById($id);
            
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }
            
            return new JsonResponse(['data' => $user->toArray()]);
            
        } catch (Exception $e) {
            $this->logger->error('UserController', $e->getMessage());
            return new JsonResponse(['error' => 'Server error'], 500);
        }
    }
    
    #[Route('/api/users', methods: ['POST'])]
    public function create(): JsonResponse {
        $data = $this->request->getValue('user_data');
        
        // Validación y creación
        $user = User::fromArray($data);
        $success = $this->userRepository->save($user);
        
        return new JsonResponse([
            'success' => $success,
            'data' => $user->toArray()
        ]);
    }
}
```

### Plan de Migración Gradual

#### Etapa 1: Backward Compatibility Layer

```php
// Crear facade/proxy para mantener compatibilidad temporal
class RequestFacade {
    private static ?RequestService $instance = null;
    
    public static function Route(string $path, $callback, bool $middleware = false): void {
        if (!self::$instance) {
            self::$instance = app(RequestService::class);
        }
        
        self::$instance->route($path, $callback, $middleware);
    }
    
    // @deprecated Will be removed in v2.0
    public static function getValue(string $key, $default = null) {
        trigger_error('Static method deprecated, use RequestService instead', E_USER_DEPRECATED);
        
        if (!self::$instance) {
            self::$instance = app(RequestService::class);
        }
        
        return self::$instance->getValue($key, $default);
    }
}
```

#### Etapa 2: Service Registration

```php
// config/services.php
return [
    // Core services
    RequestService::class => function(Container $c) {
        return new RequestService();
    },
    
    LoggerService::class => function(Container $c) {
        return new LoggerService($c->get('config.log_path'));
    },
    
    // Database
    DatabaseConnectionInterface::class => function(Container $c) {
        return new MySQLConnection($c->get(ConfigService::class));
    },
    
    // Repositories
    UserRepository::class => function(Container $c) {
        return new UserRepository($c->get(DatabaseConnectionInterface::class));
    },
];
```

#### Etapa 3: Kernel Integration

```php
// config/Kernel.php - Integrar container DI
class Kernel {
    private ServiceContainer $container;
    
    public function __construct() {
        $this->container = new ServiceContainer();
        $this->registerServices();
    }
    
    private function registerServices(): void {
        $services = require __DIR__ . '/services.php';
        
        foreach ($services as $abstract => $concrete) {
            $this->container->bind($abstract, $concrete);
        }
    }
    
    public function run(): void {
        Response::setHeaders();
        $this->getDotenv();
        
        // Inicializar request service
        $request = $this->container->get(RequestService::class);
        
        // Auto-discover controllers con DI
        $this->loadControllers();
        // ... resto de la lógica
    }
    
    private function loadControllers(): void {
        // Modificar para usar DI en controladores
        foreach ($this->discoverControllers() as $controllerClass) {
            $controller = $this->container->get($controllerClass);
            $controller->registerRoutes();
        }
    }
}
```

### Benefits del Refactoring

#### Para Testing
```php
// ✅ Ahora se puede testear fácilmente
class UserControllerTest extends TestCase {
    public function testUserCanBeRetrieved() {
        $mockRepo = $this->createMock(UserRepository::class);
        $mockRepo->expects($this->once())
                ->method('findById')
                ->with(1)
                ->willReturn(new User(['id' => 1, 'name' => 'Test']));
        
        $controller = new UserController($mockRepo, $mockRequest, $mockLogger);
        $response = $controller->show(1);
        
        $this->assertEquals(200, $response->getStatusCode());
    }
}
```

#### Para Symfony Compatibility
```php
// ✅ Compatible con Symfony desde el inicio
class UserController extends AbstractController {
    public function __construct(
        private UserRepository $userRepository,
        private LoggerInterface $logger
    ) {}
    
    #[Route('/api/users/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse {
        // Mismo código funcionará en ambos frameworks
    }
}
```

### Timeline de Refactoring

#### Semana 1-2: Infrastructure
- [ ] Crear ServiceContainer básico
- [ ] Implementar auto-wiring
- [ ] Setup de servicios core

#### Semana 3-4: Core Services  
- [ ] Refactorizar Request, Response, Helper
- [ ] Crear backward compatibility facades
- [ ] Tests para nuevos servicios

#### Semana 5-6: Storage Layer
- [ ] Refactorizar MySQL, SQLite, Redis
- [ ] Implementar Repository pattern
- [ ] Migration de repositories existentes

#### Semana 7-8: Controllers
- [ ] Refactorizar controllers existentes
- [ ] Implementar route discovery con DI
- [ ] Cleanup de métodos estáticos legacy

### Herramientas de Migración

#### 1. Static Method Detector
```bash
# Script para detectar uso de métodos estáticos
php coreshell static:detect --path=src/ --report=static-usage.json
```

#### 2. Dependency Generator
```bash
# Auto-generar constructores con DI
php coreshell di:generate UserController --services=UserRepository,LoggerService
```

#### 3. Migration Assistant
```bash
# Asistir en migración de archivos
php coreshell migrate:static-to-di --file=src/Controllers/UserController.php
```

---

## Propuestas de Evolución

### Fase 1: Fundaciones (2-3 meses)

#### 1.1 Dependency Injection Container
```php
// Implementar PSR-11
class Container implements ContainerInterface {
    public function get(string $id) {
        // Resolver dependencias automáticamente
    }
}

// Usage en controladores
class UserController {
    public function __construct(
        private UserService $userService,
        private Logger $logger
    ) {}
}
```

#### 1.2 Router Moderno
```php
// Nuevo sistema de ruteo
Router::group(['prefix' => 'api', 'middleware' => 'auth'], function() {
    Router::get('/users/{id}', [UserController::class, 'show']);
    Router::post('/users', [UserController::class, 'store']);
});
```

#### 1.3 Configuration System
```yaml
# config/database.yaml
database:
  default: mysql
  connections:
    mysql:
      host: ${DB_HOST}
      database: ${DB_NAME}
      username: ${DB_USER}
      password: ${DB_PASSWORD}
```

### Fase 2: Developer Experience (2-3 meses)

#### 2.1 ORM Ligero
```php
// Active Record pattern
class User extends Model {
    protected $table = 'users';
    protected $fillable = ['name', 'email'];
    
    public function posts() {
        return $this->hasMany(Post::class);
    }
}

// Usage
$user = User::find(1);
$posts = $user->posts; // Lazy loading
```

#### 2.2 Validation Layer
```php
// Request validation
class CreateUserRequest extends FormRequest {
    public function rules(): array {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ];
    }
}
```

#### 2.3 API Resources
```php
// API Resource transformation
class UserResource extends JsonResource {
    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

### Fase 3: Enterprise Features (3-4 meses)

#### 3.1 Event System
```php
// Event-driven architecture
Event::listen(UserRegistered::class, function($event) {
    // Send welcome email
    Mail::send(new WelcomeEmail($event->user));
});

// En controlador
Event::dispatch(new UserRegistered($user));
```

#### 3.2 Queue System
```php
// Background jobs
class SendWelcomeEmail implements ShouldQueue {
    public function handle() {
        // Enviar email en background
    }
}

// Dispatch job
Queue::push(new SendWelcomeEmail($user));
```

#### 3.3 Cache Layer
```php
// Cache facade
$users = Cache::remember('users.all', 3600, function() {
    return User::all();
});

// Cache tags
Cache::tags(['users', 'posts'])->put('key', $value);
Cache::tags('users')->flush();
```

### Fase 4: Ecosystem (2-3 meses)

#### 4.1 Package System
```json
{
  "name": "gesprender/auth-package",
  "type": "gesprender-package",
  "require": {
    "gesprender/framework": "^1.0"
  }
}
```

#### 4.2 CLI Avanzado
```bash
# Generadores avanzados
php coreshell make:controller UserController --resource
php coreshell make:model User --migration --factory
php coreshell make:request CreateUserRequest
php coreshell make:resource UserResource

# Database tools
php coreshell db:migrate
php coreshell db:rollback
php coreshell db:seed
```

#### 4.3 Admin Panel Generator
```php
// Auto-generated admin
AdminPanel::resource(User::class)
    ->fields(['name', 'email'])
    ->filters(['status', 'created_at'])
    ->actions(['export', 'bulk_delete']);
```

---

## Arquitectura Futura Propuesta

```
┌─────────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                       │
│  ┌─────────────────┐    ┌─────────────────────────────────┐ │
│  │   Backoffice    │    │     API Gateway/Routes          │ │
│  │   (Astro+React) │    │     (Modern Router)             │ │
│  └─────────────────┘    └─────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│                   APPLICATION LAYER                         │
│  ┌─────────────────┐    ┌─────────────────────────────────┐ │
│  │   Controllers   │    │        Middleware Stack        │ │
│  │   (Auto-wired)  │    │  (Auth, CORS, Rate Limit)     │ │
│  └─────────────────┘    └─────────────────────────────────┘ │
│  ┌─────────────────┐    ┌─────────────────────────────────┐ │
│  │   Services      │    │        Event System            │ │
│  │   (DI Container)│    │     (Background Jobs)          │ │
│  └─────────────────┘    └─────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│                    DOMAIN LAYER                             │
│  ┌─────────────────┐    ┌─────────────────────────────────┐ │
│  │   Models/ORM    │    │        Business Logic          │ │
│  │   (Active Rec.) │    │      (Domain Services)         │ │
│  └─────────────────┘    └─────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│                INFRASTRUCTURE LAYER                         │
│  ┌─────────────────┐    ┌─────────────────────────────────┐ │
│  │   Cache Layer   │    │        Storage Layer           │ │
│  │ (Redis/Memory)  │    │    (MySQL/SQLite/Redis)       │ │
│  └─────────────────┘    └─────────────────────────────────┘ │
│  ┌─────────────────┐    ┌─────────────────────────────────┐ │
│  │   Queue System  │    │     External Services          │ │
│  │ (Redis/Database)│    │   (Email, File Storage)       │ │
│  └─────────────────┘    └─────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

---

## Roadmap de Implementación

### ✅ **COMPLETADO: Fundaciones Básicas**
- [x] **✅ Context y Security fixes** (Typed properties, null objects, method calls)
- [x] **✅ Debugging avanzado** (Whoops integration, error handling)
- [x] **✅ Cleanup de código** (eliminación archivos innecesarios)
- [x] **✅ Dependency management** (Whoops en require, composer update)

---

### 🔥 **ACTUAL Q1 2024: Static Methods Refactoring + Foundation (CRÍTICO)**

**🎯 Fase 1: Service Container Básico (Semanas 1-2)**
- [ ] **📦 Crear ServiceContainer (PSR-11)** con autowiring básico
- [ ] **🔧 Refactorizar Request Service** → eliminar métodos estáticos
- [ ] **🔧 Refactorizar Helper Service** → eliminar métodos estáticos
- [ ] **🔧 Refactorizar JsonResponse** → eliminar métodos estáticos

**🎯 Fase 2: Storage Layer Refactoring (Semanas 3-4)**
- [ ] **🗄️ Refactorizar MySQL class** → dependency injection
- [ ] **🗄️ Refactorizar Repository pattern** → constructor injection
- [ ] **🔧 Backward compatibility layer** → facades temporales

**🎯 Fase 3: Controllers y Routing (Semanas 5-6)**
- [ ] **🎮 Refactorizar controllers** → constructor injection
- [ ] **🛣️ Modern Router** con parámetros dinámicos
- [ ] **🔀 Soporte dual de anotaciones** (comentarios + atributos PHP 8)

**🎯 Fase 4: Integration y Testing (Semanas 7-8)**
- [ ] **🏗️ Kernel integration** → DI container
- [ ] **🧪 Testing infrastructure** → mocking support
- [ ] **🚀 Symfony Router adapter** para compatibilidad

### Q2 2024: Module System Evolution
- [ ] **CoreHooks system** (inyección automática de componentes)
- [ ] **Module generator** avanzado con estructura completa
- [ ] **CLI CoreShell** extendido para módulos Symfony-compatible
- [ ] **Validation layer** con atributos
- [ ] **API Resources** para respuestas estandarizadas

### Q3 2024: Enterprise Features
- [ ] **Event system** compatible con Symfony EventDispatcher
- [ ] **Queue system** con workers
- [ ] **Cache layer** PSR-6/PSR-16 compatible
- [ ] **Migration system** automático para módulos
- [ ] **Performance optimizations**

### Q4 2024: Full Symfony Interoperability
- [ ] **Package system** para módulos intercambiables
- [ ] **Symfony Bundle compatibility** completa
- [ ] **Admin panel generator** modular
- [ ] **Documentation generator** automático
- [ ] **Migration tools** Framework ↔ Symfony

### 🎯 **Hitos de Compatibilidad Symfony**

#### Milestone 1: Annotations Migration (Q1)
```php
// Soporte dual
#[Route('/api/users', methods: ['GET'])]
# [Route('/api/users', name: 'get_users', methods: 'GET')]  // Backward compatibility
public function getUsers() { }
```

#### Milestone 2: Module Portability (Q2)
```bash
# Instalar módulo en ambos frameworks
php coreshell module:install UserModule --target=gesprender
php bin/console module:install UserModule --target=symfony
```

#### Milestone 3: Bundle Compatibility (Q4)
```yaml
# config/bundles.php
return [
    // Módulos GesPrender funcionando como Symfony Bundles
    GesPrender\UserModule\UserBundle::class => ['all' => true],
];
```

---

## Estrategia de Migración hacia Symfony

### Objetivo Estratégico

Crear un **puente de compatibilidad** que permita que los módulos desarrollados en GesPrender Framework sean **100% compatibles con Symfony**, facilitando:

1. **Migración gradual** hacia Symfony sin reescribir código
2. **Intercambiabilidad de módulos** entre frameworks  
3. **Acceso al ecosistema Symfony** (bundles, componentes)
4. **Future-proofing** del código desarrollado

### Fases de Implementación

#### Fase 1: Adapter Pattern para Routing

**Objetivo**: Hacer que el sistema actual funcione con Symfony Router

```php
// Nuevo RoutingAdapter
class SymfonyRoutingAdapter {
    public function convertAnnotations(string $content): string {
        // Convierte comentarios a atributos PHP 8
        $pattern = "/# \[Route\('([^']+)',\s*name: *'([^']+)',\s*methods: *'([^']+)'\)]/";
        return preg_replace_callback($pattern, function($matches) {
            return "#[Route('{$matches[1]}', name: '{$matches[2]}', methods: ['{$matches[3]}'])]";
        }, $content);
    }
}
```

#### Fase 2: Container Compatibility

**Objetivo**: DI Container compatible con Symfony

```php
// Wrapper del container para compatibilidad
class SymfonyContainerAdapter implements ContainerInterface {
    private $gesPrenderContainer;
    private $symfonyContainer;
    
    public function get(string $id) {
        // Intenta resolver en GesPrender primero, luego Symfony
        return $this->gesPrenderContainer->has($id) 
            ? $this->gesPrenderContainer->get($id)
            : $this->symfonyContainer->get($id);
    }
}
```

#### Fase 3: Module Structure Normalization

**Objetivo**: Estructura de módulos compatible con Symfony Bundles

```php
// Generador de estructura dual
class ModuleGenerator {
    public function generateDualStructure(string $moduleName): void {
        $this->createGesPrenderStructure($moduleName);
        $this->createSymfonyBundleStructure($moduleName);
        $this->createCompatibilityLayer($moduleName);
    }
    
    private function createSymfonyBundleStructure(string $name): void {
        // Genera estructura Bundle estándar
        // src/
        // ├── DependencyInjection/
        // ├── Controller/
        // └── Resources/config/
    }
}
```

#### Fase 4: Event System Bridge

**Objetivo**: Bridge entre sistema de eventos custom y Symfony EventDispatcher

```php
// Bridge de eventos
class EventBridge {
    private $gesPrenderDispatcher;
    private $symfonyDispatcher;
    
    public function dispatch(object $event): void {
        // Despacha en ambos sistemas
        $this->gesPrenderDispatcher->dispatch($event);
        $this->symfonyDispatcher->dispatch($event);
    }
}
```

### Consideraciones Técnicas

#### 1. **Namespace Strategy**

```php
// Estructura de namespaces compatible
namespace GesPrender\Modules\User {
    // Código específico GesPrender
}

namespace Symfony\Bundles\GesPrenderUser {
    // Adaptadores para Symfony
}

namespace Common\User {
    // Código compartido (Domain, DTOs)
}
```

#### 2. **Configuration Bridge**

```yaml
# gesprender.yaml (compatible con Symfony)
gesprender:
  modules:
    user:
      enabled: true
      routes_prefix: '/api'
      security: jwt
      
  # Mapeo automático a configuración Symfony
  symfony_mapping:
    framework:
      router: { resource: '%kernel.project_dir%/config/gesprender_routes.yaml' }
```

#### 3. **Annotation/Attribute Dual Support**

```php
// Soporte dual durante migración
class UserController {
    // Comentario legacy (será eliminado)
    # [Route('/api/users', name: 'get_users', methods: 'GET')]
    
    // Atributo PHP 8 (objetivo)
    #[Route('/api/users', name: 'get_users', methods: ['GET'])]
    #[Security('is_granted("ROLE_USER")')]  // Symfony security
    
    public function getUsers(): JsonResponse {
        // Código compatible con ambos frameworks
    }
}
```

#### 4. **Service Definition Bridge**

```php
// Definición de servicios compatible
class UserModule {
    public static function getServices(): array {
        return [
            // GesPrender format
            'user.service' => UserService::class,
            'user.repository' => UserRepository::class,
            
            // Auto-mapping to Symfony format
            UserService::class => ['arguments' => ['@user.repository']],
            UserRepository::class => ['arguments' => ['@database.connection']],
        ];
    }
}
```

### Benefits de la Estrategia

#### Para Desarrolladores
- **No vendor lock-in**: Código portable entre frameworks
- **Gradual migration**: Sin big-bang rewrites
- **Best of both worlds**: Simplicidad GesPrender + Ecosystem Symfony

#### Para el Negocio
- **Risk mitigation**: Diversificación tecnológica
- **Talent acquisition**: Desarrolladores Symfony disponibles
- **Ecosystem access**: Bundles y componentes de terceros

#### Para el Framework
- **Market position**: Posicionamiento como "Symfony alternative"
- **Evolution path**: Camino claro hacia tecnologías mainstream
- **Community**: Acceso a la comunidad Symfony

### Challenges y Soluciones

#### Challenge 1: Performance Impact
**Problema**: Layers adicionales pueden impactar performance
**Solución**: Compilation phase que elimina adapters en producción

#### Challenge 2: Complexity Management  
**Problema**: Mantener dos sistemas puede ser complejo
**Solución**: Tooling automático y documentation extensa

#### Challenge 3: Version Compatibility
**Problema**: Mantener compatibilidad con versiones Symfony
**Solución**: Adapter versioning y testing matrix

---

## Conclusión

El GesPrender Core Framework tiene una base sólida y **ha completado refactorings fundamentales** que mejoran significativamente la estabilidad y debugging. El próximo paso crítico es el **refactoring de métodos estáticos** para desbloquear la compatibilidad con Symfony.

**✅ Fortalezas Actuales:**
- Simplicidad y rapidez de desarrollo
- Sistema multi-tenant robusto
- Integración frontend-backend avanzada (CoreHooks)
- Arquitectura modular con DDD
- Docker ready

**✅ Completado Recientemente:**
- ✅ **Context y Security fixes** → No más typed property errors
- ✅ **Debugging avanzado** → Whoops integrado con detección automática
- ✅ **Error handling robusto** → Manejo gracioso de dependencias faltantes
- ✅ **Code cleanup** → Eliminación de archivos innecesarios
- ✅ **Dependency management** → Whoops disponible en producción

**🔥 Bloqueante Crítico Actual (Prioridad Máxima):**
1. **Métodos estáticos excesivos** → **SIGUIENTE PASO OBLIGATORIO**
   - Request::Route(), Request::getValue()
   - Helper::validate_input(), Helper::*
   - MySQL::query(), MySQL::Connection()
   - JsonResponse::View()

**🎯 Próximo Paso Inmediato (Semanas 1-2):**
1. **📦 Implementar ServiceContainer (PSR-11)** con autowiring básico
2. **🔧 Refactorizar Services críticos**:
   - RequestService → constructor injection
   - HelperService → constructor injection  
   - JsonResponse → constructor injection
3. **🔧 Crear backward compatibility layer** → facades temporales

**Objetivos Post-Static-Refactoring:**
- Dependency injection automático
- Testing robusto con mocking
- Compatibility con Symfony DI container
- Controllers con constructor injection
- Repository pattern moderno

**🚨 Importancia Crítica del Refactoring de Estáticos:**
- **Sin esto NO es posible**: Dependency injection, testing adecuado, Symfony compatibility
- **Con esto SÍ es posible**: Testing robusto, módulos intercambiables, ecosystem Symfony
- **Timeline**: Pre-requisito obligatorio para todas las mejoras futuras

La documentación establece el roadmap completo, comenzando por el **refactoring crítico de métodos estáticos** como paso 1 obligatorio.

---

*Documentación generada para gesprender-core-framework v0.0.1*
*Última actualización: Diciembre 2024* 