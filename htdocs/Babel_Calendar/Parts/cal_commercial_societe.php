<?php
/*
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */



/* show Societe Filter */

$showSociete = $_REQUEST['showSociete']=='on'?true:false;
$showSocieteCreate=$_REQUEST['showSocieteCreate']?true:false;



array_push($arrFilter,
        array(  "name" => "Societe" ,
                "data" => array( 0 => array( "checked" => $showSociete, "trans"=>"tous/aucun"  ) ,
                                 1 => array( "checked" => $showSocieteCreate, "trans" => "creation",     "idx" => "showSocieteCreate"),
                               )
             )
);


  //Societe
    //Date création
    //Date Societe
    //Date lim reglement
    //Date de payment
    //statut
    //ref
if ($showSociete){

    $requete = "SELECT rowid,
                       nom,
                       datec
                  FROM ".MAIN_DB_PREFIX."Societe
                 WHERE fk_user_creat = ".$usrid;

        $resql = $db->query($requete);
        $id=0;
        if ($resql)
        {
            while($res=$db->fetch_object($resql))
            {
                $url = $dolibarr_main_url_root."/comm/prospect/fiche.php?socid=".$res->rowid;
                if ($res->client=1)
                {
                    $url = $dolibarr_main_url_root."/comm/fiche.php?socid=".$res->rowid;
                }
                if ($res->datec && $showSocieteCreate)
                {
                    require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
                    $soc = new Societe($db);
                    $soc->fetch($res->rowid);
                   $arrRes = $BCalc->pushDateArr(
                                          $res->datec,
                                          "Cr&eacute;at. de " . "".$soc->nom."",
                                          "Cr&eacute;ation de la soci&eacute;t&eacute;" . $soc->nom . "<BR><P>" . $soc->note,
                                          $res->rowid,
                                          $id,
                                          "Societe",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
                }
            }

        }
}


?>