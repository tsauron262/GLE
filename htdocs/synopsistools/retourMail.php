<?php


$json = json_decode(file_get_contents('php://input'), true);

define("NOLOGIN", 1);
require("../main.inc.php");
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

$msgId = str_replace(array('<', '>'), '', $json['message-id']);
//BimpCore::addlog('test ');

//if($json['email'] == 'tommy@bimp.fr')
//    mailSyn2('test', 't.sauron@bimp.fr', null, 'ici '.print_r($json,1));

if($msgId != 0 && $msgId != ''){
    $result = BimpCache::findBimpObjectInstance('bimpcore', 'BimpMailLog', array('msg_id' => $msgId), true);
    if(!$result){
        $result = BimpCache::findBimpObjectInstance('bimpcore', 'BimpMailLog', array('mail_to' => array(
                            'operator' => 'like',
                            'value'    => '%'.$json['email'].'%'
                        ), 'mail_subject' => addslashes($json['subject']), 
                           'msg_id' => ''
            ), true);
    }
    if($result && is_object($result)){
        if($result->getData('msg_id') == '')
            $result->updateField('msg_id', $msgId);
        $result->changeStatut($json['event'], $json['email'], $json['date']);
    }
}



