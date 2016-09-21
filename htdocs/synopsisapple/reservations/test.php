<?php
require_once('../main.inc.php');
llxHeader();

$QRSCertfile = DOL_DOCUMENT_ROOT."/synopsisapple/certif/clermont/";

$headers = array(

'Accept: application/json',

'Content-Type: application/json',

'X-PARTNER-SOLDTO : 0000128630'

);


$ch = curl_init("https://asp-partner.apple.com/api/v1/partner/reservation/search");

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSLCERT, $QRSCertfile."certifFinalProdClermont.pem");
curl_setopt($ch, CURLOPT_SSLCERTPASSWD, "passPhraseProd");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("shipToCode"=> "0000128630",
"fromDate" => "2016-09-05", 
"toDate"=>"2016-09-07",
"productCode" => "MAC",
"currentStatus" => "RESERVED")));

$data = curl_exec($ch);

echo '<pre>';
//print_r($data);

//curl_error($ch);

print_r(json_decode($data));
