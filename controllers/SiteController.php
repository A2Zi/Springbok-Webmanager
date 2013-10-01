<?php
class SiteController extends AController{
	/** */
	static function index(){
		$servers=Server::findAll();
		foreach($servers as &$server){
			$server->isAlive=CDaemons::isAlive('Ssh',self::$workspace->id.'-'.$server->id);
		}
		mset($servers);
		self::render();
	}
	
	/** @ValidParams('/')
	* id > @Required
	*/ function startDaemon($id){
		(CDaemons::start('Ssh',self::$workspace->id.'-'.$id));
		redirect('/');
	}
	
	/** */
	static function favicon(){
		self::renderFile(APP.'web/img/favicon.png');
	}
}