<?php
Controller::$defaultLayout='project';
class ProjectDeploymentController extends AController{
	/** @ValidParams @AllRequired */
	static function all(int $id){
		$project=Project::ById($id);
		notFoundIfFalse($project);
		self::mset($project);
		self::set('deployments',Deployment::QAll()->byProject_id($id)->with('Server'));
		self::render();
	}
	
	/** @Post @ValidParams @AllRequired
	 * deployment > @Valid('project_id','server_id','path')
	 */
	static function add(Deployment $deployment){
		$deployment->insert();
		self::redirect('/projectDeployment/all/'.$deployment->project_id);
	}
	
	/** @ValidParams @Required('id')
	 * deployment > @Valid('path','base_url')
	 */
	static function edit(int $id,Deployment $deployment){
		$existingDeployment=Deployment::ById($id)->with('Project');
		notFoundIfFalse($existingDeployment);
		if($deployment!==null){
			$deployment->id=$id;
			$deployment->update('path','base_url','env_name');
			self::redirect('/projectDeployment/all/'.$existingDeployment->project_id);
		}
		self::set_('deployment',$existingDeployment);
		render();
	}
	
	/** @ValidParams @Required('id') */
	static function del(int $id){
		$prjId=Deployment::findValueProject_idById($id);
		notFoundIfFalse($prjId);
		Deployment::deleteOneById($id);
		redirect('/projectDeployment/all/'.$prjId);
	}
	
	
	/** @ValidParams @Required('id') */
	static function view(int $id){
		$deployment=Deployment::ById($id)->with('Project')->with('Server');
		notFoundIfFalse($deployment);
		mset($deployment);
		set('project',$deployment->project);
		render();
	}
	
	
	
	/** @ValidParams @Post @Required('id') */
	static function start(int $id){
		$deployment=Deployment::ById($id)->with('Project')->with('Server');
		notFoundIfFalse($deployment);
		CSession::setFlash($deployment->start(null,self::$workspace->id));
		redirect('/projectDeployment/view/'.$id);
	}
	/** @ValidParams @Required('id') */
	static function stop(int $id){
		$deployment=Deployment::ById($id)->with('Project')->with('Server');
		notFoundIfFalse($deployment);
		debugVar($deployment->stop(null,self::$workspace->id));
		//CSession::setFlash($deployment->stop());
		/*redirect('/projectDeployment/view/'.$id);*/
	}
	
	
	/** @ValidParams @Required('id') */
	static function deploy(int $id){
		$deployment=Deployment::ById($id)->with('Project')->with('Server');
		notFoundIfFalse($deployment);
		mset($deployment);
		set('project',$deployment->project);
		set('confirm',!CHttpRequest::isPOST());
		render();
	}
	
}