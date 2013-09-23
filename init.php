<?php
$webmanagerDir=dirname(__DIR__).'/';
$baseDir=dirname($webmanagerDir).'/';

echo shell_exec('cd '.escapeshellarg($webmanagerDir).' && ln -s src/enhance.php');

$coreDir=$baseDir.'core/dev/';
file_put_contents($webmanagerDir.'cli.php',"<?php
define('DS', DIRECTORY_SEPARATOR);
define('CORE','".$coreDir."');
define('CLIBS','".$baseDir.".'core/libs/dev/');
define('APP', __DIR__.DS.'dev'.DS);
".'unset($argv[0]);
$action=array_shift($argv);'."
include CORE.'cli.php';");

mkdir($webmanagerDir.'config');
echo shell_exec('cd '.escapeshellarg($webmanagerDir).'src && ln -s ../config && cd .. && ln -s src/db');

$env=include $baseDir.'core/env.php';

file_put_contents($webmanagerDir.'config/secure.php','<?php return array();');
file_put_contents($webmanagerDir.'config/cookies.php','<?php return null;');
file_put_contents($webmanagerDir.'config/enhance.php',"<?php return array(
	'base'=>array('i18n'),
);");
file_put_contents($webmanagerDir.'config/_.php',"<?php return array(
	'project_name'=>'webmanager',
	'projectName'=>'Springbok WebManager',
	'availableLangs'=>array('fr'),
	
	'secure'=>array(
		'crypt_key'=>'".str_replace("'",'0',uniqid('',true))."',
	)
);");

file_put_contents($webmanagerDir.'config/_'.$env.'.php',"<?php return array(
	'siteUrl'=>array('index'=>'http://localhost/'),
	'php_doc_dir'=>dirname(__DIR__).'/php-chunked-xhtml/',
	
	'db'=>array(
		'default'=>array('type'=>'SQLite', 'file'=>dirname(__DIR__).'/webmanager.db',),
	),
	'generate'=>array('default'=>true),
);");

file_put_contents($webmanagerDir.'config/routes.php',"<?php return array(
	'/'=>array('Site::index'),
	'/phpdoc/:file'=>array('Phpdoc::index'),
	'/editor/:id/:action/*'=>array('Editor::!'),
	'/editor/ace/:file'=>array('Editor::ace',array('file'=>'.*')),
	'/:controller(/:action/*)?'=>array('!::!'),
);");

file_put_contents($webmanagerDir.'config/routes-langs.php',"<?php return array(
	'phpdoc'=>array('fr'=>'phpdoc','es'=>'phpdoc','it'=>'phpdoc'),
	'editor'=>array('fr'=>'editeur','es'=>'editor','it'=>'editor'),
	'ace'=>array('fr'=>'ace','es'=>'ace','it'=>'ace'),
);");

//file_put_contents($webmanagerDir.'config/routes-langs.php','<?php rerturn array();');
