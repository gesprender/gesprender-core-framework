<?php

function ddd($var, $json_flag = false)
{
    if ($json_flag) {
        echo json_encode($var);
        die;
    }
    var_dump($var);
    die;
}

function getVersion(): string
{
    $pathComposerJson = __DIR__ . "/../Project/composer.json";
    if (file_exists($pathComposerJson)) {
        $composerJson = json_decode(file_get_contents($pathComposerJson), true);
        return $composerJson['version'] ?? "0.0.1";
    }else{
        $pathComposerJsonDefault = __DIR__ . "/../composer.json";
        $composerJson = json_decode(file_get_contents($pathComposerJsonDefault), true);
        return $composerJson['version'] ?? "0.0.1";
    }
}
