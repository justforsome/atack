<?php

use app\worker\Requester;
use app\worker\UserAgent;

spl_autoload_register(
    function(string $className)
    {
        $file = __DIR__ . '/' . preg_replace(
            '/^(app\\/)/i', '../',
            str_replace('\\', '/', $className)
            ) . '.php';
        include $file;
    }
);

$appDir = realpath($runtimeDir = __DIR__ . '/..');
$runtimeDir = $appDir . '/runtime';

if(!($runtimeExists = file_exists($runtimeDir))) {
    mkdir($runtimeDir, 0777);
}

// reading config; check for updates
chdir($appDir);

// read local app config
$localConfigFilename = $appDir . '/app-config.json';
if(!is_readable($localConfigFilename)) {
    die('Cannot read local config');
}
$localConfig = json_decode(file_get_contents($localConfigFilename), true);

// read remote config
$remoteConfig = json_decode(file_get_contents($localConfig['remoteConfigLocation']), true);

// read runtime config
$runtimeConfigFilename = $runtimeDir . '/config.json';
if(!file_exists($runtimeConfigFilename)) {
    // reduce risk of writing same config by multiple processes
    sleep(rand(0, 20));
}

if(file_exists($runtimeConfigFilename)) {
    if(is_readable($runtimeConfigFilename)) {
        $runtimeConfig = json_decode(file_get_contents($runtimeConfigFilename), true);
        $configLoaded = true;
    } else {
        unlink($runtimeConfigFilename);
        $configLoaded = false;
    }
} else {
    $configLoaded = false;
}

if(!$configLoaded) {
    $runtimeConfig = [
        'checkForUpdates' => true,
        'uid' => uniqid(),
    ];
    file_put_contents($runtimeConfigFilename, json_encode($runtimeConfig));

    // prevent from loading non-actual config
    return false;
}

// check for updates
if(
    isset($runtimeConfig['checkForUpdates']) && $runtimeConfig['checkForUpdates'] &&
    isset($remoteConfig['version'], $localConfig['version'])
) {
    if(version_compare($remoteConfig['version'], $localConfig['version'], '>')) {
        exec('git pull');
        exit();
    }
}

if($runtimeExists) {
    $files = scandir($runtimeDir);
    foreach($files as $file) {
        if(in_array($file, ['.', '..', 'config.json'])) {
            continue;
        }

        $fileParts = explode('-', $file);
        if(count($fileParts) != 2) {
            continue;
        }

        $filetime = $fileParts[0];
        if(!is_numeric($filetime) || (time() - $filetime) > 600) {
            try {
                unlink($runtimeDir . '/' . $file);
            } catch(\Exception $ex) {
            }
        }
    }
}

$filename = $runtimeDir . '/' . time() . '-' . dechex(rand(0, 0xFFFF));

try {
    $requester = new Requester(array_merge($localConfig, $runtimeConfig), $filename);
} catch(\Exception $ex) {
    echo "\033[41m " . $ex->getMessage() . " \033[0m\r\n";
}

if(!$requester->execute()) {
    sleep(1);
}
