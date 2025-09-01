<?php
/* CyberOffice
 *  @author 	LVSinformatique <contact@lvsinformatique.com>
 *  @copyright  	2014 LVSInformatique
 *  This source file is subject to a commercial license from LVSInformatique
 *  Use, copy or distribution of this source file without written
 *  license agreement from LVSInformatique is strictly forbidden.
 */

// This is to make Dolibarr working with Plesk
define('NOCSRFCHECK', 1);

set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once '../../master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/cyberoffice8/class/cyberoffice.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/commande/modules_commande.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php';

class DataServer
{
    public function create($authentication, $params, $myparam1, $myparam2)
    {
		global $db,$conf,$langs,$mysoc, $dolibarr_main_db_type;
		dol_syslog("Call Dolibarr webservices interfaces::ServerOrder ".$conf->entity);
		//set_time_limit(3600);
		@ini_set('default_socket_timeout', 160);
		$langs->load("main");
		$langs->load("cyberoffice");
		// Enable and test if module web services is enabled
		if (empty($conf->global->WEBSERVICES_KEY)) {
			$langs->load("cyberoffice@cyberoffice8");
			dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
			print $langs->trans("KeyForWebServicesAccess2");
			exit;
		}

		$now=dol_now();
		dol_syslog("Function: create login=".$authentication['login']);

		if ($authentication['entity']) {$conf->entity=$authentication['entity'];}
		$conf->setValues($db);
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
		$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
		if ($error) {
			$objectresp = array('result'=>array('result_code' => 'ko', 'result_label' => 'ko'),'webservice'=>'login');
			$error++;
			return $objectresp;
		}

		$error=0;
		dol_syslog("CyberOffice_server_order::line=".__LINE__);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

		$user = new User($db);
		$user->fetch('', $authentication['login'],'',0);
		$user->getrights();

		$cyber = new Cyberoffice;
		$cyber->entity = 0;
		$cyber->myurl = $authentication['myurl'];
		$indice = $cyber->numShop();

		$mysoc = new Societe($db);
		$mysoc->setMysoc($conf);

		$db->begin();
		//echo "<pre>".print_r($params)."</pre>";die();
		dol_syslog("CyberOffice_server_order::number of orders=".count($params).' numshop='.$indice);
		$socid_old=0;
		$list_ok = array();
		if (is_array($params) && sizeof($params)>0) {
			foreach ($params as $commande)
			{
				$cyber_current = new Cyberoffice;
				$cyber_current->entity = 0;
				$cyber_current->myurl = $commande['current_shop'];
				$indice_current = $cyber_current->numShop();

				dol_syslog("CyberOffice_server_order::traitement order=".$commande['id_order']);
				if ($commande['payment'] == 'Dolibarr') {
					require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
					$sql = "SELECT rowid";
					$sql.= " FROM ".MAIN_DB_PREFIX."expedition";
					$sql.= " WHERE import_key like 'P%-".$commande['id_order']."'";
					$resql = $db->query($sql);
					if ($resql) {
						if ($db->num_rows($resql) > 0) {
							$res = $db->fetch_array($resql);
							$commande_id=$res['rowid'];
						} else $commande_id=0;
					} else $commande_id=0;
					//dol_syslog("CyberOffice_server_order::traitement order=".$commande['id_order'].$commande_id);
					$objectExpedition = new Expedition($db);
					$objectExpedition->fetch($commande_id);
					if ($commande['valid']== 4 || $commande['valid']== 5)
						$ret = $objectExpedition->setClosed();
					if ($objectExpedition->origin == 'commande') {
						$newobject=new Commande($db);
						$newobject->fetch($objectExpedition->origin_id);
						if ($commande['valid']== 4)//expédié
							$ret = $newobject->setStatut(2);
						/*if ($commande['valid']== 5)
							$ret=$newobject->cloture($user);*/
					}
				} else {
					$newobject=new Commande($db);
					/*****recherche de la correspondance
					************************************/
					$invoiced=0;
					$commOK_0=0;
					$sql = "SELECT rowid,facture,ref";
					$sql.= " FROM ".MAIN_DB_PREFIX."commande";
					$sql.= " WHERE import_key = 'P".$indice_current."-".$commande['id_order']."'";
//					dol_syslog("CyberOffice_server_order::fetch sql=".$sql);
					$resql = $db->query($sql);
					if ($resql) {
						if ($db->num_rows($resql) > 0) {
							$res = $db->fetch_array($resql);
							$commande_id=$res['rowid'];
							$commande_ref=$res['ref'];//15/11/18
							$invoiced=$res['facture'];
							$commOK_0=1;
						} else $commande_id=0;
					} else $commande_id=0;
					dol_syslog("CyberOffice_server_order::commande_id1 =".$commande_id);

                    $sql = "SELECT rowid, fk_pays, email";
                    $sql.= " FROM ".MAIN_DB_PREFIX."societe";
                    $sql.= " WHERE import_key = 'P".$indice_current."-".$commande['id_customer']."'";
                    if ($conf->global->cu0032) {
                        $sql = "SELECT rowid, fk_pays, email";
                        $sql.= " FROM ".MAIN_DB_PREFIX."societe";
                        $sql.= " WHERE import_key LIKE '%-".$commande['id_customer']."'";
                        $sql.= " ORDER BY rowid DESC  LIMIT 1";
                    }
                    $resql = $db->query($sql);
                    if ($resql) {
                        if ($db->num_rows($resql) > 0) {
                            $res = $db->fetch_array($resql);
                            $customer_id=$res['rowid'];
                            $fk_pays = $res['fk_pays'];
                        } else $customer_id=0;
                    } else $customer_id=0;
                    if ($customer_id==0) {
                        $list_ok[]=array($commande['id_order'],"client inexistant ".$res['ref'].' '.$commande['id_customer']);
                        continue;
                    }
					$sql = "SELECT warehouse FROM ".MAIN_DB_PREFIX."c_cyberoffice2
							WHERE carrier = ".$commande['id_carrier'];
					if ((int)$commande['id_carrier']>0)
						$result_warehouse = $db->query($sql);
					$idwarehouse1 = 0;
					if ($result_warehouse) {
						$res_warehouse = $db->fetch_array($result_warehouse);
						$idwarehouse1= (isset($res_warehouse['warehouse'])?$res_warehouse['warehouse']:0);
					}
					if ($idwarehouse1 && $idwarehouse1>0)
						$idwarehouse = $idwarehouse1;
					else
						$idwarehouse = $commande['warehouse'];
                    if ($conf->global->cu0032) {
                        //2 => amz eu , 4 => amz ru
                        $mypays ='xx';
                        if (substr($email, -9, 6)=='amazon' || substr($email, -9, 6)=='mazon.') {
                            $mypays=substr($email, -2);
                            if ($mypays == 'uk')
                                $idwarehouse = 4;
                            else
                                $idwarehouse = 2;
                        }
                    }
			/****paiement seul***/
			if ($commande['paieOK'] == 1 && $invoiced == 1) {
				dol_syslog("paiement begin");
				$newobject->fetch($commande_id);
				$newobject->fetchObjectLinked();
				if (array_key_exists('facture', $newobject->linkedObjects)) {
					foreach ($newobject->linkedObjects['facture'] as $invoice) {
						$invoiceid = $invoice->id;
						$idFacture = $invoice->id;
					}
				}
//				dol_syslog("paiement find invoice ".$idFacture);
				$dateinvoice = $commande['invoice_date'];
				$cyberbank = 0;
                $sql = "SELECT c.id, c.code, c.libelle, c.cyberbank
                    FROM ".MAIN_DB_PREFIX."c_paiement as c
                    WHERE c.libelle = '".$db->escape($commande['payment'])."'";
                $resql = $db->query($sql);
                if ($resql) {
                    if ($db->num_rows($resql) > 0) {
                        $res		= $db->fetch_array($resql);
                        $cyberbank	= $res['cyberbank'];
                        } else $cyberbank = 0;
                    } else $cyberbank = 0;
				if ($cyberbank > 0)
					$test=1;
				else {
					$sql = "SELECT ba.rowid FROM ".MAIN_DB_PREFIX."bank_account as ba WHERE ba.ref='".substr($db->escape($commande['module']),0,12)."'";
					$resql = $db->query($sql);
					if ($resql) {
						if ($db->num_rows($resql) > 0) {
							$res 	= $db->fetch_array($resql);
							$accountid = $res['id'];
							$cyberbank= $res['rowid'];
						} else $cyberbank=0;
					} else $cyberbank=0;
				}
//				dol_syslog("cyberoffice paiement date ".$dateinvoice);
				$user = new User($db);
				$user->fetch('', $authentication['login'],'',0);
				$user->getrights();
				$thirdparty = new Societe($db);
				$thirdparty->fetch($invoice->socid);
				$paiement = new Paiement($db);
				$datephour = substr($dateinvoice, 11,2);
				$datepmin = substr($dateinvoice, 14,2);
				$datepsec = substr($dateinvoice, 17,2);
				$datepmonth = substr($dateinvoice, 5,2);
				$datepday = substr($dateinvoice, 8,2);
				$datepyear = substr($dateinvoice, 0,4);
				$paiement->datepaye = dol_mktime($datephour, $datepmin, $datepsec, $datepmonth, $datepday, $datepyear);
				$paiement->amounts                  = array($idFacture => $commande['total_paid']);
				$paiement->amount                   = array($idFacture => $commande['total_paid']);
				$paiement->paiementid               = $accountid;
				$paiement->num_paiement             = $commande['id_order'];
				$paiement->num_payment              = $commande['id_order'];
				if ($newobject->multicurrency_tx && $newobject->multicurrency_tx!=1)
					$paiement->multicurrency_amounts = array($idFacture => $invoice->multicurrency_total_ttc);
//				dol_syslog("paiement->create");
				$paiement_id = $paiement->create($user, 1, $thirdparty);
				$label='(CustomerInvoicePayment)';
//				dol_syslog("paiement->addPaymentToBank");
				$resultP=$paiement->addPaymentToBank($user,'payment',$label,$cyberbank,$commande['firstname'].''.$commande['lastname'],'');
				dol_syslog("paiement end");
			}
			/***facture***/
					if ($invoiced==0) {
						$newobject->fetch($commande_id);
						$newobject->setDraft($user, $idwarehouse);

						$sql = "DELETE FROM ".MAIN_DB_PREFIX."commandedet "
							. " WHERE rowid in ( "
							. " select s.* from ( "
							. " SELECT cd.rowid FROM ".MAIN_DB_PREFIX."commandedet cd "
							. " LEFT JOIN ".MAIN_DB_PREFIX."commande c ON (cd.fk_commande = c.rowid) "
							. " WHERE c.import_key = 'P".$indice_current."-".$commande['id_order']."') s "
							. ")";

						$resql = $db->query($sql);
						if($resql) {
							$sql = "DELETE ";
							$sql.= " FROM ".MAIN_DB_PREFIX."commande";
							$sql.= " WHERE ".MAIN_DB_PREFIX."commande.import_key = 'P".$indice_current."-".$commande['id_order']."'";
//							dol_syslog("CyberOffice_server_order::delete2 sql=".$sql);
							$resql = $db->query($sql);
							$sql = "SELECT rowid,ref";
							$sql.= " FROM ".MAIN_DB_PREFIX."commande";
							$sql.= " WHERE import_key = 'P".$indice_current."-".$commande['id_order']."'";

//							dol_syslog("CyberOffice_server_order::fetch sql=".$sql);
							$resql = $db->query($sql);
							if ($resql) {
								if ($db->num_rows($resql) > 0) {
									$res = $db->fetch_array($resql);
									$commande_id=$res['rowid'];
								} else $commande_id=0;
							} else $commande_id=0;
							dol_syslog("CyberOffice_server_order::commande_id2 =".$commande_id);
							/*****creation
							**************/
							$newobject=new Commande($db);
                            /*
							$sql = "SELECT rowid, fk_pays";
							$sql.= " FROM ".MAIN_DB_PREFIX."societe";
							$sql.= " WHERE import_key = 'P".$indice_current."-".$commande['id_customer']."'";
							$resql = $db->query($sql);
							if ($resql) {
								if ($db->num_rows($resql) > 0) {
									$res = $db->fetch_array($resql);
									$customer_id=$res['rowid'];
									$fk_pays = $res['fk_pays'];
								} else $customer_id=0;
							} else $customer_id=0;
							if ($customer_id==0) {
								$list_ok[]=array($commande['id_order'],"client inexistant ".$res['ref'].' '.$commande['id_customer']);
								continue;
							}
                            */
							$newobject->socid   = $customer_id;
							//$user->id
							$user = new User($db);
							$user->fetch('', $authentication['login'],'',0);
							$user->getrights();
							$newobject->fk_project = "null";
							$newobject->date = $commande['date_add'];//$commande['invoice_date'];
							$newobject->demand_reason_id = 1;//'SRC_INTE';
							//$newobject->note_private = htmlentities("eBoutique N°".$commande['id_order']."\n".$_SERVER['SERVER_NAME']."/index.php?tab=AdminOrders&amp;id_order=".$commande['id_order']."&amp;vieworder",ENT_QUOTES,'UTF-8');
							$newobject->cond_reglement_id = "null";

							//currency
							if (!empty($conf->multicurrency->enabled)) {
								$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."multicurrency WHERE code = '".$commande['iso_code']."'";
								if ($conf->global->cu0032) {
                                    $sql = "SELECT m.rowid , mr.rate"
                                        ." FROM ".MAIN_DB_PREFIX."multicurrency m "
                                        ." left join ".MAIN_DB_PREFIX."multicurrency_rate mr on (m.rowid=mr.fk_multicurrency) "
                                        ." WHERE m.code = '".$commande['iso_code']."' and m.entity = ".$conf->entity
                                        ." order by mr.date_sync desc limit 1";
                                }
								$resql = $db->query($sql);
								if ($resql) {
									if ($db->num_rows($resql) > 0) {
										$res 	= $db->fetch_array($resql);
										$newobject->fk_multicurrency = $res['rowid'];
										$newobject->multicurrency_tx = $commande['conversion_rate'];
                                        if ($conf->global->cu0032) {
                                            $newobject->multicurrency_tx = $res['rate'];
                                        }
										$newobject->multicurrency_code = $commande['iso_code'];
									}
								}
								dol_syslog("CyberOffice_server_order::".$newobject->multicurrency_code."=".$newobject->multicurrency_tx);
							} else {
								$newobject->fk_multicurrency = 0;
								$newobject->multicurrency_tx = 1;
								$newobject->multicurrency_code = $conf->currency;
							}
							//// mode reglement
							$cyberbank = 0;
                            $amazon_us = 0;
							$sql = "SELECT c.id, c.code, c.libelle, c.cyberbank
								FROM ".MAIN_DB_PREFIX."c_paiement as c
								WHERE c.libelle = '".$db->escape($commande['payment'])."'";
							$resql = $db->query($sql);
							if ($resql) {
								if ($db->num_rows($resql) > 0) {
									$res 	= $db->fetch_array($resql);
									$accountid = $res['id'];
									$cyberbank = $res['cyberbank'];
									if ($conf->global->cu0032) {
                                        $mypays ='xx';
                                        if (substr($email, -9, 6)=='amazon' || substr($email, -9, 6)=='mazon.' || substr($email, -9, 6)=='zon.co')
                                            $mypays=substr($email, -2);
                                        switch ($mypays) {
                                            case 'de':
                                                $fk_pays=5;
                                                break;
                                            case 'fr':
                                                $fk_pays=1;
                                                break;
                                            case 'be':
                                                $fk_pays=2;
                                                break;
                                            case 'uk':
                                                $fk_pays=7;
                                                $res['code']='AMZ';
                                                break;
                                            case 'nl':
                                                $fk_pays=17;
                                                break;
                                            case 'se':
                                                $fk_pays=20;
                                                break;
                                            case 'pl':
                                                $fk_pays=184;
                                                break;
                                            case 'es':
                                                $fk_pays=4;
                                                break;
                                            case 'it':
                                                $fk_pays=3;
                                                break;
                                        }
                                        $codeamz = substr($res['code'], 0, 3);
										if ($res['code']=='AMZ' && $fk_pays==5) //AMZAL
                                            {$accountid = 62;$cyberbank = 13;}
                                        elseif ($res['code']=='AMZ' && $fk_pays==1) //AMZFR FR
                                            {$accountid = 65;$cyberbank = 12;}
                                        elseif ($res['code']=='AMZ' && $fk_pays==2) //AMZFR BE
                                            {$accountid = 91;$cyberbank = 31;}
										elseif ($res['code']=='AMZ' && $fk_pays==7) //AMZUK
                                            {$accountid = 63;$cyberbank = 15;$amazon_us = 1;}
										elseif ($res['code']=='AMZ' && $fk_pays==17) //AMZNL
                                            {$accountid = 66;$cyberbank = 14;}
                                        elseif ($res['code']=='AMZ' && $fk_pays==20) //AMZSE
                                            {$accountid = 77;$cyberbank = 19;}
                                        elseif ($res['code']=='AMZ' && $fk_pays==184) //AMZPL
                                            {$accountid = 82;$cyberbank = 30;}
                                        elseif ($res['code']=='AMZ' && $fk_pays==4) //AMZES
                                            {$accountid = 81;$cyberbank = 24;}
                                        elseif ($res['code']=='AMZ' && $fk_pays==3) //AMZIT
                                            {$accountid = 80;$cyberbank = 23;}
									}
                                    if ($conf->global->cu0032us) {
                                        $mypays ='xx';
                                        $pieces = explode("@", $email);
                                        if ($pieces[1]=='marketplace.amazon.ca'
                                            || $pieces[1]=='marketplace.amazon.com'
                                            || $pieces[1]=='amazon.mp.common-services.com' ) {
                                            $mypays=substr($email, -3);
                                            switch ($mypays) {
                                                case '.ca':
                                                    $amazon_us = 1;
                                                    $fk_pays=14;//canada
                                                    $accountid = 67;
                                                    $cyberbank = 8;//amzon ca
                                                    break;
                                                case 'com':
                                                    if ($pieces[1]=='amazon.mp.common-services.com' && $fk_pays==14) {
                                                        $amazon_us = 1;
                                                        $fk_pays=14;//canada
                                                        $accountid = 67;
                                                        $cyberbank = 8;//amzon ca
                                                    } else {
                                                        $amazon_us = 1;
                                                        $fk_pays=11;//usa
                                                        $accountid = 66;
                                                        $cyberbank = 5;//amzon us
                                                    }
                                                    break;
                                                default:
                                                    $amazon_us = 0;
                                                    $accountid = 64;
                                                    $cyberbank = 5;
                                            }
                                        }
                                    }
								} else $accountid =0;
							} else $accountid =0;
							if ($accountid==0) {
								$sql = 'SELECT max(id) as newid from '.MAIN_DB_PREFIX.'c_paiement ';
								$resql= $db->query($sql);
								if ($resql)
								{
									$obj = $db->fetch_object($resql);
									$newid=($obj->newid + 1);
								}
								$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_paiement (id,code,libelle,type)
									VALUES (".$newid.",'P".$commande['id_order']."', '".$db->escape($commande['payment'])."', 2)";
								$resql = $db->query($sql);
								if ($resql)
									$accountid = $newid;
							}
							dol_syslog("CyberOffice_server_order::paiement ".$accountid);
							$newobject->mode_reglement_id   = ($accountid > 0? $accountid:"null");
							$newobject->date_livraison      = $commande['invoice_date'];
							$newobject->model               = $conf->global->COMMANDE_ADDON_PDF;
							$newobject->ref                 = '';
							if($commande['id_shop'] && $commande['id_shop'] > 0)
								$newobject->ref_client = $commande['id_shop']  . ':' . $commande['id_order'] . '/' . $commande['id_cart'];
							else
								$newobject->ref_client = $commande['id_order'] . '/' . $commande['id_cart'];
							if ($commande['ref'] && $conf->global->CYBEROFFICE_desctorefcustomer) {
								$newobject->ref_client = $commande['ref'];
							}
							$newobject->socid = $customer_id;
							$thirdparty = new Societe($db);
							$thirdparty->fetch($customer_id);
							$line=array();
							if ($commande['lines']) {
								$i=0;
								foreach ($commande['lines'] as $line) {
									$sql = "SELECT rowid, tobatch";
									$sql.= " FROM ".MAIN_DB_PREFIX."product";
									if ($commande['match'] == '{ref}' && $line['reference'])
										$sql.= " WHERE ref = '".$line['reference']."'";//."' AND entity=".$conf->entity;
									else
										$sql.= " WHERE import_key = 'P".$indice_current."-".$line['fk_product']."'";
//									dol_syslog("CyberOffice_server_order::fetch sql=".$sql);
									$resql = $db->query($sql);
									if ($resql) {
										if ($db->num_rows($resql) > 0) {
											$res = $db->fetch_array($resql);
											$product_id=$res['rowid'];
										} else $product_id=0;
									} else $product_id=0;
									if (! empty($conf->global->YCF) && $res['tobatch']==1 && $line['qty']>1) {
										for ($j = 1; $j <= $line['qty']; $j++) {
											$newobject->lines[$i]                  = new stdClass();
											$newobject->lines[$i]->desc             = $line['desc'];
											$newobject->lines[$i]->subprice         = $line['subprice'];
											$newobject->lines[$i]->qty              = 1;
											$newobject->lines[$i]->tva_tx           = $line['tva_tx'];
											$newobject->lines[$i]->fk_product       = $product_id;
											$newobject->lines[$i]->remise_percent   = $line['remise_percent'];
											$newobject->lines[$i]->product_type     = 0;
											$newobject->lines[$i]->label            = $line['label'];
											$test_tva = $line['tva_tx'];
											$i++;
											dol_syslog("CyberOffice_server_order::line =".$i);
										}
									} else {
										$newtva_tx = 0;
										$newlocaltax1_tx = 0;
										$newlocaltax1_type = 0;
										$sql  = "SELECT t.taux as rate, t.code, t.localtax1, t.localtax1_type, t.localtax2, t.localtax2_type";
										$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t";
										$sql .= ", ".MAIN_DB_PREFIX."c_country as c";
										$sql .= " WHERE t.fk_pays = c.rowid AND (c.code = '".$db->escape($thirdparty->country_code)."' OR c.code = '".$db->escape($mysoc->country_code)."')";
										$sql .= " AND t.active = 1";

										if ($dolibarr_main_db_type == "pgsql") {
											$sql .= " AND ((t.taux = ".((float) $line['tva_tx'])." AND t.localtax1 = '".((float) $line['tva_tx_local1'])."')";
											$sql .= " OR (t.taux = ".((float) $line['tva_tx_local1'])." AND t.localtax1 = '".((float) $line['tva_tx'])."'))";
										} else {
										   $sql .= " AND ((t.taux = ".((float) $line['tva_tx'])." AND t.localtax1 = ".(isset($line['tva_tx_local1'])?(float) $line['tva_tx_local1']:0).")";
											$sql .= " OR (t.taux = ".(isset($line['tva_tx_local1'])?(float) $line['tva_tx_local1']:0)." AND t.localtax1 = ".((float) $line['tva_tx'])."))";
										}
										$taxesql = $db->query($sql);
										if ($taxesql) {
											$taxenum = $db->num_rows($taxesql);
											$taxei=0;
											while ($taxei < $taxenum)
											{
												$taxeobj = $db->fetch_object($taxesql);
												$text = $taxeobj->rate.':'.$taxeobj->code.':'.$taxeobj->localtax1.':'.$taxeobj->localtax1_type;
												$newtva_tx = $taxeobj->rate. '('.$taxeobj->code.')';
												$newlocaltax1_tx = $taxeobj->localtax1;
												$newlocaltax1_type = $taxeobj->localtax1_type;
												dol_syslog("CyberOffice_server_order::localtaxes = ".$text);
												$taxei++;
											}
										}
										$newobject->lines[$i]                  = new stdClass();

										$desc = $line['desc'];

										$hidedesc = (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0);
										$object_product = new Product($db);
										if ($product_id && $product_id > 0 && $hidedesc == 0) {
											$object_product->fetch($product_id);
											$desc = '';
											if (!empty($conf->global->MAIN_MULTILANGS) && !empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
												$outputlangs=new Translate("",$conf);
												if (isset($conf->MAIN_LANG_DEFAULT) && $conf->MAIN_LANG_DEFAULT != 'auto')
												{
													$outputlangs->getDefaultLang($conf->MAIN_LANG_DEFAULT);
												}
												else
												{
													$outputlangs=$langs;
												}
												$desc = (!empty($object_product->multilangs[$outputlangs->defaultlang]["description"])) ? $object_product->multilangs[$outputlangs->defaultlang]["description"] : $object_product->description;
											} else {
												$desc = $object_product->description;
											}
										}
										$newobject->lines[$i]->desc             = $desc;
                                        if ($conf->global->cu0032) {
                                            $line['subprice'] = $line['subprice'] / $newobject->multicurrency_tx;
                                        }
										$newobject->lines[$i]->subprice         = $line['subprice'];
                                        $newobject->lines[$i]->pa_ht = '';
										$newobject->lines[$i]->qty              = $line['qty'];
										$newobject->lines[$i]->tva_tx           = $newtva_tx;
                                        if ($conf->global->cu0032) {
                                            $newobject->lines[$i]->tva_tx = ($amazon_us == 1?0:$newtva_tx);
                                        }
										$newobject->lines[$i]->localtax1_tx     = $newlocaltax1_tx;
										$newobject->lines[$i]->localtax1_type	= $newlocaltax1_type;
										if ($conf->global->cu0032 && $newobject->lines[$i]->tva_tx == 0) {// && $commande['id_carrier'] == 98
											$newobject->lines[$i]->tva_tx       = $line['tva_tx2'];
										}
										$newobject->lines[$i]->fk_product       = $product_id;
										$newobject->lines[$i]->remise_percent   = $line['remise_percent'];
										$newobject->lines[$i]->product_type     = 0;
										$newobject->lines[$i]->label            = $line['label'];
                                        if ($conf->global->cu0032us && $amazon_us == 1) {
                                            $test_tva = 0;
                                            if ($fk_pays==14) {//canada test order=26499
                                                $newobject->fetch_thirdparty();
                                                $code_province = $newobject->thirdparty->country_code . '_' . $newobject->thirdparty->state_code;
                                                dol_syslog("CyberOffice_server_order::code_province =".$code_province);
                                                $sql  = "SELECT t.taux as rate";
                                                $sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t";
                                                $sql .= " WHERE t.code = '".$db->escape($code_province)."'";
                                                $resql450 = $db->query($sql);
                                                if ($resql450) {
                                                    $res450 = $db->fetch_array($resql450);
                                                    $test_tva = $res450['rate'];
                                                    $newobject->lines[$i]->tva_tx = $test_tva;
                                                    dol_syslog("CyberOffice_server_order::test_tva =".$test_tva);
                                                }
                                            }
                                        } else {
										$test_tva = $line['tva_tx'];
                                        }
										$i++;
										dol_syslog("CyberOffice_server_order::line =".$i." tva_tx=". $line['tva_tx']. " localtax1_tx=". (isset($line['tva_tx_local1'])?$line['tva_tx_local1']:'')." localtax2_tx=". (isset($line['tva_tx_local2'])?$line['tva_tx_local2']:''));
									}
								}
							}
							//shipping
							if ($commande['total_shipping']>0) {
                                if ($conf->global->cu0032) {
                                    $commande['total_shipping'] = $commande['total_shipping'] / $newobject->multicurrency_tx;
                                }
								$sql = "SELECT tva_tx,description,label";
								$sql.= " FROM ".MAIN_DB_PREFIX."product";
								$sql.= " WHERE rowid = ".dolibarr_get_const($db, 'CYBEROFFICE_SHIPPING', $conf->entity);
								$resql = $db->query($sql);
								if ($resql) {
									if ($db->num_rows($resql) > 0) {
										$res 	= $db->fetch_array($resql);
										$tva_tx	= ($test_tva == 0 ? 0 : $res['tva_tx']);
										$desc	= $res['description'];
										$label	= $res['label'];
									} else {$tva_tx=0;$desc='shipping';$label='shipping';}
								} else {$tva_tx=0;$desc='shipping';$label='shipping';}
								if ($commande['carrier_tax_rate'] && $commande['carrier_tax_rate'] > 0) {
									$tva_tx = $commande['carrier_tax_rate'];
								}
								$newobject->lines[$i]                  = new stdClass();
								$newobject->lines[$i]->desc			= $desc;
                                $newobject->lines[$i]->qty			= 1;
								$newobject->lines[$i]->subprice			= $commande['shipping_tax_excl'];
								$newobject->lines[$i]->tva_tx			= $tva_tx;
                                if ($conf->global->cu0032) {
                                    $newobject->lines[$i]->tva_tx = ($amazon_us == 1?0:$tva_tx);
                                }
								if ($newlocaltax1_tx != 0 && $tva_tx != 0) {
									$newobject->lines[$i]->localtax1_tx     = $newlocaltax1_tx;
									$newobject->lines[$i]->localtax1_type   = $newlocaltax1_type;
								}
								$newobject->lines[$i]->fk_product		= dolibarr_get_const($db, 'CYBEROFFICE_SHIPPING', $conf->entity);
								//$newobject->lines[$i]->remise_percent	= $line['remise_percent'];
								$newobject->lines[$i]->product_type		= 1;
								//$newobject->lines[$i]->label			= $label;
								$newobject->lines[$i]->label			= $label. ($commande['carrier_name']?' ('.$commande['carrier_name'].')':'');
								$i++;
								dol_syslog("CyberOffice_server_order::line =shipping ".$i);
							}
							//discount
							$sql = "SELECT tva_tx,description,label"
								. " FROM ".MAIN_DB_PREFIX."product"
								. " WHERE rowid = ".dolibarr_get_const($db, 'CYBEROFFICE_DISCOUNT', $conf->entity);
							$resql = $db->query($sql);
							if ($resql) {
								if ($db->num_rows($resql) > 0) {
									$res 	= $db->fetch_array($resql);
									$tva_tx	= ($test_tva == 0 ? 0 : $res['tva_tx']);//$res['tva_tx'];
									$desc	= $res['description'];
									$label	= $res['label'];
								} else {$tva_tx=0;$desc='discount';$label='discount';}
							} else {$tva_tx=0;$desc='discount';$label='discount';}

							if ($conf->global->CYBEROFFICE_detaildiscount && count($commande['detail_discounts'])>0) {
								foreach ($commande['detail_discounts'] as $detaildiscount)
								{
									$newobject->lines[$i]                       = new stdClass();
									$newobject->lines[$i]->desc                 = $detaildiscount['name'];
									$newobject->lines[$i]->subprice             = $detaildiscount['discounts_tax_excl'];
									$newobject->lines[$i]->qty                  = -1;
									$newobject->lines[$i]->tva_tx               = $newtva_tx;//$tva_tx;
									if ($newlocaltax1_tx != 0 && $tva_tx != 0) {
										$newobject->lines[$i]->localtax1_tx     = $newlocaltax1_tx;
										$newobject->lines[$i]->localtax1_type   = $newlocaltax1_type;
									}
									$newobject->lines[$i]->fk_product           = dolibarr_get_const($db, 'CYBEROFFICE_DISCOUNT', $conf->entity);
									$newobject->lines[$i]->product_type         = 1;
									$newobject->lines[$i]->label                = $detaildiscount['name'];
									$i++;
								}
							} elseif ($commande['total_discounts']>0) {
                                if ($conf->global->cu0032) {
                                    $commande['total_discounts'] = $commande['total_discounts'] / $newobject->multicurrency_tx;
                                }
								$sql = "SELECT tva_tx,description,label";
								$sql.= " FROM ".MAIN_DB_PREFIX."product";
								$sql.= " WHERE rowid = ".dolibarr_get_const($db, 'CYBEROFFICE_DISCOUNT', $conf->entity);
								$resql = $db->query($sql);
								if ($resql) {
									if ($db->num_rows($resql) > 0) {
										$res 	= $db->fetch_array($resql);
										$tva_tx	= ($test_tva == 0 ? 0 : $res['tva_tx']);//$res['tva_tx'];
										$desc	= $res['description'];
										$label	= $res['label'];
									} else {$tva_tx=0;$desc='discount';$label='discount';}
								} else {$tva_tx=0;$desc='discount';$label='discount';}
								$newobject->lines[$i]                  = new stdClass();
								$newobject->lines[$i]->desc		= $desc;
								/*
								if ($commande['discounts_tax_excl'] && $commande['discounts_tax_excl'] > 0) {
									$newobject->lines[$i]->subprice = $commande['discounts_tax_excl'];
								} else {
								*/
								$newobject->lines[$i]->subprice = round($commande['total_discounts']/(1 + $newtva_tx/100),$conf->global->MAIN_MAX_DECIMALS_UNIT);
								$newobject->lines[$i]->qty		= -1;
								$newobject->lines[$i]->tva_tx		= $newtva_tx;//$tva_tx;
                                if ($conf->global->cu0032) {
                                    $newobject->lines[$i]->tva_tx = ($amazon_us == 1?0:$newtva_tx);
                                }
								if ($newlocaltax1_tx != 0 && $tva_tx != 0) {
									$newobject->lines[$i]->localtax1_tx     = $newlocaltax1_tx;
									$newobject->lines[$i]->localtax1_type   = $newlocaltax1_type;
								}
								$newobject->lines[$i]->fk_product	= dolibarr_get_const($db, 'CYBEROFFICE_DISCOUNT', $conf->entity);
								//$newobject->lines[$i]->remise_percent	= $line['remise_percent'];
								$newobject->lines[$i]->product_type	= 1;
								$newobject->lines[$i]->label		= $label;
								$i++;
								dol_syslog("CyberOffice_server_order::line =discount ".$i);
							}

							//wrapping
							if ($commande['wrapping_ti']>0) {
                                if ($conf->global->cu0032) {
                                    $commande['wrapping_ti'] = $commande['wrapping_ti'] / $newobject->multicurrency_tx;
                                    $commande['wrapping_te'] = $commande['wrapping_te'] / $newobject->multicurrency_tx;
                                }
								$sql = "SELECT tva_tx,description,label";
								$sql.= " FROM ".MAIN_DB_PREFIX."product";
								$sql.= " WHERE rowid = ".dolibarr_get_const($db, 'CYBEROFFICE_wrapping', $conf->entity);
								$resql = $db->query($sql);
								if ($resql) {
									if ($db->num_rows($resql) > 0) {
										$res 	= $db->fetch_array($resql);
										$tva_tx	= ($test_tva == 0 ? 0 : $res['tva_tx']);
										$desc	= $res['description'];
										$label	= $res['label'];
									} else {
										$tva_tx=0;
										$desc='wrapping';$label='wrapping';
									}
								} else {
									$tva_tx=0;
									$desc='wrapping';
									$label='wrapping';
								}
								$newobject->lines[$i]                  = new stdClass();
								$newobject->lines[$i]->desc		= $desc;
								$newobject->lines[$i]->subprice		= $commande['wrapping_te'];
								$newobject->lines[$i]->qty		= 1;
								$newobject->lines[$i]->tva_tx		= $tva_tx;
                                if ($conf->global->cu0032) {
                                    $newobject->lines[$i]->tva_tx = ($amazon_us == 1?0:$tva_tx);
                                }
								if ($newlocaltax1_tx != 0 && $tva_tx != 0) {
									$newobject->lines[$i]->localtax1_tx     = $newlocaltax1_tx;
									$newobject->lines[$i]->localtax1_type   = $newlocaltax1_type;
								}
								$newobject->lines[$i]->fk_product	= dolibarr_get_const($db, 'CYBEROFFICE_wrapping', $conf->entity);
								//$newobject->lines[$i]->remise_percent	= $line['remise_percent'];
								$newobject->lines[$i]->product_type	= 1;
								$newobject->lines[$i]->label		= $label;
								$i++;
								dol_syslog("CyberOffice_server_order::line =wrapping ".$i);
							}
							$newobject->socid = $customer_id;
							$newobject->fk_project = 0;
							$newobject->fetch_thirdparty();

							$result = -9;

							if ($i>0)
								$newobject->entity=$conf->entity;
							if ($conf->global->CYBEROFFICE_desctoref) {
								$newobject->ref = $commande['ref'];
							}
								$result = $newobject->create($user);
							if ($result < 0) {
								dol_syslog("server_order::erreur creation=".$commande['id_order'].' errorcode = '.$result);
								$list_ok[]=array($commande['id_order'],"Order error");
								continue;
							}
							$newobject->fetch($result);
							/*
							//->create erase localtax !!
							foreach ($newobject['lines'] as $line)
							{
								updateline($rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1 = 0.0, $txlocaltax2 = 0.0, $price_base_type = 'HT', $info_bits = 0, $date_start = '', $date_end = '', $type = 0, $fk_parent_line = 0, $skip_update_total = 0, $fk_fournprice = null, $pa_ht = 0, $label = '', $special_code = 0, $array_options = 0, $fk_unit = null, $pu_ht_devise = 0, $notrigger = 0, $ref_ext = '')
							}
							 */
							$sql = "UPDATE ".MAIN_DB_PREFIX."commande SET";
							$sql .= " import_key='P".$indice_current."-".$commande['id_order']."'";
							$sql.= " WHERE rowid=".$result;
//							dol_syslog("server_order::update key - sql=".$sql);
							$resql = $db->query($sql);
							// maj message
							$result_m=$newobject->update_note(dol_html_entity_decode($commande['message'], ENT_QUOTES),'_public');
							/*
								$sql = "UPDATE ".MAIN_DB_PREFIX."commandedet SET";
								$sql .= " import_key='P".$indice."-".$commande['id_order']."'";
								$sql.= " WHERE fk_commande=".$result;
								dol_syslog("server_order::update key - sql=".$sql);
								$resql = $db->query($sql);
							*/
							//$user->getrights();
							$newobject->id = $result;
							$newobject->socid = $customer_id;

							$newobject->array_options['options_entrepot'] = $idwarehouse;
							$newobject->insertExtraFields();

							$result_valid = $newobject->valid($user, $idwarehouse);
							$newobject->insertExtraFields();
							dol_syslog("Cyberoffice::order validation " . $result_valid." warehouse=".$idwarehouse.$sql);
							//15/11/18
							$sql = "UPDATE ".MAIN_DB_PREFIX."commande";
							$sql.= " SET ref = '".$commande_ref."'";
							$sql.= " WHERE rowid = ".$newobject->id;
							if ($commOK_0==1) {
								$resql_commande_ref=$db->query($sql);
//								dol_syslog("Cyberoffice::order ref " . $sql);
							}

							/* ajout adresse livraison
							**************************/
							$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."socpeople WHERE import_key = 'P".$indice_current."-".$commande['id_address_delivery']."'";
							$resql = $db->query($sql);
							if ($resql) {
								if ($db->num_rows($resql) > 0) {
									$res = $db->fetch_array($resql);
									$contact_ship=$res['rowid'];
								} else $contact_ship=0;
							} else $contact_ship=0;
							if($contact_ship > 0)
								$newobject->add_contact($contact_ship, 'SHIPPING', 'external');

							/* ajout adresse facturation
							****************************/
							$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."socpeople WHERE import_key = 'P".$indice_current."-".$commande['id_address_invoice']."'";
							$resql = $db->query($sql);
							if ($resql) {
								if ($db->num_rows($resql) > 0) {
									$res = $db->fetch_array($resql);
									$contact_ship=$res['rowid'];
								} else $contact_ship=0;
							} else $contact_ship=0;
							if($contact_ship > 0) $newobject->add_contact($contact_ship, 'BILLING', 'external');

							if ($result_valid >= 0) {
								/* Decrease Stock
								*****************/
								if ($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER == 1 || $conf->global->STOCK_CALCULATE_ON_BILL == 1 || $conf->global->STOCK_CALCULATE_ON_SHIPMENT == 1)
									$DecreaseYesNo = 0;
								else
									$DecreaseYesNo = 1;
								dol_syslog("Cyberoffice::order :: " . $conf->global->CYBEROFFICE_stock .'-'.$DecreaseYesNo);
								if ($conf->global->CYBEROFFICE_stock==1 && $DecreaseYesNo == 1) {
									require_once DOL_DOCUMENT_ROOT . '/product/stock/class/mouvementstock.class.php';
									$langs->load("cyberoffice@cyberoffice8");
									// Loop on each line
									$cpt=count($newobject->lines);
									for ($i = 0; $i < $cpt; $i++)
									{
										if ($newobject->lines[$i]->fk_product > 0)
										{
											$mouvP = new MouvementStock($db);
											$mouvP->origin = &$newobject;
											// We decrease stock for product
											$resultStock=$mouvP->livraison($user, $newobject->lines[$i]->fk_product, $idwarehouse, $newobject->lines[$i]->qty, $newobject->lines[$i]->subprice, $langs->trans("OrderValidatedInDolibarrFromCyberoffice",$newobject->newref));
											dol_syslog("Cyberoffice::Decrease Stock on warehouse " . $idwarehouse . " product=" . $newobject->lines[$i]->fk_product);
										}
									}
								}

								if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
									$ret = $newobject->fetch($result);
									$outputlangs = $langs;
									$newlang = '';
									if (!empty($conf->global->MAIN_MULTILANGS) && empty($newlang)) {
											$newlang = $newobject->thirdparty->default_lang;
									}
									if (!empty($newlang)) {
											$outputlangs = new Translate("", $conf);
											$outputlangs->setDefaultLang($newlang);
											$outputlangs->load('products');
									}
                                    if ($conf->global->cu0032) {
                                        if ($fk_pays == 1) {
                                            $outputlangs->setDefaultLang('fr_FR');
                                        } elseif (isset($conf->MAIN_LANG_DEFAULT) && $conf->MAIN_LANG_DEFAULT != 'auto') {
                                            $outputlangs->setDefaultLang($conf->MAIN_LANG_DEFAULT);
                                        } else {
                                            $outputlangs->setDefaultLang('en_US');
                                        }
                                    }
                                    if ($conf->global->cu0032us) {
                                        if ($fk_pays == 1) {
                                            $outputlangs->setDefaultLang('fr_FR');
                                        } elseif (isset($conf->MAIN_LANG_DEFAULT) && $conf->MAIN_LANG_DEFAULT != 'auto') {
                                            $outputlangs->setDefaultLang($conf->MAIN_LANG_DEFAULT);
                                        } else {
                                            $outputlangs->setDefaultLang('en_GB');
                                        }
                                    }

									$resultPdf = $newobject->generateDocument($newobject->model, $outputlangs, $hidedetails, $hidedesc, $hideref);
								}
							}
							/* facture
							**********/

							if($commande['factOK']==1) {
								dol_syslog("Cyberoffice::order " . __LINE__);
								$facture = new Facture($db);

								$facture->fk_multicurrency = $newobject->fk_multicurrency;
								$facture->multicurrency_tx = $newobject->multicurrency_tx;
								$facture->multicurrency_code = $newobject->multicurrency_code;
								if ($commande['transaction'])
									$facture->note_public = $commande['transaction'];
								//$dateinvoice = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
								$dateinvoice = $commande['invoice_date'];
								$facture->socid				= $customer_id;
								$facture->type           	= 0;
								$facture->mode_reglement_id	= $newobject->mode_reglement_id;
								$facture->date           	= $dateinvoice;
								//$facture->date_lim_reglement= $dateinvoice;
								//$object->note_public	= trim($_POST['note_public']);
								//$object->note_private   = trim($_POST['note_private']);
								if($commande['id_shop'] && $commande['id_shop'] > 0)
									$facture->ref_client = $commande['id_shop']  . ':' . $commande['id_order'] . '/' . $commande['id_cart'];
								else
									$facture->ref_client = $commande['id_order'] . '/' . $commande['id_cart'];
								if ($conf->global->CYBEROFFICE_desctorefcustomer) {
									$facture->ref_client = $commande['ref'];
								}
								//$facture->ref_int     		= $_POST['ref_int'];
								$facture->modelpdf       	= $conf->global->FACTURE_ADDON_PDF;
								$facture->origin			= 'commande';
								$facture->originid			= $newobject->id;
								//$facture->fac_rec 			= $_POST['fac_rec'];
								//$facture->amount			=
								//$idFacture = $facture->create($user);
								///////////////////////
								$facture->fetch_thirdparty();
								$element = $subelement = $facture->origin;
								if (preg_match('/^([^_]+)_([^_]+)/i', $facture->origin, $regs)) {
									$element = $regs [1];
									$subelement = $regs [2];
								}
								// For compatibility
								if ($element == 'order') {
									$element = $subelement = 'commande';
								}

								$facture->origin			= $element;
								$facture->origin_id			= $newobject->id;

								$facture->linked_objects [$facture->origin] = $facture->origin_id;
								$facture->entity=$conf->entity;
								$idFacture = $facture->create($user);

								if ($idFacture > 0) {
									dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

									$classname = ucfirst($subelement);
									$srcobject = new $classname($db);

									dol_syslog("Cyberoffice::Try to find source object origin=" . $facture->origin . " originid=" . $facture->origin_id . " to add lines");
									$resultF = $srcobject->fetch($facture->origin_id);
									if ($resultF > 0) {
										$lignes = $srcobject->lines;
										if (empty($lignes) && method_exists($srcobject, 'fetch_lines'))
											$lignes = $srcobject->fetch_lines();

										$fk_parent_line=0;
										$num=count($lignes);
										for ($i=0;$i<$num;$i++)
										{
											$label=(! empty($lignes[$i]->label)?$lignes[$i]->label:'');
											$desc=(! empty($lignes[$i]->desc)?$lignes[$i]->desc:$lignes[$i]->libelle);

											if ($lignes [$i]->subprice < 0) {
												// Negative line, we create a discount line
												$discount = new DiscountAbsolute($db);
												$discount->fk_soc = $facture->socid;
												$discount->amount_ht = abs($lignes [$i]->total_ht);
												$discount->amount_tva = abs($lignes [$i]->total_tva);
												$discount->amount_ttc = abs($lignes [$i]->total_ttc);
												$discount->tva_tx = $lignes [$i]->tva_tx;
												$discount->fk_user = $user->id;
												$discount->description = $desc;
												$discountid = $discount->create($user);
												if ($discountid > 0) {
													$resultL = $facture->insert_discount($discountid); // This include link_to_invoice
												} else {
													$mesgs [] = $discount->error;
													//$error ++;
													break;
												}
											} else {
												// Positive line
												$product_type = ($lignes [$i]->product_type ? $lignes [$i]->product_type : 0);

												// Date start
												$date_start = false;
												if ($lignes [$i]->date_debut_prevue)
													$date_start = $lignes [$i]->date_debut_prevue;
												if ($lignes [$i]->date_debut_reel)
													$date_start = $lignes [$i]->date_debut_reel;
												if ($lignes [$i]->date_start)
													$date_start = $lignes [$i]->date_start;

												// Date end
												$date_end = false;
												if ($lignes [$i]->date_fin_prevue)
													$date_end = $lignes [$i]->date_fin_prevue;
												if ($lignes [$i]->date_fin_reel)
													$date_end = $lignes [$i]->date_fin_reel;
												if ($lignes [$i]->date_end)
													$date_end = $lignes [$i]->date_end;

												// Reset fk_parent_line for no child products and special product
												if (($lignes [$i]->product_type != 9 && empty($lignes [$i]->fk_parent_line)) || $lignes [$i]->product_type == 9) {
													$fk_parent_line = 0;
												}

												// voir /core/class/commonobject/update_price(1,'auto',0,$mysoc)
												$resultL= $facture->addline($desc, $lignes [$i]->subprice, $lignes [$i]->qty, $lignes [$i]->tva_tx.'('.$lignes [$i]->vat_src_code.')', $lignes [$i]->localtax1_tx, $lignes [$i]->localtax2_tx, $lignes [$i]->fk_product, $lignes [$i]->remise_percent, $date_start, $date_end, 0, $lignes [$i]->info_bits, $lignes [$i]->fk_remise_except, 'HT', 0, $product_type, $lignes [$i]->rang, $lignes [$i]->special_code, $facture->origin, $lignes [$i]->rowid, $fk_parent_line, $lignes [$i]->fk_fournprice, $lignes [$i]->pa_ht, $label, $array_option);

												if ($resultL> 0) {
													$lineid = $result;
												}
												// Defined the new fk_parent_line
												if ($resultL> 0 && $lignes [$i]->product_type == 9) {
													$fk_parent_line = $result;
												}
											}
										}//fin for
									}
								}
								$facture->fetch($idFacture);
								$facture->cond_reglement_code = 0; // To clean property
								$facture->cond_reglement_id = 0; // To clean property
								$facture->date_lim_reglement = $dateinvoice;
								$resultR = $facture->update($user);
								$ret=$newobject->classifyBilled($user);
								if ($commande['valid']== 4 || $commande['valid']== 5 || $conf->global->cu0032)
									$ret=$newobject->cloture($user);//que sur le statut en cours livraison & livré
								$facture->fetch_thirdparty();
								//$idwarehouse = $commande['warehouse'];
								$resultF = $facture->validate($user,'', $idwarehouse);
								//$result = $facture->set_paid($user);
								if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
									$ret = $facture->fetch($idFacture);
									$outputlangs = $langs;
									$newlang = '';
									if (!empty($conf->global->MAIN_MULTILANGS) && empty($newlang)) {
											$newlang = $facture->thirdparty->default_lang;
									}
									if (!empty($newlang)) {
											$outputlangs = new Translate("", $conf);
											$outputlangs->setDefaultLang($newlang);
											$outputlangs->load('products');
									}
                                    if ($conf->global->cu0032) {
                                        if ($fk_pays == 1) {
                                            $outputlangs->getDefaultLang('fr_FR');
                                        } elseif (isset($conf->MAIN_LANG_DEFAULT) && $conf->MAIN_LANG_DEFAULT != 'auto') {
                                            $outputlangs->getDefaultLang($conf->MAIN_LANG_DEFAULT);
                                        } else {
                                            $outputlangs->getDefaultLang('en_GB');
                                        }
                                    }
                                    if ($conf->global->cu0032us) {
                                        if ($fk_pays == 1) {
                                            $outputlangs->setDefaultLang('fr_FR');
                                        } elseif (isset($conf->MAIN_LANG_DEFAULT) && $conf->MAIN_LANG_DEFAULT != 'auto') {
                                            $outputlangs->setDefaultLang($conf->MAIN_LANG_DEFAULT);
                                        } else {
                                            $outputlangs->setDefaultLang('en_US');
                                        }
                                    }
									$resultPdf = $facture->generateDocument($facture->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
								}
								/*payment
								*********/
								/*
								if ($commande['module'] == 'cheque') {
								$sql = "SELECT ba.rowid FROM ".MAIN_DB_PREFIX."bank_account as ba WHERE ba.code='".$db->escape($commande['payment'])."'";
								$resql = $db->query($sql);
								if ($resql) {
								if ($db->num_rows($resql) > 0) {
								$res 	= $db->fetch_array($resql);
								$accountid = $res['rowid'];
								} else $accountid =0;
								} else $accountid =0;
								} else {
								*/
								//$sql = "SELECT ba.rowid FROM ".MAIN_DB_PREFIX."bank_account as ba WHERE ba.label='".$db->escape($commande['payment'])."'";
								if ($commande['paieOK'] == 1 || empty($conf->global->CYBEROFFICE_nopayment) || $commande['valid'] != $conf->global->CYBEROFFICE_nopayment)  {
							dol_syslog("paiement2 begin");
									if ($cyberbank > 0)
										$test=1;//$accountid = $cyberbank;
									else {
										$sql = "SELECT ba.rowid FROM ".MAIN_DB_PREFIX."bank_account as ba WHERE ba.ref='".substr($db->escape($commande['module']),0,12)."'";
										$resql = $db->query($sql);
										if ($resql) {
											if ($db->num_rows($resql) > 0) {
												$res 	= $db->fetch_array($resql);
												$cyberbank= $res['rowid'];
											} else $cyberbank=0;
										} else $cyberbank=0;
										if ($cyberbank== 0) {
											$account = new Account($db,0);
											$account->comment		= 'Cyberoffice';
											$account->ref		= substr($db->escape($commande['module']),0,12);//$commande['payment'];
											$account->label		= $commande['module'];//$commande['payment'];
											$account->date_solde	= dol_now();
											$account->courant		= 1;
											$account->currency_code	= $conf->global->MAIN_MONNAIE;
											$country_id = explode( ':' , $conf->global->MAIN_INFO_SOCIETE_COUNTRY );
											$account->country_id	= $country_id[0];//1:FR:France
											$cyberbank= $account->create($user);
										}
									}
//									dol_syslog("cyberoffice paiement date ".$dateinvoice);
									$user = new User($db);
									$user->fetch('', $authentication['login'],'',0);
									$user->getrights();
									$thirdparty = new Societe($db);
									$thirdparty->fetch($facture->socid);
									$paiement = new Paiement($db);

                                    if ($conf->global->cu0032 && $amazon_us == 1) {
                                        $total_paid = $facture->total_ht;
                                    } else {
                                        $total_paid = $commande['total_paid'];
                                    }

									$datephour = substr($dateinvoice, 11,2);
									$datepmin = substr($dateinvoice, 14,2);
									$datepsec = substr($dateinvoice, 17,2);
									$datepmonth = substr($dateinvoice, 5,2);
									$datepday = substr($dateinvoice, 8,2);
									$datepyear = substr($dateinvoice, 0,4);
									$paiement->datepaye                 = dol_mktime($datephour, $datepmin, $datepsec, $datepmonth, $datepday, $datepyear);

									$paiement->amounts                  = array($idFacture => $total_paid);
									$paiement->amount                   = array($idFacture => $total_paid);

									$paiement->paiementid               = $accountid;//($commande['module'] == 'cheque'?dol_getIdFromCode($db,'CHQ','c_paiement'):dol_getIdFromCode($db,'VAD','c_paiement'));
									$paiement->num_paiement             = $commande['id_order'];
									$paiement->num_payment              = $commande['id_order'];
									if ($newobject->multicurrency_tx && $newobject->multicurrency_tx!=1)
										$paiement->multicurrency_amounts    = array($idFacture => $facture->multicurrency_total_ttc);
									//$paiement->note         = $_POST['comment'];
									//$account->fetch(0,$commande['payment']);
							if ($commande['paieOK'] == 1) {
									$paiement_id = $paiement->create($user, 1, $thirdparty);
									$label='(CustomerInvoicePayment)';
									$resultP=$paiement->addPaymentToBank($user,'payment',$label,$cyberbank,$commande['firstname'].''.$commande['lastname'],'');//27968
							}
							dol_syslog("paiement2 end");
								}//fin paiement
							}
							if ($result> 0)
								$list_ok[]=array($commande['id_order'],"Create Order ".$result);//,"(".$commande['invoice_date'].")");
						}//fin resql delete
					}//fin invoiced
					else
						$list_ok[]=array($commande['id_order'],"Order exist ".$res['ref']);

					$sql = "SELECT rowid";
					$sql.= " FROM ".MAIN_DB_PREFIX."commande";
					$sql.= " WHERE import_key = 'P".$indice_current."-".$commande['id_order']."'";
//					dol_syslog("CyberOffice_server_order::fetch".__LINE__." sql=".$sql);
					$resql = $db->query($sql);
					if ($resql) {
						if ($db->num_rows($resql) > 0) {
							$res = $db->fetch_array($resql);
							$commande_id=$res['rowid'];
						} else $commande_id=0;

					} else $commande_id=0;

					if ($commande_id>0) {
						$newobject=new Commande($db);
						$newobject->fetch($commande_id);
						$user = new User($db);
						$user->fetch('', $authentication['login'],'',0);
						$user->getrights();
						if($commande['commOK']==0 && $commOK_0==0)
							$newobject->delete($user, 1);
						else {
							//$db->commit();
							//$db->begin();
							dol_syslog("CyberOffice_server_order::".__LINE__. " : ".$commande['valid']. ' '.$commande['payment']);
							$retcommande = 99;
							if ($commande['valid']== 4)//expédié
									$ret = $newobject->setStatut(2);
							if ($commande['valid']== 5)//livré
								$ret=$newobject->cloture($user);//que sur le statut en cours livraison & livré
						}
					}
				}
			}
		}
		if (! $error) {
			$db->commit();
			$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>(isset($retcommande)?$retcommande:'')),'description'=>json_encode($list_ok));
		} else {
			$db->rollback();
			$error++;
			$errorcode='KO';
			$errorlabel=$newobject->error;
		}

		if ($error) {
			$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel),'description'=>json_encode($list_ok));
		}
	$db->close();
		return $objectresp;
	}
}

$options = array('uri' => $_SERVER['SERVER_NAME']);

$server = new SoapServer(null, $options);

$server->setClass('DataServer');

$server->handle();
