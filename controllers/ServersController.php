<?php
class ServersController extends AController{
	
	protected static function beforeRender(){
		self::setForLayout('servers',Server::findListName());
	}
	
	
	/** */
	static function index(){
		$tableservers=Server::Table()->paginate()->setActionsRUD();
		mset($tableservers);
		render();
	}
	
	/** */
	static function initSsh(){
		$sshDir='/var/www/.ssh/';
		if(!file_exists($sshDir)) mkdir($sshDir,0700);
		if(!file_exists($sshDir.'config')) file_put_contents($sshDir.'config','Host *
   ControlMaster auto
   ControlPath ~/.ssh/master-%r@%h:%p');
		redirect('/servers');
	}
	
	/** @ValidParams
	 * id > @Required
	 */
	static function view(int $id,$testSshConnection){
		$server=Server::findOneById($id);
		if(empty($server)) notFound();
		self::mset($server);
		if($testSshConnection){
			$res=UExec::exec('echo "ok"',$server->sshOptions());
			debug($res);
			CSession::setFlash($connect?'Login successful !':'Login failed...');
		}
		self::set('basicCommand',UExec::getBasicCommand($server->sshOptions()));
		self::render();
	}
	
	/** @ValidParams
	 * id > @Required
	 */
	static function delete(int $id){
		$server=Server::findOneById($id);
		notFoundIfFalse($server);
		$server->delete();
		self::redirect('/servers');
	}
	
	/**
	 * server > @Valid
	 */
	static function add(Server $server){
		if($server){
			if(!empty($server->pwd)) $server->pwd=USecure::encryptAES($server->pwd);
			$server->insert();
			self::redirect('/servers');
		}
		self::render();
	}
	/** @ValidParams
	 * id > @Required
	 */
	static function edit(int $id,Server $server){
		if($server){
			if(!empty($_FILES)){
				$sshDir=DATA.'ssh/'.AController::$workspace->name.'/';
				//self::moveUploadedFile('public_key',$sshDir.$id.'-key.pub');
				self::moveUploadedFile('private_key',$sshDir.$id.'-key');
				//chmod($sshDir.$id.'-key.pub',0600);
				chmod($sshDir.$id.'-key',0600);
			}
			
			$server->id=$id;
			if(!empty($server->pwd)) $server->pwd=USecure::encryptAES($server->pwd);
			else unset($server->pwd);
			$server->update();
			self::redirect('/servers');
		}
		$_POST['server']=Server::findOneById($id)->_getData();
		unset($_POST['server']['pwd']);
		self::render();
	}
	
	/** @ValidParams
	 * id > @Required
	 */
	static function deployments(int $id){
		$server=Server::QOne()->byId($id)->with('Deployment',array('with'=>array('Project'=>array('fields'=>'name'))));
		if(empty($server)) notFound();
		self::mset($server);
		self::render();
	}
	
	/** @ValidParams
	 * id > @Required
	 */
	static function cores(int $id){
		$server=Server::QOne()->byId($id)->with('ServerCore',array('with'=>array('Deployment'=>array('isCount'=>true))));
		if(empty($server)) notFound();
		self::mset($server);
		self::render();
	}
	
	
	/** @ValidParams
	 * id > @Required
	 */
	static function core_delete(int $id){
		$core=ServerCore::QOne()->byId($id)->with('Server');
		if(empty($core)) notFound();
		UExec::exec('rm -rf '.$core->server->core_dir.DS.$core->path,$core->server->sshOptions());
	}
	
	/** @ValidParams
	 * id > @Required
	 */
	static function core_update(int $id){
		$server=Server::findOneById($id);
		throw new Exception;
		$scPath=$server->deployCore(false,$resp,false,true);
		mset($res);
		self::render();
	}
	
	/* @ValidParams
	 * id > @Required
	public function create_update_core(int $id){
		$server=Server::QOne()->byId($id);
		if(empty($server)) notFound();
		
		file_put_contents(dirname(CORE).DS.'depl_'.$server->name.'.php',"<?php
define('DS', DIRECTORY_SEPARATOR);
define('CORE','".CORE."');
define('APP','".APP."');
define('ENV','dev');

".'$action'."='deployment';
".'$argv'."=array(
	'type'=>'core','workspace_id'=>".self::$workspace->id.",'server_id'=>".$id.",
	'projectPath'=>__DIR__.DS.'prod'.DS,
	'options'=>array(
		'simulation'=>false,
		'ssh'=>".(true?"array('user'=>'".$server->user."','host'=>'".$server->host."')":'false').",
	)
);
include CORE.'cli.php';
");
		self::redirect('/servers/cores/'.$id);
	} */
}