<?php
Controller::$defaultLayout='project';
class ProjectAclsController extends AController{
	/** @ValidParams @Required('id') */
	static function view(int $id){
		$project=Project::ById($id);
		if(empty($project)) notFound();
		$permissions=file_exists($aclFile=($project->path().'/dev/config/aclPermissions.php'))? include $aclFile  : array();
		$groups=file_exists($groupFile=($project->path().'/src/config/aclGroups.php')) ? include $groupFile : array();
		if(!empty($permissions)) $permissions=$permissions['permissions'];
		
		foreach($permissions as $permission=>$det){
			$found=false;
			foreach($groups as $group){
				if(in_array($permission,$group)){
					$found=true;
					break;
				}
			}
			if(!$found){
				if(!isset($groups['No group'])){
					$groups=array_reverse($groups,true);
					$groups['No group']=array();
					$groups=array_reverse($groups,true);
				}
				$groups['No group'][]=$permission;
			}
		}
		
		mset($project,$groups,$permissions);
		render();
	}
	
	/** @ValidParams @AllRequired */
	static function addGroup(int $id,$name){
		$project=Project::ById($id);
		if(empty($project)) notFound();
		$groups=file_exists($groupFile=($project->path().'/src/config/aclGroups.php')) ? include $groupFile : array();
		if(empty($groups[$name])) $groups[$name]=array();
		file_put_contents($groupFile,'<?php return '.UPhp::exportCode($groups).';');
		redirect('/projectAcls/view/'.$id);
	}


	/** @ValidParams @Required('id','name') */
	static function sort(int $id,$name,array $perms){
		$project=Project::ById($id);
		if(empty($project)) notFound();
		$groups=file_exists($groupFile=($project->path().'/src/config/aclGroups.php')) ? include $groupFile : array();
		$groups[$name]=$perms;
		file_put_contents($groupFile,'<?php return '.UPhp::exportCode($groups).';');
		renderText('1');
	}
}