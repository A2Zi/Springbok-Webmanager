<?php
/** @TableAlias('s') */
class Server extends SSqlModel{
	public
		/** @Pk @AutoIncrement @SqlType('INTEGER') @NotNull
		 */ $id,
		/** @SqlType('VARCHAR(200)') @NotNull
		 */ $name,
		/** @SqlType('VARCHAR(100)') @NotNull
		 */ $host,
		/** @SqlType('VARCHAR(50)') @Null
		 */ $user,
		/** @SqlType('VARCHAR(255)') @Null
		 */ $pwd,
		/** @SqlType('VARCHAR(255)') @NotNull
		 */ $projects_dir,
		/** @SqlType('VARCHAR(255)') @Null
		 */ $plugins_dir,
		/** @SqlType('VARCHAR(255)') @NotNull
		 */ $core_dir,
		/** @SqlType('VARCHAR(20)') @NotNull @Default('prod')
		 */ $env_name;
	
	public $deployments,$cores;
	public static $hasMany=array('Deployment','ServerCore'=>array('dataName'=>'cores'));
	
	
	public function ssh(){
		return array(
			'user'=>$this->user,'host'=>$this->host
		);
	}
	
	public function sshOptions($workspaceName=null){
		return array(
			'host'=>$this->host,'user'=>$this->user,
			'key_file'=>DATA.'ssh/'.($workspaceName===null?AController::$workspace->name:$workspaceName).'/'.$this->id.'-key',
			'known_hosts_file'=>DATA.'ssh/'.'known_hosts',
			'passphrase'=>USecure::decryptAES($this->pwd)
		);
		
	}
	
	public function getVersions(){
		return UExec::exec('cat '.escapeshellarg($this->core_dir.'/versions.json'),$this->sshOptions());
	}
	public function saveVersions($versions){
		return UExec::exec('echo '.escapeshellarg(json_encode($versions)).' > '.escapeshellarg($this->core_dir.'/versions.json'),$this->sshOptions());
	}
	
	public function findLastVersion($deployment,$resp){
		$versions=$this->getVersions();
		if(empty($versions)) return false;
		$versions=json_decode($versions,true);
		foreach($versions as &$v)
			if(!empty($v[1]) && false!==($key=array_search($deployment->path,$v[1]))){
				return $v[0];
			}
		return false;
	}
	
	public function deployCore($deployment,$resp,$simulation=false,$force=false){
		$resp->push("deployCore ".print_r(func_get_args(),true));
		$sshOptions=$this->sshOptions();
		
		$blockFile=UExec::exec('cat '.escapeshellarg($this->core_dir.'/block'),$sshOptions);
		if(!(endsWith($blockFile,'No such file or directory') || endsWith($blockFile,'Aucun fichier ou dossier de ce type'))){
			$resp->push("DEPLOY CORE:\n".'Fuck ! A deployment is already in progress by '.$blockFile);
			return false;
		}
		UExec::exec('echo '.escapeshellarg($this->user).' > '.escapeshellarg($this->core_dir.'/block'),$sshOptions);
		
		$versionsChanged=false;
		$versions=$this->getVersions();
		if(endsWith($versions,'No such file or directory') || endsWith($versions,'Aucun fichier ou dossier de ce type')){
			$versions=array();
			$sc=ServerCore::findAllByServer_id($this->id);
			if(!empty($sc)){
				foreach($sc as $v){
					$versions[$v->version]=array($v->path,array());
					foreach(Deployment::findAllByServer_core_id($v->id) as $d)
						$versions[$v->version][1][]=$d->path;
				}
				$versionsChanged=true;
			}
		}elseif(!empty($versions)) $versions=json_decode($versions,true);
		else{
			$resp->push('ERROR: version is empty');
			return false;
		}
		
		if(!isset($versions[Springbok::VERSION]) || $force){
			$resp->push('CORE VERSION IS NOT UP-TO-DATE ON SERVER');
			$scPath=isset($versions[Springbok::VERSION]) ? $versions[Springbok::VERSION] : 'springbok-'.date('Y-m-d');
			$versionsChanged=true;
			
			/*if(empty($versions)){
				$resp->push('ERROR versions : '.json_encode($versions));
				return false;
			}*/
			$updateVersion=false;
			foreach($versions as $sbVersion=>$v)
				if($v[0]===$scPath){
					$updateVersion=true;
					$versions[Springbok::VERSION]=$v;
					unset($versions[$sbVersion]);
				}
			if(!$updateVersion) $versions[Springbok::VERSION]=array($scPath,array());
			
			$options=array('simulation'=>$simulation,'exclude'=>array('.svn/','/enhance_def.php','/pull.php','/enhance_cli.php','/enhance_v2.php'),'ssh'=>$sshOptions);
			$resp->push("DEPLOY CORE: Libs to ".$this->core_dir.'/libs/'.' (this may take a while...)');
			$resp->push(UExec::rsync(dirname(CORE).'/libs/prod/',$this->core_dir.'/libs/',$options));
			$resp->push("DEPLOY CORE: Prod to ".$this->core_dir.DS.$scPath);
			$resp->push(UExec::rsync(dirname(CORE).'/prod/',$this->core_dir.DS.$scPath.DS,$options));
		
			$resp->push('Make sure the rights are good'.PHP_EOL
				.UExec::exec('cd '.escapeshellarg($this->core_dir).' && chmod -R --quiet 775 libs/ '.$scPath.DS,$options['ssh']));
		
			
			foreach($versions as &$v)
				if(!empty($v[1]) && false!==($key=array_search($deployment->path,$v[1]))){
					unset($v[1][$key]);
					$versionsChanged=true;
				}
			
			$versions[Springbok::VERSION][1][]=$deployment->path;
		}else{
			$resp->push('This core is already up-to-date');
			$scPath=$versions[Springbok::VERSION][0];
		}
		if($versionsChanged) $resp->push("DEPLOY CORE: Update Versions\n".$this->saveVersions($versions));
		
		return $scPath;
		/*$res.=
		$sc=ServerCore::findOneIdAndPathByServer_idAndVersion($this->id,Springbok::VERSION);
		
		if($sc===false || $force){
			$res.='-- CORE VERSION IS NOT UP-TO-DATE ON SERVER --'.PHP_EOL;
			
			$sc_path='springbok-'.date('Y-m-d');
			if($id=ServerCore::findValueIdByServer_idAndPath($this->id,$sc_path))
				ServerCore::updateOneFieldByPk($id,'version',Springbok::VERSION);
			else{
				$sc=new ServerCore();
				$sc->server_id=$this->id;
				$sc->version=Springbok::VERSION;
				$sc->path=$sc_path;
				$sc->insert();
			}
			
			$options=array('simulation'=>$simulation,'exclude'=>array('.svn/','/enhance_def.php','/pull.php','/enhance_cli.php','/enhance_v2.php'),'ssh'=>$sshOptions);
			$res.=UExec::rsync(dirname(CORE).'/libs/',$this->core_dir.'/libs/',$options);
			$res.=UExec::rsync(dirname(CORE).'/prod/',$this->core_dir.DS.$sc_path.DS,$options);
		}else $res.='This core is already up-to-date'.PHP_EOL;
		*/
	}

	public function removeOldCores(&$resp){
		$sshOptions=$this->sshOptions();
		$versions=$this->getVersions();
		$versions=json_decode($versions,true);
		
		foreach($versions as $k=>&$v){
			if(empty($v[1])){
				UExec::exec('rm -rf '.$this->core_dir.DS.$v[0],$sshOptions);
				$resp->push('DELETE OLD CORE : '.$v[0]);
				unset($versions[$k]);
			}
		}

		$this->saveVersions($versions);
		$resp->push('remove block (from removeOldCores)');
		$this->removeBlockFile($sshOptions);
	}
	
	public function removeBlockFile($sshOptions=null){
		if($sshOptions===null) $sshOptions=$this->sshOptions();
		UExec::exec('rm '.escapeshellarg($this->core_dir.'/block'),$sshOptions);
	}
	
/*
	public function connect(){
		include CORE.'libs/phpseclib/Net/SSH2.php';
		include CORE.'libs/phpseclib/Crypt/RSA.php';
		$key = new Crypt_RSA();
		$key->setPassword(USecure::decryptAES($this->pwd));
		$key->loadKey(file_get_contents($this->getPrivKeyFile()));
		$ssh = new Net_SSH2($this->host);
		if(!$ssh->login($this->user,$key)) return false;
		return $ssh;
	}
*/
}