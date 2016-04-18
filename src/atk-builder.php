<?php
require_once 'vendor/autoload.php';


namespace atk-builder {
	

use PEAR2\Console\CommandLine;

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

$GLOBALS['syscfg'] = new cpConfig($cmdlne);
$GLOBALS['syslog'] = new cpSysLogger($cmdlne->options['verbose']);
$GLOBALS['syscfg']->cpbdir=dirname(__FILE__);

try {
	$cpBuilderDirector = new BuilderDirector();
	$command = $cmdlne->command_name;
	$command = $command == NULL ? 'rungen': $command;
	$cpBuilderDirector->$command('');
}
catch (Exception $exc){
	print($exc->getMessage()."\n");
	exit(1);
}


exit(0);
?>
