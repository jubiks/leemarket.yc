<?php
namespace Leemarket\Yc\Mysql;

use \Leemarket\Yc\Auth;

Class User{
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
        return $this->query("https://mdb.api.cloud.yandex.net/managed-mysql/v1/clusters/".$this->clusterId."/users");
    }
    
    public function get(String $name){
        return $this->query("https://mdb.api.cloud.yandex.net/managed-mysql/v1/clusters/".$this->clusterId."/users/".$name);
    }
    
    public function add(String $name, String $password, String $database, Array $roles = array('ALL_PRIVILEGES')){
        return $this->query("https://mdb.api.cloud.yandex.net/managed-mysql/v1/clusters/".$this->clusterId."/users",['userSpec' => ['name' => $name, 'password' => $password, 'permissions' => [['databaseName' => $database, 'roles' => (array)$roles]]]]);
    }
    
    public function update(String $name, Array $fields = array()){
        $arMask = array();
        
        $userSpec = array(
           'updateMask' => ''
        );
        
        if(!empty($fields['password'])){
            $arMask[] = 'password';
            $userSpec['password'] = $fields['password'];
        }
        $arPermissions = array();
        foreach($fields['permissions'] as $k => $permissions){
            if(!empty($permissions['database'])){
                $arMask[] = 'databaseName';
                $arPermissions[$k]['databaseName'] = $permissions['database'];
            }elseif(!empty($permissions['databaseName'])){
                $arMask[] = 'databaseName';
                $arPermissions[$k]['databaseName'] = $permissions['databaseName'];
            }
            if(!empty($permissions['roles'])){
                $arMask[] = 'roles';
                $arPermissions[$k]['roles'] = (array)$permissions['roles'];
            }
        }
        
        $updateMask = array_unique(implode(',',$arMask));
        $userSpec['updateMask'] = $updateMask;
        $userSpec['permissions'] = $arPermissions;
        
        return $this->query("PATCH https://mdb.api.cloud.yandex.net/managed-mysql/v1/clusters/".$this->clusterId."/users/".$name,['userSpec' => $userSpec]);
    }
    
    public function grantPermission(String $name, Array $permissions){
        $arPermissions = array();
        
        if(!empty($permissions['database'])){
            $arPermissions['databaseName'] = $permissions['database'];
        }elseif(!empty($permissions['databaseName'])){
            $arPermissions['databaseName'] = $permissions['databaseName'];
        }else return false;
        
        if(!empty($permissions['roles'])){
            $arPermissions['roles'] = (array)$permissions['roles'];
        }else{
            $arPermissions['roles'] = array('ALL_PRIVILEGES');
        }

        return $this->query("https://mdb.api.cloud.yandex.net/managed-mysql/v1/clusters/".$this->clusterId."/users/".$name.":grantPermission",['permission' => $arPermissions]);
    }
    
    public function revokePermission(String $name, Array $permissions){
        $arPermissions = array();
        if(!empty($permissions['database'])){
            $arPermissions['databaseName'] = $permissions['database'];
        }elseif(!empty($permissions['databaseName'])){
            $arPermissions['databaseName'] = $permissions['databaseName'];
        }else return false;
        if(!empty($permissions['roles'])){
            $arPermissions['roles'] = (array)$permissions['roles'];
        }else{
            $arPermissions['roles'] = array('ALL_PRIVILEGES');
        }
        return $this->query("https://mdb.api.cloud.yandex.net/managed-mysql/v1/clusters/".$this->clusterId."/users/".$name.":revokePermission",['permission' => $arPermissions]);
    }
    
    public function delete(String $name){
        return $this->query("DELETE https://mdb.api.cloud.yandex.net/managed-mysql/v1/clusters/".$this->clusterId."/users/".$name);
    }
}