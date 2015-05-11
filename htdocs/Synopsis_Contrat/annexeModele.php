<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 7 mars 2011
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : annexeModele.php
 * GLE-1.2
 */
require_once('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php');
if ($conf->projet->enabled)
    require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
if ($conf->propal->enabled)
    require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
if ($conf->contrat->enabled)
    require_once(DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php");

$langs->load("contracts");
$langs->load("orders");
$langs->load("companies");
$langs->load("bills");
$langs->load("products");
//var_dump($_REQUEST);
// Security check

if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'contrat', $contratid, 'contrat');

$form = new Form($db);
$html = new Form($db);
$js = <<< EOF
    <script>
    jQuery(document).ready(function(){
        jQuery('#form').validate();

    });
    </script>

EOF;
$id = $_REQUEST["id"];
$typeAnnexe = $_REQUEST['typeAnnexe'];

if ($_REQUEST['action'] == 'newModele') {
    $modeleName = addslashes($_REQUEST['modeleName']);
    $annexe = addslashes($_REQUEST['annexe']);
    $ref = addslashes($_REQUEST['ref']);
    $afficheTitre = ($_REQUEST['afficheTitre'] . "x" == "x" ? 0 : 1);
    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf  WHERE ref = '" . $ref . "'";
    $sql = $db->query($requete);
    if ($db->num_rows($sql) > 0) {
        $msg = "Cette r&eacute;f&eacute;rence est d&eacute;j&agrave; utilis&eacute;e";
    } else {
        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf (modeleName, annexe,ref,afficheTitre,type) VALUES ('" . $modeleName . "','" . $annexe . "','" . $ref . "'," . $afficheTitre . ", ".$typeAnnexe.")";
        $sql = $db->query($requete);
        if ($sql && $id > 0) {
            header('Location:annexes.php?id=' . $id);
        } else if ($sql) {
            $msg = "OK";
        } else {
            $msg = "Erreur inconnue";
        }
    }
}
if ($_REQUEST['action'] == 'modifyModele') {
//        require_once('Var_Dump.php');
//        var_dump::display($_REQUEST);

    $modeleName = addslashes($_REQUEST['modeleName']);
    $modele = addslashes($_REQUEST['modele']);
    $annexe = addslashes($_REQUEST['annexe']);
    $afficheTitre = ($_REQUEST['afficheTitre'] . "x" == "x" ? 0 : 1);
    $ref = addslashes($_REQUEST['ref']);
    $id = addslashes($_REQUEST['id']);
    $sql = false;
    if ($_REQUEST['cancel'] == 1) {
        header('Location:annexes.php?id=' . $id);
    } else if ($_REQUEST['effacer'] == 1) {
        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf  WHERE id = '" . $modele . "'";
        $sql = $db->query($requete);
        if ($sql) {
            header('Location:annexes.php?id=' . $id);
            exit;
        } else {
            $msg = "Erreur inconnue";
        }
    } else if ($_REQUEST['clone'] == 1) {
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf  WHERE ref = '" . $ref . "'";
        $sql1 = $db->query($requete);
        if ($db->num_rows($sql1) > 0) {
            $msg = "Cette r&eacute;f&eacute;rence est d&eacute;j&agrave; utilis&eacute;e";
            $sql = false;
        } else {
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf
                                        (modeleName, annexe, ref, afficheTitre, type)
                                 VALUES ('" . $modeleName . "','" . $annexe . "','" . $ref . "'," . $afficheTitre . ", ".$typeAnnexe.")";
            $sql = $db->query($requete);
        }
    } else if (isset($_REQUEST['modForContrat']) && $_REQUEST['modForContrat']) {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_contrat_annexe
                               SET annexe = '" . $annexe . "'
                             WHERE annexe_refid = " . $modele . " AND contrat_refid = " . $_REQUEST['id'];
        $sql = $db->query($requete);
//        die($requete);
    } else {
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf  WHERE ref = '" . $ref . "' AND id <> " . $modele;
        $sql1 = $db->query($requete);
        if ($db->num_rows($sql1) > 0) {
            $msg = "Cette r&eacute;f&eacute;rence est d&eacute;j&agrave; utilis&eacute;e";
            $sql = false;
        } else {
            $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf
                               SET ref = '" . $ref . "',
                                   annexe = '" . $annexe . "',
                                   modeleName = '" . $modeleName . "',
                                   afficheTitre = " . $afficheTitre . "
                             WHERE id = " . $modele;
            $sql = $db->query($requete);
        }
    }
    if ($sql && $id > 0) {
        header('Location:annexes.php?id=' . $id);
    } else if ($sql) {
        $msg = "OK";
    } else if ($msg . "x" == "x") {
        $msg = "Erreur inconnue";
    }
}


llxHeader($js, 'Annexes PDF');

if ($id > 0) {
    $contrat = getContratObj($id);
    $result = $contrat->fetch($id);
    //saveHistoUser($contrat->id, "contrat",$contrat->ref);
    if ($result > 0)
        $result = $contrat->fetch_lines();
    if ($result < 0) {
        dol_print_error($db, $contrat->error);
        exit;
    }
    $head = contract_prepare_head($contrat);
    $head = $contrat->getExtraHeadTab($head);
    $hselected = "annexe";
    dol_fiche_head($head, $hselected, $langs->trans("Contract"));
}

if ($_REQUEST['action'] == "Modify" || $_REQUEST['action'] == "modifyModele") {
    print "<div class='titre'>Modifier le mod&egrave;le d'annexe</div>";
    if ($msg . "x" != "x") {
        print "<div class='error ui-state-error'>" . $msg . "</div>";
    }
    if (!isset($_REQUEST['modForContrat']) || !$_REQUEST['modForContrat'])
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf WHERE id = " . $_REQUEST['modele'];
    else
        $requete = "SELECT *, IF(IFNULL(a.annexe, pdf.annexe) != '', IFNULL(a.annexe, pdf.annexe), pdf.annexe) as annexe FROM " . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf as pdf 
            LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_contrat_annexe as a ON a.`annexe_refid` = pdf.id 
                WHERE annexe_refid = " . $_REQUEST['modele'] ." AND contrat_refid =".$_REQUEST["id"];
//    die($requete);
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    print "<form name='form' id='form'  method='POST' action='annexeModele.php?modele=" . $res->id . "&action=modifyModele" . ($id > 0 ? "&id=" . $id : "") . "'>";
    print "<table width=100% cellpadding=15>";
    print "<input type='hidden' name='modForContrat' value='" . (isset($_REQUEST['modForContrat']) && $_REQUEST['modForContrat']) . "'/>";
    print "<input type='hidden' name='typeAnnexe' value='" . $typeAnnexe . "'/>";
    if (!isset($_REQUEST['modForContrat']) || !$_REQUEST['modForContrat']) {
        print "<tr><th class='ui-widget-header ui-state-default'>Nom du mod&egrave;le";
        print "    <td class='ui-widget-content'><input class='required' name='modeleName' value='" . $res->modeleName . "'>";
        print "<tr><th class='ui-widget-header ui-state-default'>Ref";
        print "    <td class='ui-widget-content'><input class='required' name='ref' value='" . $res->ref . "'>";
        print "<tr><th class='ui-widget-header ui-state-default'>Affiche le titre";
        print "    <td class='ui-widget-content'><input name='afficheTitre' type='checkbox'  " . ($res->afficheTitre == 1 ? 'CHECKED' : "") . ">";
    }
    print "<tr><th class='ui-widget-header ui-state-default'>Contenu";
    print "    <td class='ui-widget-content'><textarea style='width: 100%; min-height: 8em;' class='required'  name='annexe'>" . $res->annexe . "</textarea>";
    print "<tr><th valign=top class='ui-widget-header ui-state-default'>Aide";
    print "    <td class='ui-widget-content'>";
    print "<br/><a href='aide-annexe.php?id=" . $contrat->id . "' target='_blank'><table><tr><td><span class='ui-icon ui-icon-extlink'></span></td><td>Ouvrir dans une nouvelle fen&ecirc;tre</table></a><br/><br/>";
    
    //require_once('Var_Dump.php');
    getHelp($contrat, $user, $mysoc);
//        require_once('Var_Dump.php');
//        Var_Dump::Display($contrat->societe);


    print "<tr><th align=right colspan=2 class='ui-widget-header'>";
    if ($id > 0)
        print "<button name='cancel' class='butAction'>Annuler</button>";
    print "<button class='butAction'>Modifier</button>";
    print "<button name='clone' value='1' class='butAction'>Cloner</button>";
    print "<button name='effacer' value='1' class='butActionDelete'>Effacer</button>";
    print "</table>";
} else {
    print "<div class='titre'>Nouveau mod&egrave;le d'annexe</div>";
    if ($msg . "x" != "x") {
        print "<div class='error ui-state-error'>" . $msg . "</div>";
    }

    print "<form name='form' id='form'  method='POST' action='annexeModele.php?action=newModele" . ($id > 0 ? "&id=" . $id : "") . "'>";
    print "<input type='hidden' name='typeAnnexe' value='" . $typeAnnexe . "'/>";
    print "<table width=100% cellpadding=15>";
    print "<tr><th class='ui-widget-header ui-state-default'>Nom du mod&egrave;le";
    print "    <td class='ui-widget-content'><input class='required' name='modeleName' value='" . $_REQUEST["modeleName"] . "'>";
    print "<tr><th class='ui-widget-header ui-state-default'>Ref";
    print "    <td class='ui-widget-content'><input class='required' name='ref' value='" . $_REQUEST["ref"] . "'>";
    print "<tr><th class='ui-widget-header ui-state-default'>Affiche le titre";
    print "    <td class='ui-widget-content'><input name='afficheTitre' type='checkbox'  " . ($_REQUEST["afficheTitre"] . "x" != "x" ? 'CHECKED' : "") . ">";
    print "<tr><th class='ui-widget-header ui-state-default'>Contenu";
    print "    <td class='ui-widget-content'><textarea style='width: 100%; min-height: 8em;' class='required'  name='annexe'>" . $_REQUEST["annexe"] . "</textarea>";
    print "<tr><th align=right colspan=2 class='ui-widget-header'><button class='butAction'>Valider</button>";
    print "</table>";
    getHelp($contrat, $user, $mysoc);
}


llxfooter();

function getHelp($contrat, $user, $mysoc){
    $remCode = array();
    $lastType = false;
    print "<table width=100%>";
    print "<tr><th class='ui-widget-header ui-state-hover'>Style<th class='ui-widget-header'>Ajouter en debut de ligne";
    print "<tr><td>Gras<td>&#139;g&#155;";
    print "<tr><td>Italic<td>&#139;i&#155;";
    print "<tr><td>Souligner<td>&#139;s&#155;";
    print "<tr><th class='ui-widget-header'>Variable Annexe<th class='ui-widget-header'>Libelle<th class='ui-widget-header'>Example";
//manque cp ville tel email fax nom prenom civility
    print "<tr><th class='ui-widget-header ui-state-hover' colspan=3>Utilisateur";

    print "<tr><td>User-fullname<td>Mon nom complet<td>" . $user->getFullName($langs);
    print "<tr><td>User-nom<td>Mon nom<td>" . $user->lastname;
    print "<tr><td>User-prenom<td>Mon pr&eacute;nom<td>" . $user->firstname;
    print "<tr><td>User-email<td>Mon email<td>" . $user->email;
    print "<tr><td>User-office_phone<td>Mon t&eacute;l&eacute;phone<td>" . $user->office_phone;
    print "<tr><td>User-user_mobile<td>Mon mobile<td>" . $user->user_mobile;
    print "<tr><td>User-office_fax<td>Mon fax<td>" . $user->office_fax;

    print "<tr><th class='ui-widget-header ui-state-hover' colspan=3>Ma soci&eacute;t&eacute;";

    print "<tr><td>Mysoc-nom<td>Nom de ma soci&eacute;t&eacute;<td>" . $mysoc->nom;
    print "<tr><td>Mysoc-adresse_full<td>Adresse de ma soci&eacute;t&eacute;<td>" . $mysoc->address."<br/>".$mysoc->zip." ".$mysoc->town;
    print "<tr><td>Mysoc-adresse<td>Adresse de ma soci&eacute;t&eacute;<td>" . $mysoc->address;
    print "<tr><td>Mysoc-cp<td>Code postal de ma soci&eacute;t&eacute;<td>" . $mysoc->zip;
    print "<tr><td>Mysoc-ville<td>Ville de ma soci&eacute;t&eacute;<td>" . $mysoc->town;
    print "<tr><td>Mysoc-tel<td>T&eacute;l&eacute;phone de ma soci&eacute;t&eacute;<td>" . $mysoc->phone;
    print "<tr><td>Mysoc-fax<td>Fax de ma soci&eacute;t&eacute;<td>" . $mysoc->fax;
    print "<tr><td>Mysoc-email<td>Email de ma soci&eacute;t&eacute;<td>" . $mysoc->email;
    print "<tr><td>Mysoc-url<td>URL de ma soci&eacute;t&eacute;<td>" . $mysoc->url;
    print "<tr><td>Mysoc-rcs<td>RCS de ma soci&eacute;t&eacute;<td>" . $mysoc->rcs;
    print "<tr><td>Mysoc-siren<td>SIREN de ma soci&eacute;t&eacute;<td>" . $mysoc->siren;
    print "<tr><td>Mysoc-siret<td>SIRET de ma soci&eacute;t&eacute;<td>" . $mysoc->siret;
    print "<tr><td>Mysoc-ape<td>Code APE de ma soci&eacute;t&eacute;<td>" . $mysoc->ape;
    print "<tr><td>Mysoc-tva_intra<td>TVA Intra de ma soci&eacute;t&eacute;<td>" . $mysoc->tva_intra;
    print "<tr><td>Mysoc-capital<td>Capital de ma soci&eacute;t&eacute;<td>" . $mysoc->capital;



    print "<tr><th class='ui-widget-header ui-state-hover' colspan=3>Client";
    print "<tr><td>Soc-titre<td>Titre de la soci&eacute;t&eacute; du client<td>" . $contrat->societe->titre;
    print "<tr><td>Soc-nom<td>Nom de la soci&eacute;t&eacute; du client<td>" . $contrat->societe->nom;
    print "<tr><td>Soc-adresse_full<td>Adresse de la soci&eacute;t&eacute; du client<td>" . $contrat->societe->address."<br/>".$contrat->societe->zip." ".$contrat->societe->town;
    print "<tr><td>Soc-adresse<td>Adresse de la soci&eacute;t&eacute; du client<td>" . $contrat->societe->adresse;
    print "<tr><td>Soc-cp<td>Code postal de la soci&eacute;t&eacute; du client<td>" . $contrat->societe->cp;
    print "<tr><td>Soc-ville<td>Ville de la soci&eacute;t&eacute; du client<td>" . $contrat->societe->ville;
    print utf8_encodeRien("<tr><td>Soc-tel<td>N° tel de la soci&eacute;t&eacute; du client<td>") . $contrat->societe->tel;
    print utf8_encodeRien("<tr><td>Soc-fax<td>N° fax de la soci&eacute;t&eacute; du client<td>") . $contrat->societe->dax;
    print "<tr><td>Soc-email<td>Email de la soci&eacute;t&eacute; du client<td>" . $contrat->societe->email;
    print "<tr><td>Soc-url<td>URL de la soci&eacute;t&eacute; du client<td>" . $contrat->societe->url;
    print "<tr><td>Soc-siren<td>SIREN de la soci&eacute;t&eacute; du client<td>" . $contrat->societe->siren;
    print "<tr><td>Soc-siret<td>SIRET de la soci&eacute;t&eacute; du client<td>" . $contrat->societe->siret;
    print "<tr><td>Soc-code_client<td>Code client de la soci&eacute;t&eacute; du client<td>" . $contrat->societe->code_client;
    print "<tr><td>Soc-note<td>Note de la soci&eacute;t&eacute; du client<td>" . $contrat->societe->note;
    print "<tr><td>Soc-ref<td>Ref de la soci&eacute;t&eacute; du client<td>" . $contrat->societe->ref;

    print "<tr><th class='ui-widget-header ui-state-hover' colspan=3>Contrat";

    print "<tr><td>Contrat-date_contrat<td>Date contrat<td>" . date('d/m/Y', $contrat->date_contrat);
    print "<tr><td>Contrat-ref<td>Ref contrat<td>" . $contrat->ref;
    print "<tr><td>Contrat-note_public<td>Note public contrat<td>" . $contrat->note_public;
    
    print "<tr><th class='ui-widget-header ui-state-hover' colspan=3>Ligne contrat";
    print "<tr><td>Ligne-date_fin<td>Date de fin de validit&eacute; de la ligne<td>";
    print "<tr><td>Ligne-description<td>Description de la ligne<td>";
    print "<tr><td>Ligne-phrase_delai<td>Phrase stipulant le nombre de visite par an<td>";
    print "<tr><td>Ligne-qte<td>Quantité<td>";
    print "<tr><td>Date-Di<td>Calendrier des DI<td>";

    print "<tr><th class='ui-widget-header ui-state-hover' colspan=3>Autre";
    print "<tr><td>DateDuJour<td>Date du jour<td>" . date('d/m/Y');

    print "<tr><th class='ui-widget-header ui-state-hover' colspan=3>Contacts contrat";

    $arr['fullname'] = 'Nom complet';
    $arr['cp'] = 'Code postal';
    $arr['ville'] = 'Ville';
    $arr['email'] = 'Email';
    $arr['fax'] = utf8_encodeRien('N° fax');
    $arr['tel'] = utf8_encodeRien('N° tel');
    $arr['civility'] = 'Civilit&eacute;';
    $arr['nom'] = 'Nom';
    $arr['prenom'] = 'Pr&eacute;om';

    foreach ($contrat->list_all_valid_contacts() as $key => $val) {
        foreach (array('fullname', 'civility', 'nom', 'prenom', 'cp', 'ville', 'email', 'tel', 'fax') as $val0) {
            if (in_array("Contact-" . $val['source'] . "-" . $val['code'] . "-" . $val0, $remCode))
                continue;
            $remCode[] = "Contact-" . $val['source'] . "-" . $val['code'] . "-" . $val0;
            if (!$lastType) {
                $lastType = $val['libelle'];
                print "<tr><th class='ui-widget-headet ui-state-default' colspan=3>" . $val['libelle'];
            } else if ($lastType != $val['libelle']) {
                print "<tr><th class='ui-widget-headet ui-state-default' colspan=3>" . $val['libelle'];
            }
            $lastType = $val['libelle'];
            print "<tr><td>Contact-" . $val['source'] . "-" . $val['code'] . "-" . $val0;
            print "    <td>" . $arr[$val0];
            print "    <td>" . $val[$val0];
        }
    }

    print "</table>";
}
?>
