<?php

namespace atkbuilder;

use PEAR2\Console\CommandLine\Exception;

abstract class AbstractCodeCreator
{
	
	protected  function createFromTemplate($resource, $record, $destination)
	{
		$GLOBALS['syslog']->enter();
		$GLOBALS['syslog']->debug("Creating:".$resource." at:".$destination,2);
		$GLOBALS['syslog']->debug("Record:".var_export($record, true),4);
		$contents = $this->getResource($resource);
		$contents = $this->interpolate($record, $contents);
		FsManager::filePutContents($destination,$contents);
		$GLOBALS['syslog']->debug("Code generated for:".$destination."\n".$contents,4);
		$GLOBALS['syslog']->finish();
	}
	
	protected  function interpolate($record, $contents)
    {
    	foreach($record as $field => $value)
    	{
    		$search= '${'.trim($field).'}';
    		$contents = str_ireplace($search, $value, $contents);
    	}
       	return $contents;
    }
    
    protected  function getResource($resource)
    {
    	$source=$GLOBALS['syscfg']->cpbdir."/resources/".$resource;
			$GLOBALS['syslog']->debug("Reading resource from:".$source, 2);
    	$content = FsManager::fileGetContents($source);
			$GLOBALS['syslog']->debug("Resource Read:".$resource, 4);
			return $content;
	}
	

	protected function execSQL($query)
	{
		$conn = mysqli_connect($this->dbhost, $this->dbuser,$this->dbpass);
		if (!$conn)
			throw new Exception("Could not connect to database server with the supplied parameters. (User:".$this->dbuser." ,Pass:".$this->dbpass.")");
		
		$result = mysqli_query($query);
		$GLOBALS['syslog']->debug("ExecSql:".$query,1);
		$row = null;
		if ($result)
			@$row = mysqli_fetch_array($result);
		mysqli_close($conn);
		return $row;
	}
}
?>
