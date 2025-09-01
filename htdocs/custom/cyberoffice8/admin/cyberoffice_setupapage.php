<?php
/*
 * @author 	LVSinformatique <contact@lvsinformatique.com>
 * @copyright  	2014 LVSInformatique
 * This source file is subject to a commercial license from LVSInformatique
 * Use, copy or distribution of this source file without written
 * license agreement from LVSInformatique is strictly forbidden.
 */
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

$usevariant = false;
$usevariant2 = false;
if (isset($conf->global->MAIN_MODULE_VARIANTS) && $conf->global->MAIN_MODULE_VARIANTS == 1)
    $usevariant = true;

if (isset($conf->global->CYBEROFFICE_variant) && $conf->global->CYBEROFFICE_variant == 1)
    $usevariant2 = true;
// Security check
if (!$user->admin)
    accessforbidden();

$langs->loadLangs(['products',
    'errors',
    'admin',
    'main',
    'companies',
    'resource',
    'holiday',
    'accountancy',
    'hrm',
    'orders',
    'contracts',
    'projects',
    'propal',
    'bills',
    'interventions',
    'ticket',
]);

$langs->load("cyberoffice@cyberoffice8");

$rowid=GETPOST('rowid','int');
$entity=GETPOST('entity','int');
$action=GETPOST('action','alpha');
$update=GETPOST('update','alpha');
$delete=GETPOST('delete');	// Do not use alpha here
$debug=GETPOST('debug','int');
$consts=GETPOST('const','array');
$constname=GETPOST('constname','alpha');
$constvalue=GETPOST('constvalue');
$constnote=GETPOST('constnote','alpha');

/*
 * Actions
 */
if ($action == 'deleteline') {
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."c_cyberoffice3"
        . " WHERE rowid = ".GETPOST('id', 'int');
    $db->query($sql);
    $action = '';
}

if (isset($_POST["action"]) && $_POST["action"] == 'set') {
	//dolibarr_set_const($db,"CYBEROFFICE_invoice",$_POST["CYBEROFFICE_invoice"],'chaine',0,'',$conf->entity);
    dolibarr_set_const($db,"MAIN_MAX_DECIMALS_SHOWN",$_POST["MAIN_MAX_DECIMALS_SHOWN"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db,"CYBEROFFICE_stock",$_POST["CYBEROFFICE_stock"],'chaine',0,'',$conf->entity);
	if (isset($_POST["CYBEROFFICE_variant"])) {
        dolibarr_set_const($db,"CYBEROFFICE_variant",$_POST["CYBEROFFICE_variant"],'chaine',0,'',$conf->entity);
	}
    if (isset($conf->global->CYBEROFFICE_variant) && $conf->global->CYBEROFFICE_variant) {
        dolibarr_set_const($db,'CYBEROFFICE_chanel',0,'chaine',0,'',$conf->entity);
    }
    dolibarr_set_const($db,"CYBEROFFICE_NoDescription",$_POST["CYBEROFFICE_NoDescription"],'chaine',0,'',$conf->entity);
    dolibarr_set_const($db,"CYBEROFFICE_detaildiscount",$_POST["CYBEROFFICE_detaildiscount"],'chaine',0,'',$conf->entity);
    dolibarr_set_const($db,"CYBEROFFICE_invoicelink",$_POST["CYBEROFFICE_invoicelink"],'chaine',0,'',$conf->entity);
    dolibarr_set_const($db,"INVOICE_ALLOW_EXTERNAL_DOWNLOAD",$_POST["CYBEROFFICE_invoicelink"],'chaine',1,'',$conf->entity);
    dolibarr_set_const($db,"CYBEROFFICE_desctoref",$_POST["CYBEROFFICE_desctoref"],'chaine',0,'',$conf->entity);
    dolibarr_set_const($db,"CYBEROFFICE_desctorefcustomer",$_POST["CYBEROFFICE_desctorefcustomer"],'chaine',0,'',$conf->entity);
    dolibarr_set_const($db,"CYBEROFFICE_custlang",$_POST["CYBEROFFICE_custlang"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db,"CYBER_UPPER",$_POST["CYBER_UPPER"],'chaine',0,'',$conf->entity);
    dolibarr_set_const($db,'WEBSERVICES_KEY',trim(GETPOST("WEBSERVICES_KEY")),'chaine',0,'',$conf->entity);
	$msg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";

    if ($conf->global->CYBEROFFICE_desctorefcustomer == 1) {
		$mysql1 = "SELECT u.rowid, up.value, up.param, up.fk_user, u.login FROM ".MAIN_DB_PREFIX."user u"
			." LEFT JOIN ".MAIN_DB_PREFIX."user_param up ON (u.rowid = up.fk_user AND up.param LIKE 'MAIN_SELECTEDFIELDS_orderlist')";
		$resql = $db->query($mysql1);
		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				if ($obj->param == 'MAIN_SELECTEDFIELDS_orderlist') {
					if (strpos($obj->value, 'c.ref_client') === false) {
						$newref = $obj->value.',c.ref_client';
						$tab = array('MAIN_SELECTEDFIELDS_orderlist' => $newref);
						dol_set_user_param($db, $conf, $user, $tab);
					} else {
						//RAS
					}
				} else {
					$newref = 'c.ref,c.ref_client,p.ref,s.nom,s.town,s.zip,c.date_commande,c.date_delivery,c.total_ht,u.login,c.facture,shippable,c.fk_statut';
					$mysql2 = "INSERT INTO ".MAIN_DB_PREFIX."user_param(fk_user,entity,param,value)"
						. " VALUES (".$obj->rowid.",".$conf->entity.","
						. " 'MAIN_SELECTEDFIELDS_orderlist','".$newref."')";
					$resql2 = $db->query($mysql2);
				}
			}
		}
    }
	if (isset($conf->global->CYBEROFFICE_variant) && $conf->global->CYBEROFFICE_variant == 1)
		$usevariant2 = true;
	else
		$usevariant2 = false;
}
if (isset($_POST["action"]) && $_POST["action"] == 'setaccount') {
	foreach($_POST as $key => $val) {
		if (substr($key,0,9)=='accountid')
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'c_paiement
				SET cyberbank='.(int)$val.'
				WHERE id='.(int)substr($key,9);
			if (substr($key,9) && (int)substr($key,9)>0) $resql = $db->query($sql);
	}
	$msg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
}

if (! empty($consts) && $action == 'update') {
	$nbmodified=0;
	foreach($consts as $const)
	{
		if (! empty($const["check"])) {
			if (dolibarr_set_const($db, $const["name"], $const["value"], $const["type"], 1, $const["note"], $const["entity"]) >= 0) {
				$myglobal = "MYCYBEROFFICE_key" . substr($const["name"], -2);
				if (dolibarr_set_const($db, $myglobal, $const["webkey"], 'chaine', 0, '', $const["entity"]) >= 0) {
					$nbmodified++;
				} else {
					dol_print_error($db);
				}
			} else {
				dol_print_error($db);
			}
		}
	}
	if ($nbmodified > 0)
		setEventMessage($langs->trans("RecordSaved"));
	$action='';
}

if (! empty($consts) && $action == 'delete') {

	$nbdeleted=0;
	foreach($consts as $const)
	{
		if (! empty($const["check"]))	// Is checkbox checked
		{
			if (dolibarr_del_const($db, $const["rowid"], -1) >= 0)
			{
				$nbdeleted++;
			}
			else
			{
				dol_print_error($db);
			}
		}
	}
	if ($nbdeleted > 0)
		setEventMessage($langs->trans("RecordDeleted"));
	$action='';
}

if ($action == 'delete') {
	if (dolibarr_del_const($db, $rowid, $entity) >= 0) {
		setEventMessage($langs->trans("RecordDeleted"));
	} else {
		dol_print_error($db);
	}
}

if ($usevariant2 == true) {
    require DOL_DOCUMENT_ROOT . '/variants/class/ProductAttribute.class.php';
    require DOL_DOCUMENT_ROOT . '/variants/class/ProductAttributeValue.class.php';
    $productattribute = new ProductAttribute($db);
    $objectval = new ProductAttributeValue($db);
    $variants = $productattribute->fetchAll();
    $tabVariant = array();
    $tabVariant0 = array();
    $tabVariant1 = array();
	$max = 10000;
	$i=0;
    foreach ($variants as $key => $attribute) {
        foreach ($objectval->fetchAllByProductAttribute($attribute->id) as $attrval) {
            $tabVariant[] = array($attrval->id, $attribute->label, $attrval->value,'','','','','','');
            $tabVariant0[$attribute->label] = $attribute->label;
			$i++;
			if($i < $max) {
				$tabVariant1[$attribute->label][$attrval->id] = $attrval->value;
			}
        }
    }
    $array_lowercase1 = array_map('strtolower', array_column($tabVariant, 1));
    $array_lowercase2 = array_map('strtolower', array_column($tabVariant, 2));
    array_multisort(
        $array_lowercase1, SORT_ASC,
        $array_lowercase2, SORT_ASC,
        $tabVariant
    );
}

$form=new Form($db);

llxHeader();

if ($conf->use_javascript_ajax) {
?>

<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery("#updateconst").hide();
	jQuery("#delconst").hide();
	jQuery(".checkboxfordelete").click(function() {
		jQuery("#delconst").show();
		jQuery("#action").val('delete');
	});
	jQuery(".inputforupdate").keyup(function() {	// keypress does not support back
		var field_id = jQuery(this).attr("id");
		var row_num = field_id.split("_");
		jQuery("#updateconst").show();
		jQuery("#action").val('update');
		jQuery("#check_" + row_num[1]).attr("checked",true);
	});
        //jQuery("#CYBEROFFICE_desctoref").change(function() {
	//	document.getElementById("CYBEROFFICE_desctorefcustomer").value = this.value;
	//});
});
</script>
<?php
}

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("cyberofficeSetup"),$linkback,'setup');
if (isset($msg) && $msg)
	dol_htmloutput_mesg($msg);
/*print '*'.($conf->global->WEBSERVICES_KEY);
print '<br>*'.$conf->entity.':'.dolibarr_get_const($db, 'WEBSERVICES_KEY', $conf->entity);
print '<br>*0:'.dolibarr_get_const($db, 'WEBSERVICES_KEY', 0);
print '<br>*1:'.dolibarr_get_const($db, 'WEBSERVICES_KEY', 1);
print '<br>*2:'.dolibarr_get_const($db, 'WEBSERVICES_KEY', 2);*/
$WEBSERVICES_KEY = dolibarr_get_const($db, 'WEBSERVICES_KEY', $conf->entity);
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';
print '<fieldset>';
print '<label>'.$langs->trans("KeyForWebServicesAccess").'</label>
    <input type="text" class="flat" id="WEBSERVICES_KEY" name="WEBSERVICES_KEY" value="'.(! empty($WEBSERVICES_KEY)?$WEBSERVICES_KEY:'') . '" size="40" READONLY>';
if (empty($WEBSERVICES_KEY)) {
	print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
}
print '</fieldset><br/>';

//// extrafield
        /* require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($db);
        foreach($extrafields->fetch_name_optionals_label('product') as $key => $value)
        {
            $mysql = "INSERT INTO ".MAIN_DB_PREFIX."c_cyberoffice (code) VALUES ('".$key."')";
            print $mysql.'<br>';
            $resql = $db->query($mysql);
        }
        */
/*require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
$extrafields = new ExtraFields($db);
$ProductExtraField = $extrafields->fetch_name_optionals_label('product');
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cyberoffice c WHERE c.active=1 AND c.idpresta=7";
            $resql = $db->query($sql);
            if ($resql) {
                if ($db->num_rows($resql) > 0) {
                    $res = $db->fetch_array($resql);
                    $res_rowid=$res['rowid'];
                    $res_extrafield=$res['extrafield'];
                    $res_idpresta=$res['idpresta'];
                    if ($extrafields->attribute_type[$res_extrafield]== 'select') {
                        $newobject->array_options[$res_extrafield] = 1;
                    } else {
                        $newobject->array_options[$res_extrafield] = 'toto';
                    }
                }
            }
            print_r($newobject->array_options);*/
/*print $extrafields->attribute_type['test'];
print '<pre>';print_r($extrafields);print '</pre>';*/
$var=false;
print '<fieldset>';
print '<div class="table-responsive">';
print '<table class="noborder"><thead><tr class="liste_titre"><th colspan=3  style="font-weight:bold">'.$langs->trans('Customers').'</th><th colspan=3  style="font-weight:bold">'.$langs->trans('Products').'</th><th colspan=3  style="font-weight:bold">'.$langs->trans('Orders').'</th><th colspan=3  style="font-weight:bold">'.$langs->trans('Invoices').'</th></tr></thead>';
print '<tbody><tr '.$bc[$var].'>';
print '<td><label>'.$langs->trans('langSynchronization').'</label></td><td>
			<select name="CYBEROFFICE_custlang">
				<option value="0" '.((isset($conf->global->CYBEROFFICE_custlang) && $conf->global->CYBEROFFICE_custlang==0)?'selected="selected"':'').'>'.$langs->trans('No').'</option>
				<option value="1" '.((isset($conf->global->CYBEROFFICE_custlang) && $conf->global->CYBEROFFICE_custlang==1)?'selected="selected"':'').'>'.$langs->trans('Yes').'</option>
			</select></td><td>&nbsp;</td>';
print '<td><label>'.$langs->trans('StockSynchronization').'</label></td><td>
			<select name="CYBEROFFICE_stock">
				<option value="0" '.($conf->global->CYBEROFFICE_stock==0?'selected="selected"':'').'>'.$langs->trans('No').'</option>
				<option value="1" '.($conf->global->CYBEROFFICE_stock==1?'selected="selected"':'').'>'.$langs->trans('Yes').'</option>
			</select></td><td>&nbsp;</td>';
print '<td><label>'.$langs->trans('detaildiscount').'</label></td><td>
            <select name="CYBEROFFICE_detaildiscount">
                <option value="0" '.((isset($conf->global->CYBEROFFICE_detaildiscount) && $conf->global->CYBEROFFICE_detaildiscount==0)?'selected="selected"':'').'>'.$langs->trans('No').'</option>
		<option value="1" '.((isset($conf->global->CYBEROFFICE_detaildiscount) && $conf->global->CYBEROFFICE_detaildiscount==1)?'selected="selected"':'').'>'.$langs->trans('Yes').'</option>
            </select></td><td>&nbsp;</td>';
print '<td><label>'.$langs->trans('invoicelink').'</label></td><td>
            <select name="CYBEROFFICE_invoicelink">
                <option value="0" '.((isset($conf->global->CYBEROFFICE_invoicelink) && $conf->global->CYBEROFFICE_invoicelink==0)?'selected="selected"':'').'>'.$langs->trans('No').'</option>
		<option value="1" '.((isset($conf->global->CYBEROFFICE_invoicelink) && $conf->global->CYBEROFFICE_invoicelink==1)?'selected="selected"':'').'>'.$langs->trans('Yes').'</option>
            </select></td><td>&nbsp;</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td><label>'.$langs->trans('CYBERUPPER').'</label></td><td>
			<select name="CYBER_UPPER">
				<option value="0" '.((isset($conf->global->CYBER_UPPER) && $conf->global->CYBER_UPPER==0)?'selected="selected"':'').'>'.$langs->trans('No').'</option>
				<option value="1" '.((isset($conf->global->CYBER_UPPER) && $conf->global->CYBER_UPPER==1)?'selected="selected"':'').'>'.$langs->trans('Yes').'</option>
			</select></td><td>&nbsp;</td>';
print '<td><label>'.$langs->trans('NoDescription').'</label></td><td>
            <select name="CYBEROFFICE_NoDescription">
                <option value="0" '.((isset($conf->global->CYBEROFFICE_NoDescription) && $conf->global->CYBEROFFICE_NoDescription==0)?'selected="selected"':'').'>'.$langs->trans('No').'</option>
		<option value="1" '.((isset($conf->global->CYBEROFFICE_NoDescription) && $conf->global->CYBEROFFICE_NoDescription==1)?'selected="selected"':'').'>'.$langs->trans('Yes').'</option>
            </select></td><td>&nbsp;</td>';
print '<td><label>'.$langs->trans('desctoref').'</label></td><td>
            <select name="CYBEROFFICE_desctoref" id="CYBEROFFICE_desctoref">
                <option value="0" '.((isset($conf->global->CYBEROFFICE_desctoref) && $conf->global->CYBEROFFICE_desctoref==0)?'selected="selected"':'').'>'.$langs->trans('No').'</option>
		<option value="1" '.((isset($conf->global->CYBEROFFICE_desctoref) && $conf->global->CYBEROFFICE_desctoref==1)?'selected="selected"':'').'>'.$langs->trans('Yes').'</option>
            </select></td><td>&nbsp;</td>';
print '<td></td><td></td><td>&nbsp;</td></tr>';
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td></td><td></td><td>&nbsp;</td>';
if ($usevariant == true) {
    $myselected = ((isset($conf->global->CYBEROFFICE_variant) && $conf->global->CYBEROFFICE_variant==1)?1:0);
    print '<td><label>'.$langs->trans('UseVariant').'</label></td><td>
			<select name="CYBEROFFICE_variant">
				<option value="0" '.($myselected==0?'selected="selected"':'').'>'.$langs->trans('No').'</option>
				<option value="1" '.($myselected==1?'selected="selected"':'').'>'.$langs->trans('Yes').'</option>
			</select>';
    print '</td><td>&nbsp;</td>';
} else {
    print '<td></td><td></td><td>&nbsp;</td>';
}
print '<td><label>'.$langs->trans('desctorefcustomer').'</label></td><td>
            <select name="CYBEROFFICE_desctorefcustomer" id="CYBEROFFICE_desctorefcustomer">
                <option value="0" '.((isset($conf->global->CYBEROFFICE_desctorefcustomer) && $conf->global->CYBEROFFICE_desctorefcustomer==0)?'selected="selected"':'').'>'.$langs->trans('No').'</option>
		<option value="1" '.((isset($conf->global->CYBEROFFICE_desctorefcustomer) && $conf->global->CYBEROFFICE_desctorefcustomer==1)?'selected="selected"':'').'>'.$langs->trans('Yes').'</option>
            </select></td><td>&nbsp;</td>';
print '<td></td><td></td><td>&nbsp;</td>';
print '</tr>';
print '</tbody>';

print '<thead><tr class="liste_titre"><th colspan=12  style="font-weight:bold">'.$langs->trans("LimitsSetup").'</th></tr>';
print '<tr class="liste_titre"><th colspan=12>'. info_admin($langs->trans("CYBER_MAIN_MAX_DECIMALS_SHOWN")).'</th></tr>';
print '</thead>';
print '<tbody>';
print '<tr>';
print '<td colspan="5">'.$langs->trans("MAIN_MAX_DECIMALS_SHOWN").'</td>';
print '<td colspan="7"><input class="flat right" name="MAIN_MAX_DECIMALS_SHOWN" size="3" value="'.getDolGlobalString('MAIN_MAX_DECIMALS_SHOWN').'"></td>';
print '</tr>';
print '</tbody>';

print '</table>';
print '</div>';
print '<label><input type="submit" class="button" value="'.$langs->trans("Save").'"></label>';

print '</fieldset>';
print '</form>';
//////
print '<br/>';
print_fiche_titre($langs->trans("shoplist"));
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" id="action" name="action" value="">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("eshop_webkey").'</td>';
print '<td>'.$langs->trans("ShopAddress").'</td>';
if (! empty($conf->multicompany->enabled) && !$user->entity)
	print '<td>'.$langs->trans("Entity").'</td>';
print '<td align="center">'.$langs->trans("Action").'</td>';
print "</tr>\n";

// Show constants
$sql = "SELECT";
$sql.= " rowid";
$sql.= ", ".$db->decrypt('name')." as name";
$sql.= ", ".$db->decrypt('value')." as value";
$sql.= ", type";
$sql.= ", note";
$sql.= ", entity";
$sql.= " FROM ".MAIN_DB_PREFIX."const";
$sql.= " WHERE name like 'CYBEROFFICE_SHOP%'";
$sql.= " AND entity IN (0,".$conf->entity.")";
$sql.= " ORDER BY entity, name ASC";

$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;
	$var=false;
	while ($i < $num)
	{
		$obj = $db->fetch_object($result);
		$var=!$var;

		print "\n";

		print '<tr '.$bc[$var].'><td>'.$obj->name.'</td>'."\n";

		// Value
		print '<td>';
		print '<input type="hidden" name="const['.$i.'][rowid]" value="'.$obj->rowid.'">';
		print '<input type="hidden" name="const['.$i.'][name]" value="'.$obj->name.'">';
		print '<input type="hidden" name="const['.$i.'][type]" value="'.$obj->type.'">';
		print '<input type="text" id="value_'.$i.'" class="flat inputforupdate" size="5" name="const['.$i.'][value]" value="'.htmlspecialchars($obj->value).'">';
		print '</td>';

        // webkey
        $myglobal = "MYCYBEROFFICE_key" . substr($obj->name, -2);
		print '<td>';
		print '<input type="text" id="webkey_'.$i.'" class="flat inputforupdate" size="40" name="const['.$i.'][webkey]" value="'.(isset($conf->global->$myglobal)?htmlentities($conf->global->$myglobal, ENT_COMPAT, 'UTF-8'):'').'">';
		print '</td>';

		// Note
		print '<td>';
		print '<input type="text" id="note_'.$i.'" class="flat inputforupdate" size="80" name="const['.$i.'][note]" value="'.htmlspecialchars($obj->note,1).'">';
		print '</td>';

		// Entity limit to superadmin
		if (! empty($conf->multicompany->enabled) && !$user->entity) {
			print '<td>';
			print '<input type="text" class="flat" size="1" name="const['.$i.'][entity]" value="'.$obj->entity.'">';
			print '</td>';
			print '<td align="center">';
		} else {
			print '<td align="center">';
			print '<input type="hidden" name="const['.$i.'][entity]" value="'.$obj->entity.'">';
		}

		if ($conf->use_javascript_ajax) {
			print '<input type="checkbox" class="flat checkboxfordelete" id="check_'.$i.'" name="const['.$i.'][check]" value="1">';
		} else {
			print '<a href="'.$_SERVER['PHP_SELF'].'?rowid='.$obj->rowid.'&entity='.$obj->entity.'&action=delete'.((empty($user->entity) && $debug)?'&debug=1':'').'">'.img_delete().'</a>';
		}
		if ($usevariant2==true) {
			$indshop = substr($obj->name, -2);
			print "&nbsp;".img_picto($langs->trans("ProductCombinations"), 'title_project.png', 'style="vertical-align: middle;" onclick="showvariant(\''.$indshop.'\')"');
		}
		print "</td>";

		if ($usevariant2==true) {
			${'tabVariant'.$indshop} = $tabVariant;
			$sql3 = "SELECT * FROM ".MAIN_DB_PREFIX."c_cyberoffice3"
				." WHERE shop LIKE '".$indshop."'";
			$resql3 =$db->query($sql3);
			if ($resql3) {
				$id3 = 0;
				$table3 = array();
				$num_rows3=$db->num_rows($resql3);
				while ($id3 < $num_rows3)
				{
					$array3 = $db->fetch_array($resql3);
					$keyvariant = array_search ($array3['variant'], array_column(${'tabVariant'.$indshop}, 0));
					if ($keyvariant !== false) {
						${'tabVariant'.$indshop}[$keyvariant][3] = $array3['attribut'];
						${'tabVariant'.$indshop}[$keyvariant][4] = $array3['variant'];
						${'tabVariant'.$indshop}[$keyvariant][5] = $array3['shop'];
						${'tabVariant'.$indshop}[$keyvariant][9] = $array3['rowid'];
					} else {
						${'tabVariant'.$indshop}[] = array('', '', '', $array3['attribut'], $array3['variant'], $array3['shop'],'','','',$array3['rowid']);
					}
					$id3 ++;
				}
			}
			/*print '<pre>';print_r($tabVariant);print '</pre>';*/
			print '<tr id="testvariant'.$indshop.'" style=""><td colspan='.((!empty($conf->multicompany->enabled) && !$user->entity)?5:4).'>';
			$test = testConfig($obj->note, substr($obj->name, -2));
			/*print '<pre>';print_r($test);print '</pre>';*/
			/*if (is_array($test) || is_object($test)) {
				$shoparray_lowercase1 = array_map('strtolower', array_column($test, 'attribute_group'));
				$shoparray_lowercase2 = array_map('strtolower', array_column($test, 'name'));
				array_multisort(
					$shoparray_lowercase1, SORT_ASC,
					$shoparray_lowercase2, SORT_ASC,
					$test
				);
			}*/
			$tabselect0 = array();
			$tabselect1 = array();
			if (is_array($test) || is_object($test)) {
				foreach ($test as $key => $attribute) {
					$tabselect0[$attribute['id_attribute_group']]=$attribute['attribute_group'];
					$tabselect1[$attribute['id_attribute_group']][$attribute['id_attribute']]=$attribute['name'];
					$keyvariant = array_search ($attribute['id_attribute'], array_column(${'tabVariant'.$indshop}, 3));
					if ($keyvariant !== false) {
						${'tabVariant'.$indshop}[$keyvariant][6] = $attribute['id_attribute'];
						${'tabVariant'.$indshop}[$keyvariant][7] = $attribute['attribute_group'];
						${'tabVariant'.$indshop}[$keyvariant][8] = $attribute['name'];
					} else {
						${'tabVariant'.$indshop}[] = array('', '', '', '', '', $indshop, $attribute['id_attribute'], $attribute['attribute_group'], $attribute['name']);
					}
				}
			}
			/*print '<pre>'.$indshop;print_r(${'tabVariant'.$indshop});print '</pre>';*/
			$shoparray_lowercase1 = array_map('strtolower', array_column(${'tabVariant'.$indshop}, '1'));
			$shoparray_lowercase2 = array_map('strtolower', array_column(${'tabVariant'.$indshop}, '2'));
			$shoparray_lowercase7 = array_map('strtolower', array_column(${'tabVariant'.$indshop}, '7'));
			$shoparray_lowercase8 = array_map('strtolower', array_column(${'tabVariant'.$indshop}, '8'));
			array_multisort(
				$shoparray_lowercase7, SORT_ASC,
				$shoparray_lowercase8, SORT_ASC,
				$shoparray_lowercase1, SORT_ASC,
				$shoparray_lowercase2, SORT_ASC,
				${'tabVariant'.$indshop}
			);

			print '</td></tr>';
			print '<tr id="myvariant'.$indshop.'" style="display:none"><td colspan='.((!empty($conf->multicompany->enabled) && !$user->entity)?5:4).'>';
			print "<table id='variant1".$indshop."' class='variant1 noborder' style='background-color: white;float: left;width: 50%;'>";
			//$myglobal = "MYCYBEROFFICE_key" . $indshop;
			//print '<tr class="liste_titre"><td colspan="2" class="center" style="font-weight: bold;">Eshop<br>'.htmlentities($conf->global->$myglobal, ENT_COMPAT, 'UTF-8').'</td><td>';
			print '<tr class="liste_titre"><td colspan="2" class="center" style="font-weight: bold;">Eshop</td><td>';
			print '</td><td class="center" style="font-weight: bold;">Dolibarr</td><td></td></tr>';
			print "<tr class='liste_titre'>";
			print "<td style='font-weight: bold;'>".$langs->trans('Label')."</td><td style='font-weight: bold;'>".$langs->trans('Value')."</td><td></td><td style='font-weight: bold;'>".$langs->trans('Value')."</td><td></td>";
			print "</tr>";
			$var=true;
			/*
			$select = '<select name="tabVariant'.$indshop.'">';
			foreach (${'tabVariant'.$indshop} as $attribute) {
				$select .= '<option value="rowL'.$indshop.'-'.$attribute[6].'">'.dol_htmlentities($attribute[7]).' > '.dol_htmlentities($attribute[8]).'</option>';
			}
			$select .= '</select>';
			*/
			/*print '<pre>';print_r($tabVariant0);print '</pre>';*/
			/*print '<pre>';print_r($tabVariant1);print '</pre>';*/
			/*print '<pre>';print_r($tabVariant);print '</pre>';
			print '<pre>';print_r($tabselect0);print '</pre>';
			print '<pre>';print_r($tabselect1);print '</pre>';*/
			/*print '<pre>';print_r($test);print '</pre>';
			print '<pre>';print_r($test);print '</pre>';*/
			/*print_r(${'tabVariant'.$indshop});*/
			$forceUpdate = false;
			foreach (${'tabVariant'.$indshop} as $attribute) {
				print '<tr id="rowL'.$indshop.'-'.$attribute[3].'-'.$attribute[4].'" class="drag drop oddeven" '.$bc[$var].'>';
				print '<td>'.$attribute[6].' : '.dol_htmlentities($attribute[7]).'</td>';
				print '<td>'.dol_htmlentities($attribute[8]).'</td>';
				$warning = '';
				if($attribute[3] && $attribute[5] && !$forceUpdate){
					$select1 = $attribute[1] .' : '. $attribute[2];//.print_r($attribute,1);
				}
				else {
//					die('bbbb');
					$select1 = '<select id="monselect-'.$indshop.'-'.$attribute[6].'-'.$attribute[0].'" onchange="updatecol('.$attribute[6].',this.value,\''.$indshop.'\',this.id);">'
						. '<option value="-1">Select a value</option>';
					foreach ($tabVariant1 as $key10 => $tabVariant10) {

						foreach ($tabVariant10 as $key100 => $tabVariant100) {
							if (!$forceUpdate && ($attribute[7]) == ($key10) && ($attribute[8]) == ($tabVariant100)) {
								$attribute[3] = $attribute[6];
								$attribute[0] = $key100;
								$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_cyberoffice3 (attribut, variant, shop)"
									." VALUES (".$attribute[3].",".$attribute[0].",'".$indshop."')";
								//print $sql;
								$resqlI = $db->query($sql);
								$warning = 'Correspondance auto';
//							die('ok');
							}
//							if (!$attribute[3]) {//presta
//								$warning = '<td>' . img_error() . '</td>';
//								$select1 .= '<option value="' . $key100 . '" ' . ($attribute[0] == $key100 ? 'selected' : '') . '>' . $key10 . ' : ' . $tabVariant100 . '</option>';
//							} elseif (!$attribute[5]) {//dolibarr
//								$select1 .= '<option value="' . $key100 . '">' . $key10 . ' : ' . $tabVariant100 . '</option>';
//								$warning = '<td>' . img_error() . '</td>';
//							} else {
//								$warning = '<td></td>';
//								$select1 .= '<option value="' . $key100 . '" ' . ($attribute[0] == $key100 ? 'selected' : '') . '>' . $key10 . ' : ' . $tabVariant100 . '</option>';
//							}
						}
					}
					$select1 .= '</select>';
				}
				print $warning;
				print '<td>'.$select1.'</td>';

				print '<td class="linecoldelete" align="center">';
				if (isset($attribute[9]) && $attribute[9] > 0) {
					print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $attribute[9] . '&amp;action=deleteline">';
					print img_delete();
					print '</a>';
				}
				print '</td>';
				print '</tr>';
				$var=!$var;
			}
			/*if (is_array($test) || is_object($test)) {
				foreach ($test as $key => $attribute) {
					print '<tr id="rowL'.$indshop.'-'.$attribute['id_attribute'].'" class="drag drop oddeven" '.$bc[$var].'>';
					print '<td>'.dol_htmlentities($attribute['attribute_group']).'</td>';
					print '<td>'.dol_htmlentities($attribute['name']).'</td>';
					print '</tr>';
					$var=!$var;
				}
			}*/

			/*foreach ($variants as $key => $attribute) {
				foreach ($objectval->fetchAllByProductAttribute($attribute->id) as $attrval) {
					print '<tr id="rowL'.$i.'-'.$attrval->id.'" class="drag drop oddeven" '.$bc[$var].'>';
					print '<td>'.dol_htmlentities($attribute->ref).'</td>';
					print '<td>'.dol_htmlentities($attribute->label).'</td>';
					print '<td>'.dol_htmlentities($attrval->ref).'</td>';
					print '<td>'.dol_htmlentities($attrval->value).'</td>';
					print '</tr>';
					$var=!$var;
				}
			}*/
			print "</table>";

			print "</td></tr>";
		}

		print "\n";
		$i++;
	}
}

print '</table>';

if ($conf->use_javascript_ajax) {
	print '<br>';
	print '<div id="updateconst" align="right">';
	print '<input type="submit" name="update" class="button" value="'.$langs->trans("Modify").'">';
	print '</div>';
	print '<div id="delconst" align="right">';
	print '<input type="submit" name="delete" class="button" value="'.$langs->trans("Delete").'">';
	print '</div>';
}

print "</form>\n";
print_fiche_titre($langs->trans("Associationmodedereglementcomptebancaire"));
print info_admin($langs->trans("infocyberpaiement"));
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setaccount">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("code").'</td>';
print '<td>'.$langs->trans("libelle").'</td>';
print '<td>'.$langs->trans("cyberbank").'</td>';
print '<td align="center">'.$langs->trans("banklabel").'</td>';
print "</tr>\n";


$sql = "SELECT cp.id, cp.code, cp.libelle, cp.cyberbank, ba.ref, ba.label";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_paiement cp";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account ba ON (cp.cyberbank = ba.rowid)";
        $sql.= " WHERE cp.entity IN (".getEntity('c_paiement').")";
        $sql.= " AND cp.active > 0 and cp.id > 0";
        $sql.= " ORDER BY cp.id";
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num)
	{
		$var=!$var;
		$obj = $db->fetch_object($resql);
		$langs->load("bills");
		$key = $langs->trans("PaymentType".strtoupper($obj->code));
		$valuetoshow = ($obj->code && $key != "PaymentType".strtoupper($obj->code) ? $key : $obj->libelle);
		$valuetoshow = $langs->trans($valuetoshow);
		print '<tr '.$bc[$var].'>';
		print '<td>'.$obj->code.'</td>';
		print '<td>'.$valuetoshow.'</td>';
		print '<td>'.$obj->cyberbank.'</td>';
		print '<td align="center">';
		$form->select_comptes($obj->cyberbank,'accountid'.$obj->id,0,'',1);
		print '</td>';
		print '</tr>';
		$i++;
	}
}
print '<tr '.$bc[$var].'><td colspan="3"></td><td align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';
print '</table>';
print '</form>';
llxFooter();
$db->close();

print "\n".'<script type="text/javascript">';
print '$(document).ready(function () {
		$("#generate_token").click(function() {
			$.get( "'.DOL_URL_ROOT.'/core/ajax/security.php", {
				action: \'getrandompassword\',
				generic: true
			},
			function(token) {
				$("#WEBSERVICES_KEY").val(token);
			});
		});
});';
print '</script>';

print '<script type="text/javascript">';
print "function showvariant(source) {
        if (document.getElementById('myvariant'+source).style.display == 'none')
            document.getElementById('myvariant'+source).style.display='';
        else
            document.getElementById('myvariant'+source).style.display='none';
    }";
print '</script>';

/*
print '
<style type="text/css">
tr.myDragClass td, tr.myDragClass td a {
    color: yellow;
    text-shadow: 0 0 10px black, 0 0 10px black, 0 0 8px black, 0 0 6px black, 0 0 6px black;
    -webkit-box-shadow: 0 12px 14px -12px #111 inset, 0 -2px 2px -1px #333 inset;
}
</style>';
  print '
  <script>
  	$(document).ready(function() {
		$(".variant1").tableDnD({
			onDragClass: "myDragClass",
			onDrop: function(table, row) {
                                    document.getElementById("updatecol").style.backgroundColor = "";
                                    document.getElementById("tick").style.display = "none";
                                    var rows = table.tBodies[0].rows;
                                    var debugStr = "Row dropped was "+row.id+". New order: ";
                                    for (var i=0; i<rows.length; i++) {
                                            debugStr += rows[i].id+" ";
                                    }
				console.log(debugStr);
			},
		});
	});
  </script>';*/
  print '<script type="text/javascript" language="javascript">';
  print 'function updatecol(presta,doli,eshop,thisid){
        console.log(presta+"."+doli+"."+eshop+thisid);
        jQuery.ajax({
            type : "POST",
            url : "../declination.php",
            data : { data: presta, data1: doli, data2: eshop },
            success : function(result){
                if(result==2)
                    document.getElementById(thisid).style.backgroundColor = "red";
                else
                    document.getElementById(thisid).style.backgroundColor = "green";
            },
            error : function(){
                document.getElementById(thisid).style.backgroundColor = "red";
            }
	});
    }';
print '</script>';

/*print '<script type="text/javascript" language="javascript">';
		print 'jQuery(function() {
                    jQuery(".left, #right").sortable({
                        handle: \'.boxhandle\',
                        revert: \'invalid\',
                        items: \'.box\',
                        containment: \'.fiche\',
                        connectWith: \'.connectedSortable\',
                        stop: function(event, ui) {
                            updateOrder();
                        }
                    });
                });
        ';

function show_elem($fieldssource, $pos, $key, $var, $nostyle = '')
{
	global $langs, $bc;

	$height = '24px';

	if ($key == 'none') {
		//stop multiple duplicate ids with no number
		print "\n\n<!-- Box_no-key start-->\n";
		print '<div class="box boximport" style="padding:0;">'."\n";
		print '<table summary="boxtable_no-key" width="100%" class="nobordernopadding">'."\n";
	} else {
		print "\n\n<!-- Box ".$pos." start -->\n";
		print '<div class="box boximport" style="padding: 0;" id="boxto_'.$pos.'">'."\n";

		print '<table summary="boxtable'.$pos.'" width="100%" class="nobordernopadding">'."\n";
	}

	if ($pos && $pos > count($fieldssource))	// No fields
	{
		print '<tr'.($nostyle ? '' : ' '.$bc[$var]).' style="height:'.$height.'">';
		print '<td class="nocellnopadding" width="16" style="font-weight: normal">';
		print img_picto(($pos > 0 ? $langs->trans("MoveField", $pos) : ''), 'grip_title', 'class="boxhandle" style="cursor:move;"');
		print '</td>';
		print '<td style="font-weight: normal">';
		print $langs->trans("NoFields");
		print '</td>';
		print '</tr>';
	} elseif ($key == 'none')	// Empty line
	{
		print '<tr'.($nostyle ? '' : ' '.$bc[$var]).' style="height:'.$height.'">';
		print '<td class="nocellnopadding" width="16" style="font-weight: normal">';
		print '&nbsp;';
		print '</td>';
		print '<td style="font-weight: normal">';
		print '&nbsp;';
		print '</td>';
		print '</tr>';
	} else // Print field of source file
	{
		print '<tr'.($nostyle ? '' : ' '.$bc[$var]).' style="height:'.$height.'">';
		print '<td class="nocellnopadding" width="16" style="font-weight: normal">';
		// The image must have the class 'boxhandle' beause it's value used in DOM draggable objects to define the area used to catch the full object
		print img_picto($langs->trans("MoveField", $pos), 'grip_title', 'class="boxhandle" style="cursor:move;"');
		print '</td>';
		print '<td style="font-weight: normal">';
		print $langs->trans("Field").' '.$pos;
		$example = $fieldssource[$pos]['example1'];
		if ($example)
		{
			if (!utf8_check($example)) $example = utf8_encode($example);
			print ' (<i>'.$example.'</i>)';
		}
		print '</td>';
		print '</tr>';
	}

	print "</table>\n";

	print "</div>\n";
	print "<!-- Box end -->\n\n";
}*/
function testConfig($myurl, $myshop)
{
    global $conf;

    $result='';
    $ws_dol_url = $myurl.'modules/cyberoffice/classes/server_config_soap.php';

    $ws_method  = 'getConfig';
    $ns = 'http://www.lvsinformatique.com/ns/';
    $options = array(   'location'      =>  $ws_dol_url,
                        'uri'           =>  $myurl,
                        'wsdl_cache'    =>  0,
                        'exceptions'    =>  true,
                        'trace'         =>  1);
    try {
        $soapclient = new SoapClient(NULL,$options);
    }
    catch(Throwable $e) {
        print "Exception Error!";
        print var_dump($e->getMessage());
    }

    //$keyweb = (!$conf->global->{'MYCYBEROFFICE_key'.$myshop}?$conf->global->WEBSERVICES_KEY:$conf->global->{'MYCYBEROFFICE_key'.$myshop});
    $keyweb = $conf->global->{'MYCYBEROFFICE_key'.$myshop};
    $authentication = array(
        'dolibarrkey'       =>  htmlentities($keyweb, ENT_COMPAT, 'UTF-8'),
		'sourceapplication' =>  'LVSInformatique',
		'login'             =>  '',
		'password'          =>  '',
		'shop'              =>  $conf->global->{'MYCYBEROFFICE_shop'.$myshop},
		'lang'              =>  1,//$conf->global->{'MYCYBEROFFICE_lang'.$myshop},
		'myurl'             =>  $_SERVER["PHP_SELF"]
    );

    $myparam = array(
		'repertoire'    =>  $myurl,
		'supplier' 	=>  1,
		'category' 	=>  2,
		'myurl'		=>  $_SERVER["PHP_SELF"]
    );

    if (htmlentities($keyweb, ENT_COMPAT, 'UTF-8')) {
        try {
            $result = $soapclient->getConfig($authentication, $myparam, $ns, '');
        }
        catch(SoapFault $fault)
        {
            $result = 'faultstring = '.$fault->faultstring;
            if($fault->faultstring != 'Could not connect to host' && $fault->faultstring != 'Not Found')
            {
                echo '<pre>';
                print_r($fault);
                echo '</pre>';
            }
        }
        $ar = array();
        if (is_array($result) && isset($result['Attribute'])) {
            $ar = $result['Attribute'];

            array_multisort(
                array_column($ar, 'attribute_group'),
                array_column($ar, 'name'),
                $ar
            );
            $result = $ar;
        }
        if(!empty($conf->global->MYCYBEROFFICE_debug) && $conf->global->MYCYBEROFFICE_debug==1) {
            var_dump(htmlspecialchars($soapclient->__getLastResponse()));
            echo "getLastResponse: " . $soapclient->__getLastResponse();
            echo "<br/>getLastRequest: " . $soapclient->__getLastRequest();
            echo "<br/>getLastResponseHeaders: " . $soapclient->__getLastResponseHeaders();
            echo "<h2>Result</h2>";
            print '<pre>'.print_r($result).'</pre>';
            echo "<h2>Request</h2>";
            echo "<pre>" . htmlspecialchars($soapclient->request, ENT_QUOTES) . "</pre>";
            echo "<h2>Response</h2>";
            echo "<pre>" . htmlspecialchars($soapclient->response, ENT_QUOTES) . "</pre>";
        }
        return $result;
    }
}
