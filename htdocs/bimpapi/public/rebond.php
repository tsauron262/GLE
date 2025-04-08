<?php

//echo 'coucouc';


//$arrayHeader = array(
//	'Content-Length' =>
//
//
//);

//echo '<pre>';print_r(getRequestHeaders());

$ch = curl_init('http://172.24.2.31/OLAP/msmdpump.dll');
curl_setopt($ch, CURLOPT_HTTPHEADER, getRequestHeaders());
curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
$response = curl_exec($ch);
curl_close($ch);
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

