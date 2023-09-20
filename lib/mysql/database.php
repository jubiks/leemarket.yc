<?php
namespace Leemarket\Yc\Mysql;

use \Leemarket\Yc\Auth;

Class Database{
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
    
    public function getList(){
        return $this->query("https://mdb.api.cloud.yandex.net/managed-mysql/v1/clusters/".$this->clusterId."/databases");
    }
    
    public function get(String $name){
        return $this->query("https://mdb.api.cloud.yandex.net/managed-mysql/v1/clusters/".$this->clusterId."/databases/".$name);
    }
    
    public function add(String $name){
        return $this->query("https://mdb.api.cloud.yandex.net/managed-mysql/v1/clusters/".$this->clusterId."/databases",['databaseSpec' => ['name' => $name]]);
    }
    
    public function delete(String $name){
        return $this->query("DELETE https://mdb.api.cloud.yandex.net/managed-mysql/v1/clusters/".$this->clusterId."/databases/".$name);
    }
}