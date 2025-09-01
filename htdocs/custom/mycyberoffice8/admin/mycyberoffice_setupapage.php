<?php
/**
 *  MyCyberOffice
 *
 *  @author    LVSinformatique <contact@lvsinformatique.com>
 *  @copyright 2016 LVSInformatique
 *  @license   NoLicence
 *  This source file is subject to a commercial license from LVSInformatique
 *  Use, copy or distribution of this source file without written
 *  license agreement from LVSInformatique is strictly forbidden.
 */
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

if (!$user->admin)
    accessforbidden();
$langs->load('stocks');
$langs->load('admin');
$langs->load('mycyberoffice@mycyberoffice8');
$form = new Form($db);
$formproduct = new FormProduct($db);
$msg = '';
        
if (!isset($conf->global->MYCYBEROFFICE_script) || $conf->global->MYCYBEROFFICE_script == 0 || !$conf->global->MYCYBEROFFICE_script) {
    $idscript = 0;
    $fromcat = GETPOST('TCategory');
    $tocat = GETPOST('TCategoryP');
} else {
    $split = explode('||', $conf->global->MYCYBEROFFICE_script);
    $idscript = $split[0];
    $fromcat = $split[1];
    $tocat = $split[2];
}

if (isset($_POST["action"]) && $_POST["action"] == 'script') {

    //$chaine = '0||'.$fromcat.'||'.$tocat;
    //dolibarr_set_const($db,"MYCYBEROFFICE_script", $chaine,'chaine',1,'',$conf->entity);

    if (!$_POST['CancelSynch']) {
        $categoryD = new Categorie($db);
        $categoryD->fetch($fromcat);
        $products=$categoryD->getObjectsInCateg('product', 1);
        $categoryP = new Categorie($db);
        $categoryP->fetch($tocat);
        asort($products);
        for($i = $idscript; $i < count($products); ++$i)
        {
            $newobject = new Product($db);
            $newobject->fetch($products[$i]);
            $categoryP->add_type($newobject, 'product');
            $newobject->update($products[$i], $user);
            // $categoryP->add_type($newobject,'product');
            // $result=$this->call_trigger('PRODUCT_MODIFY',$user);
            // setEventMessages('PRODUCT_MODIFY', null, 'warnings');

            // $result=$newobject->call_trigger('PRODUCT_PRICE_MODIFY',$user);
            // setEventMessages('PRODUCT_PRICE_MODIFY', null, 'warnings');

            $newobject->entrepot_id = $conf->global->{'MYCYBEROFFICE_warehouse'.GETPOST('shop', 'int')};
            $newobject->product_id = $newobject->id;
            // $result=$newobject->call_trigger('STOCK_MOVEMENT',$user);
            // setEventMessages('STOCK_MOVEMENT', null, 'warnings');

            $upload_dir = $conf->product->multidir_output[$newobject->entity] . '/' . dol_sanitizeFileName($newobject->ref);
            $filesarray = dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
            $array_picture = [];
            foreach($filesarray as $filearray)
            {
                if ($filearray['name']) {
                    $pos_point = strrpos($filearray['name'], '.');
                    $nom = substr($filearray['name'], 0, $pos_point); 
                    $picture = $filearray['fullname'];
                    $name = explode("/",$picture);
                    $name = $name[sizeof($name)-1];
                    $ext = preg_match('/(\.gif|\.jpg|\.jpeg|\.png|\.bmp)$/i', $name,$reg);
                    $imgfonction = '';
                    $ext = 'nok';
                    if (strtolower($reg[1]) == '.gif') {
                        $ext = 'gif';
                    }
                    if (strtolower($reg[1]) == '.png') {
                        $ext = 'png';
                    }
                    if (strtolower($reg[1]) == '.jpg') {
                        $ext = 'jpeg';
                    }
                    if (strtolower($reg[1]) == '.jpeg') {
                        $ext = 'jpeg';
                    }
                    if (strtolower($reg[1]) == '.bmp') {
                        $ext = 'wbmp';
                    }
                    if ($ext != 'nok') {
                        $file = ['tmp_name' => DOL_DOCUMENT_ROOT . "/custom/mycyberoffice8/images_temp/$object->id$nom.$ext", 
                            'name' => $name,
                        ];
                        $img = @call_user_func_array("imagecreatefrom".$ext,array($picture));
                        @call_user_func_array("image$ext", array($img, $file['tmp_name']));
                        @imagedestroy($img);
                        array_push($array_picture,array("name" => $nom, "url" => DOL_MAIN_URL_ROOT.'/custom/mycyberoffice8/images_temp/'.$object->id.$nom.'.'.$ext, "nom" => $nom.'.'.$ext));
                    }
                }
            }
            $newobject->mycyber = $array_picture;
            $result=$newobject->call_trigger('PICTURE_CREATE', $user);
            // setEventMessages('PICTURE_CREATE', null, 'warnings');

            setEventMessages(($i + 1) . '/' . count($products) . ' : Product id=' . $products[$i], null, 'mesgs');
            $chaine = ($i + 1) . '||' . $fromcat . '||' . $tocat;
            dolibarr_set_const($db, 'MYCYBEROFFICE_script', $chaine, 'chaine', 1, '', $conf->entity);
            // break;
        }
        if (count($products) == $i) {
            dolibarr_set_const($db, 'MYCYBEROFFICE_script', 0, 'chaine', 1, '', $conf->entity);
        }
    } else {
        dolibarr_set_const($db, 'MYCYBEROFFICE_script', 0, 'chaine', 1, '', $conf->entity);
    }
}
if (isset($_POST["action"]) && $_POST["action"] == 'set') {
    $shop = GETPOST('shop', 'int');

    if ($_POST['MYCYBEROFFICE_key' . $shop] && strlen($_POST['MYCYBEROFFICE_key'.$shop]) < 32) {
        $msg = '<div class="error">' . $langs->trans('Key length must be 32 character long') . '</div>';
    } else {
        dolibarr_set_const($db, 'MYCYBEROFFICE_InvoiceNumber', $_POST['MYCYBEROFFICE_InvoiceNumber'], 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($db,'MYCYBEROFFICE_key' . $shop, $_POST['MYCYBEROFFICE_key' . $shop], 'chaine', 0, '', $conf->entity);
        // dolibarr_set_const($db,"MYCYBEROFFICE_path",$_POST["MYCYBEROFFICE_path"],'chaine',0,'',$conf->entity);
        if (isset($_POST['MYCYBEROFFICE_debug'])) {
            dolibarr_set_const($db, 'MYCYBEROFFICE_debug', $_POST['MYCYBEROFFICE_debug'], 'chaine', 1, '', $conf->entity);
        }
        dolibarr_set_const($db, 'MYCYBEROFFICE_shop' . $shop, $_POST['MYCYBEROFFICE_shop' . $shop], 'chaine', 0, '', $conf->entity);
        if (isset($_POST['MYCYBEROFFICE_lang' . $shop])) {
            dolibarr_set_const($db, 'MYCYBEROFFICE_lang' . $shop, $_POST['MYCYBEROFFICE_lang' . $shop], 'chaine', 0, '', $conf->entity);
        } else {
            $defaultlang = dol_getIdFromCode($db, substr($conf->global->MAIN_LANG_DEFAULT, 0, 2), 'c_country', 'code', 'rowid');
            dolibarr_set_const($db, 'MYCYBEROFFICE_lang' . $shop, $defaultlang, 'chaine', 0, '', $conf->entity);
        }

        dolibarr_set_const($db, 'MYCYBEROFFICE_arraywarehouse' . $shop, (isset($_POST['MYCYBEROFFICE_arraywarehouse' . $shop]) ? json_encode($_POST['MYCYBEROFFICE_arraywarehouse' . $shop]) : json_encode([0=>0])), 'chaine', 1, '', $conf->entity);
        $arraywarehouseSelected = json_decode($conf->global->{"MYCYBEROFFICE_arraywarehouse".$shop});
        dolibarr_set_const($db, 'MYCYBEROFFICE_warehouse' . $shop, (isset($_POST['MYCYBEROFFICE_warehouse' . $shop]) ? $_POST['MYCYBEROFFICE_warehouse' . $shop] : 1), 'chaine', 0, '', $conf->entity);
        if (count($arraywarehouseSelected) == 1) {
            dolibarr_set_const($db, 'MYCYBEROFFICE_warehouse' . $shop, $arraywarehouseSelected[0], 'chaine', 0, '', $conf->entity);
        } else {
           dolibarr_set_const($db, 'MYCYBEROFFICE_warehouse' . $shop, 0, 'chaine', 0, '', $conf->entity);
        }

        dolibarr_set_const($db, 'MYCYBEROFFICE_pricelevel' . $shop,(isset($_POST['MYCYBEROFFICE_pricelevel' . $shop])? $_POST['MYCYBEROFFICE_pricelevel' . $shop] : 1), 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($db, 'MYCYBEROFFICE_Sexpedie' . $shop,($_POST['MYCYBEROFFICE_Sexpedie' . $shop] ? $_POST['MYCYBEROFFICE_Sexpedie' . $shop] : 4), 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($db, 'MYCYBEROFFICE_Slivre' . $shop,($_POST['MYCYBEROFFICE_Slivre' . $shop] ? $_POST['MYCYBEROFFICE_Slivre' . $shop] : 5), 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($db, 'MYCYBEROFFICE_Spaye' . $shop,($_POST['MYCYBEROFFICE_Spaye' . $shop] ? $_POST['MYCYBEROFFICE_Spaye' . $shop] : -1), 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($db, 'MYCYBEROFFICE_Sfacture' . $shop,(isset($_POST['MYCYBEROFFICE_Sfacture' . $shop]) ? $_POST['MYCYBEROFFICE_Sfacture' . $shop] : 2), 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($db, 'MYCYBEROFFICE_OnlyStock' . $shop,($_POST['MYCYBEROFFICE_OnlyStock' . $shop] ? $_POST['MYCYBEROFFICE_OnlyStock' . $shop] : 0), 'chaine', 0, '', $conf->entity);
        if ($conf->global->{'MYCYBEROFFICE_OnlyStock' . GETPOST('shop','int')} == 1)
            $MYCYBEROFFICE_Label = 0;
        else
            $MYCYBEROFFICE_Label = $_POST['MYCYBEROFFICE_Label' . $shop];
        dolibarr_set_const($db, 'MYCYBEROFFICE_Label' . $shop, ($MYCYBEROFFICE_Label ? $MYCYBEROFFICE_Label : 0), 'chaine', 0, '', $conf->entity);
        $code_country = "'".$mysoc->country_code . "'";
        $num = $form->load_cache_vatrates($code_country);
        foreach ($form->cache_vatrates as $rate)
        {
            dolibarr_set_const($db,"MYCYBEROFFICE_tax" . $shop . $rate['txtva'], (isset($_POST["MYCYBEROFFICE_tax" . $shop . number_format($rate['txtva'], 2, '-', '')]) ? $_POST["MYCYBEROFFICE_tax" . $shop . number_format($rate['txtva'], 2, '-', '')] : 0), 'chaine', 0, '', $conf->entity);
        }
        dolibarr_set_const($db,"MYCYBEROFFICE_stock_theorique",($_POST["stocksync"]?$_POST["stocksync"]:0),'chaine',0,'',$conf->entity);

        $msg = "<font class=\"ok\">" . $langs->trans('SetupSaved') . "</font>";
    }
}

llxHeader();

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans('BackToModuleList') . '</a>';
print_fiche_titre($langs->trans("mycyberofficeSetup"),$linkback,'setup');
if ($msg) dol_htmloutput_mesg($msg);
// Mode
$h = 0;
$head = [];
$head[$h][0] = $_SERVER['PHP_SELF'] . '?shop=' . $h;
$head[$h][1] = $langs->trans('Setup');
$head[$h][2] = $langs->trans('Setup');
$h++;

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."const WHERE name LIKE 'CYBEROFFICE_SHOP%' ORDER BY name";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql))
    {
        $h = (int)substr($obj->name,-2);
        $head[$h][0] = $_SERVER["PHP_SELF"]."?shop=".substr($obj->name,-2);
        $head[$h][1] = $langs->trans("Shop").substr($obj->name,-2);
        $head[$h][2] = $langs->trans("Shop").substr($obj->name,-2);
        $head[$h][3] = substr($obj->name,-2);//shop
        //$head[$h][4] = $obj->value;//indice
        $head[$h][5] = $obj->note;//path
        //$h++;
    }
}
$titre='MyCyberOffice';
$picto = dol_buildpath('custom/mycyberoffice8/img/object_mycyberoffice30.png',1);
if (GETPOST('shop','int') > 0)
    $active = $langs->trans("Shop").GETPOST('shop','int');//$head[GETPOST('shop',int)][3];
else
    $active = $langs->trans("Setup");

dol_fiche_head($head, $active, $titre, 0, $picto, 1);

$var=true;
//print '<div id="content" class="nobootstrap">';
if (GETPOST('shop','int') == 0) {
    print '
        <fieldset><legend><img src="../img/info.png" /> '.$langs->trans('MyCyberOffice Infos').'</legend>
    1) '.$langs->trans('CyberOfficemustbeoperational').'<br/>
    2) '.$langs->trans('OpentheWebservice').'<br/>
    3) '.$langs->trans('Createanaccesskey').' <br/>
    </fieldset><br/>';
    print '
        <fieldset><legend><img src="../img/choose.gif" /> '.$langs->trans('MyCyberOffice Documentation').'</legend>
    <a href="https://lvs-1-fo.gitbook.io/mycyberoffice/fr" target="_blank">'.$langs->trans('MyCyberOffice Documentation').'</a>
    </fieldset><br/>';
}
if (GETPOST('shop','int') > 0) {
    print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="set">';
    // print '<input type="hidden" name="indice" value="'.$head[(int)GETPOST('shop','int')][4].'">';
    print '<input type="hidden" name="shop" value="'.$head[(int)GETPOST('shop','int')][3].'">';
    $myglobal = "MYCYBEROFFICE_key" . GETPOST('shop','int');
    print '
        <fieldset><legend><img src="../img/object_mycyberoffice.png" alt="" title="" height="30px"/><b> '.$langs->trans('Settings').'</b></legend>
    <label>'.$langs->trans('key').'</label>
    <div class="margin-form">
    <input type="text" size="40" name="MYCYBEROFFICE_key'.GETPOST('shop','int').'" id="MYCYBEROFFICE_key'.GETPOST('shop','int').'" value="'.htmlentities($conf->global->$myglobal, ENT_COMPAT, 'UTF-8').'" />
    '.$langs->trans('CopiericilaclegenereedansPrestashopWebservices.').'
    </div>
    <br/>
    <label>'.$langs->trans('chemindaccesavotreboutique').'</label>
    <div class="margin-form">
    <input type="text" size="40" name="CYBEROFFICE_SHOP'.GETPOST('shop','int').'" value="'.$head[(int)GETPOST('shop','int')][5].'" readonly/> 
    </div>
    <br/>
    <label>'.$langs->trans('Warehousetouse').'</label>
    <div class="margin-form">';
    print '<input type="text" size="5" name="MYCYBEROFFICE_warehouse'.GETPOST('shop','int').'" value="'.(isset($conf->global->{"MYCYBEROFFICE_warehouse".GETPOST('shop','int')})?$conf->global->{"MYCYBEROFFICE_warehouse".GETPOST('shop','int')}:1) .'" />';
    print $langs->trans('ChooseWarehouse');
    $arraywarehouse = [];
    $arraywarehouseSelected = [];
    if (isset($conf->global->{"MYCYBEROFFICE_arraywarehouse".GETPOST('shop','int')})) {
        $arraywarehouseSelected = json_decode($conf->global->{"MYCYBEROFFICE_arraywarehouse".GETPOST('shop','int')});
    } else {
        $arraywarehouseSelected[0] = $conf->global->{"MYCYBEROFFICE_warehouse".GETPOST('shop','int')};
    }
    $show_empty = 0;
    $formproduct->loadWarehouses();
    foreach ($formproduct->cache_warehouses as $id => $arraytypes) {
        $arraywarehouse[$id] = $arraytypes['label'];
    }
    $moreparam = 'multiple';
    print $form->multiselectarray("MYCYBEROFFICE_arraywarehouse".GETPOST('shop','int'), $arraywarehouse, $arraywarehouseSelected, 0, 0, '', 0, '30%');

    print '</div>
    <br/>';

    print '<div class="margin-form">';
    $type_stock = !empty($conf->global->MYCYBEROFFICE_stock_theorique) ? $conf->global->MYCYBEROFFICE_stock_theorique : 0;
    echo $langs->trans('StockSync'). ' : ' .$form->selectarray('stocksync',array(0=>$langs->trans('PhysicalStock'),1=>$langs->trans('VirtualStock')),$type_stock);
    print '</div><br/>';
    print '<label>'.$langs->trans('OnlyStock').'</label>
    <div class="margin-form">
    <select name="MYCYBEROFFICE_OnlyStock'.GETPOST('shop','int').'">
        <option value="0" '.($conf->global->{"MYCYBEROFFICE_OnlyStock".GETPOST('shop','int')}==0?'selected="selected"':'').'>'.$langs->trans('No').'</option>
    <option value="1" '.($conf->global->{"MYCYBEROFFICE_OnlyStock".GETPOST('shop','int')}==1?'selected="selected"':'').'>'.$langs->trans('Yes').'</option>
    </select>
    </div>
    <br/>';
    print '<label>'.$langs->trans('LabelSynchronization').'</label>
    <div class="margin-form">
    <select name="MYCYBEROFFICE_Label'.GETPOST('shop','int').'">
        <option value="0" '.($conf->global->{"MYCYBEROFFICE_Label".GETPOST('shop','int')}==0?'selected="selected"':'').'>'.$langs->trans('No').'</option>
    <option value="1" '.($conf->global->{"MYCYBEROFFICE_Label".GETPOST('shop','int')}==1?'selected="selected"':'').'>'.$langs->trans('Yes').'</option>
    </select>
    </div>
    <br/>';

    print '<label>'.$langs->trans('PriceLevel').'</label>
    <div class="margin-form">
        <select name="MYCYBEROFFICE_pricelevel'.GETPOST('shop','int').'">';
        /*print $conf->global->PRODUIT_MULTIPRICES.$conf->global->PRODUIT_MULTIPRICES_LIMIT;*/
    if (empty($conf->global->PRODUIT_MULTIPRICES) || $conf->global->PRODUIT_MULTIPRICES == 0)
        $PRODUIT_MULTIPRICES_LIMIT = 1;
    else
        $PRODUIT_MULTIPRICES_LIMIT = $conf->global->PRODUIT_MULTIPRICES_LIMIT;

    for ($i = 1; $i <= $PRODUIT_MULTIPRICES_LIMIT; $i ++)
	{
        print '<option value="'.$i.'" '.($conf->global->{"MYCYBEROFFICE_pricelevel".GETPOST('shop','int')}==$i?'selected="selected"':'').'>'.$i.'</option>';
    }
    print '</select>
    </div>
	<br/>
<!--
    <label>'.$langs->trans('Lang id').'</label>
    <div class="margin-form">
    <input type="text" size="5" name="MYCYBEROFFICE_lang' . GETPOST('shop', 'int') . '" value="' . (!isset($conf->global->{"MYCYBEROFFICE_lang" . GETPOST('shop','int')}) ? 1 : $conf->global->{"MYCYBEROFFICE_lang" . GETPOST('shop','int')}) . '" />
    </div>
    <br/>
-->
    <label>'.$langs->trans('shop id').'</label>
    <div class="margin-form">
    <input type="text" size="5" name="MYCYBEROFFICE_shop'.GETPOST('shop','int').'" value="'.(!$conf->global->{"MYCYBEROFFICE_shop".GETPOST('shop','int')}?1:$conf->global->{"MYCYBEROFFICE_shop".GETPOST('shop','int')}).'" />
    </div>
    <br/>

    <label>'.$langs->trans('InvoiceNumberSynchronization').'</label>
    <div class="margin-form">
    <select name="MYCYBEROFFICE_InvoiceNumber">
    <option value="0" '.($conf->global->MYCYBEROFFICE_InvoiceNumber==0?'selected="selected"':'').'>'.$langs->trans('No').'</option>
    <option value="1" '.($conf->global->MYCYBEROFFICE_InvoiceNumber==1?'selected="selected"':'').'>'.$langs->trans('Yes').'</option>
    </select>
    </div>
    <br/>
    <label>'.$langs->trans('PrestashopOrdersstatuses').'</label>
    <div class="margin-form">
    <input type="text" size="5" name="MYCYBEROFFICE_Sexpedie'.GETPOST('shop','int').'" value="'.(!$conf->global->{'MYCYBEROFFICE_Sexpedie'.GETPOST('shop','int')}?4:$conf->global->{"MYCYBEROFFICE_Sexpedie".GETPOST('shop','int')}).'" />'.$langs->trans('Expedie').'
    </div>
    <div class="margin-form">
    <input type="text" size="5" name="MYCYBEROFFICE_Slivre'.GETPOST('shop','int').'" value="'.(!$conf->global->{'MYCYBEROFFICE_Slivre'.GETPOST('shop','int')}?5:$conf->global->{'MYCYBEROFFICE_Slivre'.GETPOST('shop','int')}).'" />'.$langs->trans('Livre').'
    </div>
        <div class="margin-form">
    <input type="text" size="5" name="MYCYBEROFFICE_Spaye'.GETPOST('shop','int').'" value="'.(!$conf->global->{'MYCYBEROFFICE_Spaye'.GETPOST('shop','int')}?-1:$conf->global->{'MYCYBEROFFICE_Spaye'.GETPOST('shop','int')}).'" />'.$langs->trans('Paye').'
    </div>
    <br/>
    <label>'.$langs->trans('VAT').'</label>
    <div class="margin-form">';
    //print __LINE__.'<br/>';
    if ($conf->global->{'MYCYBEROFFICE_key' . GETPOST('shop','int')} && $conf->global->{'CYBEROFFICE_SHOP' . GETPOST('shop','int')}) 
        $test = testConfig(GETPOST('shop','int'), $head[(int)GETPOST('shop','int')][5]);
    //print  $test['result']['result_code'];
    //print __LINE__.'<br/>';
    $code_country="'".$mysoc->country_code."'";
    //print __LINE__.'::'.$code_country.'<br/>';
    $num = $form->load_cache_vatrates($code_country);
    //print __LINE__.'::'.$num.'<br/>';
    //print_r($mysoc);
    /*print '<pre>';print_r($test);print '</pre>';*/
    print '<table><tr><td style="text-align:center;font-weight:bold">eshop</td><td style="text-align:center;font-weight:bold">Dolibarr</td></tr>';
    if (is_array($test) && isset($test['result']['result_code']) && $test['result']['result_code'] != 'KO') {
        foreach ($form->cache_vatrates as $rate)
        {
            print '<tr>';
            $html = '<td><select name="MYCYBEROFFICE_tax' . GETPOST('shop','int') . number_format($rate['txtva'], 2, '-', '') . '">';
            $html.= '<option value="-1">-SELECT ONE TAX-</option>';
            foreach ($test['tax'] as $k => $v)
            {
                $html.= '<option value="'.$v['id_tax_rules_group'].'" '.($conf->global->{"MYCYBEROFFICE_tax" . GETPOST('shop','int') . $rate['txtva']}==$v['id_tax_rules_group']?'selected="selected"':'').'>'.$v['name'].'</option>';
            }
            $html.='</select></td>';
            print $html;
            print '<td style="text-align:right;font-weight:bold">'.(version_compare(DOL_VERSION, '6.0.0', '<')?$rate['libtva']:$rate['label']).'</td></tr>';
        }
    }
    print '</table>
        </div>
        <br/>
        <label>'.$langs->trans('Debug Mode').'</label>
        <div class="margin-form">
        <select name="MYCYBEROFFICE_debug" disabled>
        <option value="0" ' . (!isset($conf->global->MYCYBEROFFICE_debug) || $conf->global->MYCYBEROFFICE_debug == 0 ? 'selected="selected"' : '') . '>' . $langs->trans('No') . '</option>
        <option value="1" ' . (isset($conf->global->MYCYBEROFFICE_debug) && $conf->global->MYCYBEROFFICE_debug == 1 ? 'selected="selected"' : '') . '>' . $langs->trans('Yes') . '</option>
        </select>
        </div>
        <br/>
        <label>Permissions</label>
        <div class="margin-form">
        <a href="'.$head[(int)GETPOST('shop','int')][5].'api/" target="_blank">'.$langs->trans('Click_to_see_permissions_for_this_key').'</a>
        </div>
        <br/>
    ';

    print '<br/><br/><label><input type="submit" class="button" value="'.$langs->trans("Save").'"></label>';
    print '</fieldset>';
    print "</form>\n";

    print '<form id="parametrage" action="'.$_SERVER["PHP_SELF"].'?#parametrage" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="script">';
    // print '<input type="hidden" name="indice" value="'.$head[(int)GETPOST('shop','int')][4].'">';
    print '<input type="hidden" name="shop" value="'.$head[(int)GETPOST('shop','int')][3].'">';
    print '
        <fieldset><legend><img src="../img/object_mycyberoffice.png" alt="" title="" height="30px"/><b> '.$langs->trans('ScriptSynchro').'</b></legend>';
    $category=new Categorie($db);
    //////////////////
    $TCategoryD=array();
    $TCategoryP=array();
    $sql = "SELECT rowid, fk_parent, entity, label, fk_soc, visible, type, import_key
        FROM ".MAIN_DB_PREFIX."categorie
        WHERE entity IN (".getEntity('category',1).") AND type = 0";
    $resql = $db->query($sql);
    if ($resql)
    {
        while ($rec = $db->fetch_array($resql))
        {
            $Tab[$rec['rowid']] = $rec;
        }
    }
///////////////////////////////
    //$TCategoryD=[];
    //$TCategoryP=[];
    if (! empty($Tab)) {
        foreach($Tab as &$c)
        {
            $category->fetch($c['rowid']);
            $nom = strip_tags( implode('', $category->print_all_ways() ));
            if (isset($c['import_key']) && substr($c['import_key'],0,4) == ('P'.$conf->global->{"CYBEROFFICE_SHOP".GETPOST('shop','int')}.'-'))
            {
                $TCategoryP[$c['rowid']] = $nom;
            } else {
                $TCategoryD[$c['rowid']] = $nom;
            }
        }
        asort($TCategoryP);
        asort($TCategoryD);
        if (!isset($conf->global->MYCYBEROFFICE_script) || $conf->global->MYCYBEROFFICE_script == 0 || !$conf->global->MYCYBEROFFICE_script)
        {
            $TCategorySelectedD=0;
            $TCategorySelectedP=0;
        } else {
            $split = explode("||", $conf->global->MYCYBEROFFICE_script);
            $TCategorySelectedD=$split[1];
            $TCategorySelectedP=$split[2];
        }
    }
    if (!isset($conf->global->MYCYBEROFFICE_script) || $conf->global->MYCYBEROFFICE_script == 0 || !$conf->global->MYCYBEROFFICE_script)
    {
        $moreparam = 'onchange="nbproduct(this)"';
        $moreparamP = '';
    } else {
        $moreparam = 'onchange="nbproduct(this)" disabled';
        $moreparamP = 'disabled';
    }
    echo'<br/>'.$langs->trans('CategoryToIncludeDescription').'<br/>';
    echo '<br/><label>'.$langs->trans('CategoryToInclude'). ' : </label><div class="margin-form">' . 
    $form->selectarray('TCategory', $TCategoryD, $TCategorySelectedD, 0, 0, 0, $moreparam).'</div>';
    echo '<br/><label>'.$langs->trans('ToCategorie').'  : </label><div class="margin-form">' . 
    $form->selectarray('TCategoryP', $TCategoryP, $TCategorySelectedP, 0, 0, 0, $moreparamP).'</div><br />';
    if (!isset($conf->global->MYCYBEROFFICE_script) || $conf->global->MYCYBEROFFICE_script == 0 || !$conf->global->MYCYBEROFFICE_script)
    {
        print '<br/><input type="submit" class="button" value="'.$langs->trans('Validate').'">'; 
    } else {
        print '<br/>';
        $split = explode("||", $conf->global->MYCYBEROFFICE_script);
        //print 'ERREUR  : Seulement '.$split[0]. ' produits de la catégorie '.$split[1];
         //$category->fetch($split[1]);
        //$nomcat = strip_tags( implode('', $category->print_all_ways() ));
        print $langs->trans('errorsynch',$split[0]);
        print '<input type="submit" class="button" name="ContinueSynch" value="'.$langs->trans('ContinueSynch').'">';
        print '<input type="submit" class="button" name="CancelSynch" value="'.$langs->trans('CancelSynch').'">'; 
    }
    print '<div id="nbproducts" style="display: inline;color: red;"></div>';

    print '</fieldset>';
    print "</form>\n";

    $test = testConfig(GETPOST('shop','int'), $head[(int)GETPOST('shop','int')][5]);
}
print '</div>';
print '
    <script type="text/javascript" language="javascript">
        function nbproduct(selectObject)
        {
            var myvalue = selectObject.value; 
            jQuery.ajax({
                type : "POST",
                url : "ajax1.php",
                data : "id=" + myvalue,
                success : function(data){
                    var text = " " + data + " " + "'.$langs->trans('nbproducts').'";
                    jQuery("#nbproducts").html(text);
                }
            });
        }
    </script>
';

llxFooter();

function testConfig($myshop, $myurl)
{
    global $conf;
    //$ws_dol_url = $conf->global->{'CYBEROFFICE_SHOP' . $myshop} . 'modules/mycyberoffice/server_config_soap.php';
    //$ws_method  = 'getConfig';
    $ns = 'http://www.lvsinformatique.com/ns/';

    // Set the WebService URL
    $options = ['location' => $myurl . 'modules/mycyberoffice/server_config_soap.php',
        'uri' => $myurl, 
        'wsdl_cache' => 0,
        'exceptions' => true,
        'trace' => 1,
    ];

    try {
        $soapclient = new SoapClient(null, $options);
    } catch(Throwable $e) {
        print 'Exception Error!';
        print var_dump($e->getMessage());
    }

    $authentication = ['dolibarrkey' => htmlentities($conf->global->{'MYCYBEROFFICE_key' . $myshop}, ENT_COMPAT, 'UTF-8'),
        'sourceapplication' => 'LVSInformatique',
        'login' => '',
        'password' => '',
        'shop' => $conf->global->{'MYCYBEROFFICE_shop'.$myshop},
        'lang' => 1,
        'myurl' => $_SERVER["PHP_SELF"],
    ];
    //print_r($authentication );
    $myparam = ['repertoire' => $myurl,
        'supplier' => 1,
        'category' => 2,
        'myurl' => $_SERVER["PHP_SELF"],
    ];
    //print_r($myparam );
    //$parameters = array('authentication'=>$authentication, $myparam);

    try {
        if (htmlentities($conf->global->{'MYCYBEROFFICE_key' . $myshop}, ENT_COMPAT, 'UTF-8'))
            $result = $soapclient->getConfig($authentication, $myparam, $ns, '');
    } catch (SoapFault $fault) {
        print 'faultstring = ' . $fault->faultstring;
        if($fault->faultstring != 'Could not connect to host' && $fault->faultstring != 'Not Found') {
            echo '<pre>';
            print_r($fault);
            echo '</pre>';
            //throw $fault;
        }
    }

    if (! $result || $result['result']['result_label'] != 'OK') {
        print '<br/><div class="error">**NOK**'. __LINE__.'::'.$result['result']['result_label'].'</div>';
        //$result = '**NOK**';
        $result = ['result' => ['result_code' => 'KO',
            ],
            'repertoire' => $myurl,
            'repertoireTF' => 'KO',
            'webservice' => 'KO',
            'dolicyber' => 'KO',
            'indice' => -1,
        ];
    }

    if (isset($conf->global->MYCYBEROFFICE_debug) && $conf->global->MYCYBEROFFICE_debug == 1) {
        var_dump(htmlspecialchars($soapclient->__getLastResponse()));
        echo "getLastResponse: " . $soapclient->__getLastResponse();
        echo "<br/>getLastRequest: " . $soapclient->__getLastRequest();
        echo "<br/>getLastResponseHeaders: " . $soapclient->__getLastResponseHeaders();

        print '<pre>';print_r($result); print '</pre>';
        // show soap request and response
        echo "<h2>Request</h2>";
        echo "<pre>" . htmlspecialchars($soapclient->request, ENT_QUOTES) . "</pre>";
        echo "<h2>Response</h2>";
        echo "<pre>" . htmlspecialchars($soapclient->response, ENT_QUOTES) . "</pre>";
    }

    return $result;

    //return $result['description']['repertoire'];
}
