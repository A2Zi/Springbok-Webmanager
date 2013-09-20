<?php
class CoreController extends AController{
	/** */
	function index(){
		self::set('current_version',Springbok::VERSION);
		self::render();
	}
	
}
