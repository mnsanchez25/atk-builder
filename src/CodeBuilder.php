<?php
/**
 * This file is part of atk code generator
 *
 */	

class cpCodeBuilder 
{
	private $dd;
	private $modules_dir=null; 
	
	function cpCodeBuilder($data_dictionary)
	{
		$this->dd=$data_dictionary->dd;
		$this->base_dir=getcwd();
		$this->parent_node="cpNode";
	}
	
	private function say($message, $level=0)
	{
		$GLOBALS['syslog']->log($message, $level);
	}
	
	public function buildAll()
	{
		$GLOBALS['syslog']->enter();
		$this->ds=DIRECTORY_SEPARATOR;
		$this->modules_dir=$this->base_dir.$this->ds."modules";
		$this->ensureFolderExists($this->modules_dir);
		
		foreach ($this->dd['modules'] as $module_name => $module_contents) 
			$this->processModule($module_name, $module_contents);
		$this->modules_build_config_modules_base();
		$GLOBALS['syslog']->finish();
	}
	
	private function modules_build_config_modules_base()
	{
		$GLOBALS['syslog']->enter();
		$config_modules_base_file=$this->modules_dir.$this->ds."config.modules_base.inc";
		$this->say("...Building:".$config_modules_base_file);
		$file_contents="<?php\n";
		$file_contents.="\n";
		foreach ($this->dd['modules'] as $module_name => $module_contents) 
			$file_contents.="\tmodule(\"".$module_name."\");\n";
    	$file_contents.="\n";
		$file_contents.="?>\n";
		file_put_contents($config_modules_base_file, $file_contents);
		$GLOBALS['syslog']->finish();	
	}
	
	private function processModule($module_name, $module_contents)
	{
		$GLOBALS['syslog']->enter();
		$module_name=trim($module_name);
		$this->say("Processing Module: ".$module_name);
		$module_dir=$this->modules_dir.$this->ds.$module_name;	
		$this->ensureFolderExists($module_dir);
		//Build module structure
		$folders=array('install', 'languages', 'themes');
		foreach ($folders as $folder) 
			$this->ensureFolderExists($module_dir.$this->ds.$folder);
		//Build nodes
		foreach ($this->dd['modules'][$module_name]['nodes'] as $node_name => $node_contents)
			$this->processNode($module_dir,$node_name,$node_contents);
		//Build module inc 	
		$module_inc_custom_file=$module_dir.$this->ds."module.inc";
		if(!file_exists($module_inc_custom_file))
		{
			$module_custom_contents=$this->buildModuleCustomContents($module_name);
			file_put_contents($module_inc_custom_file, $module_custom_contents);
			chmod($module_inc_custom_file,0774);
		}
		$module_base_file=$module_dir.$this->ds.$module_name."_base.inc";
		$module_base_contents= $this->buildModuleBaseContents($module_name, $module_dir, $module_contents);
		file_put_contents($module_base_file, $module_base_contents);
		chmod($module_base_file,0774);
		//Build Language files				
		$this->modules_build_language_files($module_dir, $module_contents);
		//Build install files
		$this->modules_build_install_inc($module_name, $module_dir, $module_contents);
		$GLOBALS['syslog']->finish();
	}
	
	private function modules_build_install_inc($module_name, $module_dir, $module_contents)
	{
		$GLOBALS['syslog']->enter();
		$install_dir=$module_dir.$this->ds."install";
		$install_file=$install_dir.$this->ds."/install.inc";
		$vs = $this->getModuleVersionNumberAndSignature($module_dir, $module_contents);
		//Build Install file
		$ins_file_contents="<?php\n";
		$ins_file_contents.="\n";
		foreach ($module_contents['nodes'] as $node => $node_def)
		{				
			if ($node_def['install']==true)
			{	
				$node_name = $node_def['id'];				
				$ins_file_contents.="\t\$setup->installNode(\"".$module_name.".".$node_name."_base\");\n";				
			}		
		}
    	$ins_file_contents.="\n";
		$ins_file_contents.="\t\$setup->setVersion(".trim($vs['version']).");\n";
		$ins_file_contents.="?>\n";
		file_put_contents($install_file, $ins_file_contents);
		if ($vs['version'] > 1)
		{
			$patch_file=$install_dir.$this->ds."/patch-".trim($vs['version'].".inc");
			file_put_contents($patch_file, "<?php\n\n?>");
		}
		$this->putModuleVersionNumberAndSignature($module_dir, $vs);
		$GLOBALS['syslog']->finish();
	}
	
	private function putModuleVersionNumberAndSignature($module_dir, $vs)
	{
		$GLOBALS['syslog']->enter();
		$signature_file=$lang_file=$module_dir.$this->ds."module.sgn";
		file_put_contents($signature_file, trim($vs['version']).':'.$vs['signature']);
		$GLOBALS['syslog']->finish();
	}
	
	private function getModuleVersionNumberAndSignature($module_dir, $module_contents)
	{
		$GLOBALS['syslog']->enter();
		//Calc a signature for the modul it will be used to check if the node needs
		//To change to a upper version number
		$signature= md5(var_export($module_contents['attrs'],true));
		$signature_file=$lang_file=$module_dir.$this->ds."module.sgn";
		if(!file_exists($signature_file))
		{
			file_put_contents($signature_file, '1:'.$signature);
			return array('version' => 1, 'signature' => $signature);
		}
		$sgn_file_contents=file_get_contents($signature_file);
		list($version_number,$previous_signature)=split(":",$sgn_file_contents);		
		if ($signature != $previous_signature)		
			array('version' => $version_number++, 'signature' => $signature);
		return array('version' => $version_number, 'signature' => $signature);
		$GLOBALS['syslog']->finish();
	}
	
	private function modules_build_language_files($module_dir,  $module_contents)
	{
		$GLOBALS['syslog']->enter();
		$languages=$this->dd['lnglst'];	
		foreach ($languages as $lng) 
		{			
			$lang_file_base=$module_dir.$this->ds."languages".$this->ds.$lng.".lng";						
			$this->say("Building language file:".$lang_file);
			$lang_file_contents="<?php\n";
			$lang_file_contents.="\$".strtolower($lng)."=array(\n";
				
			foreach($module_contents['languages'] as  $entry)
				$lang_file_contents.="\t\t".$entry."\n";				
			
			$lang_custom_file=$module_dir.$this->ds."languages".$this->ds.$lng."_custom.lng";
			if(!file_exists($lang_custom_file))
			{
				file_put_contents($lang_custom_file, "");
				chmod($lang_custom_file,0774);
			}
			$lang_custom_file_contents=file_get_contents($lang_custom_file);
			$lang_file_contents.=$lang_custom_file_contents;
			$lang_file_contents.=");\n";
			$lang_file_contents.="\?\>\n";
			file_put_contents($lang_file_base, $lang_file_contents);
			chmod($lang_file_base,0774);			
		}
		$GLOBALS['syslog']->finish();	
	}
	
	private function ensureFolderExists($folder)
	{
		if (trim($folder)=="")
			$this->abort("Empty folder name received by ensureFolderExists");
			
		$this->say("Checking existence of: ".$folder);
		if(!file_exists($folder))
		{
			$this->say("...creating: ".$folder);
			mkdir($folder);
			$this->say("...chmod 0774 ".$folder);
			chmod($folder,0774);
			return false;
		}
		return true;
	}
	
	private function buildModuleBaseContents($module_name, $module_dir, $module_contents)
	{
		$GLOBALS['syslog']->enter();		
		$label=$module_contents['description'];	
		$module_inc_contents="<?php\n";
		$module_inc_contents.=$this->getFileHeader()."\n";
		$module_inc_contents.="class mod_".$module_name."_base extends cpModule\n";
		$module_inc_contents.="{\n";
		$module_inc_contents.="\tvar \$module=\"$module_name\";\n\n";
		if ($module_contents['menu'])
		{
			$module_inc_contents.="\tfunction getMenuItems()\n";
			$module_inc_contents.="\t{\n";
			$module_inc_contents.="\t\t\$menu_label=atktext(\"".$label."\");\n";
			$module_inc_contents.="\t\t\$enable=array(\n";
			foreach ($module_contents['nodes'] as $node => $node_def)
				$module_inc_contents.="\t\t\t\t\t\t\"".$node_def['module'].".".$node_def['id']."\", \"".$node_def['actions'][0]." \", \n";
	 	
			$module_inc_contents.="\t\t\t\t\t\t);\n";
			$module_inc_contents.="\t\t\$this->menuitem(\$menu_label,\"\", \"main\",\$enable);\n";
			foreach ($module_contents['nodes'] as $node => $node_def)
			{
				$node_desc = $node_def['description'];
				$action = $node_def['actions'][0];
				$url="dispatch_url(\"".$node_def['module'].".".$node_def['id']."\",\"$action\")";
				$module_inc_contents.="\t\t\$this->menuitem(atktext(\"$node_desc\"),$url, \$menu_label);\n";
			}	 	
			$module_inc_contents.="\t}\n\n";
		}
		$module_inc_contents.="\tfunction getNodes()\n";
		$module_inc_contents.="\t{\n";
		foreach ($module_contents['nodes'] as $node => $node_def)
			{
				//print_r($node_def['actions']);print("\n");
				$node = $node_def['id'];
				$auth_array="array('".implode("', '", $node_def['actions'])."')";
				$module_inc_contents.="\t\tregisterNode(\$module.\"$node\", $auth_array);\n";
			}	 	
		$module_inc_contents.="\t}\n\n";
		$module_inc_contents.="\tfunction search(\$expression)\n";
		$module_inc_contents.="\t{\n";
		$module_inc_contents.="\t\t\$results=array();\n";
		foreach ($module_contents['nodes'] as $node => $node_def)
		{				
			if ($node_def['search']==true)
			{	
				$node = $node_def['id'];
				$node_label = $node_def['description'];
				$module_inc_contents.="\t\t\$node = &atkGetNode(\$this->module.\".$node\");\n";
				$module_inc_contents.="\t\t\$results[\"$node_label\"] = \$this->recLinks(\$node->searchDb(\$expression),\$this->module.\".$node\");\n";
			}		
		}
		$module_inc_contents.="\t\treturn \$results;\n";
		$module_inc_contents.="\t}\n\n";
		$module_inc_contents.="}\n";
		return $module_inc_contents;
		$GLOBALS['syslog']->finish();
	}
	
	private function buildModuleCustomContents($mod_name, $mod_dir, $def)
	{
		$GLOBALS['syslog']->enter();
		$output="<?php\n";
		$output.=$this->getFileHeader()."\n";
		$output.="include_once(\"".$mod_name."_base.inc\"); \n";
		$output.="class mod_".$mod_name." extends mod_".$mod_name."_base \n";
		$output.="{\n";
		$output.="\tfunction mod_$mod_name(\$name=\"\")\n";
		$output.="\t{\n";
		$output.="\t\t\$class_name =  get_class(\$this);\n";
		$output.="\t\tlist(\$module_prefix,\$this->module_name) = split(\"mod_\",\$class_name); \n";
		$output.="\t\t\$this->cpModule(\$this->module_name); ;\n";
		$output.="\t}\n\n";
		$output.="}\n";
		$output.="?>\n";
		$GLOBALS['syslog']->finish();
		return $output;		
	}
	
	private function processNode($module_dir, $node_name, $node_contents)
	{
		$GLOBALS['syslog']->enter();
		$node_name=trim($node_name);
		$this->say("..Processing Node: ".$node_name);
		
		$node_custom_file=$module_dir.$this->ds."class.".$node_name.".inc";
		if(!file_exists($node_custom_file))
		{
			$node_custom_contents=$this->buildNodeCustomContents($node_name, $node_contents);
			file_put_contents($node_custom_file, $node_custom_contents);
			chmod($node_custom_file,0774);
		}
		$node_base_file=$module_dir.$this->ds."class.".$node_name."_base.inc";
		$node_base_contents=$this->buildNodeBaseContents($node_name, $node_contents);
		file_put_contents($node_base_file, $node_base_contents);
		chmod($node_base_file,0774);
		$GLOBALS['syslog']->finish();	
	}
	
	private function buildNodeBaseContents($node_name, $node_contents)
	{
		$GLOBALS['syslog']->enter();
		$output="<?php\n";
		$output.=$this->getFileHeader()."\n";
		$output.="class ".$node_name."_base extends $this->parent_node \n";
		$output.="{\n";
		$output.="\tfunction ".$node_name."_base()\n";
		$output.="\t{\n";
		$node_flags=$node_contents['flags'];
		$sep=', ';
		if ($node_flags =='')
				$sep='';
		$output.="\t\t\$this->$this->parent_node(\"".$node_name."_base\"$sep $node_flags);\n";
		$count=10;
		foreach ($node_contents['attributes'] as $at_name => $at_def)
		{
			$at_name=trim($at_name);
			$tab=$at_def['tabs'];
			$params=$at_def['params'];
			$sep=', ';
			if ($params =='')
				$sep='';
			$atkatr=$at_def['type']."('".$at_name."'".$sep.$params.")";
			$output.="\t\t\$this->add(new $atkatr, $tab, $count);\n";
			$count = $count + 10;	
		}
		$output.="\t}\n";
		$output.="}\n";
		$output.="?>\n";
		$GLOBALS['syslog']->finish();
		return $output;
	}
	
	private function buildNodeCustomContents($node_name, $node_contents)
	{
		$GLOBALS['syslog']->enter();
		$output="<?php\n";
		$output.=$this->getFileHeader()."\n";
		$output.="\n";
		$output.="include_once(\"class.".$node_name."_base.inc\");\n";
		$output.="\n";
		$output.="class ".$node_name." extends ".$node_name."_base \n";
		$output.="{\n";
		$output.="\tfunction ".$node_name."()\n";
		$output.="\t{\n";
		$output.="\t\t\$this->".$node_name."_base(\"$node_name\");\n";
		$output.="\t}\n";
		$output.="}\n";
		$output.="?>\n";	
		$GLOBALS['syslog']->finish();
		return $output;
	}
	
	private function getFileHeader()
	{
		$GLOBALS['syslog']->enter();
		$output.="/**\n";
		$output.=" *\n";
		$output.=" *\n";
		$output.=" **/\n";
		$GLOBALS['syslog']->finish();		
		return $output;		
	}
}
?>
