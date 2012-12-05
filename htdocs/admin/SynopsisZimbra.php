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

/**
        \file       htdocs/admin/webcalendar.php
        \ingroup    webcalendar
        \brief      Page de configuration du module webcalendar
        \version    $Revision: 1.23 $
*/

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
#require_once(DOL_DOCUMENT_ROOT.'/lib/webcal.class.php');

        //require_once('Var_Dump.php');
        //Var_Dump::displayInit(array('display_mode' => 'HTML4_Text'), array('mode' => 'normal','offset' => 4));

if (!$user->admin)
    accessforbidden();

$langs->load("admin");
$langs->load("other");
$langs->load("synopsisGene@Synopsis_Tools");

$def = array();
$actionsave=$_POST["save"];


//debug//


//"action"]=> string(15) "populateZimbra" ["sub1"]=> string(7) "Correct"
// Positionne la variable pour le test d'affichage de l'icone
if ($actionsave)
{
    $i=0;

    $db->begin();

    $i+=dolibarr_set_const($db,'ZIMBRA_HOST',trim($_POST["ZIMBRA_HOST"]),'',0);
    $i+=dolibarr_set_const($db,'ZIMBRA_DOMAIN',trim($_POST["ZIMBRA_DOMAIN"]),'',0);
    $i+=dolibarr_set_const($db,'ZIMBRA_PROTO',trim($_POST["ZIMBRA_PROTO"]),'',0);
    $i+=dolibarr_set_const($db,'ZIMBRA_PREAUTH',trim($_POST["ZIMBRA_PREAUTH"]),'',0);
    $i+=dolibarr_set_const($db,'ZIMBRA_WEBDAVPROTO',trim($_POST["ZIMBRA_WEBDAVPROTO"]),'',0);
    $i+=dolibarr_set_const($db,'ZIMBRA_ZIMBRA_USE_LDAP',trim($_POST["ZIMBRA_ZIMBRA_USE_LDAP"]?"true":"false"),'',0);
    $i+=dolibarr_set_const($db,'ZIMBRA_ADMINUSER',trim($_POST["ZIMBRA_ADMINUSER"]),'',0);
    $i+=dolibarr_set_const($db,'ZIMBRA_ADMINPASS',trim($_POST["ZIMBRA_ADMINPASS"]),'',0);
    $i+=dolibarr_set_const($db,'ZIMBRA_ADMINID',trim($_POST["ZIMBRA_ADMINID"]),'',0);

    if ($i >= 7)
    {
        $db->commit();
        header("Location: SynopsisZimbra.php");
        exit;
    }
    else
    {
        $db->rollback();
        $mesg = "<font class=\"ok\">".$langs->trans("ZimbraSetupSaved")."</font>";
    }
}


/**
 * Affichage du formulaire de saisie
 */


//Var_Dump::Display($_REQUEST);

if ($_REQUEST['action'] == "populateZimbra"  && $_REQUEST['sub1'] . "x" != "x")
{
    ini_set('max_execution_time', 3600); //1h en seconde
    ini_set('memory_limit', "256M");

    if ($conf->global->ZIMBRA_PREAUTH ."x" != 'x' && $conf->global->ZIMBRA_ADMINUSER ."x" != "x")
    {
        require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
        $zim = new Zimbra($conf->global->ZIMBRA_ADMINUSER);
        $ret=$zim->connect();
        $zim->db=$db;
        $zim->debug=true;
//        require_once('Var_Dump.php');
//        var_dump::Display($zim);
        if ($zim)
        {
            //remove main folder

            $ZimMainCalStatus = $_REQUEST['ZimMainCalStatus'];
            $SQLStatus = $_REQUEST['SQLStatus'];
            $ZimCalSocStatus = $_REQUEST['ZimCalSocStatus'];
            $ZimUsrSocStatus = $_REQUEST['ZimUsrSocStatus'];
            $arrName['appointment']="Calendriers - GLE";
            $arrName['contact']="Contacts - GLE";

            $arrAlpha=array(0=>'abc',1=>'def',2=>'ghi',3=>'jkl',4=>'mno', 5=>'pqrs', 6=>'tuv', 7=>'wxyz', 8=>'autres');

            if (((!$ZimMainCalStatus || $ZimMainCalStatus == "ko") || (!$SQLStatus || $SQLStatus == "ko"))||$_REQUEST['sub1']=='Reinit')
            {
                //On cree dans zimbra, on met dans SQL
                //On cree le main
                //Efface les folders Zimbra
//                $ret1=$zim->parseRecursiveAptFolder($ret);
                //Get Main Folder Id:
                $zim->parseRecursiveAptFolder($ret);
                $arrFolderDesc = $zim->appointmentFolderLevel;
                foreach($arrFolderDesc as $key=>$val)
                {
                    if (preg_match('/Calendriers - GLE/',$val['name']) && $val['parent']==1)
                    {
                        $remZimId = $val['id'];
                        $zim->BabelDeleteFolder($remZimId);
                        continue;
                    }
                }

                $zim->parseRecursiveContactFolder($ret);
                //Get Main Folder Id:
                $arrFolderDesc = $zim->contactFolderLevel;
                foreach($arrFolderDesc as $key=>$val)
                {
                    if (preg_match('/Contacts - GLE/',$val['name']) && $val['parent']==1)
                    {
                        $remZimId = $val['id'];
                        $zim->BabelDeleteFolder($remZimId);
                        continue;
                    }
                }


                //racine main Calendar
                $createArray = array();
                $createArray=array('view' => 'appointment',
                                   "name" => $arrName['appointment'] ,
                                   "where" => 1);

                $elemFold['appointment'] = $zim->BabelCreateFolder($createArray);
                $apptFoldId = $elemFold['appointment']['id'];
                //=> ajoute les sous dossiers Sociétés et Utilisateurs
                $createArray = array();
                $createArray=array('view' => 'appointment',
                                   "name" => htmlspecialchars("Sociétés") ,
                                   "where" => $apptFoldId);
                $arr2ndFold = array();
                $arr2ndFold["appointment"][0] = $zim->BabelCreateFolder($createArray);
                $createArray = array();
                $createArray=array('view' => 'appointment',
                                   "name" => "Utilisateurs" ,
                                   "where" => $apptFoldId);
                $arr2ndFold["appointment"][1] = $zim->BabelCreateFolder($createArray);


                //=> ajoute les sous dossiers Ressources
                $createArray = array();
                $createArray=array('view' => 'appointment',
                                   "name" => htmlspecialchars("Ressources") ,
                                   "where" => $apptFoldId);
                $arrResFold = array();
                $arrResFold["appointment"][0] = $zim->BabelCreateFolder($createArray);



                //=> ajoute les sous dossiers Affaires
                $createArray = array();
                $createArray=array('view' => 'appointment',
                                   "name" => htmlspecialchars("Affaires") ,
                                   "where" => $apptFoldId);
                $arrAffFold = array();
                $arrAffFold["appointment"][0] = $zim->BabelCreateFolder($createArray);


                //racine main Contact
                $createArray = array();
                $createArray=array('view' => 'contact',
                                   "name" => $arrName['contact'] ,
                                   "where" => 1);
                $elemFold['contact'] = $zim->BabelCreateFolder($createArray);
                $contFoldId = $elemFold['contact']['id'];
                $createArray = array();
                $createArray=array('view' => 'contact',
                                   "name" => "Societes" ,
                                   "where" => $contFoldId);
                $arr2ndFold["contact"][0] = $zim->BabelCreateFolder($createArray);
                $createArray = array();
                $createArray=array('view' => 'contact',
                                   "name" => "Utilisateurs" ,
                                   "where" => $contFoldId);
                $arr2ndFold["contact"][1] = $zim->BabelCreateFolder($createArray);
                //Dans contact :> ajoute un repertoire Personnes dans contacts
                $createArray=array('view' => 'contact',
                                   "name" => "Personnes" ,
                                   "where" => $contFoldId);
                $arr3ndFold=array();
                $arr3ndFold["contact"][0] = $zim->BabelCreateFolder($createArray);


                //sous repertoire de societe et utilisateur
                foreach($arrAlpha as $keyA=>$valA)
                {
//                    $zim->debug=true;
                    $createArray=array('view' => 'appointment',
                                       "name" => $valA ,
                                       "where" => $arr2ndFold["appointment"][0]['id']);
                    $arr3ndFold["appointment"][$valA][0] = $zim->BabelCreateFolder($createArray);
                    $createArray=array('view' => 'appointment',
                                       "name" => $valA ,
                                       "where" => $arr2ndFold["appointment"][1]['id']);
                    $arr3ndFold["appointment"][$valA][1] = $zim->BabelCreateFolder($createArray);
                }
                //build SQL
                //$type="appointment"; // || contact
                $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_folder
                                  WHERE skeleton_part > 0";
                $db->query($requete);
                $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger";
                $db->query($requete);

                $zim->BabelInsertTriggerFolder($arrAffFold["appointment"][0]['id'],'Affaires',$arrAffFold["appointment"][0]['parent'],'appointment',1);
                $zim->BabelInsertTriggerFolder($arrResFold["appointment"][0]['id'],'Ressources',$arrResFold["appointment"][0]['parent'],'appointment',1);
                foreach (array('appointment','contact') as $key)
                {
//                    print "<H1>".$key."</H1><BR>";
                    $zim->BabelInsertTriggerFolder($elemFold[$key]['id'],$elemFold[$key]['name'],1,$key,1);
                      //Store subfolder
                    $zim->BabelInsertTriggerFolder($arr2ndFold[$key][0]['id'],'Sociétés',$elemFold[$key]['id'],$key,1);
                    $zim->BabelInsertTriggerFolder($arr2ndFold[$key][1]['id'],'Utilisateurs',$elemFold[$key]['id'],$key,1);
                    if ($key == 'appointment')
                    {
                        $zim->BabelInsertTriggerFolder($arr2ndFold[$key][1]['id'],'Ressources',$elemFold[$key]['id'],$key,1);
                    }
                    //sous repertoire de societe et utilisateur SQL

//                    print '<br>arr3ndFold: '.$key.'<br>';
//                    require_once('Var_Dump.php');
//                    Var_Dump::displayInit(array('display_mode' => 'HTML4_Table'), array('mode' => 'normal','offset' => 4));
//                    var_dump::Display($arr3ndFold);
//                    print '<br>';

                    foreach($arr3ndFold[$key] as $keyA1 => $valA1)
                    {
                        if (preg_match('/[0-9]/',$keyA1))
                        {
                            foreach(array(0) as $key2)//Société 0 => utilisateur 1
                            {
                                $zim->BabelInsertTriggerFolder($valA1[$key2]['id'],$valA1[$key2]['name'],$valA1[$key2]['parent'],$key,1);
                            }
                        } else {
                            foreach(array(0,1) as $key2)//Société 0 => utilisateur 1
                            {
                                $zim->BabelInsertTriggerFolder($valA1[$key2]['id'],$valA1[$key2]['name'],$valA1[$key2]['parent'],$key,1);
                            }
                        }
                    }
//trouver tous les utlisateurs qui ont acces au calendrier
//perm//
                }
                //ajoute le repertoire Personne dans contacts:
                $zim->BabelInsertTriggerFolder($arr3ndFold["contact"][0]['id'],'Personnes',$arr3ndFold["contact"][0]['parent'],$key,1);
            }
        }
    }
    header('Location: ?msg=Reinit%20OK');
}

if ($_REQUEST['action'] == "populateZimbra"  && $_REQUEST['sub2'] . "x" != "x")
{
    ini_set('max_execution_time', 3600);
    ini_set('memory_limit', "256M");
    if ($conf->global->ZIMBRA_PREAUTH ."x" != 'x' && $conf->global->ZIMBRA_ADMINUSER ."x" != "x")
    {
        require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
        $zim = new Zimbra($conf->global->ZIMBRA_ADMINUSER);
        $zim->connect();
        $zim ->dolibarr_main_url_root = $dolibarr_main_url_root;
        $zim->db=$db;
//        var_dump($zim);
        if ($zim)
        {
            $zim->debug=false;
            $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_folder
                              WHERE folder_type_refid = (SELECT id FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type WHERE val='appointment')
                                AND skeleton_part='2'";
            $db->query($requete);
            $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger";
            $db->query($requete);
            print "Synchro soci&eacute;t&eacute;s : ";
            $zim->Synopsis_Zimbra_GetSoc();
            print "<BR>Synchro fournisseurs : ";
            $zim->Synopsis_Zimbra_GetFourn();
            print "done<br>";
            //on efface la base locale des propales
//            print "Synchro Propal";
//            reinit_Propal($zim,$db);
        }
    }
    header('Location: ?msg=societe%20OK');
}


if ($_REQUEST['action'] == "populateZimbra"  && $_REQUEST['subPropal'] . "x" != "x")
{
    ini_set('max_execution_time', 3600);
    ini_set('memory_limit', "256M");
    if ($conf->global->ZIMBRA_PREAUTH ."x" != 'x' && $conf->global->ZIMBRA_ADMINUSER ."x" != "x")
    {
        require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
        $zim = new Zimbra($conf->global->ZIMBRA_ADMINUSER);
        $zim->connect();
        $zim ->dolibarr_main_url_root = $dolibarr_main_url_root;
        $zim->db=$db;
        if ($zim)
        {
            print "Synchro Propal";
            reinit_Propal($zim,$db);
        }
    }
    header('Location: ?action=none&msg=Synchro%20Propale');
}

if ($_REQUEST['action'] == "populateZimbra"  && $_REQUEST['subCommande'] . "x" != "x")
{
    ini_set('max_execution_time', 3600);
    ini_set('memory_limit', "256M");
    if ($conf->global->ZIMBRA_PREAUTH ."x" != 'x' && $conf->global->ZIMBRA_ADMINUSER ."x" != "x")
    {
        require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
        $zim = new Zimbra($conf->global->ZIMBRA_ADMINUSER);
        $zim->connect();
        $zim ->dolibarr_main_url_root = $dolibarr_main_url_root;
        $zim->db=$db;
        if ($zim)
        {
            print "Synchro Commande";
            reinit_Commande($zim,$db);
        }
    }
    header('Location: ?action=none&msg=Synchro%20Commande');
}

if ($_REQUEST['action'] == "populateZimbra"  && $_REQUEST['subFacture'] . "x" != "x")
{
    ini_set('max_execution_time', 3600);
    ini_set('memory_limit', "256M");
    if ($conf->global->ZIMBRA_PREAUTH ."x" != 'x' && $conf->global->ZIMBRA_ADMINUSER ."x" != "x")
    {
        require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
        $zim = new Zimbra($conf->global->ZIMBRA_ADMINUSER);
        $zim->connect();
        $zim ->dolibarr_main_url_root = $dolibarr_main_url_root;
        $zim->db=$db;
        if ($zim)
        {
            print "Synchro Facture";
            reinit_Facture($zim,$db);
        }
    }
    header('Location: ?action=none&msg=Synchro%20Facture');
}
if ($_REQUEST['action'] == "populateZimbra"  && $_REQUEST['subIntervention'] . "x" != "x")
{
    ini_set('max_execution_time', 3600);
    ini_set('memory_limit', "256M");
    if ($conf->global->ZIMBRA_PREAUTH ."x" != 'x' && $conf->global->ZIMBRA_ADMINUSER ."x" != "x")
    {
        require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
        $zim = new Zimbra($conf->global->ZIMBRA_ADMINUSER);
        $zim->connect();
        $zim ->dolibarr_main_url_root = $dolibarr_main_url_root;
        $zim->db=$db;
        if ($zim)
        {
            print "Synchro Interventions";
            reinit_Intervention($zim,$db);
        }
    }
    header('Location: ?action=none&msg=Synchro%20Intervention');
}
if ($_REQUEST['action'] == "populateZimbra"  && $_REQUEST['subLivraison'] . "x" != "x")
{
    ini_set('max_execution_time', 3600);
    ini_set('memory_limit', "256M");
    if ($conf->global->ZIMBRA_PREAUTH ."x" != 'x' && $conf->global->ZIMBRA_ADMINUSER ."x" != "x")
    {
        require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
        $zim = new Zimbra($conf->global->ZIMBRA_ADMINUSER);
        $zim->connect();
        $zim ->dolibarr_main_url_root = $dolibarr_main_url_root;
        $zim->db=$db;
        if ($zim)
        {
            print "Synchro Livraisons";
            reinit_Livraison($zim,$db);
        }
    }
    header('Location: ?action=none&msg=Synchro%20Livraison');
}
if ($_REQUEST['action'] == "populateZimbra"  && $_REQUEST['subExpedition'] . "x" != "x")
{
    ini_set('max_execution_time', 3600);
    ini_set('memory_limit', "256M");
    if ($conf->global->ZIMBRA_PREAUTH ."x" != 'x' && $conf->global->ZIMBRA_ADMINUSER ."x" != "x")
    {
        require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
        $zim = new Zimbra($conf->global->ZIMBRA_ADMINUSER);
        $zim->connect();
        $zim ->dolibarr_main_url_root = $dolibarr_main_url_root;
        $zim->db=$db;
        if ($zim)
        {
            print "Synchro Expeditions";
            reinit_Expedition($zim,$db);
        }
    }
    header('Location: ?action=none&msg=Synchro%20Expedition');
}
//var_dump($_REQUEST);
if ($_REQUEST['action'] == "populateZimbra"  && $_REQUEST['subActionComm'] . "x" != "x")
{
    ini_set('max_execution_time', 3600);
    ini_set('memory_limit', "256M");
    if ($conf->global->ZIMBRA_PREAUTH ."x" != 'x' && $conf->global->ZIMBRA_ADMINUSER ."x" != "x")
    {
        require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
        $zim = new Zimbra($conf->global->ZIMBRA_ADMINUSER);
        $zim->connect();
        $zim ->dolibarr_main_url_root = $dolibarr_main_url_root;
        $zim->db=$db;
        if ($zim)
        {
            print "Synchro Action Comm";
            reinit_ActionCom($zim,$db);
        }
    }
    header('Location: ?action=none&msg=Synchro%20ActionCo');
}

if ($_REQUEST['action'] == "populateZimbra"  && $_REQUEST['subContact'] . "x" != "x")
{
    ini_set('max_execution_time', 3600);
    ini_set('memory_limit', "256M");
    if ($conf->global->ZIMBRA_PREAUTH ."x" != 'x' && $conf->global->ZIMBRA_ADMINUSER ."x" != "x")
    {
        require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
        $zim = new Zimbra($conf->global->ZIMBRA_ADMINUSER);
        $zim->connect();
        $zim ->dolibarr_main_url_root = $dolibarr_main_url_root;
        $zim->db=$db;
        if ($zim)
        {
            print "Synchro contacts";
            reinit_Contact($zim,$db);
        }
    }
    header('Location: ?action=none&msg=Synchro%20Contact');
}




if ($_REQUEST['action'] == "populateZimbra"  && $_REQUEST['subCalUtilisateur'] . "x" != "x")
{
    ini_set('max_execution_time', 3600);
    ini_set('memory_limit', "256M");
    if ($conf->global->ZIMBRA_PREAUTH ."x" != 'x' && $conf->global->ZIMBRA_ADMINUSER ."x" != "x")
    {
        require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
        $zim = new Zimbra($conf->global->ZIMBRA_ADMINUSER);
        $zim->connect();
        $zim ->dolibarr_main_url_root = $dolibarr_main_url_root;
        $zim->db=$db;
        if ($zim)
        {
            print "Synchro Users";
            reinit_CalUtilisateur($zim,$db);
        }
    }
    header('Location: ?action=none&msg=Synchro%20UserCal');
}


if ($_REQUEST['action'] == "populateZimbra"  && $_REQUEST['subPaiement'] . "x" != "x")
{
    ini_set('max_execution_time', 3600);
    ini_set('memory_limit', "256M");
    if ($conf->global->ZIMBRA_PREAUTH ."x" != 'x' && $conf->global->ZIMBRA_ADMINUSER ."x" != "x")
    {
        require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
        $zim = new Zimbra($conf->global->ZIMBRA_ADMINUSER);
        $zim->connect();
        $zim ->dolibarr_main_url_root = $dolibarr_main_url_root;
        $zim->db=$db;
        if ($zim)
        {
            print "Synchro paiements";
            reinit_Paiement($zim,$db);
        }
    }
    header('Location: ?action=none&msg=Synchro%20Paiement');
}

if ($_REQUEST['action'] == "populateZimbra"  && $_REQUEST['subPaiementFourn'] . "x" != "x")
{
    ini_set('max_execution_time', 3600);
    ini_set('memory_limit', "256M");
    if ($conf->global->ZIMBRA_PREAUTH ."x" != 'x' && $conf->global->ZIMBRA_ADMINUSER ."x" != "x")
    {
        require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
        $zim = new Zimbra($conf->global->ZIMBRA_ADMINUSER);
        $zim->connect();
        $zim ->dolibarr_main_url_root = $dolibarr_main_url_root;
        $zim->db=$db;
        if ($zim)
        {
            print "Synchro paiements fournisseurs";
            reinit_PaiementFourn($zim,$db);
        }
    }
    header('Location: ?action=none&msg=Synchro%20Paiement%20Fournisseur');
}

if ($_REQUEST['action'] == "populateZimbra"  && $_REQUEST['subCalRessources'] . "x" != "x")
{
    ini_set('max_execution_time', 3600);
    ini_set('memory_limit', "256M");
    if ($conf->global->ZIMBRA_PREAUTH ."x" != 'x' && $conf->global->ZIMBRA_ADMINUSER ."x" != "x")
    {
        require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
        $zim = new Zimbra($conf->global->ZIMBRA_ADMINUSER);
        $ret = $zim->connectAdmin($conf->global->ZIMBRA_ADMINUSER,$conf->global->ZIMBRA_ADMINPASS);
        $zim ->dolibarr_main_url_root = $dolibarr_main_url_root;
        $zim->db=$db;
        if ($zim)
        {
            print "Synchro Ressources";
            reinit_Ressources($zim,$db);
        }
    }
    header('Location: ?action=none&msg=Synchro%20Ressources');
}


function reinit_Paiement($zim,$db)
{
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger
                      WHERE type_event_refid = (SELECT id FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type WHERE val='appointment')
                        AND event_table_link='".MAIN_DB_PREFIX."propal'";
    $db->query($requete);
    $zim->Synopsis_Zimbra_GetPaiement();
}
function reinit_PaiementFourn($zim,$db)
{
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger
                      WHERE type_event_refid = (SELECT id FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type WHERE val='appointment')
                        AND event_table_link='".MAIN_DB_PREFIX."propal'";
    $db->query($requete);
    $zim->Synopsis_Zimbra_GetPaiementFourn();
}

function reinit_Propal($zim,$db)
{
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger
                      WHERE type_event_refid = (SELECT id FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type WHERE val='appointment')
                        AND event_table_link='".MAIN_DB_PREFIX."propal'";
    $db->query($requete);
    $zim->Synopsis_Zimbra_GetPropal();
}
function reinit_Commande($zim,$db)
{
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger
                      WHERE type_event_refid = (SELECT id FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type WHERE val='appointment')
                        AND event_table_link='".MAIN_DB_PREFIX."commande'";
    $db->query($requete);
    $zim->Synopsis_Zimbra_GetCommande();
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger
                      WHERE type_event_refid = (SELECT id FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type WHERE val='appointment')
                        AND event_table_link='".MAIN_DB_PREFIX."commande_fournisseur'";
    $db->query($requete);
    $zim->Synopsis_Zimbra_GetCommandeFourn();
}
function reinit_Facture($zim,$db)
{
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger
                      WHERE type_event_refid = (SELECT id FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type WHERE val='appointment')
                        AND event_table_link='".MAIN_DB_PREFIX."facture'";
    $db->query($requete);
    $zim->Synopsis_Zimbra_GetFacture();
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger
                      WHERE type_event_refid = (SELECT id FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type WHERE val='appointment')
                        AND event_table_link='".MAIN_DB_PREFIX."facture_fourn'";
    $db->query($requete);
    $zim->Synopsis_Zimbra_GetFactureFounisseur();
}

function reinit_Intervention($zim,$db)
{
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger
                      WHERE type_event_refid = (SELECT id FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type WHERE val='appointment')
                        AND event_table_link='".MAIN_DB_PREFIX."fichinter'";
    $db->query($requete);
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger
                      WHERE type_event_refid = (SELECT id FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type WHERE val='appointment')
                        AND event_table_link='".MAIN_DB_PREFIX."Synopsis_demandeInterv'";
    $db->query($requete);
    $zim->Synopsis_Zimbra_GetDemandeIntervention();
    $zim->Synopsis_Zimbra_GetIntervention();
}

function reinit_Livraison($zim,$db)
{
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger
                      WHERE type_event_refid = (SELECT id FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type WHERE val='appointment')
                        AND event_table_link='".MAIN_DB_PREFIX."livraison'";
    $db->query($requete);
    $zim->Synopsis_Zimbra_GetLivraison();
}
function reinit_Expedition($zim,$db)
{
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger
                      WHERE type_event_refid = (SELECT id FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type WHERE val='appointment')
                        AND event_table_link='".MAIN_DB_PREFIX."expedition'";
    $db->query($requete);
    $zim->Synopsis_Zimbra_GetExpedition();
}
function reinit_ActionCom($zim,$db)
{
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger
                      WHERE type_event_refid = (SELECT id FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type WHERE val='appointment')
                        AND event_table_link='".MAIN_DB_PREFIX."actioncomm'";
    $db->query($requete);
    $zim->Synopsis_Zimbra_GetActionCom();

}
function reinit_Contact($zim,$db)
{
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger
                      WHERE type_event_refid = (SELECT id FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type WHERE val='appointment')
                        AND event_table_link='".MAIN_DB_PREFIX."socpeople'";
    $db->query($requete);
    $zim->Synopsis_Zimbra_GetPeople();

}
function reinit_Ressources($zim,$db)
{
    //$zim->debug=true;
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger
                      WHERE type_event_refid = (SELECT id FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type WHERE val='appointment')
                        AND event_table_link='".MAIN_DB_PREFIX."Synopsis_global_ressources'";
    $db->query($requete);

    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_li_Ressources";
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($sql))
    {
        $zim->BabelDeleteRessources($res->ZimbraId);
    }

    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_li_Ressources";
    $db->query($requete);

    $zim->Synopsis_Zimbra_GetRessources();

}

function reinit_CalUtilisateur($zim,$db)
{

    //vider la base // Zimbra vider par reinit all

//    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger
//                      WHERE type_event_refid = (SELECT id FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type WHERE val='appointment')
//                        AND event_table_link='".MAIN_DB_PREFIX."socpeople'";
//    $db->query($requete);
//    $zim->Synopsis_Zimbra_GetPeople();
    //1)Creer repertoire surZim : 1 par User + 1 subForlder : Action, Propal, Commande, Facture, Livraison, Interventions,Contrat
        //liste les subfolders
        $requete = "SELECT *
                      FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_folder
                     WHERE folder_parent = (SELECT folder_uid
                                              FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_folder
                                             WHERE folder_type_refid =1
                                               AND folder_name='Utilisateurs')";
        $parentFolderArray=array();
        $parentFolderOther= array();
        if ($resql = $db->query($requete))
        {
            while($res = $db->fetch_object($resql))
            {
                if ($res->folder_name == "autres")
                {
                    $parentFolderOther['id2letter']=$res->folder_name;
                    $parentFolderOther['letter2id']=$res->folder_uid;
                } else {
                    $parentFolderArray['id2letter'][$res->folder_uid]=$res->folder_name;
                    $parentFolderArray['letter2id'][$res->folder_name]=$res->folder_uid;
                }
            }
        }


        //liste les utilisateurs


        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."user";
        if ($resql  = $db->query($requete))
        {
            while ($res = $db->fetch_object($resql))
            {
                $famName = $res->name;
                //Select parent
                $firstLetter = $famName[0];
                $found = false;
                $parentFolderId = false;
                foreach ($parentFolderArray['letter2id'] as $letters => $tmpFolderUid)
                {
                    if (preg_match("/[".$firstLetter."]/i",$letters))
                    {
                        $found = true;
                        $parentFolderId=$tmpFolderUid;
                        break;
                    }
                }
                if (!$found)
                {
                    $parentFolderId  = $parentFolderOther['letter2id'];
                    $found = true;
                }

                //Create Folder
                $createArray = array();
                $createArray=array('view' => 'appointment',
                                   "name" =>  trim($res->firstname.' '.$res->name),
                                   "where" => $parentFolderId);

                $elemFold = $zim->BabelCreateFolder($createArray);
                $apptFoldId = $elemFold['id'];
                //Ajouter dans la table ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_folder
                $zim->BabelInsertTriggerFolder($apptFoldId,trim($res->firstname.' '.$res->name),$parentFolderId,"appointment",0);
                    $requeteUpdtUser = "UPDATE ".MAIN_DB_PREFIX."Synopsis_Zimbra_li_User " .
                            "               SET calFolderZimId ='". $apptFoldId  ."' " .
                                    "     WHERE User_refid = ".$res->rowid;
                    $db->query($requeteUpdtUser);

                //=> ajoute les sous dossiers Actions, Propales, Commandes Factures, Livraisons, Interventions, Contrats
                foreach(array("Actions","Propales","Commandes","Factures","Livraisons","Interventions","Contrats") as $subFolderName)
                {
                    $createArray = array();
                    $createArray=array('view' => 'appointment',
                                       "name" =>  "$subFolderName",
                                       "where" => $apptFoldId);

                    $elemFold1 = $zim->BabelCreateFolder($createArray);
                    $apptFoldId1 = $elemFold1['id'];
                   $zim->BabelInsertTriggerFolder($apptFoldId1,$subFolderName,$apptFoldId,"appointment",0);
                }

                //Actions
                //Propale:
                $zim->Synopsis_Zimbra_GetPropalUser($res->rowid,$apptFoldId);
                $zim->Synopsis_Zimbra_GetCommandeUser($res->rowid,$apptFoldId);
                $zim->Synopsis_Zimbra_GetFactureUser($res->rowid,$apptFoldId);
                $zim->Synopsis_Zimbra_GetLivraisonUser($res->rowid,$apptFoldId);
                $zim->Synopsis_Zimbra_GetExpeditionUser($res->rowid,$apptFoldId);
                $zim->Synopsis_Zimbra_GetInterventionUser($res->rowid,$apptFoldId);
                $zim->Synopsis_Zimbra_GetDemandeInterventionUser($res->rowid,$apptFoldId);
                $zim->Synopsis_Zimbra_GetActionComUser($res->rowid,$apptFoldId);
            }
        }
    //2)Chercher toutes les obj de l'utilisateurs et les placer dans le bon rep
    //3)Vider la table SQL
    //4)placer les droits sur le calendrier => Admin read sur tous, pas admn => read le sien only



}


//Todo Get Livraison et Get Expedition, Get Contrat, Get Action Com

llxHeader("","Admin Zimbra");

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($langs->trans("Zimbra"),$linkback,'setup');


//print_titre($langs->trans("Zimbra"));
if ($_REQUEST['msg']."x" != "x")
{
    print '<br>';
    print "<div class='ui-state-highlight'>".$_REQUEST['msg']."</div>";
}
print '<br>';


print '<form name="zimbraconfig" action="" method="post">';
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td width=\"30%\">".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "<td>".$langs->trans("Examples")."</td>";
print "</tr>";
print "<tr class=\"impair\">";
print "<td>".$langs->trans("ZIMBRA_HOST")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"ZIMBRA_HOST\" value=\"". ($_POST["ZIMBRA_HOST"]?$_POST["ZIMBRA_HOST"]:$conf->global->ZIMBRA_HOST) . "\" size=\"40\"></td>";
print "<td>zimbra.synopsis-erp.com";
print "</td>";
print "</tr>";
print "<tr class=\"pair\">";
print "<td>".$langs->trans("ZIMBRA_DOMAIN")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"ZIMBRA_DOMAIN\" value=\"". ($_POST["ZIMBRA_DOMAIN"]?$_POST["ZIMBRA_DOMAIN"]:$conf->global->ZIMBRA_DOMAIN) . "\" size=\"40\"></td>";
print "<td>synopsis-erp.com";
//print "<br>__dolibarr_main_db_host__ <i>(".$dolibarr_main_db_host.")</i>"
print "</td>";
print "</tr>";

print "<tr class=\"impair\">";
print "<td>".$langs->trans("ZIMBRA_PROTO")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"ZIMBRA_PROTO\" value=\"". ($_POST["ZIMBRA_PROTO"]?$_POST["ZIMBRA_PROTO"]:$conf->global->ZIMBRA_PROTO) . "\" size=\"40\"></td>";
print "<td>http ou https";
print "</td>";
print "</tr>";

print "<tr class=\"pair\">";
print "<td>".$langs->trans("ZIMBRA_PREAUTH")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"ZIMBRA_PREAUTH\" value=\"". ($_POST["ZIMBRA_PREAUTH"]?$_POST["ZIMBRA_PREAUTH"]:$conf->global->ZIMBRA_PREAUTH) . "\" size=\"40\"></td>";
print "<td>0123456789af12effe2d24a7091e262db37eb9542bc921b2ae4434fcb6338284";
print "</td>";
print "</tr>";

print "<tr class=\"impair\">";
print "<td>".$langs->trans("ZIMBRA_WEBDAVPROTO")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"ZIMBRA_WEBDAVPROTO\" value=\"". ($_POST["ZIMBRA_WEBDAVPROTO"]?$_POST["ZIMBRA_WEBDAVPROTO"]:$conf->global->ZIMBRA_WEBDAVPROTO) . "\" size=\"40\"></td>";
print "<td>webdav / webdavs";
print "</td>";
print "</tr>";

print "<tr class=\"pair\">";
print "<td>".$langs->trans("ZIMBRA_ADMINUSER")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"ZIMBRA_ADMINUSER\" value=\"". ($_POST["ZIMBRA_ADMINUSER"]?$_POST["ZIMBRA_ADMINUSER"]:$conf->global->ZIMBRA_ADMINUSER) . "\" size=\"40\"></td>";
print "<td>gle";
print "</td>";
print "</tr>";


print "<tr class=\"impair\">";
print "<td>".$langs->trans("ZIMBRA_ADMINPASS")."</td>";
print "<td><input type=\"password\" class=\"flat\" name=\"ZIMBRA_ADMINPASS\" value=\"". ($_POST["ZIMBRA_ADMINPASS"]?$_POST["ZIMBRA_ADMINPASS"]:$conf->global->ZIMBRA_ADMINPASS) . "\" size=\"40\"></td>";
print "<td>GleByB@bel";
print "</td>";
print "</tr>";

print "<tr class=\"pair\">";
print "<td>".$langs->trans("ZIMBRA_ADMINID")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"ZIMBRA_ADMINID\" value=\"". ($_POST["ZIMBRA_ADMINID"]?$_POST["ZIMBRA_ADMINID"]:$conf->global->ZIMBRA_ADMINID) . "\" size=\"40\"></td>";
print "<td>GleByB@bel";
print "</td>";
print "</tr>";



print "<tr class=\"impair\">";
print "<td>".$langs->trans("ZIMBRA_ZIMBRA_USE_LDAP")."</td>";
print "<td><input type=\"checkbox\"  class=\"flat\" name=\"ZIMBRA_ZIMBRA_USE_LDAP\" ". ($_POST["ZIMBRA_ZIMBRA_USE_LDAP"]?"checked":($conf->global->ZIMBRA_ZIMBRA_USE_LDAP == "true")?"checked":"")  . " size=\"40\"></td>";
print "<td>&nbsp;";
print "</td>";
print "</tr>";


print "</TABLE>";

print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</center>";

print "</form>\n";

//si clef SSO entree et OK, affiche de creation de dossier sinon affiche message sympa
if ($conf->global->ZIMBRA_PREAUTH ."x" != 'x' && $conf->global->ZIMBRA_ADMINUSER ."x" != "x")
{
    require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
    $zim = new Zimbra($conf->global->ZIMBRA_ADMINUSER);
    $zim->langs=$langs;
    if ($zim)
    {
        //TODO
        $refNumberItem = 25; //2 racines 2 element par racine
        //affiche le status:
        //Zimbra :
//        $zim->debug=true;
        $ret = $zim->connect();
//        die($ret);
        $zim->db=$db;

//        var_dump($ret);
//        $ret=$zim->parseRecursiveAptFolder($ret);
        $ret=$zim->parseRecursiveAptFolder($zim->getAllFolder());
        $arrFolderDesc = $zim->appointmentFolderLevel;
//        var_dump($ret);
        echo "<br/><br/>";
        //SQL
        $sqlCnt = 0;
        $requete = "SELECT count(*) as cnt
                      FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_folder
                     WHERE skeleton_part = 1";
        if ($resql=$db->query($requete))
        {
            $sqlCnt = $db->fetch_object($resql)->cnt;
        }
        print "<form action='?action=populateZimbra' method='POST' >";
        print "<table width='450px' class='border' style='text-align: left;'>";
        print "<tr><td class='liste_titre' style='width: 150px;'>Status SQL";
        if ($sqlCnt <$refNumberItem)
        {
            print "<input type='hidden' name='SQLStatus' value='ko' />";
            print "<td  colspan=1>".img_picto("ko","warning");
            print "<td><input type='submit' name='sub1' value='Correct'";
        } else {
            print "<input type='hidden' name='SQLStatus' value='ok' />";
            print "<td colspan=2>".img_picto("ok","tick");
        }
        print "<tr><td class='liste_titre' >Status Zimbra";
        $html ='';
        $ok=array();
        $ok['Calendrier']=false;
        $ok['CalendrierSociete']=false;
        $ok['CalendrierUtilisateur']=false;
        $remZimId = false;
        $remZimCalUsrId=false;
        $remZimCalSocId=false;
//require_once('Var_Dump.php');
//var_dump::display($arrFolderDesc);
        foreach($arrFolderDesc as $key=>$val)
        {
            if (preg_match('/Calendriers - GLE/',$val['name']) && $val['parent']==1 && !$ok['Calendrier'])
            {
                $ok['Calendrier']=true;
                $remZimId = $val['id'];
                continue;
            }
        }
        if ($ok['Calendrier'])
        {
            print "<input type='hidden' name='ZimMainCalStatus' value='ok' />";
            $html .= "<tr><th class='liste_titre'  align='right'>Calendrier<td colspan=2>".img_picto("ok","tick");

            foreach($arrFolderDesc as $key=>$val)
            {
                if ("Sociétés" == $val['name'] && $val['parent'] == $remZimId &&!$ok['CalendrierSociete'])
                {
                    $ok['CalendrierSociete']=true;
                    $remZimCalSocId = $val['id'];
                    continue;
                }
                if ($val['name'] == "Utilisateurs" && $val['parent'] == $remZimId  && !$ok['CalendrierUtilisateur'])
                {
                    $ok['CalendrierUtilisateur']=true;
                    $remZimCalUsrId = $val['id'];
                    continue;
                }

            }
            if ($ok['CalendrierSociete'])
            {
                $html .= "<tr><th class='liste_titre' align='right'>Soci&eacute;t&eacute;<td colspan=2>".img_picto("ok","tick");
                print "<input type='hidden' name='ZimCalSocStatus' value='ok' />";
            } else {
                $html .= "<tr><th class='liste_titre' align='right'>Soci&eacute;t&eacute;<td colspan=2>".img_picto("ko","warning");
                print "<input type='hidden' name='ZimCalSocStatus' value='ko' />";
            }
            if ($ok['CalendrierUtilisateur'])
            {
                $html .= "<tr><th class='liste_titre' align='right'>Utilisateurs<td colspan=2>".img_picto("ok","tick");
                print "<input type='hidden' name='ZimUsrSocStatus' value='ok' />";
            } else {
                $html .= "<tr><th class='liste_titre' align='right'>Utilisateurs<td colspan=2>".img_picto("ko","warning");
                print "<input type='hidden' name='ZimUsrSocStatus' value='ko' />";
            }

        } else {
            $html .= "<td>".img_picto("ko","warning");
            print "<input type='hidden' name='ZimMainCalStatus' value='ko' />";
        }

        if ($ok['CalendrierUtilisateur'] && $ok['CalendrierSociete'] && $ok['Calendrier'])
        {
            print "<td colspan=2>".img_picto("ok","tick");
            print $html;
        } else {
            print  "<td>".img_picto("ko","warning");
            print "<td><input type='submit' name='sub1' value='Correction'";
            print $html;
        }


//        Var_Dump::Display($zim->appointmentFolderDesc);

//Var_Dump::Display($zim->appointmentFolderDesc);
        //rep principaux : GLE calendar | /societe |/utilisateur  et GLE contact | /societe |/utilisateur
        //rep secondaire : GLE calendar/societe/abc GLE calendar/societe/def GLE calendar/societe/ghi ...
        //1 test si les repertoires principaux existent sur Zimbra et dans la base
        //  si existe dans la base et pas dans Zimbra :> efface la base => mode creation
        //  si existe dans zimbra et pas dans la base :> maj de la base
        //  si existe dans les 2 => chck coherence des datas => si fail => update SQL
        //  si existe pas dans les 2 => mode creation
        //1a > idem que 1 mais avec rep secondaire
        //2 affiche status Zimbra et status SQL
        //3 affiche le bouton populate
        //4 si populate
        //  4a :> cree 1 dossier par societe dans rep secondaire
        //  4b :> cree 1 dossier par utilisateur dans rep secondaire
        //  4c :> cree 1 dossier action com par utilisateur, 1 dossier client/prospect
        //  4d :> cree 1 dossier par societe pour propal , commande ...
        //  4e :> efface le contenu de la base SQL des events avant imports
        print "</table><BR>";
        print "<p>Migrations dolibarr : </p><hr>";
        if ($ok['CalendrierUtilisateur'] && $ok['CalendrierSociete'] && $ok['Calendrier'])
        {
            print "<table><tr><td>";
            print "<input type='submit' style='width:200px;' name='sub1' value='".$langs->trans('Reinit')."' class='butAction'></input>";
            print "<td>";
            print "<input type='submit' style='width:200px;' name='sub2' value='".$langs->trans('populateSoc')."' class='butAction'></input>";
            print "<td>";
            print "<input type='submit' style='width:200px;' name='subPropal' value='".$langs->trans('populateProp')."' class='butAction'></input>";
            print "<td>";
            print "<input type='submit' style='width:200px;' name='subCommande' value='".$langs->trans('populateCommande')."' class='butAction'></input>";
            print "<td>";
            print "<input type='submit' style='width:200px;' name='subFacture' value='".$langs->trans('populateFacture')."' class='butAction'></input>";
            print "<td>";
            print "<input type='submit' style='width:200px;' name='subIntervention' value='".$langs->trans('populateIntervention')."' class='butAction'></input>";
            print "<td>";
            print "<input type='submit' style='width:200px;' name='subExpedition' value='".$langs->trans('populateExpedition')."' class='butAction'></input>";
            print "<td>";
            print "<input type='submit' style='width:200px;' name='subLivraison' value='".$langs->trans('populateLivraison')."' class='butAction'></input>";
            print "<td>";
            print "<input type='submit' style='width:200px;' name='subActionComm' value='".$langs->trans('populateActionCom')."' class='butAction'></input>";
            print "<td>";
            print "<input type='submit' style='width:200px;' name='subPaiement' value='".$langs->trans('populatePaiement')."' class='butAction'></input>";
            print "<td>";
            print "<input type='submit' style='width:200px;' name='subPaiementFourn' value='".$langs->trans('populatePaiementFourn')."' class='butAction'></input>";
            print "<tr><td>";
            print "<input type='submit' style='width:200px;' name='subContact' value='".$langs->trans('populateContact')."' class='butAction'></input>";
            print "<tr><td>";
            print "<input type='submit' style='width:200px;' name='subCalUtilisateur' value='".$langs->trans('populateCalUtilisateur')."' class='butAction'></input>";
            print "<tr><td>";
            print "<input type='submit' style='width:200px;' name='subCalRessources' value='".$langs->trans('populateRessources')."' class='butAction'></input>";
            print "</table>";
        }
        print "</form>";

    }
//on cree les dossiers principaux de Zimbra et on stock les infos dans SQL
//babelCreateFolder
//on parse la base pour populate du calendar et gestion des permissions

}


clearstatcache();

if ($mesg) print "<br>$mesg<br>";
print "<br>";

$db->close();

llxFooter('$Date: 2005/10/03 01:36:21 $ - $Revision: 1.23 $');
?>
