<?php

require_once '../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once __DIR__ . '/classes/nusoap/lib/nusoap.php';
require_once __DIR__ . '/BDS_Lib.php';

ini_set('display_errors', 0);
BDS_Process::$debug_mod = false;

global $conf, $soap_server, $debug_mod;
$errors = array();

if (empty($conf->global->MAIN_MODULE_WEBSERVICES)) {
    $errors[] = 'Module webservice non actif sur Dolibarr';
}

$debug_mod = false;

function display_debug($msg)
{
    global $debug_mod;
    if (!$debug_mod) {
        return;
    }

    if (is_array($msg)) {
        foreach ($msg as $m) {
            echo $m . '<br/>';
        }
    } else {
        echo $msg . '<br/>';
    }
}
$soap_server = new nusoap_server();
//$soap_server->soap_defencoding = 'UTF-8';
//$soap_server->decode_utf8 = false;

$soap_server->register('set', array(
    'authentication' => 'tns:authentication',
    'params'         => 'tns:params'
        ), array(
    'success'        => 'tns:success',
    'errors'         => 'tns:errors',
    'return'         => 'tns:return',
    'ext_id_process' => 'tns:ext_id_process'
        )
);

//$soap_server->register('get', array(
//    'authentication' => 'tns:authentication',
//    'params'         => 'tns:params'
//        ), array(
//    'success' => 'tns:success',
//    'errors'  => 'tns:errors',
//    'return'  => 'tns:return'
//));

if (count($errors)) {
    display_debug($errors);
}

function authenticate($authentication, &$errors, &$fuser)
{
    if (!is_array($authentication) || !count($authentication)) {
        $errors[] = 'Données d\'authentification absentes';
    } else {
        $fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);

        if ($error > 0) {
            $errors[] = 'Echec de l\authentification. (code: ' . $errorcode . ', message: ' . $errorlabel . ')';
        } else {
            $fuser->getrights();
            global $user;
            $user = $fuser;
        }
    }
    return count($errors) ? 0 : 1;
}

function set($authentication, $params)
{
    $errors = array();
    $fuser = null;

    global $debug_mod;

    if (isset($params['debug_mod'])) {
        $debug_mod = $params['debug_mod'];
    }

    if ($debug_mod) {
        $debug_mod = true;
        ini_set('display_errors', 1);
        BDS_Process::$debug_mod = true;
    } else {
        $debug_mod = false;
        ini_set('display_errors', 0);
        BDS_Process::$debug_mod = false;
    }
    display_debug('<h1>SOAP Request: "set"</h1>');

    authenticate($authentication, $errors, $fuser);

    if (!is_array($params) || !count($params)) {
        $errors[] = 'Paramètres absents';
    }

    $process = null;
    if (!count($errors) && !is_null($fuser)) {
        if (!isset($params['process_name'])) {
            $errors[] = 'Nom du processus non spécifié dans les paramètres de la requete';
        } else {
            $error = 0;
            $process = BDS_Process::createProcessByName($fuser, $params['process_name'], $error);
            if ($error) {
                $errors[] = $error;
            }
        }
    }

    $result = array();
    if (!count($errors) && !is_null($process)) {
        $result = $process->executeSoapRequest($params, $errors);
    } else {
        $msg = 'Requête d\'import non exécutée' . "\n";
        $msg .= 'Processus: ' . ((!is_null($process)) ? $process->processDefinition->title : (isset($params['id_process']) ? 'ID ' . $params['id_process'] : 'non spécifié')) . "\n";
        $msg .= 'Opération: ' . ((!is_null($params['operation'])) ? $params['operation'] : 'non spécifiée') . "\n\n";
        $nErrors = count($errors);
        $msg .= $nErrors . ' erreur' . (($nErrors > 1) ? 's' : '') . ' : ' . "\n";
        foreach ($errors as $e) {
            $msg .= "\t" . '- ' . $e . "\n";
        }

        dol_syslog($msg, 3);
        display_debug($msg);
    }

    $response = array(
        'success'        => (count($errors) ? 0 : 1),
        'errors'         => $errors,
        'return'         => $result,
        'ext_id_process' => (!is_null($process) ? $process->processDefinition->id : 0)
    );
    return $response;
}
$soap_server->service(file_get_contents("php://input"));
