<?php
namespace Leemarket\Yc\Mysql;

use \Leemarket\Yc\Auth;

Class Cluster{
    var $clusterId;
    private $iamToken;
    
    function __construct(String $clusterId, String $iamToken){
        $this->clusterId = $clusterId;
        $this->iamToken = $iamToken;
    }
    
    private function query($uri, $fields = false){
        if(empty($this->iamToken)) throw new \Exception('No token');
        $auth_head = "Authorization: Bearer ".$this->iamToken;
        if($fields) $fields = json_encode($fields);
        $result = Auth::query($uri,$fields,false,$auth_head);
        return !empty($result) ? json_decode($result,true) : $result;
    }
    
    public function get(){
        return $this->query("https://mdb.api.cloud.yandex.net/managed-mysql/v1/clusters/".$this->clusterId);
    }
    
    public function getList(String $folderId){
        return $this->query("https://mdb.api.cloud.yandex.net/managed-mysql/v1/clusters/",['folderId' => $folderId]);
    }
    
    public function backup(){
        return $this->query("https://mdb.api.cloud.yandex.net/managed-mysql/v1/clusters/".$this->clusterId.":backup");
    }
    
    public function getHosts(){
        return $this->query("https://mdb.api.cloud.yandex.net/managed-mysql/v1/clusters/".$this->clusterId."/hosts");
    }
}