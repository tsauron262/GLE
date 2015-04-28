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



/* show SocieteAss Filter */

$showSocieteAss = $_REQUEST['showSocieteAss']=='on'?true:false;
$showSocieteAssCreate=$_REQUEST['showSocieteAssCreate']?true:false;



array_push($arrFilter,
        array(  "name" => "SocieteAss" ,
                "data" => array( 0 => array( "checked" => $showSocieteAss, "trans"=>"tous/aucun"  ) ,
                                 1 => array( "checked" => $showSocieteAssCreate, "trans" => "creation",     "idx" => "showSocieteAssCreate"),
                               )
             )
);


  //SocieteAss
    //Date création
    //Date SocieteAss
    //Date lim reglement
    //Date de payment
    //statut
    //ref
if ($showSocieteAss)
{
    $requetePre = " SELECT fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux WHERE fk_user = ".$usrid;
    $preresql = $db->query($requetePre);
    $id=0;
    if ($preresql)
    {
        while ($preres = $db->fetch_object($preresql))
        {

            $requete = "SELECT rowid,
                               nom,
                               datec
                          FROM ".MAIN_DB_PREFIX."Societe
                         WHERE rowid = ".$preres->fk_soc;

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

                    if ($res->datec && $showSocieteAssCreate)
                    {
                        require_once(DOL_DOCUMENT_ROOT."/SocieteAss.class.php");
                        $soc = new SocieteAss($db);
                        $soc->fetch($res->rowid);
                       $arrRes = $BCalc->pushDateArr(
                                              $res->datec,
                                              "Cr&eacute;at. de " . "".$soc->nom."",
                                              "Cr&eacute;ation de la soci&eacute;t&eacute;" . $soc->nom . "<BR><P>" . $soc->note,
                                              $res->rowid,
                                              $id,
                                              "SocieteAss",
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
    }
}


?>