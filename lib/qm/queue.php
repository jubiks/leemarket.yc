<?php
namespace Leemarket\Yc\QM;

Class Queue{
    private $ymq;
    
    function __construct(){
        putenv('HOME=/home/bitrix');
        
        $this->ymq = new \Aws\Sqs\SqsClient([
            'version' => 'latest',
            'region' => 'ru-central1',
            'endpoint' => 'https://message-queue.api.cloud.yandex.net',
        ]);
    }
    
    public function add(string $name, array $attributes = array()){
        $queue = array('QueueName' => $name);
        $i = 1;
        foreach($attributes as $name => $value){
            $queue['Attribute.'.$i.'.Name'] = $name;
            $queue['Attribute.'.$i.'.Value'] = $value;
            $i++;
        }
        return $this->ymq->createQueue($queue);
    }
    
    public function delete(string $queueUrl){
        return $this->ymq->deleteQueue(['QueueUrl' => $queueUrl]);
    }
    
    public function getAttributes(string $queueUrl, array $attributes = array()){
        $queue = array('QueueUrl' => $queueUrl);
        $i = 1;
        foreach($attributes as $name => $value){
            $queue['Attribute.'.$i.'.Name'] = $name;
            $queue['Attribute.'.$i.'.Value'] = $value;
            $i++;
        }
        return $this->ymq->GetQueueAttributes($queue);
    }
    
    public function setAttributes(string $queueUrl, array $attributes = array()){
        $queue = array('QueueUrl' => $queueUrl);
        $i = 1;
        foreach($attributes as $name => $value){
            $queue['Attribute.'.$i.'.Name'] = $name;
            $queue['Attribute.'.$i.'.Value'] = $value;
            $i++;
        }
        return $this->ymq->SetQueueAttributes($queue);
    }
    
    public function getUrl(string $name){
        return $this->ymq->GetQueueUrl(['QueueName' => $name]);
    }
    
    public function getList(){
        return $this->ymq->ListQueues();
    }
    
    public function purge(string $queueUrl){
        return $this->ymq->PurgeQueue(['QueueUrl' => $queueUrl]);
    }
}