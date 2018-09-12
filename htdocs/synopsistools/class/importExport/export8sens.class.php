<?php


class export8sens {     
    var $sep = "\t";
    var $saut = "\n";
    
    function __construct($db) {
        $this->db = $db;
        $this->path = (defined('DIR_SYNCH') ? DIR_SYNCH : DOL_DATA_ROOT . "/synopsischrono/export/" ) . "/import/";
    }
    
    function traiteStr($str){
        $str = html_entity_decode($str);
        $str = str_replace("&#39;", "'", $str);
        $str = preg_replace("/\r\n|\r|\n/", "", $str);
        $str = str_replace("&#39;", "'", $str);
        $str = strip_tags($str);
        return $str;
    }
    

    function getTxt($tab1, $tab2) {
        $sortie = "";
        if (!isset($tab1[0]) || !isset($tab1[0]))
            return 0;

        $i=0;
        foreach ($tab1[0] as $clef => $inut){
            $i++;
            $sortie .= $clef;
            if($i < count($tab1[0]))
                $sortie .=  $this->sep;
        }
        $sortie .= $this->saut;
        
        $i=0;
        foreach ($tab2[0] as $clef => $inut){
            $i++;
            $sortie .= $clef;
            if($i < count($tab2[0]))
                $sortie .=  $this->sep;
        }
        $sortie .= $this->saut;


        foreach ($tab1 as $tabT) {
            $i = 0;
            foreach ($tabT as $val){
                $i++;
                $sortie .= str_replace(array($this->saut, $this->sep, "\n", "\r"), "  ", $val);
                if($i < count($tabT))
                    $sortie .=  $this->sep;
            }
            $sortie .= $this->saut;
        }
        foreach ($tab2 as $tabT) {
            $i = 0;
            foreach ($tabT as $val){
                $i++;
                $sortie .= str_replace(array($this->saut, $this->sep, "\n", "\r"), "  ", $val);
                if($i < count($tabT))
                    $sortie .=  $this->sep;
            }
            $sortie .= $this->saut;
        }

        return $sortie;
    }

}
