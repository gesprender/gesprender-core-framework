<?php

namespace Config;

use Core\Services\JsonResponse;
use Core\Services\Request;
use Core\Services\Response;
use Dotenv\Dotenv;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final readonly class Kernel
{
    public function run(): void
    {
        Response::setHeaders();

        $this->getDotenv();


        if ($_ENV['MODE'] == 'prod') error_reporting(E_ALL & ~E_WARNING);

        # Load Session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        # Defines
        require  'defines.php';

        $this->leadFiles();
        $this->endpointNotFound();
        $this->Welcome();
    }

    private function getDotenv(): void
    {
        $loadCustomDefines = '../Backoffice/.env';
        if (file_exists($loadCustomDefines)) {
            $dotenv = Dotenv::createImmutable('./../Backoffice/');
            $dotenv->load();
        } else {
            $dotenv = Dotenv::createImmutable('./../');
            $dotenv->load();
        }
    }

    private function leadFiles()
    {

        # Load backoffice custom defines
        $loadCustomDefines = '../Backoffice/src/custom_defines.php';
        if (file_exists($loadCustomDefines)) {
            require $loadCustomDefines;
        }

        # Load backoffice Endpoints
        $this->autoload_controllers('../Backoffice/src/Modules');
        $this->autoload_endpoints($this->scanBackofficeRoutes('..'));
    }

    private function autoload_controllers($directory): void
    {
        $modules = scandir($directory);
        foreach ($modules as $module) {
            if ($module === '.' || $module === '..') continue;

            $controllerPath = $directory . '/' . $module . '/Infrastructure/' . $module . 'Controller.php';
            if (file_exists($controllerPath)) {
                $controllerClass = 'Backoffice\\Modules\\' . $module . '\\Infrastructure\\' . $module . 'Controller';
                if (class_exists($controllerClass)) {
                    $controllerClass::endpoints();
                }
            }
        }
    }

    private function autoload_endpoints($directory): void
    {
        foreach ($directory as $value) {
            Request::Route($value['routes'], function () use ($value) {
                $classController = $value['namespace']."\\".$value['class_name'];
                if (class_exists($classController)) {
                    new $classController();
                }
            }, $value['useMiddleware']);
        }
    }

    private function endpointNotFound(): ?JsonResponse
    {
        if ($_REQUEST) {
            return new JsonResponse([
                'status' => false,
                'message' => 'Route not found.'
            ], 404);
        }
        return null;
    }

    private function Welcome(): ?JsonResponse
    {
        return new JsonResponse([
            'status' => true,
            'message' => 'Welcom to Api.',
            'data' => []
        ], 200);
    }

    private function scanBackofficeRoutes($basePath)
    {
        $modulesPath = "$basePath/Backoffice/src/Modules";
        $result = [];

        if (!is_dir($modulesPath)) {
            return "El directorio de módulos no existe.";
        }

        foreach (scandir($modulesPath) as $module) {
            if ($module === '.' || $module === '..') {
                continue;
            }

            $applicationPath = "$modulesPath/$module/Application";
            if (!is_dir($applicationPath)) {
                continue;
            }

            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($applicationPath));
            foreach ($files as $file) {
                if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $content = file_get_contents($file->getRealPath());
                    $pattern = "/# \[Route\('([^']+)',\s*name: *'([^']+)',\s*methods: *'([^']+)'\)]/";

                    // Extraer el namespace
                    $namespace = null;
                    if (preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches)) {
                        $namespace = trim($namespaceMatches[1]);
                    }

                    // Extraer el nombre de la clase
                    $className = null;
                    if (preg_match('/class\s+(\w+)/', $content, $classMatches)) {
                        $className = trim($classMatches[1]);
                    }

                    if (preg_match($pattern, $content, $matches)) {
                        $useMiddleware = strpos($content, '# useMiddleware') !== false;

                        $result[] = [
                            'routes' => $matches[1],
                            'name' => $matches[2],
                            'method' => $matches[3],
                            'file_name' => $file->getFilename(),
                            'file_path' => $file->getRealPath(),
                            'useMiddleware' => $useMiddleware,
                            'namespace' => $namespace,
                            'class_name' => $className
                        ];
                    }
                }
            }
        }

        return $result;
    }
}
