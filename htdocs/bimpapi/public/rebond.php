<?php

//echo 'coucouc';

//echo '<pre>';
//print_r(getRequestHeaders());
//print_r($_SERVER);die;

//$arrayHeader = array(
//	'Content-Length' =>
//
//
//);

//echo '<pre>';print_r(getRequestHeaders());

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'http://172.24.2.31/OLAP/msmdpump.dll');
curl_setopt($ch, CURLOPT_HTTPHEADER, getRequestHeaders());
curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
curl_setopt($ch, CURLOPT_POST, 1);
$response = curl_exec($ch);
curl_close($ch);


echo '<h1>header</h1>';
print_r(getRequestHeaders());


echo '<h1>body</h1>';
echo file_get_contents('php://input');


echo '<h1>response header</h1>';
$response_infos = curl_getinfo($ch);
echo '<pre>';
print_r($response_infos);
echo '</pre>';





echo $response;


//function getRequestHeaders() {
//	$headers = array();
//	foreach($_SERVER as $key => $value) {
//		if (substr($key, 0, 5) <> 'HTTP_') {
//			continue;
//		}
//		$header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
//		$headers[$header] = $value;
//	}
//	return $headers;
//}


function getRequestHeaders() {
	$headers = array();
	$tabGarde = array(
		'CONTENT_LENGTH' => 'Content-Length',
		'CONTENT_TYPE' => 'Content-Type',
		'HTTP_AUTHORIZATION' => 'Authorization',
	);
	foreach($tabGarde as $key => $value) {
		if (isset($_SERVER[$key])) {
			$headers[$value] = $_SERVER[$key];
		}
	}
	$headers['Host'] = 'bimp.fr';
	return $headers;
}
?>

