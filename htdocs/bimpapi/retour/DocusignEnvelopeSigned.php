<?php

define('NOREQUIREHTML', 1);
define('NOCSRFCHECK', 1);
define('NO_REDIRECT_LOGIN', 1);
require_once("../../main.inc.php");

$errors = array();

$body = file_get_contents('php://input');
file_put_contents(DOL_DATA_ROOT . '/docusign_webhook.txt', $body);

$in = json_decode(file_get_contents(DOL_DATA_ROOT . '/docusign_webhook.txt'));
//$in = json_decode(file_get_contents('/var/www/html/bimp-erp/documents/docusign_webhook.txt'), 1);

//print_r($in);
//die();
if(is_array($in)) {
    
    if(isset($in['data'])) {
        
        if(isset($in['data']['envelopeId'])) {
            
            $signatures = BimpCache::getBimpObjectObjects('bimpcore', 'BimpSignature', array('id_envelope_docu_sign' => $in['data']['envelopeId']));

            $signature  = array_shift($signatures);
            
            if(BimpObject::objectLoaded($signature)) {
                $data = array(
                    'id_account'  => $in['data']['accountId'],
                    'id_envelope' => $in['data']['envelopeId']
                );
                $success = '';
                $return = $signature->actionSignDocuSign($data, $success);
                $errors = BimpTools::merge_array($errors, $return['errors']);
            } else {
                $errors[] = "Il n'existe pas d'enveloppe avec pour id DocuSign " . $in['data']['envelopeId'];
            }
            
        } else {
            $errors[] = "La requête reçu ne contient pas le champs \"envelopeId\"";
        }

    } else {
        $errors[] = "La requête reçu ne contient pas le champs \"data\"";
    }
    
} else {
    $errors[] = "La requête reçu n'est pas de type array";
}

if(count($errors)) {
    $output = "Erreurs lors du webhook DocusignEnvelopeSigned :<br/>";
    foreach($errors as $e) {
        $output .= '- ' . $e . '<br/>';
    }
    $output .= "<br/><br/>Body en entrée :<br/>";
    $output .= print_r($in, 1);
}

echo $output;

//mailSyn2('Erreurs Webhook DocusignEnvelopeSigned', BimpCore::getConf('devs_email'), '', $output);


//echo $a->apiVersion;

//var_dump(a{'a' => 'b'});


//var_dump($a);


//foreach($a as $k => $v) {
//    if(is_string($v))
//        echo "k = $k  v = $v\n";
//    else
//        print_r($v);
//}


//curl -X POST -H 'Content-Type: application/json' -d '{  "event": "envelope-completed",  "apiVersion": "v2.1",  "uri": "/restapi/v2.1/accounts/8b411bfe-54f5-47fc-bbf2-55d9a71a200f/envelopes/f826722d-0cc8-4ad0-a4ef-bdb128502a61",  "retryCount": 0,  "configurationId": 10343415,  "generatedDateTime": "2022-09-21T13:58:43.9115721Z",  "data": {    "accountId": "8b411bfe-54f5-47fc-bbf2-55d9a71a200f",    "userId": "4214323f-c281-4a0e-80f7-37b3ea7d8665",    "envelopeId": "f826722d-0cc8-4ad0-a4ef-bdb128502a61"  }}' http://localhost/bimp-erp/htdocs/bimpapi/retour/DocusignEnvelopeSigned.php?test=GET_OK&var=OK | printf "\n\n\n"





