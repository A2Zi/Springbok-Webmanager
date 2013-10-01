<?php
class EditorController extends AController{
	/** @ValidParams
	 * id > @Required
	 */
	static function project(int $id){
		$project=Project::findOneById($id);
		if(empty($project)) notFound();
		self::mset($project);
		self::set('projectType','project');
		self::render('start_ace');
	}
	
	/** @ValidParams
	 * id > @Required
	 */
	static function plugin(int $id){
		$plugin=Plugin::findOneById($id);
		if(empty($plugin)) notFound();
		self::set_('project',$plugin);
		self::set('projectType','plugin');
		self::render('start_ace');
	}
	
	/** */
	static function ace($file){
		self::renderFile(APP.'web/js/ace/'.$file);
	}
	
	private static function _projectRoot($id){
		$project=Project::findOneById($id);
		if(empty($project)) notFound();
		return $project->path().DS.'src';
	}
	
	private static function _pluginRoot($id){
		$plugin=Plugin::QOne()->byId($id)->with('PluginPath');
		if(empty($plugin)) notFound();
		return $plugin->path().DS.'src';
	}
	/** @Ajax
	 * id > @Required
	 */
	static function projectFileTree(int $id,$dir){
		if(empty($dir)) $dir=DS;
		self::_fileTree(self::_projectRoot($id),$dir);
	}
	/** @Ajax
	 * id > @Required
	 */
	static function pluginFileTree(int $id,$dir){
		if(empty($dir)) $dir=DS;
		self::_fileTree(self::_pluginRoot($id),$dir);
	}
	

	private static function _fileTree($root,$dir){
		if(file_exists($root . $dir)){
			$files = scandir($root . $dir);
			natcasesort($files);
			if(count($files) > 2 ) { /* The 2 accounts for . and .. */
				$res=array();
				// All dirs
				foreach($files as $file){
					if(file_exists($root . $dir . $file) && $file != '.' && $file != '..'){
						if(is_dir($root . $dir . $file)) $res['folders'][h($dir.$file)]=h($file);
						else $res['files'][h($dir.$file)]=h($file);
					}
				}
				self::renderJSON(json_encode($res));
			}
		}
		self::renderJSON('{}');
	}

	/** @Ajax
	 * id > @Required
	 * dir > @Required
	 */
	static function projectFileContent(int $id,$file){
		$filename=self::_projectRoot($id).$file;
		if(!file_exists($filename)) notFound();
		self::renderText(file_get_contents($filename));
	}
	
	
	/** @Ajax
	 * id > @Required
	 * dir > @Required
	 */
	static function pluginFileContent(int $id,$file){
		$filename=self::_pluginRoot($id).$file;
		if(!file_exists($filename)) notFound();
		self::renderText(file_get_contents($filename));
	}
	
	/** @Ajax
	 * id > @Required
	 * dir > @Required
	 * content > @Required
	 */
	static function projectSaveFileContent(int $id,$file,$content){
		$filename=self::_projectRoot($id).$file;
		if(!file_exists($filename)) notFound();
		file_put_contents($filename,$content);
	}
	
	/** @Ajax
	 * id > @Required
	 * dir > @Required
	 * content > @Required
	 */
	static function pluginSaveFileContent(int $id,$file,$content){
		$filename=self::_pluginRoot($id).$file;
		if(!file_exists($filename)) notFound();
		file_put_contents($filename,$content);
	}
	
	
	/* FOLDERS */
	
	
	/** @Ajax @ValidParams
	* id > @Required
	* folderName > @Required
	*/ function projectFolderAdd(int $id,$dir,$folderName){
		if(empty($dir)) $dir=DS;
		mkdir(self::_projectRoot($id).$dir.$folderName);
	}
	/** @Ajax @ValidParams
	* id > @Required
	* folderName > @Required
	*/ function pluginFolderAdd(int $id,$dir,$folderName){
		if(empty($dir)) $dir=DS;
		mkdir(self::_pluginRoot($id).$dir.$folderName);
	}
	
	/* FILES */
	
	/** @Ajax @ValidParams
	* id > @Required
	* fileName > @Required
	*/ function projectFileAdd(int $id,$dir,$fileName){
		if(empty($dir)) $dir=DS;
		file_put_contents(self::_projectRoot($id).$dir.$fileName,'');
	}
	/** @Ajax @ValidParams
	* id > @Required
	* fileName > @Required
	*/ function pluginFileAdd(int $id,$dir,$fileName){
		if(empty($dir)) $dir=DS;
		file_put_contents(self::_pluginRoot($id).$dir.$fileName,'');
	}
}
