<?php

if(isset($_REQUEST['nolog']) && $_REQUEST['nolog'] == 'ujgjhkhkfghgkvgkfdkshfiohf5453FF454FFDzelef'){
    define("NOLOGIN", 1);
    header('x-frame-options: ALLOWALL',true);
}


if(!isset($_REQUEST['no_menu'])){
    require_once('../main.inc.php');
    llxHeader();
}
else
    require_once('../conf/conf.php');


error_reporting(E_ALL);
ini_set("display_errors", 1);


if(defined('ID_ERP'))
    echo '<h1>Serveur : '.ID_ERP.'</h1>';

curl_init();

$ok = (isset($_REQUEST['go']) && $_REQUEST['go']);
$branche = (isset($_REQUEST['branche'])?$_REQUEST['branche'] : 'master') ;

echo '<form><input type="hidden" name="go" value="1"/><input type="text" name="branche" value="'.$branche.'"/><br/><input type="submit" value="Go"/></form>';

if($ok && $branche != ''){
    $tabHook = array();
    $tabHook[] = array(
        'url' => WEBHOOK_SERVER.WEBHOOK_PATH_GIT_PULL,
        'data'=> array(
            'secret' => WEBHOOK_SECRET_GIT_PULL,
            'branch' => $branche
        )
    );
    //$tabHook[] = array(
    //    'url' => WEBHOOK_SERVER.WEBHOOK_PATH_REDIS_RESTART,//"http://10.192.20.5:9000/hooks/bimp8";
    //    'secret' => WEBHOOK_SECRET_REDIS_RESTART
    //);

    foreach($tabHook as $hook){
        echo '<textarea style="whidth=\'200px\'; height=\'auto\'">';
        $ch = curl_init($hook['url']);
        $file_name = PATH_TMP.'/secret.json';
        $secretJson = json_encode($hook['data']);



        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$secretJson);


        curl_exec($ch);

        if(curl_error($ch)) {
            print_r(curl_error($ch));
        }
        curl_close($ch);

        echo '</textarea>';
        echo '<br/><br/>';
        echo 'Hook : '.$hook['url'].' OK';
        echo '<br/><br/>';
    }
}


if(!isset($_REQUEST['no_menu']))
    llxFooter();