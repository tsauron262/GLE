<?php

define("NOLOGIN", 1);  // This means this output page does not require to be logged.
define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.

require_once('../../main.inc.php');
ini_set("display_errors", 1);


if(!defined('CONSUL_SERVERS') || !defined('CONSUL_SERVICE_DATABASE'))
    die('Consul mal configurée');

$variables = array('wsrep_ready', 'wsrep_connected', 'wsrep_cluster_status', 'wsrep_local_state_comment', 'wsrep_cluster_state_uuid', 'wsrep_cluster_conf_id', 'wsrep_cluster_size', 'wsrep_last_committed');

foreach(unserialize(CONSUL_SERVERS) as $serv){
    echo '<h1>Consul : '.$serv.'</h1>';
    $consulAdd = $serv.':8500/v1/catalog/service/'.CONSUL_SERVICE_DATABASE;
    $datas = json_decode(file_get_contents($consulAdd));
    foreach($datas as $data){
        $dataSql = $data->ServiceTaggedAddresses->lan_ipv4;
        echo '<h2>Mysql : '.$dataSql->Address.'</h2>';
        require_once(DOL_DOCUMENT_ROOT.'/core/db/mysqli.class.php');
        $dbT = new DoliDBMysqli('mysql', $dataSql->Address, $db->database_user, $db->database_pass, $db->database_name, $dataSql->Port);
        if(!$dbT->connected){
            echo 'Attention probléme de connexion '.$dbT->error;
        }
        $sql = $dbT->query("SHOW GLOBAL STATUS LIKE 'wsrep_%';");
        while($ln = $db->fetch_object($sql)){
            if(in_array($ln->Variable_name, $variables))
                echo $ln->Variable_name.' => '.$ln->Value.'<br/>';
        }
    }
}



echo '<br/><br/>Fin';