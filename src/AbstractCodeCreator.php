<?php
require_once 'CodePoint/cpFsManager.php';

abstract class cpAbstractCodeCreator
{
	
	protected  function createFromTemplate($resource, $record, $destination)
	{
		$GLOBALS['syslog']->enter();
		$GLOBALS['syslog']->debug("Creating:".$resource." at:".$destination,2);
		$GLOBALS['syslog']->debug("Record:".var_export($record, true),4);
		$contents = $this->getResource($resource);
		$contents = $this->interpolate($record, $contents);
		cpFsManager::filePutContents($destination,$contents);
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
    	return cpFsManager::fileGetContents($GLOBALS['syscfg']->cpbdir."/resources/".$resource);
	}
	

	protected function execSQL($query)
	{
		$conn = mysql_connect($this->dbhost, $this->dbuser,$this->dbpass);
		if (!$conn)
			throw new Exception("Could not connect to database server with the supplied parameters");
		$dbname = $this->dbname;
		$result = mysql_query($query);
		$GLOBALS['syslog']->debug("ExecSql:".$query,1);
		$row = null;
		if ($result)
			@$row = mysql_fetch_array($result);
		mysql_close($conn);
		return $row;
	}
}
?>