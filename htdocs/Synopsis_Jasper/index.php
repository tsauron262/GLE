<?php

/*
 * * GLE by Synopsis et DRSI
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



//Need pear::soap & Net_DIME
//Pour Zimbra Need pear::HTTP
#
if ($_REQUEST['confirmEcrase'] == "-1" && $_REQUEST['action'] == "PushToECM") {
    header("Location: " . $_SERVER['PHP_SELF']);
    exit(0);
}

require_once("../main.inc.php");

require_once ("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/ecm/class/ecmdirectory.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/ecm.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Jasper/SynopsisJasper.class.php");
if ($user->rights->SynopsisJasper->SynopsisJasper->Affiche != 1) {
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}

if ($conf->global->MAIN_MODULE_ZIMBRA == 1) {
    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Zimbra/Zimbra.class.php");
}

//Load this before client.php
$proto = $conf->global->JASPER_PROTO;
$host = $conf->global->JASPER_HOST;
$port = $conf->global->JASPER_PORT;
$path = $conf->global->JASPER_PATH;

$webservices_uri = $proto . "://" . $host . ":" . $port . $path;
$jasUser = "jasperadmin";
$jasPass = "jasperadmin";
    $GLOBALS["loginJasper"] = $jasUser;
    $GLOBALS["passJasper"] = $jasPass;
//Connect to webServices

function geneAndGetDoc($webservices_uri, $jasUser, $jasPass, $format){
        include_once("generation.class.php");
        $jasperSoap = new jasperSoap(str_replace("repository", "", $webservices_uri), $jasUser, $jasPass);
        $jasperSoap->traiteReport($_SESSION['tableFile'][$_REQUEST['GenRapportURI']], $format);    
}

    if ($_REQUEST['GenRapportURI'] != -1 && $_REQUEST['subActionPDF'] == "Afficher" && $_REQUEST['action'] == "generate") {
        geneAndGetDoc($webservices_uri, $jasUser, $jasPass, "PDF");
//        $jasper_obj->genReport($remArray2, $_REQUEST['GenRapportURI']);
        exit;
    }
    elseif ($_REQUEST['GenRapportURI'] != -1 && preg_match("/Afficher/i", $_REQUEST['subAction']) && $_REQUEST['action'] == "generate") {
        geneAndGetDoc($webservices_uri, $jasUser, $jasPass, "HTML");
        //echo 'gen';
//        $jasper_obj->genHTMLReport($remArray2, $_REQUEST['GenRapportURI']);
        exit;
    }
    elseif ($_REQUEST['GenRapportURI'] != -1 && $_REQUEST['subActionXLS'] == "Afficher" && $_REQUEST['action'] == "generate") {
        geneAndGetDoc($webservices_uri, $jasUser, $jasPass, "XLS");
//        $jasper_obj->genXLSReport($remArray2, $_REQUEST['GenRapportURI']);
        exit;
    }
    else{
    $jasper_obj = new SynopsisJasper($db);

    $someInputControlUri = $conf->global->JASPER_REPO_PATH_GENERATED;
    $jrxmlControlUri = $conf->global->JASPER_REPO_PATH_REPORT;
    $GLOBALS["verbose"] = false;

    $requete = "";
    if ($conf->global->BABELJASPER_JASPER_USE_LDAP == 'true') {
        $requete = "SELECT login, pass
                  FROM " . MAIN_DB_PREFIX . "user
                 WHERE rowid = " . $user->id;
    } else {
        $requete = "SELECT jasperLogin as login, jasperPass  as pass" .
                "     FROM Babel_JasperBI_li_Users " .
                "    WHERE user_refid = " . $user->id . "";
    }
//$resql = $db->query($requete);
//$res = $db->fetch_object($resql);
//$GLOBALS["loginJasper"]= $res->login;
//$GLOBALS["passJasper"] = $res->pass;

    $folders = array();
    $remArray = array();
    $preg_filter = "/html$/";
    $remArray = $jasper_obj->parseFolder($someInputControlUri, $preg_filter, $remArray);


    $remArray1 = array();
    $preg_filter = "/pdf$/";
    $remArray1 = $jasper_obj->parseFolder($someInputControlUri, $preg_filter, $remArray1);

    $remArray2 = array();
    $preg_filter = "//";
    $remArray2 = $jasper_obj->parseFolder($jrxmlControlUri, $preg_filter, $remArray2);
    $_SESSION['tableFile'] = $remArray2;

    /* Modif drsi */

//$Directory = $jrxmlControlUri;
//$MyDirectory = opendir($Directory) or die('Erreur');
//while ($Entry = @readdir($MyDirectory)) {
//    if ($Entry != '.' && $Entry != '..') {
//        if (is_dir($Directory . '/' . $Entry)) {
//            //Dossier
//        } else {
//            $remArray2[] = $Entry;
//        }
//    }
//}
//closedir($MyDirectory);
///*Modif drsi louche /*/
    $remArrayZimbra = array();
    $zimbra_obj = false;
    if ($conf->global->MAIN_MODULE_ZIMBRA == 1) {
        $zimbra_obj = new Zimbra($db);

        $zimbra_obj->fetch_user($user->id);
        $GLOBALS["zimbraLogin"] = $zimbra_obj->zimbraLogin;
        $GLOBALS["zimbraPass"] = $zimbra_obj->zimbraPass;

        $GLOBALS["zimbraHost"] = $conf->global->ZIMBRA_HOST;
        $GLOBALS["zimbraProto"] = $conf->global->ZIMBRA_PROTO;
        $GLOBALS["zimbraDavProto"] = $conf->global->ZIMBRA_WEBDAVPROTO;

        if ($_REQUEST['action'] == "refreshZimbra") {
            require_once (DOL_DOCUMENT_ROOT . '/Synopsis_Jasper/WebDAV/Client.php');
            $zimbra_obj->RecursiveRefreshBriefcase($user->id);
        }
        $remArrayZimbra = $zimbra_obj->ParseBriefcase($user->id);
    }
//header
    llxHeader();


    print_fiche_titre($langs->trans("BI : Reporting"));

    print "<p>" . $langs->trans('DescBImodule') . "</p>";

    print "<br><hr>";

//List available graphes
    print '<table width="100%" class=""><tr><td nowrap></td></tr>';
    print "   <tr class='ui-widget-header ui-state-hover'>\n";
    print "       <td colspan=4 class=''><SPAN style='width: 100%;font-size:14pt; padding: 5pt;'>Interface Jasper BI</SPAN></td>\n";
    print "   </tr>\n";
    print '</table>';


    /*     * ************ Affichage ************** */

    print "<FORM method='POST' ACTION='" . $_SERVER['PHP_SELF'] . "?action=display'>";
    print '<table width="100%" class="" cellpadding=15>';
    print "   <tr class=''>\n";
    print "   <th class='ui-state-default ui-widget-header' colspan=1 style='width:15%;'>Rapport pr&eacute;-programm&eacute;</TH>\n";
    print "   <td class='ui-widget-content' colspan=1 style='width:150px; padding-left: 10pt;'>\n";

    print "<SELECT name='RapportURI'>";
    print " <OPTION value ='-1'>Selection-></OPTION>";
    print "<OPTGROUP label='Afficher' >";
    foreach ($remArray as $key => $val) {
        if ($_REQUEST['RapportURI'] && $_REQUEST['RapportURI'] == "R" . $key) {
            print "<OPTION SELECTED value='R" . $key . "'>" . utf8_decode(basename($val, ".html")) . "</OPTION>";
        } else {
            print "<OPTION value='R" . $key . "'>" . utf8_decode(basename($val, ".html")) . "</OPTION>";
        }
    }
    print "</OPTGROUP>";
    print "<OPTGROUP label='T&eacute;l&eacute;charger en PDF' >";

//print "<SELECT name='DlRapportURI'>";
    foreach ($remArray1 as $key => $val) {
        if ($_REQUEST['DlRapportURI'] && $_REQUEST['DlRapportURI'] == "D" . $key) {
            print "<OPTION SELECTED value='D" . $key . "'>" . utf8_decode(basename($val, ".pdf")) . "</OPTION>";
        } else {
            print "<OPTION value='D" . $key . "'>" . utf8_decode(basename($val, ".pdf")) . "</OPTION>";
        }
    }
    print "</OPTGROUP>";
    print "</SELECT>";

    print "</TD>";
    print "<TD class='ui-widget-content' style=' padding-left: 20pt;'><input class='butAction' type='submit' name='subAction' value='Soumettre'/></TD>";
    print "   </tr>\n";
    print '</table>';
    print "</FORM>";


    /*     * ********* GENERATE ********** */

    if ($user->rights->SynopsisJasper->SynopsisJasper->Generate == 1) {
        print "<FORM method='POST' ACTION='" . $_SERVER['PHP_SELF'] . "?action=generate'>";
        print '<table width="100%" class="" cellpadding=15>';
        print "   <tr>\n";
        print "   <th class='ui-state-default ui-widget-header' colspan=1 style='width:15%;'>Rapport OnDemand</TH>\n";
        print "   <td class='ui-widget-content' colspan=1 style='width:150px; padding-left: 10pt;'>\n";
        //print "<TABLE><TR><TH>Nom du rapport<TD>";
        print "<SELECT name='GenRapportURI'>";
        print " <OPTION value ='-1'>Selection-></OPTION>";
//    print " <OPTGROUP label ='Selectionner le rapport : '>";
        foreach ($remArray2 as $key => $val) {
            if ($_REQUEST['GenRapportURI'] && $_REQUEST['GenRapportURI'] == $key) {
                print "<OPTION SELECTED value='" . $key . "'>" . utf8_decode(basename($val, "")) . "</OPTION>";
            } else {
                print "<OPTION value='" . $key . "'>" . utf8_decode(basename($val, "")) . "</OPTION>";
            }
        }
//    print "</OPTGROUP>";
        print "</SELECT>";
        print "</TD>";
        print "<TD class='ui-widget-content' style=' padding-left: 20pt;'>
            <button class='butAction' style='padding-left: 5px;'  name='subActionPDF' value='Afficher'><span style='width: 16px;'><img src='" . DOL_URL_ROOT . "/theme/common/mime/pdf.png'></span>   T&eacute;l&eacute;charger en PDF</button>
            <button class='butAction' style='padding-left: 5px;'  name='subActionXLS' value='Afficher'><span style='width: 16px;'><img src='" . DOL_URL_ROOT . "/theme/common/mime/xls.png'></span>  T&eacute;l&eacute;charger en XLS</button>
            <br/><input class='butAction' type='submit'   name='subAction' value='Afficher'/>
           </TD>";
        print "   </tr>\n";
        print '</table>';
        print "</FORM>";
    }


    /*     * ****** PUSH ******* */


    if ($conf->global->MAIN_MODULE_SYNOPSISZIMBRA && ($_REQUEST['GenRapportURI'] >= 0 || $_REQUEST['DlRapportURI'] >= 0 ) && $user->rights->SynopsisZimbra->Push == 1 && $user->rights->SynopsisJasper->SynopsisJasper->PushZimbra == 1) {
        print "<FORM method='POST' ACTION='" . $_SERVER['PHP_SELF'] . "?action=PushToZimbra'>";
        print '<table width="100%" class="">';
        print "   <tr class=''>\n";
        print "   <th class='ui-widget-header ui-state-default' colspan=1 style='width:15%; padding-left: 10pt;'>Copier le rapport dans Zimbra</TH>\n";
        print "   <td colspan=1 class='ui-widget-content' style='width:166px; padding-left: 10pt;'>";
        print "     O&ugrave;?  <SELECT name='ZimbraBC' > ";
        print "      <OPTION value ='-1'>Selection-></OPTION>";
        foreach ($remArrayZimbra as $keyZ => $valZ) {
            if ($_REQUEST["ZimbraBC"] && $_REQUEST["ZimbraBC"] == "z" . $keyZ) {
                print "            <OPTION SELECTED value=z" . $keyZ . ">" . utf8_decode($valZ) . "</OPTION>";
            } else {
                print "            <OPTION value=z" . $keyZ . ">" . utf8_decode($valZ) . "</OPTION>";
            }
        }
        print "       </SELECT>";
        print '<a href="' . $_SERVER["PHP_SELF"] . '?action=refreshZimbra">' . img_picto($langs->trans("Refresh"), 'refresh') . '</a>';
        print " <BR> Quoi? <SELECT name='RapportURI'>";
        print "      <OPTION value ='-1'>Selection-></OPTION>";
        print "      <OPTGROUP label=\"Rapports T&eacute;l&eacute;chargeable\">";
        foreach ($remArray1 as $key => $val) {
            if ($_REQUEST['RapportURI'] && $_REQUEST['RapportURI'] == "Dl_" . $key) {
                print "<OPTION SELECTED value='Dl_" . $key . "'>" . utf8_decode(basename($val, ".pdf")) . "</OPTION>";
            } else {
                print "<OPTION value='Dl_" . $key . "'>" . utf8_decode(basename($val, ".pdf")) . "</OPTION>";
            }
        }
        print "      </OPTGROUP>";
        print "      <OPTGROUP label=\"Rapports On Demand\">";
        foreach ($remArray2 as $key => $val) {
            if ($_REQUEST['RapportURI'] && $_REQUEST['RapportURI'] == "Gen_" . $key) {
                print "<OPTION SELECTED value='Gen_" . $key . "'>" . utf8_decode(basename($val, "")) . "</OPTION>";
            } else {
                print "<OPTION value='Gen_" . $key . "'>" . utf8_decode(basename($val, "")) . "</OPTION>";
            }
        }
        print "</OPTGROUP>";

        print "</SELECT></TD>";
        print "<TD colspan=1 class='ui-widget-content' style=' padding-left: 20pt;'><input class='butAction' type='submit' name='subAction' value='Copier'/>";
        print "</TD>";
        print "</TR>";
        print "</TABLE>";
        print "</FORM>";
    }
    if ($conf->global->MAIN_MODULE_ECM && ($_REQUEST['GenRapportURI'] >= 0 || $_REQUEST['DlRapportURI'] >= 0 ) && $user->rights->SynopsisJasper->SynopsisJasper->PushECM == 1) {
        print "<FORM method='POST' ACTION='" . $_SERVER['PHP_SELF'] . "?action=PushToECM'>";
        print '<table width="100%" class="">';
        print "   <tr class=''>\n";
        print "   <th class='ui-widget-header ui-state-default' colspan=1 style='width:15%; padding-left: 10pt;'>Copier le rapport dans l'ECM</th>\n";
        print "   <td colspan=1 class='ui-widget-content' style='width:166px; padding-left: 10pt;'><SELECT name='RapportURI'>";
        print " <OPTION value ='-1'>Selection-></OPTION>";
        print "      <OPTGROUP label=\"Rapports T&eacute;l&eacute;chargeable\">";

        foreach ($remArray1 as $key => $val) {
            if ($_REQUEST['RapportURI'] && $_REQUEST['RapportURI'] == "Dl_" . $key) {
                print "<OPTION SELECTED value='Dl_" . $key . "'>" . utf8_decode(basename($val, ".pdf")) . "</OPTION>";
            } else {
                print "<OPTION value='Dl_" . $key . "'>" . utf8_decode(basename($val, ".pdf")) . "</OPTION>";
            }
        }
        print "      </OPTGROUP>";
        print "      <OPTGROUP label=\"Rapports on Demand\">";
        foreach ($remArray2 as $key => $val) {
            if ($_REQUEST['RapportURI'] && $_REQUEST['RapportURI'] == "Gen_" . $key) {
                print "<OPTION SELECTED value='Gen_" . $key . "'>" . utf8_decode(basename($val, "")) . "</OPTION>";
            } else {
                print "<OPTION value='Gen_" . $key . "'>" . utf8_decode(basename($val, "")) . "</OPTION>";
            }
        }
        print "</OPTGROUP>";

        print "</SELECT></TD>";
        print "<TD class='ui-widget-content' style=' padding-left: 20pt;'><input class='butAction' type='submit' name='subAction' value='Copier'/></TD>";
        print "</TR>";
        print "</TABLE>";
        print "</FORM>";
    }
    if ($_REQUEST['RapportURI'] && $_REQUEST['RapportURI'] != -1 && $_REQUEST['action'] == "display" && preg_match("/^D/", $_REQUEST['RapportURI'])) {
        $jasper_obj->dlReport($remArray1, preg_replace("/^D/", "", $_REQUEST['RapportURI']));
        exit;
    }
    if ($_REQUEST['RapportURI'] && $_REQUEST['RapportURI'] != -1 && $_REQUEST['action'] == "display" && preg_match('/^R/', $_REQUEST['RapportURI'])) {
        //TODO : vider la 1ere lettre
        $jasper_obj->saveReport($remArray, preg_replace('/^R/', "", $_REQUEST['RapportURI']));
        exit;
    }

    if ($_REQUEST['action'] == "PushToECM") {
        //echo $_REQUEST['RapportURI'];
        $arrSub = array();
        $id_rapport = false;
        $pdf_content = false;
        if (preg_match("/^Dl_([\w]*)/", $_REQUEST['RapportURI'], $arrSub)) {
            //print $arrSub[1];
            $id_rapport = $arrSub[1];
        } else if (preg_match("/^Gen_([\w]*)/", $_REQUEST['RapportURI'], $arrSub)) {
            $id_rapport = $arrSub[1];
        }
        // Load ecm object
        $ecmdir = new ECMDirectory($db);
        $ecmdir->get_full_arbo();
        $ecmId = 0;
        foreach ($ecmdir->cats as $key => $val) {
            if ($ecmdir->cats[$key]["label"] == "BI") {
                $ecmId = $ecmdir->cats[$key]["id"];
            }
        }
        $ecmdir->id = $ecmId;
        $relativepath = $ecmdir->getRelativePath(1);
        $upload_dir = $conf->ecm->dir_output . '/' . $relativepath;
        //On Génère le rapport et on le Stock dans l'ECM
        $flag_continue = true;
        $strictFileName = utf8_decode(basename($remArray2[$id_rapport])) . ".pdf";
        if (is_readable($upload_dir)) {
            $ECMfilename = $upload_dir . "" . utf8_decode(basename($remArray2[$id_rapport])) . ".pdf";
            if (is_file($ECMfilename) && $_REQUEST['confirmEcrase'] == 1) {
                $flag_continue = true;
            } else if (is_file($ECMfilename)) {
                if ("x" . $_REQUEST['confirmEcrase'] == "x") {
                    //Print Confirm Screen => rerun
                    print "Un fichier de l'ECM va etre ecraser OK ? ";
                    print "<FORM method='POST' action='" . $_SERVER['PHP_SELF'] . "?action=PushToECM'";
                    print "<SELECT name='confirmEcrase'>";
                    print "<OPTION value='-1'> non</OPTION>";
                    print "<OPTION value='1'> oui</OPTION>";
                    print "</SELECT>";
                    print "<input type='hidden' name='RapportURI' value='" . $_REQUEST['RapportURI'] . "' />";
                    print "<input type='submit' name='submit' value='submit' />";
                    print "</FORM>";
                    $flag_continue = false;
                } else {
                    //header("Location : ".$_SERVER['PHP_SELF']);
                    print "Return to begin";
                    $flag_continue = false;
                }
            }
        } else {
            echo "Err : " . $upload_dir . " n'est pas accessible en lecture";
        }


        if (preg_match("/^Dl_([\w]*)/", $_REQUEST['RapportURI'], $arrSub) && $flag_continue) {
            //print $arrSub[1];
            //On Télécharge le rapport et on le Stock dans l'ECM
            $pdf_content = $jasper_obj->dlReport($remArray1, $arrSub[1], true);
            $id_rapport = $arrSub[1];
        } else if (preg_match("/^Gen_([\w]*)/", $_REQUEST['RapportURI'], $arrSub) && $flag_continue) {
            $pdf_content = $jasper_obj->genReport($remArray2, $arrSub[1], true);
            $id_rapport = $arrSub[1];
        }
        if ($pdf_content && $id_rapport && $flag_continue) {
            // Load ecm object
            $ecmdir = new ECMDirectory($db);
            $ecmdir->get_full_arbo();
            $ecmId = 0;
            foreach ($ecmdir->cats as $key => $val) {
                if ($ecmdir->cats[$key]["label"] == "BI") {
                    $ecmId = $ecmdir->cats[$key]["id"];
                }
            }
            $ecmdir->id = $ecmId;
            $relativepath = $ecmdir->getRelativePath(1);
            $upload_dir = $conf->ecm->dir_output . '/' . $relativepath;
            //On Génère le rapport et on le Stock dans l'ECM
            $flag_add = true;
            if (is_writable($upload_dir)) {
                $ECMfilename = $upload_dir . "" . utf8_decode(basename($remArray2[$id_rapport])) . ".pdf";
                if (is_file($ECMfilename)) {
                    $flag_add = false;
                }
                $fh = fopen($ECMfilename, "w");
                //echo $ECMfilename;
                fwrite($fh, $pdf_content);
                fclose($fh);
                //ajoute le document dans l'ecm
                $desc = "Rapport BI : " . utf8_decode(basename($remArray2[$id_rapport]));
                $keyword = "Rapport BI auto ";
//            global $user;
                $ecmdir->create_manual($ecmId, utf8_decode(basename($remArray2[$id_rapport])) . ".pdf", $desc, $keyword, $user);
            } else {
                echo "Err : " . $upload_dir . " n'est pas accessible en &eacute;criture";
            }

            if ($flag_add)
                $result = $ecmdir->changeNbOfFiles('+');
        }
    }

    if ($_REQUEST['action'] == "PushToZimbra" && $user->rights->SynopsisZimbra->Push == 1 && $user->rights->SynopsisJasper->SynopsisJasper->PushZimbra == 1) {
        $arrSub = array();
        $id_rapport = false;
        $pdf_content = false;
        $rapport_name = false;
        $to_webDav_BriefCase = $remArrayZimbra[preg_replace("/^z/", "", $_REQUEST["ZimbraBC"])];

        if (preg_match("/^Dl_([\w]*)/", $_REQUEST['RapportURI'], $arrSub)) {
            //On Télécharge le rapport et on le Stock dans l'ECM
            $pdf_content = $jasper_obj->dlReport($remArray1, $arrSub[1], true);
            $id_rapport = $arrSub[1];
            $rapport_name = $remArray1[$arrSub[1]];
        } else if (preg_match("/^Gen_([\w]*)/", $_REQUEST['RapportURI'], $arrSub)) {
            $pdf_content = $jasper_obj->genReport($remArray2, $arrSub[1], true);
            $id_rapport = $arrSub[1];
            $rapport_name = $remArray2[$arrSub[1]];
        }
        if ($pdf_content && $id_rapport && $_REQUEST["ZimbraBC"] && basename($rapport_name)) {
            $url = $GLOBALS['zimbraDavProto'] . "://" . $GLOBALS['zimbraHost'] . "/dav/" . $GLOBALS['zimbraLogin'] . "/Briefcase";

            $davPath = "" . $to_webDav_BriefCase . "/" . basename($rapport_name) . ".pdf";
            $url .= $davPath;
            $zimbra_obj->pushToZimbraBriefcase($url, $pdf_content, $user->id);
        }
    }
}

//Get List of static reports


llxFooter('$Date: 2008/06/19 08:50:59 $ - $Revision: 1.60 $');
?>
