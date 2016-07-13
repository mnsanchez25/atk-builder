<?php

namespace atkbuilder;

use atkbuilder\AbstractCodeCreator;

class DelApp extends AbstractCodeCreator
{
	public function __construct($basedir, $appnme)
	{
		$GLOBALS['syslog']->enter();
		$this->basedir=$basedir;		
		$this->appnme=$appnme;
		$this->full_basedir = FsManager::normalizePath($this->basedir.$appnme);
		$this->dbname = trim($def_file = $GLOBALS['syscfg']->cmdlne->command->options['dbname']);
		$this->dbname =  trim($this->dbname) == "" ? $this->appnme:$this->dbname;
		$this->dbhost = trim($def_file = $GLOBALS['syscfg']->cmdlne->command->options['dbhost']);
		$this->dbuser = trim($def_file = $GLOBALS['syscfg']->cmdlne->command->options['dbuser']);
		$this->dbpass = trim($def_file = $GLOBALS['syscfg']->cmdlne->command->options['dbpass']);
		
		$GLOBALS['syslog']->finish();
	}
	
	/**
	 * Builds the new application in the base_dir passed to the creator
	 * using the def file passed in the creator.
	 */
	public function build()
	{
		$GLOBALS['syslog']->enter();
		try{ 
			FsManager::assertFileExists($this->full_basedir);
		} catch(Exception $e){
			print($e->getMessage());
		}
		//if ($this->dbpass == null)
		//	throw new Exception("This option requires a database user and password provide it with -u and -p. -u defaults to root");
		FsManager::rmdir($this->full_basedir);
		$this->delDatabase();
		
		$GLOBALS['syslog']->finish();	
	}
	
	
	private function delDatabase()
	{
		$dbname=$this->dbname;
		$query = "DROP DATABASE ${dbname};";
		$row = $this->execSQL($query);
	}
}
?>