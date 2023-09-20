<?php
namespace Leemarket\Yc\QM;

Class Message{
    private $ymq;
    private $queueUrl;
    var $delaySeconds = false;
    var $messageGroupId = false;
    var $waitTimeSeconds = 10;
    var $maxNumberOfMessages = 1;
    var $visibilityTimeout = 300;
    var $lastMessageId = null;
    var $lastMessageReceiptHandle = null;
    
    function __construct(string $queueUrl = null){
        putenv('HOME=/home/bitrix');
        
        $this->ymq = new \Aws\Sqs\SqsClient([
            'version' => 'latest',
            'region' => 'ru-central1',
            'endpoint' => 'https://message-queue.api.cloud.yandex.net',
        ]);
        
        if($queueUrl) $this->queueUrl = $queueUrl;
    }
    
    public function send(string $message, string $queueUrl = null, array $attributes = array()){
        if(!$queueUrl) $queueUrl = $this->queueUrl;
        if(!$queueUrl) throw new \Exception('Required parameter not passed: QueueUrl');
        
        $arMessage = array(
            'QueueUrl' => $queueUrl,
            'MessageBody' => $message
        );
        
        if($this->delaySeconds !== false){
            $arMessage['DelaySeconds'] = $this->delaySeconds;
        }
        
        $i = 1;
        foreach($attributes as $name => $value){
            $arMessage['MessageAttributeName.'.$i] = $name;
            $arMessage['MessageAttributeValue.'.$i] = $value;
            $i++;
        }
        
        if(stripos($queueUrl,'.fifo') !== false){
            $arMessage['MessageDeduplicationId'] = hash('sha256',$message);
        }
        
        if($this->messageGroupId !== false){
            $arMessage['MessageGroupId'] = $this->messageGroupId;
        }
        
        return $this->ymq->sendMessage($arMessage);
    }
    
    public function get(string $queueUrl = null, array $attributes = array(), string $receiveRequestAttemptId = null){
        if(!$queueUrl) $queueUrl = $this->queueUrl;
        if(!$queueUrl) throw new \Exception('Required parameter not passed: QueueUrl');
        
        $arMessage = array(
            'QueueUrl' => $queueUrl,
            'MaxNumberOfMessages' => $this->maxNumberOfMessages,
            'WaitTimeSeconds' => $this->waitTimeSeconds,
            'VisibilityTimeout' => $this->visibilityTimeout
        );
        
        $i = 1;
        foreach($attributes as $name => $value){
            $arMessage['MessageAttributeName.'.$i] = $name;
            $i++;
        }
        
        if($receiveRequestAttemptId){
            $arMessage['ReceiveRequestAttemptId'] = $receiveRequestAttemptId;
        }
        
        $result = $this->ymq->receiveMessage($arMessage);
        if($this->maxNumberOfMessages == 1){
            $r = $result->toArray();
            $r = array_shift($r['Messages']);
            $this->lastMessageReceiptHandle = $r['ReceiptHandle'];
            $this->lastMessageId = $r['MessageId'];
        }
        return $result;
    }
    
    public function changeVisibility(int $timeout, string $receiptHandle = null, string $queueUrl = null){
        if(!$receiptHandle) $receiptHandle = $this->lastMessageReceiptHandle;
        if(!$receiptHandle) return false;
        
        if(!$queueUrl) $queueUrl = $this->queueUrl;
        if(!$queueUrl) throw new \Exception('Required parameter not passed: QueueUrl');
        
        $timeout = intval($timeout) > 43200 ? 43200 : intval($timeout);
        
        $this->ymq->ChangeMessageVisibility([
            'QueueUrl' => $queueUrl,
            'ReceiptHandle' => $receiptHandle,
            'VisibilityTimeout' => $timeout
        ]);
        
        return true;
    }
    
    public function delete(string $receiptHandle = null, string $queueUrl = null){
        if(!$receiptHandle) $receiptHandle = $this->lastMessageReceiptHandle;
        if(!$receiptHandle) return false;
        
        if(!$queueUrl) $queueUrl = $this->queueUrl;
        if(!$queueUrl) throw new \Exception('Required parameter not passed: QueueUrl');
        
        $this->ymq->deleteMessage([
            'QueueUrl' => $queueUrl,
            'ReceiptHandle' => $receiptHandle,
        ]);
        
        if($this->lastMessageReceiptHandle === $receiptHandle){
            $this->lastMessageReceiptHandle = null;
            $this->lastMessageId = null;
        }
        
        return true;
    }
    
    public function sendBatch(array $messages, string $queueUrl = null){
        if(!$queueUrl) $queueUrl = $this->queueUrl;
        if(!$queueUrl) throw new \Exception('Required parameter not passed: QueueUrl');
        
        $arMessages = array();
        
        $k = 1;
        foreach($messages as $message){
            $mess = array(
                'Id' => $k,
                'MessageBody' => !empty($message['MessageBody']) ? $message['MessageBody'] : $message['message']
            );
            
            if(isset($message['DelaySeconds']) && intval($message['DelaySeconds']) >= 0){
                $mess['DelaySeconds'] = $message['DelaySeconds'];
            }elseif($this->delaySeconds !== false){
                $mess['DelaySeconds'] = $this->delaySeconds;
            }
            
            if($message['MessageAttribute']){
                $mess['MessageAttribute'] = $message['MessageAttribute'];
            }
            
            if(stripos($queueUrl,'.fifo') !== false){
                $mess['MessageDeduplicationId'] = hash('sha256',$mess['MessageBody']);
            }
            
            if($this->messageGroupId !== false){
                $mess['MessageGroupId'] = $this->messageGroupId;
            }
            
            $arMessages[] = array(
                'SendMessageBatchRequestEntry.'.$k++ => $mess,
                'QueueUrl' => $queueUrl
            );
        }
        
        return $this->ymq->SendMessageBatch($arMessages);
    }
    
    
}