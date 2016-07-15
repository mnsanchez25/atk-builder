<?php
require_once dirname(__FILE__). DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

use PEAR2\Console\CommandLine;
use atkbuilder\Config;
use atkbuilder\BuilderDirector;
use atkbuilder\SysLogger;

error_reporting(E_ERROR | 	 E_PARSE);


$xmlfile = dirname(__FILE__) . 
			DIRECTORY_SEPARATOR .
			'..'.
			DIRECTORY_SEPARATOR .
		    'resources'
			. DIRECTORY_SEPARATOR .
			'cmdlne'
			. DIRECTORY_SEPARATOR .
			'atk-builder_cmdlne.xml';

$cmdLineParser = PEAR2\Console\CommandLine::fromXmlFile($xmlfile);

try {
	$cmdlne = $cmdLineParser->parse();
}
catch (Exception $exc){
	$cmdLineParser->displayError($exc->getMessage());
	exit(1);
}

$GLOBALS['syscfg'] = new Config($cmdlne);
$GLOBALS['syslog'] = new SysLogger($cmdlne->options['verbose']);
$GLOBALS['syscfg']->cpbdir=dirname(dirname(__FILE__));

try {
	$BuilderDirector = new BuilderDirector();
	$command = $cmdlne->command_name;
	$command = $command == NULL ? 'rungen': $command;
	$BuilderDirector->$command('');
}
catch (Exception $exc){
	print($exc->getMessage()."\n");
	exit(1);
}


exit(0);
?>
