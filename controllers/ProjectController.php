<?php
Controller::$defaultLayout='project';
class ProjectController extends AController{
	
	/** */
	static function index(){
		Project::Table()->paginate()->setActionsRUD()->render(_t('Projects'));
	}
	
	/** @ValidParams
	* id > @Required
	*/ function view(int $id){
		$project=Project::ById($id)->notFoundIfFalse();
		$project->openGit();
		mset($project);
		render();
	}
	
	/** @ValidParams
	* id > @Required
	*/ function enhance(int $id){
		$project=Project::ById($id)->notFoundIfFalse();
		$project->checkCli();
		$res=UExec::exec('php '.escapeshellarg($project->path().'/cli.php').' enhance');
		if(!empty($res)) debugCode($res);
		else self::redirect('/project/view/'.$id);
		exit;
		
		/*#if PROD*/include dirname(CORE).'/dev/enhancers/EnhanceApp.php';/*#/if*/
		$f=new Folder($projectPath.DS.'tmp'); if($f->exists()) $f->delete();
		$instance=new EnhanceApp($projectPath);
		$res=$instance->process(true);
		self::mset($project);
		self::set_('changes',$res);
		self::render();
	}
	
	/** @ValidParams
	* id > @Required
	*/ function schema(int $id){
		$project=Project::ById($id);
		if(empty($project)) notFound();
		$projectPath=self::$workspace->projects_dir.$project->path.'/';
		
		$baseDefine="<?php
define('DS', DIRECTORY_SEPARATOR);
define('CORE','".dirname(CORE).DS."dev".DS."');
define('APP', __DIR__.'/dev/');";

		$schemaContent=$baseDefine."
".'$action'."='schema';
include CORE.'cli.php';";
		
		file_put_contents($projectPath.'schema.php',$schemaContent);
		CSession::setFlash(UExec::exec('php '.escapeshellarg($projectPath.'schema.php')));
		redirect('/project/view/'.$id);
	}
	

	/** @ValidParams
	* id > @Required
	*/ function start_prod(int $id){
		$project=Project::ById($id)->notFoundIfFalse();
		$projectPath=self::$workspace->projects_dir.$project->path.'/prod/'.DS;
		
		$baseDefine="<?php
define('DS', DIRECTORY_SEPARATOR);
define('CORE','".dirname(CORE)."/prod/');
define('CLIBS','".dirname(CORE)."/libs/prod/');
define('APP', __DIR__.DS);";

		$indexProdContent=$baseDefine."
define('APP_DATE',".time()."); define('APP_VERSION',''); define('WEB_FOLDER','');
include CORE.'app.php';";

		$schemaContent=$baseDefine."
".'$action'."='schema';
include CORE.'cli.php';";
		file_put_contents($projectPath.'schema.php',$schemaContent);
		CSession::setFlash(UExec::exec('php '.escapeshellarg($projectPath.'schema.php')));
		file_put_contents($projectPath.'index.php',$indexProdContent);
		
		
		self::redirect('/project/view/'.$id);
	}
	
	
	
	
	/**
	* project > @Valid('name','path')
	*/ function add(Project $project){
		if($project!==NULL && !CValidation::hasErrors()){
			$projectPath=self::$workspace->projects_dir.$project->path.DS;
			if(!file_exists($projectPath)) mkdir($projectPath,0777,true);
			if(!file_exists($projectPath.'tmp/')) mkdir($projectPath.'tmp/',0755);
			if(!file_exists($projectPath.'dev/')) mkdir($projectPath.'dev/',0755);
			if(!file_exists($projectPath.'prod/')) mkdir($projectPath.'prod/',0755);
			if(!file_exists($projectPath.'db/')) mkdir($projectPath.'db/',0755);
			if(!file_exists($projectPath.'data/')) mkdir($projectPath.'data/',0775);
			if(!file_exists($projectPath.'data/tmp/')) mkdir($projectPath.'data/tmp/',0775);
			if(!file_exists($projectPath.'sql/')) mkdir($projectPath.'sql/',0777);
			if(!file_exists($projectPath.'src/')) self::_createStructure($projectPath.'src/',$project->name);
			$project->insert();
			self::redirect('/project');
		}
		self::render();
	}
	
	
	const CRUD_MODEL='Project';
	/** @ValidParams @Required('id') */
	static function edit(int $id){
		CRUD::edit(self::CRUD_MODEL,$id,null,null,'project');
	}
	
	/*
	* id > @Required
	function edit(int $id){
		CRUD::edit('Project',$id);/*
		if(empty($id)) notFound();
		if($project!==NULL && !CValidation::hasErrors()){
			$project->id=$id;
			$project->update();
			self::redirect('/project/view/'.$id);
		}elseif(!$project) $project=Project::findOneById($id);
		self::mset($project);
		self::render();
	}*/
	
	
	private static function _createStructure($projectPath,$projectName){
		if(!file_exists($projectPath)){
			mkdir($projectPath,0777,true);
			mkdir($dir=$projectPath.'config/');
			file_put_contents($dir.'_.php',"<?"."php return array(\n\t'project_name'=>'".UString::underscore(preg_replace('/\s+/','',$projectName))."',"
				."\n\t'projectName'=>'".$projectName."',\n\t'availableLangs'=>array('fr'),\n"
				."\n\t'secure'=>array('crypt_key'=>'".str_replace("'",'0',uniqid('',true))."',)"
				."\n);");
			file_put_contents($dir.'_'.ENV.'.php',"<?"."php return array(\n\t'siteUrl'=>array('index'=>'http://localhost/'),"
					."\n\t'db'=>array(\n\t\t'default'=>array(\n\t\t\t\n\t\t\t'user'=>'root','password'=>'root'\n\t\t),\n\t),'generate'=>array('default'=>true)\n\t\n);");
			file_put_contents($dir.'routes.php',"<?"."php return array(\n\t'/favicon'=>array('Site::favicon','ext'=>'[a-z]+'),\n\t'/'=>array('Site::index'),"
				."\n\t'/:controller(/:action/*)?'=>array('Site::index'),\n);");
			file_put_contents($dir.'routes-langs.php',"<?"."php return NULL;");
			file_put_contents($dir.'cookies.php',"<?"."php return array();");
			file_put_contents($dir.'secure.php',"<?"."php return array(\n\t'url_login'=>'/site/login', 'url_redirect'=>'/',\n\t\n);");
			file_put_contents($dir.'enhance.php',"<?"."php return array(\n\t'base'=>array('i18n'),\n\t'includes'=>array(\n\t\t\n\t)\n);");
			
			mkdir($dir=$projectPath.'controllers/');
			file_put_contents($dir.'SiteCont'.'roller.php',"<?"."php\ncl"."ass SiteContr"."oller extends Contr"."oller{\n"
				."\t/* @"."ImportAction('core','Site','index') */\n"
				."\t/* @"."ImportAction('core','Site','favicon') */\n}");
			mkdir($dir=$projectPath.'models/');
			mkdir($dir=$projectPath.'views/');
			mkdir($dir2=$dir.'Site/');
			file_put_contents($dir2.'index.php',"<?"."php new AjaxContentView() ?".">");
			mkdir($dir=$projectPath.'viewsLayouts/');
			file_put_contents($dir.'base.php',"<? HHtml::doctype() ?>\n<html lang=\"<? CLang::get() ?>\">\n"
				."\t<head>\n\t\t<? HHtml::metaCharset() ?>\n\t\t<?php\n\t\t\tHHead::title(\$layout_title);"
				."\n\t\t\tHHead::linkCss();\n\t\t\tHHead::display();"
				."\n\t\t?".">\n\t</head>\n\t<body>"
				."\n\t\t".'{=$layout_content}'
				."\n\t</body>"."\n</html>");
			
			file_put_contents($dir.'page.php',"<?php new AjaxBaseView(".'$layout_title'.") ?>"
				."\n<header>\n\t<div id=\"logo\">".$projectName."</div>\n\t{menuTop 'startsWith':true"
				."\n\t\t_tC('Home'):false,\n"."\t}"
				."\n</header>\n".'{=$layout_content}'
				."\n<footer>Version du <b><? HHtml::enhanceDate() ?></b> | <? HHtml::powered() ?></footer>");
			file_put_contents($dir.'default.php',"<?php new AjaxPageView(".'$layout_title'.") ?>"
				."\n<div class=\"variable padding\">"
				."\n\t<h1>".'{$layout_title}'."</h1>"
				."\n\t".'{=$layout_content}'
				."\n</div>");
			mkdir($dir=$projectPath.'web/');
			mkdir($dir2=$dir.'css/');
			file_put_contents($dir2.'main.scss',"\$PAGE_FIXED:false;\n@includeCore 'colors/darkblue';\n@includeCore 'default';");
			mkdir($dir.'img/');
			mkdir($dir.'js/');
		}
	}

	/** @ValidParams
	* id > @Required
	*/ function createStructure(int $id){
		$project=Project::ById($id)->notFoundIfFalse();
		$projectPath=self::$workspace->projects_dir.$project->path.DS.'src'.DS;
		self::_createStructure($projectPath,$project->name);
		self::redirect('/project/view/'.$id);
	}
	
	
	
	
	
	
	
	
	/** @ValidParams
	 * id > @Required
	 */
	static function jobs(int $id){
		$project=Project::ById($id)->notFoundIfFalse();
		mset($project);
		set('jobs',file_exists($filename=$project->path().DS.'dev/config/jobs.php') ? include $filename : false);
		render();
	}
	
	/** @ValidParams
	 * id > @Required
	 * name > @Required
	 */
	static function job_execute(int $id,$name){
		$project=Project::ById($id)->notFoundIfFalse();
		$jobs=include $project->path().DS.'dev/config/jobs.php';
		if(!isset($jobs[$name])) notFound();
		/*if(!CHttpRequest::isPOST()) render('job_confirm');*/
		else{
			set_time_limit(0);
			self::set('output',UExec::php(CORE.'cron.php',$project->path().'/dev/',$name));
			self::mset($project,$name);
			self::render();
		}
	}
	
	
	
	
	
	/*
	public function create_deployment(int $id){
		$deployment=Deployment::QOne()->byId($id)->with('Project')->with('Server');
		if(empty($deployment)) notFound();
		
		file_put_contents($deployment->project->path().DS.'depl_'.$deployment->server->name.'.php',"<?php
define('DS', DIRECTORY_SEPARATOR);
define('CORE','".CORE."');
define('APP','".APP."');
define('ENV','dev');

".'$action'."='deployment';
".'$entries'."=include __DIR__.DS.'src'.DS.'config'.DS.'enhance.php';
".'$entries=$entries["entries"];'."
".'$argv'."=array(
	'type'=>'app','workspace_id'=>".self::$workspace->id.",'deployment_id'=>".$id.",
	'projectPath'=>__DIR__.DS.'prod'.DS,
	'entries'=>".'$entries'.",
	'dbPath'=>__DIR__.DS.'db'.DS,
	'options'=>array(
		'simulation'=>false,
		'ssh'=>".($deployment->ssh?"array('user'=>'".$deployment->server->user."','host'=>'".$deployment->server->host."')":'false').",
	)
);
include CORE.'cli.php';
");
		self::redirect('/project/deployment/'.$id);
	}*/
}
