<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 29 sept. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : dispoUser-html_response.php
  * GLE-1.2
  */
require_once('../../../main.inc.php');
$userStr = $_REQUEST['user'];
$userArr = preg_split('/-/',$userStr);
print "<div>";
$dateDeb = $_REQUEST['dateDeb'];
$period = $_REQUEST['period'];
if (!$period > 0)
{
    $period = 10;
}

$curDate = mktime(0,0,0,date('m'),date('d'),date('Y'));
if ($dateDeb>0)
{
    $curDate = mktime(0,0,0,date('m',$dateDeb),date('d',$dateDeb),date('Y',$dateDeb));
}
$lastDate = $curDate + $period * 3600 * 24;
foreach($userArr as $key=> $userId)
{
    if ($userId > 0)
    {
        $requete = "SELECT UNIX_TIMESTAMP(datei) as udatei, duree FROM ".MAIN_DB_PREFIX."Synopsis_demandeInterv WHERE ifnull(fk_user_target,fk_user_prisencharge) = $userId";
        $sql=$db->query($requete);
        while ( $res=$db->fetch_object($sql) ) {
            $arrDate[$res->udatei]=$res->duree + $arrDate[$res->udatei];
        }
        $requete = "SELECT UNIX_TIMESTAMP(datei) as udatei, duree FROM ".MAIN_DB_PREFIX."Synopsis_fichinter WHERE fk_user_author = $userId";
        $sql=$db->query($requete);
        while ( $res=$db->fetch_object($sql) ) {
            $arrDate1[$res->udatei]=$res->duree + $arrDate1[$res->udatei];
        }
        $suser = new User($db);
        $suser->fetch($userId);
        print "<table border=1><tr><td class='ui-widget-content' style='max-width:150px; width:150px;' width=150>".$suser->getNomUrl(1);
        for($i=$curDate;$i<$lastDate;$i+=3600*24)
        {
            $color='#33F333';
            if ($arrDate[$i]>3600*7|| $arrDate1[$i]>3600*7)
            {
                $color= "#EE3333";
            } else if ($arrDate[$i]>4*3600|| $arrDate1[$i]>4*3600)
            {
                $color= "#EB9611";
            } else if ($arrDate[$i]>0||$arrDate1[$i]>0)
            {
                $color= "#F5F3A5";
            } else if (date('N',$i)==6 ||date('N',$i)==7)
            {
                $color= "#A5A5A5";
            }

            $durTmp = convDur($arrDate[$i]);
            $durTmp1 = convDur($arrDate1[$i]);
            print '<td id="'.$userId.'-'.date('U',$i).'" style="background-color: '.$color.';line-height: 10px; width: 40px;"><table width=100%><tr><td>'.date('d/m/Y',$i).'<tr><td>'.($arrDate1[$i]>0? $durTmp1['hours']['abs'].'h'.($durTmp1['minutes']['rel']>0?$durTmp1['minutes']['rel'].'m':'').'&nbsp;/&nbsp;':"&nbsp;").($arrDate1[$i]>0?$durTmp['hours']['abs'].'h'.($durTmp['minutes']['rel']>0?$durTmp['minutes']['rel'].'m':""):"&nbsp;").'</table></td>';
        }
        print "</table>";

    }

}
print "</div>";


?>
