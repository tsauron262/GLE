<?php
/*
 * @author 	LVSinformatique <contact@lvsinformatique.com>
 * @copyright  	2021 LVSInformatique
 * This source file is subject to a commercial license from LVSInformatique
 * Use, copy or distribution of this source file without written
 * license agreement from LVSInformatique is strictly forbidden.
 */

require_once '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT. '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
include_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/client.class.php';


$companystatic = new Societe($db);
$form = new Form($db);
$confirm = GETPOST('confirm', 'alpha');
if (GETPOST('action') == 'confirm_merge' && $confirm == 'yes') {
    //print 'fusion from '.GETPOST('idF'). ' To '.GETPOST("idT");
    $error = 0;
    $soc_origin_id = GETPOST('idF', 'int');
    $soc_origin = new Societe($db);
    $object = new Societe($db);
    if (GETPOST('idT', 'int') > 0)
        $object->fetch(GETPOST('idT', 'int'));
    if ($soc_origin_id <= 0) {
        $langs->load('errors');
	setEventMessages($langs->trans('ErrorThirdPartyIdIsMandatory', $langs->transnoentitiesnoconv('MergeOriginThirdparty')), null, 'errors');
    } else {
	if (!$error && $soc_origin->fetch($soc_origin_id) < 1) {
            setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
            $error++;
	}

	if (!$error) {
            $db->begin();
            $object->client = $object->client | $soc_origin->client;
            $listofproperties = array(
		'address', 'zip', 'town', 'state_id', 'country_id', 'phone', 'phone_pro', 'fax', 'email', 'skype', 'twitter', 'facebook', 'linkedin', 'socialnetworks', 'url', 'barcode',
		'idprof1', 'idprof2', 'idprof3', 'idprof4', 'idprof5', 'idprof6',
		'tva_intra', 'effectif_id', 'forme_juridique', 'remise_percent', 'remise_supplier_percent', 'mode_reglement_supplier_id', 'cond_reglement_supplier_id', 'name_bis',
		'stcomm_id', 'outstanding_limit', 'price_level', 'parent', 'default_lang', 'ref', 'ref_ext', 'import_key', 'fk_incoterms', 'fk_multicurrency',
		'code_client', 'code_fournisseur', 'code_compta', 'code_compta_fournisseur',
		'model_pdf', 'fk_projet'
            );
            foreach ($listofproperties as $property)
            {
                if (empty($object->$property))
                    $object->$property = $soc_origin->$property;
            }

            $listofproperties = array('note_public', 'note_private');
            foreach ($listofproperties as $property)
            {
                $object->$property = dol_concatdesc($object->$property, $soc_origin->$property);
            }

            if (is_array($soc_origin->array_options)) {
                foreach ($soc_origin->array_options as $key => $val)
		{
                    if (empty($object->array_options[$key]))
                        $object->array_options[$key] = $val;
		}
            }

            $static_cat = new Categorie($db);

            $custcats_ori = $static_cat->containing($soc_origin->id, 'customer', 'id');
            $custcats = $static_cat->containing($object->id, 'customer', 'id');
            $custcats = array_merge($custcats, $custcats_ori);
            $object->setCategories($custcats, 'customer');

            $suppcats_ori = $static_cat->containing($soc_origin->id, 'supplier', 'id');
            $suppcats = $static_cat->containing($object->id, 'supplier', 'id');
            $suppcats = array_merge($suppcats, $suppcats_ori);
            $object->setCategories($suppcats, 'supplier');

            if ($soc_origin->code_client == $object->code_client
                || $soc_origin->code_fournisseur == $object->code_fournisseur
		|| $soc_origin->barcode == $object->barcode) {
                    dol_syslog("We clean customer and supplier code so we will be able to make the update of target");
                    $soc_origin->code_client = '';
                    $soc_origin->code_fournisseur = '';
                    $soc_origin->barcode = '';
                    $soc_origin->update($soc_origin->id, $user, 0, 1, 1, 'merge');
            }
            if ($soc_origin->code_client && (substr($object->code_client, 0, 5) == 'eshop' || !$object->code_client)) {
                $object->code_client = $soc_origin->code_client;
            }
            $result = $object->update($object->id, $user, 0, 1, 1, 'merge');
            if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
		$error++;
            }

            if (!$error) {
		$objects = array(
                    'Adherent' => '/adherents/class/adherent.class.php',
                    'Don' => '/don/class/don.class.php',
                    'Societe' => '/societe/class/societe.class.php',
                    //'Categorie' => '/categories/class/categorie.class.php',
                    'ActionComm' => '/comm/action/class/actioncomm.class.php',
                    'Propal' => '/comm/propal/class/propal.class.php',
                    'Commande' => '/commande/class/commande.class.php',
                    'Facture' => '/compta/facture/class/facture.class.php',
                    'FactureRec' => '/compta/facture/class/facture-rec.class.php',
                    'LignePrelevement' => '/compta/prelevement/class/ligneprelevement.class.php',
                    'Mo' => '/mrp/class/mo.class.php',
                    'Contact' => '/contact/class/contact.class.php',
                    'Contrat' => '/contrat/class/contrat.class.php',
                    'Expedition' => '/expedition/class/expedition.class.php',
                    'Fichinter' => '/fichinter/class/fichinter.class.php',
                    'CommandeFournisseur' => '/fourn/class/fournisseur.commande.class.php',
                    'FactureFournisseur' => '/fourn/class/fournisseur.facture.class.php',
                    'SupplierProposal' => '/supplier_proposal/class/supplier_proposal.class.php',
                    'ProductFournisseur' => '/fourn/class/fournisseur.product.class.php',
                    'Delivery' => '/delivery/class/delivery.class.php',
                    'Product' => '/product/class/product.class.php',
                    'Project' => '/projet/class/project.class.php',
                    'Ticket' => '/ticket/class/ticket.class.php',
                    'User' => '/user/class/user.class.php'
                );

		foreach ($objects as $object_name => $object_file)
		{
                    require_once DOL_DOCUMENT_ROOT . $object_file;
                    if (!$error && !$object_name::replaceThirdparty($db, $soc_origin->id, $object->id)) {
			$error++;
			setEventMessages($db->lasterror(), null, 'errors');
			break;
                    }
		}
            }

            if (!$error) {
                $reshook = $hookmanager->executeHooks('replaceThirdparty',
                    array(
                        'soc_origin' => $soc_origin->id,
                        'soc_dest' => $object->id
                    ),
                    $object,
                    $action);

		if ($reshook < 0) {
                    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
                    $error++;
		}
            }

            if (!$error) {
		$object->context = array('merge'=>1, 'mergefromid'=>$soc_origin->id);
                $result = $object->call_trigger('COMPANY_MODIFY', $user);
		if ($result < 0) {
                    setEventMessages($object->error, $object->errors, 'errors');
                    $error++;
                }
            }
            if (!$error) {
		if ($soc_origin->delete($soc_origin->id, $user) < 1) {
                    $error++;
		}
            }
            if (!$error) {
		setEventMessages($langs->trans('ThirdpartiesMergeSuccess'), null, 'mesgs');
		$db->commit();
            } else {
                $langs->load("errors");
		setEventMessages($langs->trans('ErrorsThirdpartyMerge'), null, 'errors');
		$db->rollback();
            }
	}
    }
}

$search_customer_code2 = GETPOST('search_customer_code2');
$search_nom2 = GETPOST('search_nom2');
$search_customer_code1 = GETPOST('search_customer_code1');
$search_nom1 = GETPOST('search_nom1');
if (!$search_customer_code2 && !$search_nom2)
    $limit2 = $conf->liste_limit;
if (!$search_customer_code1 && !$search_nom1)
    $limit1 = $conf->liste_limit;
$sql0 = "SELECT * "
    . " FROM ".MAIN_DB_PREFIX."const "
    . " WHERE name LIKE 'CYBEROFFICE_SHOP%'";
$req0 = $db->query($sql0);
if ($req0) {
    while ($obj0 = $db->fetch_object($req0))
    {
        if ($eshop)
            $eshop.=',';
        $eshop.= '"P'.$obj0->value.'-"';
    }
}

$sql = "SELECT s.rowid, s.nom as name, s.name_alias, s.barcode, s.address, s.town, s.zip, s.datec, s.code_client, s.code_fournisseur, s.logo, s.entity"
    . ", s.fk_stcomm as stcomm_id, s.fk_prospectlevel, s.prefix_comm, s.client, s.fournisseur, s.canvas, s.status as status, s.email, s.phone, s.fax, s.url, s.siren as idprof1, s.siret as idprof2, s.ape as idprof3, s.idprof4 as idprof4, s.idprof5 as idprof5, s.idprof6 as idprof6, s.tva_intra, s.fk_pays, s.tms as date_update, s.datec as date_creation, s.code_compta, s.code_compta_fournisseur, s.parent as fk_parent"
    . ",s.import_key"
    . ", country.code as country_code"
    . ", country.label as country_label"
    . ", state.code_departement as state_code, state.nom as state_name"
    . ", region.code_region as region_code, region.nom as region_name"
    . ", s.entity "
    . " FROM ".MAIN_DB_PREFIX."societe as s "
    . " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays) "
    . " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement) "
    . " LEFT JOIN ".MAIN_DB_PREFIX."c_regions as region on (region. code_region = state.fk_region) "
    . " WHERE s.entity IN (2,3,1) AND s.client IN (1,3) AND (s.status IN (1)) "
    . " AND SUBSTR(s.import_key,1,4) IN (".$eshop.")"
    . " ORDER BY s.nom ASC ";
$sql .= $db->plimit($limit1);
$req1 = $db->query($sql);
if ($req1) {
    $list1 = '<tr class="liste_titre_filter">';
    $list1.= '<td class="liste_titre">';
    $list1.= '<input class="flat searchstring maxwidth75imp" type="text" name="search_customer_code1" id="search_customer_code1" value="'.dol_escape_htmltag($search_customer_code1).'">';
    $list1.= '</td>';
    $list1.= '<td class="liste_titre" colspan="2" align="center">';
    $list1.= '<input class="flat searchstring maxwidth75imp" type="text" name="search_nom1" id="search_nom1" value="'.dol_escape_htmltag($search_nom1).'">';
    $list1.= '</td>';
    $list1.= '<td class="liste_titre center">';
    $list1.= '<span class="fa fa-search" onclick="search(1);" style="width: 30%;"></span>';
    $list1.= '<img src="ajax-loader.gif" alt="loader" id="ajax-loader1" style="display:none"/>';
    $list1.= '<span class="fa fa-search-plus" onclick="help(1);" style="width: 30%;" title="'.$langs->trans("advanced search").'"></span>';
    $list1.= '</td>';
    $list1.= '</tr>';
    $list1.= '<tr class="liste_titre"><th nowrap>code_client</th><th>name</th><th align="right">name_alias</th><th align="right">import_key</th></tr>';
    $list1.= "\r\n".'</table>'."\r\n".'<table class="liste result1">'."\r\n";
    $list1.= '<tr class="oddeven"><td colspan="4" align="center" style="color: red;">Limited to '.$db->num_rows($req1).' rows</td></tr>';
    while ($obj = $db->fetch_object($req1))
    {
        $title = $obj->name_alias.'<br>';
        $title.= $obj->name.'<br>';
        $title.= $obj->address.'<br>';
        $title.= $obj->zip.' '.$obj2->town.'<br>';
        $title.= $obj->email.'<br>';
        $title.= $obj->phone.'<br>';
        $tooltip = '<a href="#" class="classfortooltip" alt="'.dol_escape_htmltag($title, 1).'" title="'.dol_escape_htmltag($title, 1).'">'.$obj->code_client.'</a>';
        $list1.= '<tr id="T'.$obj->rowid.'" onclick="ValuesTo(this.id)" class="oddeven"><td nowrap>'.$tooltip.'</td><td>'.$obj->name.'</td><td>'.$obj->name_alias.'</td><td>'.$obj->import_key.'</td></tr>'."\r\n";
    }
    //$list1.= "\r\n".'</table>'."\r\n";
}
$sql2 = "SELECT s.rowid, s.nom as name, s.name_alias, s.barcode, s.address, s.town, s.zip, s.datec, s.code_client, s.code_fournisseur, s.logo, s.entity"
    . ", s.fk_stcomm as stcomm_id, s.fk_prospectlevel, s.prefix_comm, s.client, s.fournisseur, s.canvas, s.status as status, s.email, s.phone, s.fax, s.url, s.siren as idprof1, s.siret as idprof2, s.ape as idprof3, s.idprof4 as idprof4, s.idprof5 as idprof5, s.idprof6 as idprof6, s.tva_intra, s.fk_pays, s.tms as date_update, s.datec as date_creation, s.code_compta, s.code_compta_fournisseur, s.parent as fk_parent"
    . ",s.import_key"
    . ", country.code as country_code"
    . ", country.label as country_label"
    . ", state.code_departement as state_code, state.nom as state_name"
    . ", region.code_region as region_code, region.nom as region_name"
    . ", s.entity "
    . " FROM ".MAIN_DB_PREFIX."societe as s "
    . " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays) "
    . " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement) "
    . " LEFT JOIN ".MAIN_DB_PREFIX."c_regions as region on (region. code_region = state.fk_region) "
    . " WHERE s.entity IN (2,3,1) AND s.client IN (1,3) AND (s.status IN (1))"
    . " AND (SUBSTR(s.import_key,1,4) NOT IN (".$eshop.") OR s.import_key IS NULL)"
    . " ORDER BY s.nom ASC ";
$sql2 .= $db->plimit($limit2);
$req2 = $db->query($sql2);
if ($req2) {
    $list2 = '<tr class="liste_titre_filter">';
    $list2.= '<td class="liste_titre">';
    $list2.= '<input class="flat searchstring maxwidth75imp" type="text" name="search_customer_code2" id="search_customer_code2" value="'.dol_escape_htmltag($search_customer_code2).'">';
    $list2.= '</td>';
    $list2.= '<td class="liste_titre" colspan="2" align="center">';
    $list2.= '<input class="flat searchstring maxwidth75imp" type="text" name="search_nom2" id="search_nom2" value="'.dol_escape_htmltag($search_nom2).'">';
    $list2.= '</td>';
    $list2.= '<td class="liste_titre center">';
    $list2.= '<span class="fa fa-search" onclick="search(2);" style="width: 30%;"></span>';
    $list2.= '<img src="ajax-loader.gif" alt="loader" id="ajax-loader2" style="display:none"/>';
    $list2.= '<span class="fa fa-search-plus" onclick="help(2);" style="width: 30%;" title="'.$langs->trans("advanced search").'"></span>';
    $list2.= '</td>';
    $list2.= '</tr>';
    $list2.= '<tr class="liste_titre"><th nowrap>code_client</th><th>name</th><th align="right">name_alias</th><th align="right">import_key</th></tr>';
    $list2.= "\r\n".'</table>'."\r\n".'<table class="liste result2">'."\r\n";
    $list2.= '<tr class="oddeven"><td colspan="4" align="center" style="color: red;">Limited to '.$db->num_rows($req2).' rows</td></tr>';
    while ($obj2 = $db->fetch_object($req2))
    {
        $title = $obj2->name_alias.'<br>';
        $title.= $obj2->name.'<br>';
        $title.= $obj2->address.'<br>';
        $title.= $obj2->zip.' '.$obj2->town.'<br>';
        $title.= $obj2->email.'<br>';
        $title.= $obj2->phone.'<br>';
        //$companystatic->fetch($obj2->rowid);
        //$toto= $companystatic->getNomUrl(1, '', 100, 0, 1);
        $tooltip = '<a href="#" class="classfortooltip" alt="'.dol_escape_htmltag($title, 1).'" title="'.dol_escape_htmltag($title, 1).'">'.$obj2->code_client.'</a>';
        
        $list2.= '<tr id="F'.$obj2->rowid.'" onclick="ValuesFrom(this.id)" class="oddeven"><td nowrap>'.$tooltip.'</td><td>'.$obj2->name.'</td><td>'.$obj2->name_alias.'</td><td>'.$obj2->import_key.'</td></tr>'."\r\n";
    }
    //$list2.= "\r\n".'</table>'."\r\n";
}

$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $langs->trans("List_Cyber"), $help_url);
if (GETPOST('action')=="fusion") {
    $formquestion = array();
    print $form->formconfirm($_SERVER["PHP_SELF"]."?idT=".GETPOST("idT").'&idF='.GETPOST("idF"), $langs->trans("MergeThirdparties"), $langs->trans("ConfirmMergeThirdparties"), "confirm_merge", $formquestion, 'no', 1, 250);
}
print "\r\n".'<table style="height:100%">'."\r\n";
    print '<tr style="height:10%">';
        print '<td id="resultF" style="text-align: right;font-size: larger;font-weight: bold;">';
        print '</td>';
        print '<td id="result" style="text-align: center;">';
            print '<form name="fusion" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
            print '<input type="hidden" name="action" value="fusion">';
            print '<input id="idF" name ="idF" value="0" style="display:none"><input id="idT" name="idT" value="0" style="display:none">';
            print '<input id="fusion" type="submit" class="button" style="display:none" value="'.$langs->trans('Fusionner').'">';
            print '</form>';
        print '</td>';
        print '<td id="resultT" style="text-align: left;font-size: larger;font-weight: bold;">';
        print '</td>';
    print '</tr>'."\r\n";
    print '<tr>';
        print '<td width="45%">';
            //print '<form name="formfilter2" style="height:80vh;">';
            print '<div style="overflow:scroll; border:#000000 1px solid;height: 100%;">';
                print "\r\n".'<table class="liste">'."\r\n";
                print $list2;
                print "\r\n".'</table>'."\r\n";
            print '</div>';
            //print '</form>';
        print '</td>';
        print '<td width="10%">';
            print '<i class="far fa-arrow-alt-circle-right fa-w-14 fa-9x"></i>';
        print '</td>';
        print '<td width="45%">';
            //print '<form name="formfilter1" style="height:80vh;">';
            print '<div style="overflow:scroll; border:#000000 1px solid; height: 100%;">';
                print "\r\n".'<table class="liste">'."\r\n";
                    print $list1;
                print "\r\n".'</table>'."\r\n";
            print '</div>';
            //print '</form>';
        print '</td>';
    print '</tr>'."\r\n";
print "\r\n".'</table>'."\r\n";
print '<script type="text/javascript">
    function ValuesTo(idTr){
        var tr = document.getElementById(idTr);
        var cells =  tr.cells
        document.getElementById("resultT").innerText=cells[1].innerHTML;
        tr.style.color="blue";
        tr.style.fontWeight ="bold";
        if (document.getElementById("idT").value != 0) {
            var idT = "T" + document.getElementById("idT").value;
            document.getElementById(idT).style.color="";
            document.getElementById(idT).style.fontWeight ="normal";
        }
        document.getElementById("idT").value=idTr.substring(1);
        if (document.getElementById("resultF").innerText)
            document.getElementById("fusion").style.display="";
    }
    function ValuesFrom(idTr){
        var tr = document.getElementById(idTr);
        var cells =  tr.cells
        document.getElementById("resultF").innerText=cells[1].innerHTML+"\r\n";
        tr.style.color="blue";
        tr.style.fontWeight ="bold";
        if (document.getElementById("idF").value != 0) {
            var idF = "F" + document.getElementById("idF").value;
            document.getElementById(idF).style.color="";
            document.getElementById(idF).style.fontWeight ="normal";
        }
        document.getElementById("idF").value=idTr.substring(1);
        if (document.getElementById("resultT").innerText)
            document.getElementById("fusion").style.display="";
    }
    </script>';
print '<style>
    .id-container, .fiche {
        height: 100%;
    }
    ::-webkit-scrollbar {
        -webkit-appearance: none;
        width: 15px;
    }
    ::-webkit-scrollbar-thumb {
        border-radius: 4px;
        background-color: rgba(0, 0, 0, .5);
        box-shadow: 0 0 1px rgba(255, 255, 255, .5);
    }
    </style>';
print "<script type='text/javascript' language='javascript'>
            function search(list){
                var field = jQuery(this);
                if(list==1) {
                    jQuery.ajax({
                        type : 'GET',
                        url : 'list_ajax-1.php' ,
                        data : 'c='+$('#search_customer_code1').val() + '&l=1&n='+$('#search_nom1').val(),
                        beforeSend : function() {
                            document.getElementById('ajax-loader1').style.display='';
                        },
                        success : function(data){
                            document.getElementById('ajax-loader1').style.display='none';
                            $('.result1').html(data);
                        }
                    });
                } else {
                    jQuery.ajax({
                        type : 'GET',
                        url : 'list_ajax-1.php' ,
                        data : 'c='+$('#search_customer_code2').val() + '&l=2&n='+$('#search_nom2').val(),
                        beforeSend : function() {
                            document.getElementById('ajax-loader2').style.display='';
                        },
                        success : function(data){
                            document.getElementById('ajax-loader2').style.display='none';
                            jQuery('.result2').html(data);
                        }
                    });
                }
            }
            function help(list){
                if (document.getElementById('idF').value != 0) {
                    var idF = document.getElementById('idF').value;
                }
                if (document.getElementById('idT').value != 0) {
                    var idT = document.getElementById('idT').value;
                }
                if(list==1) {
                    jQuery.ajax({
                        type : 'GET',
                        url : 'list_ajax-2.php' ,
                        data : 'l=1&id='+idF,
                        beforeSend : function() {
                            document.getElementById('ajax-loader1').style.display='';
                        },
                        success : function(data){
                            document.getElementById('ajax-loader1').style.display='none';
                            var res = decodeURIComponent(data);
                            $('.result1').html(data);
                        }
                    });
                } else {
                    jQuery.ajax({
                        type : 'GET',
                        url : 'list_ajax-2.php' ,
                        data : 'l=2&id='+idT,
                        beforeSend : function() {
                            document.getElementById('ajax-loader2').style.display='';
                        },
                        success : function(data){
                            document.getElementById('ajax-loader2').style.display='none';
                            jQuery('.result2').html(data);
                        }
                    });
                }
            }
</script>";
// End of page
llxFooter();
$db->close();
