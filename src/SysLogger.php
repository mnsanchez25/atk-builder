<?php
namespace atkbuilder;

define("LOG_DEBUG" 	,		4);

class SysLogger
{

	private $syscfg;
	private $loglvl;
	
	public function __construct($loglvl)
	{
		$this->loglvl=$loglvl;		
	}
	
		
	public function log($message="", $level=0)
	{
		$prefix="";
		if ($level >= LOG_DEBUG)
		{
			$bt = debug_backtrace();
			$prefix .= $prefix."[".$bt[1]['file']."#".$bt[1]['function']."@".$bt[0]['line']."] ";	
		} 
		$msg=$prefix.$message."\n";
		if ($level <= $this->loglvl)
			print($msg);
	}
	
	public function debug($message="", $level=0)
	{
		$prefix="";
		if ($level >= LOG_DEBUG)
		{
			$bt = debug_backtrace();
			$prefix .= $prefix."[".$bt[1]['file']."#".$bt[1]['function']."@".$bt[0]['line']."] ";	
		} 
		$msg=$prefix.$message."\n";
		if ($level <= $this->loglvl)
			print($msg);
	}
	public function enter($message="")
	{
		
		$prefix="";
		
		if ($this->loglvl >= LOG_DEBUG)
		{
			$bt = debug_backtrace();
			$file = basename(isset($bt[0]['file']) ? $bt[0]['file']:"?");
			$function = isset($bt[1]['function']) ? $bt[1]['function']:'';
			$prefix .= $prefix."[".$file."#".$function."@".$bt[0]['line']."] Entering Method";	
			$msg=$prefix.$message."\n";
			print $msg;
		}
	}
	
	public function finish($message="")
	{
		$prefix="";
		if ($this->loglvl >= LOG_DEBUG)
		{
			$bt = debug_backtrace();
			$file =basename(isset($bt[0]['file']) ? $bt[0]['file']:"?");
			$function = isset($bt[1]['function']) ? $bt[1]['function']:'';
			$prefix .= $prefix."[".$file."#".$function."@".$bt[0]['line']."] Exiting Method";	
			$msg=$prefix.$message."\n";
			print $msg;
		}
	}
	
	public function abort($message)
	{
		throw new Exception($message);
	}
}

?>