<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 2 aout 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : contact.php
  * GLE-1.2
  */
    require_once('pre.inc.php');
    require_once('fct_affaire.php');
    require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Affaire/Affaire.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/livraison/class/livraison.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
    require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
    require_once(DOL_DOCUMENT_ROOT.'/domain/domain.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/Babel_SSLCert/SSLCert.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/domain/domain.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php');

  //liste tous les contacts de tous les eléments
    $langs->load('affaires');
    $langs->load('orders');
    $langs->load('propal');
    $langs->load('projects');
    $langs->load('bills');
    if (!$user->rights->affaire->lire) accessforbidden();
//require_once('Var_Dump.php');
//var_dump::display($_REQUEST);

if ($_REQUEST['action']=='generatePdf' || $_REQUEST['action'] == 'builddoc')
{
    $result = 0;
    $affaire = new Affaire($db);
    $result = $affaire->fetch($_REQUEST["id"]);

    if ($result > 0 && $_REQUEST["id"] > 0)
    {

        require_once(DOL_DOCUMENT_ROOT."/core/modules/synopsisaffaire/modules_affaire.php");
        affaireContact_pdf_create($db, $affaire->id, $_REQUEST['model']);
        header('location: contact.php?id='.$affaire->id."#documentAnchor");
    }
}

if ($_REQUEST["action"] == 'addcontact' && $user->rights->affaire->creer)
{

    $result = 0;
    $affaire = new Affaire($db);
    $result = $affaire->fetch($_REQUEST["id"]);

    if ($result > 0 && $_REQUEST["id"] > 0)
    {
        $result = $affaire->add_contact($_REQUEST["contactid"], $_REQUEST["type"], $_REQUEST["source"]);
    }

    if ($result >= 0)
    {
        //Header("Location: contact.php?id=".$affaire->id);
        //exit;
    }
    else
    {
        if ($affaire->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
        {
            $langs->load("errors");
            $mesg = '<div class="error ui-state-error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
        }
        else
        {
            $mesg = '<div class="error ui-state-error">'.$propal->error.'</div>';
        }
    }
}
// modification d'un contact. On enregistre le type
if ($_REQUEST["action"] == 'updateligne' && $user->rights->affaire->creer)
{
    $affaire = new Affaire($db);
    if ($affaire->fetch($_REQUEST["id"]))
    {
        $contact = $affaire->detail_contact($_REQUEST["elrowid"]);
        $type = $_REQUEST["type"];
        $statut = $contact->statut;

        $result = $affaire->update_contact($_REQUEST["elrowid"], $statut, $type);
        if ($result >= 0)
        {
            $db->commit();
        } else
        {
            dol_print_error($db, "result=$result");
            $db->rollback();
        }
    } else
    {
        dol_print_error($db);
    }
}

// bascule du statut d'un contact

if($_REQUEST['action'] == "swapInPDF" && $user->rights->affaire->creer)
{
    $id = $_REQUEST['id'];
    $idLigne = $_REQUEST["ligne"];
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."element_contact WHERE rowid = ".$idLigne;

    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $inPdfNew = 1;
    if ($res->inPDF == 1 )
    {
        $inPdfNew = 0;
    }

    $requete = "UPDATE ".MAIN_DB_PREFIX."element_contact SET inPDF = ".$inPdfNew. " WHERE rowid = ".$idLigne;
    $sql = $db->query($requete);

    if ($sql >= 0)
    {
        Header("Location: contact.php?id=".$id);
        exit;
    }
    else {
        dol_print_error($db);
    }


}
if ($_REQUEST["action"] == 'swapstatut' && $user->rights->affaire->creer)
{
    $affaire = new Affaire($db);
    if ($affaire->fetch($_REQUEST["id"]))
    {
        if ($_REQUEST['type']."x"!="x")
        {
            $affaire->element=$_REQUEST['type'];
        }
        $contact = $affaire->detail_contact($_REQUEST["ligne"]);
        $id_type_contact = $contact->fk_c_type_contact;
        $statut = ($contact->statut == 4) ? 5 : 4;
        $result = $affaire->update_contact($_REQUEST["ligne"], $statut, $id_type_contact);
        if ($result >= 0)
        {
            $db->commit();
        } else
        {
            dol_print_error($db, "result=$result");
            $db->rollback();
        }
    } else
    {
        dol_print_error($db);
    }
}

// Efface un contact
if ($_REQUEST["action"] == 'deleteline' && $user->rights->affaire->creer)
{
    $affaire = new Affaire($db);
    $affaire->fetch($_REQUEST["id"]);
    $result = $affaire->delete_contact($_REQUEST["lineid"]);

    if ($result >= 0)
    {
        Header("Location: contact.php?id=".$affaire->id);
        exit;
    }
    else {
        dol_print_error($db);
    }
}



    $affaireid=$_REQUEST['id'];


    $affaire = new Affaire($db);
    $affaire->fetch($affaireid);

    $js = "<script type='text/javascript' src='".DOL_URL_ROOT."/core/lib/lib_head.js'></script>";
    llxHeader($js,"Affaire - Contacts","",1);
    print_cartoucheAffaire($affaire,'Contacts');

    print "<table width=100% cellpadding=10>";
        if ($_GET["action"] != 'editline' && $user->rights->affaire->creer)
        {
            $html = new Form($db);

            print '<tr class="liste_titre">';
            print '<td>'.$langs->trans("Source").'</td>';
            print '<td>'.$langs->trans("Company").'</td>';
            print '<td>'.$langs->trans("Contacts").'</td>';
            print '<td>'.$langs->trans("ContactType").'</td>';
            print '<td colspan="3">&nbsp;</td>';
            print "</tr>\n";

            $var = false;

            print '<form action="contact.php?id='.$affaireid.'" method="post">';
            print '<input type="hidden" name="action" value="addcontact">';
            print '<input type="hidden" name="source" value="internal">';
            print '<input type="hidden" name="id" value="'.$affaireid.'">';

            // Ligne ajout pour contact interne
            print "<tr $bc[$var]>";

            print '<td>';
            print $langs->trans("Internal");
            print '</td>';

            print '<td colspan="1">';
            print $conf->global->MAIN_INFO_SOCIETE_NOM;
            print '</td>';

            print '<td colspan="1">';
            // On recupere les id des users deja selectionnes
            //$userAlreadySelected = $propal->getListContactId('internal');    // On ne doit pas desactiver un contact deja selectionner car on doit pouvoir le seclectionner une deuxieme fois pour un autre type
            $html->select_users($user->id,'contactid',0,$userAlreadySelected);
            print '</td>';
            print '<td>';
            $affaire->selectTypeContact($affaire, '', 'type','internal');
            print '</td>';
            print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
            print '</tr>';

            print '</form>';

            print '<form action="contact.php?id='.$affaireid.'" method="post">';
            print '<input type="hidden" name="action" value="addcontact">';
            print '<input type="hidden" name="source" value="external">';
            print '<input type="hidden" name="id" value="'.$affaireid.'">';

            // Ligne ajout pour contact externe
            $var=!$var;
            print "<tr $bc[$var]>";

            print '<td>';
            print $langs->trans("External");
            print '</td>';

            print '<td colspan="1">';
            $selectedCompany = isset($_GET["newcompany"])?$_GET["newcompany"]:$affaire->client->id;
            $selectedCompany = $affaire->selectCompaniesForNewContact($affaire, 'id', $selectedCompany, 'newcompany');
            print '</td>';

            print '<td colspan="1">';
            // On recupere les id des contacts deja selectionnes
            //$contactAlreadySelected = $propal->getListContactId('external');        // On ne doit pas desactiver un contact deja selectionner car on doit pouvoir le seclectionner une deuxieme fois pour un autre type
            $nbofcontacts=$html->select_contacts($selectedCompany, '', 'contactid',0,$contactAlreadySelected);
            if ($nbofcontacts == 0) print $langs->trans("NoContactDefined");
            print '</td>';
            print '<td>';
            $affaire->selectTypeContact($affaire, '', 'type','external');
            print '</td>';
            print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"';
            if (! $nbofcontacts) print ' disabled="true"';
            print '></td>';
            print '</tr>';

            print "</form>";

            print '<tr><td colspan="6">&nbsp;</td></tr>';
        }


    // Liste des contacts lies
    print "<br>";
    print "<div class='titre'>Contacts impliqu&eacute;s</div>";
    print "<br>";
    print '<table cellpadding=10 width=100%;> ';
    print '<tr>';
    print '<th class="ui-widget-header ui-state-default" colspan=3>'.$langs->trans("Source").'</th>';
    print '<th class="ui-widget-header ui-state-default">'.$langs->trans("Company").'</th>';
    print '<th class="ui-widget-header ui-state-default">'.$langs->trans("Contacts").'</th>';
    print '<th class="ui-widget-header ui-state-default">'.$langs->trans("ContactType").'</th>';
    print '<th class="ui-widget-header ui-state-default" colspan=2 align="center">'.$langs->trans("Status").'</th>';
    print '<th class="ui-widget-header ui-state-default" colspan="2">&nbsp;</th>';
    print "</tr>\n";

    $societe = new Societe($db);
    $var = true;

    $contactstatic=new Contact($db);

    $requete =" SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Affaire_Element WHERE affaire_refid =".$affaireid;
    $sql=$db->query($requete);
    $arr['affaire']['SQL']= MAIN_DB_PREFIX . 'Synopsis_Affaire';
    $arr['affaire']['OBJ']='Affaire';
    $arr['affaire']['RIGHT']='affaire';


    $arr['propale']['SQL']=MAIN_DB_PREFIX.'propal';
    $arr['propale']['OBJ']='Propal';
    $arr['propale']['RIGHT']='propale';

    $arr['commande']['SQL']=MAIN_DB_PREFIX.'commande';
    $arr['commande']['OBJ']='Commande';
    $arr['commande']['RIGHT']='commande';

    $arr['facture']['SQL']=MAIN_DB_PREFIX.'facture';
    $arr['facture']['OBJ']='Facture';
    $arr['facture']['RIGHT']='facture';

    $arr['expedition']['SQL']=MAIN_DB_PREFIX.'expedition';
    $arr['expedition']['OBJ']='Expedition';
    $arr['expedition']['RIGHT']='expedition';

    $arr['livraison']['SQL']=MAIN_DB_PREFIX.'livraison';
    $arr['livraison']['OBJ']='Livraison';
    $arr['livraison']['RIGHT']='livraison';

    $arr['projet']['SQL']=MAIN_DB_PREFIX.'Synopsis_projet_view';
    $arr['projet']['OBJ']='Project';
    $arr['projet']['RIGHT']='projet';

    $arr['contrat']['SQL']=MAIN_DB_PREFIX.'contrat';
    $arr['contrat']['OBJ']='Contrat';
    $arr['contrat']['RIGHT']='contrat';

    $arr['contratGA']['SQL']=MAIN_DB_PREFIX.'contrat';
    $arr['contratGA']['OBJ']='ContratGA';
    $arr['contratGA']['RIGHT']='GA->contrat';

    $arr['commande fournisseur']['SQL']=MAIN_DB_PREFIX.'commande_fournisseur';
    $arr['commande fournisseur']['OBJ']='CommandeFournisseur';
    $arr['commande fournisseur']['RIGHT']='fournisseur->commande';

    $arr['facture fournisseur']['SQL']=MAIN_DB_PREFIX.'facture_fourn';
    $arr['facture fournisseur']['OBJ']='FactureFournisseur';
    $arr['facture fournisseur']['RIGHT']='fournisseur->facture';
    $bc[false]=$bc[true]='class="ui-widget-content"';

    $obj = new Affaire($db);
    $obj->fetch($affaireid);

    foreach(array('internal','external') as $source)
    {
        $tab = $obj->liste_contact(-1,$source);
        $num=sizeof($tab);

        $i = 0;
        while ($i < $num)
        {
            $var = !$var;

            print '<tr valign="top">';
            print '<td '.$bc[$var].' align="center">Affaire<td align=center '.$bc[$var].'>'.$obj->getNomUrl(1);

            // Source
            print '<td '.$bc[$var].' align="center">';
            if ($tab[$i]['source']=='internal') print $langs->trans("Internal");
            if ($tab[$i]['source']=='external') print $langs->trans("External");
            print '</td>';

            // Societe
            print '<td align="left" '.$bc[$var].'>';
            if ($tab[$i]['socid'] > 0)
            {
                print '<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$tab[$i]['socid'].'">';
                print img_object($langs->trans("ShowCompany"),"company").' '.$societe->get_nom($tab[$i]['socid']);
                print '</a>';
            }
            if ($tab[$i]['socid'] < 0)
            {
                print $conf->global->MAIN_INFO_SOCIETE_NOM;
            }
            if (! $tab[$i]['socid'])
            {
                print '&nbsp;';
            }
            print '</td>';

            // Contact
            print '<td '.$bc[$var].'>';
            if ($tab[$i]['source']=='internal')
            {
                print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$tab[$i]['id'].'">';
                print img_object($langs->trans("ShowUser"),"user").' '.$tab[$i]['nom'].'</a>';
            }
            if ($tab[$i]['source']=='external')
            {
                print '<a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$tab[$i]['id'].'">';
                print img_object($langs->trans("ShowContact"),"contact").' '.$tab[$i]['nom'].'</a>';
            }
            print '</td>';

            // Type de contact
            print '<td '.$bc[$var].'>'.$tab[$i]['libelle'].'</td>';

            // Statut
            print '<td align="center" '.$bc[$var].'>';
            // Activation desativation du contact
            if ($obj->statut >= 0) print '<a href="contact.php?id='.$affaire->id.'&amp;action=swapstatut&amp;ligne='.$tab[$i]['rowid'].'">';
            print $contactstatic->LibStatut($tab[$i]['status'],3);
            if ($obj->statut >= 0) print '</a>';
            print '</td>';
//In PDF ??
            print '<td align="center" '.$bc[$var].'>';
            if ($user->rights->affaire->creer) print '<a href="contact.php?id='.$affaire->id.'&amp;action=swapInPDF&amp;ligne='.$tab[$i]['rowid'].'">';
            if($tab[$i]['inPDF'] > 0) print img_picto("Dans le PDF",DOL_URL_ROOT."/theme/common/mime/pdf.png","",1);
            else print img_picto("Pas dans le PDF",DOL_URL_ROOT."/theme/common/mime/notavailable.png","",1);
            if ($user->rights->affaire->creer) print '</a>';

            print '</td>';

            // Icon update et delete
            print '<td align="center" nowrap '.$bc[$var].'>';
            if ($user->rights->propale->creer)
            {
//TODO>supprimer si droit de modifier le contact
                print '&nbsp;';
                print '<a href="contact.php?id='.$affaire->id.'&amp;action=deleteline&amp;lineid='.$tab[$i]['rowid'].'">';
                print img_delete();
                print '</a>';
            }
            print '</td>';

            print "</tr>\n";

            $i ++;
        }
    }

//autre elements
    while ($res=$db->fetch_object($sql))
    {
        $obj = new $arr[$res->type]['OBJ']($db);
        $obj->fetch($res->element_id);
        foreach(array('internal','external') as $source)
        {
            $tab = $obj->liste_contact(-1,$source);
            $num=sizeof($tab);

            $i = 0;
            while ($i < $num)
            {
                $var = !$var;

                print '<tr valign="top">';
                print '<td '.$bc[$var].' align="center">'.$res->type . '<td align=center '.$bc[$var].'>'.$obj->getNomUrl(1);

                // Source
                print '<td '.$bc[$var].' align="center">';
                if ($tab[$i]['source']=='internal') print $langs->trans("Internal");
                if ($tab[$i]['source']=='external') print $langs->trans("External");
                print '</td>';

                // Societe
                print '<td align="left" '.$bc[$var].'>';
                if ($tab[$i]['socid'] > 0)
                {
                    print '<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$tab[$i]['socid'].'">';
                    print img_object($langs->trans("ShowCompany"),"company").' '.$societe->get_nom($tab[$i]['socid']);
                    print '</a>';
                }
                if ($tab[$i]['socid'] < 0)
                {
                    print $conf->global->MAIN_INFO_SOCIETE_NOM;
                }
                if (! $tab[$i]['socid'])
                {
                    print '&nbsp;';
                }
                print '</td>';

                // Contact
                print '<td '.$bc[$var].'>';
                if ($tab[$i]['source']=='internal')
                {
                    print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$tab[$i]['id'].'">';
                    print img_object($langs->trans("ShowUser"),"user").' '.$tab[$i]['nom'].'</a>';
                }
                if ($tab[$i]['source']=='external')
                {
                    print '<a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$tab[$i]['id'].'">';
                    print img_object($langs->trans("ShowContact"),"contact").' '.$tab[$i]['nom'].'</a>';
                }
                print '</td>';

                // Type de contact
                print '<td '.$bc[$var].'>'.$tab[$i]['libelle'].'</td>';

                // Statut
                print '<td align="center" '.$bc[$var].' colspan=2>';
                // Activation desativation du contact
                if ($obj->statut >= 0) print '<a href="contact.php?id='.$affaire->id.'&amp;action=swapstatut&amp;ligne='.$tab[$i]['rowid'].'&amp;type='.$tab[$i]['type'].'">';
                print $contactstatic->LibStatut($tab[$i]['status'],3);
                if ($obj->statut >= 0) print '</a>';
                print '</td>';
//In PDF ??
//            print '<td align="center" '.$bc[$var].'>';
//            if ($user->rights->affaire->creer) print '<a href="contact.php?id='.$affaire->id.'&amp;action=swapInPDF&amp;ligne='.$tab[$i]['rowid'].'">';
//            if($tab[$i]['inPDF'] > 0) print img_picto("Dans le PDF",DOL_URL_ROOT."/theme/common/mime/pdf.png","",1);
//            else print img_picto("Pas dans le PDF",DOL_URL_ROOT."/theme/common/mime/notavailable.png","",1);
//            if ($user->rights->affaire->creer) print '</a>';
//
//            print '</td>';

                // Icon update et delete
                print '<td align="center" nowrap '.$bc[$var].'>';
                if ($user->rights->propale->creer)
                {
//TODO>supprimer si droit de modifier le contact
                    print '&nbsp;';
                    print '<a href="contact.php?id='.$affaire->id.'&amp;action=deleteline&amp;lineid='.$tab[$i]['rowid'].'">';
                    print img_delete();
                    print '</a>';
                }
                print '</td>';

                print "</tr>\n";

                $i ++;
            }
        }
    }
print "</table><br/><div style='width:500px'>";

    //PDF "Feuille de route"
       /*
        * Documents generes
        */


        $filename=sanitize_string($affaire->ref);
        $filedir = $conf->synopsisaffaire->dir_output.'/'.sanitize_string($affaire->ref);
        $urlsource="contact.php?id=".$affaire->id;

        $genallowed = ($user->rights->affaire->lire );
        $delallowed = ($user->rights->contrat->creer);

        $var=true;
        require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

        $html = new Form($db);
        $formfile = new FormFile($db);
        $filedir = $conf->synopsisaffaire->dir_output.'/'.sanitize_string($affaire->ref);
        $somethingshown=$formfile->show_documents('affaireContact',$filename,$filedir,$urlsource,$genallowed,$delallowed,$affaire->modelContactPDF_refid);

print "</div>";

?>
