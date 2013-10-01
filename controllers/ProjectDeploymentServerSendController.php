<?php
class ProjectDeploymentServerSendController extends SControllerServerSentEvents{
	
	public static function beforeDispatch(){
		AController::beforeDispatch();
	}
	
	/** @ValidParams @Required('id') */
	static function deploy(int $id){
		try{
			$deployment=Deployment::ById($id)->with('Project')->with('Server');
			notFoundIfFalse($deployment);
			
			$projectPath=dirname($deployment->getProjectPath()).'/';
			if(file_exists($projectPath.'block_enhance')){
				self::$resp->data('ERROR: An enhance is still in progress. Aborted.');
				self::$resp->push();
			}elseif(file_exists($projectPath.'block_delayedEnhanceDaemon')){
				self::$resp->data('ERROR: The delayed enhance daemon is still active. Aborted.');
				self::$resp->push();
			}else{
				file_put_contents($projectPath.'block_deploy','');
				$deployment->doDeployment(AController::$workspace->id,self::$resp);
				unlink($projectPath.'block_deploy');
			}
		}catch(Exception $e){
			self::$resp->data('EXCEPTION : '.$e->__toString());
			self::$resp->push();
			self::$resp->event('close');
			self::$resp->data('');
			self::$resp->push();
			throw $e;
		}
		
		self::$resp->event('close');
		self::$resp->data('');
		self::$resp->push();
		usleep(100);
		return true;
		
	}
	
}