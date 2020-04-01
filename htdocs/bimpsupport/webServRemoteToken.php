<?php
define("NOLOGIN", 1); 

require_once '../bimpcore/main.php';


require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
$errors = $result = array();

$data = json_decode(file_get_contents("php://input"));

dol_syslog(file_get_contents("php://input"),3);
dol_syslog(print_r($data,1),3);

if(is_object($data)){
    $token = $data->id;//"770935";
    $cmd = $data->cmd;//"start";
}

switch ($cmd){
    case 'login':
            $remoteToken = getToken($token, $errors, ' AND `date_valid` > now()');

            if($remoteToken){
                $userD = $remoteToken->getChildObject('user_demand');
                $result['status'] = 'OK';
                $result['port'] = $remoteToken->getData('port');
                $result['name'] = $userD->getName();
                $result['code'] = $remoteToken->getData('mdp');
                $result['server'] = 'stun.bimp.fr';
                $result['srvcode'] = 'Viaphieshaiso2cee7Aec3Ar6fohngeb';
                $result['srvport'] = '443';
                $result['code'] = $remoteToken->getData('mdp');
                $remoteToken->addNote('Login');
            }
        break;
    case 'start':
        $remoteToken = getToken($token, $errors, ' AND date_create >= DATE_SUB(now(),INTERVAL 12 HOUR)');

        if($remoteToken){
            $result['status'] = 'OK';
//            $remoteToken->updateField('date_start', dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S'));
             $remoteToken->addNote('Start');
        }
        break;
    case 'stop':
        $remoteToken = getToken($token, $errors, ' AND date_create >= DATE_SUB(now(),INTERVAL 1 DAY)');

        if($remoteToken){
            $result['status'] = 'OK';
            $remoteToken->addNote('Stop');
        }
        break;
    default:
        $errors[] = 'Action inconnue : '.$cmd;
        break;
        
}

//echo "<pre>";print_r($result);

if(!count($errors))
    echo json_encode($result);
else
    echo json_encode (array('status'=>'FAIL', 'infos'=>implode(" ", $errors)));



function getToken($token, &$errors, $and){
    global $db;
    $sql = $db->query('SELECT * FROM `'.MAIN_DB_PREFIX.'bs_remote_token` WHERE `token` = \''.$token.'\' '.$and);
    if($db->num_rows($sql) > 1){
        $errors[] = "Plusieurs résultats, pas normal";
    }elseif($db->num_rows($sql) == 1){
        $ln = $db->fetch_object($sql);
        return BimpCache::getBimpObjectInstance("bimpsupport", "BS_Remote_Token", $ln->id);
    }
    else{
        $errors[] = "Aucun résultat";
    }
    return null;
}