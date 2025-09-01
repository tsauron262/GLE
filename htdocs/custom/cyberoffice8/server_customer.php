<?php
/**
 *  CyberOffice
 *
 *  @author    LVSinformatique <contact@lvsinformatique.com>
 *  @copyright 2014 LVSInformatique
 *  @license   NoLicence
 *  This source file is subject to a commercial license from LVSInformatique
 *  Use, copy, modification or distribution of this source file without written
 *  license agreement from LVSInformatique is strictly forbidden.
 */

// This is to make Dolibarr working with Plesk
define('NOCSRFCHECK', 1);

set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once '../../master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/cyberoffice8/class/cyberoffice.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
class DataServer
{
    public function create($authentication, $params, $myparam1, $myparam2)
    {
		global $db,$conf,$langs;

		dol_syslog("Call Dolibarr webservices interfaces::ServerCustomer");

		$langs->load("main");

		if (empty($conf->global->WEBSERVICES_KEY)) {
			$langs->load("cyberoffice@cyberoffice8");
			dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
			print $langs->trans("KeyForWebServicesAccess2");
			exit;
		}

		$now=dol_now();

		dol_syslog("Function: create login=".$authentication['login']);

		if ($authentication['entity']) $conf->entity=$authentication['entity'];
        if (!empty($conf->global->MAIN_MODULE_MULTICOMPANY)) {
            dol_include_once(DOL_DOCUMENT_ROOT . '/custom/multicompany/class/actions_multicompany.class.php');
            $mc = new ActionsMulticompany($db);
            $returnmc = $mc->switchEntity($authentication['entity']);
            $conf->global->WEBSERVICES_KEY=dolibarr_get_const($db, 'WEBSERVICES_KEY', $authentication['entity']);
        }
		$objectresp=array();
		$errorcode='';
		$errorlabel='';
		$error=0;
		$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
		if ($error) {
			$objectresp = array('result'=>array('result_code' => 'ko', 'result_label' => 'ko'),'webservice'=>'login');
			$error++;
			return $objectresp;
		}

		$error=0;
		//dol_syslog("CyberOffice_server_customer::line=".__LINE__);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

		$user = new User($db);
		$user->fetch('', $authentication['login'],'',0);
		$user->getrights();

		$cyber = new Cyberoffice;
		$cyber->entity = 0;
		$cyber->myurl = $authentication['myurl'];
		$indice = $cyber->numShop();

		$socid_old=0;
		if (is_array($params) && sizeof($params)>0) {	
			foreach ($params as $customer)
			{
				$newobject = new Societe($db);
				$db->begin();
				$list_ok.= '<br/>Traitement : '.$customer['id_customer'];
				$default_lang = "null";
				if ($conf->global->CYBEROFFICE_custlang) {
					$default_lang_array = explode('-', $customer['lang']);
					if (count($default_lang_array) == 2) {
						$default_lang = $default_lang_array[0].'_'.strtoupper($default_lang_array[1]);
					} elseif (count($default_lang_array) == 1) {
						$default_lang = $default_lang_array[0].'_'.strtoupper($default_lang_array[0]);
					}
					if (is_dir(DOL_DOCUMENT_ROOT."/langs/".$default_lang)) {
						//ras
					} else {
						$default_lang = "null";
					}
				}

			//recherche de la correspondance
			$sql = "SELECT rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."societe";
			$sql.= " WHERE import_key = 'P".$indice."-".$customer['id_customer']."'";
			//dol_syslog("CyberOffice_server_customer::fetch sql=".$sql);
			$resql = $db->query($sql);
			if ($resql) {
					if ($db->num_rows($resql) > 0) {
						$res = $db->fetch_array($resql);
						$res_rowid=$res['rowid'];
					} else {
						$res_rowid=0;
					}
			} else {
				$res_rowid=0;
			}

			if ($customer['id_customer'] == '') {
				$res_rowid=0;
			}

			//creation
			$newobject->status		= 1;
			$newobject->name 		= ($customer['company']?$customer['company']:$customer['lastname'].' '.$customer['firstname']);
			$newobject->name_alias		= ($customer['company']?$customer['lastname'].' '.$customer['firstname']:'');
			if ($conf->global->CYBER_UPPER) {
					$newobject->name = mb_strtoupper($newobject->name);
					$newobject->name_alias = mb_strtoupper($newobject->name_alias);
				}
				$newobject->client		= ($customer['client']==2?2:1);//1=customer 2=prospect
			$newobject->fournisseur		= 0;
			$newobject->import_key		= ($customer['id_customer']?"P".$indice."-".$customer['id_customer']:'');
			$newobject->email		= $customer['email'];
				$newobject->get_codeclient($newobject, 0);
				if ($newobject->code_client == '') {
					$newobject->code_client = 'eshop'.time().$customer['id_customer'];
				} else {
					$newobject->code_client 	= -1;
				}
			$newobject->id_customer		= $customer['id_customer']; 
			$newobject->civility_id		= '';//$customer['id_gender'];  //1 hommeMR, 2 femmeMME 9 inconnu
				if ($customer['id_gender'] == 1) {$newobject->civility_id = 'MR';}
				if ($customer['id_gender'] == 2) {$newobject->civility_id = 'MME';}
			$newobject->firstname 		= $customer['firstname']; 
			$newobject->id_address 		= $customer['id_address']; 
			$newobject->company 		= $customer['company'];
			$newobject->address			= $customer['address']; 
			$newobject->zip				= $customer['postcode'];
			$newobject->town			= $customer['city'];
			$newobject->phone_pro 		= ($customer['phone']?$customer['phone']:$customer['phone_mobile']);
			$newobject->phone	 	= ($customer['phone']?$customer['phone']:$customer['phone_mobile']);
			$newobject->phone_mobile	= ($customer['phone_mobile']?$customer['phone_mobile']:$customer['phone']);
			$newobject->tva_intra		= $customer['vat_number'];
					$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."c_country WHERE code LIKE '".$customer['c_iso_code']."'";

			$resql = $db->query($sql);
			if ($resql) {
					if ($db->num_rows($resql) > 0) {
						$res = $db->fetch_array($resql);
				$pays_rowid	=	$res['rowid'];
					} else 	$pays_rowid	=	0;
			} else 		$pays_rowid	=	0;
			$newobject->country_id      = $pays_rowid;

				$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."c_departements WHERE code_departement LIKE '".$customer['state_iso']."'";
				$sql = "SELECT d.* FROM ".MAIN_DB_PREFIX."c_departements d "
					. " left join ".MAIN_DB_PREFIX."c_regions r ON (d.fk_region = r.code_region) "
					. " WHERE d.code_departement LIKE '".$customer['state_iso']."' and r.fk_pays=".$pays_rowid;
				//dol_syslog("CyberOffice_server_customer::etats sql=".$sql);
			$resql = $db->query($sql);
			if ($resql) {
					if ($db->num_rows($resql) > 0) {
						$res = $db->fetch_array($resql);
				$etat_rowid	=	$res['rowid'];
					} else 	$etat_rowid	=	0;
			} else 		$etat_rowid	=	0;
			$newobject->state_id      = $etat_rowid;
				if ($conf->global->cu0032 && $indice=='02') {
					$newobject->array_options = array("options_btob" => 1);
				}
			$newobject->commercial_id 	= -1;
			$user = new User($db);
			//$user = new User($db);
				$user->fetch('', $authentication['login'],'',0);
				$user->getrights();
			//dol_syslog("CyberOffice_server_customer::".__LINE__);
			$result = $res_rowid;
			if ($res_rowid == 0) {
					$result = $newobject->create($user);
					$newobject->fetch($result);
					$newobject->default_lang = ($default_lang!="null" ? $db->escape($default_lang) : "");

					$newobject->update($result,$user);
//					dol_syslog("CyberOffice_server_customer::create ".__LINE__);
					if ($result > 0) $list_ok.="<br/>Create Tiers : *".$result. '* : ' .$newobject->name;
					$societe_id = $result;
			} else {
					$list_ok.="<br/>Update Tiers : *".$res_rowid. '* : ' .$newobject->name;
					$sql = "SELECT rowid";
					$sql.= " FROM ".MAIN_DB_PREFIX."societe";
					$sql.= " WHERE import_key = 'P".$indice."-".$customer['id_customer']."'";
//					dol_syslog("CyberOffice_server_customer::fetch sql=".$sql);
					$resql = $db->query($sql);
					if ($resql) {
						if ($db->num_rows($resql) > 0) {
							$res = $db->fetch_array($resql);
							$societe_id=$res['rowid'];
						} else $societe_id=0;
					} else $societe_id=0;
//					dol_syslog("CyberOffice_server_customer::".__LINE__);
					if ($societe_id>0) {
						$newobject->fetch($societe_id);
						$newobject->default_lang = ($default_lang!="null" ? $db->escape($default_lang) : "");
				$newobject->status      = 1;
				$newobject->name 	= ($customer['company']?$customer['company']:$customer['lastname'].' '.$customer['firstname']);
				$newobject->name_alias	= ($customer['company']?$customer['lastname'].' '.$customer['firstname']:'');
				if ($conf->global->CYBER_UPPER) {
							$newobject->name = mb_strtoupper($newobject->name);
							$newobject->name_alias = mb_strtoupper($newobject->name_alias);
						}
				$newobject->client	= 1;
				//$newobject->fournisseur = 0;
				$newobject->import_key	= "P".$indice."-".$customer['id_customer'];
				$newobject->email	= $customer['email'];
				if (!$newobject->code_client || $newobject->code_client == '') {
							$newobject->get_codeclient($newobject, 0);
						}
						if ($newobject->code_client == '') {
							$newobject->code_client = 'eshop'.time().$customer['id_customer'];
						} else {
							$newobject->code_client 	= -1;
						}
				$newobject->id_customer	= $customer['id_customer']; 
				$newobject->civility_id	= '';//$customer['id_gender'];  //1 homme, 2 femme 9 inconnu
						if ($customer['id_gender'] == 1) {$newobject->civility_id = 'MR';}
						if ($customer['id_gender'] == 2) {$newobject->civility_id = 'MME';}
				$newobject->firstname 	= $customer['firstname']; 
				$newobject->id_address 	= $customer['id_address']; 
				$newobject->company 	= $customer['company'];
				$newobject->address	= $customer['address']; 
				$newobject->zip		= $customer['postcode'];
				$newobject->town	= $customer['city'];
				$newobject->phone_pro = ($customer['phone']?$customer['phone']:$customer['phone_mobile']);
				$newobject->phone = ($customer['phone']?$customer['phone']:$customer['phone_mobile']);
				$newobject->phone_mobile	= ($customer['phone_mobile']?$customer['phone_mobile']:$customer['phone']);
				$newobject->tva_intra		= $customer['vat_number'];
							$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."c_country WHERE code LIKE '".$customer['c_iso_code']."'";
//				dol_syslog("CyberOffice_server_customer::pays sql=".$sql);
				$resql = $db->query($sql);
				if ($resql) {
							if ($db->num_rows($resql) > 0) {
								$res = $db->fetch_array($resql);
					$pays_rowid	=	$res['rowid'];
							} else 	$pays_rowid	=	0;
				} else 		$pays_rowid	=	0;
				$newobject->country_id  = $pays_rowid;

						$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."c_departements WHERE code_departement LIKE '".$customer['state_iso']."'";
						$sql = "SELECT d.* FROM ".MAIN_DB_PREFIX."c_departements d "
							. " left join ".MAIN_DB_PREFIX."c_regions r ON (d.fk_region = r.code_region) "
							. " WHERE d.code_departement LIKE '".$customer['state_iso']."' and r.fk_pays=".$pays_rowid;
//						dol_syslog("CyberOffice_server_customer::etat sql=".$sql);
						$resql = $db->query($sql);
						if ($resql) {
							if ($db->num_rows($resql) > 0) {
								$res = $db->fetch_array($resql);
								$etat_rowid	=	$res['rowid'];
							} else 	$etat_rowid	=	0;
						} else 		$etat_rowid	=	0;
						$newobject->state_id      = $etat_rowid;

                        if ($conf->global->cu0032 && $indice=='02') {
							$newobject->array_options = array("options_btob" => 1);
						}

						if ($customer['invoice'] == 1)
						{
							$resultS_U=$newobject->update($societe_id,$user);
							dol_syslog("CyberOffice_server_customer::update".__LINE__);
						}

					}
					if ($resultS_U> 0) $list_ok.="<br/>Update Tiers : ".$customer['id_customer'].'->'.$societe_id . ' : ' .$newobject->name;
			}
			//if (!$customer['company']) {
			$newobject->lastname    = $customer['lastname'];
			$newobject->name        = $customer['lastname'];
			$newobject->name_bis    = $customer['lastname'];
			$newobject->socid       = $result;
			$newobject->id          = $result;
			$newobject->statut      = 1;
			$newobject->priv        = 0;//0 = public, 1 = private
				if (trim($customer['companyC']) != trim($customer['company'])) {
					$newobject->array_options["options_company"] = trim($customer['companyC']);
				}
			//$contact->state_id          = $this->state_id;
			//*****recherche si existe
			$sql = "SELECT rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."socpeople";
			$sql.= " WHERE import_key = 'P".$indice."-".$customer['id_address']."'";
//			dol_syslog("CyberOffice_server_customer::search contact sql=".$sql);
			if ((int)$customer['id_address']>0) $resqlp = $db->query($sql);
			if ($resqlp) {
					if ($db->num_rows($resqlp) > 0) {
				$res = $db->fetch_array($resqlp);
				$contact_id=$res['rowid'];
					} else $contact_id=0;
				} else $contact_id=0;
			$resultC = $contact_id;
			if ($contact_id==0 && (int)$customer['id_address']>0) {
					$socid_old=$newobject->socid;
					dol_syslog("CyberOffice_server_customer::individual ".__LINE__);
					$resultC = $newobject->create_individual($user);
					if ($resultC > 0) 
                        $list_ok.="<br/>Create Contact : ".$customer['id_address'].'->'.$resultC . ' : ' .$newobject->name;
			} elseif ($contact_id > 0) {
					dol_syslog("CyberOffice_server_customer::contact ".__LINE__);
					require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
					$contact=new Contact($db);
					$contact->fetch($contact_id);
					if($customer['lastname']) {
						$contact->lastname = $customer['lastname'];
					}
					if($customer['firstname']) {
						$contact->firstname = $customer['firstname'];
					}
					if($customer['address']) {
						$contact->address = $customer['address'];
					}
					if($customer['postcode']) {
						$contact->zip = $customer['postcode'];
					}
					if($customer['city']) {
						$contact->town = $customer['city'];
					}
					if($pays_rowid) {
						$contact->country_id = $pays_rowid;
					}
					if($etat_rowid) {
						$contact->state_id = $etat_rowid;
					}
					if($customer['phone']) $contact->phone = $customer['phone'];

					if($customer['phone_mobile']) $contact->phone_mobile = $customer['phone_mobile'];

					if($customer['email']) {
						$contact->email = $customer['email'];
					}
					if (trim($customer['companyC']) != trim($customer['company'])) {
						$contact->array_options["options_company"] = trim($customer['companyC']);
					}
					$contact->update($contact_id, $user);
					dol_syslog("CyberOffice_server_customer::updatecontact ".__LINE__);
			}
				$sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET";
			$sql.= " birthday=".(($customer['birthday'] && $customer['birthday']!='0000-00-00') ? "'".$customer['birthday']."'" : "null");
			$sql .= ", import_key='P".$indice."-".$customer['id_address']."'";
			$sql.= " WHERE rowid=".$resultC;
//			dol_syslog("server_customer::create - sql=".$sql);
				if ((int)$customer['id_address']>0) {
					$resql = $db->query($sql);
				}
			if ($result <= 0) {
					$db->rollback();
					$error++;
					$list_ok.= '<br/>Erreur : '.$customer['id_customer'];
					$list_ref.= ' '.$newobject->label ;
			} else {
					$db->commit();
				}
			}    
		}
			if (! $error)
			{
				$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'description'=>json_encode($list_ok));
			}
			else
			{
				$error++;
				$errorcode='KO';
				$errorlabel=$newobject->error;
			}

			if ($error)
			{
				$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel),'description'=>json_encode($list_ok));
			}
			//$db->close();
			return $objectresp;
	}							
}
$options = array('uri' => $_SERVER['SERVER_NAME']);
$server = new SoapServer(null, $options);
$server->setClass('DataServer');
$server->handle();
