<?php
namespace atkbuilder;

use PEAR2\Console\CommandLine\Exception;
use Phar;

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

	public static function symLink($from, $to)
	{
		$from = FsManager::normalizePath($from);
		$to = FsManager::normalizePath($to);
		symlink($from, $to);
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

	public static function copyr($source, $dest)
	{
		// Check for symlinks
		if (is_link($source)) 
		{
			return symlink(readlink($source), $dest);
		}	

		// Simple copy for a file
		if (is_file($source)) 
		{
			return copy($source, $dest);
		}

		// Make destination directory
		if (!is_dir($dest)) 
		{
			mkdir($dest);
		}

		// Loop through the folder
		$dir = dir($source);
		while (false !== $entry = $dir->read()) 
		{
			// Skip pointers
			if ($entry == '.' || $entry == '..') 
			{
				continue;
			}

			// Deep copy directories
			FsManager::copyr("$source/$entry", "$dest/$entry");
		}

		// Clean up
		$dir->close();
		return true;
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
		//$file = FsManager::normalizePath($file);
		$GLOBALS['syslog']->debug("Getting contents of file:".$file,3);
		///DIRTY CODE I don't fully understand WHY file_get_contents doesn't read the file when 
		//the path is passed. This "search" works but I DO NOT LIKE IT
		$target_file=basename($file);
		$GLOBALS['syslog']->debug("Target file:".$target_file,3);
		$dirstr = dirname($file);
		$GLOBALS['syslog']->debug("Dir str:".$dirstr,3);
		$dir = dir($dirstr);
		$contents = "";
		$contexto = stream_context_create(array('phar' =>
																	array('metadata' => array('user' => 'cellog')
																)));
		while (false !== $entry = $dir->read()) 
		{
			if ($entry == $target_file)
			{
				$fullname = 	$dirstr.DIRECTORY_SEPARATOR.$entry;
				$GLOBALS['syslog']->debug("Fullname:".$fullname,3);
				$contents = file_get_contents($fullname, false, $contexto);
			}	

		}
		return $contents;
	}
 
	public static function fileExists($file)
	{
		$file = FsManager::normalizePath($file);
		return file_exists($file);
	}
}
?>
