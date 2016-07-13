<?php
namespace atkbuilder;

use PEAR2\Console\CommandLine\Exception;

class FsManager
{
	public static  function ensureFolderExists($folder,$auth=0774)
	{
		if (trim($folder)=="")
			throw new Exception("Empty folder name received by ensureFolderExists");
			
		$folder = FsManager::normalizePath($folder);
		if(!file_exists($folder))
		{
      		mkdir($folder,true);
      		chmod($folder,$auth);
      		FsManager::chown($folder,"www-data:www-data");
      		return false;
		}
		return true;
	}
	
	public static function mkdir($folder)
	{
		$folder = FsManager::normalizePath($folder);
		$mkdir = "mkdir -p \"$folder\"";
		system($mkdir);		
	}
	
	public static function rmdir($folder)
	{
		$folder = FsManager::normalizePath($folder);
		$mkdir = "rm -rf \"$folder\"";
		system($mkdir);		
	}
	
	
	public static function assertFileNotExists($file)
	{
		$file = FsManager::normalizePath($file);
		if (file_exists($file))
			throw new Exception("File or directory allready exists:".$file);
	}
	
	public static function assertFileExists($file)
	{
		$file = FsManager::normalizePath($file);
		if (!file_exists($file))
			throw new Exception("File or directory does not exists:".$file);
	}
	
	public static function normalizePath($path)
	{
		$path=str_replace("/",  DIRECTORY_SEPARATOR, $path);
		$path=str_replace("//",  DIRECTORY_SEPARATOR, $path);
		$path=str_replace("\\",  DIRECTORY_SEPARATOR, $path);
		$path=str_replace("\\\\",  DIRECTORY_SEPARATOR, $path);
		return $path;
	}
	
	public static function copy($from, $to)
	{
		$from = FsManager::normalizePath($from);
		$to = FsManager::normalizePath($to);
		$GLOBALS['syslog']->debug("Copying from:".$from." to:".$to,1);
		$copy = " cp -R \"$from\" \"$to\"";
		$GLOBALS['syslog']->debug($copy,2);
		system($copy);
	}
	
	public static function chmod($from, $auth)
	{
		/*
		$from = FsManager::normalizePath($from);
		
		$GLOBALS['syslog']->debug("Chmod -R".$auth." ".$from." from:".$from,1);
		$chmod = " chmod -R ${auth} \"$from\" ";
		$GLOBALS['syslog']->debug($chmod,2);
		system($chmod);
    	$GLOBALS['syslog']->debug("Chown -R www-data:www-data $from from:".$from,1);
		$chmod = " chown -R www-data:www-data \"$from\" ";
		$GLOBALS['syslog']->debug($chmod,2);
		system($chmod);
		*/
	}
	
  public static function chown($from, $own)
	{
		/*
		$from = FsManager::normalizePath($from);
		
		$GLOBALS['syslog']->debug("Chown -R ".$own." ".$from." from:".$from,1);
		$chown = " chown -R ${own} \"$from\" ";
		$GLOBALS['syslog']->debug($chown,2);
		system($chown);
		*/
	}

	public static function filePutContents($file, $contents)
	{
		$file = FsManager::normalizePath($file);
		$bytes_written=file_put_contents($file, $contents);
		if ($bytes_written === false)
		{
			throw new Exception("Could'nt write file:"+$file);
		}
		chmod($file,0774);
	}
	
	public static function fileGetContents($file)
	{
		$file = FsManager::normalizePath($file);
		return file_get_contents($file);
	}
	
	public static function fileExists($file)
	{
		$file = FsManager::normalizePath($file);
		return file_exists($file);
	}
}
?>
