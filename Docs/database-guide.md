# Guía de Base de Datos - GesPrender Core Framework

Esta guía te explica cómo conectar y realizar consultas a la base de datos usando el sistema moderno del framework con **Dependency Injection** y **Repository Pattern**.

## Índice

1. [Configuración de Conexión](#configuración-de-conexión)
2. [Sistema Moderno vs Legacy](#sistema-moderno-vs-legacy)
3. [Uso del DatabaseService](#uso-del-databaseservice)
4. [Repository Pattern](#repository-pattern)
5. [Ejemplos Prácticos](#ejemplos-prácticos)
6. [Migraciones](#migraciones)
7. [Multi-tenant](#multi-tenant)
8. [Mejores Prácticas](#mejores-prácticas)

---

## Configuración de Conexión

### Variables de Entorno

Configura tu archivo `.env` con las credenciales de base de datos:

```env
# Configuración de Base de Datos
DDBB_HOST=localhost
DDBB_USER=tu_usuario
DDBB_PASSWORD=tu_password
DDBB_DBNAME=tu_base_datos

# Multi-tenant (opcional)
MULTI_TENANT_MODE=false

# Para multi-tenant, configura dominios específicos:
# ejemplo.com=bd_especifica
# ejemplo.com_password=password_especifica
```

### Configuración en ServiceContainer

El framework registra automáticamente el servicio de base de datos:

```php
// En src/Services/ServiceContainer.php
$this->singleton('Core\Contracts\DatabaseConnectionInterface', function($container) {
    return new DatabaseService(
        $container->get('config'),
        $container->get(LoggerService::class)
    );
});
```

---

## Sistema Moderno vs Legacy

### 🚀 Sistema Moderno (RECOMENDADO)

**Usa Dependency Injection, Repository Pattern y es seguro ante SQL Injection**

```php
use Core\Services\DatabaseService;
use Core\Contracts\RepositoryAbstract;

// En un Repository
class UserRepository extends RepositoryAbstract 
{
    public function getActiveUsers(): array 
    {
        return $this->findBy('users', ['active' => 1]);
    }
}
```

### ⚠️ Sistema Legacy (DEPRECADO)

**Métodos estáticos, sin DI - Solo para compatibilidad**

```php
use Core\Storage\MySQL;

// Método anterior (evitar en código nuevo)
$users = MySQL::query("SELECT * FROM users WHERE active = 1", true);
```

---

## Uso del DatabaseService

### Inyección de Dependencias

```php
use Core\Contracts\DatabaseConnectionInterface;
use Core\Services\ServiceContainer;

class MiClase 
{
    private DatabaseConnectionInterface $database;
    
    public function __construct(DatabaseConnectionInterface $database = null) 
    {
        $this->database = $database ?? ServiceContainer::resolve(DatabaseConnectionInterface::class);
    }
}
```

### Métodos Disponibles

#### 1. Query Raw (con Prepared Statements)

```php
// Query simple
$users = $this->database->query(
    "SELECT * FROM users WHERE active = 1", 
    true, // fetch results
    'fetch_all'
);

// Query con parámetros (prepared statement automático)
$user = $this->database->query(
    "SELECT * FROM users WHERE email = ? AND active = ?",
    true,
    'fetch_assoc',
    ['usuario@email.com', 1]
);
```

#### 2. Métodos de Conveniencia

```php
// Obtener registros específicos
$activeUsers = $this->database->get(
    ['id', 'name', 'email'], // columnas
    'users',                  // tabla
    ['active' => 1],         // condiciones WHERE
    'created_at DESC'        // ORDER BY
);

// Buscar por ID
$user = $this->database->findById('users', 123);

// Buscar un registro
$admin = $this->database->findOneBy('users', [
    'role' => 'admin', 
    'active' => 1
]);

// Búsqueda de texto
$results = $this->database->search(
    'products',
    ['name', 'description'], // columnas a buscar
    'smartphone'             // término de búsqueda
);

// Insertar
$success = $this->database->insert('users', [
    'name' => 'Juan Pérez',
    'email' => 'juan@email.com',
    'active' => 1
]);

// Actualizar
$success = $this->database->update(
    'users',
    ['last_login' => date('Y-m-d H:i:s')], // datos a actualizar
    ['id' => 123]                          // condiciones WHERE
);

// Eliminar por ID
$success = $this->database->deleteById('users', 123);
```

---

## Repository Pattern

### Crear un Repository

```php
<?php
declare(strict_types=1);

namespace Backoffice\Modules\Products\Infrastructure;

use Core\Contracts\RepositoryAbstract;

class ProductRepository extends RepositoryAbstract
{
    protected string $table = 'products';
    
    /**
     * Obtiene productos activos
     */
    public function getActiveProducts(): array
    {
        return $this->findBy($this->table, ['active' => 1]);
    }
    
    /**
     * Busca productos por categoría
     */
    public function getByCategory(int $categoryId): array
    {
        return $this->findBy($this->table, [
            'category_id' => $categoryId,
            'active' => 1
        ]);
    }
    
    /**
     * Busca productos por nombre
     */
    public function searchByName(string $searchTerm): array
    {
        return $this->search($this->table, ['name', 'description'], $searchTerm);
    }
    
    /**
     * Crea un nuevo producto
     */
    public function createProduct(array $productData): bool
    {
        // Validar datos antes de insertar
        if (!$this->validateProductData($productData)) {
            return false;
        }
        
        return $this->insert($this->table, $productData);
    }
    
    /**
     * Actualiza un producto
     */
    public function updateProduct(int $id, array $productData): bool
    {
        return $this->update($this->table, $productData, ['id' => $id]);
    }
    
    /**
     * Elimina un producto
     */
    public function deleteProduct(int $id): bool
    {
        return $this->deleteById($this->table, $id);
    }
    
    /**
     * Obtiene productos con bajo stock
     */
    public function getLowStockProducts(int $threshold = 10): array
    {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE stock < ? AND active = 1",
            true,
            'fetch_all',
            [$threshold]
        );
    }
    
    /**
     * Validación personalizada
     */
    private function validateProductData(array $data): bool
    {
        $required = ['name', 'price', 'category_id'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        
        return true;
    }
}
```

### Usar el Repository en un Controlador

```php
<?php
declare(strict_types=1);

namespace Backoffice\Modules\Products\Application;

use Backoffice\Modules\Products\Infrastructure\ProductRepository;
use Core\Services\JsonResponse;

class ProductController
{
    private ProductRepository $productRepository;
    
    public function __construct()
    {
        $this->productRepository = new ProductRepository();
    }
    
    public function getProducts(): void
    {
        try {
            $products = $this->productRepository->getActiveProducts();
            
            JsonResponse::success([
                'products' => $products,
                'total' => count($products)
            ]);
            
        } catch (\Exception $e) {
            JsonResponse::error('Error al obtener productos: ' . $e->getMessage());
        }
    }
    
    public function createProduct(): void
    {
        try {
            $data = $_POST;
            
            if ($this->productRepository->createProduct($data)) {
                JsonResponse::success('Producto creado correctamente');
            } else {
                JsonResponse::error('Error al crear el producto');
            }
            
        } catch (\Exception $e) {
            JsonResponse::error('Error: ' . $e->getMessage());
        }
    }
    
    public function searchProducts(): void
    {
        try {
            $searchTerm = $_GET['q'] ?? '';
            
            if (empty($searchTerm)) {
                JsonResponse::error('Término de búsqueda requerido');
                return;
            }
            
            $products = $this->productRepository->searchByName($searchTerm);
            
            JsonResponse::success([
                'products' => $products,
                'search_term' => $searchTerm
            ]);
            
        } catch (\Exception $e) {
            JsonResponse::error('Error en la búsqueda: ' . $e->getMessage());
        }
    }
}
```

---

## Ejemplos Prácticos

### Caso 1: CRUD Completo de Usuarios

```php
class UserRepository extends RepositoryAbstract
{
    protected string $table = 'users';
    
    // Crear usuario
    public function create(array $userData): array
    {
        // Hash de password
        if (isset($userData['password'])) {
            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        }
        
        $userData['created_at'] = date('Y-m-d H:i:s');
        
        if ($this->insert($this->table, $userData)) {
            return $this->findOneBy($this->table, ['email' => $userData['email']]);
        }
        
        return [];
    }
    
    // Autenticar usuario
    public function authenticate(string $email, string $password): array
    {
        $user = $this->findOneBy($this->table, ['email' => $email, 'active' => 1]);
        
        if (!empty($user) && password_verify($password, $user['password'])) {
            // Actualizar último login
            $this->update($this->table, 
                ['last_login' => date('Y-m-d H:i:s')], 
                ['id' => $user['id']]
            );
            
            return $user;
        }
        
        return [];
    }
    
    // Obtener usuarios paginados
    public function getPaginated(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        $users = $this->query(
            "SELECT id, name, email, role, created_at FROM {$this->table} 
             WHERE active = 1 
             ORDER BY created_at DESC 
             LIMIT ? OFFSET ?",
            true,
            'fetch_all',
            [$limit, $offset]
        );
        
        // Contar total
        $total = $this->query(
            "SELECT COUNT(*) as total FROM {$this->table} WHERE active = 1",
            true,
            'fetch_assoc'
        )['total'] ?? 0;
        
        return [
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }
}
```

### Caso 2: Reportes y Estadísticas

```php
class StatsRepository extends RepositoryAbstract
{
    public function getSalesStats(string $startDate, string $endDate): array
    {
        $sql = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_sales,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount
            FROM sales 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ";
        
        return $this->query($sql, true, 'fetch_all', [$startDate, $endDate]);
    }
    
    public function getTopProducts(int $limit = 10): array
    {
        $sql = "
            SELECT 
                p.name,
                p.price,
                COUNT(si.product_id) as sales_count,
                SUM(si.quantity) as total_quantity
            FROM products p
            INNER JOIN sale_items si ON p.id = si.product_id
            INNER JOIN sales s ON si.sale_id = s.id
            WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY p.id
            ORDER BY sales_count DESC
            LIMIT ?
        ";
        
        return $this->query($sql, true, 'fetch_all', [$limit]);
    }
}
```

### Caso 3: Transacciones

```php
class OrderRepository extends RepositoryAbstract
{
    public function createOrderWithItems(array $orderData, array $items): bool
    {
        try {
            // Iniciar transacción
            $this->database->query("START TRANSACTION");
            
            // Crear orden
            $orderData['created_at'] = date('Y-m-d H:i:s');
            $orderData['status'] = 'pending';
            
            if (!$this->insert('orders', $orderData)) {
                throw new \Exception('Error al crear la orden');
            }
            
            // Obtener ID de la orden creada
            $orderId = $this->database->query("SELECT LAST_INSERT_ID() as id", true, 'fetch_assoc')['id'];
            
            // Insertar items
            foreach ($items as $item) {
                $item['order_id'] = $orderId;
                
                if (!$this->insert('order_items', $item)) {
                    throw new \Exception('Error al insertar item');
                }
                
                // Actualizar stock
                $this->query(
                    "UPDATE products SET stock = stock - ? WHERE id = ?",
                    false,
                    'fetch_all',
                    [$item['quantity'], $item['product_id']]
                );
            }
            
            // Confirmar transacción
            $this->database->query("COMMIT");
            return true;
            
        } catch (\Exception $e) {
            // Rollback en caso de error
            $this->database->query("ROLLBACK");
            return false;
        }
    }
}
```

---

## Migraciones

### Crear Migraciones

Coloca tus migraciones en: `Backoffice/src/Modules/[TuModulo]/Infrastructure/Migrations/`

```php
<?php
// 001_create_products_table.php

use Core\Contracts\RepositoryAbstract;

class CreateProductsTable extends RepositoryAbstract
{
    public function up(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                stock INT DEFAULT 0,
                category_id INT,
                active BOOLEAN DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_category (category_id),
                INDEX idx_active (active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        
        $this->database->query($sql);
    }
    
    public function down(): void
    {
        $this->database->query("DROP TABLE IF EXISTS products");
    }
}
```

### Ejecutar Migraciones

```bash
# Usando CLI del framework
php cli migrate:run

# O manualmente
php -r "
include 'config/bootstrap.php';
$migration = new CreateProductsTable();
$migration->up();
"
```

---

## Multi-tenant

### Configuración Multi-tenant

```env
# Activar modo multi-tenant
MULTI_TENANT_MODE=true

# Configurar dominios y bases de datos
cliente1.miapp.com=db_cliente1
cliente1.miapp.com_password=password_cliente1

cliente2.miapp.com=db_cliente2
cliente2.miapp.com_password=password_cliente2
```

### Uso Automático

El framework detecta automáticamente el dominio y conecta a la BD correspondiente:

```php
// El DatabaseService automáticamente detecta el dominio
// y conecta a la base de datos correcta
$products = $this->productRepository->getActiveProducts();
// → Se conecta a db_cliente1 si la request viene de cliente1.miapp.com
```

---

## Mejores Prácticas

### 1. Seguridad

```php
// ✅ CORRECTO: Usar prepared statements
$user = $this->database->query(
    "SELECT * FROM users WHERE email = ?",
    true,
    'fetch_assoc',
    [$email]
);

// ❌ INCORRECTO: Concatenación directa (SQL Injection!)
$user = $this->database->query(
    "SELECT * FROM users WHERE email = '$email'",
    true
);
```

### 2. Manejo de Errores

```php
public function getUser(int $id): array
{
    try {
        $user = $this->findById('users', $id);
        
        if (empty($user)) {
            throw new \Exception("Usuario no encontrado");
        }
        
        return $user;
        
    } catch (\Exception $e) {
        // Log del error
        error_log("Error getting user: " . $e->getMessage());
        
        // Retornar array vacío o lanzar excepción
        return [];
    }
}
```

### 3. Validación de Datos

```php
public function createUser(array $data): bool
{
    // Validar campos requeridos
    $required = ['name', 'email', 'password'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new \InvalidArgumentException("Campo requerido: $field");
        }
    }
    
    // Validar email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new \InvalidArgumentException("Email inválido");
    }
    
    // Verificar email único
    $existing = $this->findOneBy('users', ['email' => $data['email']]);
    if (!empty($existing)) {
        throw new \Exception("El email ya está registrado");
    }
    
    return $this->insert('users', $data);
}
```

### 4. Optimización de Consultas

```php
// ✅ Usar índices en las consultas WHERE
$this->database->query("SELECT * FROM products WHERE category_id = ?", true, 'fetch_all', [$categoryId]);

// ✅ Limitar resultados cuando sea posible
$this->database->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 50", true);

// ✅ Seleccionar solo columnas necesarias
$this->database->get(['id', 'name', 'price'], 'products', ['active' => 1]);
```

### 5. Cacheo

```php
class ProductRepository extends RepositoryAbstract
{
    private array $cache = [];
    
    public function getCategory(int $id): array
    {
        if (isset($this->cache["category_$id"])) {
            return $this->cache["category_$id"];
        }
        
        $category = $this->findById('categories', $id);
        $this->cache["category_$id"] = $category;
        
        return $category;
    }
}
```

---

## Migración del Sistema Legacy

### Paso a Paso

1. **Identificar código que usa MySQL::query()**
2. **Crear Repository para el módulo**
3. **Reemplazar llamadas estáticas**
4. **Probar funcionalidad**

### Ejemplo de Migración

**Antes (Legacy):**
```php
// En un controlador
$products = MySQL::query("SELECT * FROM products WHERE active = 1", true);
```

**Después (Moderno):**
```php
// Crear ProductRepository
class ProductRepository extends RepositoryAbstract
{
    public function getActiveProducts(): array
    {
        return $this->findBy('products', ['active' => 1]);
    }
}

// En el controlador
$productRepository = new ProductRepository();
$products = $productRepository->getActiveProducts();
```

---

## Solución de Problemas Comunes

### Error: "Cannot read properties of null"

**Problema:** Variables null antes de usar métodos
```php
// ❌ Error si $module_name es null
$modules.find(t => t.module_name.toLowerCase() == folder.toLowerCase())

// ✅ Solución: Validar antes de usar
$modules.find(t => t.module_name && t.module_name.toLowerCase() == folder.toLowerCase())
```

### Error de Conexión a BD

```php
// Verificar configuración .env
// Verificar permisos de usuario de BD
// Verificar que el servidor MySQL esté corriendo

// Debug de conexión
try {
    $db = ServiceContainer::resolve(DatabaseConnectionInterface::class);
    $result = $db->query("SELECT 1", true);
    echo "Conexión OK";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Rendimiento Lento

```php
// ✅ Añadir índices
ALTER TABLE products ADD INDEX idx_category (category_id);
ALTER TABLE products ADD INDEX idx_active (active);

// ✅ Optimizar consultas
// En lugar de N+1 queries:
foreach ($products as $product) {
    $category = $this->findById('categories', $product['category_id']);
}

// Usar JOIN:
$sql = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.active = 1
";
$products = $this->database->query($sql, true);
```

---

**¡Ya estás listo para usar el sistema moderno de base de datos del GesPrender Core Framework!** 🚀

Para más información, consulta las otras documentaciones:
- `framework-documentation.md` - Documentación general
- `developer-module-extension-guide.md` - Creación de módulos
- `logging-system.md` - Sistema de logs 