<?php

namespace atkbuilder;


use PEAR2\Console\CommandLine\Exception;

class NewApp extends AbstractCodeCreator
{
	public function __construct($basedir, $appnme)
	{
		$GLOBALS['syslog']->enter();
		$this->basedir=$basedir;		
		$this->appnme=$appnme;
		$this->full_basedir = FsManager::normalizePath($this->basedir.DIRECTORY_SEPARATOR.$appnme);
		$this->dbname = trim($GLOBALS['syscfg']->cmdlne->command->options['dbname']);
		$this->dbname = trim($this->dbname) == "" ? $this->appnme:$this->dbname;
		$this->dbhost = trim($GLOBALS['syscfg']->cmdlne->command->options['dbhost']);
		$this->dbuser = trim($GLOBALS['syscfg']->cmdlne->command->options['dbuser']);
		$this->dbpass = trim($GLOBALS['syscfg']->cmdlne->command->options['dbpass']);
		
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
			FsManager::assertFileExists($this->basedir);
			FsManager::assertFileNotExists($this->full_basedir);
		} catch(Exception $e){
			throw new Exception($e->getMessage());
		}
		//if ($this->dbpass == null)
		//	throw new Exception("This option requires a database user and password provide it with -u and -p. -u defaults to root");
		FsManager::ensureFolderExists($this->basedir);
		$this->assertDatabaseNew();
		$this->extractFramework();
		$this->createDefFile();
		$this->createEnvFile();
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
		$record = array();
		$record["appnme"]=$this->appnme;
		$record["dbnme"]=$this->appnme;
		$record["dbusr"]=$this->appnme;
		$record["dbpas"]=$this->appnme;			 
		$this->createFromTemplate('templates'.DIRECTORY_SEPARATOR.'DefFile', $record, $this->full_basedir.DIRECTORY_SEPARATOR.'DefFile');	
		$GLOBALS['syslog']->finish();						
	}
	
	private function createEnvFile()
	{					
		$GLOBALS['syslog']->enter();
		$record = array();
		$record["appnme"]=$this->appnme;
		$record["dbhost"]=$this->dbhost;
		$record["dbnme"]=$this->appnme;
		$record["dbusr"]=$this->appnme;
		$record["dbpas"]=$this->appnme;			 
		$this->createFromTemplate('templates'.DIRECTORY_SEPARATOR.'.env.example', $record, $this->full_basedir.DIRECTORY_SEPARATOR.'.env');	
		$GLOBALS['syslog']->finish();						
	}
	
	private function extractFramework()
	{	
		$GLOBALS['syslog']->enter();	
		$dest=$this->full_basedir;				
		$command = "git clone https://github.com/Sintattica/atk-skeleton.git ".$dest;
		system($command);
		$GLOBALS['syslog']->finish();		
	}	
}
?>