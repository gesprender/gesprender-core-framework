<?php

namespace Config\Commands;

use Config\Commands\AbstractConsoleLibrary;

class Make extends AbstractConsoleLibrary
{

    public static function build()
    {
        echo self::colorText(" [●] Ejecutando build...\n", self::GREEN);
        self::createBuild();
    }

    private static function createBuild()
    {
        # Creamos el archivo index.html
        echo "      [●] Creando index...\n";
        $moduleContent = '<html><head><title>Document</title></head><body><div id="root"></div><script type="module" src="./src/Modules/themes/App.jsx"></script></body></html>';
        file_put_contents('index.html', $moduleContent);
        echo "      [●] Ejecutando build de react...\n";
        # Ejecutamos el build de react
        exec('npm run build');
        exec('del index.html');
        # Capturamos el build
        echo "      [●] Capturando build y renombrando archivos...\n";
        $filesDist = scandir("./dist/assets");
        # Renombramos los archivos
        foreach ($filesDist as $key => $file) {
            if ($file == '.' || $file == '..') continue;

            $filePath = explode('.', $file);
            $extension = $filePath[count($filePath) - 1];
            rename("./dist/assets/$file", "./dist/assets/themes.$extension");
        }
        echo "      [●] Copiando build en themes...\n";
        self::folderCopy('./dist/assets/', "./src/Modules/themes/dist");
        echo self::colorText(" [●] Build generado con exito...\n", self::GREEN);
    }

    private static function folderCopy($source, $target)
    {
        if (is_dir($source)) {
            @mkdir($target);
            $d = dir($source);
            while (FALSE !== ($entry = $d->read())) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                $Entry = $source . '/' . $entry;
                if (is_dir($Entry)) {
                    self::folderCopy($Entry, $target . '/' . $entry);
                    continue;
                }
                copy($Entry, $target . '/' . $entry);
            }

            $d->close();
        } else {
            copy($source, $target);
        }
        exec("rmdir /s /q dist");
    }

    public static function project()
    {
        $estructura = [
            "Project" => [
                "src" => [
                    "TestModule" => [
                        "Test.php" => "<?php\n// #GesPrender Core Framework\n",
                        "Migrations.sql" => "",
                        "TestRepository.php" => "<?php\n// #GesPrender Core Framework\n",
                        "TestRequestController.php" => "<?php\n// #GesPrender Core Framework\n"
                    ],
                    "Endpoints.php" => "<?php\n// #GesPrender Core Framework\n"
                ],
                "theme" => [
                    "App.jsx" => "//GesPrender Core Framework\n"
                ],
                "dist" => []
            ]
        ];
        echo self::colorText(" [●] Creando estructura...\n", self::GREEN);
        self::createStructure('.', $estructura);
        echo self::colorText(" [●] Estructura creada ...\n", self::GREEN);
    }

    private static function createStructure($path, $estructura)
    {
        foreach ($estructura as $key => $value) {
            $currentPath = $path . '/' . $key;
            if (!is_array($value) || (is_numeric($key) && is_string($value))) {
                // Es un archivo
                $filePath = is_numeric($key) ? $path . '/' . $value : $currentPath;
                $contenido = is_numeric($key) ? '' : $value;
                $pathFileCopy = "./config/Commands/Models/$key";
                $pathFileCopy = str_replace('.php', '.txt', $pathFileCopy);
                
                if (file_exists($pathFileCopy)) {
                    $contenido = file_get_contents($pathFileCopy);
                }
                
                file_put_contents($filePath, $contenido); // Crea un archivo con contenido
            } else {
                // Es un directorio
                if (!file_exists($currentPath)) {
                    mkdir($currentPath, 0777, true);
                }
                self::createStructure($currentPath, $value); // Recursividad para subdirectorios
            }
        }
    }
}
