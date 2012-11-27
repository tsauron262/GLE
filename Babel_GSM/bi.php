<?php
/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
include_once("../master.inc.php");

include_once ("./main.inc.php");
include_once ("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/ecm/class/ecmdirectory.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/ecm.lib.php");
require_once(DOL_DOCUMENT_ROOT."/BabelJasper/BabelJasper.class.php");
//var_dump($user->rights->BabelGSM);
if ($user->rights->JasperBabel->JasperBabel->Affiche != 1 || $user->rights->BabelGSM->BabelGSM_ctrlGest->AfficheBI !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}
$langs->load("synopsisGene@Synopsis_Tools");
if ($conf->global->MAIN_MODULE_ZIMBRA == 1)
{
    require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/Zimbra.class.php");
}

require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");


//Load this before client.php
$proto = $conf->global->JASPER_PROTO;
$host = $conf->global->JASPER_HOST;
$port = $conf->global->JASPER_PORT;
$path = $conf->global->JASPER_PATH;

//$webservices_uri = "http://10.91.130.6:8280/jasperserver/services/repository";
$webservices_uri = $proto . "://".$host . ":".$port . $path;
//Connect to webServices
//require_once('client.php');

$jasper_obj = new BabelJasper($db);

//$someInputControlUri = "/ContentFiles/Babel/ERP";
$someInputControlUri = $conf->global->JASPER_REPO_PATH_GENERATED;
$jrxmlControlUri = $conf->global->JASPER_REPO_PATH_REPORT;
//$jrxmlControlUri = "/Reports/Babel/ERP";
$GLOBALS["verbose"] = false;

$requete = "SELECT jasperLogin, jasperPass " .
        "     FROM Babel_JasperBI_li_Users " .
        "    WHERE user_refid = ".$user->id."";
$resql = $db->query($requete);
$res = $db->fetch_object($resql);
$GLOBALS["loginJasper"]= $res->jasperLogin;
$GLOBALS["passJasper"] = $res->jasperPass;
//$GLOBALS["passJasper"] = "redalert";
//var_dump ($_SESSION);
//$GLOBALS["filePath"] = $GLOBALS["davAdd"]  . $GLOBALS["filePath"] ;
$folders = array();
$remArray = array();
$preg_filter = "/html$/";
$jasper_obj->parseFolder($someInputControlUri,$preg_filter,&$remArray);

$remArray1 = array();
$preg_filter = "/pdf$/";
$jasper_obj->parseFolder($someInputControlUri,$preg_filter,&$remArray1);

$remArray2 = array();
$preg_filter = "//";
$jasper_obj->parseFolder($jrxmlControlUri,$preg_filter,&$remArray2);
$remArrayZimbra = array();
$zimbra_obj=false;
if ($conf->global->MAIN_MODULE_ZIMBRA == 1)
{
    $zimbra_obj = new Zimbra($db);

    $zimbra_obj->fetch_user($user->id);
    $GLOBALS["zimbraLogin"]= $zimbra_obj->zimbraLogin;
    $GLOBALS["zimbraPass"] = $zimbra_obj->zimbraPass;

    $GLOBALS["zimbraHost"]=$conf->global->ZIMBRA_HOST;
    $GLOBALS["zimbraProto"]=$conf->global->ZIMBRA_PROTO;
    $GLOBALS["zimbraDavProto"]=$conf->global->ZIMBRA_WEBDAVPROTO;

    if ($_GET['action']=="refreshZimbra")
    {
        require_once (DOL_DOCUMENT_ROOT . '/BabelJasper/WebDAV/Client.php');
        $zimbra_obj->RecursiveRefreshBriefcase($user->id);
    }
    $remArrayZimbra= $zimbra_obj->ParseBriefcase($user->id);
}

if ($_POST['DlRapportURI']  && $_POST['DlRapportURI'] != -1 && $_GET['action']=="download")
{
    $jasper_obj->dlReport($remArray1,$_POST['DlRapportURI']);
    exit;
}
if ($_POST['GenRapportURI'] != -1  && !preg_match("/Afficher/i",$_POST['subAction'] ) && $_GET['action']=="generate")
{
    $jasper_obj->genReport($remArray2,$_POST['GenRapportURI']);
    exit;
}
//header

llxHeader("", "Dolibarr BI", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);
print $gsm->MainMenu();


//List available graphes
print '<table width="100%" class="border">';
print "   <tr class='liste_titre'>\n";
print "       <td colspan=4><SPAN style='width: 100%;font-size:14pt; padding: 5pt;'>".$langs->trans("BabelGSMBiTitle")."</SPAN></td>\n";
print "   </tr>\n";
print '</table>';
if ($user->rights->JasperBabel->JasperBabel->Generate == 1)
{
    print "<FORM method='POST' ACTION='".$_SERVER['PHP_SELF']."?action=generate'>";
    print '<table width="100%" class="border">';
    print "   <tr class='pair'>\n";
    print "   <th  class='bi' colspan=1 style=' '>".$langs->trans("BabelGSMGenReport")."</TH>\n";
    print "   <td  class='bi' colspan=1 style='  max-width: 100px; width: 100px;'>\n";
    #print "<TABLE><TR><TH>Nom du rapport<TD>";
    print "<SELECT class='biselect' name='GenRapportURI'>";
        print " <OPTION value ='-1'>Select-></OPTION>";
    foreach($remArray2 as $key=>$val)
    {
        print $key;
        if ($_POST['GenRapportURI'] && $_POST['GenRapportURI'] == $key)
        {
            print "<OPTION SELECTED value='".$key."'>".utf8_decode(basename($val,""))."</OPTION>";
        } else {
            print "<OPTION value='".$key."'>".utf8_decode(basename($val,""))."</OPTION>";
        }
    }
    print "</SELECT>";
    print "</TD>";
    print "<TD >";
    print "<input class='bibutton' type='submit' name='subAction' value='".$langs->trans("BabelGSMGen")."'/>";
    print "   </tr>\n";
    print '</table>';
    print "</FORM>";

}

print "<FORM method='POST' ACTION='".$_SERVER['PHP_SELF']."?action=download'>";
print '<table width="100%" class="border">';
print "   <tr class='impair'>\n";
print "   <th  class='bi' colspan=1 style=' '>".$langs->trans("BabelGSMDlReport")."</TH>\n";
print "   <td  class='bi' colspan=1 style='  max-width: 100px; width: 100px;'>\n";

#print "<TABLE><TR><TH>Nom du rapport<TD>";
print "<SELECT class='biselect' name='DlRapportURI'>";
    print " <OPTION value ='-1'>Select-></OPTION>";
foreach($remArray1 as $key=>$val)
{
    if ($_POST['DlRapportURI'] && $_POST['DlRapportURI'] == $key)
    {
        print "<OPTION SELECTED value='".$key."'>".basename($val,".pdf")."</OPTION>";
    } else {
        print "<OPTION value='".$key."'>".basename($val,".pdf")."</OPTION>";
    }
}
print "</SELECT>";
print "</TD>";
print "<TD ><input class='bibutton' type='submit' name='subAction' value='T&eacute;l&eacute;charger'/></TD>";
print '</TR>';
print '</table>';
print "</FORM>";

if ($conf->global->MAIN_MODULE_ECM && ($_POST['GenRapportURI'] >= 0 || $_POST['DlRapportURI'] >=0 ) && $user->rights->JasperBabel->JasperBabel->PushECM == 1)
{
    print "<FORM method='POST' ACTION='".$_SERVER['PHP_SELF']."?action=PushToECM'>";
    print '<table width="100%" class="border">';
    print "   <tr class='impair'>\n";
    print "   <th  class='bi' colspan=1 style='  '>".$langs->trans('BabelGSMpushECM')."</TH>\n";
    print "   <td   class='bi' colspan=1 style=' max-width: 100px; width: 100px; '>";
    print "<SELECT class='biselect' name='RapportURI'>";
    print " <OPTION value ='-1'>Select-></OPTION>";
    print "      <OPTGROUP label=\"".$langs->trans('BabelGSMDlReport')."\">";

    foreach($remArray1 as $key=>$val)
    {
        if ($_POST['RapportURI'] && $_POST['RapportURI'] == "Dl_".$key)
        {
            print "<OPTION SELECTED value='Dl_".$key."'>".utf8_decode(basename($val,".pdf"))."</OPTION>";
        } else {
            print "<OPTION value='Dl_".$key."'>".utf8_decode(basename($val,".pdf"))."</OPTION>";
        }
    }
    print "      </OPTGROUP>";
    print "      <OPTGROUP label=\"".$langs->trans('BabelGSMGenReport')."\">";
    foreach($remArray2 as $key=>$val)
    {
        if ($_POST['RapportURI'] && $_POST['RapportURI'] == "Gen_".$key)
        {
            print "<OPTION SELECTED value='Gen_".$key."'>".utf8_decode(basename($val,""))."</OPTION>";
        } else {
            print "<OPTION value='Gen_".$key."'>".utf8_decode(basename($val,""))."</OPTION>";
        }
    }
    print "</OPTGROUP>";

    print "</SELECT></TD>";
    print "<TD>";
    print "<input class='bibutton'
                  type='submit'
                  name='subAction'
                  value='".$langs->Trans("BabelGSMPush")."'/>";
    print "</TD>";
    print "</TR>";
    print "</TABLE>";
    print "</FORM>";
}

if ($conf->global->MAIN_MODULE_ZIMBRA && ($_POST['GenRapportURI'] >= 0 || $_POST['DlRapportURI'] >=0 ) && $user->rights->SynopsisZimbra->Push == 1 && $user->rights->JasperBabel->JasperBabel->PushZimbra == 1)
{
    print "<FORM method='POST' ACTION='".$_SERVER['PHP_SELF']."?action=PushToZimbra'>";
    print '<table width="100%" class="border">';
    print "   <tr class='pair'>\n";
    print "   <th  class='bi' colspan=1 style='  '>".$langs->trans("BabelGSMPushZimbra")."</TH>\n";
    print "   <td  class='bi' colspan=1 style='  '>";
    print "     ".$langs->trans('BabelGSMWhere');

    print '<a href="'.$_SERVER["PHP_SELF"].'?action=refreshZimbra">'.img_picto($langs->trans("Refresh"),'refresh').'</a>';

    print "  <SELECT class='biselect' name='ZimbraBC' > ";
    print "      <OPTION value ='-1'>Select-></OPTION>";
    foreach ($remArrayZimbra as $keyZ => $valZ)
    {
            if ($_POST["ZimbraBC"] && $_POST["ZimbraBC"] == "z".$keyZ)
            {
                print "            <OPTION SELECTED value=z".$keyZ.">".$valZ."</OPTION>";
            } else {
                print "            <OPTION value=z".$keyZ.">".$valZ."</OPTION>";
            }
    }
    print "       </SELECT>";
    print " <BR> ".$langs->trans('BabelGSMWhat');

    print " <SELECT class='biselect' name='RapportURI'>";
    print "      <OPTION value ='-1'>Select-></OPTION>";
    print "      <OPTGROUP label=\"".$langs->trans('BabelGSMDlReport')."\">";
    foreach($remArray1 as $key=>$val)
    {
        if ($_POST['RapportURI'] && $_POST['RapportURI'] == "Dl_".$key)
        {
            print "<OPTION SELECTED value='Dl_".$key."'>".utf8_decode(basename($val,".pdf"))."</OPTION>";
        } else {
            print "<OPTION value='Dl_".$key."'>".utf8_decode(basename($val,".pdf"))."</OPTION>";
        }
    }
    print "      </OPTGROUP>";
    print "      <OPTGROUP label=\"".$langs->trans('BabelGSMGenReport')."\">";
    foreach($remArray2 as $key=>$val)
    {
        if ($_POST['RapportURI'] && $_POST['RapportURI'] == "Gen_".$key)
        {
            print "<OPTION SELECTED value='Gen_".$key."'>".utf8_decode(basename($val,""))."</OPTION>";
        } else {
            print "<OPTION value='Gen_".$key."'>".utf8_decode(basename($val,""))."</OPTION>";
        }
    }
    print "</OPTGROUP>";

    print "</SELECT></TD>";
    print "<TD><input class='bibutton' type='submit' name='subAction' value='".$langs->trans('BabelGSMPush')."'/>";
    print "</TD>";
    print "</TR>";
    print "</TABLE>";
    print "</FORM>";
}
//print "</TABLE>\n";
//List graphes that are generables instantly
//var_dump($_POST);
if ($_POST['RapportURI'] != -1 && $_GET['action']=="display")
{
    //echo 'display';
    $jasper_obj->saveReport($remArray,$_POST['RapportURI']);
}
if ($_POST['GenRapportURI'] != -1  && preg_match("/Afficher/i",$_POST['subAction'] ) && $_GET['action']=="generate")
{
    //echo 'gen';
    $jasper_obj->genHTMLReport($remArray2,$_POST['GenRapportURI']);
}

if ( $_GET['action']=="PushToECM")
{
    //echo $_POST['RapportURI'];
    $arrSub=array();
    $id_rapport=false;
    $pdf_content=false;
    if (preg_match("/^Dl_([\w]*)/",$_POST['RapportURI'],$arrSub))
    {
         //print $arrSub[1];
         $id_rapport=$arrSub[1];
    } else if (preg_match("/^Gen_([\w]*)/",$_POST['RapportURI'],$arrSub)){
         $id_rapport=$arrSub[1];
    }
    // Load ecm object
    $ecmdir = new ECMDirectory($db);
    $ecmdir->get_full_arbo();
    $ecmId=0;
    //       var_dump($ecmdir->cats);
    foreach($ecmdir->cats as $key => $val)
    {
        if ($ecmdir->cats[$key]["label"] == "BI")
        {
            $ecmId = $ecmdir->cats[$key]["id"] ;
        }
    }
    $ecmdir->id = $ecmId;
    $relativepath=$ecmdir->getRelativePath(1);
    $upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
    //On Génère le rapport et on le Stock dans l'ECM
    $flag_continue=true;
    if (is_readable($upload_dir))
    {
        $ECMfilename =$upload_dir."".utf8_decode(basename($remArray2[$id_rapport])).".pdf";
        if (is_file($ECMfilename) && $_POST['confirmEcrase'] == 1)
        {
            $flag_continue = true;

        } else if (is_file($ECMfilename))
        {
            if ("x".$_POST['confirmEcrase']=="x")
            {
                //Print Confirm Screen => rerun
                print $langs->trans("BabelGSMConfirmCrush");
                print "<FORM method='POST' action='".$_SERVER['PHP_SELF']."?action=PushToECM'";
                print "<SELECT class='biselect' name='confirmEcrase'>";
                print "<OPTION value='-1'> ".$langs->trans("non")."</OPTION>";
                print "<OPTION value='1'> ".$langs->trans("oui")."</OPTION>";
                print "</SELECT>";
                print "<input type='hidden' name='RapportURI' value='".$_POST['RapportURI']."' />";
                print "<input type='submit' name='submit' value='submit' />";
                print "</FORM>";
                $flag_continue= false;
            } else {
                //header("Location : ".$_SERVER['PHP_SELF']);
                print "Return to begin";
                $flag_continue= false;
            }
        }
    } else {
        echo "Err : ".$upload_dir . " n'est pas accessible en lecture";
    }


    if (preg_match("/^Dl_([\w]*)/",$_POST['RapportURI'],$arrSub) && $flag_continue )
    {
         //print $arrSub[1];
         //On Télécharge le rapport et on le Stock dans l'ECM
         $pdf_content = $jasper_obj->dlReport($remArray1,$arrSub[1],true);
         $id_rapport=$arrSub[1];
    } else if (preg_match("/^Gen_([\w]*)/",$_POST['RapportURI'],$arrSub) && $flag_continue){
         $pdf_content = $jasper_obj->genReport($remArray2,$arrSub[1],true);
         $id_rapport=$arrSub[1];
    }
    if ($pdf_content && $id_rapport && $flag_continue)
    {
         // Load ecm object
         $ecmdir = new ECMDirectory($db);
         $ecmdir->get_full_arbo();
         $ecmId=0;
  //       var_dump($ecmdir->cats);
         foreach($ecmdir->cats as $key => $val)
         {
            if ($ecmdir->cats[$key]["label"] == "BI")
            {
                $ecmId = $ecmdir->cats[$key]["id"] ;
            }
         }
         $ecmdir->id = $ecmId;
         $relativepath=$ecmdir->getRelativePath(1);
         $upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
         //On Génère le rapport et on le Stock dans l'ECM
         $flag_add=true;
         if (is_writable($upload_dir))
         {
            $ECMfilename =$upload_dir."".utf8_decode(basename($remArray2[$id_rapport])).".pdf";
            if (is_file($ECMfilename))
            {
                $flag_add = false;
            }
            $fh = fopen($ECMfilename,"w");
            //echo $ECMfilename;
            fwrite($fh,$pdf_content);
            fclose($fh);
         } else {
            echo "Err : ".$upload_dir . " n'est pas accessible en &eacute;criture";
         }

         if ($flag_add) $result=$ecmdir->changeNbOfFiles('+');
    }
}

if ( $_GET['action']=="PushToZimbra" && $user->rights->SynopsisZimbra->Push == 1 && $user->rights->JasperBabel->JasperBabel->PushZimbra == 1)
{
    //echo $_POST['RapportURI'];
    $arrSub=array();
    $id_rapport=false;
    $pdf_content=false;
    $rapport_name = false;
    $to_webDav_BriefCase = $remArrayZimbra[preg_replace("/^z/","",$_POST["ZimbraBC"])];

    if (preg_match("/^Dl_([\w]*)/",$_POST['RapportURI'],$arrSub))
    {
         //print $arrSub[1];
         //On Télécharge le rapport et on le Stock dans l'ECM
         $pdf_content = $jasper_obj->dlReport($remArray1,$arrSub[1],true);
         $id_rapport=$arrSub[1];
         $rapport_name=$remArray1[$arrSub[1]];
    } else if (preg_match("/^Gen_([\w]*)/",$_POST['RapportURI'],$arrSub)){
         $pdf_content = $jasper_obj->genReport($remArray2,$arrSub[1],true);
         $id_rapport=$arrSub[1];
         $rapport_name=$remArray2[$arrSub[1]];
    }
    if ($pdf_content && $id_rapport && $_POST["ZimbraBC"] && basename($rapport_name))
    {
        $url = $GLOBALS['zimbraDavProto']."://".$GLOBALS['zimbraHost']."/dav/".$GLOBALS['zimbraLogin']."/Briefcase";

        //$url .= "testdav/".basename($rapport_name);
        $davPath = "".$to_webDav_BriefCase."/".basename($rapport_name).".pdf";
        $url .= $davPath;
//        print "<BR>".$url."<BR>";
        $zimbra_obj->pushToZimbraBriefcase($url,$pdf_content,$user->id);
    }
}
$gsm->jsCorrectSize(true);

?>