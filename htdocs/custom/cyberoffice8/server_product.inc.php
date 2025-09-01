<?php
/*
*  @author 	LVSinformatique <contact@lvsinformatique.com>
*  @copyright  	2014 LVSInformatique
*  @licence   	All Rights Reserved
*  This source file is subject to a commercial license from LVSInformatique
*  Use, copy, modification or distribution of this source file without written
*  license agreement from LVSInformatique is strictly forbidden.
*/

// This is to make Dolibarr working with Plesk
//define('NOCSRFCHECK', 1);

// check codebarre empty($conf->barcode->enabled)
//check ref

set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');
require_once '../../master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/cunits.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/cyberoffice8/class/cyberoffice.class.php';

dol_syslog("cyberoffice::Call Dolibarr webservices interfaces::ServerProduct_ws",6,0, '_cyber');

//sleep(15);
//set_time_limit(3600);
@ini_set('default_socket_timeout', 320);
//@ini_set('soap.wsdl_cache_enabled', '0');
//@ini_set('soap.wsdl_cache_ttl', '0');
$langs->load("main");
global $db,$conf,$langs;
$authentication=array();
$params=array();
$authentication=$_POST['authentication'];
$params = (isset($_POST['params'])?$_POST['params']:array());
$now=dol_now();
dol_syslog("cyberoffice::Function: server_product.inc login=".$authentication['login'],6,0, '_cyber');

if ($authentication['entity'])
    $conf->entity=$authentication['entity'];
if (!empty($conf->global->MAIN_MODULE_MULTICOMPANY)) {
    dol_include_once(DOL_DOCUMENT_ROOT . '/custom/multicompany/class/actions_multicompany.class.php');
    $mc = new ActionsMulticompany($db);
    $returnmc = $mc->switchEntity($authentication['entity']);
    $conf->global->WEBSERVICES_KEY=dolibarr_get_const($db, 'WEBSERVICES_KEY', $authentication['entity']);
}
    // Init and check authentication
$objectresp=array();
$errorcode='';$errorlabel='';
$error=0;
$errortot=0;
$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
if ($error)
{
    $objectresp = array('result'=>array('result_code' => 'ko', 'result_label' => 'ko'),'webservice'=>'login');
    $error++;
    return $objectresp;
}

$error=0;
dol_syslog("CyberOffice_server_product::line=".__LINE__,6,0, '_cyber');
include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

$newobject=new Product($db);
$newobject->fk_default_warehouse = 0;
//$db->begin();
$count_params = (is_array($params)?count($params):0);
dol_syslog("CyberOffice_server_product::nb produits=".$count_params,6,0, '_cyber');
$socid_old=0;

$user = new User($db);
$cunits = new CUnits($db);
$user->fetch('', $authentication['login'],'',0);
$user->getrights();

$cyber = new Cyberoffice;
$cyber->entity = 0;
$cyber->myurl = $authentication['myurl'];
$indice = $cyber->numShop();
$indice_name = $cyber->numShop(1);
$objectcat=new Categorie($db);
$catparent0 = array();
$catparent0 = $objectcat->rechercher(null,$cyber->myurl,'product');

foreach ($catparent0 as $cat_parent0)
{
    $idparent0 = $cat_parent0->id;
}
$ttcht = (isset($conf->global->{"MYCYBEROFFICEttcht".$indice_name})?$conf->global->{"MYCYBEROFFICEttcht".$indice_name}:'') ;
if (!$ttcht || ($ttcht != 'HT' && $ttcht != 'TTC'))
    $ttcht = 'HT';
$usevariant = false;
if (isset($conf->global->MAIN_MODULE_VARIANTS) && $conf->global->MAIN_MODULE_VARIANTS == 1 && $conf->global->CYBEROFFICE_variant == 1) {
    $usevariant = true;
}

$list_ok = '';
$list_id = '';
//$this->myLog(print_r($params,true));
if (is_array($params) && sizeof($params)>0) {
	dol_syslog('ici'.print_r($params,1),3);
    foreach ($params as $product)
    {
		dol_syslog(print_r($product, true),6,0, '_cyber');
        $newobject=new Product($db);
		$db->begin();
        $myref = (isset($product['reference'])?dol_sanitizeFileName(stripslashes($product['reference'])):(isset($product['id_product'])?'P'.$product['id_product']:''));
        dol_syslog("CyberOffice_server_product::traitement produit=".(isset($product['id_product'])?$product['id_product'].'::'.$myref:''),6,0, '_cyber');
		//$product['reference'] = $myref;
	/*****recherche de la correspondance
	************************************/
	if (isset($product['match']) && $product['match'] == '{ref}' && !$product['reference']) {
        $list_ok.= "<br/>ERROR ref ".$product['id_product'];
            dol_syslog("CyberOffice_server_product::ERROR ref ".$product['id_product'].':'.$product['reference'],6,0, '_cyber');
        continue;
	}
    /***** test produit parent
	**************************/
	$nbr = strpos((isset($product['id_product'])?$product['id_product']:''), '-');
	if ($nbr === false) {
            $nbr=0;
	}
        /* variant */
        if ($usevariant == true && $product['match'] == '{ref}') {
            /*if ($nbr==0) continue;*/
        } else {
            $product_id_product = "P".$indice."-".substr((isset($product['id_product'])?$product['id_product']:''),0,$nbr);
            $sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'product WHERE import_key="'.$product_id_product.'"';
            dol_syslog("CyberOffice_server_product::fetch combination sql=".$sql.' nbr='.$nbr,6,0, '_cyber');
            if ($nbr>0) {
                $resql = $db->query($sql);
                if ($resql) {
                    if ($db->num_rows($resql) > 0) {
                        $res = $db->fetch_array($resql);
                        $produit_id=$res['rowid'];
                    } else
                        $produit_id=0;
                } else
                    $produit_id=0;
                if ($produit_id > 0) {
                    $sql = "UPDATE ".MAIN_DB_PREFIX."product SET";
                    $sql .= " import_key='P".$indice."-".$product['id_product']."'";
                    $sql .= ", ref='".$product['reference']."'";
                    $sql.= " WHERE rowid=".$produit_id;
//					$this->myLog("server_product::update combination - sql=".$sql);
                    $resql = $db->query($sql);
                    //$db->commit();
                }
            }
        }
	$sql = "SELECT rowid";
	$sql.= " FROM ".MAIN_DB_PREFIX."product";
	if (isset($product['match']) && $product['match'] == '{ref}')
            $sql.= " WHERE ref = '".$product['reference']."'";
	else
            $sql.= " WHERE import_key = 'P".$indice."-".(isset($product['id_product'])?$product['id_product']:'')."'";

	$resql = $db->query($sql);
	if ($resql) {
            if ($db->num_rows($resql) > 0) {
                $res = $db->fetch_array($resql);
		$produit_id=$res['rowid'];
            } else
                $produit_id=0;
        } else
            $produit_id=0;
        if ($db->num_rows($resql) > 1 && $product['match'] == '{ref}') {
            //$error++;
            $list_ok.= "<br/>ERROR ref ".$product['reference']." x ".$db->num_rows($resql);
            dol_syslog("CyberOffice_server_product::ERROR ref ".$product['reference']." x ".$db->num_rows($resql),6,0, '_cyber');
            continue;
	}
	/*****creation
	**************/
    $newobject->price_base_type = $ttcht;
	$newobject->price = (isset($product['price'])?$product['price']:0);
	$newobject->price_ttc = price2num((isset($product['price'])?$product['price']:0) * (1 + ((isset($product['tax_rate'])?$product['tax_rate']:0) / 100)),'MU');
	$newobject->tva_tx = (isset($product['tax_rate'])?$product['tax_rate']:0);

        if (!empty($conf->global->MAIN_MULTILANGS)) {
            $outputlangs = $langs;
            $outputlangs->setDefaultLang($product['LOCALELANG']);
        }
        $newobject->label				= (isset($product['name'])?$product['name']:'');
        if (!$conf->global->cu0032) {
        if (isset($product['description_short']) && is_array($product['description_short'])) {
            foreach ($product['description_short'] as $key => $myname) {
                $newkey = $product['ALLLANGUAGES'][$key];
                if (!empty($conf->global->MAIN_MULTILANGS)) {
                    $newobject->multilangs["$newkey"]["description"]=($myname);
                }
                if ($newkey == $product['LOCALELANG']) {
                    $newobject->description=($myname);
                }
            }
        } else {
            $newobject->description=(isset($product['description_short'])?($product['description_short']):'');
        }
        }
        if ($conf->global->CYBEROFFICE_NoDescription != 1) {
            if (isset($product['description']) && !is_null(trim($product['description'])) && !empty(trim($product['description']))) {
                $newobject->array_options['options_longdescript'] = trim($product['description']);
            }
        }

    $newobject->type				= 0;
	$newobject->status				= (isset($product['active'])?$product['active']:0);
	$newobject->status_buy			= 1;
    $newobject->barcode = '';
    $newobject->barcode_type = null;
	if ($conf->global->MAIN_MODULE_BARCODE) {//ean 2 upc 3 isbn 4
            if (isset($product['ean13']) && $product['ean13']) {
                $newobject->barcode				= $product['ean13'];
		$newobject->barcode_type		= 2;
            }
            elseif (isset($product['upc']) && $product['upc']) {
                $newobject->barcode				= $product['upc'];
		$newobject->barcode_type		= 3;
            }
            elseif (isset($product['isbn']) && $product['isbn']) {
                $newobject->barcode				= $product['isbn'];
		$newobject->barcode_type		= 4;
            } else {
                $newobject->barcode = '';
                $newobject->barcode_type = null;
            }
	}
	$newobject->ref					= '';
	if (!empty($product['reference'])) {
            $newobject->ref = $product['reference'];
            dol_syslog("CyberOffice_server_product::ref1 =".$newobject->ref,6,0, '_cyber');
	} else {
            // Load object modCodeProduct
            $module=(! empty($conf->global->PRODUCT_CODEPRODUCT_ADDON)?$conf->global->PRODUCT_CODEPRODUCT_ADDON:'mod_codeproduct_leopard');
            if ($module != 'mod_codeproduct_leopard') {
                if (substr($module, 0, 16) == 'mod_codeproduct_' && substr($module, -3) == 'php') {
                    $module = substr($module, 0, dol_strlen($module)-4);
                }
		dol_include_once('/core/modules/product/'.$module.'.php');
		$modCodeProduct = new $module;
                if (! empty($modCodeProduct->code_auto)) {
                    $newobject->ref = $modCodeProduct->getNextValue($newobject,$newobject->type);
                }
		unset($modCodeProduct);
            }
            if (empty($newobject->ref) || !$newobject->ref)
                $newobject->ref = 'eshop'.(isset($product['id_product'])?$product['id_product']:'');
	}
        dol_syslog("CyberOffice_server_product::ref2 =".$newobject->ref,6,0, '_cyber');
	$user = new User($db);
	$Ruser=$user->fetch('', $authentication['login'],'',0);

	/* verification ref existant
	****************************/
	$sql = "SELECT count(*) as nb";
	$sql.= " FROM ".MAIN_DB_PREFIX."product";
	$sql.= " WHERE (ref = '" .$newobject->ref."' OR ref LIKE '".$newobject->ref."(%')";
	$sql.= " AND import_key != 'P".$indice."-".(isset($product['id_product'])?$product['id_product']:'')."'";
	if (isset($product['match']) && $product['match'] == '{ref}')
            $resultCheck = '';
	else
            $resultCheck = $db->query($sql);
        if ($resultCheck ) {
            $obj = $db->fetch_object($resultCheck );
            if ($obj->nb > 0)
                $newobject->ref = $newobject->ref.'('.($obj->nb + 1).')';
	}

	$result=$produit_id;
	if ($produit_id == 0) {
            $newobject->oldcopy=null;;
            /*
            if ($conf->global->MAIN_MODULE_BARCODE && (!$product['ean13'] && !$product['upc'] && !$product['isbn']))
            {
                dol_syslog("CyberOffice_server_product::cb error =".$product['id_product']);
		continue;
            }
            */
            $newobject->barcode = '';
            $newobject->barcode_type = null;
            if ($conf->global->MAIN_MODULE_BARCODE) {
                if (isset($product['ean13']) && $product['ean13']) {
                    $newobject->barcode = $product['ean13'];
                    $newobject->barcode_type = 2;
                } elseif (isset($product['upc']) && $product['upc']) {
                    $newobject->barcode = $product['upc'];
                    $newobject->barcode_type = 3;
                } elseif (isset($product['isbn']) && $product['isbn']) {
                    $newobject->barcode = $product['isbn'];
                    $newobject->barcode_type = 4;
                } else {
                    $newobject->barcode = '';
                    $newobject->barcode_type = null;
                }
            }

            if ($usevariant == true && $product['match'] == '{ref}') {
                if ( isset($product['variant']) && is_array($product['variant'])) {
                    require_once DOL_DOCUMENT_ROOT . '/variants/class/ProductAttributeValue.class.php';
					require_once DOL_DOCUMENT_ROOT . '/variants/class/ProductCombination.class.php';
                    dol_syslog("server_product::variantpere ::".$product['reference'],6,0, '_cyber');
                    //recherche pere + creation
                    $variantpere = new Product($db);
                    $variantpere->fetch('', $product['variant']['ref']);
                    dol_syslog("server_product::variantpere trouvé::".$variantpere->id,6,0, '_cyber');
                    if ($variantpere->id && $variantpere->id > 0) {
                        dol_syslog("server_product::variantpere - id=".$variantpere->id.'::'.$product['reference'],6,0, '_cyber');
                        $variantpereid = $variantpere->id;
                    } else {
                        //$this->myLog(print_r($product['variant'], true));
                        dol_syslog("server_product::variantpere creation ::".$product['variant']['ref'].'/'.$product['reference'],6,0, '_cyber');
                        $newobject->ref = $product['variant']['ref'];
                        //$newobject->label = $product['variant']['label'];
						if (is_array($product['variant']['label'])) {
							foreach ($product['variant']['label'] as $key => $myname) {
								$newkey = $product['ALLLANGUAGES'][$key];
								if (!empty($conf->global->MAIN_MULTILANGS)) {
									$newobject->multilangs["$newkey"]["label"] = htmlentities($myname, ENT_QUOTES, 'utf-8');
								}
								if ($newkey == $product['LOCALELANG']) {
									$newobject->label = htmlentities($myname, ENT_QUOTES, 'utf-8');
								}
							}
						} else {
							$newobject->label = htmlentities($product['variant']['label'], ENT_QUOTES, 'utf-8');
						}

                        if (isset($product['description_short']) && is_array($product['description_short'])) {
							foreach ($product['description_short'] as $key => $myname) {
								$newkey = $product['ALLLANGUAGES'][$key];
								if (!empty($conf->global->MAIN_MULTILANGS)) {
									$newobject->multilangs["$newkey"]["description"]=($myname);
								}
								if ($newkey == $product['LOCALELANG']) {
									$newobject->description=($myname);
								}
							}
						} else {
							$newobject->description=(isset($product['description_short'])?($product['description_short']):'');
						}

                        //$this->myLog("CyberOffice_server_product::trim=".__LINE__);
                        if ($conf->global->CYBEROFFICE_NoDescription != 1) {
                            if (!is_null(trim($product['description'])) && !empty(trim($product['description']))) {
                                $newobject->array_options['options_longdescript'] = ($product['description']);//1.6.2
                            }
                        }
//						$this->myLog("CyberOffice_server_product::trim=".__LINE__);
                        $newobject->price_base_type = $ttcht;
                        if ($ttcht == 'TTC') {
                            $totalttc = price2num($product['variant']['price'] * (1 + ($product['tax_rate'] / 100)),'MU');
                        } else {
                            $totalttc = $product['variant']['price'];
                        }
						$newobject->barcode = '';
						$newobject->barcode_type = '';
                        $newobject->weight = $product['variant']['weight'];
                        $variantpereid = $newobject->create($user);
						dol_syslog("server_product::variantpere creation ::".__LINE__,6,0, '_cyber');
                        /*if ($variantpereid == -1) {
                            $variantpere->fetch('', $product['variant']['ref']);
                            $variantpereid = $variantpere->id;
                        }*/
						dol_syslog("server_product::variantpere creation ".$variantpereid,6,0, '_cyber');
                        $variantpere->fetch($variantpereid);
                        if (empty($conf->global->PRODUIT_MULTIPRICES) || $conf->global->PRODUIT_MULTIPRICES == 0) {
                            dol_syslog("server_product::variantpere updatePrice ".$totalttc,6,0, '_cyber');
                            $notrigger = 1;
                            $variantpere->updatePrice($totalttc, $ttcht, $user, $product['tax_rate'], 0, 0, 0, 0, 0, array(), '', $notrigger);
                        } else {
                            $pricelevel = (int)$conf->global->{"MYCYBEROFFICE_pricelevel".$indice};
                            if ($pricelevel==0) $pricelevel = 1;
                            dol_syslog("server_product::variantpere updatePrice ".$totalttc.' level='.$pricelevel,6,0, '_cyber');
                            $notrigger = 1;
                            $variantpere->updatePrice($totalttc, $ttcht, $user, $product['tax_rate'], 0,$pricelevel, 0, 0, 0, array(), '', $notrigger);
                        }
                        dol_syslog("server_product::variantpere creation ".$variantpereid,6,0, '_cyber');
                    }
                    $attributes = array();
                    dol_syslog("server_product::traitement variant pere".$variantpere->id.' fils='.$product['reference'],6,0, '_cyber');
                    $testProductAttributeValue = 0;
                    foreach($product['variant']['declinaison'] as $variant)
                    {
                        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cyberoffice3"
                            . " WHERE shop LIKE '".$indice."' AND attribut = ".$variant;
                        $resql = $db->query($sql);
                        if ($resql) {
                            while ($row = $db->fetch_array($resql))
                            {
                                $ProductAttributeValue = new ProductAttributeValue($db);
                                $myresult = $ProductAttributeValue->fetch($row['variant']);
                                if ($myresult < 0) {
                                    $testProductAttributeValue ++;
                                } else {
                                    $attributes[$ProductAttributeValue->fk_product_attribute] = $row['variant'];
                                }
								//$row_variant = $row['variant'];
                                dol_syslog("server_product::add variant =".$variant." - ".$row['variant'].' :: '.$product['reference'],6,0, '_cyber');
                            }
                        } else {
							$testProductAttributeValue ++;
						}
                        if ($testProductAttributeValue > 0) {
							dol_syslog("server_product::variant break=".$variant,6,0, '_cyber');
                            break;
                        }
                        dol_syslog("server_product::add variant =".$row['variant'].' :: '.$product['reference'],6,0, '_cyber');
                    }
                    $prodcomb = new ProductCombination($db);
                    $level_price_impact_percent = 0;

                    $weight_impact = $product['variant']['variation-weight'];
                    $reference = $product['reference'];
                    $variations = array();
                    if ($conf->global->PRODUIT_MULTIPRICES && (int)$conf->global->{"MYCYBEROFFICE_pricelevel".$indice} > 0) {
						$level_price_impact[(int)$conf->global->{"MYCYBEROFFICE_pricelevel".$indice}] = $product['variant']['variation-price'];
                    } else {
						$level_price_impact = $product['variant']['variation-price'];
                    }
                    dol_syslog("server_product::createProductCombination =".$reference.' pere='.$variantpereid,6,0, '_cyber');
                    $result = $prodcomb->createProductCombination($user, $variantpere, $attributes, $variations, $level_price_impact_percent, $level_price_impact, $weight_impact, $reference);
//                    $this->myLog("server_product::update =".__LINE__);
					$prodcomb->update($user);
                    //$variantpere->fetch($variantpereid);
//					$this->myLog("server_product::update =".__LINE__);
                    $notrigger = true;
                    $variantpere->update($variantpereid, $user, $notrigger);
                    $db->commit();
                    continue;
                }
            } else {
                // no variant
            }

                if (isset($product['name']) && is_array($product['name'])) {
                    foreach ($product['name'] as $key => $myname) {
                        $newkey = $product['ALLLANGUAGES'][$key];
                        if (!empty($conf->global->MAIN_MULTILANGS)) {
                            $newobject->multilangs["$newkey"]["label"] = htmlentities($myname, ENT_QUOTES, 'utf-8');
                        }
                        if ($newkey == $product['LOCALELANG']) {
                            $newobject->label = htmlentities($myname, ENT_QUOTES, 'utf-8');
                        }
                    }
                } else {
                    $newobject->label = (isset($product['name'])?htmlentities($product['name'], ENT_QUOTES, 'utf-8'):'');
                }
            if (!$conf->global->cu0032) {
                if (isset($product['description_short']) && is_array($product['description_short'])) {
                    foreach ($product['description_short'] as $key => $myname) {
                        $newkey = $product['ALLLANGUAGES'][$key];
                        if (!empty($conf->global->MAIN_MULTILANGS)) {
                            $newobject->multilangs["$newkey"]["description"]=($myname);
                        /*if (isset($product['variant']))*/
                        $newobject->multilangs["$newkey"]["description"] = '#'.$product['variant']['group_name'].'<br>'.$newobject->multilangs["$newkey"]["description"];
                        }
                        if ($newkey == $product['LOCALELANG']) {
                            $newobject->description=($myname);
                        /*if (isset($product['variant']))*/
                        $newobject->description = '#'.$product['variant']['group_name'].'<br>'.$newobject->description;
                        }
                    }
                } else {
                    $newobject->description=(isset($product['description_short'])?($product['description_short']):'');
                $newobject->description = '#'.$product['variant']['group_name'].'<br>'.$newobject->description;
                }
            }
            $newobject->status_batch = 0;
            $result = $newobject->create($user);
            if ($result > 0) {
                $produit_id=$result;
            $list_ok.="<br/>Create Product : ".$result;
            dol_syslog("CyberOffice_server_product::Create Product : ".$result,6,0, '_cyber');
		$sql = "UPDATE ".MAIN_DB_PREFIX."product SET";
		$sql .= " import_key='P".$indice."-".$product['id_product']."'";
		$sql.= " WHERE rowid=".$result;

		$resql = $db->query($sql);
                //$db->commit();
            } else {
                //$db->rollback();
                continue;
            }
	}
        dol_syslog("server_product:".__LINE__,6,0, '_cyber');//9
	/*****modification
	******************/
	$sql = "SELECT rowid";
	$sql.= " FROM ".MAIN_DB_PREFIX."product";
	if ($product['match'] == '{ref}')
            $sql.= " WHERE ref = '".$product['reference']."'";
	else
            $sql.= " WHERE import_key = 'P".$indice."-".$product['id_product']."'";

        dol_syslog("CyberOffice_server_product::" . __LINE__ . " sql=".$sql,6,0, '_cyber');
	$resql = $db->query($sql);
	if ($resql) {
		if ($db->num_rows($resql) > 0) {
			$res = $db->fetch_array($resql);
			$produit_id=$res['rowid'];
			$newobject->fetch($produit_id);
		} else
			$produit_id=0;
	} else
        $produit_id=0;

	/*****delete attribute
	********************/
	if ($produit_id != 0 && $product['active']=='no') {
            dol_syslog("CyberOffice_server_product::active id=".$produit_id,6,0, '_cyber');
        $newobject->status = 0;
		$newobject->status_buy = 0;
            //$newobject->status_batch = 0;
            $notrigger = true;
            $newobject->update($produit_id, $user, $notrigger);
		$db->commit();
        continue;
	}

	if ($db->num_rows($resql) > 1 && $product['match'] == '{ref}') {
		//$error++;
		$list_ok.= "<br/>ERROR ref ".$product['reference']." x ".$db->num_rows($resql);
            dol_syslog("CyberOffice_server_product::ERROR ref ".$product['reference']." x ".$db->num_rows($resql),6,0, '_cyber');
		continue;
	}
        if (!empty($conf->global->MAIN_MULTILANGS)) {
            $outputlangs = $langs;
            $outputlangs->setDefaultLang($product['LOCALELANG']);//MAIN_LANG_DEFAULT
        }

        $testvariant = 0;

        if ($usevariant == true
        && $product['match'] == '{ref}'
        && isset($product['variant'])
        && is_array($product['variant'])) {
            require_once DOL_DOCUMENT_ROOT . '/variants/class/ProductAttributeValue.class.php';
			require_once DOL_DOCUMENT_ROOT. '/variants/class/ProductCombination.class.php';
            dol_syslog("CyberOffice_server_product:: begin variant =" . $product['variant']['ref'],6,0, '_cyber');
            $variantpere = new Product($db);
            $variantpere->fetch('', $product['variant']['ref']);
            $variantpereid = $variantpere->id;
            //$newobject->ref = $product['variant']['ref'];

			//$variantpere->label = $product['variant']['label'];
			if (is_array($product['variant']['label'])) {
				foreach ($product['variant']['label'] as $key => $myname) {
					$newkey = $product['ALLLANGUAGES'][$key];
					if (!empty($conf->global->MAIN_MULTILANGS)) {
						$variantpere->multilangs["$newkey"]["label"] = htmlentities($myname, ENT_QUOTES, 'utf-8');
					}
					if ($newkey == $product['LOCALELANG']) {
						$variantpere->label = htmlentities($myname, ENT_QUOTES, 'utf-8');
					}
				}
			} else {
				$variantpere->label = htmlentities($product['variant']['label'], ENT_QUOTES, 'utf-8');
			}
			/*if (is_array($product['variant']['description'])) {
				foreach ($product['variant']['description'] as $key => $myname) {
					$newkey = $product['ALLLANGUAGES'][$key];
					if (!empty($conf->global->MAIN_MULTILANGS)) {
						$variantpere->multilangs["$newkey"]["description"] = htmlentities($myname, ENT_QUOTES, 'utf-8');
					}
					if ($newkey == $product['LOCALELANG']) {
						$variantpere->description = htmlentities($myname, ENT_QUOTES, 'utf-8');
					}
				}
			} else {
				$variantpere->description = htmlentities($product['variant']['description'], ENT_QUOTES, 'utf-8');
			}*/
			if (isset($product['description_short']) && is_array($product['description_short'])) {
				foreach ($product['description_short'] as $key => $myname) {
					$newkey = $product['ALLLANGUAGES'][$key];
					if (!empty($conf->global->MAIN_MULTILANGS)) {
						$newobject->multilangs["$newkey"]["description"] = ($myname);
                        $variantpere->multilangs["$newkey"]["description"] = ($myname);
					}
					if ($newkey == $product['LOCALELANG']) {
						$newobject->description = ($myname);
                        $variantpere->description = ($myname);
					}
				}
			} else {
				$newobject->description = ($product['description_short']);
                $variantpere->description = ($product['description_short']);
			}
            //$variantpere->description = $product['variant']['description'];
            //$newobject->description = $product['description_short'];
//			$this->myLog("CyberOffice_server_product::trim=".__LINE__);
            if ($conf->global->CYBEROFFICE_NoDescription != 1) {
                if (!is_null(trim($product['description'])) && !empty(trim($product['description']))) {
                    $newobject->array_options['options_longdescript'] = trim($product['description']);
                    $variantpere->array_options['options_longdescript'] = trim($product['description']);
                }
            }
//			$this->myLog("CyberOffice_server_product::trim=".__LINE__);
            /*if ($conf->global->MAIN_MODULE_BARCODE) {
                if ($product['ean13'])
                {
                    $newobject->barcode		= $product['ean13'];
                    $newobject->barcode_type    = 2;
                }
                elseif ($product['upc'])
                {
                    $newobject->barcode		= $product['upc'];
                    $newobject->barcode_type	= 3;
                }
                elseif ($product['isbn'])
                {
                    $newobject->barcode		= $product['isbn'];
                    $newobject->barcode_type	= 4;
                }
            }*/
			$variantpere->net_measure_units = '';
			$newobject->net_measure_units = '';

            $newobject->status = $product['active'];
            $newobject->height = $product['height'];//hauteur
            $newobject->width = $product['width'];//largeur
            $newobject->length = $product['depth'];//longueur profondeur

			$variantpere->status = $product['active'];
			$variantpere->height = $product['height'];//hauteur
            $variantpere->width = $product['width'];//largeur
            $variantpere->length = $product['depth'];//longueur profondeur

			$tabunits = $cunits->fetch(null, '', $product['WEIGHT_UNIT']);
            $newobject->weight_units = ($tabunits < 0? '': $cunits->scale);//0;//kg
            $variantpere->weight_units = ($tabunits < 0? '': $cunits->scale);//0;//kg

			//$variantpere->barcode = '';
			$variantpere->surface_units = '';
			$newobject->surface_units = '';

            $tabunits = $cunits->fetch(null, '', $product['DIMENSION_UNIT']);
            $newobject->length_units = ($tabunits < 0? '': $cunits->scale);
            $variantpere->length_units = ($tabunits < 0? '': $cunits->scale);
			$newobject->width_units = ($tabunits < 0? '': $cunits->scale);
			$newobject->height_units = ($tabunits < 0? '': $cunits->scale);
			$variantpere->width_units = ($tabunits < 0? '': $cunits->scale);
			$variantpere->height_units = ($tabunits < 0? '': $cunits->scale);
            $tabunits = $cunits->fetch(null, '', $product['VOLUME_UNIT']);
            $newobject->volume_units = ($tabunits < 0? '': $cunits->scale);
            $variantpere->volume_units = ($tabunits < 0? '': $cunits->scale);
            //$newobject->url = $product['product_url'];
            $variantpere->url = $product['product_url'];
            /*if ($conf->global->CYBEROFFICE_stock==1) {
                $newobject->id=$produit_id;
                $sql = "SELECT ps.reel, ps.rowid as product_stock_id "
                    . " FROM ".MAIN_DB_PREFIX."product_stock as ps "
                    . " WHERE ps.fk_entrepot = ".$product['warehouse']
                    . " AND ps.fk_product = ".$newobject->id;
                $resql = $db->query($sql);
                if ($resql) {
                    if ($db->num_rows($resql) > 0) {
                        $res = $db->fetch_array($resql);
                        $stockW=$res['reel'];
                    } else
                        $stockW=0;
                } else
                    $stockW=0;
                $quantity=$product['quantity'] - $stockW;//$newobject->stock_reel;
                if ($quantity != 0)
                    $newobject->correct_stock($user, $product['warehouse'], $quantity, 0, 'PrestaShop');
            }*/
            $notrigger = true;
            dol_syslog("CyberOffice_server_product".__LINE__."::update ".$newobject->id,6,0, '_cyber');
            $newobject->update($newobject->id, $user, $notrigger);

            $variantpere->price_base_type = $ttcht;
            if ($ttcht == 'TTC') {
                $totalttc = price2num($product['variant']['price'] * (1 + ($product['tax_rate'] / 100)),'MU');
            } else {
                $totalttc = $product['variant']['price'];
            }
            $variantpere->weight = $product['variant']['weight'];
            /***** category
            ***************/
            dol_syslog('category:variantpere' . $variantpereid,6,0, '_cyber');
            /*$sql = "DELETE FROM ".MAIN_DB_PREFIX."categorie_product
                    where fk_categorie in (select rowid from ".MAIN_DB_PREFIX."categorie where SUBSTRING(import_key,2,2)='".$indice."')
                    and fk_product=".$variantpereid ;*/
            $sql = "DELETE cp
                    FROM " . MAIN_DB_PREFIX . "categorie_product cp
                    INNER JOIN " . MAIN_DB_PREFIX . "categorie c ON cp.fk_categorie = c.rowid
                    WHERE SUBSTRING(c.import_key, 2, 2) = '" . $indice . "'
                    AND cp.fk_product = " . $variantpereid;
            dol_syslog('categoryvariantpere:sql=' . $sql,6,0, '_cyber');
            $resql = $db->query($sql);
            $categs = explode('-',$product['category']);
            foreach ($categs  as $categ)
            {
                dol_syslog("CyberOffice_server_product::categorievariantpere=".$indice."-".$categ,6,0, '_cyber');
                $sql = "SELECT rowid";
                $sql.= " FROM ".MAIN_DB_PREFIX."categorie";
                $sql.= " WHERE import_key = 'P".$indice."-".$categ."'";
                $resql = $db->query($sql);
                if ($resql) {
                    if ($db->num_rows($resql) > 0) {
                        $res = $db->fetch_array($resql);
                        $res_rowid=$res['rowid'];
                    } else
                        $res_rowid=0;
                } else
                    $res_rowid=0;

                if ($res_rowid != 0) {
                    dol_syslog('add categoryvariantpere:' . $res_rowid,6,0, '_cyber');
                    $cat = new Categorie($db);
                    $result_Cat=$cat->fetch($res_rowid);
                    $result_Cat=$cat->add_type($variantpere,'product');
                }
            }
            dol_syslog("server_product::variantpere update ".$variantpereid,6,0, '_cyber');
            $notrigger = true;
            $variantpere->update($variantpereid, $user, $notrigger);
            if (empty($conf->global->PRODUIT_MULTIPRICES) || $conf->global->PRODUIT_MULTIPRICES == 0) {
                dol_syslog("server_product::variantpere updatePrice ".$totalttc,6,0, '_cyber');
                $notrigger = 1;
                $variantpere->updatePrice($totalttc, $ttcht, $user, $product['tax_rate'], 0, 0, 0, 0, 0, array(), '', $notrigger);
            } else {
                $pricelevel = (int)$conf->global->{"MYCYBEROFFICE_pricelevel".$indice};
                if ($pricelevel==0) $pricelevel = 1;
                dol_syslog("server_product::variantpere updatePrice ".$totalttc.' level='.$pricelevel,6,0, '_cyber');
                $notrigger = 1;
                $variantpere->updatePrice($totalttc, $ttcht, $user, $product['tax_rate'], 0,$pricelevel, 0, 0, 0, array(), '', $notrigger);
            }
//			$this->myLog("CyberOffice_server_product::".__LINE__);
            $attributes = array();
            foreach($product['variant']['declinaison'] as $variant)
            {
                $sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cyberoffice3"
                    . " WHERE shop LIKE '".$indice."' AND attribut = ".$variant;
                $resql = $db->query($sql);
                if ($resql) {
                    while ($row = $db->fetch_array($resql))
                    {
                        $ProductAttributeValue = new ProductAttributeValue($db);
                        $ProductAttributeValue->fetch($row['variant']);
                        $attributes[$ProductAttributeValue->fk_product_attribute] = $row['variant'];
                        dol_syslog("server_product::add variant =".$variant." - ".$row['variant'],6,0, '_cyber');
                    }
                }
                dol_syslog("server_product::add variant",6,0, '_cyber');
            }
//			$this->myLog("CyberOffice_server_product::".__LINE__);
            $prodcomb = new ProductCombination($db);
            $level_price_impact_percent = array('1' => 0);//false;
            $weight_impact = $product['variant']['variation-weight'];
            $reference = $product['reference'];
            $variations = array();
            if (!empty($conf->global->PRODUIT_MULTIPRICES) && (int)$conf->global->{"MYCYBEROFFICE_pricelevel".$indice} > 0) {
                $level_price_impact[(int)$conf->global->{"MYCYBEROFFICE_pricelevel".$indice}] = $product['variant']['variation-price'];
            } else {
                $level_price_impact = $product['variant']['variation-price'];
            }
            $existingCombination = $prodcomb->fetchByProductCombination2ValuePairs($variantpereid, $attributes);
            $prodcomb = $existingCombination;
//            $this->myLog("server_product::existingCombination ".print_r($existingCombination, true));
            if ($prodcomb) {
//				$this->myLog("server_product::existingCombination ".__LINE__);
                $result = $prodcomb->createProductCombination($user, $variantpere, $attributes, $variations, $level_price_impact_percent, $level_price_impact, $weight_impact, $reference);
//				$this->myLog("server_product::existingCombination ".$result);
            }
            /*$prodattr = new ProductAttribute($db);
            $prodattrval = new ProductAttributeValue($db);
            $prodattr->fetch($currcombattr);
            $prodattrval->fetch($currcombval);*/
            //$prodcomb->update($user);
            //$variantpere->fetch($variantpereid);
            //$newobject->update($newobject->id, $user);

            //$db->commit();
            //continue;
            $testvariant = 1;
        }
		dol_syslog("CyberOffice_server_product:: end variant =".__LINE__,6,0, '_cyber');

        $newobject->url	= $product['product_url'];

        if ($testvariant == 0) {
            $newobject->label				= $product['name'];
            if (isset($product['description_short']) && is_array($product['description_short'])) {
                foreach ($product['description_short'] as $key => $myname) {
                    $newkey = $product['ALLLANGUAGES'][$key];
                    if (!empty($conf->global->MAIN_MULTILANGS)) {
                        $newobject->multilangs["$newkey"]["description"]=($myname);
                    }
                    if ($newkey == $product['LOCALELANG']) {
                        $newobject->description=($myname);
                    }
                }
            } else {
                $newobject->description=(isset($product['description_short'])?($product['description_short']):'');
            }
            if ($conf->global->CYBEROFFICE_NoDescription != 1) {
                if (!is_null(trim($product['description'])) && !empty(trim($product['description']))) {
                    $newobject->array_options['options_longdescript'] = trim($product['description']);
                }
            }
//			$this->myLog("CyberOffice_server_product::trim=".__LINE__);
            $newobject->weight = $product['weight'];
            $newobject->price = $product['price'];
            $newobjectprice = $product['price'];
            $newobject->ref = $product['reference'];
            $newobject->price_base_type = $ttcht;
            if ($product['tax_rate'])
                $newobject->tva_tx = $product['tax_rate'];
            $newobject->price_ttc = price2num($newobjectprice * (1 + ($product['tax_rate'] / 100)),'MU');
            $newobject->id = $produit_id;
        }

        $newobject->status		= $product['active'];
        $newobject->height              = $product['height'];//hauteur
        $newobject->width               = $product['width'];//largeur
        $newobject->length              = $product['depth'];//longueur profondeur

        $tabunits = $cunits->fetch(null, '', $product['WEIGHT_UNIT']);
        $newobject->weight_units 	= ($tabunits < 0? '': $cunits->scale);//0;//kg
		$newobject->net_measure_units = '';
		//$newobject->barcode = '';
		$newobject->surface_units = '';
        $tabunits = $cunits->fetch(null, '', $product['DIMENSION_UNIT']);
		$newobject->length_units = ($tabunits < 0? '': $cunits->scale);
		$newobject->width_units = ($tabunits < 0? '': $cunits->scale);
		$newobject->height_units = ($tabunits < 0? '': $cunits->scale);
        $tabunits = $cunits->fetch(null, '', $product['VOLUME_UNIT']);
        $newobject->volume_units        = ($tabunits < 0? '': $cunits->scale);
		$newobject->barcode = '';
        $newobject->barcode_type = null;
		if ($conf->global->MAIN_MODULE_BARCODE) {
            if ($product['ean13'])
            {
                $newobject->barcode	 = $product['ean13'];
				$newobject->barcode_type = 2;
            }
            elseif ($product['upc'])
            {
                $newobject->barcode	 = $product['upc'];
				$newobject->barcode_type = 3;
            }
            elseif ($product['isbn'])
            {
                $newobject->barcode	 = $product['isbn'];
				$newobject->barcode_type = 4;
            } else {
                $newobject->barcode = null;
                $newobject->barcode_type = null;
            }
        }

	if ($produit_id>0) {
		/**Extrafield
		*************/
		$extraFields = new ExtraFields($db);
		$ProductExtraField = $extraFields->fetch_name_optionals_label('product');
            //$this->myLog(print_r($extraFields,true));
		if (is_array($product['features']) && count($product['features'])>0) {
			foreach($product['features'] as $feature) {
                    //$this->myLog(print_r($feature,true));
				$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cyberoffice c WHERE c.active=1 AND c.idpresta=".(int)$feature['id_feature'];
				$resql = $db->query($sql);
				if ($resql) {
					if ($db->num_rows($resql) > 0) {
						$res = $db->fetch_array($resql);
						$res_extrafield=$res['extrafield'];
						if ($extraFields->attributes['product']['type'][$res_extrafield]== 'select') {
							$newobject->array_options['options_'.$res_extrafield] = $feature['id_feature_value'];
						} else {
							if (!is_null($feature['feature_value_lang']) && !empty($feature['feature_value_lang'])) {
								$newobject->array_options['options_'.$res_extrafield] = $feature['feature_value_lang'];
							}
						}
                            dol_syslog("CyberOffice_server_product::extrafield  -> ".$res_extrafield.'::'.$newobject->array_options['options_'.$res_extrafield],6,0, '_cyber');
					}
				}
			}
		}
            dol_syslog("CyberOffice_server_product::".__LINE__,6,0, '_cyber');
            if ($testvariant == 0) {
                /* verification ref existant
                ****************************/
                $sql = "SELECT count(*) as nb";
                $sql.= " FROM ".MAIN_DB_PREFIX."product";
                $sql.= " WHERE (ref = '" .$newobject->ref."' OR ref LIKE '".$newobject->ref."(%')";
                $sql.= " AND import_key != 'P".$indice."-".$product['id_product']."'";
                if ($product['match'] == '{ref}')
                    $resultCheck = '';
                else
                    $resultCheck = $db->query($sql);
                if ($resultCheck )
                {
                    $obj = $db->fetch_object($resultCheck );
                    if ($obj->nb > 0)
                        $newobject->ref = $newobject->ref.'('.($obj->nb + 1).')';
                }

                $product_price = new Product($db);
                $product_price->fetch($produit_id);
                if ($ttcht == 'TTC') {
                    $totalttc = price2num($newobjectprice * (1 + ($product['tax_rate'] / 100)),'MU');
                } else {
                    $totalttc = $newobjectprice;
                }
                if (empty($conf->global->PRODUIT_MULTIPRICES) || $conf->global->PRODUIT_MULTIPRICES == 0)
                {
                    //$this->myLog("CyberOffice_server_product::Update Price : ".round($product_price->price,3).'='.round($product['price'],3));
                    if (round($product_price->price,3) != round($newobjectprice,3) || round($product_price->tva_tx,3) != round($product['tax_rate'],3)) {
                        $notrigger = 1;
                        $newobject->updatePrice($totalttc, $ttcht, $user, $product['tax_rate'], 0,$pricelevel, 0, 0, 0, array(), '', $notrigger);
                    }
                } else {
                    $pricelevel = (int)$conf->global->{"MYCYBEROFFICE_pricelevel".$indice_name} ;
                    if ($pricelevel==0) $pricelevel = 1;
                    //$this->myLog("CyberOffice_server_product::Update Prices : ".round($product_price->multiprices[$pricelevel],3).'='.round($product['price'],3));
                    if (round($product_price->multiprices[$pricelevel],3) != round($newobjectprice,3) || round($product_price->tva_tx,3) != round($product['tax_rate'],3)) {
                        $notrigger = 1;
                        $newobject->updatePrice($totalttc, $ttcht, $user, $product['tax_rate'], $product_price->multiprices[$pricelevel],$pricelevel, 0, 0, 0, array(), '', $notrigger);
                        $newobject->updatePrice($totalttc, $ttcht, $user, $product['tax_rate'], $product_price->multiprices_min[$pricelevel],$pricelevel, 0, 0, 0, array(), '', $notrigger);
                    }
                }
                $newobject->oldcopy=null;
                dol_syslog("CyberOffice_server_product::Update Product : ".$product['id_product'].'->'.$produit_id,6,0, '_cyber');
                /*
                if ($conf->global->MAIN_MODULE_BARCODE && (!$product['ean13'] && !$product['upc'] && !$product['isbn']))
                {
                    dol_syslog("CyberOffice_server_product::cb error =".$product['id_product']);
                    continue;
                }
                */
                /*if ($conf->global->MAIN_MODULE_BARCODE) {
                    if ($product['ean13'])
                    {
                        $newobject->barcode				= $product['ean13'];
                        $newobject->barcode_type		= 2;
                    }
                    elseif ($product['upc'])
                    {
                        $newobject->barcode				= $product['upc'];
                        $newobject->barcode_type		= 3;
                    }
                    elseif ($product['isbn'])
                    {
                        $newobject->barcode				= $product['isbn'];
                        $newobject->barcode_type		= 4;
                    }
                }*/

                if (isset($product['description_short']) && is_array($product['description_short'])) {
                    foreach ($product['description_short'] as $key => $myname) {
                        $newkey = $product['ALLLANGUAGES'][$key];
                        if (!empty($conf->global->MAIN_MULTILANGS)) {
                            $newobject->multilangs["$newkey"]["description"]=($myname);
                        }
                        if ($newkey == $product['LOCALELANG']) {
                            $newobject->description=($myname);
                        }
                    }
                } else {
                    $newobject->description=(isset($product['description_short'])?($product['description_short']):'');
                }
                if ($conf->global->CYBEROFFICE_NoDescription != 1) {
                    if (!is_null(trim($product['description'])) && !empty(trim($product['description']))) {
                        $newobject->array_options['options_longdescript'] = trim($product['description']);//1.6.2
                    }
                }
//                $this->myLog("CyberOffice_server_product::trim=".__LINE__);

                /*if ($conf->global->MAIN_MODULE_PRICELIST==1) {
                    dol_include_once('./custom/pricelist/class/pricelist.class.php');
                    foreach ($product['specificprice'] as $myi => $myvalue) {
                        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe
                            WHERE import_key LIKE 'P%-".$product['specificprice'][$myi]['id_customer']."'";
                        $resql = $db->query($sql);
                        if ($resql) {
                            if ($db->num_rows($resql) > 0) {
                                $res = $db->fetch_array($resql);
                                $res_rowid=$res['rowid'];
                            } else $res_rowid=0;
                        } else $res_rowid=0;
                        $MyPricelist = new Pricelist($db);
                        $MyPricelist->product_id = $produit_id;
                        $MyPricelist->socid = ($res_rowid>0?$res_rowid:0);
                        $MyPricelist->from_qty = $product['specificprice'][$myi]['from_quantity'];
                        $MyPricelist->price = $product['specificprice'][$myi]['price'];
                        if ($MyPricelist->price==-1) {
                            if ($product['specificprice'][$myi]['reduction_type']=='amount') {
                                $MyPricelist->price = $product['price'] - $product['specificprice'][$myi]['reduction'];
                            } else {
                                $MyPricelist->price = $product['price'] * (1 - $product['specificprice'][$myi]['reduction']);
                            }
                        }

                        if (is_array($product['specificprice'][0])) {
                            $sql = "DELETE FROM ".MAIN_DB_PREFIX."pricelist
                            WHERE import_key='P".$product['specificprice'][$myi]['id_specific_price']."'";
                            $resql = $db->query($sql);
                            $res = $MyPricelist->create($user);
                            $sql = "UPDATE ".MAIN_DB_PREFIX."pricelist SET
                            import_key='P".$product['specificprice'][$myi]['id_specific_price']."'
                            WHERE rowid=".$res;
                            $resql = $db->query($sql);
                        }
                    }
                }*/
            } else {
                if (isset($product['description_short']) && is_array($product['description_short'])) {
                    foreach ($product['description_short'] as $key => $myname) {
                        $newkey = $product['ALLLANGUAGES'][$key];
                        if (!empty($conf->global->MAIN_MULTILANGS)) {
                            $newobject->multilangs["$newkey"]["description"]=($myname);
                            $newobject->multilangs["$newkey"]["description"] = '#'.$product['variant']['group_name']."<br>".($myname);;
                        }
                        if ($newkey == $product['LOCALELANG']) {
                            $newobject->description=($myname);
                            $newobject->description = '#'.$product['variant']['group_name']."<br>".($myname);
                        }
                    }
                } else {
                    $newobject->description=($product['description_short']);
                    $newobject->description = '#'.$product['variant']['group_name'].'<br>'.($product['description_short']);
                }
            }
            dol_syslog("server_product::=".__LINE__,6,0, '_cyber');
            /** specificPrice
            ******************/
            //$this->myLog("server_product::specificprice=".print_r($product['specificprice'], true));
            $produit_idSP=0;
            $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product WHERE ref = '".$product['reference']."'";
            $resqlSP = $db->query($sql);
            if ($resqlSP) {
                if ($db->num_rows($resqlSP) > 0) {
                    $resSP = $db->fetch_array($resqlSP);
                    $produit_idSP=$resSP['rowid'];
                } else
                    $produit_idSP=0;
            } else
                $produit_idSP=0;
            if (isset($conf->global->MAIN_MODULE_PRICELIST) && $conf->global->MAIN_MODULE_PRICELIST==1 && $product['match'] == '{ref}' && $produit_idSP>0) {
                    dol_include_once('./custom/pricelist/class/pricelist.class.php');
					//21112022
					$sql = "DELETE FROM ".MAIN_DB_PREFIX."pricelist
                            WHERE fk_product=".$produit_idSP;
                    $resql = $db->query($sql);
                if (is_array($product['specificprice']) && count($product['specificprice'])>0) {
                    foreach ($product['specificprice'] as $myi => $myvalue) {
                        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe
                            WHERE import_key LIKE 'P%-".$product['specificprice'][$myi]['id_customer']."'";
                        $resql = $db->query($sql);
                        if ($resql) {
                            if ($db->num_rows($resql) > 0) {
                                $res = $db->fetch_array($resql);
                                $res_rowid=$res['rowid'];
                            } else $res_rowid=0;
                        } else $res_rowid=0;
                        $MyPricelist = new Pricelist($db);
                        $MyPricelist->product_id = $produit_idSP;
						$newobjectSP = new Product($db);
						$newobjectSP->fetch($produit_idSP);
                        $MyPricelist->socid = ($res_rowid>0?$res_rowid:0);
                        $MyPricelist->from_qty = $product['specificprice'][$myi]['from_quantity'];
                        $MyPricelist->price = $product['specificprice'][$myi]['price'];
						if ($MyPricelist->price==-1) {
                            if ($product['specificprice'][$myi]['reduction_type']=='amount') {
                                dol_syslog("server_product::specificprice=".$product['price'].'-'.$product['specificprice'][$myi]['reduction'].'-'.$newobjectSP->tva_tx,6,0, '_cyber');
								if ($product['specificprice'][$myi]['reduction_tax'] == 0 || $newobjectSP->tva_tx == 0) {
									$MyPricelist->price = $product['price'] - $product['specificprice'][$myi]['reduction'];
								} else {
									$MyPricelist->price = $product['price'] - ($product['specificprice'][$myi]['reduction']/(1+$newobjectSP->tva_tx/100));
								}
                            } else {
                                $MyPricelist->price = null;
								$MyPricelist->tx_discount = $product['specificprice'][$myi]['reduction']*100;
                            }
                        }
						/*
						 *  if ($MyPricelist->price==-1) {
                            if ($product['specificprice'][$myi]['reduction_type']=='amount') {
                                $MyPricelist->price = $product['price'] - $product['specificprice'][$myi]['reduction'];
                            } else {
                                $MyPricelist->price = $product['price'] * (1 - $product['specificprice'][$myi]['reduction']);
                            }
                        }

						 */
                        //TODO pb avec le delete sur toutes les declinaisons, c'est le meme id presta !!!

                        if (is_array($product['specificprice'][0])) {
                            $sql = "DELETE FROM ".MAIN_DB_PREFIX."pricelist
                            WHERE import_key='P".$product['specificprice'][$myi]['id_specific_price']."'";
                            //$resql = $db->query($sql);
                            $res = $MyPricelist->create($user);
                            $sql = "UPDATE ".MAIN_DB_PREFIX."pricelist SET
                            import_key='P".$product['specificprice'][$myi]['id_specific_price']."'
                            WHERE rowid=".$res;
                            $resql = $db->query($sql);
                        }
                    }
                }
            }

            if (is_array($product['name']) && count($product['name'])>0) {
                    foreach ($product['name'] as $key => $myname) {
                        $newkey = $product['ALLLANGUAGES'][$key];
                        if (!empty($conf->global->MAIN_MULTILANGS)) {
                            $newobject->multilangs["$newkey"]["label"] = htmlentities($myname, ENT_QUOTES, 'utf-8');
                        }
                        if ($newkey == $product['LOCALELANG']) {
                            $newobject->label = htmlentities($myname, ENT_QUOTES, 'utf-8');
                        }
                    }
                } else {
                    $newobject->label = htmlentities($product['name'], ENT_QUOTES, 'utf-8');
                }

            if ($conf->global->CYBEROFFICE_NoDescription != 1) {
                if (!is_null(trim($product['description'])) && !empty(trim($product['description']))) {
                    $newobject->array_options['options_longdescript'] = ($product['description']);
                }
                }
			$newobject->net_measure_units = '';
			$newobject->surface_units = '';
			$newobject->height = $product['height'];
			$newobject->width = $product['width'];
			$newobject->length = $product['depth'];
			$tabunits = $cunits->fetch(null, '', $product['WEIGHT_UNIT']);
			$newobject->weight_units = ($tabunits < 0? '': $cunits->scale);
			$tabunits = $cunits->fetch(null, '', $product['DIMENSION_UNIT']);
			$newobject->length_units = ($tabunits < 0? '': $cunits->scale);
			$newobject->width_units = ($tabunits < 0? '': $cunits->scale);
			$newobject->height_units = ($tabunits < 0? '': $cunits->scale);
			$tabunits = $cunits->fetch(null, '', $product['VOLUME_UNIT']);
			$newobject->volume_units = ($tabunits < 0? '': $cunits->scale);
            $notrigger = true;
            //$newobject->status_batch = 0;
            dol_syslog("server_product::update=".__LINE__,6,0, '_cyber');
            $resultS_U=$newobject->update($produit_id, $user, $notrigger);
            $sql = "UPDATE ".MAIN_DB_PREFIX."product SET";
            $sql .= " import_key='P".$indice."-".$product['id_product']."'";
            $sql.= " WHERE rowid=".$produit_id;
            dol_syslog("server_product::update=".__LINE__,6,0, '_cyber');
            $resql = $db->query($sql);
        }
	if ($resultS_U> 0)
            $list_ok.="<br/>Update Product : ".$product['id_product'].'->'.$produit_id . ' : ' .$newobject->label;
					//}
	/*****mise a  jour du stock
	**************************/
        dol_syslog("CyberOffice_server_product::maj_stock  -> ".$product['warehouse'].'::'.$produit_id,6,0, '_cyber');
	if ($conf->global->CYBEROFFICE_stock==1) {
            $newobject->id=$produit_id;
            //$stock=$newobject->load_stock();
            $sql = "SELECT ps.reel, ps.rowid as product_stock_id";
            $sql.= " FROM ".MAIN_DB_PREFIX."product_stock as ps";
            $sql.= " WHERE ps.fk_entrepot = ".$product['warehouse'];
            $sql.= " AND ps.fk_product = ".$newobject->id;
            //$sql.= " FOR UPDATE";
            $resql = $db->query($sql);
            if ($resql) {
                if ($db->num_rows($resql) > 0) {
                    $res = $db->fetch_array($resql);
                    $stockW=$res['reel'];
                } else
                    $stockW=0;
            } else
                $stockW=0;
            if (isset($conf->global->MYCYBEROFFICE_stock_theorique) && $conf->global->MYCYBEROFFICE_stock_theorique == 1) {
                $newobjectStock=new Product($db);
                $newobjectStock->fetch($newobject->id);
                $newobjectStock->load_stock();
                $stockW = $newobjectStock->stock_theorique;
            }
            $quantity=$product['quantity'] - $stockW;//$newobject->stock_reel;
            dol_syslog($quantity.' = '.$product['quantity'].' - '.$stockW,6,0, '_cyber');
            if ($quantity != 0) {
                if ($quantity > 0) {
                    $movement = 0;
                } else {
                    $movement = 1;
                    $quantity = -1 * $quantity;
                }
                $myresultstock=$newobject->correct_stock($user, $product['warehouse'], $quantity, $movement, 'Cyberoffice');
            }
	}
	/*****photo
	***********/
	/**********************************************************
	** IMPORTANT !! php directive allow_url_fopen must be on **
	***********************************************************/
        dol_syslog("CyberOffice_server_product::IMAGE -> ".$product['image'],6,0, '_cyber');

	/*******************
	** supression images
	********************/
	$sdir = $conf->product->multidir_output[$conf->entity];

	if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) {
		$dir = $sdir .'/'. get_exdir($produit_id,2,0,0,$newobject,'product') . $produit_id ."/photos";
	} else
        $dir = $sdir .'/'.dol_sanitizeFileName($product['reference']);
        dol_syslog("CyberOffice_server_product::IMAGE -> suppression".__LINE__.$dir,6,0, '_cyber');
        if (is_dir($dir)) {
            if ($repertoire = opendir($dir)) {
                while(false !== ($fichier = readdir($repertoire)))
		{
                    $chemin = $dir."/".$fichier;
                    $infos = pathinfo($chemin);
                    $extension = (isset($infos['extension']) ? $infos['extension'] : '');
                    dol_syslog("CyberOffice_server_product::IMAGE -> suppression".__LINE__.$chemin.'-'.$extension,6,0, '_cyber');
                    if($fichier!="." && $fichier!=".." && !is_dir($fichier) && in_array($extension, array('gif','jpg','jpeg','png','bmp')))
                    {
                        dol_syslog("CyberOffice_server_product::IMAGE -> suppression".__LINE__.$chemin,6,0, '_cyber');
                        if ($product['images'] != 'cybernull')
                        {
                            unlink($chemin);
                        }
                    }
		}
                dol_syslog("CyberOffice_server_product::IMAGE -> fermeture".__LINE__.$repertoire,6,0, '_cyber');
                closedir($repertoire);
            }
        }

	////////////////
	foreach($product['images'] as $productimages) {
            dol_syslog("CyberOffice_server_product::IMAGE -> ".__LINE__.$productimages['name'].$productimages['url'],6,0, '_cyber');
            $picture = $productimages['url'];
            $name = $productimages['name'];
            $ext=preg_match('/(\.gif|\.jpg|\.jpeg|\.png|\.bmp)$/i',$picture,$reg);
            $imgfonction='';
            if (strtolower($reg[1]) == '.gif')
                $ext= 'gif';
            if (strtolower($reg[1]) == '.png')
                $ext= 'png';
            if (strtolower($reg[1]) == '.jpg')
                $ext= 'jpeg';
            if (strtolower($reg[1]) == '.jpeg')
                $ext= 'jpeg';
            if (strtolower($reg[1]) == '.bmp')
                $ext= 'wbmp';
            $name=$name.'.'.$ext;
            $file = array("tmp_name"=>"images_temp/temp.$ext","name"=>$name);

            switch ($ext) {
                case 'gif' :
                    $img = imagecreatefromgif($picture);
                    break;
				case 'png' :
                    $img = imagecreatefrompng($picture);
                    break;
				case 'jpeg' :
                    if ( false !== (@$fd = fopen($picture, 'rb' )) )
                    {
                        if ( fread($fd,2) == chr(255).chr(216) )
                            $img = imagecreatefromjpeg($picture);
                        else
                            $img = imagecreatefrompng($picture);
                    } else
						$img = imagecreatefromjpeg($picture);
                    break;
				case 'wbmp' :
                    $img = imagecreatefromwbmp($picture);
                    break;
                case 'webp' :
                    $img = imagecreatefromwebp($picture);
                    break;
            }

            $upload_dir = $conf->product->multidir_output[$conf->entity];

            $sdir = $conf->product->multidir_output[$conf->entity];

            if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) {
				$dir = $sdir .'/'. get_exdir($produit_id,2,0,0,$newobject,'product') . $produit_id ."/photos";
            } else
                $dir = $sdir .'/'.dol_sanitizeFileName($product['reference']);
            dol_syslog("CyberOffice_server_product::IMAGE dir ".$dir,6,0, '_cyber');
            if (! file_exists($dir))
                dol_mkdir($dir);//,'','0705');

            if ($img) {
				@call_user_func_array("image$ext",array($img,$dir.'/'.$file['name']));
				@imagedestroy($img);
			}

            include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
            $myfile = $dir . '/' . $file['name'];
            if (file_exists($myfile) && image_format_supported($myfile) == 1) {
                $imgThumbSmall = vignette($myfile, 160, 120, '_small', 50, "thumbs");
                $imgThumbMini = vignette($myfile, 160, 120, '_mini', 50, "thumbs");
            }

            //$list_ok.="<br/>Image Product : ".$dir.'/'.$file['name']. ' : ' .$product['name'];
            dol_syslog("CyberOffice_server_product::IMAGE Product : ".$dir.'/'.$file['name'],6,0, '_cyber');
        }
	/***** category
	***************/
	if($newobject->id==0)
            $newobject->fetch($produit_id);
    dol_syslog('category:' . $newobject->id,6,0, '_cyber');
	/*
	$sql  = 'DELETE cp
            FROM '.MAIN_DB_PREFIX.'categorie_product cp
            LEFT JOIN '.MAIN_DB_PREFIX.'categorie c ON (cp.fk_categorie = c.rowid)
            WHERE cp.fk_product='.$newobject->id . ' AND SUBSTRING(c.import_key,2,2)="'.$indice.'"';
	*/
	/*$sql = "DELETE FROM ".MAIN_DB_PREFIX."categorie_product
			where fk_categorie in (select rowid from ".MAIN_DB_PREFIX."categorie where SUBSTRING(import_key,2,2)='".$indice."')
			and fk_product=".$newobject->id ;*/
    $sql = "DELETE cp
            FROM " . MAIN_DB_PREFIX . "categorie_product cp
            INNER JOIN " . MAIN_DB_PREFIX . "categorie c ON cp.fk_categorie = c.rowid
            WHERE SUBSTRING(c.import_key, 2, 2) = '" . $indice . "'
            AND cp.fk_product = " . $newobject->id;
    dol_syslog('category:sql=' . $sql,6,0, '_cyber');
	$resql = $db->query($sql);
	$categs = explode('-',$product['category']);
	foreach ($categs  as $categ)
	{
        dol_syslog("CyberOffice_server_product::categorie=".$indice."-".$categ,6,0, '_cyber');
        $sql = "SELECT rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."categorie";
        $sql.= " WHERE import_key = 'P".$indice."-".$categ."'";
        $resql = $db->query($sql);
        if ($resql) {
            if ($db->num_rows($resql) > 0) {
                $res = $db->fetch_array($resql);
                $res_rowid=$res['rowid'];
            } else
                $res_rowid=0;
        } else
            $res_rowid=0;
            //if ($res_rowid==0) $res_rowid=$idparent0;
        if ($res_rowid != 0) {
            dol_syslog('add category:' . $res_rowid,6,0, '_cyber');
			$cat = new Categorie($db);
			$result_Cat=$cat->fetch($res_rowid);
			$result_Cat=$cat->add_type($newobject,'product');
        }
	}

	/****** manufacurer
	*******************/
					/*
					$newobject_m=new Societe($db);
					$sql = "SELECT rowid";
					$sql.= " FROM ".MAIN_DB_PREFIX."societe";
					$sql.= " WHERE import_key = 'P".$indice."m-".$product['id_manufacturer']."'";
					dol_syslog("CyberOffice_server_product::fetch sql=".$sql,6,0, '_cyber');
					$resql = $db->query($sql);
					if ($resql) {
						if ($db->num_rows($resql) > 0) {
							$res = $db->fetch_array($resql);
							$res_manufacturer=$res['rowid'];
						} else $res_manufacturer = 0;
					} else $res_manufacturer = 0;
					$newobject_m->status				= 1;
					$newobject_m->name 				= $product['manufacturer'];
					$newobject_m->client				= 0;
					$newobject_m->fournisseur			= 1;
					$newobject_m->import_key			= "P".$indice."m-".$product['id_manufacturer'];
					$newobject_m->code_fournisseur	= -1;
					if ($res_manufacturer == 0) $resultM = $newobject_m->create($user);
					*/
		if ($result <= 0) {
            $db->rollback();
            $error++;
            $list_id.= ' '.$product['id_product'].' NOK<br>';
            $list_ref.= ' '.$newobject->label;
        dol_syslog("CyberOffice_server_productERROR::product=".$product['id_product'].'::'.$result,6,0, '_cyber');
        } else {
//			$this->myLog("CyberOffice_server_productOK::commit=".__LINE__);
            $db->commit();

            $list_id.= ' '.$product['id_product'].' OK<br>';
        dol_syslog("CyberOffice_server_productOK::product=".$product['id_product'].'::'.$result,6,0, '_cyber');
        }
				//}//fin foreach declinaison
			//}//fin if count
    }  //fin foreach
}
if (! $error || $error==0) {
    //$db->commit();
    $objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>'ok'),'description'=>$list_ok);//$list_ok
} else {
    //$db->rollback();
    $error++;
    $errorcode='KO';
    $errorlabel=$list_ok.'<br/>'.$newobject->error;
}

if ($error && $error > 0) {
    $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel),'description'=>'nok');//$list_id
}
