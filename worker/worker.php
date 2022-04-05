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

$runtimeDir = __DIR__ . '/../runtime/';
if(!file_exists($runtimeDir)) {
    mkdir($runtimeDir, 0777);
} else {
    $files = scandir($runtimeDir);
    foreach($files as $file) {
        if(in_array($file, ['.', '..'])) {
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

$filename = $runtimeDir . time() . '-' . dechex(rand(0, 0xFFFF));

try {
    $requester = new Requester($filename);
} catch(\Exception $ex) {
    echo "\033[41m " . $ex->getMessage() . " \033[0m\r\n";
}

if(!$requester->execute()) {
    sleep(1);
}
