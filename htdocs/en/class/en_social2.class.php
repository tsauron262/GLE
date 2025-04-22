<?php
require_once DOL_DOCUMENT_ROOT.'/en/class/en_social.class.php';
require_once DOL_DOCUMENT_ROOT.'/en/class/phpMqtt.class.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
class en_social2{
	public $onlyUp = false;
	function cronExec(){
		$message = array(
			array("c" => "FF0000", "t" => "A"),
			array("c" => "00FF00", "t" => "B"),
			array("c" => "0000FF", "t" => "O"),
			array("c" => "FFFFFF", "t" => "N"),
			array("c" => "FF0000", "t" => "N"),
			array("c" => "00FF00", "t" => "E"),
			array("c" => "0000FF", "t" => "Z"),
			array("c" => "FFFFFF", "t" => "-"),
			array("c" => "FF0000", "t" => "V"),
			array("c" => "00FF00", "t" => "O"),
			array("c" => "0000FF", "t" => "U"),
			array("c" => "FFFFFF", "t" => "S"),
		);
		$this->createMessage($message, 'MSG', 'text');
		for($i=0; $i < 10; $i++){
			$this->checkSocial();
			sleep(5);
		}
		return 0;
	}

	function getCodeToToken($code){
		$object = new en_social('igc', $code);
		return $object->getCodeToToken();
	}


	function saveToken($type, $token, $expires = ''){
		$fileConf = DOL_DATA_ROOT.'/info.json';
		$object = new en_social($type, $token);
		$longToken = $object->getLongToken();
		if($longToken != '')
			$token = $longToken;
		$datas = json_decode(file_get_contents($fileConf),true);
		if($type == 'fb'){
			$datas['fb']['tokenUser'] = $token;
			$datas['fb']['expires_tokenUser'] = $expires;
		}
		elseif($type == 'ig'){
			$datas['ig']['token'] = $token;
			$datas['ig']['expires'] = $expires;
		}
		file_put_contents($fileConf, json_encode($datas));
	}

	public function savePageId($pageId)
	{
		$mode = 'fb';
		$fileConf = DOL_DATA_ROOT.'/info.json';
		$datas = json_decode(file_get_contents($fileConf),true);
		if($mode == 'fb'){
			$datas['fb']['pageId'] = $pageId;
			$object = new en_social('fb', $datas['fb']['tokenUser']);
			$datas['fb']['pageToken'] = $object->getPageToken($pageId);;
		}
		file_put_contents($fileConf, json_encode($datas));
	}

	function getPages(){
		$fileConf = DOL_DATA_ROOT.'/info.json';
		$datas = json_decode(file_get_contents($fileConf),true);
		$object = new en_social('fb', $datas['fb']['tokenUser']);
		$result = $object->getPages();
		return $result;
		exit;
	}


	function checkSocial(){
		$fileConf = DOL_DATA_ROOT.'/info.json';
		if(is_file($fileConf))
			$datas = json_decode(file_get_contents($fileConf),true);
		else
			$datas = array();
//		$datas = array(
//			'fb'=> array(
//				'token' => 'EAAaI7w3JtZCQBOZCEXwZC0I5tSlurTUZA5ysgmZBsZAh1GQc42MQD3FjZAOsiuP9eogl74fdqwExa6YxZBZBJH3Q1MFLMEpjpZBeZBYSQZBX5PFeLzMd3K80YzxuxdIBwKBfrWMrdojtZCWNnwU5wA82aqXQR2EPk56sNxfvI22bx0oeSkUPa0wF1ERyI0MCt',
//				'pageId' => '183853124811817',
//			),
//			'ig'=> array(
//				'token' => 'IGAA5rYgdSDgZABZAE04Mi1QeXNlMno5bHZAOM2R2TnhMVEtkTGdBOVlydjFzYnFKNDQ0bm1lWGZAUUzRZAR0VaWlcwRHdlV29fS1NoajNoVXZAjeGY2dUVONkplbHQ2UDNlZA1I5UTRVbF9teEFtUGxEZA3hVM215eVpleEVOdjBjaWRXSQZDZD',
//				'pageId' => '9524903440933786',
//			),
//		);

		$maj = false;
		$this->traiteSocial($datas, 'fb', $maj);
		$this->traiteSocial($datas, 'ig', $maj);
		if($maj)
			file_put_contents($fileConf, json_encode($datas));
	}


	function traiteSocial(&$datas, $mode, &$maj){
		$token = '';
		if($mode == 'fb'){
			if(isset($datas[$mode]['pageToken']))
				$token = $datas[$mode]['pageToken'];
			elseif(isset($datas[$mode]['tokenUser']))
				$token = $datas[$mode]['tokenUser'];
		}
		else{
			if(isset($datas[$mode]['token']))
				$token = $datas[$mode]['token'];
		}

		if(!isset($datas[$mode]['nb']))
			$datas[$mode]['nb'] = 'N/C';

		if(strlen($token) > 10) {
			$object = new en_social($mode, $token);
			$pageId = (isset($datas[$mode]['pageId']) ? $datas[$mode]['pageId'] : '');
			$result = $object->getFollowers($pageId);
//	$result = 10;

			if ($datas[$mode]['nb'] != $result && !$this->onlyUp || $datas[$mode]['nb'] < $result) {
				$maj = true;
				$nb = 1;
				while (substr($result, -$nb) == 0) {
					$nb++;
				}
				$message = array(
					array("t" => substr($result, 0, -$nb)),
					array("c" => "FF0000", "t" => substr($result, -$nb)),
				);
				$this->createMessage($message, '', $mode, 'adams', 'Matrix');
				$datas[$mode]['nb'] = $result;
			}
		}
		$this->createMessage($datas[$mode]['nb'], $mode, $mode);
	}


	function createMessage($text, $topic = '', $icon = '', $sound = '', $effect = '', $color = '#FF0000') {
		if($icon == 'fb'){
			$icon = '18426';
			$color = '#3B5998';
		}
		elseif($icon == 'ig'){
			$icon = '3741';
			$color = '#E1306C';
		}
		if($topic != '')
			$topic = 'awtrix_53fa78/custom/' . $topic;
		else
			$topic = 'awtrix_53fa78/notify';
		$message = array();
		if(is_int($text))
			$text = (string) $text;
		$message['text'] = $text;
		if($icon != '')
			$message['icon'] = $icon;
		if($sound != '')
			$message['sound'] = $sound;
		if($effect != '')
			$message['effect'] = $effect;
		if($color != '')
			$message['color'] = $color;

		$this->sendMessage($topic, json_encode($message));
	}

	function sendMessage($topic, $message) {
		error_reporting(E_ALL);
		ini_set('display_errors', 1);

		$server = 'work.freespace.fr';     // change if necessary
		$port = 51883;                     // change if necessary
		$username = 'jeedomMqtt';                   // set your username
		$password = 'Freeparty@43';                   // set your password
		$client_id = 'phpMQTT-publisher'; // make sure this is unique for connecting to sever - you could use uniqid()

		$mqtt = new Bluerhinos\phpMQTT($server, $port, $client_id);

		if ($mqtt->connect(true, NULL, $username, $password)) {
			$mqtt->publish($topic, $message, false);
			$mqtt->close();
		} else {
			echo "Time out!\n";
		}
	}

}
