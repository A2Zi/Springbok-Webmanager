<?php
Controller::$defaultLayout='project';
class ProjectTestsController extends AController{
	/** @ValidParams @Id */
	static function index(int $id,$entry,$env){
		$project=Project::ById($id);
		notFoundIfFalse($project);
		if(empty($entry)) $entry='index';
		if(empty($env)) $env=ENV;
		
		$tests=file_exists($filename=$project->path().'/tests/'.$entry.'.json') ? json_decode(file_get_contents($filename),true) : array();
		
		$entries=$project->entries();
		if(!empty($entries)) array_unshift($entries,'index');
		
		$environments=glob(($configPath=$project->path().'/src/config/').'_*.php');
		$lenConfigPath=strlen($configPath);
		unset($environments[0]);
		$environments=array_map(function($e) use($lenConfigPath){return substr($e,$lenConfigPath+1,-4); },$environments);
		
		set('envBaseUrl',str_replace('HTTP_OR_HTTPS','http',$project->entryBaseUrl($env,$entry)));
		
		mset($project,$tests,$entries,$entry,$environments,$env);
		render();
	}
	
	/** @ValidParams @Id */
	static function save(int $id,$entry,array $tests){
		$project=Project::ById($id);
		notFoundIfFalse($project);
		file_put_contents($project->path().'/tests/'.$entry.'.json',str_replace('},',"},\n",json_encode($tests/*, JSON_PRETTY_PRINT*/)));
	}
}