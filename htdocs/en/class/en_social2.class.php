<?php
require_once DOL_DOCUMENT_ROOT.'/en/class/en_social.class.php';
require_once DOL_DOCUMENT_ROOT.'/en/class/phpMqtt.class.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
class en_social2{
	public $onlyUp = false;
	function cronExec(){
		$message = array(
			array("c" => "FF33F9", "t" => "A"),
			array("c" => "33D7FF", "t" => "B"),
			array("c" => "33FF5B", "t" => "O"),
			array("c" => "AFFF33", "t" => "N"),
			array("c" => "ECFF33", "t" => "N"),
			array("c" => "FFC133", "t" => "E"),
			array("c" => "FF6E33", "t" => "Z"),
			array("c" => "FF3352", "t" => "-"),
			array("c" => "FF3368", "t" => "V"),
			array("c" => "B233FF", "t" => "O"),
			array("c" => "3C33FF", "t" => "U"),
			array("c" => "33A8FF", "t" => "S"),
//			array("c" => "33A8FF", "t" => " PAS !!!!"),
		);
		$this->createMessage($message, 'MSG', 'text');


//		$this->createMessage('01-05-2021 => 30-04-2025  = 4 ANS', 'MSG', '102');
//// Your OpenWeatherMap API key
//		$apiKey = 'b5f44b0578e132472aa516e50ae0d66d';
//		$city = 'Les Estables';
//		$units = 'metric'; // Use 'imperial' for Fahrenheit
//		$apiUrl = "https://api.openweathermap.org/data/2.5/weather?q={$city}&units={$units}&appid={$apiKey}";
//
//// Fetch weather data
//		$response = file_get_contents($apiUrl);
//		if ($response === false) {
//			die('Error occurred while fetching weather data.');
//		}
//
//// Decode JSON response
//		$data = json_decode($response, true);
//
//// Display weather information
//		if (isset($data['main'])) {
//			echo "City: " . $data['name'] . "\n";
//			echo "Temperature: " . $data['main']['temp'] . "°C\n";
//			echo "Weather: " . $data['weather'][0]['description'] . "\n";
//		} else {
//			echo "Error: Unable to fetch weather data.\n";
//		}
//		$this->createMessage($data['main']['temp'] . "°C", 'temp', 'text');
//

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
					array("c" => "00FF00", "t" => substr($result, -$nb)),
				);
				$this->createMessage($message, '', $mode, 'adams', 'Matrix');
				$datas[$mode]['nb'] = $result;
			}
		}
		$this->createMessage($datas[$mode]['nb'], $mode, $mode);
	}


	function createMessage($text, $topic = '', $icon = '', $sound = '', $effect = '', $color = '#FF0000') {
		if($icon == 'fb'){
			$icon = '7303';
			$color = '#3B5998';
		}
		elseif($icon == 'ig'){
			$icon = '58261';
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
