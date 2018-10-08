<?php
/**
 *	CyberOffice
 *
 *  @author    LVSinformatique <contact@lvsinformatique.com>
 *  @copyright 2014 LVSInformatique
 *	@license   NoLicence
 *  @version   1.2.34
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
// Security check
if (!$user->admin)
	accessforbidden();

$langs->load("admin");
$langs->load("cyberoffice@cyberoffice");

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
if ($_POST["action"] == 'set')
{
		//dolibarr_set_const($db,"CYBEROFFICE_invoice",$_POST["CYBEROFFICE_invoice"],'chaine',0,'',$conf->entity);
		dolibarr_set_const($db,"CYBEROFFICE_stock",$_POST["CYBEROFFICE_stock"],'chaine',0,'',$conf->entity);
		if (DOL_VERSION >= '3.9.0') 
			dolibarr_set_const($db,'WEBSERVICES_KEY',trim(GETPOST("WEBSERVICES_KEY")),'chaine',0,'',$conf->entity);
		$msg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";

}
if ($_POST["action"] == 'setaccount')
{
		foreach($_POST as $key => $val) {
			if (substr($key,0,9)=='accountid')
				//echo '$_POST["'.$key.'"]='.$val.'--'.(int)substr($key,9).'<br />';
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'c_paiement
					SET cyberbank='.(int)$val.'
					WHERE id='.(int)substr($key,9);
				if (substr($key,9) && (int)substr($key,9)>0) $resql = $db->query($sql);
		}
		$msg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
}

if (! empty($consts) && $action == 'update')
{
	$nbmodified=0;
	foreach($consts as $const)
	{
		if (! empty($const["check"]))
		{
			if (dolibarr_set_const($db, $const["name"], $const["value"], $const["type"], 1, $const["note"], $const["entity"]) >= 0)
			{
				$nbmodified++;
			}
			else
			{
				dol_print_error($db);
			}
		}
	}
	if ($nbmodified > 0) setEventMessage($langs->trans("RecordSaved"));
	$action='';
}

if (! empty($consts) && $action == 'delete')
{

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
	if ($nbdeleted > 0) setEventMessage($langs->trans("RecordDeleted"));
	$action='';
}

if ($action == 'delete')
{
	if (dolibarr_del_const($db, $rowid, $entity) >= 0)
	{
		setEventMessage($langs->trans("RecordDeleted"));
	}
	else
	{
		dol_print_error($db);
	}
}


/*
 * View
 */

$form=new Form($db);

llxHeader();

// Add logic to show/hide buttons
if ($conf->use_javascript_ajax)
{
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
});
</script>
<?php
}

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("cyberofficeSetup"),$linkback,'setup');
if ($msg) dol_htmloutput_mesg($msg);

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';
print '<fieldset>';
print '<label>'.$langs->trans("KeyForWebServicesAccess").'</label>
			<input type="text" class="flat" id="WEBSERVICES_KEY" name="WEBSERVICES_KEY" value="'.(! empty($conf->global->WEBSERVICES_KEY)?$conf->global->WEBSERVICES_KEY:'') . '" size="40" READONLY>';
if (empty($conf->global->MAIN_MODULE_WEBSERVICES))
{
	$langs->load("admin");
	print '<br/>'.img_picto($langs->trans('Generate'), 'high').$langs->trans("WarningModuleNotActive",'WebServices').'&nbsp;'.$langs->trans("ToActivateModule").img_picto($langs->trans('Generate'), 'high');
} elseif (empty($conf->global->WEBSERVICES_KEY)) {
	if (DOL_VERSION < '3.9.0')
		print '&nbsp;'.img_picto($langs->trans('Generate'), 'high').'
			<a href="'.$dolibarr_main_url_root.'/webservices/admin/webservices.php">'.$langs->trans('genererlacle').'</a>
			&nbsp;'.img_picto($langs->trans('Generate'), 'high');
	else
		print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
	}
print '</fieldset><br/>';
/*
print '<label>'.$langs->trans('InvoiceSynchronization').'</label>
			<select name="CYBEROFFICE_invoice">
				<option value="0" '.($conf->global->CYBEROFFICE_invoice==0?'selected="selected"':'').'>'.$langs->trans('No').'</option>
				<option value="1" '.($conf->global->CYBEROFFICE_invoice==1?'selected="selected"':'').'>'.$langs->trans('Yes').'</option>
			</select>
		<br/>';
*/
print '<fieldset>';
print '<label>'.$langs->trans('StockSynchronization').'</label>
			<select name="CYBEROFFICE_stock">
				<option value="0" '.($conf->global->CYBEROFFICE_stock==0?'selected="selected"':'').'>'.$langs->trans('No').'</option>
				<option value="1" '.($conf->global->CYBEROFFICE_stock==1?'selected="selected"':'').'>'.$langs->trans('Yes').'</option>
			</select>';
print '&nbsp;<label><input type="submit" class="button" value="'.$langs->trans("Save").'"></label>';

print '</fieldset></form>';
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
print '<td>'.$langs->trans("ShopAddress").'</td>';
if (! empty($conf->multicompany->enabled) && !$user->entity) print '<td>'.$langs->trans("Entity").'</td>';
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
//$sql.= " WHERE entity IN (".$user->entity.",".$conf->entity.")";
$sql.= " WHERE name like 'CYBEROFFICE_SHOP%'";
$sql.= " ORDER BY entity, name ASC";

$result = $db->query($sql);
if ($result)
{
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
		print '<input type="text" id="value_'.$i.'" class="flat inputforupdate" size="30" name="const['.$i.'][value]" value="'.htmlspecialchars($obj->value).'">';
		print '</td>';

		// Note
		print '<td>';
		print '<input type="text" id="note_'.$i.'" class="flat inputforupdate" size="80" name="const['.$i.'][note]" value="'.htmlspecialchars($obj->note,1).'">';
		print '</td>';

		// Entity limit to superadmin
		if (! empty($conf->multicompany->enabled) && !$user->entity)
		{
			print '<td>';
			print '<input type="text" class="flat" size="1" name="const['.$i.'][entity]" value="'.$obj->entity.'">';
			print '</td>';
			print '<td align="center">';
		}
		else
		{
			print '<td align="center">';
			print '<input type="hidden" name="const['.$i.'][entity]" value="'.$obj->entity.'">';
		}

		if ($conf->use_javascript_ajax)
		{
			print '<input type="checkbox" class="flat checkboxfordelete" id="check_'.$i.'" name="const['.$i.'][check]" value="1">';
		}
		else
		{
			print '<a href="'.$_SERVER['PHP_SELF'].'?rowid='.$obj->rowid.'&entity='.$obj->entity.'&action=delete'.((empty($user->entity) && $debug)?'&debug=1':'').'">'.img_delete().'</a>';
		}

		print "</td></tr>\n";

		print "\n";
		$i++;
	}
}


print '</table>';

if ($conf->use_javascript_ajax)
{
	print '<br>';
	print '<div id="updateconst" align="right">';
	print '<input type="submit" name="update" class="button" value="'.$langs->trans("Modify").'">';
	print '</div>';
	print '<div id="delconst" align="right">';
	print '<input type="submit" name="delete" class="button" value="'.$langs->trans("Delete").'">';
	print '</div>';
}

print "</form>\n";
print_fiche_titre($langs->trans("Association mode de reglement compte bancaire"));
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
        $sql.= " WHERE cp.active > 0 and cp.id > 0";
        $sql.= " ORDER BY cp.id";
        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
            	$var=!$var;
                $obj = $db->fetch_object($resql);
                print '<tr '.$bc[$var].'>';
                print '<td>'.$obj->code.'</td>';
                print '<td>'.$obj->libelle.'</td>';
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
if (DOL_VERSION >= '3.9.0') {
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
}
?>