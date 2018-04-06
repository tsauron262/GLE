<a title="Logiciel billetterie en ligne" href="https://www.weezevent.com/?c=sys_widget" class="weezevent-widget-integration"target="_blank"data-src="https://www.weezevent.com/widget_billeterie.php?id_evenement=317787&lg_billetterie=1&code=76834&resize=1&width_auto=1&color_primary=00AEEF"       data-width="650"   data-height="600"   data-id="317787"   data-resize="1"   data-width_auto="1"   data-noscroll="0"   data-nopb="0">BilletterieWeezevent</a><script type="text/javascript" src="https://www.weezevent.com/js/widget/min/widget.min.js"></script>

<?php
die("fin");
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
