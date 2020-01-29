<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of importFacture
 *
 * @author tommy
 */

require_once DOL_DOCUMENT_ROOT."/synopsistools/class/importExport/import8sens.class.php";

class importFacture extends import8sens {
    

    public function importFact() {
        global $db;
        $dir = $this->pathI;
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if(stripos($file, ".txt") !== false){
                        $tabF = explode("\r", file_get_contents($dir.$file));
                        foreach($tabF as $idLn => $ln){
                            $tabLn = explode("\t", $ln);
                            if($idLn > 1 && isset($tabLn[2])){
                                $req = "UPDATE llx_facture set codeCli8Sens = '".$tabLn[1]."', Collab8sens = '".$tabLn[2]."', extraparams = 2 WHERE extraparams = 1 AND ref = '".$tabLn[0]."'";
                                echo $req."<br/>";
                                $db->query($req);
                            }
                            
                        }
                        rename($dir.$file,$dir."imported/".$file);
                    }
                }
                closedir($dh);
            }
        }
    }
}
