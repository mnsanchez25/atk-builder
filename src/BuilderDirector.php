<?php
/**
 * This file is part of atk code generator
 */

use DataDictionary;
use CodeBuilder; 
use NewApp;
use DelApp;
use RunGen;

class BuilderDirector 
{
	public function __call($method_name, $arguments) 
	{
		$GLOBALS['syslog']->enter();
		if (!method_exists($this, $method_name))
			throw new Exception('Uknown command:'.$method_name);
         $this->$method_name($arguments);
         $GLOBALS['syslog']->finish();
    }
	
     function newapp($arguments)
    {
    	$GLOBALS['syslog']->enter();
		$base_dir = $GLOBALS['syscfg']->cmdlne->command->options['basedir'];				
		$base_dir = FsManager::normalizePath($base_dir);
		$GLOBALS['syslog']->debug("New App base_dir:".$base_dir,1);
		$appnme = $GLOBALS['syscfg']->cmdlne->command->args['appnme'];		
		$appcrt = new NewApp($base_dir, $appnme);	    
		$appcrt->build();
		$GLOBALS['syslog']->finish();		
    }
	
     function delapp($arguments)
    {
    	$GLOBALS['syslog']->enter();
		$base_dir = $GLOBALS['syscfg']->cmdlne->command->options['basedir'];				
		$base_dir = FsManager::normalizePath($base_dir);
		$appnme = $GLOBALS['syscfg']->cmdlne->command->args['appnme'];		
		$appcrt = new DelApp($base_dir, $appnme);	    
		$appcrt->build();
		$GLOBALS['syslog']->finish();		
    }
	
     function rungen($arguments)
    {
    	$GLOBALS['syslog']->enter();
    	$base_dir = './';
    	$def_file = 'DefFile';
    	if (isset($GLOBALS['syscfg']->cmdlne->command->options['basedir']))
    		$base_dir = $GLOBALS['syscfg']->cmdlne->command->options['basedir'];
    	if (isset($GLOBALS['syscfg']->cmdlne->command->options['deffile']))
    		$def_file = $GLOBALS['syscfg']->cmdlne->command->options['deffile'];

		$base_dir = FsManager::normalizePath($base_dir);
		 
		$dict = new DataDictionary($def_file);
	    $builder = new RunGen($base_dir, $dict);
		$builder->build();
		$GLOBALS['syslog']->finish();		
    }
    
	 function dumpdd($arguments)
	{
		$GLOBALS['syslog']->enter();
		$def_file = "./DefFile";
		if (isset($GLOBALS['syscfg']->cmdlne->options['deffile']))
			$def_file = $GLOBALS['syscfg']->cmdlne->options['deffile']; 
		$dict = new DataDictionary($def_file);
		$dict->dumpDictionary();
		$GLOBALS['syslog']->finish();	
	}	
	
}
?>
