<?php
exit('TODO : edit yaml files');
Controller::$defaultLayout='project';
class ProjectLangsController extends AController{
	/** @Required('id') */
	static function view(int $id){
		$project=Project::ById($id)->with('ProjectLang')->notFoundIfFalse();
		self::mset($project);
		self::render();
	}
	
	/** @ValidParams */
	static function add(int $id,ProjectLang $projectLang){
		if($projectLang){
			$projectLang->project_id=$id;
			$projectLang->insert();
			self::redirect('/projectLangs/view/'.$id);
		}
	}
	
	/** @ValidParams
	* id > @Required
	* lang > @Required
	*/ function lang(int $id, string $lang){
		$project=Project::ById($id)->notFoundIfFalse();
		$projectPath=self::$workspace->projects_dir.$project->path.DS.'src';
		self::mset($project,$lang);
		
		$arrayStrings=array('all'=>array());
		self::_recursiveFiles($projectPath,$arrayStrings);
		$all=array_unique($arrayStrings['all']);
		unset($arrayStrings['all']);
		//debug($arrayStrings);
		//exit;
		
		$db=self::_loadDbLang($projectPath, $lang);
		//$db->doUpdate('CREATE TABLE IF NOT EXISTS t(s NOT NULL,c NOT NULL,t NOT NULL, PRIMARY KEY(s,c)');
		
		
		$dbSchema=new DBSchemaSQLite($db,'t');
		$dbSchema->setModelInfos(array(
			'primaryKeys'=>array('s','c'),
			'columns'=>array(
				's'=>array('type'=>'TEXT','notnull'=>true,'unique'=>false,'default'=>false),
				'c'=>array('type'=>'TEXT','notnull'=>true,'unique'=>false,'default'=>'"a"'),
				't'=>array('type'=>'TEXT','notnull'=>true,'unique'=>false,'default'=>false)
			)
		));
		if(!$dbSchema->tableExist()) $dbSchema->createTable();
		//else $dbSchema->compareTableAndApply();
		
		self::set('translations',$db->doSelectListValue('SELECT s,t FROM t WHERE c=\'a\' AND s NOT LIKE "plugin%"'));
		
		self::set_('allStrings',$all);
		self::set_('arrayStrings',$arrayStrings);
		self::render();
	}

	/** @ValidParams
	* id > @Required
	* lang > @Required
	*/ function save(int $id, string $lang, array $data){
		$project=Project::ById($id)->notFoundIfFalse();
		$projectPath=self::$workspace->projects_dir.$project->path.DS.'src';
		$db=self::_loadDbLang($projectPath, $lang);
		$db->doUpdate('DELETE FROM t WHERE c=\'a\' AND s NOT LIKE "plugin%"');
		$statement=$db->getConnect()->prepare('INSERT INTO t(s,c,t) VALUES (:s,\'a\',:t)');
		if(!empty($data)) foreach($data as $d){
			$statement->bindValue(':s',$d['s']);
			$statement->bindValue(':t',$d['t']);
			$statement->execute();
		}
		
		self::redirect('/projectLangs/lang/'.$id.'/'.$lang);
	}
	
	/** @ValidParams
	* id > @Required
	* lang > @Required
	*/ function sp(int $id, string $lang){
		$project=Project::ById($id)->notFoundIfFalse();
		$projectPath=self::$workspace->projects_dir.$project->path.DS.'src';
		self::mset($project,$lang);
		
		$arrayStrings=array('all'=>array());
		self::_recursiveFiles($projectPath,$arrayStrings,'_t_p',true);
		$all=array_unique($arrayStrings['all']);
		unset($arrayStrings['all']);
		
		
		$db=self::_loadDbLang($projectPath, $lang);
		self::set('translations',$db->doSelectListRows('SELECT t1.s,t1.t AS singular,t2.t AS plural FROM t t1 LEFT JOIN t t2 ON t1.s=t2.s WHERE t1.c=\'s\' AND t2.c=\'p\''));
		
		self::set_('allStrings',$all);
		self::render();
	}
	
	/** @ValidParams
	* id > @Required
	* lang > @Required
	*/ function sp_save(int $id, string $lang, array $data){
		$project=Project::ById($id)->notFoundIfFalse();
		$projectPath=self::$workspace->projects_dir.$project->path.DS.'src';
		$db=self::_loadDbLang($projectPath, $lang);
		$db->doUpdate('DELETE FROM t WHERE c IN(\'s\',\'p\')');
		$statementSingular=$db->getConnect()->prepare('INSERT INTO t(s,c,t) VALUES (:s,\'s\',:t)');
		$statementPlural=$db->getConnect()->prepare('INSERT INTO t(s,c,t) VALUES (:s,\'p\',:t)');
		foreach($data as $d){
			$statementSingular->bindValue(':s',$d['s']);
			$statementSingular->bindValue(':t',$d['singular']);
			$statementSingular->execute();
			$statementPlural->bindValue(':s',$d['s']);
			$statementPlural->bindValue(':t',$d['plural']);
			$statementPlural->execute();
		}
		
		self::redirect('/projectLangs/sp/'.$id.'/'.$lang);
	}
	
	
	/** @ValidParams
	 * id > @Required
	 * lang > @Required
	 */
	static function models(int $id, string $lang){
		$project=Project::ById($id)->notFoundIfFalse();
		$projectPath=self::$workspace->projects_dir.$project->path.DS.'src';
		mset($project,$lang);
		
		$all=array();
		if($dir=opendir(($dirname=dirname($projectPath).'/dev/models/infos/'))){
			$files=array();
			while (false !== ($file = readdir($dir)))
				if($file != '.' && $file != '..' && substr($file,-1)!=='_' && !is_dir($filename=$dirname.$file)) $files[$file]=$filename;
			closedir($dir);
			ksort($files);
			
			foreach($files as $modelname=>$file){
				$infos=include $file;
				$all[$modelname][]='';
				$all[$modelname][]='New';
				foreach($infos['columns'] as $key=>$v) $all[$modelname][]=$key;
			}
		}
		
		
		$db=self::_loadDbLang($projectPath, $lang);
		self::set('translations',$db->doSelectListValue('SELECT s,t FROM t WHERE c=\'f\''));
		
		self::set_('allStrings',$all);
		self::render();
	}
	
	/** @Ajax @ValidParams
	 * id > @Required
	 * lang > @Required
	 */
	static function modelsSave(int $id, string $lang, string $modelname,array $data){
		$project=Project::ById($id)->notFoundIfFalse();
		$projectPath=self::$workspace->projects_dir.$project->path.'/src';
		$db=self::_loadDbLang($projectPath, $lang);
		$db->doUpdate('DELETE FROM t WHERE c=\'f\' AND s like '.$db->escape($modelname.'%'));
		$statement=$db->prepare('INSERT INTO t(s,c,t) VALUES (:s,\'f\',:t)');
		foreach($data as $s=>$t){
			if($t==='') continue;
			if($s===0) $s='';
			$statement->bindValue(':s',$modelname.':'.$s);
			$statement->bindValue(':t',$t);
			$statement->execute();
		}
		
		renderText('1');
	}
	
	
	/** @ValidParams @Id @NotEmpty('lang') */
	static function plugins(int $id,$lang){
		$project=Project::ById($id)->notFoundIfFalse();
		mset($project,$lang);
		$projectPath=self::$workspace->projects_dir.$project->path.DS.'src';
		
		$enhanceConfig=include $projectPath.'/config/enhance.php';
		$plugins=array_map(function(&$v){return $v[1];},$enhanceConfig['plugins']);
		
		$db=self::_loadDbLang($projectPath, $lang);
		
		$translations=array();
		foreach($plugins as $plugin){
			$translations[$plugin]=$db->doSelectListValue('SELECT s,t FROM t WHERE c=\'a\' AND s LIKE "plugin.'.$plugin.'%"');
		}
		mset($translations);
		
		self::render();
	}
	
	/** @ValidParams @Id @NotEmpty('lang','pluginName') */
	static function pluginSave(int $id,$lang,$pluginName,array $data){
		$project=Project::ById($id)->notFoundIfFalse();
		
		$projectPath=self::$workspace->projects_dir.$project->path.DS.'src';
		$db=self::_loadDbLang($projectPath, $lang);
		$db->doUpdate('DELETE FROM t WHERE c=\'a\' AND s like '.$db->escape('plugin.'.$pluginName.'.%'));
		$statement=$db->prepare('INSERT INTO t(s,c,t) VALUES (:s,\'a\',:t)');
		foreach($data as $s=>$t){
			if($t==='') continue;
			$statement->bindValue(':s',$s);
			$statement->bindValue(':t',$t);
			$statement->execute();
		}
		
		renderText('1');
	}
	
	
	
	/** @ValidParams
	 * id > @Required
	 * lang > @Required
	 */
	static function js(int $id, string $lang){
		$project=Project::ById($id)->notFoundIfFalse();
		$projectPath=self::$workspace->projects_dir.$project->path.'/src/web/js/';
		self::mset($project,$lang);
		
		$arrayStrings=array('all'=>array());
		self::_recursiveFiles($projectPath,$arrayStrings);
		$all=array_unique($arrayStrings['all']);
		unset($arrayStrings['all']);
		//debug($arrayStrings);
		//exit;
		
		$translations=array();
		if(file_exists($filename=$projectPath.'i18n-'.$lang.'.js')){
			$content=file_get_contents($filename); $matches=array();
			if(preg_match('window.i18n={(.*)};\s*$/Us',$content,$matches)){
			//debug($matches);
			
				foreach(explode("\n",$matches[1]) as $val){
					eval('list($key,$val)=array('.preg_replace('/(\'|\")=(\'|\")/','$1,$2',$val,1).');');
					$translations[$key]=$val;
				}
			}
			/*preg_match('/window.i18n={(.*)\n}/U',$content,$i18nMatches);
			if(!empty($i18nMatches)){
				$i18nMatches[1]=','.$i18nMatches[1];
				preg_match_all('/([^:]*):(.*),\n/Ums',$i18nMatches[1],$matches);
				debug($matches);
				
				foreach($matches[1] as $i=>$k){
					$key='';$val='';
					eval('$key='.$k.';$val='.$matches[2][$i].';');
					$translations[$key]=$val;
				}
			}*/
		}
		self::set('translations',$translations);
		
		self::set_('allStrings',$all);
		self::render();
	}
	
	/** @ValidParams
	 * id > @Required
	 * lang > @Required
	 */
	static function js_save(int $id, string $lang, array $data){
		$project=Project::ById($id)->notFoundIfFalse();
		$projectPath=self::$workspace->projects_dir.$project->path.'/src/web/js/';
		
		$content='';
		//if(file_exists($filename=CORE.'includes/js/langs/core-'.$lang.'.js'))
		//	$content="includeCore('langs/core-".$lang."');";
		if(file_exists($filename=CORE.'includes/js/langs/'.$lang.'.js'))
			$content="includeCore('langs/".$lang."');";
		$content.="S.lang='$lang';function _t(string){\nvar t=i18n[string];\nif(t===undefined) return string;\nreturn t;\n}\nwindow.i18n={\n";
		if(!empty($data)) foreach($data as $d)
			$content.="\n".UPhp::exportString($d['s']).':'.UPhp::exportString($d['t']).',';
		$content=substr($content,0,0-1)."\n};";
		
		file_put_contents($projectPath.'i18n-'.$lang.'.js',$content);
		
		self::redirect('/projectLangs/js/'.$id.'/'.$lang);
	}
	
	/** @ValidParams
	 * id > @Required
	 * lang > @Required
	 */
	static function update_core(int $id, $lang){
		$project=Project::ById($id)->notFoundIfFalse();
		$projectPath=self::$workspace->projects_dir.$project->path.'/src';
		$db=self::loadCoreDB($lang);
		$data=$db->doSelectListValue('SELECT s,t FROM t WHERE t != ""');
		
		$db=self::_loadDbLang($projectPath, $lang);
		$db->doUpdate('DELETE FROM t WHERE c=\'c\'');
		$statement=$db->prepare('INSERT INTO t(s,c,t) VALUES (:s,\'c\',:t)');
		foreach($data as $s=>$t){
			$statement->bindValue(':s',$s);
			$statement->bindValue(':t',$t);
			$statement->execute();
		}
		self::redirect('/projectLangs/view/'.$id);
	}
	
	
	private static function _loadDbLang($projectPath,$lang){
		$projectConfig=include $projectPath.'/config/_'.ENV.'.php';
		return new DBSQLite(false,array( 'file'=>dirname($projectPath).'/db/'.$lang.'.db','flags'=>SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE ));
	}
	
}