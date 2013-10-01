<?php
class PhpdocController extends Controller{
	/** */
	static function index($file,$search){
		$phpdoc_dir=Config::$php_doc_dir;
		$search_res=NULL;
		if($file) $file=$phpdoc_dir.$file.'.html';
		if(!$search) $search=CSession::getOr('phpdoc_search');
		if($search){
			$search=preg_replace('/[^a-z]/','-',$search);
			CSession::set('phpdoc_search',$search);
			
			if(is_file($filename=$phpdoc_dir.'function.'.$search.'.html')) $file=$filename;
			elseif(is_file($filename=$phpdoc_dir.'ref.'.$search.'.html')) $file=$filename;
			else $search_res=glob($phpdoc_dir.'*'.$search.'*.html');
		}
		if($file===NULL) $file=$phpdoc_dir.'index.html';
		
		$content=file_get_contents($file);
		$matches=array();
		preg_match('/<body\>(.*)<\/body>/s',$content,$matches);
		$content=$matches[1];
		$content=preg_replace('/<div class="home">(.*)<\/div>/','',$content);
		$content=preg_replace('/href="/','href="'.HHtml::url('/phpdoc').'/',$content);
	
		self::mset($content,$search,$search_res);
		self::render();
	}
}
