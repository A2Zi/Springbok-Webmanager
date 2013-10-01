<?php
class CoreController extends AController{
	/** */
	static function index(){
		self::set('current_version',Springbok::VERSION);
		self::render();
	}
	
}
