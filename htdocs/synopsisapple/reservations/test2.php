
<?php
require_once('../../main.inc.php');
llxHeader();

//Location of QRS client certificate

$QRSCertfile = DOL_DOCUMENT_ROOT."/synopsisapple/certif/clermont/";

//Set up the required headers

$headers = array(

'Accept: application/json',

'Content-Type: application/json',

'X-PARTNER-SOLDTO : 0000128630'

);

//Create Connection using Curl


$ch = curl_init("https://asp-partner.apple.com/api/v1/partner/reservation/0000128630/B2160900012430");

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


curl_setopt($ch, CURLOPT_SSLCERT, $QRSCertfile."certifFinalProdClermont.pem");
curl_setopt($ch, CURLOPT_SSLCERTPASSWD, "passPhraseProd");


curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);


$data = curl_exec($ch);

echo curl_error($ch);
echo "<pre>";
print_r(json_decode($data));
