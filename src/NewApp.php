<?php

require_once 'CodePoint/cpAbstractCodeCreator.php';

class cpNewApp extends cpAbstractCodeCreator
{
	public function __construct($basedir, $appnme)
	{
		$GLOBALS['syslog']->enter();
		$this->basedir=$basedir;		
		$this->appnme=$appnme;
		$this->full_basedir = cpFsManager::normalizePath($this->basedir.$appnme);
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
			cpFsManager::assertFileExists($this->basedir);
			cpFsManager::assertFileNotExists($this->full_basedir);
		} catch(Exception $e){
			throw new Exception($e->getMessage());
		}
		//if ($this->dbpass == null)
		//	throw new Exception("This option requires a database user and password provide it with -u and -p. -u defaults to root");
		cpFsManager::ensureFolderExists($this->basedir);
		$this->assertDatabaseNew();
		$this->extractFramework();
		$this->createDefFile();
		$this->createConfigInc();
		$GLOBALS['syslog']->finish();	
	}
	
	
	private function assertDatabaseNew()
	{
		$dbname=$this->dbname;
		$query = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '${dbname}'";
		$row = $this->execSQL($query);
		if ($row != false)
			throw new Exception("Database:".$dbname." allready exists");
		$query = "CREATE DATABASE ${dbname};";
		$row = $this->execSQL($query);
		$query = "GRANT ALL ON ${dbname}.* TO `${dbname}`@`localhost` identified by '${dbname}';";
		$row = $this->execSQL($query);
	}
	
	private function createDefFile()
	{					
		$GLOBALS['syslog']->enter();
		$record["appnme"]=$this->appnme;
		$record["dbnme"]=$this->appnme;
		$record["dbusr"]=$this->appnme;
		$record["dbpas"]=$this->appnme;			 
		$this->createFromTemplate('templates/DefFile', $record, $this->full_basedir.'/DefFile');	
		$GLOBALS['syslog']->finish();						
	}
	
	private function createConfigInc()
	{					
		$GLOBALS['syslog']->enter();
		$record["appnme"]=$this->appnme;
		$record["dbhost"]=$this->dbhost;
		$record["dbnme"]=$this->appnme;
		$record["dbusr"]=$this->appnme;
		$record["dbpas"]=$this->appnme;			 
		$this->createFromTemplate('templates/config.inc.php', $record, $this->full_basedir.'/config.inc.php');	
		$GLOBALS['syslog']->finish();						
	}
	
	private function extractFramework()
	{	
		$GLOBALS['syslog']->enter();	
		$source_root = $GLOBALS['syscfg']->cpbdir."/resources/craddle-1.0.0";
		$dest=$this->full_basedir;				
		cpFsManager::copy($source_root, $dest);
		cpFsManager::chmod($dest, "774");
		$GLOBALS['syslog']->finish();		
	}	
}
?>