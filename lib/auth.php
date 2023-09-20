<?php
namespace Leemarket\Yc;

use \Jose\Component\Core\AlgorithmManager;
use \Jose\Component\Core\Converter\StandardConverter;
use \Jose\Component\KeyManagement\JWKFactory;
use \Jose\Component\Signature\JWSBuilder;
use \Jose\Component\Signature\Algorithm\PS256;
use \Jose\Component\Signature\Serializer\CompactSerializer;

Class Auth {
    
    private $service_account_id;
    private $key_id;
    private $path_to_auth_key_file = '~/key.json';
    private $path_private_key_file = '~/private.pem';
    
    function __construct(string $service_account_id, string $key_id){
        $this->service_account_id = $service_account_id;
        $this->key_id = $key_id;
    }
    
    public function setPathAuthKey($path){
        $this->path_to_auth_key_file = realpath($path);
    }
    
    public function setPathPrivateKey($path){
        $this->path_private_key_file = $path;
    }
    
    private function createFromKeyFile(){
        if(!file_exists($this->path_private_key_file) && file_exists($this->path_to_auth_key_file)){
            $content = file_get_contents($this->path_to_auth_key_file);
            $json_key = json_decode($content,true);
            if(!empty($json_key['private_key'])){
                file_put_contents($this->path_private_key_file,$json_key['private_key']);
            }
        }elseif(!file_exists($this->path_to_auth_key_file)){
            throw new \Exception('File with authorization key not found');
        }
        
        if(file_exists($this->path_private_key_file))
            return JWKFactory::createFromKeyFile($this->path_private_key_file);
            
        return '';
    }
    
    private function getJsonWebToken(){
        $jsonConverter = new StandardConverter();
        $algorithmManager = AlgorithmManager::create([
            new PS256()
        ]);
        
        $jwsBuilder = new JWSBuilder($jsonConverter, $algorithmManager);
        
        $now = time();
        
        $claims = [
            'aud' => 'https://iam.api.cloud.yandex.net/iam/v1/tokens',
            'iss' => $this->service_account_id,
            'iat' => $now,
            'exp' => $now + 360
        ];
        
        $header = [
            'alg' => 'PS256',
            'typ' => 'JWT',
            'kid' => $this->key_id
        ];
        
        $key = self::createFromKeyFile();
        
        $payload = $jsonConverter->encode($claims);
        
        // Формирование подписи.
        $jws = $jwsBuilder
            ->create()
            ->withPayload($payload)
            ->addSignature($key, $header)
            ->build();
        
        $serializer = new CompactSerializer($jsonConverter);
        
        // Формирование JWT.
        $token = $serializer->serialize($jws);
        
        return $token;
    }
    
    public function getIamToken(){
        $token = self::getJsonWebToken();
        $url = "https://iam.api.cloud.yandex.net/iam/v1/tokens";
        $post = array('jwt' => $token);
        $res = self::query($url,json_encode($post));
        $result = json_decode($res,true);
        return !empty($result['iamToken']) ? $result['iamToken'] : false;
    }
    
    public static function query($url, $fields = array(), $auth = false, $headers = false){
        $arUri = explode(' ',$url);
        if(count($arUri) > 1){
            $method = strtoupper(trim($arUri[0]));
            switch($method){
                case "POST": $method = 'POST'; break;
                case "PATCH": $method = 'PATCH'; break;
                case "DELETE": $method = 'DELETE'; break;
                default: $method = 'GET';
            }
            $url = trim($arUri[1]);
        }
        
		$curl = curl_init(trim($url));
		curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_VERBOSE, 1);
		curl_setopt($curl, CURLOPT_HEADER, 1);
        
        
        $arHeaders = array(
            "Content-Type: application/json; charset=utf8"
        );
        if($headers){
            if(is_array($headers)){
                foreach($headers as $head)
                    $arHeaders[] = $head;
            }else{
                $arHeaders[] = $headers;
            }
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $arHeaders);
        
		if($auth){
			curl_setopt($curl, CURLOPT_USERPWD, "$auth");
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		}

		if($fields){
			//$fields_string = http_build_query($fields);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
		}elseif($method){
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		}
        
		$response = curl_exec($curl);
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$header_string = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
/*
		$header_rows = explode(PHP_EOL, $header_string);
		$header_rows = array_filter($header_rows, trim);
		foreach((array)$header_rows as $hr){
			$colonpos = strpos($hr, ':');
			$key = $colonpos !== false ? substr($hr, 0, $colonpos) : (int)$i++;
			$headers[$key] = $colonpos !== false ? trim(substr($hr, $colonpos+1)) : $hr;
		}
		foreach((array)$headers as $key => $val){
			$vals = explode(';', $val);
			if(count($vals) >= 2){
				unset($headers[$key]);
				foreach($vals as $vk => $vv){
					$equalpos = strpos($vv, '=');
					$vkey = $equalpos !== false ? trim(substr($vv, 0, $equalpos)) : (int)$j++;
					$headers[$key][$vkey] = $equalpos !== false ? trim(substr($vv, $equalpos+1)) : $vv;
				}
			}
		} 
*/
		curl_close($curl);
		return $body;
	}
}