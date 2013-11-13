<?php
class WorkspaceController extends AController{
	/** */
	static function index(){
		$tableworkspaces=Workspace::Table()->paginate()->setActionsRUD()->actionClick('select')->addAction('select');
		mset($tableworkspaces);
		render();
	}
	
	/** @ValidParams
	* id > @Required
	*/ function view(int $id){
		$workspace=Workspace::findOneById($id);
		notFoundIfFalse($workspace);
		mset($workspace);
		render();
	}
	
	/** @ValidParams
	* id > @Required
	*/ function delete(int $id){
		$workspace=Workspace::findOneById($id);
		notFoundIfFalse($workspace);
		$workspace->delete();
		self::redirect('/workspace');
	}
	
	/** @ValidParams
	* id > @Required
	*/	function select(int $id){
		$workspace=Workspace::findOneById($id);
		if(empty($workspace)) notFound();
		CSession::set('workspace',$workspace);
		$cookie=CCookie::get('Workspace');
		$cookie->id=$id;
		$cookie->write();
		self::redirect('/project');
	}
	
	/**
	* workspace > @Valid
	*/	function add(Workspace $workspace){
		if($workspace){
			$workspace->insert();
			/*Model::$__dbName=$workspace->db_name;
			foreach(array('Database','Project','ProjectLang','Deployment','Server','ServerCore','Plugin','PluginPath','PluginDeployment','PluginPathDeployment') as $model){
				$schema=new DBSchema($model);
				$schema->process();
			}*/
			self::redirect('/workspace');
		}
		self::render();
	}

	/**
	* id > @Required
	* workspace > @Valid
	*/	function edit(int $id,Workspace $workspace){
		if($workspace){
			$workspace->id=$id;
			$workspace->update();
			CSession::set('workspace',$workspace);
			
			/*Model::$__dbName=$workspace->db_name;
			foreach(array('Database','Project','ProjectLang','Deployment','Server','ServerCore','Plugin','PluginPath','PluginDeployment','PluginPathDeployment') as $model){
				$schema=new DBSchema($model);
				$schema->process();
			}*/
			self::redirect('/workspace');
		}
		$_POST['workspace']=Workspace::findOneById($id)->_getData();
		self::render();
	}
}