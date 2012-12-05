<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 17 nov. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : createDI-xml_response.php
  * GLE-1.2
  */
    require_once('../../../main.inc.php');
    require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
    require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
    require_once(DOL_DOCUMENT_ROOT."/Synopsis_DemandeInterv/demandeInterv.class.php");
    $id = $_REQUEST['id'];
    $com = new Synopsis_Commande($db);
    $com->fetch($id);

    $xmlStr = "<ajax-response>";
    //1 create DI principale
    $di = new demandeInterv($db);
    $di->socid = $com->socid;
    $di->fk_commande = $id;
    $di->date = convertDate($_REQUEST['datei']);
    $di->ref=$di->getNextNumRef($com->societe);
    $di->fk_user_prisencharge=$_REQUEST['userid'];
    $di->author=$user->id;
    $di->description=utf8_decode($_REQUEST['desc']);
    $di->modelpdf='soleil';

    $diId = $di->create();

    if ($diId > 0){

        $demandeIntervid=$diId;
        foreach($_REQUEST as $key=>$val){
            if (preg_match('/^extraKey-([0-9]*)/',$key,$arr))
            {
                $idExtraKey = $arr[1];
                $valExtraKey = $val;
                //print "<input type='hidden' name='type-".$res->id."' value='checkbox'>";
                if ($_REQUEST['type-'.$idExtraKey]=="checkbox")
                {
                    if ($valExtraKey == 'On' ||$valExtraKey == 'on' || $valExtraKey == 'ON' ){
                        $valExtraKey = 1;
                    } else {
                        $valExtraKey = 0;
                    }
                }
                $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_fichinter_extra_value
                                        ( interv_refid,extra_key_refid,extra_value,typeI)
                                 VALUES ( ".$demandeIntervid.",'".$idExtraKey."','".addslashes(utf8_decode($val))."','DI')";
                $sql = $db->query($requete);
                //print $requete;
            }
        }



        //2 create intervDet
        $xmlStr .= "<OK>OK</OK>";
        foreach($_REQUEST as $key=>$val)
        {
            if (preg_match('/^desci([0-9]*)/',$key,$arr))
            {
                 $desc = utf8_decode($val);
                 $datei = "'".date('Y-m-d',convertDate($_REQUEST['datei'.$arr[1]]))."'";
                 $duration = ConvertTime2Seconds($_REQUEST['duri'.$arr[1]],$_REQUEST['durmini'.$arr[1]]);
                 $typeInter = $_REQUEST['typeInterv'.$arr[1]];
                 $isForfait = ($_REQUEST['isForfait'.$arr[1]]=='On' ||$_REQUEST['isForfait'.$arr[1]]=='on' ||$_REQUEST['isForfait'.$arr[1]]=='ON'?1:0);
                 $pu_ht = $_REQUEST['pu_ht'.$arr[1]];
                 $qte = $_REQUEST['qte'.$arr[1]];
                 $fk_commandedet = $arr[1];
                 //    function addline($demandeIntervid, $desc, $date_intervention, $duration,$typeinterv,$qte=1,$pu_ht=0,$isForfait=0,$fk_commandedet=false,$fk_contratdet=false)
                 $di->addline($diId, $desc,$datei,$duration,$typeInter,$qte,$pu_ht,$isForfait,$fk_commandedet,false);
            }
        }
    } else {
        $xmlStr .= "<KO>KO</KO>";
    }

    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
        header("Content-type: application/xhtml+xml;charset=utf-8");
    } else {
        header("Content-type: text/xml;charset=utf-8");
    }
    $et = ">";
    print "<?xml version='1.0' encoding='utf-8'?$et\n";
    print $xmlStr;
    print "</ajax-response>";

function convertDate($date){
    if (preg_match("/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/",$date,$arr))
    {
        return strtotime(($arr[3].'-'.$arr[2].'-'.$arr[1]. " ".$arr[4].":".$arr[5]));
    }
    if (preg_match("/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})/",$date,$arr))
    {
        return strtotime(($arr[3].'-'.$arr[2].'-'.$arr[1]));
    }
}
?>