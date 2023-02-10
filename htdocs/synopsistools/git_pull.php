<?php

if(isset($_REQUEST['nolog']) && $_REQUEST['nolog'] == 'ujgjhkhkfghgkvgkfdkshfiohf5453FF454FFDzelef'){
    define("NOLOGIN", 1);
    header('x-frame-options: ALLOWALL',true);
}

require_once('../main.inc.php');

if(!isset($_REQUEST['no_menu']))
    llxHeader();


if(defined('ID_ERP'))
    echo '<h1>Serveur : '.ID_ERP.'</h1>';

curl_init();

$ok = (isset($_REQUEST['go']) && $_REQUEST['go']);

echo '<form><input type="submit" value="Go"/><input type="hidden" name="go" value="1"/></form>';

if($ok){
    $tabHook = array();
    $tabHook[] = array(
        'url' => WEBHOOK_SERVER.WEBHOOK_PATH_GIT_PULL,
        'data'=> array(
            'secret' => WEBHOOK_SECRET_GIT_PULL
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