<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 6 avr. 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : aide-annexe.php
  * GLE-1.2
  */

    require_once('pre.inc.php');
    require_once(DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php');

    if ($conf->projet->enabled)  require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
    if ($conf->propal->enabled)  require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
    if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

    $id = $_REQUEST["id"];


    top_menu("", "Aide annexe contrat", "",1,false);
    if ($id > 0){
        $contrat=getContratObj($id);
        $result=$contrat->fetch($id);
        //saveHistoUser($contrat->id, "contrat",$contrat->ref);
        if ($result > 0) $result=$contrat->fetch_lines();
        if ($result < 0)
        {
            dol_print_error($db,$contrat->error);
            exit;
        }

    }
    print "<table width=100%>";
    //require_once('Var_Dump.php');
    $remCode = array();
    $lastType = false;
    print "<tr><th class='ui-widget-header'>Variable Annexe<th class='ui-widget-header'>Libelle<th class='ui-widget-header'>Example";
//manque cp ville tel email fax nom prenom civility
    print "<tr><th class='ui-widget-header ui-state-hover' colspan=3>Utilisateur";

    print "<tr><td>User-fullname<td>Mon nom complet<td>".$user->getFullName($langs);
    print "<tr><td>User-nom<td>Mon nom<td>".$user->lastname;
    print "<tr><td>User-prenom<td>Mon pr&eacute;nom<td>".$user->firstname;
    print "<tr><td>User-email<td>Mon email<td>".$user->email;
    print "<tr><td>User-office_phone<td>Mon t&eacute;l&eacute;phone<td>".$user->office_phone;
    print "<tr><td>User-user_mobile<td>Mon mobile<td>".$user->user_mobile;
    print "<tr><td>User-office_fax<td>Mon fax<td>".$user->office_fax;

    print "<tr><th class='ui-widget-header ui-state-hover' colspan=3>Ma soci&eacute;t&eacute;";

    print "<tr><td>Mysoc-nom<td>Nom de ma soci&eacute;t&eacute;<td>".$mysoc->nom;
    print "<tr><td>Mysoc-adresse_full<td>Adresse de ma soci&eacute;t&eacute;<td>".$mysoc->address."<br/>".$mysoc->zip." ".$mysoc->town;
    print "<tr><td>Mysoc-adresse<td>Adresse de ma soci&eacute;t&eacute;<td>".$mysoc->address;
    print "<tr><td>Mysoc-cp<td>Code postal de ma soci&eacute;t&eacute;<td>".$mysoc->zip;
    print "<tr><td>Mysoc-ville<td>Ville de ma soci&eacute;t&eacute;<td>".$mysoc->town;
    print "<tr><td>Mysoc-tel<td>T&eacute;l&eacute;phone de ma soci&eacute;t&eacute;<td>".$mysoc->phone;
    print "<tr><td>Mysoc-fax<td>Fax de ma soci&eacute;t&eacute;<td>".$mysoc->fax;
    print "<tr><td>Mysoc-email<td>Email de ma soci&eacute;t&eacute;<td>".$mysoc->email;
    print "<tr><td>Mysoc-url<td>URL de ma soci&eacute;t&eacute;<td>".$mysoc->url;
    print "<tr><td>Mysoc-rcs<td>RCS de ma soci&eacute;t&eacute;<td>".$mysoc->rcs;
    print "<tr><td>Mysoc-siren<td>SIREN de ma soci&eacute;t&eacute;<td>".$mysoc->siren;
    print "<tr><td>Mysoc-siret<td>SIRET de ma soci&eacute;t&eacute;<td>".$mysoc->siret;
    print "<tr><td>Mysoc-ape<td>Code APE de ma soci&eacute;t&eacute;<td>".$mysoc->ape;
    print "<tr><td>Mysoc-tva_intra<td>TVA Intra de ma soci&eacute;t&eacute;<td>".$mysoc->tva_intra;
    print "<tr><td>Mysoc-capital<td>Capital de ma soci&eacute;t&eacute;<td>".$mysoc->capital;



    print "<tr><th class='ui-widget-header ui-state-hover' colspan=3>Client";
    print "<tr><td>Soc-titre<td>Titre de la soci&eacute;t&eacute; du client<td>".$contrat->societe->titre;
    print "<tr><td>Soc-nom<td>Nom de la soci&eacute;t&eacute; du client<td>".$contrat->societe->nom;
    print "<tr><td>Soc-adresse_full<td>Adresse de la soci&eacute;t&eacute; du client<td>".$contrat->societe->address."<br/>".$contrat->societe->zip." ".$contrat->societe->town;
    print "<tr><td>Soc-adresse<td>Adresse de la soci&eacute;t&eacute; du client<td>".$contrat->societe->adresse;
    print "<tr><td>Soc-cp<td>Code postal de la soci&eacute;t&eacute; du client<td>".$contrat->societe->cp;
    print "<tr><td>Soc-ville<td>Ville de la soci&eacute;t&eacute; du client<td>".$contrat->societe->ville;
    print utf8_decode("<tr><td>Soc-tel<td>N° tel de la soci&eacute;t&eacute; du client<td>").$contrat->societe->tel;
    print utf8_decode("<tr><td>Soc-fax<td>N° fax de la soci&eacute;t&eacute; du client<td>").$contrat->societe->dax;
    print "<tr><td>Soc-email<td>Email de la soci&eacute;t&eacute; du client<td>".$contrat->societe->email;
    print "<tr><td>Soc-url<td>URL de la soci&eacute;t&eacute; du client<td>".$contrat->societe->url;
    print "<tr><td>Soc-siren<td>SIREN de la soci&eacute;t&eacute; du client<td>".$contrat->societe->siren;
    print "<tr><td>Soc-siret<td>SIRET de la soci&eacute;t&eacute; du client<td>".$contrat->societe->siret;
    print "<tr><td>Soc-code_client<td>Code client de la soci&eacute;t&eacute; du client<td>".$contrat->societe->code_client;
    print "<tr><td>Soc-note<td>Note de la soci&eacute;t&eacute; du client<td>".$contrat->societe->note;
    print "<tr><td>Soc-ref<td>Ref de la soci&eacute;t&eacute; du client<td>".$contrat->societe->ref;

    print "<tr><th class='ui-widget-header ui-state-hover' colspan=3>Contrat";

    print "<tr><td>Contrat-date_contrat<td>Date contrat<td>".date('d/m/Y',$contrat->date_contrat);
    print "<tr><td>Contrat-ref<td>Ref contrat<td>".$contrat->ref;
    print "<tr><td>Contrat-note_public<td>Note public contrat<td>".$contrat->note_public;

    print "<tr><th class='ui-widget-header ui-state-hover' colspan=3>Autre";
    print "<tr><td>DateDuJour<td>Date du jour<td>".date('d/m/Y');

    print "<tr><th class='ui-widget-header ui-state-hover' colspan=3>Contacts contrat";

    $arr['fullname'] = 'Nom complet';
    $arr['cp'] = 'Code postal';
    $arr['ville'] = 'Ville';
    $arr['email'] = 'Email';
    $arr['fax'] = utf8_decode('N° fax');
    $arr['tel'] = utf8_decode('N° tel');
    $arr['civility'] = 'Civilit&eacute;';
    $arr['nom'] = 'Nom';
    $arr['prenom'] = 'Pr&eacute;om';

    foreach($contrat->list_all_valid_contacts() as $key=>$val)
    {
        foreach(array('fullname','civility','nom','prenom','cp','ville','email','tel','fax') as $val0)
        {
            if (in_array($remCode,"Contact-".$val['source']."-".$val['code']."-".$val0)) continue;
            $remCode[]="Contact-".$val['source']."-".$val['code']."-".$val0;
            if (!$lastType)
            {
                $lastType = $val['libelle'];
                 print "<tr><th class='ui-widget-headet ui-state-default' colspan=3>".$val['libelle'];
            } else if ($lastType != $val['libelle'])
            {
                print "<tr><th class='ui-widget-headet ui-state-default' colspan=3>".$val['libelle'];
            }
            $lastType = $val['libelle'];
            print "<tr><td>Contact-".$val['source']."-".$val['code']."-".$val0;
            print "    <td>".$arr[$val0];
            print "    <td>".$val['obj']->$val0;
        }
    }

    print "</table>";
?>