<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 3-8-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : testECMjquery.php
  * GLE-1.1
  */

require_once('../../main.inc.php');

require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/ecm/ecm.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/ecm/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/ecm/treeview.lib.php");
require_once(DOL_DOCUMENT_ROOT."/ecm/class/ecmdirectory.class.php");


          $db1 = $db;
$ecmdir = new ecmdirectory($db);
$ecmdir->Load($db,$langs,$conf);


$langs->load("ecm");
$langs->load("companies");
$langs->load("other");
$langs->load("users");
$langs->load("deliveries");
$langs->load("orders");
$langs->load("propal");
$langs->load("bills");
$langs->load("contracts");
$langs->load("synopsisGene@Synopsis_Tools");

// Load permissions
$user->getrights('ecm');

// Get parameters
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
$action = isset($_GET["action"])?$_GET["action"]:$_POST['action'];
$section=isset($_GET["section"])?$_GET["section"]:$_POST['section'];
if (! $section) $section=0;

$upload_dir = $conf->ecm->dir_output.'/'.$section;

$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="label";



/*******************************************************************
 * ACTIONS
 *
 * Put here all code to do according to value of "action" parameter
 ********************************************************************/

// Envoie fichier
if ( $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
    $result=$ecmdir->fetch($_REQUEST["section"]);
    if (! $result > 0)
    {
        dol_print_error($db,$ecmdir->error);
        exit;
    }
    $relativepath=$ecmdir->getRelativePath();
    $upload_dir = $conf->ecm->dir_output.'/'.$relativepath;

    if (! is_dir($upload_dir))
    {
        $result=dol_mkdir($upload_dir);
    }

    if (is_dir($upload_dir))
    {
        $tmpName = $_FILES['userfile']['name'];
        //decode decimal HTML entities added by web browser
        $tmpName = dol_unescapefile($tmpName );

        $result = dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $tmpName,0);
        if ($result > 0)
        {
            //$mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
            //print_r($_FILES);
            $result=$ecmdir->changeNbOfFiles('+');
        } else if ($result < 0) {
            // Echec transfert (fichier depassant la limite ?)
            $langs->load("errors");
            $mesg = '<div class="error ui-state-error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
            // print_r($_FILES);
        } else {
            // File infected by a virus
            $langs->load("errors");
            $mesg = '<div class="error ui-state-error">'.$langs->trans("ErrorFileIsInfectedWith",$result).'</div>';
        }
    } else {
        // Echec transfert (fichier depassant la limite ?)
        $langs->load("errors");
        $mesg = '<div class="error ui-state-error">'.$langs->trans("ErrorFailToCreateDir",$upload_dir).'</div>';
    }
}

// Remove file
if ($_POST['action'] == 'confirm_deletefile' && $_POST['confirm'] == 'yes')
{
    $result=$ecmdir->fetch($_REQUEST["section"]);
    if (! $result > 0)
    {
        dol_print_error($db,$ecmdir->error);
        exit;
    }
    $relativepath=$ecmdir->getRelativePath();
    $upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
    $file = $upload_dir . "/" . urldecode($_GET["urlfile"]);

    $result=dol_delete_file($file);

    $mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';

    $result=$ecmdir->changeNbOfFiles('-');
    $action='file_manager';
}

// Action ajout d'un produit ou service
if ($_POST["action"] == 'add' && $user->rights->ecm->setup)
{
    $ecmdir->ref                = $_POST["ref"];
    $ecmdir->label              = $_POST["label"];
    $ecmdir->description        = $_POST["desc"];

    $id = $ecmdir->create($user);
    if ($id > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        $mesg='<div class="error ui-state-error">Error '.$langs->trans($ecmdir->error).'</div>';
        $_GET["action"] = "create";
    }
}

// Suppression fichier
if ($_POST['action'] == 'confirm_deletesection' && $_POST['confirm'] == 'yes')
{
    $result=$ecmdir->delete($user);
    $mesg = '<div class="ok">'.$langs->trans("ECMSectionWasRemoved", $ecmdir->label).'</div>';
}




$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";


/*******************************************************************
 * PAGE
 *
 * Put here all code to do according to value of "action" parameter
 ********************************************************************/

$js = ' <link rel="stylesheet" href="'.DOL_URL_ROOT.'/Synopsis_Common/css/jquery.treeview.css" />';
//   print ' <link rel="stylesheet" href="../red-treeview.css" />';
//   print ' <link rel="stylesheet" href="screen.css" />';
   $js .= ' <script src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.cookie.js" type="text/javascript"></script>';
   $js .= ' <script src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.treeview.js" type="text/javascript"></script>';
//$js .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
//$js .= " <script> jQuery(document).ready(function(){ jQuery('select').selectmenu({style: 'dropdown', maxHeight: 300 }); });  </script>\n";

$js .= '<style>';
$js .= '.folder{ background: transparent url('.DOL_URL_ROOT.'/Synopsis_Common/images/folderopen.gif) no-repeat scroll 1pt -2pt; padding-left: 20px;}'."\n";
$js .= '.societe{ background: transparent url('.DOL_URL_ROOT.'/Synopsis_Common/images/object_company.png) no-repeat scroll 1pt 1pt; padding-left: 18px;}'."\n";
$js .= '.MyZimbra{ background: transparent url('.DOL_URL_ROOT.'/theme/'.$conf->theme.'/MyZimbra16.png) no-repeat scroll 1pt 1pt; padding-left: 18px;}'."\n";

$js .= 'ul { line-height: 10px;}'."\n";
$js .= "#ecmtree { font-size: 13px; }"."\n";
$js .= ".treeview li.lastCollapsable, .treeview li.lastExpandable { background-image: url(".DOL_URL_ROOT."/Synopsis_Common/images/treeview-default.gif); }"."\n";
$js .= '.treeview .placeholder {
    background: url('.DOL_URL_ROOT.'/Synopsis_Common/images/ajax-loader.gif) 0 0 no-repeat;
    height: 16px;
    width: 16px;
    display: block;
}';

$js .= <<<EOCSS

.treeview, .treeview ul {
    padding: 0;
    margin: 0;
    list-style: none;
}

.treeview ul {
    background-color: rgb(247, 245, 200);
    margin-top: 4px;
}

/* fix for IE6 */
* html .hitarea {
    display: inline;
    float:none;
}

.treeview li {
    margin: 0;
    padding: 3px 0pt 3px 16px;
}

.treeview a.selected {
    background-color: #eee;
}

#treecontrol { margin: 1em 0; display: none; }

.treeview .hover { color: red; cursor: pointer; }

.treeview li.collapsable, .treeview li.expandable { background-position: 0 -176px; }

.treeview .expandable-hitarea { background-position: -80px -3px; }

.treeview li.last { background-position: 0 -1766px }
.treeview li.lastCollapsable { background-position: 0 -111px }
.treeview li.lastExpandable { background-position: -32px -67px }

.treeview div.lastCollapsable-hitarea, .treeview div.lastExpandable-hitarea { background-position: 0; }

#ecmtreeDiv {background-color: rgb(247, 245, 200) ; width: 350px; overflow: auto; max-height: 400px;height: 600px; }

ul#ecmtree { min-width: 345px; white-space: nowrap;}
#ecmtree a { font-weight: 900; text-decoration: none; color: #000000; margin-top: 3px; }
#ecmtree a:hover { text-decoration: underline; cursor: pointer; font-size: 14px;}

#ecmsidetreecontrol { font-size: small; background-color: rgb(247, 245, 200) ; width: 275px; overflow: auto;}
body {
    position relative;
    min-height: 500px;
}
EOCSS;
$js .= '</style>';
$js .= "";
//$js .= "<script language='javascript' type='text/javascript' src='".DOL_URL_ROOT."/ecm/js/dtree.js' ></script>";
$js .= "<script language='javascript' type='text/javascript' src='".DOL_URL_ROOT."/ecm/js/ecm.js' ></script>";
$js .= '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/ecm/css/dtree.css" />';
$js .= "<script type='text/javascript'> var DOL_URL_ROOT='".DOL_URL_ROOT."';";
$js .= "  var DOL_DOCUMENT_ROOT='".DOL_DOCUMENT_ROOT."';";
$js .= "     </script>\n";

$js .= <<<EOJS
    <script type="text/javascript">
//        $(document).ready(function() {
//            $("#tree").treeview({
//                collapsed: true,
//                animated: "slow",
//                control:"#sidetreecontrol",
//                prerendered: true,
//                persist: "location"
//            });
//        })
        $(document).ready(function() {
            $("#ecmtree").treeview({
                collapsed: true,
                animated: "slow",
                control:"#ecmsidetreecontrol",
                prerendered: true,
                persist: "none"
            });
        $("body").css('min-height',"300px");
        })

    </script>
EOJS;

if ($conf->global->MAIN_MODULE_ZIMBRA)
{

    $js .=  "<script type='text/javascript' src='".DOL_URL_ROOT."/Synopsis_Zimbra/contacts/zimbra_contact.js'>
        </script>\n";
    $js .= "<script type='text/javascript'> var DOL_URL_ROOT='".DOL_URL_ROOT."';";
    $js .= "     </script>\n";

    $js .= "
    <style type='text/css'>
        .pairCont { background: #e6ebed; border: 0px; }
        .pairCont:hover { background: #1215c7; color: #EEEEDD; border: 0px; cursor: pointer ; }
        .impairCont { background: #d0d4d7; border: 0px; }
        .impairCont:hover { background: #1215c7; color: #EEEEDD; border: 0px; cursor: pointer ; }
        table { border-collapse: collapse; padding: 0px;}
    </style>";
}


llxHeader($js,"GLE - ECM", 1);
function llxHeader($head, $title,$noscript)
{
        global $conf,$langs,$user;
    $langs->load("ecm");
    $langs->load("bills");
    $langs->load("propal");

    top_menu($head, $title,"",$noscript,false);


}
$form=new Form($db);
$ecmdirstatic = new ECMDirectory($db);
$userstatic = new User($db);


// Ajout rubriques automatiques
$rowspan=0;


//***********************
// List
//***********************
print_fiche_titre($langs->trans("Gestion documentaire"));

print "<br>\n";

// Confirm remove file
if ($_GET['action'] == 'delete')
{
    $form->form_confirm($_SERVER["PHP_SELF"].'?section='.$_REQUEST["section"].'&amp;urlfile='.urldecode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile');
    print '<br>';
}

if ($mesg) { print $mesg."<br>"; }
print "<div style='clear: both;'>";
// Tool bar
$selected='file_manager';
if (preg_match('/search/i',$action)) $selected='search_form';
//$head = ecm_prepare_head_fm($fac);
//dol_fiche_head($head, $selected, '', '');


print "<div style='vertical-align: top; padding: 0px; margin: 0px; clear: left;'>";
print '<table class="bordernopadding" style="padding-top:0px; margin-top:0px;" width="100%"><tr><td width="25%" valign="top">';

// Left area
print '<table class="nobordernopadding" width="100%"><tr><td valign="top">';

if (empty($action) || $action == 'file_manager' || preg_match('/refresh/i',$action) || $action == 'delete')
{
    $userstatic = new User($db);
    $ecmdirstatic = new ECMDirectory($db);

    // Confirmation de la suppression d'une ligne categorie
    if ($_GET['action'] == 'delete_section')
    {
        $form->form_confirm($_SERVER["PHP_SELF"].'?section='.urldecode($_GET["section"]), $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection',$ecmdir->label), 'confirm_deletesection');
        print '<br>';
    }

    $catMan = array();
    $dir = DOL_DATA_ROOT . "/ecm/";
//    $catMan = $ecmdirstatic->scan_directory_recursively($dir,FALSE,array(0=>"meta",1=>"tmp"));

    // Construit liste des repertoires
    print '<table width="100%" class="nobordernopadding">';

    print '<tr class="liste_titre" style="padding-left: 5pt; line-height: 22pt; font-size:120% ">';
    print '<td class="liste_titre" align="left">'.$langs->trans("ECMSections").'</td>';
    print '</tr><tr><td>';

    print '<div id="main">';
    print '<div id="ecmtreeDiv">';
    print '<br/>';
    print '<ul class="treeview" id="ecmtree">';
    print '      <li class="expandable"><div class="hitarea expandable-hitarea"></div>';
    print '<span style="background: transparent url('.DOL_URL_ROOT.'/Synopsis_Common/images/base.gif) no-repeat scroll 1pt 0pt; padding-top: 1px;  padding-right: 4px; padding-left: 18px; font-size: 16px;"><a href="?/racine.com"><strong>&nbsp;Racine</strong></a></span>';

    $requete = "SELECT max(displayOrder) as maxD
                  FROM ".MAIN_DB_PREFIX."Synopsis_ecm_document_auto_categorie
                 WHERE disabled = 0";
    $sql=$db->query($requete);
    $res = $db->fetch_object($sql);
    $maxD = $res->maxD;

    $requete = "SELECT *
                  FROM ".MAIN_DB_PREFIX."Synopsis_ecm_document_auto_categorie
                 WHERE disabled = 0
              ORDER BY displayOrder";


    if ($sql=$db->query($requete))
    {
        while ($res = $db->fetch_object($sql))
        {
            $extraClass = "";
            $extraClassLi = "";
            if ($maxD == $res->displayOrder)
            {
                $extraClass=" lastExpandable ";
                $extraClassLi=" lastExpandable-hitarea ";
            }
            print '<ul style="display: block;">'."\n";
            print '    <li class="expandable '.$extraClass.'"><div class="hitarea expandable-hitarea '.$extraClassLi.'"></div><a href="#">'.$langs->Trans($res->nom).'</a>'."\n";
            $sqlTable = $res->sqlTable;
            switch($sqlTable)
            {
                case '".MAIN_DB_PREFIX."propal':
                {
                    if ($conf->global->ECM_SHOWPROPAL == "true" && $user->rights->ecm->showPropal){
                        $ecmdir->currentTable = $sqlTable;
                        $ecmdir->jqtreePropal('propal');
                    }
                }
                break;
                case '".MAIN_DB_PREFIX."commande':
                    if ($conf->global->ECM_SHOWCOMMANDE == "true" && $user->rights->ecm->showCommande){
                        $ecmdir->currentTable = $sqlTable;
                        $ecmdir->jqtreePropal('commande');
                    }
                break;
                case '".MAIN_DB_PREFIX."facture':
                    if ($conf->global->ECM_SHOWFACTURE == "true" && $user->rights->ecm->showFacture){
                        $ecmdir->currentTable = $sqlTable;
                        $ecmdir->jqtreePropal('facture');
                    }
                break;
                case '".MAIN_DB_PREFIX."fichinter':
                    if ($conf->global->ECM_SHOWINTERV == "true" && $user->rights->ecm->showInterv){
                        $ecmdir->currentTable = $sqlTable;
                        $ecmdir->jqtreePropal('fichinter');
                    }
                break;
                case MAIN_DB_PREFIX.'Synopsis_demandeInterv':
                    if ($conf->global->ECM_SHOWDEMANDEINTERV == "true" && $user->rights->ecm->showdemandeInterv){
                        $ecmdir->currentTable = $sqlTable;
                        $ecmdir->jqtreePropal('demandeInterv');
                    }
                break;
                case '".MAIN_DB_PREFIX."livraison':
                    if ($conf->global->ECM_SHOWLIVRAISON == "true" && $user->rights->ecm->showLivraison){
                        $ecmdir->currentTable = $sqlTable;
                        $ecmdir->jqtreePropal('livraison');
                    }
                break;
                case '".MAIN_DB_PREFIX."expedition':
                    if ($conf->global->ECM_SHOWEXPEDITION == "true" && $user->rights->ecm->showExpedition){
                        $ecmdir->currentTable = $sqlTable;
                        $ecmdir->jqtreePropal('expedition');
                    }
                break;
        //        case '".MAIN_DB_PREFIX."product':
        //            if ($conf->global->ECM_SHOWPRODUIT == "true" && $user->rights->ecm->showProduit)
        //                $scatId = jqtreePropal('produit');
        //        break;
                case '".MAIN_DB_PREFIX."commande_fournisseur':
                    if ($conf->global->ECM_SHOWCOMMANDEFOURN == "true" && $user->rights->ecm->showCommFourn){
                        $ecmdir->currentTable = $sqlTable;
                        $ecmdir->jqtreePropal('commande_fournisseur');
                    }
                break;
        //        case '".MAIN_DB_PREFIX."facture_fourn':
        //            if ($conf->global->ECM_SHOWFACTUREFOURN == "true" && $user->rights->ecm->showFactureFourn)
        //                $scatId = jqtreePropal('facture_fourn');
        //        break;
        //        case '".MAIN_DB_PREFIX."contrat':
        //            if ($conf->global->ECM_SHOWCONTRAT == "true" && $user->rights->ecm->showContrat)
        //                $scatId = jqtreePropal('contrat');
        //        break;
        //        case '".MAIN_DB_PREFIX."societe':
        //            if ($conf->global->ECM_SHOWSOCIETE == "true" && $user->rights->ecm->showSociete)
        //                $scatId = jqtreePropal('societe');
        //        break;
        //        case '".MAIN_DB_PREFIX."user':
        //            if ($conf->global->ECM_SHOWUTILISATEUR == "true" && $user->rights->ecm->showUtilisateur)
        //                $scatId = jqtreePropal('user');
        //        break;
        //        case '".MAIN_DB_PREFIX."bank':
        //            if ($conf->global->ECM_SHOWBANQUE == "true" && $user->rights->ecm->showBanque)
        //                $scatId = jqtreePropal('banque');
        //        break;
                case '".MAIN_DB_PREFIX."bordereau_cheque':
                    if ($conf->global->ECM_SHOWCHEQUE == "true" && $user->rights->ecm->showCheque)
                    {
                        $ecmdir->currentTable = $sqlTable;
                        $ecmdir->jqtreePropal('bordereau_cheque');
                    }
                break;
        //        case '".MAIN_DB_PREFIX."bonprelev':
        //            if ($conf->global->ECM_SHOWBONPRELEV == "true" && $user->rights->ecm->showBonPrelev)
        //                $scatId = jqtreePropal('bonprelev);
        //        break;
        //        case '".MAIN_DB_PREFIX."taxe':
        //            if ($conf->global->ECM_SHOWTAXE == "true" && $user->rights->ecm->showTaxe)
        //                $scatId = jqtreePropal('taxe');
        //        break;

            }
            print '    </li>'."\n";
            print '</ul>'."\n";
        }
    }


//Document Manuel
    if (!($conf->global->MAIN_MODULE_ZIMBRA && $user->rights->SynopsisZimbra->PushECM))
    {
        $extraClass = "lastExpandable" ;
        $extraClassLi = "lastExpandable-hitarea" ;
    }

    print "</li>";
    print '<ul style="display: block;">'."\n";
    print '<li class="expandable '.$extraClass.'"><div class="hitarea expandable-hitarea '.$extraClassLi.'"></div><a href="#">'.$langs->Trans('ECMMan').'</a>'."\n";

    //Prob need recursive

    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."ecm_directories WHERE fk_parent=0 OR fk_parent is null";
    $iter4=0;
    if ($resql = $db->query($requete))
    {
        $countTot = $db->num_rows($resql);

        while ($res=$db->fetch_object($resql))
        {

            $iter4++;
            $extraClass = "";
            $extraClassLi = "";
            $extraClassDiv="";

            if ($countTot == $iter4)
            {
                $extraClass="last";
                $extraClassLi="lastExpandable";
                $extraClassDiv="lastExpandable-hitarea";
            }

            print '<ul style="display: none;">'."\n";
            $requete1 = "SELECT *
                      FROM ".MAIN_DB_PREFIX."ecm_directories
                     WHERE fk_parent=$res->rowid";
            $iter4=0;
            if ($resql1 = $db->query($requete1))
            {
                $countTot1 = $db->num_rows($resql1);
                if ($countTot1 > 0)
                {
                    print '<li class="expandable '.$extraClassLi.'"><div class="hitarea expandable-hitarea '.$extraClassDiv.'"></div><span class="folder"><a href="javascript:showManDoc('.$res->rowid.')">'.$res->label.'</a></span>'."\n";

                } else {
                    print '<li class="'.$extraClass.'"><div class="'.$extraClass.'"></div><span class="folder"><a href="javascript:showManDoc('.$res->rowid.')">'.$res->label.'</a></span>'."\n";

                }
                $ecmdir->jqmanCat($res->rowid);
            } else {
                print '<li class="'.$extraClass.'"><div class="'.$extraClass.'"></div><span class="folder"><a href="javascript:showManDoc('.$res->rowid.')">'.$res->label.'</a></span>'."\n";

            }

            print '</li></ul>'."\n";
        }
    }
     print '</li></ul>'."\n";
    print "        </li>";

//mon Zimbra
    if ($conf->global->MAIN_MODULE_ZIMBRA && $user->rights->SynopsisZimbra->PushECM)
    {
         require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");
        $zimuser="";
        if ($conf->global->ZIMBRA_ZIMBRA_USE_LDAP=="true")
        {
            $zimuser=$user->login;
        } else {
            $user->getZimbraCred($user->id);
            $zimuser=$user->ZimbraLogin;
        }

//DEBUG
        $zim = new Zimbra($zimuser);
         $zim->debug=false;
         $zim->langs=$langs;
         $ret = $zim->connect();
        $extraClass = "lastExpandable" ;
        $extraClassLi = "lastExpandable-hitarea" ;



//        // print "        a.add('aa3','1',' ".$langs->Trans('MyZimbra')." ','',' ".$langs->Trans('MyZimbra')." ','','".DOL_URL_ROOT."/theme/".$conf->theme."/MyZimbra32.png"."','".DOL_URL_ROOT."/theme/auguria/img/zimbra-folder.png"."');\n";
//         print "        a.add('aa3','1',' ".$langs->Trans('MyZimbra')." ','',' ".$langs->Trans('MyZimbra')." ');\n";
//         //print "        a.add('zw1','aa3','".$langs->trans('RacineZimWiki')."','javascript:void(0)');\n";
//         print "        a.add('zn1','aa3','".$langs->trans('RacineZimDocument')."','javascript:void(0)');\n";

    print '<ul style="display: block;">'."\n";
    print '<li class="expandable '.$extraClass.'"><div class="hitarea expandable-hitarea '.$extraClassLi.'"></div><span class="MyZimbra"><a href="#">'.$langs->Trans('MyZimbra').'</a></span>'."\n";

//        $zim->parseRecursiveWikiFolder($ret);
//
//        foreach($zim->wikiFolderLevel as $key=>$val)
//        {
//            print "        a.add('zw".$val["id"]."','zw".$val["parent"]."',' ".$val["name"]."','javascript:showZimDoc(\'".$val['id']."\')');\n";
//        }
//Get Parent ???
        if ($zim->isConnected())
        {
            $zim->parseRecursiveDocumentFolder($ret);
            $cntLev1 = 0;
            foreach($zim->documentFolderLevel as $key=>$val)
            {
                if ($val['parent'] == 1)
                {
                    $cntLev1 ++;
                }
            }
            $iter = 0;
            foreach($zim->documentFolderLevel as $key=>$val)
            {
                if ($val['parent'] == 1)
                {
                    $iter++;
                    $extraClass="";
                    $extraClassLi="";
                    $extraClassDiv="";
                    if ($cntLev1 == $iter)
                    {
                        $extraClass="last";
                        $extraClassLi="lastExpandable";
                        $extraClassDiv="lastExpandable-hitarea";
                    }
                                //Does it have children
                    $cntChild=0;
                    foreach($zim->documentFolderLevel as $key1=>$val1)
                    {
                        if ($val1['parent'] == $val["id"])
                        {
                            $cntChild ++;
                        }
                    }
                    if ($cntChild == 0)
                    {
                        print '<ul style="display: none;">'."\n";
                        print '    <li class=" '.$extraClass.'"><div class=" '.$extraClass.'"></div><span class="folder"><a href="javascript:showZimDoc(\''.$val['id'].'\')">'.$langs->Trans($val['name']).'</a></span>'."\n";
                        print '</li></ul>';

                    } else {
                        print '<ul style="display: none;">'."\n";
                        print '    <li class="expandable '.$extraClassLi.'"><div class="hitarea expandable-hitarea '.$extraClassDiv.'"></div><span class="folder"></span><a href="javascript:showZimDoc(\''.$val['id'].'\');">'.$langs->Trans($val['name']).'</a>'."\n";
                        parseRecursECM($val['id'],$zim,$langs);
                        print '</li></ul>';
                    }
                }
            }
        }

    }
    print '</li></ul>';
    print "    </ul>";


    print "</div>";

 }
    print "</table>\n";
print '</td></tr></table>'."\n";
print '</td><td valign="top">'."\n";
print "<table class='nobordernopadding' width=100%>\n";
print "<tr class='liste_titre'  style='padding-left: 5pt; line-height: 22pt; font-size:120% ' ><td class='liste_titre'>D&eacute;tails<tr><td>\n";
print "<div id ='ecmdetailDiv'>\n";
print "<span>S&eacute;lectionner un r&eacute;pertoire sur la gauche</span>\n";
print "</div>\n";
print "</table>\n";
print '</td></tr>';


print '&nbsp;';
print '</td></tr>'."\n";

print '</table>'."\n";
print '</table>'."\n";
print '</div>'."\n";
print '</div>'."\n";
print '</div>'."\n";


//push to Zimbra :


    if ($conf->global->MAIN_MODULE_ZIMBRA && $user->rights->SynopsisZimbra->Push)
    {
        print '<br/>'."\n";
        print "<div style='padding-top: 6px; display:none; ' id='showZimbra'>\n";
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
                    <tr>
                        <th>Zimbra</th>'."\n";
        print '         <th>Fichiers et notes</th>'."\n";
        print '     </tr>'."\n";



        require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Zimbra/ZimbraSoap.class.php');
        $zimuser="";
        if ($conf->global->ZIMBRA_ZIMBRA_USE_LDAP=="true")
        {
            $zimuser=$user->login;
        } else {
            $user->getZimbraCred($user->id);
            $zimuser=$user->ZimbraLogin;
        }
        $zim = new Zimbra($zimuser);
        $zim->debug=false;
        $zim->langs=$langs;
        $ret = $zim->connect();
        $ret1 = $ret;
        $zim->parseRecursiveWikiFolder($ret);
        $zim->parseRecursiveDocumentFolder($ret1);
        print '<tr class="impair">
                <td style="width: 250px;">'."\n";
        print "<div style='max-height: 170px; background-color: rgb(230,235,237); border-bottom: 2px Solid rgb(208,212,215); overflow: auto;'>\n";
        print "<div class='dtree' >\n";
//        print "    <script type='text/javascript'>\n";
//        print "        dt = new dTree('dt');\n";
//        print "        dt.icon = {\n";
//        print "            root            : '".DOL_URL_ROOT."/Synopsis_Zimbra/contacts/img/zimRootIcone.gif',\n";
//        print "            folder          : '".DOL_URL_ROOT."/Synopsis_Zimbra/contacts/img/kaddressbook.png',\n";
//        print "            folderOpen      : '".DOL_URL_ROOT."/Synopsis_Zimbra/contacts/img/kaddressbook.png',\n";
//        print "            node            : '".DOL_URL_ROOT."/Synopsis_Zimbra/contacts/img/vcard.png',\n";
//        print "            empty           : '".DOL_URL_ROOT."/Synopsis_Zimbra/contacts/img/empty.gif',\n";
//        print "            line            : '".DOL_URL_ROOT."/Synopsis_Zimbra/contacts/img/line.gif',\n";
//        print "            join            : '".DOL_URL_ROOT."/Synopsis_Zimbra/contacts/img/join.gif',\n";
//        print "            joinBottom      : '".DOL_URL_ROOT."/Synopsis_Zimbra/contacts/img/joinbottom.gif',\n";
//        print "            plus            : '".DOL_URL_ROOT."/Synopsis_Zimbra/contacts/img/plus.gif',\n";
//        print "            plusBottom      : '".DOL_URL_ROOT."/Synopsis_Zimbra/contacts/img/plusbottom.gif',\n";
//        print "            minus           : '".DOL_URL_ROOT."/Synopsis_Zimbra/contacts/img/minus.gif',\n";
//        print "            minusBottom     : '".DOL_URL_ROOT."/Synopsis_Zimbra/contacts/img/minusbottom.gif',\n";
//        print "            nlPlus          : '".DOL_URL_ROOT."/Synopsis_Zimbra/contacts/img/nolines_plus.gif',\n";
//        print "            nlMinus         : '".DOL_URL_ROOT."/Synopsis_Zimbra/contactsadd_group.png/img/nolines_minus.gif'\n";
//        print "        };\n";
//        print "\n";
//
//        print "        dt.add(1,-1,' ".$langs->Trans('ZimRacine')." ');\n";
//        print "        dt.add('w1','1','".$langs->trans('RacineZimWiki')."','javascript:void(0)');\n";
//        print "        dt.add('n1','1','".$langs->trans('RacineZimDocument')."','javascript:void(0)');\n";
//        foreach($zim->wikiFolderLevel as $key=>$val)
//        {
//            print "        dt.add('w".$val["id"]."','w".$val["parent"]."',' ".$val["name"]."','javascript:setZimbraFolder(\'".$val["name"]."\',".$val["id"].")');\n";
//        }
//        foreach($zim->documentFolderLevel as $key=>$val)
//        {
//            print "        dt.add('n".$val["id"]."','n".$val["parent"]."',' ".$val["name"]."','javascript:setZimbraFolder(\'".$val["name"]."\',".$val["id"].")');\n";
//        }
//        print "        document.write(dt);\n";
//        print "    </script>\n";
        print "\n";
        print "</div>\n"; //dtree
//        print "</div>\n";
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

        print '<form action=?action=pushToZimbra'.$getUrl.' method="post">';
        print  img_picto($langs->trans("ajoute un contact"),"add_user")."&nbsp;<span id='repZimbra' style='font-size: 13pt; font-weight: 900;'>".$langs->Trans("ZimRacine")."</span> : <p/><table>
            <tr>
                <td>".$langs->Trans('ZimCreateCalIn')."
                </td>
                <td>&nbsp;" .img_picto($langs->Trans('ZimNewFolderName'),'kdmconfig.png'). "

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
            </tr>

            <tr>
                <td colspan='1'>".$langs->Trans('ZimTags')."&nbsp;</td>
                <td colspan='2'>&nbsp;";
 $zim->displayTagsSelect(true,"zimbraTag","zimbraTag");
print "

                </td>
            </tr>
           </table>";

            print '<p><input type="submit" class="button"/></p></form>';
            print "</div>";
            print "</div>";

//        print "    <tr><td><p><a href='javascript: dt.openAll();'>".$langs->Trans("open all")."</a> |
//                      <a href='javascript: dt.closeAll();'>".$langs->Trans("close all")."</a>
//                   </p>\n";
            print "</div>";
            print "</table>";

    print "</div>";


    }

// End of page
$db->close();
//print $returnErr;
llxFooter('$Date: 2009/01/30 21:21:25 $ - $Revision: 1.39 $');


function parseRecursECM($lev,$zim,$langs)
{
    $itot = 0;
    foreach($zim->documentFolderLevel as $key=>$val)
    {
        if ($val['parent'] == $lev)
        {
            $itot ++;
        }
    }
    $i=0;
    foreach($zim->documentFolderLevel as $key=>$val)
    {
        if ($val['parent'] == $lev)
        {
            $i++;
            if ($itot == $i)
            {
                $extraClass="last";
                $extraClassLi="lastExpandable";
                $extraClassDiv="lastExpandable-hitarea";
            }

            //Does it have children
            $cntChild=0;
            foreach($zim->documentFolderLevel as $key1=>$val1)
            {
                if ($val1['parent'] == $val["id"])
                {
                    $cntChild ++;
                }
            }

            if ($cntChild == 0)
            {
                print '<ul style="display: none;">'."\n";
                print '    <li class=" '.$extraClass.'"><div class=" '.$extraClass.'"></div><span class="folder"><a href="javascript:showZimDoc(\''.$val['id'].'\')">'.$langs->Trans($val['name']).'</a></span>'."\n";
                print '</li></ul>';

            } else {
                print '<ul style="display: none;">'."\n";
                print '    <li class="expandable '.$extraClassLi.'"><div class="hitarea expandable-hitarea '.$extraClassDiv.'"></div><span class="folder"><a href="javascript:showZimDoc(\''.$val['id'].'\')">'.$langs->Trans($val['name']).'</a></span>'."\n";
                parseRecursECM($val['id'],$zim,$langs);
                print '</li></ul>';
            }

        }
    }

}
?>