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


/* show Propal Filter */

$showPropal = $_REQUEST['showPropal']=='on'?true:false;
$showPropalCreate = $_REQUEST['showPropalCreate']=='on'?true:false;
$showPropalValid = $_REQUEST['showPropalValid']=='on'?true:false;
$showPropalCloture = $_REQUEST['showPropalCloture']=='on'?true:false;
$showPropalDate = $_REQUEST['showPropalDate']=='on'?true:false;
$showPropalFinValid = $_REQUEST['showPropalFinValid']=='on'?true:false;

array_push($arrFilter,
        array(  "name" => "Propal" ,
                "data" => array( 0 => array( "checked" => $showPropal, "trans"=>"tous/aucun"  ) ,
                                 1 => array( "checked" => $showPropalCreate, "trans" => "creation",     "idx" => "showPropalCreate"),
                                 2 => array( "checked" => $showPropalValid, "trans" => "validation",   "idx" => "showPropalValid"),
                                 3 => array( "checked" => $showPropalCloture, "trans" => "cloture",      "idx" => "showPropalCloture"),
                                 4 => array( "checked" => $showPropalDate, "trans" => "date propale", "idx" => "showPropalDate"),
                                 5 => array( "checked" => $showPropalFinValid, "trans" => "fin de validit&eacute",   "idx" => "showPropalFinValid"),
                               )
             )
);


//var_dump($_REQUEST);


//Res format : aptArr= array("0" => array( "start"    => array( "year"=> 2009 , "month" => 2 , "day" => 26, "hour"=>12 , "min" => 30 ),
//                              "end"      => array( "year"=> 2009 , "month" => 2 , "day" => 26, "hour"=>14 , "min" => 30 ),
//                              "fb"       => "B",
//                              "transp"   => "O",
//                              "status"   => "TENT",
//                              "allDay"   => "0",
//                              "name"     => "Test Zimbra 1",
//                              "loc"      => "Aix en Provence",
//                              "isOrg"    => "1",
//                              "url"      => "http://10.91.130.1/test.php",
//                              "noBlob"   => "0",
//                              "l"        => $zim->appointmentFolderId[2],
//                              "desc"     => "test",
//                              "descHtml" => "test"
//                 ),
//                "1" => array( "start"    => array( "year"=> 2009 , "month" => 2 , "day" => 26, "hour"=>18 , "min" => 30 ),
//                              "end"      => array( "year"=> 2009 , "month" => 2 , "day" => 26, "hour"=>20 , "min" => 30 ),
//                              "fb"       => "B",
//                              "transp"   => "O",
//                              "status"   => "TENT",
//                              "allDay"   => "0",
//                              "name"     => "Test Zimbra 2 HTML",
//                              "loc"      => "Aix en Provence",
//                              "isOrg"    => "1",
//                              "url"      => "http://10.91.130.1/test.php",
//                              "noBlob"   => "0",
//                              "l"        => $zim->appointmentFolderId[2],
//                              "desc"     => "test",
//                              "descHtml" => "<H2>test</H2>"
//                 ),
//                "2" => array( "start"    => array( "year"=> 2009 , "month" => 2 , "day" => 26, "hour"=>10 , "min" => 30 ),
//                              "end"      => array( "year"=> 2009 , "month" => 2 , "day" => 26, "hour"=>12 , "min" => 45 ),
//                              "fb"       => "B",
//                              "transp"   => "O",
//                              "status"   => "TENT",
//                              "allDay"   => "0",
//                              "name"     => "Test Zimbra 3",
//                              "loc"      => "Aix en Provence",
//                              "isOrg"    => "1",
//                              "url"      => "http://10.91.130.1/test.php",
//                              "noBlob"   => "0",
//                              "l"        => $zim->appointmentFolderId[2],
//                              "desc"     => "test",
//                              "descHtml" => "test"
//                 )
//                 );


  //Date Création
  $requete = "SELECT datec
                FROM ".MAIN_DB_PREFIX."societe
               WHERE rowid = " . $socid;


if ($showPropal)
{
  //Propal
    //Date création
    //Date validation
    //Date cloture
    //Date fin_validite
    //statut
    //ref
  $requete = "SELECT rowid,
                     ref,
                     datec,
                     datep,
                     fin_validite,
                     date_valid,
                     date_cloture,
                     fk_user_author,
                     fk_user_valid,
                     fk_user_cloture,
                     fk_statut,
                     note,
                     note_public,
                     date_livraison
                 FROM ".MAIN_DB_PREFIX."propal
                 WHERE fk_soc =" . $socid;
    $resql = $db->query($requete);
    $id=0;
//print $socid;
//var_dump($db);
    if ($resql)
    {
        while($res=$db->fetch_object($resql))
        {
            $url = $dolibarr_main_url_root ."/comm/propal.php?propalid=".$res->rowid;
            if ($res->datec && $showPropalCreate)
            {

                   $arrRes = $BCalc->pushDateArr(
                                          $res->datec,
                                          "Créat. de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Cr&eacute;ation de la proposition commerciale " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "propal",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
            }
            if ($res->datep && $showPropalDate)
            {
                    $arrRes = $BCalc->pushDateArr(
                                          $res->datep,
                                          "Date Prop " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Proposition commerciale " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "propal",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
            }
            if ($res->fin_validite && $showPropalFinValid)
            {
                $arrRes = $BCalc->pushDateArr(
                                      $res->fin_validite,
                                      "Fin valid de " . "".$res->ref."" . " (".$soc->nom.")",
                                      "Fin de validiter de la proposition commerciale " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                      $res->ref,
                                      $id,
                                      "propal",
                                      1,
                                      "",
                                      1,
                                      'null',
                                          $url);
                             $id++;

            }
            if ($res->date_valid && $showPropalValid)
            {
                    $arrRes = $BCalc->pushDateArr(
                                          $res->date_valid,
                                          "Valid de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Validation de la proposition commerciale " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "propal",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
            }
            if ($res->date_cloture && $showPropalFinValid)
            {
                $arrRes = $BCalc->pushDateArr(
                                      $res->date_cloture,
                                      "Clot de " . "".$res->ref."" . " (".$soc->nom.")",
                                      "Cloture Proposition commerciale " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                      $res->ref,
                                      $id,
                                      "propal",
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