<?php
class ProjectTestsServerSendController extends SControllerServerSentEvents{
	
	public static function beforeDispatch(){
		AController::beforeDispatch();
	}
	
	/** @ValidParams @Id @NotEmpty('entry','env')*/
	function index(int $id,$entry,$env){
		$project=Project::ById($id);
		notFoundIfFalse($project);
		$tests=file_exists($filename=($projectPath=$project->path()).'/tests/'.$entry.'.json') ? json_decode(file_get_contents($filename),true) : array();
		
		$httpClient=new CHttpClient;
		$httpClient->doNotfollowRedirects();
		
		$envConfig=include $projectPath.'/src/config/_'.$env.'.php';
		$baseurl=rtrim($envConfig['siteUrl'][$entry],'/').'/';
		
		foreach($tests as $i=>$test){
			try{
				$httpClient->get($baseurl.ltrim($test['url'],'/').(strpos($test['url'],'?')===false?'?':'&').'springbokNoEnhance=true&springbokNoDevBar=true');
			}catch(HttpClientError $hce){}
			$status=$httpClient->getStatus();
			
			self::$resp->jsonData(array('i'=>$i,'status'=>$status,'success'=>$success=($status==$test['type']),'result'=>$result=$httpClient->getResult(),
						'contentOk'=>$success&&!empty($test['content'])?strpos($result,$test['content'])!==false:null));
			self::$resp->push();
		}
		
		
		self::$resp->event('close');
		self::$resp->data('');
		self::$resp->push();
		usleep(100);
		return true;
		
	}
	
}