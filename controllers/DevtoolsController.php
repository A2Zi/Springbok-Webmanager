<?php
Controller::$defaultLayout='dev_tools';
class DevtoolsController extends Controller{
	/** */
	static function index(){
		self::render();
	}
	
	/** */
	static function colors($hexColor){
		if($hexColor===NULL) $hexColor='888888';
		self::set('colors',UColors::darkerAndLighterShadesWithForeground($hexColor));
		self::render();
	}
	
	/** */
	static function pt_px(){
		self::render();
	}
	
	/** */
	static function jqueryui(){
		self::render();
	}
	
	/* @SimpleAction('css') */
	/* @SimpleAction('stringCompare') */
}
