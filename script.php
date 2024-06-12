<?php

function copyDirectory($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copyDirectory($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

function getDirectories($path) {
    $directories = array();
    $items = scandir($path);
    foreach ($items as $item) {
        if ($item != '.' && $item != '..' && is_dir($path . '/' . $item)) {
            $directories[] = $item;
        }
    }
    return $directories;
}

$baseDir = 'Backoffice';
$modulesDir = "$baseDir/src/Modules";
$pagesDir = "$baseDir/theme/src/Pages";

$modules = getDirectories($modulesDir);

foreach ($modules as $module) {
    $srcModuleDir = "$modulesDir/$module";
    $pageModuleDir = "$pagesDir/$module";
    $distDir = "$srcModuleDir/dist";

    if (is_dir($pageModuleDir)) {
        // Create the dist directory if it doesn't exist
        if (!is_dir($distDir)) {
            mkdir($distDir, 0755, true);
        }
        // Copy contents of theme/src/pages/{module} to src/modules/{module}/dist
        copyDirectory($pageModuleDir, $distDir);
        echo "Copied contents of $pageModuleDir to $distDir\n";
    } else {
        echo "Directory $pageModuleDir does not exist, skipping...\n";
    }
}

echo "Copying completed.\n";
