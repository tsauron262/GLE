<?php



abstract class import8sens {
    var $tabResult = array("connue" => 0, "inc" => 0, "double" => 0, "modifier" => 0, "creer" => 0);
    var $path = "";
    var $errors = array();
    var $alerts = array();
    var $object;
    var $update = false;
    var $utf8 = true;
    var $debug = false;
    var $moveFile = true;
    
    function __construct($db) {
        $this->db = $db;
        $this->path = (defined('DIR_SYNCH') ? DIR_SYNCH : DOL_DATA_ROOT . "/synopsischrono/export/" ) . "/export/";
        set_time_limit(5000000);
        ini_set('memory_limit', '10240M');
    }
    
    
    function go() {
        $this->getFiles();
        echo "<pre>";
        $this->printError();
        print_r($this->tabResult);
        echo "</pre>";
    }
    
    
    function getFiles() {
        $extension = ".txt";
        if (is_dir($this->path)) {
            if ($dh = opendir($this->path)) {
                if($this->debug)
                    echo "<br/>Dossier : ".$this->path;
                $files = array();
                while (($file = readdir($dh)) !== false) {
                    if (stripos($file, $extension)) {
                        $files[] = $file;
                    }
                }
                foreach($files as $file){
                    echo $file;
                    $content = file_get_contents($this->path . $file);
                    $newFile = str_replace($extension, ".ENcGLE", $file);
                    rename($this->path . $file, $this->path . $newFile);
                    $this->traiteFile($content);
                    if(count($this->errors) == 0){
                        if($this->moveFile){
                            if(rename ($this->path . $newFile, $this->path ."imported/". $file))
                                echo "<br/>Fichier traité déplacé vers ".$this->path ."imported/". $file;
                            else
                                $this->error("Impossible de déplacé le fichier ".$this->path . $file);
                        }
                    }
                    rename($this->path . $newFile, $this->path . $file);
                }
            }
        }
        else{
            $this->error("Le rep ".$this->path ." n'est pas correct");
        }
    }
    
    function traiteStr($str){
        $str = str_replace("a􏰀o", "ao", $str);
        return $str;
    }

    function traiteFile($content) {
        if($this->utf8){
            $content = str_replace("\r", "\n", $content);
            $content = str_replace("\n\n", "\n", $content);
            $tabLigne = explode("\n", $content);
        }
        else{
            $content = utf8_encode($content);
            $tabLigne = explode("\r", $content);
        }
        if(isset($tabLigne[1]) && $tabLigne[1] != "")
            $tabTitre = explode("	", $tabLigne[1]);
        else
            $tabTitre = explode("	", $tabLigne[0]);
        $tabFinal = $tabTitre2 = array();
        foreach ($tabLigne as $idLn => $ligne) {
            if (($idLn != 1)) {
                $tabTmp = explode("	", $ligne);
                $tabLn = array();
                foreach ($tabTmp as $idTmp => $chTmp) {
                    $tabLn[$tabTitre[$idTmp]] = $chTmp;
                }
                if ($idLn != 0)
                    $tabFinal[] = $tabLn;
                else
                    $tabTitre2 = $tabLn;
            }
        }
        echo "<pre>";
        echo "Tab Titre: ".print_r($tabTitre,1);
//        print_r($tabFinal[5363]);
//        print_r(count($tabFinal));

        foreach ($tabFinal as $ln)
            if(count($ln) > 1)
                $this->traiteLn($ln);
    }
    

    function printError() {
        foreach($this->errors as $msg)
            echo "<br/><div class='red'>ERROR FATAL : " . $msg . "</div><br/>";
        foreach($this->alerts as $msg)
            echo "<br/>" . $msg . "<br/>";
    }

    function error($msgOrArray) {
        if(is_array($msgOrArray))
            foreach($msgOrArray as $msg)
                $this->errors[] = $msg;
        else
            $this->errors[] = $msgOrArray;
    }

    function alert($msgOrArray) {
        if(is_array($msgOrArray))
            foreach($msgOrArray as $msg)
                $this->alerts[] = $msg;
        else
            $this->alerts[] = $msgOrArray;
    }
    
    function traiteNumber($number){
        $number = str_replace(",", ".", $number);
        return number_format($number, 5, ".", "");
    }
    
    function traiteChamp($cible, $val, $number = false){
        $val = trim($val);
        $type = "normal";
        if(stripos($cible, "options_") !== false){
            $oldVal = $this->object->array_options[$cible];
            $type = "option";
        }
        else
            $oldVal = $this->object->$cible;
        if($number){
            $val = $this->traiteNumber($val);
            $oldVal = $this->traiteNumber($oldVal);
        }
        else{
            //$val = utf8_decode($val);
            $val = $this->traiteStr($val);
        }
        if($oldVal != $val){
            $this->error($this->ident." Champ ".$cible." diferent ancienne val |".$oldVal."| new val |".$val."|");
            if($type == "option"){
                $this->object->array_options[$cible] = $val;
                $this->update = true;
            }
            else{
                $this->object->$cible = $val;
                $this->update = true;
            }
        }
        else{
            //$this->error("Champ deja bon ".$cible . " val : ".$val);
        }
    }
}

