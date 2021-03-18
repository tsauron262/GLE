<?php

class BimpTest extends BimpObject{
    function actionTestSpeed($data, &$success, $errors = array(), $warnings = array()){
        $success = 'Test Terminé';
        
        
        $timeDeb = $this->microtime_float();
        
        $timeGoogle = 0;
        $commande = '/usr/local/bin/curl -o /dev/null -s -w %{time_total}\  http://google.fr';
        $timeGoogle += exec($commande);
        $timeGoogle += exec($commande);
        $timeGoogle += exec($commande);
        
        
        $timePhpDeb = $this->microtime_float();
        for($i=0;$i< 10000000;$i++){
            for($j=0;$j< 3;$j++){
                //echo 'o';
            }
        }
        $timePhp = $this->microtime_float() - $timePhpDeb;
        
        
        
        global $db;
        $timeMySqlDeb = $this->microtime_float();
        $db->query("SELECT * FROM llx_facture LIMIT 0,100000");
        $timeMySql = $this->microtime_float() - $timeMySqlDeb;
        
        
        
        $html .= $this->getMsgTime('google', $timeGoogle,1);
        $html .= $this->getMsgTime('PHP', $timePhp);
        $html .= $this->getMsgTime('MySql', $timeMySql);
        $time = $this->microtime_float() - $timeDeb;
        $html .= $this->getMsgTime('Total', $time, 3);
        
        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => ''
                . "timeFin = new Date().getTime();"
                . 'displayResult('.$timeGoogle.','.$timePhp.','.$timeMySql.',(timeFin - timeDeb)/1000);'
        );
    }
    function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
    public function getMsgTime($nom, $time, $max = 1.5){
        $html = '<br/><br/>';
        
        $class = '';
        if($time > $max)
            $class .= ' error';
        $html .= "<span class='".$class."'> Time ".$nom." : ".$time."</span>";
        return $html;
    }
}

