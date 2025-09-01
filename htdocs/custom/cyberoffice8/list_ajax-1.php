<?php
/* 
 * @author 	LVSinformatique <contact@lvsinformatique.com>
 * @copyright  	2020 LVSInformatique
 * This source file is subject to a commercial license from LVSInformatique
 * Use, copy or distribution of this source file without written
 * license agreement from LVSInformatique is strictly forbidden.
 */
require_once '../../main.inc.php';

$l = GETPOST('l', 'int');
$c = safe(GETPOST('c'));
$n = safe(GETPOST('n'));

//connexion à la base de données
define('DB_NAME', $dolibarr_main_db_name);
define('DB_USER', $dolibarr_main_db_user);
define('DB_PASSWORD', $dolibarr_main_db_pass);
define('DB_HOST', $dolibarr_main_db_host);

$link = $db->connect( DB_HOST , DB_USER , DB_PASSWORD , DB_NAME);
$db->select_db( DB_NAME);

$sql0 = "SELECT * "
    . " FROM ".MAIN_DB_PREFIX."const "
    . " WHERE name LIKE 'CYBEROFFICE_SHOP%'";
$req0 = $db->query($sql0);
if ($req0) {
    while ($obj0 = $db->fetch_object($req0))
    {
        if ($eshop)
            $eshop.=',';
        $eshop.= "'P".$obj0->value."-'";
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
    . " WHERE s.client IN (1,3) AND (s.status IN (1)) ";
if ($l==1) {
    $sql.= " AND SUBSTR(s.import_key,1,4) IN (".$eshop.")";
}
if ($l==2) {
    $sql.= " AND (SUBSTR(s.import_key,1,4) NOT IN (".$eshop.") OR s.import_key IS NULL)";
}
if ($c && $c!='') {
    $sql.= " AND s.code_client LIKE '%".$c."%'";
}
if ($n && $n!='') {
    $sql.= " AND (s.nom LIKE '%".$n."%' OR s.name_alias LIKE '%".$n."%' OR s.import_key LIKE '%".$n."%')";
}
    $sql.= " ORDER BY s.nom ASC ";

$req1 = $db->query($sql);
if ($req1) {
    $list2 = '<tr class="oddeven"><td colspan="4" align="center" style="color: red;">Find '.$db->num_rows($req1).' rows</td></tr>';
    while ($obj2 = $db->fetch_object($req1))
    {
        $title = $obj2->name_alias."<br>";
        $title.= $obj2->name."<br>";
        $title.= $obj2->address.'<br>';
        $title.= $obj2->zip.' '.$obj2->town.'<br>';
        $title.= $obj2->email.'<br>';
        $title.= $obj2->phone.'<br>';
        //$companystatic->fetch($obj2->rowid);
        //$toto= $companystatic->getNomUrl(1, '', 100, 0, 1);
        $tooltip = '<a href="#" class="classfortooltip" alt="'.dol_escape_htmltag($title, 1).'" title="'.dol_escape_htmltag($title, 1).'">'.$obj2->code_client.'</a>';
        $list2.= '<tr id="'.($l==1?'T':'F').$obj2->rowid.'" onclick="'.($l==1?'ValuesTo':'ValuesFrom').'(this.id)" class="oddeven"><td nowrap>'.$tooltip.'</td><td>'.$obj2->name.'</td><td>'.$obj2->name_alias.'</td><td>'.$obj2->import_key.'</td></tr>';
    }
}
$str = '<tbody>'.$list2.'</tbody>';
//$str=utf8_decode($str);
//$str = urlencode($str);
print $str;
function safe($var)
{
    global $db;
	$var = $db->escape($var);
	$var = addcslashes($var, '%_');
	$var = trim($var);
	$var = htmlspecialchars($var);
	return $var;
}
