<?php
Controller::$defaultLayout='plugin';
class PluginController extends AController{
	protected static function beforeRender(){
		self::setForLayout('pluginsPaths',PluginPath::findListName());
		self::setForLayout('plugins',Plugin::findListName());
	}
	
	/** */
	static function index(){
		Plugin::Table()->paginate()->setActionsRUD()->render(_t('Plugins'));
	}
	
	
	/** @ValidParams
	* id > @Required
	*/ function view(int $id){
		$plugin=Plugin::findOneById($id);
		if(empty($plugin)) notFound();
		$plugin->findWith('Project',array('orderBy'=>array('plgPrj.position')));
		$servers=Server::findListName(); $projects=Project::findListName();
		$deployments=PluginDeployment::QAll()->byPlugin_id($id)->with('Server')->with('Plugin')->with('PluginPathDeployment');
		self::mset($plugin,$servers,$projects,$deployments);
		self::render();
	}
	
	/** @Ajax @ValidParams
	* pluginId > @Required
	* adr > @Required
	*/ function sortProjects(int $pluginId,array $prj){
		foreach($prj as $position=>$prjId)
			PluginProject::QUpdateOneField('position',$position)->where(array('plugin_id'=>$pluginId,'project_id'=>$prjId));
		renderText('1');
	}
	
	/** @ValidParams
	 * id > @Required
	 */
	static function enhance(int $id){
		$plugin=Plugin::ById($id)->with('PluginPath');
		if(empty($plugin)) notFound();
		$pluginPath=$plugin->path();
		$f=new Folder($pluginPath.DS.'tmp_dev'); if($f->exists()) $f->delete();
		include_once CORE.'enhancers/EnhancePlugin.php';
		$instance=new EnhancePlugin($pluginPath);
		$changes=$instance->process(true);
		self::mset($changes,$plugin);
		self::render();
	}
	
	/** @ValidParams
	* id > @Required
	*/ function schema(int $id){
		$plugin=Plugin::ById($id)->with('PluginPath');
		if(empty($plugin)) notFound();
		$pluginPath=$plugin->path().DS;
		
		$baseDefine="<?php
define('DS', DIRECTORY_SEPARATOR);
define('CORE','".dirname(CORE)."/dev/"."');
define('APP', __DIR__.'/dev/');
define('ENV','dev');";

		$schemaContent=$baseDefine."
".'$action'."='schema';
include CORE.'plugin.php';";
		
		file_put_contents($pluginPath.'schema.php',$schemaContent);
		CSession::setFlash(UExec::exec('php '.escapeshellarg($pluginPath.'schema.php')));
		redirect('/plugin/view/'.$id);
	}
	
	
	/**
	 * plugin > @Valid('name','folder_name','plugin_path_id')
	 */
	static function add(Plugin $plugin){
		if($plugin!==NULL && !CValidation::hasErrors()){
			$pluginPath=PluginPath::findValuePathById($plugin->plugin_path_id).DS.$plugin->folder_name.DS.'src'.DS;
			self::_createStructure($pluginPath,$plugin->name);
			$plugin->insert();
			self::redirect('/plugin');
		}
		self::set('plugin_paths',PluginPath::findListName());
		self::render();
	}
	
	
	/** @ValidParams
	 * id > @Required
	 * plugin > @Valid('name','folder_name','plugin_path_id')
	 */
	static function edit(int $id,Plugin $plugin){
		if($plugin===NULL){
			$plugin=Plugin::findOneById($id);
		}elseif(!CValidation::hasErrors()){
			$pluginPath=PluginPath::findValuePathById($plugin->plugin_path_id).DS.$plugin->folder_name.DS.'src'.DS;
			self::_createStructure($pluginPath,$plugin->name);
			$plugin->insert();
			self::redirect('/plugin');
		}
		self::set('plugin_paths',PluginPath::findListName());
		self::render();
	}
	
	private static function _createStructure($pluginPath,$projectName){
		if(!file_exists($pluginPath)){
			mkdir($pluginPath,0777,true);
			mkdir($dir=$pluginPath.'config'.DS);
			file_put_contents($dir.'_dev.php',"<?"."php return array(\n\t'db'=>array(\n\t\t\n\t)\n);");
			mkdir($dir=$pluginPath.'models'.DS);
		}
	}
	
	/** @ValidParams
	* pluginDeployment > @Valid('server_id','plugin_id')
	*/ function deployment_add(PluginDeployment $pluginDeployment){
		$pluginDeployment->insert();
		self::redirect('/plugin/view/'.$pluginDeployment->plugin_id);
	}
	
	/** @ValidParams
	* pluginProject > @Valid
	*/ function project_add(PluginProject $pluginProject){
		$pluginProject->insert();
		self::redirect('/plugin/view/'.$pluginProject->plugin_id);
	}
	
	/** @ValidParams
	* id > @Required
	*/ function do_deployment(int $id,bool $schema){
		$deployment=PluginDeployment::QOne()->byPlugin_id($id)->with('Server')->with('Plugin')->with('PluginPathDeployment')->byId($id);
		$deployment->plugin->findWith('PluginPath');
		self::mset($deployment);
		self::set('output',$deployment->doDeployment(self::$workspace->id,false,false,$schema));
		self::set('plugin',$deployment->plugin);
		self::render();
	}
	
	
	/* PLUGINS PATHS */
	/**
	* plugin > @Valid('name','path')
	*/ function path_add(PluginPath $pluginPath){
		if($pluginPath!==NULL && !CValidation::hasErrors()){
			$pluginPath->insert();
			self::redirect('/plugin');
		}
		self::render();
	}
	
	/** @ValidParams
	* id > @Required
	*/ function view_pluginsPath(int $id){
		$pluginPath=PluginPath::ById($id)->mustFetch();
		$deployments=PluginPathDeployment::QAll()->byPlugin_path_id($id)->with('Server');
		$servers=Server::findListName();
		self::mset($pluginPath,$deployments,$servers);
		self::render();
	}
	
	/** @ValidParams
	* pluginPathDeployment > @Valid('server_id','plugin_path_id','path')
	*/ function path_deployment_add(PluginPathDeployment $pluginPathDeployment){
		$pluginPathDeployment->insert();
		self::redirect('/plugin/view_pluginsPath/'.$pluginPathDeployment->plugin_path_id);
	}
	
	/** @ValidParams
	* id > @Required
	*/ function path_do_deployment(int $id){
		$ppd=PluginPathDeployment::ById($id)->mustFetch();
		$ppd->doDeployment();
	}
}
