<?php

define("NOLOGIN", 1);  // This means this output page does not require to be logged.
define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.

require_once('../../main.inc.php');
ini_set("display_errors", 1);



require_once(DOL_DOCUMENT_ROOT.'/core/db/mysqli.class.php');

if(isset($_REQUEST['active'])){
        $dbT = new DoliDBMysqli('mysql', $_REQUEST['active'], $db->database_user, $db->database_pass, $db->database_name, $db->database_port);
        if(!$dbT->connected){
            echo 'Attention probléme de connexion '.$dbT->error;
        }
        $req = "SET GLOBAL wsrep_provider_options='pc.bootstrap=YES';";
        $sql = $dbT->query($req);
        echo $req.' sur '.$_REQUEST['active'];
}
else{
    if(!defined('CONSUL_SERVERS') || !defined('CONSUL_SERVICE_DATABASE'))
        die('Consul mal configurée');

    $variables = array('wsrep_ready', 'wsrep_connected', 'wsrep_cluster_status', 'wsrep_local_state_comment', 'wsrep_cluster_state_uuid', 'wsrep_cluster_conf_id', 'wsrep_cluster_size', 'wsrep_last_committed');

    $maxWsrep_last_committed = 0;
    $bestServ = '';
    $result = array();
    $datasServ = array();
    foreach(unserialize(CONSUL_SERVERS) as $serv){
        $consulAdd = $serv.':8500/v1/catalog/service/'.CONSUL_SERVICE_DATABASE;
        $datas = json_decode(file_get_contents($consulAdd));
        foreach($datas as $data){
            $dataSql = $data->ServiceTaggedAddresses->lan_ipv4;
            $datasServ[$dataSql->Address] = $dataSql->Port;
        }
    }

    foreach($datasServ as $servSql => $port){
        $dbT = new DoliDBMysqli('mysql', $servSql, $db->database_user, $db->database_pass, $db->database_name, $port);
        if(!$dbT->connected){
            echo 'Attention probléme de connexion serv : '.$servSql.' '.$dbT->error;
        }
        $sql = $dbT->query("SHOW GLOBAL STATUS LIKE 'wsrep_%';");
        while($ln = $db->fetch_object($sql)){
            if(in_array($ln->Variable_name, $variables))
                $result[$servSql][$ln->Variable_name] = $ln->Value;
            if($ln->Variable_name == 'wsrep_last_committed' && $ln->Value > $maxWsrep_last_committed){
                $maxWsrep_last_committed = $ln->Value;
                $bestServ = $servSql;
            }
        }
    }
        
    foreach($result as $servSql => $dataSql){
        echo '<div style="float:left; margin: 10px"><h2>Mysql : '.$servSql.'</h2>';
        foreach($dataSql as $var => $val){
            $style = '';
            if($var == 'wsrep_cluster_status'){
                if($val == 'Primary'){
                    $style = 'color:green';
                }
                else{
                    $style = 'color:red';
                }
            }
            echo '<div style="'.$style.'">'.$var.' => '.$val.'</div><br/>';
        }

        $style = 'color: red;';
        if($servSql == $bestServ)
            $style = 'color: green;';
        echo '<button style="'.$style.'" onclick="location.href = \'?active='.$servSql.'\'">Activer</button>';
        echo '</div>';
    }
    echo '</div>';
}

