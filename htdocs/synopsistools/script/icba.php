<?php

//if (! defined('NOLOGIN'))        define('NOLOGIN','1');
require("../../main.inc.php");

require_once DOL_DOCUMENT_ROOT . "/bimpcore/Bimp_Lib.php";

llxHeader();

set_time_limit(5000000);
ini_set('memory_limit', '1024M');

$dir = DOL_DOCUMENT_ROOT.'/synopsistools/ICBA/';


$files1 = scandir($dir);
foreach($files1 as $fichier)
{
    if($fichier != '.' && $fichier != '..')
    {
        $dataName = explode(' - ', $fichier);
        if(count($dataName) >= 2){
            $siren = str_replace(' ', '', $dataName[0]);
            $sql = $db->query("SELECT * FROM llx_societe WHERE siren = '".$siren."'");
            if($db->num_rows($sql) == 0)
                echo 'erreur siren introuvable '.$siren;
            else{
                while($ln = $db->fetch_object($sql)){
                    $db->query("UPDATE llx_societe SET date_depot_icba = '2021-01-01' WHERE rowid = ".$ln->rowid);
    //                die(DOL_DATA_ROOT.'/societe/'.$ln->rowid.'/'.$fichier);
                    $new_dir = DOL_DATA_ROOT.'/societe/'.$ln->rowid.'/';
                    if(!is_dir($new_dir))
                        mkdir($new_dir);
                    
                    if(!is_file($new_dir.'/atradius.pdf'))
                        if(!copy($dir.$fichier, $new_dir.'atradius.pdf'))
                                die('Impossible '.$new_dir.'atradius.pdf');
                   
                    
                    $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $ln->rowid);
                    $soc->getChildrenObjects('files');
                }
            }
            
//            echo $siren.'<br/>';
        }
        else{
            echo 'errrerur name '.$fichier."<br/>";
        }
//        $siren = 
        
//        die('fin anticipé'.$siren);
    }
}

llxFooter();