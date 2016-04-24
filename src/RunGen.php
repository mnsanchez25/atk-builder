<?php

namespace atkbuilder;

define(DS, DIRECTORY_SEPARATOR);

class RunGen extends AbstractCodeCreator
{
	public function __construct($basedir, DataDictionary $dd)
	{
		$GLOBALS['syslog']->enter();
		$this->data_dict=$dd;
		$this->dd=$dd->dd;
		$this->parent_node="Node";
		$this->basedir=$basedir;	
		$this->modules_dir=$this->basedir.DS."App".DS."Modules";	
		$GLOBALS['syslog']->finish();
	}
	
	public function build()
	{
		$GLOBALS['syslog']->enter();
		FsManager::ensureFolderExists($this->modules_dir);
		foreach ($this->dd['modules'] as $module_name => $module_def) 
			$this->processModule($module_name, $module_def);
		$this->modules_build_config_modules_base();
		$GLOBALS['syslog']->finish();	
	}
	
	private function processModule($module_name, $module_def)
	{
		$GLOBALS['syslog']->enter();
		$module_name=trim($module_name);
		$GLOBALS['syslog']->debug("Processing Module: ".$module_name, 1);
		$module_dir=$this->modules_dir.DS.ucfirst($module_name);	
		FsManager::ensureFolderExists($module_dir);
		//Build module structure
		$folders=array('install', 'languages', 'themes');
		foreach ($folders as $folder) 
			FsManager::ensureFolderExists($module_dir.DS.$folder);
		//Build nodes
		foreach ($this->dd['modules'][$module_name]['nodes'] as $node_name => $node_contents)
			$this->processNode($module_dir,$node_name,$node_contents);
		//Build module class 	
		$module_custom_file=$module_dir.DS."Module.php";
		if(!FsManager::fileExists($module_custom_file))
			$this->buildModuleCustomFile($module_name, $module_dir, $module_def, $module_custom_file);
			
		
		$module_base_file=$module_dir.DS.ucfirst($module_name)."_base.php";
		$this->buildModuleBase($module_name, $module_dir, $module_def, $module_base_file);
		//Build Language files				
		$this->modules_build_language_files($module_dir, $module_def);
		//Build install files
		$this->modules_build_install_inc($module_name, $module_dir, $module_def);
		$GLOBALS['syslog']->finish();
	}
	
	private function buildModuleCustomFile($mod_name, $mod_dir, $def, $destination)
	{
		$GLOBALS['syslog']->enter();
		$record = [];
		$record['modnme']=$mod_name;
		$this->createFromTemplate('templates'.DS.'module_custom', $record, $destination);
		$GLOBALS['syslog']->finish();		
	}
	
	private function modules_build_config_modules_base()
	{
		$GLOBALS['syslog']->enter();
		$config_modules_base_file=$this->modules_dir.DS."config.modules_base.inc";
		$GLOBALS['syslog']->debug("...Building:".$config_modules_base_file,1);
		$file_contents="<?php\n";
		$file_contents.="\n";
		foreach ($this->dd['modules'] as $module_name => $module_contents) 
			$file_contents.="\tApp\\Modules\\".ucfirst($module_name)."\\Module::class,\n";
    	$file_contents.="\n";
		$file_contents.="?>\n";
		FsManager::filePutContents($config_modules_base_file, $file_contents);
		$GLOBALS['syslog']->finish();	
	}
	
	
	private function modules_build_install_inc($module_name, $module_dir, $module_contents)
	{
		$GLOBALS['syslog']->enter();
		$install_dir=$module_dir."/install";
		$install_file=$install_dir."/install.inc";
		$vs = $this->getModuleVersionNumberAndSignature($module_dir, $module_contents);
		//Build Install file
		$record['ndelst']='';
		foreach ($module_contents['nodes'] as $node => $node_def)
		{				
			if ($node_def['install']==true)
			{	
				$node_name = $node_def['id'];				
				$record['ndelst'].="\t\$setup->installNode(\"".$module_name.".".$node_name."_base\");\n";				
			}		
		}
		$record['vernbr']=trim($vs['version']);
		$this->createFromTemplate('templates/install_inc',$record, $install_file);

		$after_file=$install_dir."/after_install.inc";
  	    if(!FsManager::fileExists($after_file))
		{
		  $this->createFromTemplate('templates/blank_php_file',$record, $after_file);
		}

		
		if ($vs['version'] > 1)
		{
			$record = $this->diff_with_previous_version($module_name,$module_contents);
			if ($record['has_patch'] == true)
				$GLOBALS['syslog']->log("DB Structure changed, plese run setup.php. Changed Node:".$node,0);
			$patch_file=$install_dir.DS."patch-".trim($vs['version'].".inc");
			$this->createFromTemplate('templates'.DS.'patch_file',$record, $patch_file);
		}
		$this->putModuleVersionNumberAndSignature($module_dir, $vs);
		$GLOBALS['syslog']->finish();
	}
	
	private function diff_with_previous_version($module_name, $module_contents)
	{
		$record = [];
		$record['node_install']='';
		$record['table_drop']='';
		$record['col_add']='';
		$record['col_drop']='';
		$record['has_patch']=false;
		//To auto generate diff patchs we need access to the Db.
		if (!isset($this->dd['db']['dbname']) || 
		!isset($this->dd['db']['user']) ||
		!isset($this->dd['db']['password']) )
		{
			$GLOBALS['syslog']->log("Patch file generation disabled, please fill db data in def:".$node,0);
			return $record;
		}	
		$db = new DbManager($this->dd);	
		$tables = $db->tablesForPrefix($module_name);
		//... Node/table in Model but not in Db => New node to install
		foreach ($module_contents['nodes'] as $node => $node_def)
		{
			if ($node_def['install']==true)
			{	
				$node = $node_def['id'];
				$table_name = $module_name.'_'.$node;
				if (array_search($table_name, $tables) === false)
				{
					$record['node_install'].="\t\$setup->installNode(\"".$module_name.".".$node."_base\");\n";
					$record['has_patch']=true;
					$GLOBALS['syslog']->log("New node to install:".$node,1);
				}
			}
		}	 	
		//... Tables in Db but not in model => Old Tables to drop
		$nodes_to_check=array();
		foreach ($tables as $table)
		{
			$node=preg_replace('/^'.$module_name.'_/', '', $table,1);
			if (!isset($module_contents['nodes'][$node]))
			{
				$record['table_drop'].="\t\$setup->dropTable(\"".$table."\");\n";
				$record['has_patch']=true;
				$GLOBALS['syslog']->log("old table to drop:".$table,1);
			}
			else 
			{
				//Nodes in Db => check Structure
				$nodes_to_check[$node]=$table;
			}				
		}
		//Nodes with corresponding tables, check Structure
		foreach ($nodes_to_check as $node => $table)
		{				
			$GLOBALS['syslog']->log("Check Node:".$node." with table:".$table."\n", 1);
			//Attributes in model but not in Db => Columns to Add
			$metadata = $db->metadata($table);
			foreach ($module_contents['nodes'][$node]['attributes'] as $attr => $attr_def)
			{
				if (strstr($attr,'hasmany') === false and  !isset($metadata[$attr]))
				{
					$type_a = $this->data_dict->sugestType($attr);
					$type= $type_a['dbtype'];
					$record['has_patch']=true;
					$record['col_add'].="\t\$setup->addColumn(\"".$table."\", \"".$attr."\", \"".$type."\", true);\n";
					$GLOBALS['syslog']->log("New col to add:".$attr." to table:".$table." DBtype:".$type,1);				
				}
			}			
			//Columns in table but no Attributes in model => Columns to Drop
			foreach (array_keys($metadata) as $col) 
			{
				//Don't check automatic fields present in Node
				if (array_search($col, array("id", "system_reserved", "created_at", "created_by", "updated_at", "updated_by")) === false)
				{
					if (!isset($module_contents['nodes'][$node]['attributes'][$col] ))
					{
						$record['col_drop'].="\t\$setup->dropColumn(\"".$table."\", \"".$col."\");\n";
						$record['has_patch']=true;
						$GLOBALS['syslog']->log("Old col to drop:".$col." from table:".$table,1);
					}
				}
			}
			//Attribute in model and in table but different types => Columns to Alter
		}		
		return $record;
	}
	
	private function putModuleVersionNumberAndSignature($module_dir, $vs)
	{
		$GLOBALS['syslog']->enter();
		$signature_file=$module_dir.DS."module.sgn";
		FsManager::filePutContents($signature_file, trim($vs['version']).':'.$vs['signature']);
		$GLOBALS['syslog']->finish();
	}
	
	private function getModuleVersionNumberAndSignature($module_dir, $module_contents)
	{
		$GLOBALS['syslog']->enter();
		//Calc a signature for the modul it will be used to check if the node needs
		//To change to a upper version number
		$signature= md5(var_export($module_contents['attrs'],true));
		$signature_file=$module_dir.DS."module.sgn";
		if(!FsManager::fileExists($signature_file))
		{
			FsManager::filePutContents($signature_file, '1:'.$signature);
			return array('version' => 1, 'signature' => $signature);
		}
		$sgn_file_contents=file_get_contents($signature_file);
		list($version_number,$previous_signature)=explode(":",$sgn_file_contents);		
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
      $record=array();
			$lang_file_base=$module_dir."/languages/".$lng.".lng";						
			$GLOBALS['syslog']->debug("Building language file:".$lang_file_base,1);
			$record['lngide'] = strtolower($lng);
			$record['lsttrl'] = '';
			foreach($module_contents['languages'] as  $entry)
				$record['lsttrl'] .= "\t\t".$entry."\n";				
			$lang_custom_file=$module_dir.DS."languages".DS.$lng."_custom.lng";
			if(!FsManager::fileExists($lang_custom_file))
				$this->createFromTemplate(DS.'templates'.DS.'blank_file', $record, $lang_custom_file);
			
			$lang_custom_file_contents= FsManager::fileGetContents($lang_custom_file);
			$record['custrl'] = $lang_custom_file_contents;
			$this->createFromTemplate(DS.'templates'.DS.'language_file', $record, $lang_file_base);
		}
		$GLOBALS['syslog']->finish();	
	}
	
	private function buildModuleBase($module_name, $module_dir, $module_contents, $destination)
	{
		$GLOBALS['syslog']->enter();	
		$record = [];
		$record['modnme'] = $module_name;
		$record['mnulbl'] = $module_contents['description'];
		$record['enbarr'] = '';
		foreach ($module_contents['nodes'] as $node => $node_def)
				$record['enbarr'].="\t\t\t\t\t\t\"".$node_def['module'].".".$node_def['id']."\", \"".$node_def['actions'][0]."\", \n";
		
		//Create Menu Items
		$record['menu_items'] = '';
		$record['menu_items'] .="\t\t\$this->addMenuItem('".$module_name."')";
		foreach ($module_contents['nodes'] as $node => $node_def)
		{
			$node_desc = $node_def['description'];
			$action = $node_def['actions'][0];
			if ($node_def['nomenu'] == false)
				$record['menu_items'] .="\t\t\$this->addNodeToMenu(atktext(\"$node_desc\"),$node, $action, $module_name);\n";
		}	
		//Register nodes
		$record['register_nodes'] = '';
		foreach ($module_contents['nodes'] as $node => $node_def)
			{
				$node = $node_def['id'];
				//print $node_def['id']."\n";
				//print_r($node_def);
				$auth_entries=implode("', '", $node_def['actions']);
				$auth_array="['".$auth_entries."']";
				$record['register_nodes'].="\t\t\$this->registerNode('".strtolower($node). "'," .ucfirst($node)."::class,". $auth_array.");\n";
			}	 	
		/*	
		$record['sealst'] = '';	
		foreach ($module_contents['nodes'] as $node => $node_def)
		{				
			if ($node_def['search']==true)
			{	
				$node = $node_def['id'];
				$node_label = $node_def['description'];
				$record['sealst'].="\t\t\$node = &atkGetNode(\$this->module.\".$node\");\n";
				$record['sealst'].="\t\t\$results[\"$node_label\"] = \$this->recLinks(\$node->searchDb(\$expression),\$this->module.\".$node\");\n";
			}		
		}
		*/		
		$this->createFromTemplate(DS.'templates'.DS.'module_base', $record, $destination);	
		$GLOBALS['syslog']->finish();
	}
	
	private function processNode($module_dir, $node_name, $node_contents)
	{
		$GLOBALS['syslog']->enter();
		$node_name=ucfirst(trim($node_name));
		$GLOBALS['syslog']->debug("..Processing Node: ".$node_name,1);
		
		$node_custom_file=$module_dir.DS.$node_name.".php";
		
		if(!FsManager::fileExists($node_custom_file))
			$this->buildNodeCustom($node_name, $node_contents, $node_custom_file);

		$node_base_file=$module_dir.DS.$node_name."_base.php";
		$this->buildNodeBase($node_name, $node_contents, $node_base_file);
		
		$GLOBALS['syslog']->finish();	
	}
	
	private function buildNodeBase($node_name, $node_contents, $destination)
	{
		$GLOBALS['syslog']->enter();
		$record = [];
		$record['ndenme']=$node_name;
		$record['parnde']=$node_contents['type'];

		$node_flags=$node_contents['flags'];
		$ndefse=', ';
		if ($node_flags =='')
				$ndefse='';
		$record['ndefse']= $ndefse;
		$record['ndeflg']= $node_flags;				
		$count=10;
		$record['attlst']='';
		foreach ($node_contents['attributes'] as $at_name => $at_def)
		{
			$at_name=trim($at_name);
			$tab=$at_def['tabs'];
			$params=$at_def['params'];
			$sep=', ';
			if ($params =='')
				$sep='';
			$atkatr=$at_def['type']."('".$at_name."'".$sep.$params.")";
			$record['attlst'].="\t\t\$this->add(new $atkatr, $tab, $count);\n";
			$count = $count + 10;	
		}	
		$this->createFromTemplate('templates'.DS.'node_base',$record, $destination);
		$GLOBALS['syslog']->finish();
	}
	
	private function buildNodeCustom($node_name, $node_contents,$destination)
	{
		$GLOBALS['syslog']->enter();
		$record=[];
		$record['ndenme']=$node_name;
		$this->createFromTemplate('templates'.DS.'node_custom',$record, $destination);
		$GLOBALS['syslog']->finish();
	}
}
?>
