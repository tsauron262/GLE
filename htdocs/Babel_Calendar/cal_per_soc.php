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


require_once('pre.inc.php');

require_once(DOL_DOCUMENT_ROOT.'/societe.class.php');
require_once(DOL_DOCUMENT_ROOT.'/Babel_Calendar/BabelCalendar.class.php');


global $BCalc;
$BCalc = new BabelCalendar($langs);


//var_dump($db);

    $action = $_REQUEST['action'];
//1 Get All events pour une company cliente

    $socid = $_REQUEST['societe_id']; //CMP=6
    $soc = new societe($db);
    $soc->fetch($socid);
$arrRes=array();
    $arrFilter = array();
//if module enable et user has right => include_once parts/cal_toto.php
if ($soc->fournisseur > 0)
{
    include("Parts/cal_commande_fourn.php");
    include("Parts/cal_facture_fourn.php");

}
if ($soc->client > 0) {
    include("Parts/cal_actioncomm.php");
    include("Parts/cal_commande.php");
    include("Parts/cal_contrat.php");
    include("Parts/cal_expedition.php");
    include("Parts/cal_facture.php");
    include("Parts/cal_intervention.php");
    include("Parts/cal_livraison.php");
    include("Parts/cal_projet.php");
    include("Parts/cal_propal.php");
    include("Parts/cal_remise.php");

}

if ($_REQUEST['loadAll'] == 1)
{
    include("Parts/cal_commande_fourn.php");
    include("Parts/cal_facture_fourn.php");

    include("Parts/cal_actioncomm.php");
    include("Parts/cal_commande.php");
    include("Parts/cal_contrat.php");
    include("Parts/cal_expedition.php");
    include("Parts/cal_facture.php");
    include("Parts/cal_intervention.php");
    include("Parts/cal_livraison.php");
    include("Parts/cal_projet.php");
    include("Parts/cal_propal.php");
    include("Parts/cal_remise.php");

}

//var_dump($arrFilter);

//Action

// @import "../js/BabelCalWidget/widget/templates/Calendar.css";
//    @import "../js/dojo/resources/dojo.css";
//    @import "../js/dijit/themes/tundra/tundra.css";
//    @import "../js/dojox/grid/_grid/tundraGrid.css";

require_once('cal_per_soc_action.php');
$dtreeJs = '<script type="text/javascript" src="'.DOL_URL_ROOT.'/Babel_Calendar/js/dojo/dojo.js" djConfig="isDebug: false, parseOnLoad: true"></script>';
$dtreeJs .= "<script language='javascript' type='text/javascript' src='".DOL_URL_ROOT."/Babel_Calendar/js/dtree.js' ></script>";
$dtreeJs .= '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/Babel_Calendar/css/dtree.css" />';

$dtreeJs .= '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/Babel_Calendar/js/BabelCalWidget/widget/templates/Calendar.css" />';
$dtreeJs .= '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/Babel_Calendar/js/dojo/resources/dojo.css" />';
$dtreeJs .= '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/Babel_Calendar/js/dijit/themes/tundra/tundra.css" />';
$dtreeJs .= '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/Babel_Calendar/js/dojox/grid/_grid/tundraGrid.css" />';


llxHeader($dtreeJs.$css);
$langs->load('synopsisGene@Synopsis_Tools');
$langs->load('BabelCal');


/*
 *
 * result in HTML
 *
 */


print <<<EOF



<div style="width:1024px; height:600px; background-color:#cccccc; overflow: visible; float: left;">
    <div id="dojoCalendar" dojoType="BabelCalWidget.widget.Calendar"></div>
</div>
<!-- Nom_de_l_objet = new Date("jour, mois date année heures:minutes:secondes") -->
<!-- <input type=button onclick='populate()' value='populate'/>test -->


EOF;
$dtreeJs = '<script type="text/javascript" src="'.DOL_URL_ROOT.'/Babel_Calendar/js/Babel_Calendar.js" djConfig="isDebug: true, parseOnLoad: true"></script>';
//$dtreeJs .= '<script type="text/javascript" src="'.DOL_URL_ROOT.'/Babel_Calendar/css/Babel_Calendar.css" djConfig="isDebug: false, parseOnLoad: true"></script>';
print $dtreeJs;



$BCalc->displayPopulateJs();


print '<input type="hidden" id="leftImg" value="'.DOL_URL_ROOT.'/Babel_Calendar/img/next.gif" />';
print '<input type="hidden" id="basImg" value="'.DOL_URL_ROOT.'/Babel_Calendar/img/bas.gif" />';

/* main table */

print "<div style='float: left; padding-left: 5px; clear: right;'>";
print "<div style='width: auto; height: 600px; clear: both;'>";
print "     <form name='ListChoice' action='' method='POST'>\n";


print "         <table id='filterTable' class='nobordernopadding' style='max-width: 260px;width: 260px;'><thead>\n";
print "             <thead><tr><tH colspan='3'style='font-size:12pt;' >".$langs->Trans('Filtre')."</th><th   style='width: 16px; text-align: right; padding-right: 1px;'  align=right><input type='checkbox'  onChange='checkboxAnimeMainAll(this);' $checked  id='showAll' name='showAll'/></th></tr></thead>\n";



$BCalc->displayFilterPart($arrFilter,$langs);


print "              </tbody></table>";


print "         \n";
print "<div style='clear: both; width: 100%; border-top:4pt Solid rgb(208,212,215);' class='pair'>";
print "     <input type='submit'/ class='button' style='width:100%'>\n";
print "</div>";

print "</div>";
print "     </form>\n";
print "     </div>\n";

print "<div style='padding-top: 6px; clear:both; width: 1024px;'>";
$getUrl = "";
foreach($_REQUEST as $key=>$val)
{
    if (preg_match("/^DOLSESSID/",$key) || preg_match("/^webcalendar/",$key) ||preg_match("/^action/",$key) )
    {
        continue;
    }
    $getUrl .= "&".$key."=".$val;
}
print '<table class="nobordernopadding" width=100%>
        <tr class="pair">
            <td>
                <A href="?action=downloadICS'.$getUrl.'"> '.$langs->trans('Fichier ICS').'</A>
                <A href="?action=downloadVCS'.$getUrl.'"> '.$langs->trans('Fichier VCS').'</A>
            </td>
        </tr>
       </table>';
print '<table class="nobordernopadding" width=100%>
            <tr>
                <th>Zimbra</th>';
print '         <th>Calendar</th>';
print '     </tr>';

//require('Var_Dump.php');
//Var_Dump::displayInit(array('display_mode' => 'HTML4_Text'), array('mode' => 'normal','offset' => 4));


require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Zimbra/ZimbraSoap.class.php');
$zim = new Zimbra("eos");
$zim->langs=$langs;
$ret = $zim->connect();
//Var_Dump::display($ret);
//$zim->debug=true;
$zim->parseRecursiveAptFolder($ret);

print '<tr class="impair">
        <td style="width: 250px;">';
print "<div style='max-height: 170px; background-color: rgb(230,235,237); border-bottom: 2px Solid rgb(208,212,215); overflow: auto;'>";
print "<div class='dtree' >\n";
print "    <script type='text/javascript'>\n";
print "        d = new dTree('d');\n";
print "        d.icon = {\n";
print "            root            : '".DOL_URL_ROOT."/Babel_Calendar/img/zimRootIcone.gif',";
print "            folder          : '".DOL_URL_ROOT."/Babel_Calendar/img/calendar-16x16.png',";
print "            folderOpen      : '".DOL_URL_ROOT."/Babel_Calendar/img/calendar-16x16.png',";
print "            node            : '".DOL_URL_ROOT."/Babel_Calendar/img/ImgCalendarApp.gif',";
print "            empty           : '".DOL_URL_ROOT."/Babel_Calendar/img/empty.gif',";
print "            line            : '".DOL_URL_ROOT."/Babel_Calendar/img/line.gif',";
print "            join            : '".DOL_URL_ROOT."/Babel_Calendar/img/join.gif',";
print "            joinBottom      : '".DOL_URL_ROOT."/Babel_Calendar/img/joinbottom.gif',";
print "            plus            : '".DOL_URL_ROOT."/Babel_Calendar/img/plus.gif',";
print "            plusBottom      : '".DOL_URL_ROOT."/Babel_Calendar/img/plusbottom.gif',";
print "            minus           : '".DOL_URL_ROOT."/Babel_Calendar/img/minus.gif',";
print "            minusBottom     : '".DOL_URL_ROOT."/Babel_Calendar/img/minusbottom.gif',";
print "            nlPlus          : '".DOL_URL_ROOT."/Babel_Calendar/img/nolines_plus.gif',";
print "            nlMinus         : '".DOL_URL_ROOT."/Babel_Calendar/img/nolines_minus.gif'";
print "        };\n";
print "\n";

print "        d.add(1,-1,' ".$langs->Trans('ZimRacine')." ');\n";
foreach($zim->appointmentFolderLevel as $key=>$val)
{
    print "        d.add(".$val["id"].",".$val["parent"].",' ".$val["name"]."','javascript:setZimbraFolder(\'".$val["name"]."\',".$val["id"].")');\n";
}

print "        document.write(d);\n";

print "    </script>\n";
print "\n";
print "    <p><a href='javascript: d.openAll();'>".$langs->Trans("open all")."</a> |
              <a href='javascript: d.closeAll();'>".$langs->Trans("close all")."</a>
           </p>\n";
print "</div>\n"; //dtree
print "</div>\n";

print "\n";
print "<td><div id='FormZimbra' style='display:none; padding-left: 10pt;'>";
//Ajout des parametres
$getUrl = "";
foreach($_REQUEST as $key=>$val)
{
    if (preg_match("/^DOLSESSID/",$key) || preg_match("/^webcalendar/",$key) )
    {
        continue;
    }
    $getUrl .= "&".$key."=".$val;
}

print '<form action=?action=sendToZimbra'.$getUrl.' method="post">';
print  img_picto('calendar-22x22.png','calendar-22x22.png')."&nbsp;<span id='repZimbra' style='font-size: 13pt; font-weight: 900;'>".$langs->Trans("ZimRacine")."</span> : <p/><table>
        <tr>
            <td>".$langs->Trans('ZimCreateCalIn')."
            </td>
            <td>&nbsp;" .img_picto('calendar','calendar'). "

                <input type='hidden' id='repZimbraId' name='repZimbraId' value=''/>
            </td>
            <td>
                <input name='zimbraCreateFold' style='width: 150px;'/>
            </td>
        </tr>
        <tr>
            <td colspan='1'>".$langs->Trans('ZimCalColor')."&nbsp;</td>
            <td colspan='2'>&nbsp;
                <SELECT name='zimbraColorFold' style='width: 170px;'>
                    <option SELECTED value='1'>".$langs->Trans("blue")."</option>
                    <option value='2'>".$langs->Trans("cyan")."</option>
                    <option value='3'>".$langs->Trans("green")."</option>
                    <option value='4'>".$langs->Trans("purple")."</option>
                    <option value='5'>".$langs->Trans("red")."</option>
                    <option value='6'>".$langs->Trans("yellow")."</option>
                    <option value='7'>".$langs->Trans("rose")."</option>
                    <option value='8'>".$langs->Trans("grey")."</option>
                    <option value='9'>".$langs->Trans("orange")."</option>
                </SELECT>
            </td>
        </tr>";

//            <tr>
//                <td colspan='1'>".$langs->Trans('ZimTags')."&nbsp;</td>
//                <td colspan='2'>&nbsp;";
// $zim->displayTagsSelect(true,"zimbraTag","zimbraTag");
//print "
//
//                </td>
//            </tr>

print "       </table>";

print '<p><input type="submit" class="button"/></p></form>';
print "</div>";
//print '          <tr> <td><form action=? method="post"><input type="submit"/></form></td></tr>';
print "</table>";
print "</div>";

?>
<script type="text/javascript">

postInit();
</script>
</body>
</html>

<?php

/* Fonctions */


//
//function pushDateArr($arrRes,$date,$name,$desc,$doliId,$uid,$cat,$allday,$loc="",$isOrg=1,$l='null')
//{
////    var_dump($arrRes);
//
//    if (preg_match("/([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})[\W]?([0-9]{2})?[\W]?([0-9]{2})?[\W]?([0-9]{2})?/",$date,$arrPreg))//2007-08-31 12:01:01
//    {
//        array_push($arrRes,array( "start" => array("year"=> $arrPreg[1] , "month" => $arrPreg[2] , "day" => $arrPreg[3], "hour"=>$arrPreg[4] , "min" => $arrPreg[5]),
//                                  "end"  => array("year"=> $arrPreg[1] , "month" => $arrPreg[2]  , "day" => $arrPreg[3], "hour"=>$arrPreg[4] , "min" => $arrPreg[5]),
//                                  "transp"   => "O",
//                                  "fb"       => "B",
//                                  "status"   => "TENT",
//                                  "allDay"   => $allday,
//                                  "isOrg"    => $isOrg,
//                                  "noBlob"   => "0",
//                                  "l"        => $l,
//                                  "name"     => $name,
//                                  "loc"      => $loc,
//                                  "descHtml" => $desc,
//                                  "doliId"   => $doliId,
//                                  "cat"      => $cat,
//                                  'uid'      => $uid,
//                                 )
//                             );
//
//    return($arrRes);
//    }
//}
//
//function displayFilterPart($arr,$langs)
//{
//    foreach($arr as $key=>$val)
//    {
//        $name=$val["name"];
//        print "             <thead>
//                              <tr class='impair' ><td rowspan=1  colspan=2 style='padding-left: 1px; padding-right: 10px; ' >
//                                <span onClick='hideTab(\"tr".$name."\")'>
//                                  <img ALIGN='ABSMIDDLE' id='tr".$name."Img' name='bas' src='img/bas.gif'/>
//                                    &nbsp;".$langs->trans($name)."&nbsp;
//                                </span>
//                              </td>\n";
//        $catChecked = false;
//        foreach($val['data'] as $key1=>$val1)
//        {
//            $checked = false; if ($val1['checked']) { $checked="checked='checked'";}
//            if ($val1['idx'])
//            {
//                $checked = false;if ($val1["checked"] && $catChecked) { $checked="checked='checked'";}
//                print "             <tr class='pair' >
//                                      <td colspan='2' style='width: 130px;'>".$langs->Trans($val1["trans"])."</td>
//                                      <td  style='width: 16px;'  align=right><input onChange='checkboxFirst();' type='checkbox' $checked name='".$val1["idx"]."'/>\n";
//
//
//            } else {
//                $catChecked = true;
//                print "
//                                      </td>
//                                      <td colspan=2 style='width: 16px;' align=right><input $checked onChange='checkboxAnimeMain(this);' type='checkbox' $checked name='show".$name."'/>
//                                    </tr>\n";
//
//                print "           </thead><tbody id='tr".$name."'>";
//                print "             <tr class='pair' >
//                                      <td style='padding-left:12pt;' rowspan='".count($val["data"])."' id='tmpTest'  >&nbsp;
//                                      </td>
//                                    </tr>\n";
//            }
//            print " \n";
//
//        }
//
//    }
//}

?>