<?php

require __DIR__ . '/../vendor/autoload.php';

//define('WWW_DIR', dirname(__FILE__)); // path to the web root
//define('APP_DIR', '..'.DIRECTORY_SEPARATOR.dirname(__FILE__)); // path to the web root

if (!class_exists('Tester\Assert')) {
	echo "Install Nette Tester using `composer update --dev`\n";
	exit(1);
}

Tester\Environment::setup();

define('TMP_DIR', '/tmp/demo-app-tests');
date_default_timezone_set('Europe/Prague');

$loader = new Nette\Loaders\RobotLoader;

$loader->addDirectory(__DIR__ . '/../src')
	->setTempDirectory(__DIR__ . '/../temp')
	->register();
