<?php
class cpFsManager
{
	public static  function ensureFolderExists($folder,$auth=0774)
	{
		if (trim($folder)=="")
			throw new Exception("Empty folder name received by ensureFolderExists");
			
		$folder = cpFsManager::normalizePath($folder);
		if(!file_exists($folder))
		{
      mkdir($folder,true);
      chmod($folder,$auth);
      cpFsManager::chown($folder,"www-data:www-data");
      return false;
		}
		return true;
	}
	
	public static function mkdir($folder)
	{
		$folder = cpFsManager::normalizePath($folder);
		$mkdir = "mkdir -p \"$folder\"";
		system($mkdir);		
	}
	
	public static function rmdir($folder)
	{
		$folder = cpFsManager::normalizePath($folder);
		$mkdir = "rm -rf \"$folder\"";
		system($mkdir);		
	}
	
	
	public static function assertFileNotExists($file)
	{
		$file = cpFsManager::normalizePath($file);
		if (file_exists($file))
			throw new Exception("File or directory allready exists:".$file);
	}
	
	public static function assertFileExists($file)
	{
		$file = cpFsManager::normalizePath($file);
		if (!file_exists($file))
			throw new Exception("File or directory does not exists:".$file);
	}
	
	public static function normalizePath($path)
	{
		return str_replace("/",  DIRECTORY_SEPARATOR, $path);
	}
	
	public static function copy($from, $to)
	{
		$from = cpFsManager::normalizePath($from);
		$to = cpFsManager::normalizePath($to);
		$GLOBALS['syslog']->debug("Copying from:".$from." to:".$to,1);
		$copy = " cp -R \"$from\" \"$to\"";
		$GLOBALS['syslog']->debug($copy,2);
		system($copy);
	}
	
	public static function chmod($from, $auth)
	{
		$from = cpFsManager::normalizePath($from);
		
		$GLOBALS['syslog']->debug("Chmod -R".$auth." ".$from." from:".$from,1);
		$chmod = " chmod -R ${auth} \"$from\" ";
		$GLOBALS['syslog']->debug($chmod,2);
		system($chmod);
    $GLOBALS['syslog']->debug("Chown -R www-data:www-data $from from:".$from,1);
		$chmod = " chown -R www-data:www-data \"$from\" ";
		$GLOBALS['syslog']->debug($chmod,2);
		system($chmod);

	}
	
  public static function chown($from, $own)
	{
		$from = cpFsManager::normalizePath($from);
		
		$GLOBALS['syslog']->debug("Chown -R ".$own." ".$from." from:".$from,1);
		$chown = " chown -R ${own} \"$from\" ";
		$GLOBALS['syslog']->debug($chown,2);
		system($chown);
	}

	public static function filePutContents($file, $contents)
	{
		$file = cpFsManager::normalizePath($file);
		file_put_contents($file, $contents);
	}
	
	public static function fileGetContents($file)
	{
		$file = cpFsManager::normalizePath($file);
		return file_get_contents($file);
	}
	
	public static function fileExists($file)
	{
		$file = cpFsManager::normalizePath($file);
		return file_exists($file);
	}
}
?>
