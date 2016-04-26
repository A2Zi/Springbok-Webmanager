<?php
/** @TableAlias('d') */
class Deployment extends SSqlModel{
	public
		/** @Pk @AutoIncrement @SqlType('INTEGER') @NotNull
		*/ $id,
		/** @SqlType('INTEGER') @NotNull
		* @Index
		*/ $server_id,
		/** @SqlType('INTEGER') @NotNull
		* @Index
		*/ $project_id,
		/** @SqlType('INTEGER') @Null @Default(NULL)
		*/ $server_core_id,
		/** @SqlType('TEXT') @NotNull
		*/ $path,
		/** @SqlType('TEXT') @NotNull @Default("'/'")
		*/ $base_url,
		/** @SqlType('TEXT') @Null @Default('')
		*/ $ssh,
		/** @SqlType('VARCHAR(20)') @NotNull @Default('prod')
		 */ $env_name;
	
	public static $belongsTo=array('Server','Project');
	
	public function path($NULL=NULL){
		return $this->server->projects_dir.$this->path;
	}
	
	public function name(){
		return $this->server->host.':'.$this->path();
	}
	
	
	public function getProjectPath(){
		$projectPath=$this->project->path().'/prod/';
		if(!is_dir($projectPath))
			throw new Exception('Project path does not exists: '.$projectPath);
		return $projectPath;
	}
	
	
	public function doDeployment($workspaceId,$resp=null,$simulation=false,$backup=false,$schema=false){$schema=true;
		$resp=new AHDeploymentResponse($resp);
		/* PROJECT PATH */
		$projectBasePath=rtrim($this->project->path(),'/').'/';
		$projectPath=$this->getProjectPath();
		$entries=$this->project->entries();
		$envConfig=$this->project->envConfig($this->env_name);
		$deploymentConfig=$this->project->deploymentConfig($this->server->host);
		$target=rtrim($this->path(),'/').DS;
		
		$resp->push('Hi ! Deployment : '.$projectBasePath.' ===> '.$this->server->host.':'.$target);
		
		/* Pre - deployment */
		// REQUIRED : pre-dbprocessing by PROD (use APP instead of dirname(APP))
		copy($projectBasePath.'currentDbVersion',$projectPath.'currentDbVersion');
		$projectStopBeforeDbEvolution=isset($_REQUEST['projectStopBeforeDbEvolution']) && $_REQUEST['projectStopBeforeDbEvolution']=='1';
		
		
		/* Prepare SSH */
		$sshOptions=$this->server->sshOptions();
		
		$resp->push($this->stopDaemon($workspaceId));
		$resp->push($this->startDaemon($workspaceId));
		
		//base define
		
		$isPhp5_4='yes'===trim(UExec::exec('cd / && php -r "echo version_compare(PHP_VERSION,\'5.4.0\')===-1 ? \'no\' : \'yes\';"',$sshOptions));
		
		$baseDefine="<?php
define('DS', DIRECTORY_SEPARATOR);
define('CORE','".dirname(CORE)."/prod/');
define('CLIBS','".dirname(CORE)."/libs/prod/');
define('APP', __DIR__.DS);";

		$schemaContent=$baseDefine."
".'$action'."='schema';
include CORE.'cli.php';";
		file_put_contents($projectPath.'schema.php',$schemaContent);
		
		$resp->push($resSchemaProcess=UExec::exec('php '.escapeshellarg($projectPath.'schema.php')));
		if('Schema processed'!==substr($resSchemaProcess,-strlen('Schema processed'))) return false;
		
		// Get current db version
		$currentLocalDbVersion=trim(UFile::getContents($projectBasePath.'currentDbVersion'));
		$currentServerDbVersion=trim(UExec::exec('cd / && cat '.escapeshellarg($target.'currentDbVersion'),$sshOptions));
		if(stripos($currentServerDbVersion,'denied')!==false) return $resp->push('DENIED : '.$currentServerDbVersion);
		$resp->push('DB Versions : server='.$currentServerDbVersion.', local='.$currentLocalDbVersion);
		$stopProject=$currentServerDbVersion != $currentLocalDbVersion || (isset($_REQUEST['projectStop']) && $_REQUEST['projectStop']=='1')
							|| $projectStopBeforeDbEvolution;
		$resp->push('stop : '.($stopProject?'true':'false'));
		
		
		/* SLAVES - REPLICATION */
		if($deploymentConfig){
			$resp->push('Slaves: '.(empty($deploymentConfig['slaves'])?'empty':count($deploymentConfig['slaves'])));
			if(!empty($deploymentConfig['slaves'])){
				try{
					$soap = new SoapClient("https://www.ovh.com/soapi/soapi-re-1.59.wsdl");
					
					//login
					$session = $soap->login($deploymentConfig['ovh-nic'],$deploymentConfig['ovh-password'],'fr',false);
					$resp->push('=> OVH: login successfull');
					
					foreach($deploymentConfig['slaves'] as $slave){
						//http://www.ovh.com/soapi/fr/?method=dedicatedFailoverUpdate
						foreach($slave['failovers'] as $failover){
							if(CSimpleHttpClient::get($failover.':3000/ip.txt')===$deploymentConfig['master']['ip']) continue; //already in master
							$ok=false;
							while($ok===false){
								$ok=true;
								try{
									$soap->dedicatedFailoverUpdate($session,$slave['hostname'],$failover,$deploymentConfig['master']['ip']);
									$resp->push('Failover: '.$failover.' to '.$deploymentConfig['master']['ip']);
								}catch(SoapFault $fault){
									$resp->push($fault->faultcode);
									$resp->push($fault->faultstring);
									if($fault->faultstring==='Action already done'){
										//$ok=true;
										throw $fault;
										//the hostname of the server is probably wrong ?
									}else throw $fault;
								}
							}
						}
					}
					
					//logout
					$soap->logout($session);
					$resp->push('=> OVH: logout successfull');

				}catch(SoapFault $fault){
					$resp->push('=> FAULT OVH'.PHP_EOL
							.$fault);
					return false;
					
				}
				//TODO : do that after deployed core (this can leave some time)
				//wait for all slave to go to the master
				foreach($deploymentConfig['slaves'] as $slave){
					foreach($slave['failovers'] as $failover){
						while(true){
							$response=CSimpleHttpClient::get($failover.':3000/ip.txt');
							if($response===$deploymentConfig['master']['ip']) break;
							usleep(50);
						}
						$resp->push($failover.' ==> '.$response);
					}
				}
			}
		}
		
		
		/* DEPLOY CORE */
		$scPath=$this->server->deployCore($this,$resp,$simulation);
		if($scPath===false) return;
		
		$resp->push($this->stopDaemon($workspaceId));
		$resp->push($this->startDaemon($workspaceId));
		
		/* DO PROJECT DEPLOYMENT */
		if (!$simulation){
			 if ($backup){
			 	$options=array('simulation'=>$simulation,'exclude'=>NULL,'ssh'=>$sshOptions); // --exclude .* ?
			 	$targetBackup = $backup.DS;
				$resp->push('BACKUP'.PHP_EOL.UExec::rsync($projectPath,$targetBackup,$options));
			 }
		}
		
		$baseDefine=$this->baseDefine($scPath,$isPhp5_4);
		
		
		/* -- -- -- */
		
		
		
		/* -- -- -- */
		
		$options=array('simulation'=>$simulation,'ssh'=>$sshOptions,'exclude'=>array());
		
		
		$tmpfname = tempnam('/tmp','projectdepl');
		
		file_put_contents($tmpfname,'<?php return \''.$this->env_name.'\';');
		$resp->push('=> SEND ENV'.PHP_EOL
					.UExec::copyFile($tmpfname,$target.'env.php',$sshOptions));
		
		
		file_put_contents($tmpfname,"<?php".$baseDefine."
".'$action'."='schema';
include CORE.'cli.php';");
		$resp->push('COPY schema.php'.PHP_EOL.UExec::copyFile($tmpfname,$target.'schema.php',$sshOptions));
		
		file_put_contents($tmpfname,"<?php".$baseDefine."
".'$action'."='job';
include CORE.'cli.php';");
		$resp->push('COPY job.php'.PHP_EOL.UExec::copyFile($tmpfname,$target.'job.php',$sshOptions));
		
		
		file_put_contents($tmpfname,"<?php".$baseDefine."
".'unset($argv[0]); $action'."=".'array_shift($argv);'."
include CORE.'cli.php';");
		$resp->push('COPY cli.php'.PHP_EOL.UExec::copyFile($tmpfname,$target.'cli.php',$sshOptions));
		
		
		
		/* Prepare content */
		
		$lastWebFolder=UExec::exec('cd / && cat '.escapeshellarg($target.'lastWebFolder'),$sshOptions);
		
		$webFolder=shortAlphaNumber_enc(floor((time()/60-strtotime(date('Y').'-01-01')/60)/3)); //nombres de (3) minutes depuis le début de l'année (2 minutes : on est à 4 lettres à la fin de l'année ; 3 on reste à 3)
		if(in_array($webFolder,array('css','js','img','ie'))) $webFolder.='_';
		
		$jsFilenames=array('global.js','jsapp.js','index.js');
		foreach($entries as $entry){
			$jsFilenames[]=$entry.'.js';
			$options['exclude'][]='/'.$entry.'.php';
		}
		foreach($jsFilenames as $jsfilename){
			if(file_exists($filename=$projectPath.'web/js/'.$jsfilename)){
				$jsFile=file($filename);
				$resp->push('First line : '.$jsfilename."\n".$jsFile[0]);
				$line0="'use strict';var baseUrl='".$this->base_url."',staticUrl=baseUrl+'web/',webUrl=staticUrl+'".$webFolder."/',imgUrl=webUrl+'img/',version='".$webFolder."'";
				if($jsfilename==='admin.js') $line0.=',entryUrl='.json_encode($envConfig['siteUrl'],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
				$line0.=";\n";
				if($jsFile[0]!=$line0){
					$jsFile[0]=substr($jsFile[0],0,12)==='var baseUrl='||substr($jsFile[0],0,12+13)==="'use strict';var baseUrl=" ? $line0 : $line0.$jsFile[0];
					file_put_contents($filename,implode('',$jsFile));
				}
			}
		}
		
		
		
		$options['exclude']=array('.svn/');
		if(is_dir($dbPath=$projectBasePath.'db/'))
			$resp->push('SYNC DB DIR'.PHP_EOL.UExec::rsync($dbPath,$target.'db/',$options));
		/*if(is_dir($soapPath=$projectBasePath.'data/soap/')){
			$options['exclude']=array('.svn/','*.cache');
			$resp->push('SYNC DB DIR'.PHP_EOL.UExec::rsync($soapPath,$target.'data/soap/',$options));
		}*/
		
		$options['exclude']=array('.svn/');
		
		//$resp->push('SYNC dbEvolutions dir'.PHP_EOL.UExec::rsync($projectPath.'dbEvolutions',$target.'dbEvolutions/',$options));
		
		
		if($stopProject) $resp->push($this->stop($scPath));
		
		$options['exclude']=array('logs/','web/files','db','data','.htaccess','authfile','/schema.php','/job.php','/cli.php',
				'/env.php','/index.php',/*'/dbEvolutions',*/'/currentDbVersion','/lastWebFolder','/web/'.$lastWebFolder);
		foreach($entries as $entry) $options['exclude'][]='/'.$entry.'.php';
		/*$res.=UExec::rsync(dirname(CORE).DS.'prod'.DS,$this->server->core_dir.DS.$sc->path.DS,$options);*/
		$resp->push('SYNC'.PHP_EOL.UExec::rsync($projectPath,$target,$options));
		
		if(!$projectStopBeforeDbEvolution){
			$resp->push('EXECUTE schema.php'.PHP_EOL
				.($resSchema=UExec::exec('php '.escapeshellarg($target.'schema.php'),$options['ssh']+array('forcePseudoTty'=>true))));
			$shemaProcessSuccess=('Schema processed'===substr($resSchema,-strlen('Schema processed')));
		
		}
		
		$resp->push('CREATE symb link: cd '.escapeshellarg($target.'web/').' && ln -s . "'.$webFolder.'"'.PHP_EOL
			.UExec::exec('cd '.escapeshellarg($target.'web/').' && ln -s .'.($webFolder[0]==='-'?' --':'').' "'.$webFolder.'"',$options['ssh']));
		
		$resp->push('Make sure the rights are good'.PHP_EOL
			.UExec::exec('cd '.escapeshellarg($target).' ; chmod -R --quiet 775 web/ controllers* views* config/ helpers/ libs/ models/ ; chmod --quiet 664 *.php',$options['ssh']));
		
		//$resp->push('Delete CACHE files'.PHP_EOL
		//	.UExec::exec('cd '.escapeshellarg($target.'data/').' && rm -f cache/* ; rm -f cache/*/* ; rm -f elementsCache/* ; rm -f elementsCache/*/*',$options['ssh']));
		
		$deleteCache='cd '.escapeshellarg($target.'data/').' && find cache/ -type f -delete ; find elementsCache/ -type f -delete -name "*_view"';
		
		//if($stopProject) $resp->push('Delete CACHE files'.PHP_EOL.UExec::exec($deleteCache,$options['ssh']));
		
		if(!$projectStopBeforeDbEvolution && $shemaProcessSuccess) $resp->push($this->start($scPath,$webFolder,$isPhp5_4));
		
		//if(!$stopProject) $resp->push('Delete CACHE files'.PHP_EOL.UExec::exec($deleteCache,$options['ssh']));
		
		UExec::exec('cd / && echo '.escapeshellarg($webFolder).' > '.escapeshellarg($target.'lastWebFolder'),$sshOptions);
		
		
		/* UPDATE CRON */
	
		if(false && file_exists($jobsFilePath=$projectPath.'config/jobs.php')){
			$jobs=include $jobsFilePath;
			
			/*
			 * minute (0-59), hour (0-23, 0 = midnight), day (1-31), month (1-12), weekday (0-6, 0 = Sunday), command
			 * x,y = at x and y
			 * x-y = every _ between x and y
			 * * /x = every x _ => * /10 => 0,10,20,30,40,50
			*/
			$cronfile='';
			
			foreach($jobs as $jobName=>$job){
				$cronfile.=$job.' www-data php '.escapeshellarg($target.'job.php').' '.$jobName.PHP_EOL;
			}
			
			if(!empty($cronfile)){
				file_put_contents($tmpfname,$cronfile);
				$resp->push('COPY CRON'.PHP_EOL
					.UExec::copyFile($tmpfname,'/etc/cron.d/springbok-'.$this->id,$sshOptions));
			}
		}
		
		if(!$projectStopBeforeDbEvolution && $shemaProcessSuccess){
			if(file_exists($jobFilePath=$projectPath.'jobs/AfterDeployJob.php')){
				$resp->push('EXECUTE job AfterDeploy'.PHP_EOL
					.UExec::exec('php '.escapeshellarg($target.'job.php').' AfterDeploy',$options['ssh']+array('forcePseudoTty'=>true)));
			}
			
			/* Delete old cores */
			$this->server->removeOldCores($resp);
		
		}else {
			$resp->push('remove block (from doDeployment)');
			$this->server->removeBlockFile();
		}
		
		/* SLAVES - REPLICATION */
		if($deploymentConfig && $shemaProcessSuccess){
			// START COMMENT HERE FOR MOVING Allo FAILOVER to SV3
			if(!empty($deploymentConfig['slaves'])){
				//rsync how to check it's done ?
				$resp->push('Sleeping for 2 minutes...');
				sleep(2*60);
				try{
					$soap = new SoapClient("https://www.ovh.com/soapi/soapi-re-1.59.wsdl");
					
					//login
					$session = $soap->login($deploymentConfig['ovh-nic'],$deploymentConfig['ovh-password'],'fr',false);
					$resp->push('=> OVH: login successfull');
					
					foreach($deploymentConfig['slaves'] as $slave){
						//http://www.ovh.com/soapi/fr/?method=dedicatedFailoverUpdate
						foreach($slave['failovers'] as $failover){
							$soap->dedicatedFailoverUpdate($session,$deploymentConfig['master']['hostname'],$failover,$slave['ip']);
							$resp->push('Failover: '.$failover.' to '.$slave['ip']);
						}
					}
					
					//logout
					$soap->logout($session);
					$resp->push('=> OVH: logout successfull');

				}catch(SoapFault $fault){
					$resp->push('=> FAULT OVH'.PHP_EOL
							.$fault);
					return false;
					
				}
				$resp->push('Failovers updated');
				//no need to wait
			}
			// STOP COMMENT HERE FOR MOVING Allo FAILOVER to SV3
		}
		
		$this->stopDaemon($workspaceId);
		
		/* Delete tmp file */
		unlink($tmpfname);
	}
	
	
	private function baseDefine($scPath,$isPhp5_4=true){
		return "
define('DS', DIRECTORY_SEPARATOR);
define('CORE','".rtrim($this->server->core_dir,'/').DS.$scPath.DS."');
define('CLIBS','".rtrim($this->server->core_dir,'/')."/libs/');
define('APP', __DIR__.DS);"
		.($isPhp5_4===false ? "include CORE.'php5-3.php';" : '');
	}
	
	
	private $daemonStarted;
	private function startDaemon($workspaceId){
		if(true===($resDaemon=CDaemons::startIfNotAlive('Ssh',$workspaceId.'-'.$this->server_id))){
			$daemonStarted=true;
			sleep(2);
		}
		return 'Start daemon: '.($resDaemon===true?'succeeded':$resDaemon).PHP_EOL;
	}
	private function stopDaemon($workspaceId){
		return 'Kill daemon : '.(CDaemons::kill('Ssh',$workspaceId.'-'.$this->server_id)?'succeeded':'failed');
	}
	
	/* NEED : project,server */
	public function start($scPath=NULL,$appVersion,$isPhp5_4=true){
		if($scPath===null){
			$scPath=$this->server->findLastVersion($this,$resp=new ABasicResp(),false);
			if(empty($scPath)) return $resp->getResp();
			$res=$resp->getResp();
		}
		
		$indexContentStarted="<?php".$this->baseDefine($scPath,$isPhp5_4)."
define('APP_DATE',".time().");define('APP_VERSION','".$appVersion."'); define('WEB_FOLDER','".$appVersion."/');
include CORE.'app.php';";

		$tmpfname = tempnam('/tmp','projectstart');
		file_put_contents($tmpfname,$indexContentStarted);
		$entries=$this->project->entries();
		$sshOptions=$this->server->sshOptions();
		$target=$this->path().DS;

		$res='=> START PROJECT'.PHP_EOL
			.UExec::copyFile($tmpfname,$target.'index.php',$sshOptions);
		
		if(!empty($entries)){
			file_put_contents($tmpfname,'<?php include __DIR__."/index.php";');
			foreach($entries as $entry)
				$res.=PHP_EOL.'=> START ENTRANCE: '.$entry.PHP_EOL
					.UExec::copyFile($tmpfname,$target.$entry.'.php',$sshOptions);
		}
		
		if(file_exists($deamonsFilePath=$this->getProjectPath().'config/daemons.php')){
			$res.=PHP_EOL.'=> START daemons'.PHP_EOL;
			$res.= UExec::exec('php '.escapeshellarg($target.'cli.php').' daemons',$options['ssh']+array('forcePseudoTty'=>true));
		}
		
		unlink($tmpfname);
		return $res;
	}
	
	/* NEED : project,server */
	public function stop($scPath=null,$workspaceId=null,$isPhp5_4=true){
		$res=''; $daemonStarted=false;
		if($scPath===null){
			if(empty($workspaceId)) return 'Deployment::stop: missing "workspaceId"';
			$res.=$this->startDaemon($workspaceId);
			
			$scPath=$this->server->findLastVersion($this,$resp=new ABasicResp(),false);
			if(empty($scPath)) return $resp->getResp();
			$res.=$resp->getResp();
		}
		
		
		$indexContentStopped="<?php
header('HTTP/1.1 503 Service Temporarily Unavailable',true,503);".$this->baseDefine($scPath,$isPhp5_4)."
if(file_exists((".'$filename'."=CORE.'maintenance.php'))){
	define('APP_DATE',".time()."); define('APP_VERSION',''); define('WEB_FOLDER','');
	include ".'$filename'.";
}else echo '<h1>503 Service Temporarily Unavailable</h1>';";

		$tmpfname = tempnam('/tmp','projectstop');
		file_put_contents($tmpfname,$indexContentStopped);
		$entries=$this->project->entries();
		$sshOptions=$this->server->sshOptions();
		$target=$this->path().DS;
		
		$res.=PHP_EOL.'=> STOP PROJECT'.PHP_EOL
			.UExec::copyFile($tmpfname,$target.'index.php',$sshOptions);
		/*if(!empty($entries))
			foreach($entries as $entry)
				$res.=PHP_EOL.'=> STOP ENTRANCE: '.$entry.PHP_EOL
					.UExec::copyFile($tmpfname,$target.$entry.'.php',$sshOptions);
		*/
		unlink($tmpfname);
		
		if(file_exists($deamonsFilePath=$this->getProjectPath().'config/daemons.php')){
			//$res.=PHP_EOL.'=> KILL DAEMONS: '.$entry.PHP_EOL.UExec::exec('killall php',$sshOptions);
		}

		$this->stopDaemon($workspaceId);
		
		return $res;
	}
}