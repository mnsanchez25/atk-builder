<?php
class Config 
{
	private $config;
	
	function cpConfig($cmdlne)
	{
		$this->config['cwd']=getcwd();
		$this->config['basedir']=getcwd();
		$this->config['cmdlne']=$cmdlne;
	}
	
	public function __get($name) 
	{
        if (array_key_exists($name, $this->config)) 
            return $this->config[$name];
        $trace = debug_backtrace();
        throw new Exception('Uknown config entry:'.$name." in". $trace[0]['file']." at line:".$trace[0]['line']);
        return null;
    }
    
	public function __set($name, $value) 
	{
		$this->config[$name] = $value;
    }	
}

?>