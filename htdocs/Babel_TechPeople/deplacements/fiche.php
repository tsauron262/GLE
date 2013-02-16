<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/
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
//TODO :
// table :> ".MAIN_DB_PREFIX."deplacement
// 1 fiche  ndf = 1 mois 1 anne 1 utilisateur => ajouter un bouton genere / valide la ndf du mis de + cran de synthese
// modifier catégorie dans la conf
// desactivé ndf doli car conf
//

/**
   \file           htdocs/compta/deplacement/fiche.php
   \brief          Page fiche d'un deplacement
   \version        $Id: fiche.php,v 1.28 2008/06/04 00:19:36 eldy Exp $
*/

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/deplacement.lib.php");

$langs->load("trips");
$langs->load("synopsisGene@Synopsis_Tools");

// Security check
$id=isset($_GET["id"])?$_GET["id"]:$_POST["id"];
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'deplacement', $id,'');


$mesg = '';


/*
 * Actions
 */
if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes" && $user->rights->deplacement->supprimer)
{
    $deplacement = new Deplacement($db);
    $deplacement->id = $_GET['id'];
    $deplacement->fetch($_GET["id"]);
    $deplacement->delete($_GET["id"]);
    Header("Location: index.php");
    exit;
}
//    var_dump($_REQUEST);

if ($_POST["action"] == 'add' && $user->rights->deplacement->creer)
{
    if (! $_POST["cancel"])
    {
        $deplacement = new Deplacement($db);

        $deplacement->date = dol_mktime(12, 0, 0,
                        $_POST["remonth"],
                        $_POST["reday"],
                        $_POST["reyear"]);

        if ($_POST['fk_type'] == 2)
        {
           $deplacement->km = $_POST["km"];
        } else {
            $deplacement->prix_ht = $_POST['km'];
        }

        $deplacement->socid = $_POST["socid"];
        $deplacement->type = $_POST["fk_type"];
        $deplacement->fk_user = $_POST["fk_user"];
        $deplacement->note = $_POST["note"];
        $deplacement->tva_taux = $_POST["taux"];
        $deplacement->lieu = $_POST["lieu"];

        $id = $deplacement->create($user);
//            $mesg = $deplacement->error;
//            print "<br>".$mesg."<br>";
//            var_dump($deplacement);
        if ($id > 0)
        {
            $db->commit();
            header ( "Location: fiche.php?id=".$id);
            exit;
        }
        else
        {
            $mesg=$deplacement->error;
            $_GET["action"]='create';
            exit();
        }
    }
    else
    {
        Header ( "Location: index.php");
        exit;
    }
}

if ($_POST["action"] == 'update' && $user->rights->deplacement->creer)
{
//    var_dump($_REQUEST);
    if (empty($_POST["cancel"]))
    {
        $deplacement = new Deplacement($db);
        $result = $deplacement->fetch($_POST["id"]);

        $deplacement->date = dol_mktime(12, 0, 0,
                        $_POST["remonth"],
                        $_POST["reday"],
                        $_POST["reyear"]);

        if ($_POST['fk_type'] == 2)
        {
            $deplacement->km = $_POST["km"];
        } else {
            $deplacement->prix_ht = $_POST['km'];
        }

        $deplacement->socid = $_POST["socid"];
        $deplacement->type = $_POST["fk_type"];
        $deplacement->fk_user = ($_POST["fk_user"]?$_POST['fk_user']:$user->id);
        $deplacement->note = $_POST["note"];
        $deplacement->tva_taux = $_POST["taux"];
        $deplacement->lieu = $_POST["lieu"];

//        $deplacement->date = dol_mktime(12, 0 , 0,
//                    $_POST["remonth"],
//                    $_POST["reday"],
//                    $_POST["reyear"]);
//
//        $deplacement->km     = $_POST["km"];
//        $deplacement->type   = $_POST["type"];
//        $deplacement->fk_user = $_POST["fk_user"];

        $result = $deplacement->update($user);

        if ($result > 0)
        {
            Header("Location: fiche.php?id=".$_POST["id"]);
            exit;
        }
        else
        {
            $mesg=$deplacement->error;
        }
    }
    else
    {
        Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$_POST["id"]);
        exit;
    }
}



llxHeader();

$html = new Form($db);

/*
 * Action create
 */
if ($_GET["action"] == 'create')
{
    print_fiche_titre($langs->trans("NewTrip"));
    if ($mesg) print $mesg."<br>";

    print "<form name='add' action=\"fiche.php\" method=\"post\">\n";
    print '<input type="hidden" name="action" value="add">';

    print '<table class="border" width="100%">';

    print "<tr>";
    print '<td class="ui-state-default ui-widget-header"  width="25%">'.$langs->trans("CompanyVisited").'</td>
           <td class="ui-widget-content">';
    $html->select_company($_GET["socid"],'socid','',1);
    print '</td>';
    print '<td rowspan=6 class="ui-widget-content">';
    if ($conf->fckeditor->enabled)
    {
        // Editeur wysiwyg
        require_once(DOL_DOCUMENT_ROOT."/core/lib/doleditor.class.php");
        $doleditor=new DolEditor('note','',180,'dolibarr_notes','In',false);
        $doleditor->Create();
    } else {
        print '<textarea name="note" cols="90" rows="'.ROWS_6.'"></textarea>';
    }
    print "</td></tr><tr>";
    print '<td class="ui-state-default ui-widget-header">'.$langs->trans("Type").'</td>
           <td class="ui-widget-content">';
    print $html->select_type_fees($_GET["type"],'fk_type',1);
    print '</td></tr>';
    print "<input type='hidden' name='fk_user' value='".$user->id."'></input>";

    print '<tr><td class="ui-state-default ui-widget-header">'.$langs->trans("VAT").'</td>
               <td class="ui-widget-content">';
    $html->load_tva('taux','','','','update');
    print '</td></tr>';


    print "<tr>";
    print '<td class="ui-state-default ui-widget-header">'.$langs->trans("Date").'</td>
           <td class="ui-widget-content">';
    print $html->select_date('','','','','','add');
    print '</td></tr>';

    print '<tr><td class="ui-state-default ui-widget-header">'.$langs->trans("FeesKilometersOrAmout").'</td><td class="ui-widget-content"><input name="km" size="40" value=""></td>';
    print '<tr><td class="ui-state-default ui-widget-header">'.$langs->trans("Location").'</td><td class="ui-widget-content"><input name="lieu" size="40" value=""></td></tr>';

    print '<tr><td class="ui-state-default ui-widget-header" colspan="3" align="center"><input class="button" type="submit" value="'.$langs->trans("Save").'">&nbsp;';
    print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
    print '</table>';
    print '</form>';
} else {
    if ($id)
    {
        $deplacement = new Deplacement($db);
        $deplacement->id=$id;
        $result = $deplacement->fetch($id);
        $ndf = $deplacement->getNdf();
        if ($ndf == -1){
            //print "Err : Ndf non trouv&eacute;e"; exit ;
            //Create Ndf
            $ndftmp = new Ndf($db);
            $ndftmp->createAuto(new User($db,$deplacement->fk_user));
            $result = $deplacement->fetch($id);
            $ndf = $deplacement->getNdf();
        }
        if (!$ndf->fk_user_author == $user->id || !$user->rights->deplacement->creer)
        {
            print "Acc&egrave;s non autoris&eacute;";
            exit(0);
        }

        if ($result)
        {
            if ($mesg) print $mesg."<br>";
            if ($_GET["action"] == 'edit')
            {
               $head = deplacement_prepare_head($deplacement);

                dol_fiche_head($head, 0, $langs->trans("Dep."));

                print "<form name='update' action=\"fiche.php\" method=\"post\">\n";
                print '<input type="hidden" name="action" value="update">';
                print '<input type="hidden" name="id" value="'.$deplacement->id.'">';

                print '<table class="border" width="100%">';

                $soc = new Societe($db);
                $soc->fetch($deplacement->socid);

                print "<tr>";
                print '<td class="ui-widget-header ui-state-default" width="20%">'.$langs->trans("CompanyVisited").'</td>
                       <td class="ui-widget-content">';
                $html->select_company($soc->id,'socid','',1);
                print '</td>';
                print '<td rowspan=6 class="ui-widget-content">';
                if ($conf->fckeditor->enabled)
                {
                    // Editeur wysiwyg
                    require_once(DOL_DOCUMENT_ROOT."/core/lib/doleditor.class.php");
                    $doleditor=new DolEditor('note',$deplacement->note,180,'dolibarr_notes','In',false);
                    $doleditor->Create();
                } else {
                    print '<textarea name="note" cols="90" rows="'.ROWS_6.'">'.$deplacement->note.'</textarea>';
                }
                print "</td></tr>";
                print "<tr>";
                print '<td class="ui-widget-header ui-state-default">'.$langs->trans("Type").'</td>
                       <td class="ui-widget-content">';
                print $html->select_type_fees($deplacement->type,'fk_type',1);
                print '</td></tr>';

                print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans("Date").'</td>
                           <td class="ui-widget-content">';
                print $html->select_date($deplacement->date,'','','','','update');
                print '</td></tr>';
                print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans("Lieu").'</td>
                           <td class="ui-widget-content"><input name="lieu" class="flat" size="10" value="'.$deplacement->lieu.'"></input></td></tr>';

                print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans("FeesKilometersOrAmout").'</td>
                           <td class="ui-widget-content"><input name="km" class="flat" size="10" value="'.$deplacement->km.'"></td></tr>';

                print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans("VAT").'</td>
                           <td class="ui-widget-content">';
                $html->load_tva("taux",$deplacement->tva_taux,'','','','update');
                print '</td></tr>';

                print '<tr><td class="ui-widget-header ui-state-default" align="center" colspan="3"><input type="submit" class="button" value="'.$langs->trans("Save").'"> &nbsp; ';
                print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'"></td></tr>';
                print '</table>';
                print '</form>';

                print '</div>';
            } else {
                $h=0;

               $head = deplacement_prepare_head($demande);
                dol_fiche_head($head, 0, $langs->trans("Dep."));

                /*
                * Confirmation de la suppression du deplacement
                */
                if ($_GET["action"] == 'delete')
                {
                    $html = new Form($db);
                    $html->form_confirm("fiche.php?id=".$id,$langs->trans("DeleteTrip"),$langs->trans("ConfirmDeleteTrip"),"confirm_delete");
                    print '<br>';
                }
                $soc = new Societe($db);
                $soc->fetch($deplacement->socid);

                print '<table class="border" width="100%">';
                print '<tr><td width="20%">'.$langs->trans("CompanyVisited").'</td><td>'.$soc->getNomUrl(1).'</td>';
                $rowspan=6;
                print "<td width='30%' rowspan='".$rowspan."'>".$deplacement->note."</td></tr>";
                print '<tr><td>'.$langs->trans("Type").'</td><td>'.$deplacement->type_dep.'</td></tr>';

//                print '<tr><td>'.$langs->trans("Person").'</td><td>';
//                $userfee=new User($db);
//                $userfee->fetch($deplacement->fk_user);
//                print $userfee->getNomUrl(1);
//                print '</td></tr>';

                print '<tr><td>'.$langs->trans("Date").'</td><td>';
                print dol_print_date($deplacement->date);
                print '</td></tr>';

                if ('x'.$deplacement->km !='x')
                {
                    print '<tr><td>'.$langs->trans("Distance").'</td><td>'.$deplacement->km.'</td></tr>';
                }
                print '<tr><td>'.$langs->trans("Lieu").'</td><td>'.$deplacement->lieu.'</td></tr>';
                print '<tr><td>'.$langs->trans("Montant HT").'</td><td>'.price($deplacement->prix_ht).'&euro;</td></tr>';
                if ('x'.$deplacement->km =='x')
                {
                    print '<tr><td>'.$langs->trans("VAT").'</td><td>'.round($deplacement->tva_taux,2).'%</td></tr>';
                }
                print "</table>";

                print '</div>';
            }
        } else {
            dol_print_error($db);
        }
    }
}


/*
 * Barre d'actions
 *
 */

print '<div class="tabsAction">';

if ($_GET["action"] != 'create' && $_GET["action"] != 'edit')
{

  if ($ndf->statut <2 ||  $ndf->statut >3){
      print '<a class="butAction" href="fiche.php?action=edit&id='.$id.'">'.$langs->trans('Modify').'</a>';
      print '<a class="butActionDelete" href="fiche.php?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
  }

}

print '</div>';

$db->close();

llxFooter('$Date: 2008/06/04 00:19:36 $ - $Revision: 1.28 $');
?>
