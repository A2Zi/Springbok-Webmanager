<?php
class ABasicResp{
	private $resp;
	public function id($id){
		$this->resp.="\n".$id."\n";
	}
	
	public function event($eventName){
		$this->resp.="\n".$eventName."\n";
	}
	
	public function data($data){
		$this->resp.=$data."\n";
	}
	
	public function jsonData($data){
		$this->resp.=$data."\n";
	}
	
	public function comment($comment){
		$this->resp.=': '.$comment."\n";
	}
	
	public function push(){
		$this->resp.="\n";
	}
	
	public function getResp(){
		return $this->resp;
	}
}