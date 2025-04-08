<?php

//echo 'coucouc';

//print_r(file_get_contents('php://input'));

//$arrayHeader = array(
//	'Content-Length' =>
//
//
//);

//echo '<pre>';print_r(getRequestHeaders());

$ch = curl_init('http://172.24.2.31/OLAP/msmdpump.dll');
curl_setopt($ch, CURLOPT_HTTPHEADER, getRequestHeaders());
curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
$response = curl_exec($ch);
curl_close($ch);


echo '<h1>header</h1>';
print_r(getRequestHeaders());


echo '<h1>body</h1>';
curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));


echo '<h1>response header</h1>';
$response_infos = curl_getinfo($ch);
echo '<pre>';
print_r($response_infos);
echo '</pre>';





echo $response;


function getRequestHeaders() {
	$headers = array();
	foreach($_SERVER as $key => $value) {
		if (substr($key, 0, 5) <> 'HTTP_') {
			continue;
		}
		$header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
		$headers[$header] = $value;
	}
	return $headers;
}
?>

