<?php 

// Calculate the base directory
$config['root'] = explode('/', __DIR__);
array_pop($config['root']);
$config['root'] = implode('/', $config['root']);

$config['host'] = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://{$_SERVER["SERVER_NAME"]}" : "http://{$_SERVER["SERVER_NAME"]}";

?>
