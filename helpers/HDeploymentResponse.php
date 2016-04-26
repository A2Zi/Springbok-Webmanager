<?php
class AHDeploymentResponse{
	private $response;
	
	public function __construct($response){
		$this->response=&$response;
	}
	
	public function push($message){
		$message=date('\[H:i:s\] ').$message;
		CLogger::get('AHDeplResp')->log($message);
		if($this->response !== null){
			$this->response->data($message);
			$this->response->push();
		}else{
			echo $message;
		}
	}
}
