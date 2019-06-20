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
    public $sepCollone = "	";
    public $maxLn = 0;
    public $minLn = 0;
    public $nbLigne = 0;//Soit nbLigne soit maxLn
    private $memoireLn = null;
    public $mode = 1;
    
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
                                $this->error("Impossible de déplacé le fichier ".$this->path . $file." vers ".$this->path ."imported/". $file);
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

    /*
     * mode 1 normal 2 une ligne titre une ligne entete
     */
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
            $tabTitre = explode($this->sepCollone, $tabLigne[1]);
        else
            $tabTitre = explode($this->sepCollone, $tabLigne[0]);
        $tabFinal = $tabTitre2 = array();
        foreach ($tabLigne as $idLn => $ligne) {
            $tabTmp = explode($this->sepCollone, $ligne);
            $tabLn = array();
            if($this->mode == 1){
                if (($idLn != 1)) {
                    foreach ($tabTmp as $idTmp => $chTmp) {
                        $tabLn[$tabTitre[$idTmp]] = $chTmp;
                    }
                    if ($idLn != 0)
                        $tabFinal[] = $tabLn;
                    else
                        $tabTitre2 = $tabLn;
                }
            }
            elseif($this->mode == 2){
                if($idLn == 3){
                    $tabTitre2 = $tabTmp;
                }
                elseif($idLn > 3){
                    if($tabTmp[0] == "E"){//new ligne
                        foreach ($tabTmp as $idTmp => $chTmp) {
                            $tabLn[$tabTitre[$idTmp]] = $chTmp;
                        }
                        $tabFinal[] = $tabLn;
                        $this->memoireLn = count($tabFinal)-1;
                        $tabFinal[ $this->memoireLn]['lignes'] = array();
                    }
                    elseif($tabTmp[0] == "L"){//old ligne
                        foreach ($tabTmp as $idTmp => $chTmp) {
                            $tabLn[$tabTitre2[$idTmp]] = $chTmp;
                        }
                        $tabFinal[$this->memoireLn]['lignes'][] = $tabLn;
                    }
                }
            }
        }
        echo "<pre>";
        echo "Tab Titre: ".print_r($tabTitre,1);
        echo "</pre>";
//        print_r($tabFinal[5363]);
//        print_r(count($tabFinal));

        $i = $j = 0;
        $trunquer = false;
        foreach ($tabFinal as $ln){
            $i++;
            if(count($ln) > 1 && ($i <= $this->maxLn || $this->maxLn < 1) && $i >= $this->minLn && ($i < $this->nbLigne+$this->minLn || $this->nbLigne < 1)){
                $j++;
                $this->traiteLn($ln);
            }
            else
                $trunquer = true;
        }
        echo "<br/><br/>".$j." lignes traitées sur ".$i;
        
        if($trunquer)
            echo "<br/><br/>ATTENTION Toutes les lignes 'on pas été traitées";
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

