<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005      Regis Houssin         <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.*//*
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
  *//*
 */

/**
        \file       htdocs/comm/affaire/document.php
        \ingroup    affairee
        \brief      Page de gestion des documents attachees a une proposition commerciale
        \version    $Id: document.php,v 1.43 2008/07/10 17:11:05 eldy Exp $
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/Babel_Affaire/Affaire.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

require_once(DOL_DOCUMENT_ROOT."/Babel_Affaire/fct_affaire.php");


$langs->load('compta');
$langs->load('other');
$langs->load("synopsisGene@Synopsis_Tools");

$action=empty($_GET['action']) ? (empty($_POST['action']) ? '' : $_POST['action']) : $_GET['action'];

$affaireid = isset($_GET["id"])?$_GET["id"]:'';

// Security check
if ($user->societe_id)
{
    unset($_GET["action"]);
    $action='';
    $socid = $user->societe_id;
}
//$result = restrictedArea($user, 'affaire', $affaireid, 'propal');

// Get parameters
$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;




if ($_REQUEST['SynAction'] == 'dlZip')
{
    require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
    $zipFilename =  tempnam("/tmp", "zipping-gle-affaire-");
    unlink($zipFilename);
    $zipFilename .= ".zip";
    $societe = new Societe($db);
    $affaire = new Affaire($db);
    if ($affaire->fetch($affaireid))
    {
        $upload_dir = $conf->affaire->dir_output.'/'.sanitize_string($affaire->ref);
        if ($societe->fetch($affaire->socid))
        {
            $finalFileName = "doc_affaire_".sanitize_string($affaire->ref)."_".sanitize_string($societe->nom) ."-". date("Ymd-Hi", mktime()).".zip";
            $filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
            $zip = new ZipArchive();
            if ( $zip->open($zipFilename,ZIPARCHIVE::CREATE) === TRUE)
            {
                $zip->setArchiveComment('Generate by GLE - Synopsis et DRSI');
                foreach($filearray as $key=>$val)
                {
                    //Add files
                     $zip->addFile($val['fullname'], "".sanitize_string($societe->nom).'/'.$val['name']);
                }
            $zip->close();
            }
        }
        if (is_file($zipFilename) &&filesize($zipFilename))
        {
              header("content-type: application/zip");
              header("Content-Disposition: attachment; filename=".$finalFileName);
              print file_get_contents($zipFilename);

              unlink ($zipFilename);
        }
    }
}


/*
 * Actions
 */

// Envoi fichier
if ($_POST["sendit"] && $conf->upload)
{
    $affaire = new Affaire($db);

    if ($affaire->fetch($affaireid))
    {
        $upload_dir = $conf->affaire->dir_output . "/" . sanitize_string($affaire->ref);
        if (! is_dir($upload_dir)) dol_mkdir($upload_dir);
        if (is_dir($upload_dir))
        {
              $tmpName = $_FILES['userfile']['name'];
              //decode decimal HTML entities added by web browser
              $tmpName = dol_unescapefile($tmpName );

            if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $tmpName,0) > 0)
            {
                $mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
//                add file to ecm
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($db);
                $interface->texte=$tmpName;
                $result=$interface->run_triggers('ECM_UL_AFFAIRE',$affaire,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
//                 Fin appel triggers
                require_once(DOL_DOCUMENT_ROOT . "/ecm/class/ecmdirectory.class.php" );
                $ecm = new EcmDirectory($db);
                $ecm->create_assoc("affaire",$affaire, $tmpName,$user,$conf);
            } else {
                // Echec transfert (fichier depassant la limite ?)
                $mesg = '<div class="error ui-state-error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
                // print_r($_FILES);
            }
        }
    }
}

// Delete
if ($action=='delete')
{
    $affaire = new Affaire($db);

    $affaireid=$_GET["id"];
    if ($affaire->fetch($affaireid))
    {
          $tmpName = $_FILES['userfile']['name'];
          //decode decimal HTML entities added by web browser
          $tmpName = dol_unescapefile($tmpName );

        $upload_dir = $conf->affaire->dir_output . "/" . sanitize_string($affaire->ref);
        $file = $upload_dir . '/' . urldecode($_GET['urlfile']);
        dol_delete_file($file);
        $mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface=new Interfaces($db);
        $interface->texte=$tmpName;
        $result=$interface->run_triggers('ECM_UL_DEL_AFFAIRE',$affaire,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }

    }
}


/*
 * Affichage
 */

llxHeader("","Affaire - document","",1);
if ($affaireid > 0)
{
    $affaire = new Affaire($db);
//    print "toto".$affaireid;
    if ($affaire->fetch($affaireid))
    {
        $upload_dir = $conf->affaire->dir_output.'/'.sanitize_string($affaire->ref);

        $societe = new Societe($db);
        $societe->fetch($affaire->socid);

        print_cartoucheAffaire($affaire,'document',"");
//        $head = affaire_prepare_head($affaire);
//        dol_fiche_head($head, 'document', $langs->trans('Proposal'));


        // Construit liste des fichiers
        $filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
        $totalsize=0;
        foreach($filearray as $key => $file)
        {
            $totalsize+=$file['size'];
        }
        if ($totalsize >  1024 * 1024)
        {
            $totalsize = round (10 * $totalsize / (1024 * 1024))/10;
            $totalsize .= " Mo";
        } else if ($totalsize >  1024)
        {
            $totalsize = round (10 * $totalsize / 1024)/10;
            $totalsize .= " ko";
        }else {
            $totalsize .= ' o';
        }

        print '<table class="border"width="100%" cellpadding=15>';

        print '<tr><th width=200  style="border-top: 0px Solid" class="ui-widget-header ui-state-default">'.$langs->trans("NbOfAttachedFiles").'</th><td style="border-top: 0px Solid" colspan="1" class="ui-widget-content">'.sizeof($filearray).'</td></tr>';
        print '<tr><th width=200 class="ui-widget-header ui-state-default">'.$langs->trans("TotalSizeOfAttachedFiles").'</th><td colspan="1" class="ui-widget-content">'.$totalsize.'</td></tr>';

        print '</table>';

        print '</div>';

        if ($mesg) { print "$mesg<br>"; }

        // Affiche formulaire upload
        $formfile=new FormFile($db);
        $formfile->form_attach_new_file(DOL_URL_ROOT.'/Babel_Affaire/document.php?id='.$affaire->id);


        // List of document
        $param='&id='.$affaire->id;
        $formfile->list_of_documents($filearray,$affaire,'affaire',$param);
        //Download all docs via zip
        print "<br>";
        print '<form action="?id='.$affaireid.'" method="POST">';
        print '<input type="hidden" name="affaire_id" value="'.$affaireid.'"/>';
        print '<input type="hidden" name="SynAction" value="dlZip"/>';
        print '<input class="button" style="width: 230px;" type="submit" value="'.$langs->trans('DownloadZip').'"/>';
        print '</form>';

        //List de tous les documents
        $requete = "SELECT * FROM Babel_Affaire_Element WHERE affaire_refid =".$affaireid;
        //    print $requete;
        $sql=$db->query($requete);
        $filearray1=array();
        while ($res=$db->fetch_object($sql))
        {
            $type = $res->type;
            $eid = $res->element_id;
            switch($type){
                case 'propale':
                {
                    require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
                    $obj=new Propal($db);
                    $obj->fetch($res->element_id);
                    $upload_dir = $conf->propale->dir_output.'/'.sanitize_string($obj->ref);
                    $filearrayt['propale'][sanitize_string($obj->ref)]=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
                    $filearray1 = array_merge($filearray1,$filearrayt);
                }
                break;
                case 'FI':
                {
                    require_once(DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php');
                    $obj=new Fichinter($db);
                    $obj->fetch($res->element_id);
                    $upload_dir = $conf->fichinter->dir_output.'/'.sanitize_string($obj->ref);
                    $filearrayt['FI'][sanitize_string($obj->ref)]=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
                    $filearray1 = array_merge($filearray1,$filearrayt);
                }
                break;
                case 'DI':
                {
                    require_once(DOL_DOCUMENT_ROOT.'/Synopsis_DemandeInterv/demandeInterv.class.php');
                    $obj=new demandeInterv($db);
                    $obj->fetch($res->element_id);
                    $upload_dir = $conf->synopsisdemandeinterv->dir_output.'/'.sanitize_string($obj->ref);
                    $filearrayt['DI'][sanitize_string($obj->ref)]=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
                    $filearray1 = array_merge($filearray1,$filearrayt);
                }
                break;
                case 'commande':
                {
                    require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
                    $obj=new Commande($db);
                    $obj->fetch($res->element_id);
                    $upload_dir = $conf->commande->dir_output.'/'.sanitize_string($obj->ref);
                    $filearrayt['commande'][sanitize_string($obj->ref)]=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
                    $filearray1 = array_merge($filearray1,$filearrayt);
                }
                break;
                case 'facture':
                {
                    require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
                    $obj=new Facture($db);
                    $obj->fetch($res->element_id);
                    $upload_dir = $conf->facture->dir_output.'/'.sanitize_string($obj->ref);
                    $filearrayt['facture'][sanitize_string($obj->ref)]=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
                    $filearray1 = array_merge($filearray1,$filearrayt);
                }
                break;
                case 'commande fournisseur':
                {
                    require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php');
                    $obj=new CommandeFournisseur($db);
                    $obj->fetch($res->element_id);
                    $upload_dir = $conf->fournisseur->commande->dir_output.'/'.sanitize_string($obj->ref);
                    $filearrayt['commande fournisseur'][sanitize_string($obj->ref)]=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
                    $filearray1 = array_merge($filearray1,$filearrayt);
                }
                break;
                case 'facture fournisseur':
                {
                    require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php');
                    $obj=new FactureFournisseur($db);
                    $obj->fetch($res->element_id);
                    $upload_dir = $conf->fournisseur->facture->dir_output.'/'.sanitize_string($obj->ref);
                    $filearrayt['facture fournisseur'][sanitize_string($obj->ref)]=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
                    $filearray1 = array_merge($filearray1,$filearrayt);
                }
                break;
                case 'projet':
                {
                    require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
                    $obj=new Project($db);
                    $obj->fetch($res->element_id);
                    $upload_dir = $conf->projet->dir_output.'/'.sanitize_string($obj->ref);
                    $filearrayt['projet'][sanitize_string($obj->ref)]=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
                    $filearray1 = array_merge($filearray1,$filearrayt);
                }
                break;
                case 'livraison':
                {
                    require_once(DOL_DOCUMENT_ROOT.'/livraison/livraison.class.php');
                    $obj=new Livraison($db);
                    $obj->fetch($res->element_id);
                    $upload_dir = $conf->livraison->dir_output.'/'.sanitize_string($obj->ref);
                    $filearrayt['projet'][sanitize_string($obj->ref)]=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
                    $filearray1 = array_merge($filearray1,$filearrayt);
                }
                break;
                case 'expedition':
                {
                    require_once(DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php');
                    $obj=new Expedition($db);
                    $obj->fetch($res->element_id);
                    $upload_dir = $conf->expedition->dir_output.'/'.sanitize_string($obj->ref);
                    $filearrayt['expedition'][sanitize_string($obj->ref)]=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
                    $filearray1 = array_merge($filearray1,$filearrayt);
                }
                break;
                case 'contrat':
                {
                    require_once(DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php');
                    $obj=new Contrat($db);
                    $obj->fetch($res->element_id);
                    $upload_dir = $conf->contrat->dir_output.'/'.sanitize_string($obj->ref);
                    $filearrayt['contrat'][sanitize_string($obj->ref)]=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
                    $filearray1 = array_merge($filearray1,$filearrayt);
                }
                break;
                case 'contratGA':
                {
                    require_once(DOL_DOCUMENT_ROOT.'/Babel_GA/contratGA.class.php');
                    $obj=new ContratGA($db);
                    $obj->fetch($res->element_id);
                    $upload_dir = $conf->CONTRATGA->dir_output.'/'.sanitize_string($obj->ref);
                    $filearrayt['contratGA'][sanitize_string($obj->ref)]=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
                    $filearray1 = array_merge($filearray1,$filearrayt);
                }
                break;
            }
        }
        print "<br/>";
        // List of document

        $param='&id='.$affaire->id;

        $url=$_SERVER["PHP_SELF"];
        print '<table width="100%" class="noborder">';
        print '<tr class="liste_titre">';
        print_liste_field_titre($langs->trans("Document"),$_SERVER["PHP_SELF"],"name","",$param,'align="left"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("Size"),$_SERVER["PHP_SELF"],"size","",$param,'align="right"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"date","",$param,'align="center"',$sortfield,$sortorder);
        print '<td>&nbsp;</td>';
        print '</tr>';

        $var=true;
        foreach($filearray1 as $key => $fileA)
        {
            $modulepart = $key;
            if (is_array($fileA))
            {
                foreach($fileA as $ref => $fileB)
                {
                    foreach($fileB as $key1 => $file)
                    {
                        if (!is_dir($file['name'])
                        && $file['name'] != '.'
                        && $file['name'] != '..'
                        && $file['name'] != 'CVS'
                        && $file['name'] != '.svn'
                        && ! preg_match('/\.meta$/i',$file['name']))
                        {
                            $var=!$var;
                            print "<tr $bc[$var]><td>";
                            print '<a href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart;
                            print '&file='.urlencode($ref."/".$file['name']).'">';
                            print img_mime($file['name']).' ';
                            print $file['name'];
                            print '</a>';
                            print "</td>\n";
                            print '<td align="right">'.dol_print_size($file['size']).'</td>';
                            print '<td align="center">'.dol_print_date($file['date'],"dayhour").'</td>';
                            print '<td align="right">';
                            $urldel = '<a href="'.$url.'?id='.$file['rowid'].'&amp;section='.$_REQUEST["section"].'&amp;action=delete&urlfile='.urlencode($file['name']).'">'.img_delete().'</a>';
                            //print '&nbsp;';
                            if ($permtodelete){
                                print $urldel;
                            } else{
                                print '&nbsp;';
                            }
                            print "</td></tr>\n";
                        }
                    }
                }
            }
        }
        if (sizeof($filearray1) == 0) print '<tr '.$bc[$var].'><td colspan="4">'.$langs->trans("NoFileFound").'</td></tr>';
        print "</table>";
        print "<br/>";


    } else {
        dol_print_error($db);
    }
} else {
    print $langs->trans("UnkownError");
}

$db->close();

llxFooter('$Date: 2008/07/10 17:11:05 $ - $Revision: 1.43 $');
?>
