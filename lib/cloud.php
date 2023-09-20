<?php
namespace Leemarket\Yc;

use Leemarket\Yc\Mysql\Cluster;
use Leemarket\Yc\Mysql\Database;
use Leemarket\Yc\Mysql\User;
use Leemarket\Yc\QM\Queue;
use Leemarket\Yc\QM\Message as QueueMessage;
use Leemarket\Yc\Translation\Translation;

Class Cloud {
    private $iamToken;
    
    function __construct($iamToken = ''){
        $this->iamToken = $iamToken;
    }
    
    private function query($uri, $fields = false){
        if(empty($this->iamToken)) throw new \Exception('No token');
        $auth_head = "Authorization: Bearer ".$this->iamToken;
        if($fields) $fields = json_encode($fields);
        $result = Auth::query($uri,$fields,false,$auth_head);
        return !empty($result) ? json_decode($result,true) : $result;
    }
    
    function mysqlCluster($clusterId,$iamToken = ''){
        return new Cluster($clusterId,$iamToken);
    }
    
    function mysqlDatabase($clusterId,$iamToken = ''){
        return new Database($clusterId,$iamToken);
    }
    
    function mysqlUser($clusterId,$iamToken = ''){
        return new User($clusterId,$iamToken);
    }
    
    function queue(){
        return new Queue();
    }
    
    function queueMessages(string $queueUrl = null){
        return new QueueMessage($queueUrl);
    }
    
    public function getOperation(String $operationId){
        return $this->query("https://operation.api.cloud.yandex.net/operations/".$operationId);
    }

    function translation($iamToken = ''){
        $iamToken = empty($iamToken) ? $this->iamToken : $iamToken;
        return new Translation($iamToken);
    }
}