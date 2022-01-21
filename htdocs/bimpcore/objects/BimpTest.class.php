<?php

class BimpTest extends BimpObject{
    function actionTestSpeed($data, &$success, $errors = array(), $warnings = array()){
        $success = 'Test Terminé';
        
        $tabTxt = array();
        
        $timeDeb = $this->microtime_float();
        
        $commande = '/usr/local/bin/curl -o /dev/null -s -w %{time_total}\  http://google.fr';
        exec($commande);
        exec($commande);
        exec($commande);
        $timeFinGoogle = $this->microtime_float();
        $timeGoogle = $timeFinGoogle - $timeDeb;
        
        if(defined('ID_ERP'))
            $tabTxt[] = 'Acuelle : '.ID_ERP;
        $str = '';
        for($i=0;$i< 10000000;$i++){
            for($j=0;$j< 3;$j++){
                $str .= 'pppppjjjjjjj';
            }
        }
        $timeFinPhp = $this->microtime_float();
        $timePhp = $timeFinPhp - $timeFinGoogle;
        
        file_put_contents(PATH_TMP.'/testFile.txt', $str);//=<> 360Mo
        $inut = file_get_contents(PATH_TMP.'/testFile.txt');
        $timeFinFile = $this->microtime_float();
        $timeFile = $timeFinFile - $timeFinPhp;
        
        global $db;
        $tabTxt[] = $db->db->host_info;
        $db->query("SELECT * FROM llx_facture LIMIT 0,100000");
        $sql = $db->query("SELECT * FROM `llx_bimpcore_conf` WHERE `name` LIKE 'nb_req_%';");
        $tot = 0;
        $tabServ = array();
        while($ln = $db->fetch_object($sql)){
                $tot += $ln->value;
                $tabServ[str_replace('nb_req_', '', $ln->name)] = $ln->value;
        }
        foreach($tabServ as $idS => $valS)
                $tabTxt[] = 'Serveur : '.$idS.' : '.$valS.' requetes ('.round($valS / $tot * 100).' %)';
        $timeMySql = $this->microtime_float() - $timeFinFile;
        
        
        
//        $time = $this->microtime_float() - $timeDeb;
//        $html .= $this->getMsgTime('google', $timeGoogle,1);
//        $html .= $this->getMsgTime('file', $timeFile,1);
//        $html .= $this->getMsgTime('PHP', $timePhp);
//        $html .= $this->getMsgTime('MySql', $timeMySql);
//        $html .= $this->getMsgTime('Total', $time, 3);
        
        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => ''
                . "timeFin = new Date().getTime();"
                . 'displayResult('.$timeGoogle.','.$timePhp.','.$timeMySql.','.$timeFile.',(timeFin - timeDeb)/1000, "<br/>'.implode('<br/>', $tabTxt).'");'
        );
    }
    function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
//    public function getMsgTime($nom, $time, $max = 1.5){
//        $html = '<br/><br/>';
//        
//        $class = '';
//        if($time > $max)
//            $class .= ' error';
//        $html .= "<span class='".$class."'> Time ".$nom." : ".$time."</span>";
//        return $html;
//    }
}

