<?php
/**
 *  MyCyberOffice
 *
 *  @author    LVSinformatique <contact@lvsinformatique.com>
 *  @copyright 2016 LVSInformatique
 *  @license   NoLicence
 *  This source file is subject to a commercial license from LVSInformatique
 *  Use, copy, modification or distribution of this source file without written
 *  license agreement from LVSInformatique is strictly forbidden.
 */
class InterfaceCust extends DolibarrTriggers
{
    var $db;
    var $location;

    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        $this->debug = 0;
        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "mycyberoffice";
        $this->description = "Triggers of this module allows to manage mycyberoffice workflow";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'mycyberoffice@mycyberoffice8';
    }


    /**
     *   Return name of trigger file
     *
     *   @return     string      Name of trigger file
     */
    function getName()
    {
        return $this->name;
    }

    /**
     *   Return description of trigger file
     *
     *   @return     string      Description of trigger file
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *
     *   @return     string      Version of trigger file
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("Development");
        elseif ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
     *
     *      @param	string		$action		Event action code
     *      @param  Object		$object     Object
     *      @param  User		$user       Object user
     *      @param  Translate	$langs      Object langs
     *      @param  conf		$conf       Object conf
     *      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
     */
    function runTrigger($action,$object,$user,$langs,$conf)
    {
        global $db, $conf, $user, $langs;
        if ($this->debug ==1) {
            ini_set('error_log', __DIR__ . '/../../log_error');
            ini_set('display_errors','1');
            error_reporting(E_ALL);
        }

        dol_syslog("interface_90_mycyberoffice8_Cust.class ".$action.__LINE__,6,0, '_cyber');
        $pos_nusoap = stripos($_SERVER['HTTP_USER_AGENT'], 'NuSOAP');
    	$pos_curl = stripos($_SERVER['HTTP_USER_AGENT'], 'CURL');
        $pos_soap = stripos($_SERVER['HTTP_USER_AGENT'], 'PHP-SOAP');
    	if(($pos_soap !== false || $pos_curl !== false || $pos_nusoap !== false) && $action != 'BILL_VALIDATE')
            {$action = "xxx";dol_syslog("Mycyber NOT executed by ".$action.'::'.$_SERVER['HTTP_USER_AGENT']);}
        if (substr($action, 0, 14) == 'ORDER_SUPPLIER')
            $action = 'ORDER_SUPPLIER';
		/*if($conf->global->CYBEROFFICE_OPALE && $action != 'STOCK_MOVEMENT') {$action = "xxx";}*/

        if ($action == 'xxPRODUCT_CREATExx') {
            $cats = array();
            $cats[] = array('id'    =>  $object->id,
                'ref'               =>	$object->ref,
		'label'             =>	(version_compare(DOL_VERSION, '3.8.0', '<')?$object->libelle:$object->label),
		'description'       =>	dol_trunc($object->description, 797),
		'longdescript'      =>  $object->array_options['options_longdescript'],
		'reel'              =>  'cybernull',
		//'cyberprice'        =>	$object->array_options['options_cyberprice'],
		'price'             =>	($price && $price > 0?$price:'cybernull'),
		//'tva_tx'            =>	$conf->global->{"MYCYBEROFFICE_tax".(float)$object->tva_tx},
		'status'            =>	$object->status,
		'import_key'        =>  $object->import_key,
		'ean13'             =>	($object->barcode_type==2?$object->barcode:'cybernull'),
		'upc'               =>	($object->barcode_type==3?$object->barcode:'cybernull'),
		'isbn'              =>	($object->barcode_type==4?$object->barcode:'cybernull'),
		'weight'            =>  $object->weight,
		'height'            =>  $object->height,//hauteur
        'width'             =>  $object->width,//largeur
        'length'            =>  $object->length,//longueur profondeur
        'cost_price' => $object->cost_price,
		'imageDel'          =>	'cybernull',
		'reel0'             =>  'cybernull',
            );

		//	if (substr($cats['0']['import_key'], -1, 1) != '-' && $cats['0']['import_key'] != '')
            $this->MyProduct($cats,$object, $action);//xxPRODUCT_CREATExx
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id.', importkey='.$cats['0']['import_key'].','.substr($cats['0']['import_key'], -1, 1));
        }
        elseif ($action == 'ORDER_VALIDATE' && $conf->global->MYCYBEROFFICE_stock_theorique == 1) {
            require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
            foreach($object->lines as $line)
            {
                if ($line->fk_product && $line->fk_product > 0) {
                    $Myobject = new Product($db);
                    $Myobject->fetch($line->fk_product);
                    $this->runTrigger('PRODUCT_MODIFY',$Myobject,$user,$langs,$conf);
                }
            }
        }
        elseif ($action == 'ADD_ORDER') {
            /*
            select ed.*, cd.* from expeditiondet ed
            left join commandedet cd on (ed.fk_origin_line = cd.rowid)

             */
            require_once DOL_DOCUMENT_ROOT.'/custom/mycyberoffice8/class/PSWebServiceLibrary.php';
            require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
            $myglobalkey = "MYCYBEROFFICE_key".GETPOST('ShopChoice');
            $dolibarrkey = htmlentities($conf->global->$myglobalkey, ENT_COMPAT, 'UTF-8');
            $myglobalshop = "CYBEROFFICE_SHOP".GETPOST('ShopChoice');
            $sql = "SELECT * FROM ".MAIN_DB_PREFIX."const WHERE name LIKE '".$myglobalshop."' ORDER BY name";
            $result = $this->db->query($sql);
            if ($result) {
                $obj = $this->db->fetch_object($result);
		if ($obj) {
                   $dolibarrshop = $obj->note;
                   $dolibarrvalue = $obj->value;
                }
            }
            try {
                include_once DOL_DOCUMENT_ROOT.'/custom/mycyberoffice8/config'.GETPOST('ShopChoice').'.inc.php';

                $webService = new PrestaShopWebservice($dolibarrshop, $dolibarrkey, $debug);
                $xml = $webService->get(array('url' => $dolibarrshop.'api/carts?schema=blank'));

                $customerId = 0;
                $searchcustomer = $webService->get(array(
                    'resource' => 'customers',
                    'filter' => array('email'=>$object->thirdparty->email),
                ));
                if (count($searchcustomer->customers->customer) == 1) {
                    //print $object->thirdparty->email. ' OK<br>';
                    $customerId = (int)$searchcustomer->customers->customer[0]->attributes()['id'][0];
                } else {
                    //print $object->thirdparty->email. ' NOK<br>';
                    require_once DOL_DOCUMENT_ROOT .'/core/lib/security2.lib.php';
                    $xmlCustomer = $webService->get(array('url' => $dolibarrshop.'api/customers?schema=blank'));
                    $password=getRandomPassword(false);
                    $name = dol_sanitizeFileName(dol_string_nospecial(trim($object->thirdparty->name),' '));
                    if ($debug==true) print '<br>'.$name.'<br>';
                    $xmlCustomer->customer->passwd    = $password;
                    $xmlCustomer->customer->lastname  = $name;
                    $xmlCustomer->customer->firstname = $name;
                    $xmlCustomer->customer->email     = $object->thirdparty->email;
                    $xmlCustomer->customer->active    = 1;
                    $opt = array('resource' => 'customers');
                    $opt['postXml'] = $xmlCustomer->asXML();
                    $xmlCustomer = $webService->add($opt);
                    $customerId = $xmlCustomer->customer->id;
                    $sql = 'UPDATE '.MAIN_DB_PREFIX.'societe SET import_key ="'.'P'.$dolibarrvalue.'-'.$customerId.'"'
                        . ' WHERE rowid ='.$object->thirdparty->id;
                    $result = $db->query($sql);
                }

                $xml->cart->id_customer = $customerId;
                $objectsrc = new Commande($db);
                $objectsrc->fetch($object->origin_id);
                $tab = $objectsrc->liste_contact(-1,'external');
                $num=count($tab);
                $i = 0;
                if ($debug==true) print $num. ' contact(s)<br>';
                while ($i < $num) {
                    $contactstatic=new Contact($db);
                    $contactstatic->fetch($tab[$i]['id']);
                    $countryId = 0;
                    $tmparray=getCountry($contactstatic->country_id,'all');
                    $searchcountries = $webService->get(array(
                        'resource' => 'countries',
                        'filter' => array('iso_code'=>$tmparray['code']),
                    ));
                    if (count($searchcountries->countries->country) == 1) {
                        $countryId = (int)$searchcountries->countries->country[0]->attributes()['id'][0];
                    }
                    $stateId = 0;
                    $searchstate = $webService->get(array(
                        'resource' => 'states',
                        'filter' => array('iso_code'=>$contactstatic->state_code,'id_country'=>$countryId),
                    ));
                    if (count($searchstate->states->state) == 1) {
                        $stateId = (int)$searchstate->states->state[0]->attributes()['id'][0];
                    }
                    if ($tab[$i]['fk_c_type_contact']==100) {
                        if ($debug==true)
                            print 'invoice contact100 '.'invoice'.$customerId.'<br>';
                        $searchinvoiceadr = $webService->get(array(
                            'resource' => 'addresses',
                            'filter' => array('alias'=>'invoice'.$customerId),
                        ));
                        if (count($searchinvoiceadr->addresses->address) > 0) {
                            //print count($searchinvoiceadr->addresses->address).' addresses<br>';
                            $addressId = (int)$searchinvoiceadr->addresses->address[0]->attributes()['id'][0];
                            $opt = array('resource' => 'addresses');
                            $opt['id'] = $addressId;
                            $xmlAd100 = $webService->get($opt);
                            $opt = array('resource' => 'addresses');
                            $opt['putXml'] = $xmlAd100->asXML();
                            $opt['id'] = $addressId;
                            $xmlAd100->address->id_customer = $customerId;
                            $xmlAd100->address->lastname  = $contactstatic->lastname;
                            $xmlAd100->address->firstname = $contactstatic->firstname;
                            $xmlAd100->address->alias = 'invoice'.$customerId;
                            $xmlAd100->address->id_country = $countryId;
                            $xmlAd100->address->address1 = $contactstatic->address;
                            $xmlAd100->address->city = $contactstatic->town;
                            $xmlAd100->address->postcode = $contactstatic->zip;
                            $xmlAd100->address->id_state = $stateId;
                            $xmlAd100 = $webService->edit($opt);
                            $ContactId100 = $addressId;
                        } else {
                            $xmlContact = $webService->get(array('url' => $dolibarrshop.'api/addresses?schema=blank'));
                            $xmlContact->address->id_customer = $customerId;
                            $xmlContact->address->lastname  = $contactstatic->lastname;
                            $xmlContact->address->firstname = $contactstatic->firstname;
                            $xmlContact->address->alias = 'invoice'.$customerId;
                            $xmlContact->address->id_country = $countryId;
                            $xmlContact->address->address1 = $contactstatic->address;
                            $xmlContact->address->city = $contactstatic->town;
                            $xmlContact->address->postcode = $contactstatic->zip;
                            $xmlContact->address->id_state = $stateId;
                            $opt = array('resource' => 'addresses');
                            $opt['postXml'] = $xmlContact->asXML();
                            $xmlContact = $webService->add($opt);
                            $ContactId100 = $xmlContact->address->id;
                            $sql = 'UPDATE '.MAIN_DB_PREFIX.'socpeople SET import_key ="'.'P'.$dolibarrvalue.'-'.$ContactId100.'"'
                            . ' WHERE rowid ='.$contactstatic->id;
                            $result = $db->query($sql);
                        }
                        $xml->cart->id_address_invoice = $ContactId100;
                    }
                    if ($tab[$i]['fk_c_type_contact']==102) {
                        if ($debug==true)
                            print 'delivery contact102 '.'delivery'.$customerId.'<br>';
                        $searchinvoiceadr = $webService->get(array(
                            'resource' => 'addresses',
                            'filter' => array('alias'=>'delivery'.$customerId),
                        ));
                        if (count($searchinvoiceadr->addresses->address) > 0) {
                            //print count($searchinvoiceadr->addresses->address).' addresses<br>';
                            $addressId = (int)$searchinvoiceadr->addresses->address[0]->attributes()['id'][0];
                            $opt = array('resource' => 'addresses');
                            $opt['id'] = $addressId;
                            $xmlAd102 = $webService->get($opt);
                            $opt = array('resource' => 'addresses');
                            $opt['putXml'] = $xmlAd102->asXML();
                            $opt['id'] = $addressId;
                            $xmlAd102->address->id_customer = $customerId;
                            $xmlAd102->address->lastname  = $contactstatic->lastname;
                            $xmlAd102->address->firstname = $contactstatic->firstname;
                            $xmlAd102->address->alias = 'delivery'.$customerId;
                            $xmlAd102->address->id_country = $countryId;
                            $xmlAd102->address->address1 = $contactstatic->address;
                            $xmlAd102->address->city = $contactstatic->town;
                            $xmlAd102->address->postcode = $contactstatic->zip;
                            $xmlAd102->address->id_state = $stateId;
                            $xmlAd102 = $webService->edit($opt);
                            $ContactId102 = $addressId;
			} else {
                            $xmlContact = $webService->get(array('url' => $dolibarrshop.'api/addresses?schema=blank'));
                            $xmlContact->address->id_customer = $customerId;
                            $xmlContact->address->lastname  = $contactstatic->lastname;
                            $xmlContact->address->firstname = $contactstatic->firstname;
                            $xmlContact->address->alias = 'delivery'.$customerId;
                            $xmlContact->address->id_country = $countryId;
                            $xmlContact->address->address1 = $contactstatic->address;
                            $xmlContact->address->city = $contactstatic->town;
                            $xmlContact->address->postcode = $contactstatic->zip;
                            $xmlContact->address->id_state = $stateId;
                            $opt = array('resource' => 'addresses');
                            $opt['postXml'] = $xmlContact->asXML();
                            $xmlContact = $webService->add($opt);
                            $ContactId102 = $xmlContact->address->id;
                            $sql = 'UPDATE '.MAIN_DB_PREFIX.'socpeople SET import_key ="'.'P'.$dolibarrvalue.'-'.$ContactId102.'"'
                                . ' WHERE rowid ='.$contactstatic->id;
                            $result = $db->query($sql);
                        }
                        $xml->cart->id_address_delivery = $ContactId102;
                    }
		    $i++;
                }
                if ($debug==true) {
                    print 'myadressedelivery '.$xml->cart->id_address_delivery .'='. $ContactId102.'<br>';
                    print 'myadresseinvoice '.$xml->cart->id_address_invoice .'='. $ContactId100.'<br>';
                }
                if ($xml->cart->id_address_delivery && $xml->cart->id_address_delivery>0 && empty($xml->cart->id_address_invoice))
                    $xml->cart->id_address_invoice = $xml->cart->id_address_delivery;
                if ($xml->cart->id_address_invoice && $xml->cart->id_address_invoice>0 && empty($xml->cart->id_address_delivery))
                    $xml->cart->id_address_delivery = $xml->cart->id_address_invoice;
                if ($i==0) {
                    $countryId = 0;
                    $tmparray=getCountry($object->thirdparty->country_id,'all');
                    $searchcountries = $webService->get(array(
                        'resource' => 'countries',
                        'filter' => array('iso_code'=>$tmparray['code']),
                    ));
                    if (count($searchcountries->countries->country) == 1) {
                        $countryId = (int)$searchcountries->countries->country[0]->attributes()['id'][0];
                    }
                    $stateId = 0;
                    $searchstate = $webService->get(array(
                        'resource' => 'states',
                        'filter' => array('iso_code'=>$object->thirdparty->state_code,'id_country'=>$countryId),
                    ));
                    if (count($searchstate->states->state) == 1) {
                        $stateId = (int)$searchstate->states->state[0]->attributes()['id'][0];
                    }
                        //print 'invoice contact100<br>';
                        $searchinvoiceadr = $webService->get(array(
                            'resource' => 'addresses',
                            'filter' => array('alias'=>'invoice'),
                        ));
                        if (count($searchinvoiceadr->addresses->address) > 0) {
                            //print count($searchinvoiceadr->addresses->address).' addresses<br>';
                            $addressId = (int)$searchinvoiceadr->addresses->address[0]->attributes()['id'][0];
                            $opt = array('resource' => 'addresses');
                            $opt['id'] = $addressId;
                            $xmlAd100 = $webService->get($opt);
                            $opt = array('resource' => 'addresses');
                            $opt['putXml'] = $xmlAd100->asXML();
                            $opt['id'] = $addressId;
                            $xmlAd100->address->id_customer = $customerId;
                            $xmlAd100->address->lastname  = $name;
                            $xmlAd100->address->firstname = $name;
                            $xmlAd100->address->alias = 'invoice';
                            $xmlAd100->address->id_country = $countryId;
                            $xmlAd100->address->address1 = $object->thirdparty->address;
                            $xmlAd100->address->city = $object->thirdparty->town;
                            $xmlAd100->address->postcode = $object->thirdparty->zip;
                            $xmlAd100->address->id_state = $stateId;
                            $xmlAd100 = $webService->edit($opt);
                            $ContactId100 = $addressId;
                        } else {
                            $xmlContact = $webService->get(array('url' => $dolibarrshop.'api/addresses?schema=blank'));
                            $xmlContact->address->id_customer = $customerId;
                            $xmlContact->address->lastname  = $name;
                            $xmlContact->address->firstname = $name;
                            $xmlContact->address->alias = 'invoice';
                            $xmlContact->address->id_country = $countryId;
                            $xmlContact->address->address1 = $object->thirdparty->address;
                            $xmlContact->address->city = $object->thirdparty->town;
                            $xmlContact->address->postcode = $object->thirdparty->zip;
                            $xmlContact->address->id_state = $stateId;
                            $opt = array('resource' => 'addresses');
                            $opt['postXml'] = $xmlContact->asXML();
                            $xmlContact = $webService->add($opt);
                            $ContactId100 = $xmlContact->address->id;
                        }
                        $xml->cart->id_address_invoice = $ContactId100;

                        //print 'delivery contact102<br>';
                        $searchinvoiceadr = $webService->get(array(
                            'resource' => 'addresses',
                            'filter' => array('alias'=>'delivery'),
                        ));
                        if (count($searchinvoiceadr->addresses->address) > 0) {
                            //print count($searchinvoiceadr->addresses->address).' addresses<br>';
                            $addressId = (int)$searchinvoiceadr->addresses->address[0]->attributes()['id'][0];
                            $opt = array('resource' => 'addresses');
                            $opt['id'] = $addressId;
                            $xmlAd102 = $webService->get($opt);
                            $opt = array('resource' => 'addresses');
                            $opt['putXml'] = $xmlAd102->asXML();
                            $opt['id'] = $addressId;
                            $xmlAd102->address->id_customer = $customerId;
                            $xmlAd102->address->lastname  = $name;
                            $xmlAd102->address->firstname = $name;
                            $xmlAd102->address->alias = 'delivery';
                            $xmlAd102->address->id_country = $countryId;
                            $xmlAd102->address->address1 = $object->thirdparty->address;
                            $xmlAd102->address->city = $object->thirdparty->town;
                            $xmlAd102->address->postcode = $object->thirdparty->zip;
                            $xmlAd102->address->id_state = $stateId;
                            $xmlAd102 = $webService->edit($opt);
                            $ContactId102 = $addressId;
			} else {
                            $xmlContact = $webService->get(array('url' => $dolibarrshop.'api/addresses?schema=blank'));
                            $xmlContact->address->id_customer = $customerId;
                            $xmlContact->address->lastname  = $name;
                            $xmlContact->address->firstname = $name;
                            $xmlContact->address->alias = 'delivery';
                            $xmlContact->address->id_country = $countryId;
                            $xmlContact->address->address1 = $object->thirdparty->address;
                            $xmlContact->address->city = $object->thirdparty->town;
                            $xmlContact->address->postcode = $object->thirdparty->zip;
                            $xmlContact->address->id_state = $stateId;
                            $opt = array('resource' => 'addresses');
                            $opt['postXml'] = $xmlContact->asXML();
                            $xmlContact = $webService->add($opt);
                            $ContactId102 = $xmlContact->address->id;
                        }
                        $xml->cart->id_address_delivery = $ContactId102;

                }
                if ($debug==true) print 'invoice '.$xml->cart->id_address_invoice.'<br>delivery '.$xml->cart->id_address_delivery.'<br>';

                $searchCurrencies = $webService->get(array(
                    'resource' => 'currencies',
                    'filter' => array('iso_code'=>$object->multicurrency_code),
                ));
                if (count($searchCurrencies->currencies->currency) == 1)
                    $CurrenciesId = (int)$searchCurrencies->currencies->currency[0]->attributes()['id'][0];
                else
                    $CurrenciesId = $default_currency;
                //print 'currency '.$CurrenciesId.'<br>';

                $xml->cart->id_currency = $CurrenciesId;
                $xml->cart->id_carrier = $defaultid_carrier;
                $xml->cart->id_lang = $defaultid_lang;
                $productIds = array();
                $shipping_price=0;
                $shipping_tva=0;

                foreach ($objectsrc->lines as $myline) {
                    if ($debug == true) print 'product id='.$myline->fk_product .'::'. $id_shipping.'<br>';
                    if ($myline->fk_product == $id_shipping) {
                        //print 'shipping '.$id_shipping.'<br>';
                        $shipping_price = $myline->subprice * $myline->qty;
                        $shipping_tva = $myline->tva_tx;
                        if ($debug == true) print 'shipping '.$id_shipping.':'.$myline->subprice.'*'.$myline->qty.':'.$myline->tva_tx.'<br>';
                        continue;
                    }
                    $sql = "SELECT import_key FROM ".MAIN_DB_PREFIX."product WHERE rowid=".$myline->fk_product;
                    //print 'search produit '.$myline->fk_product.'<br>';
                    $resql = $db->query($sql);
                    if ($resql) {
                        $res = $db->fetch_object($resql);
                        $ids = explode("-", $res->import_key);
                        $productIds[] = [
                            'id_product' => $ids[1],
                            'id_product_attribute' => (isset($ids[2])?$ids[2]:0),
                            'qty' => $myline->qty,
                            'product_price' => $myline->subprice,
                            'tva_tx' => $myline->tva_tx,
                        ];
                        //print 'find produit '.$myline->fk_product.' '.$res->import_key.'<br>';
                    }
                }

                unset($xml->cart->associations->cart_rows->children()[0]);
                foreach ($productIds as $productId) {
                    if ( $productId['id_product'] == 0 || $productId['id_product'] =="")
                        continue;
                    $child = $xml->cart->associations->cart_rows->addChild('cart_row');
                    $child->id_product = $productId['id_product'];
                    $child->id_product_attribute = $productId['id_product_attribute'];
                    $child->quantity = $productId['qty'];
                    $child->id_address_delivery = $xml->cart->id_address_delivery;
                    //print 'traitement produit '.$productId['id_product'].'<br>';
                }
                //print 'prparation carts<br>';
                $opt = array('resource' => 'carts');
                $opt['postXml'] = $xml->asXML();
                /*print '<pre>';print_r($xml);print '</pre>';*/
                $xml = $webService->add($opt);
                //print 'add carts '.$xml->cart->id.'<br>';

                //Create order
                $xmlOrder = $webService->get(array('url' => $dolibarrshop.'api/orders?schema=blank'));
                /*print '<pre>';print_r($xmlOrder);print '</pre>';*/
                $xmlOrder->order->valid = 1;
                $xmlOrder->order->module = $default_module;
                $xmlOrder->order->payment = $default_payment;
                $xmlOrder->order->id_address_delivery = $xml->cart->id_address_delivery;
                $xmlOrder->order->id_address_invoice = $xml->cart->id_address_invoice;
                $xmlOrder->order->id_cart = $xml->cart->id;
                $xmlOrder->order->id_customer = $xml->cart->id_customer;
                $xmlOrder->order->id_carrier = $xml->cart->id_carrier;
                $xmlOrder->order->id_currency = $xml->cart->id_currency;
                $xmlOrder->order->id_shop = $xml->cart->id_shop;
                $xmlOrder->order->id_shop_group = $xml->cart->id_shop_group;
                $xmlOrder->order->id_lang = $xml->cart->id_lang;
                $xmlOrder->order->total_shipping = $shipping_price * (1 + ($shipping_tva / 100));
                $xmlOrder->order->total_shipping_tax_incl = $shipping_price * (1 + ($shipping_tva / 100));
                $xmlOrder->order->total_shipping_tax_excl = $shipping_price;
                $orderTotal = 0;
                $orderTotalWt = 0;
                unset($xmlOrder->order->associations->order_rows->children()[0]);
                foreach ($productIds as $line) {
                    if ( $line['id_product'] == 0 || $line['id_product'] =="")
                        continue;
                    $orderLine = $xmlOrder->order->associations->order_rows->addChild('order_row');
                    $orderLine->product_id = $line['id_product'];
                    $orderLine->product_attribute_id = $line['id_product_attribute'];
                    $orderLine->product_quantity = $line['qty'];
                    $orderLine->product_price = $line['product_price'];
                    $orderLine->unit_price_tax_incl = $line['product_price']* (1 + ($line['tva_tx']) /100);
                    $orderLine->unit_price_tax_excl = $line['product_price'];
                    $xmlProduct = $webService->get(array('url' => $dolibarrshop . 'api/products/'.$line['id_product']));
                    $orderLine->product_name = $xmlProduct->product->name;
                    $orderLine->product_reference = $xmlProduct->product->reference;
                    $orderLine->id_customization = 0;
                    $orderTotal += ( (float)$orderLine->product_price * (int)$orderLine->product_quantity );
                    $orderTotalWt += ( (float)$orderLine->product_price * (int)$orderLine->product_quantity ) * (1 + ($line['tva_tx']) /100);
                    //print 'order line '.$orderLine->product_id.'<br>';
                }
                $xmlOrder->order->total_paid_tax_incl = $orderTotalWt + $xmlOrder->order->total_shipping_tax_incl;
                $xmlOrder->order->total_paid_tax_excl = $orderTotal + $xmlOrder->order->total_shipping_tax_excl;
                $xmlOrder->order->total_paid_real = $orderTotalWt + $xmlOrder->order->total_shipping_tax_incl;
                $xmlOrder->order->total_paid = $orderTotalWt + $xmlOrder->order->total_shipping_tax_incl;
                $xmlOrder->order->total_products = $orderTotal;
                $xmlOrder->order->total_products_wt = $orderTotalWt;
                $xmlOrder->order->conversion_rate = 1;
                $opt = array('resource' => 'orders');
                $opt['postXml'] = $xmlOrder->asXML();
                /*print '<pre>';print_r($xmlOrder);print '</pre>';*/
                //print 'prepare order '.$xml->cart->id.'<br>';
                $xmlOrder = $webService->add($opt);
                //print 'add order '.$xmlOrder->order->id.'<br>';

                $opt = array('resource' => 'orders');
                $opt['id'] = $xmlOrder->order->id;
                $xml = $webService->get($opt);
                /*print '<pre>';print_r($xml);print '</pre>';*/
                $xml->children()->children()->current_state=$current_state;
                $xml->children()->children()->invoice_date = date('Y-m-d H:i:s',$objectsrc->date_validation);
                $xml->children()->children()->date_add = date('Y-m-d H:i:s',$objectsrc->date_validation);
                $xml->children()->children()->date_upd = date('Y-m-d H:i:s',$objectsrc->date_validation);
                $xml->order->total_shipping = $shipping_price * (1 + ($shipping_tva / 100));
                $xml->order->total_shipping_tax_incl = $shipping_price * (1 + ($shipping_tva / 100));
                $xml->order->total_shipping_tax_excl = $shipping_price;
                $xml->order->total_paid_tax_incl = $orderTotalWt + $xml->order->total_shipping_tax_incl;
                $xml->order->total_paid_tax_excl = $orderTotal + $xml->order->total_shipping_tax_excl;
                $xml->order->total_paid_real = $xml->order->total_paid_tax_incl;
                $xml->order->total_paid = $xml->order->total_paid_tax_incl;
                $xml->order->total_products = $orderTotal;
                $xml->order->total_products_wt = $orderTotalWt;
                /*print '<pre>';print_r($xml);print '</pre>';*/
                $opt = array('resource' => 'orders');
                $opt['putXml'] = $xml->asXML();
                $opt['id'] = $xmlOrder->order->id;
                $xml = $webService->edit($opt);
                //print 'edit orders<br>';

                $opt = array('resource' => 'order_details');
                //$opt['id_order'] = $xmlOrder->order->id;
                $opt['id'] = $xml->children()->children()->associations->order_rows->children()[0]->id;
                $xmlorder_details = $webService->get($opt);
                /*print '<pre>';print_r($xmlorder_details);print '</pre>';*/

                $xmlorder_details->children()->children()->product_price=$productIds[0]['product_price'];
                $xmlorder_details->children()->children()->unit_price_tax_incl=$productIds[0]['product_price']*(1 + ($line['tva_tx'] /100));
                $xmlorder_details->children()->children()->unit_price_tax_excl=$productIds[0]['product_price'];
                $xmlorder_details->children()->children()->product_quantity=$productIds[0]['qty'];
                $xmlorder_details->children()->children()->total_price_tax_incl = $productIds[0]['qty'] * $productIds[0]['product_price']*(1 + ($line['tva_tx'] /100));
                $xmlorder_details->children()->children()->total_price_tax_excl = $productIds[0]['qty'] *$productIds[0]['product_price'];
                $opt = array('resource' => 'order_details');
                $opt['putXml'] = $xmlorder_details->asXML();
                $opt['id'] = $xml->children()->children()->associations->order_rows->children()[0]->id;
                $xmlorder_details = $webService->edit($opt);
                //print 'edit order_details<br>';

                $xmlorder_carriers = $webService->get(array('url' => $dolibarrshop.'api/order_carriers?schema=blank'));
                $xmlorder_carriers->order_carrier->id_order = $xmlOrder->order->id;
                $xmlorder_carriers->order_carrier->id_carrier = $defaultid_carrier;
                $xmlorder_carriers->order_carrier->shipping_cost_tax_excl = $shipping_price;
                $xmlorder_carriers->order_carrier->shipping_cost_tax_incl = $shipping_price * (1 + ($shipping_tva / 100));
                /*$opt = array('resource' => 'order_carriers');
                $opt['putXml'] = $xmlorder_carriers->asXML();
                $opt['id'] = $xmlOrder->order->id;*/
                $opt = array('resource' => 'order_carriers');
                $opt['postXml'] = $xmlorder_carriers->asXML();
                $xmlorder_carriers = $webService->add($opt);

                //fin
                $sql = 'UPDATE '.MAIN_DB_PREFIX.'expedition SET import_key ="'.'P'.$dolibarrvalue.'-'.$xmlOrder->order->id.'"'
                    . ', ref_customer="'.$xml->order->id_shop.':'.$xmlOrder->order->id.'/'.$xml->order->id_cart.'"'
                    . ', note_private="'.$db->escape('order n° '.$xml->order->reference).'"'
		    . ' WHERE rowid ='.$object->id;
                $result = $db->query($sql);
                //print $sql;
            } catch (PrestaShopWebserviceException $e) {
                $trace = $e->getTrace();
                $errorCode = $trace[0]['args'][0];
                echo $errorCode.'::'.$e->getMessage().' <br> ';
                echo $e->getFile().' Line '.$e->getLine().' <br> ';
                return -1;
            }
        }
        elseif ($action == 'xxPRICELIST_DELETExx') {
            require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
            $Myobject = new Product($db);
            $Myobject->fetch($object->product_id);
            $pricelist_array = array(
                'id' => $object->id,
                'product_id' => $object->product_id,
                'socid' => $object->socid,
                'from_qty' => $object->from_qty,
                'price' => $object->price
            );
            //$this->MyProduct($pricelist_array, $Myobject, $action);
        }
        elseif ($action == 'PRODUCT_MODIFY' || $action == 'PRICELIST_CREATE' || $action == 'PRODUCT_SET_MULTILANGS' || $action == 'PRICELIST_DELETE') {
            if (isset($conf->legmod) && ($conf->legmod || $conf->legmod->enabled) && $action == 'PRODUCT_SET_MULTILANGS') {
                return 1;
            }
            if ($action == 'PRICELIST_CREATE' || $action == 'PRICELIST_DELETE') {
                $myId = $object->product_id;
                $object = new Product($db);
                $object->fetch($myId);
            }
            $price=array();
            $tva_tx=array();
            //$Myobject = new Product($db);
            //$object->fetch($object->id);
            if (empty($conf->global->PRODUIT_MULTIPRICES) || $conf->global->PRODUIT_MULTIPRICES == 0) {
                $price[] = $object->price;
                //$tva_tx[] = (!empty($conf->global->{'MYCYBEROFFICE_tax' . (float) $object->tva_tx}) ? $conf->global->{'MYCYBEROFFICE_tax' . (float) $object->tva_tx} : 0);
            } else {
                for ($i = 1; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i ++)
                {
                    $price[] = $object->multiprices[$i];
                    //$tva_tx[] = $conf->global->{"MYCYBEROFFICE_tax".(float)$object->multiprices_tva_tx[$i]};
                }
            }

            $features = [];
            $resql = false;
            foreach($object->array_options as $kfeature => $feature) {
                $sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cyberoffice c WHERE c.active=1 AND c.extrafield='".substr($kfeature,8)."'";
                if ($kfeature != 'options_longdescript') {
                    $resql = $this->db->query($sql);
                }
                if ($resql) {
                    while ($res = $this->db->fetch_object($resql))
                    {
                        $res_extrafield=$res->idpresta;
                        if ($res_extrafield > 0) {
                            $features[$res_extrafield]['id_feature'] = $res_extrafield;
                            $features[$res_extrafield]['id_feature_value'] = $feature;
                            $features[$res_extrafield]['feature_value_lang'] = $feature;
                        }
                    }
                }
            }

            $object->load_stock();
            $stock = 0;
            $stock0 = 0;
            //$warehouse = $conf->global->MYCYBEROFFICE_warehouse;
            foreach ($object->stock_warehouse as $key => $value) {
                $stock_real_theorique = ($conf->global->MYCYBEROFFICE_stock_theorique == 1?$value->stock_theorique:$value->real);
                $stock += $stock_real_theorique;
                $stock0 += $stock_real_theorique;
            }
            $object->entrepot_id = 0;
            $object->product_id = $object->id;

            $cats = array();
            $cats[] = array('id'    =>	$object->id,
				'ref'               =>	$object->ref,
				'label'             =>	$object->label,
				'description'       =>	dol_trunc($object->description, 797),
				'longdescript'      =>	(isset($object->array_options['options_longdescript'])?$object->array_options['options_longdescript']:''),
				'reel'              =>  $stock,
                //'cyberprice' => isset($object->array_options['options_cyberprice']) ? $object->array_options['options_cyberprice'] : null,
				'price'             =>	$price,
				'tva_tx'            =>	$tva_tx,
				'status'            =>	$object->status,
				'import_key'        =>  $object->import_key,
				'ean13'             =>	($object->barcode_type==2?$object->barcode:'cybernull'),
				'upc'               =>	($object->barcode_type==3?$object->barcode:'cybernull'),
				'isbn'              =>	($object->barcode_type==4?$object->barcode:'cybernull'),
				'weight'            =>  $object->weight,
				'height'            =>  $object->height,//hauteur
				'width'             =>  $object->width,//largeur
				'length'            =>  $object->length,//longueur profondeur
                'cost_price' => $object->cost_price,
				'imageDel'          =>	'cybernull',
				'reel0'             =>  $stock0,
				'features'          =>  $features
            );
            /*print '<pre>';print_r($cats);print '</pre>';*/
            $sql = "SELECT c.import_key
                FROM " . MAIN_DB_PREFIX . "categorie_product as ct, " . MAIN_DB_PREFIX . "categorie as c
                WHERE ct.fk_categorie = c.rowid AND ct.fk_product = " . $object->id . " AND c.type = 0
                AND c.entity IN (" . getEntity( 'category', 1 ) . ") AND c.import_key is not null";
            $res = $this->db->query($sql);
            if ($res) {
                while ($obj = $this->db->fetch_object($res))
                {
                    $cats[0]['default_cat']=substr($obj->import_key,4);
					$cats[0]['cat']=$obj->import_key;
                    //$this->MyProduct($cats,$object, $action, -1);//PRODUCT_MODIFY
                }
            }
            $this->MyProduct($cats,$object, $action, -1);//PRODUCT_MODIFY
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id.', importkey='.$cats['0']['import_key']);

        } elseif ($action == 'PRODUCT_PRICE_MODIFY') {
            dol_syslog("interface_90_mycyberoffice8_Cust.class ".$action.__LINE__.'-'.$object->level,6,0, '_cyber');
            /*
             * if (empty($conf->global->PRODUIT_MULTIPRICES) || $conf->global->PRODUIT_MULTIPRICES == 0)
                    $pricelevel = 0;
                else
                    $pricelevel = (int)$conf->global->{"MYCYBEROFFICE_pricelevel".substr($obj->name,-2)} - 1;
             */
            $price=array();
            $tva_tx=array();
            if (empty($conf->global->PRODUIT_MULTIPRICES) || $conf->global->PRODUIT_MULTIPRICES == 0) {
                $price[] = $object->price;
				//$tva_tx[] = $conf->global->{"MYCYBEROFFICE_tax".(float)$object->tva_tx};
			} else {
                for ($i = 1; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i ++)
                {
                    $price[] = $object->multiprices[$i];
                    //$tva_tx[] = $conf->global->{"MYCYBEROFFICE_tax".(float)$object->multiprices_tva_tx[$i]};
                }
            }
         	//print_r($_SERVER);
            $cats = array();
            $cats[] = array('id'    =>	$object->id,
            	'ref'               =>	$object->ref,
				'label'             =>	$object->label,
				'description'       =>	dol_trunc($object->description, 797),
				'longdescript'      =>	$object->array_options['options_longdescript'],
				'reel'              =>  'cybernull',
				//'cyberprice'        =>	'cybernull',
				'price'             =>	$price,
				'tva_tx'            =>	$tva_tx,
				'status'            =>	'cybernull',
				'import_key'        =>  $object->import_key,
				'ean13'             =>	($object->barcode_type==2?$object->barcode:'cybernull'),
				'upc'               =>	($object->barcode_type==3?$object->barcode:'cybernull'),
				'isbn'              =>	($object->barcode_type==4?$object->barcode:'cybernull'),
				'weight'            =>  $object->weight,
				'height'            =>  $object->height,//hauteur
				'width'             =>  $object->width,//largeur
				'length'            =>  $object->length,//longueur profondeur
                'cost_price' => $object->cost_price,
				'imageDel'          =>	'cybernull',
				'reel0'             =>  'cybernull',
            );

			//if (substr($cats['0']['import_key'], -1, 1) != '-' && $cats['0']['import_key'] != '' )
            dol_syslog("interface_90_mycyberoffice8_Cust.class ".$action.__LINE__,6,0, '_cyber');
            $this->MyProduct($cats,$object, $action, 0, $object->level);//PRODUCT_PRICE_MODIFY

			/***** A FAIRE :: mise à jour de tous les taux de tva sur decliansion
			updatePrice($newprice, $newpricebase, $user, $newvat='',$newminprice='', $level=0, $newnpr=0, $newpsq=0)
			$object->price, $object->price_base_type,$user,$object->tva_tx)

				$count = 0;
				$count = substr_count($object->import_key, '-');
				if($count == 2) {
					$nbr = 0;
					$nbr = strrpos($object->import_key, '-');
					if ($nbr === false) $nbr=0;
					$product_search = substr($object->import_key, 0, $nbr + 1);
					$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'product
						WHERE import_key LIKE "'.$product_search.'%"';
					dol_syslog("MyCyberoffice ".$action." sql = ".$sql);
					$resql = $db->query($sql);
					if ($resql) {
						while ( $product = $db->fetch_object($resql) )
						{
							print_r($product);
							print '<br/>';exit;
						}
					}
				}
		*/
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id.' price '.$object->price.', importkey='.$cats['0']['import_key']);

        } elseif ($action == 'ORDER_SUPPLIER') {
            require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
            foreach($object->lines as $line)
            {
                if ($line->fk_product && $line->fk_product > 0) {
                    $Myobject = new Product($db);
                    $Myobject->fetch($line->fk_product);
                    $this->runTrigger('PRODUCT_MODIFY',$Myobject,$user,$langs,$conf);
                }
            }
        } elseif ($action == 'STOCK_MOVEMENT') {
        	//if ($object->entrepot_id == $conf->global->MYCYBEROFFICE_warehouse || $conf->global->MYCYBEROFFICE_warehouse == 0) {
            $cats = array();
	        //if ($conf->global->MYCYBEROFFICE_warehouse != 0)
	        //{
            /*
            $sql = 'SELECT p.*, ps.reel, ps.fk_entrepot
                FROM '.MAIN_DB_PREFIX.'product p
                LEFT JOIN '.MAIN_DB_PREFIX.'product_stock as ps ON (ps.fk_product = p.rowid and ps.fk_entrepot = '.$object->entrepot_id.')
		WHERE p.entity IN (0, '.$conf->entity.') AND p.rowid='.$object->product_id.' ORDER BY p.rowid ';//1322
            $res = $this->db->query($sql);
            $produits = $this->db->fetch_array($res);
	        //}

            $sql = 'SELECT p.*, SUM(ps.reel) as reel
                FROM '.MAIN_DB_PREFIX.'product p
		LEFT JOIN '.MAIN_DB_PREFIX.'product_stock as ps ON (ps.fk_product = p.rowid)
		WHERE p.entity IN (0, '.$conf->entity.') AND p.rowid='.$object->product_id.' GROUP BY p.rowid ';//1322
            $res = $this->db->query($sql);
            $produits0 = $this->db->fetch_array($res);
            */
            require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
            $Myobject = new Product($db);
            $Myobject->fetch($object->product_id);
            $Myobject->load_stock();
            $stock = 0;
            $stock0 = 0;
            foreach ($Myobject->stock_warehouse as $key => $value) {
                $stock_real_theorique = ($conf->global->MYCYBEROFFICE_stock_theorique == 1?$value->stock_theorique:$value->real);
                if ($key == $object->entrepot_id)
                    $stock += $stock_real_theorique;
                $stock0 += $stock_real_theorique;
            }

            $cats = [];
            $cats[] = ['id'    =>	$object->product_id,
				'ref'               =>	$Myobject->ref,
				'label'             =>	(version_compare(DOL_VERSION, '3.8.0', '<')?$Myobject->libelle:$Myobject->label),
				'description'       =>	dol_trunc($Myobject->description, 797),
				'longdescript'      =>	$Myobject->array_options['options_longdescript'],
				'reel'              =>  $stock,
				//'cyberprice'        =>	'cybernull',
				'price'             =>	'cybernull',
                //'tva_tx' => isset($conf->global->{'MYCYBEROFFICE_tax' . (float) $Myobject->tva_tx}) ? $conf->global->{'MYCYBEROFFICE_tax' . (float) $Myobject->tva_tx} : 0,
				'status'            =>	'cybernull',
				'import_key'        =>  $Myobject->import_key,
				'ean13'             =>	($Myobject->barcode_type==2?$Myobject->barcode:'cybernull'),
				'upc'               =>	($Myobject->barcode_type==3?$Myobject->barcode:'cybernull'),
				'isbn'              =>	($Myobject->barcode_type==4?$Myobject->barcode:'cybernull'),
                'cost_price' => $Myobject->cost_price,
				'imageDel'          =>	'cybernull',
				'reel0'             =>  $stock0,
            ];

            $this->MyProduct($cats,$object, $action, $object->entrepot_id);//STOCK_MOVEMENT
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->product_id.' qty='.$stock.'/'.$stock0);
	        //}
		} elseif ($action == 'PICTURE_CREATE') {
            if (isset($object->mycyber) && is_array($object->mycyber) && count($object->mycyber) > 0) {
                $cats = array();
				$cats[] = array('id'    =>  $object->id,
                    'ref'		=>  $object->ref,
                    'label'     =>	(version_compare(DOL_VERSION, '3.8.0', '<')?$object->libelle:$object->label),
                    'reel'		=>  'cybernull',
                    'image'		=>  $object->mycyber,
                    //'cyberprice'	=>  'cybernull',
                    'price'		=>  'cybernull',
                    'status'		=>  'cybernull',
                    'ean13'		=>  ($object->barcode_type==2?$object->barcode:'cybernull'),
                    'upc'		=>  ($object->barcode_type==3?$object->barcode:'cybernull'),
                    'isbn'		=>  ($object->barcode_type==4?$object->barcode:'cybernull'),
                    'imageDel'		=>  'cybernull',
                    'import_key'	=>  $object->import_key,
                    'action'		=>  $action,
                    'reel0'		=>  'cybernull',
                );

				$this->MyProduct($cats,$object, $action);//PICTURE_CREATE
            }
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
		} elseif ($action == 'PICTURE_DELETE') {
            $cats = array();
            $cats[] = array('id'    =>	$object->id,
				'ref'               =>	$object->ref,
				'label'             =>	(version_compare(DOL_VERSION, '3.8.0', '<')?$object->libelle:$object->label),
				'reel'              =>	'cybernull',
				'image'             =>	$object->mycyber,
				//'cyberprice'        =>	'cybernull',
				'price'             =>	'cybernull',
				'status'            =>	'cybernull',
				'ean13'             =>	($object->barcode_type==2?$object->barcode:'cybernull'),
				'upc'               =>	($object->barcode_type==3?$object->barcode:'cybernull'),
				'isbn'              =>	($object->barcode_type==4?$object->barcode:'cybernull'),
				'imageDel'          =>	$object->mycyberDel,
				'import_key'        =>  $object->import_key,
				'action'            => $action,
				'reel0'             =>  'cybernull',
            );

            $this->MyProduct($cats,$object, $action);//PICTURE_DELETE
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
		} elseif ($action == 'CATEGORY_LINK' || ($action == 'CATEGORY_MODIFY' && $object->context['linkto'] && $object->context['linkto']->element == 'product')) {
            $sql = "SELECT rowid, import_key";
            $sql.= " FROM ".MAIN_DB_PREFIX."categorie";
            $sql.= " WHERE rowid = '".$object->id."'";
            $resql = $this->db->query($sql);
            if ($resql) {
                $res = $this->db->fetch_array($resql);
				$import_key = $res['import_key'];
            }

                $mycontext = $object->context['linkto'];
            dol_syslog("Mycyberoffice CATEGORY_LINK context=".$mycontext->import_key.'/'.$mycontext->id,6,0, '_cyber');
            if (!$mycontext->import_key) {
                $newobject = new Product($this->db);
				$newobject->fetch($mycontext->id);

				$newimport_key = $newobject->import_key;
				$price=array();
				$tva_tx=array();
				//print __LINE__.$newobject->rowid.$newobject->price;
				if (empty($conf->global->PRODUIT_MULTIPRICES) || $conf->global->PRODUIT_MULTIPRICES == 0) {
					$price[] = $newobject->price;
					//$tva_tx[] = $conf->global->{"MYCYBEROFFICE_tax".(float)$newobject->tva_tx};//php8
				} else {
					for ($i = 1; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i ++)
					{
						$price[] = $newobject->multiprices[$i];
						//$tva_tx[] = $conf->global->{"MYCYBEROFFICE_tax".(float)$newobject->multiprices_tva_tx[$i]};
					}
				}
				$features = array();
				foreach($newobject->array_options as $kfeature => $feature) {
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cyberoffice c WHERE c.active=1 AND c.extrafield='".substr($kfeature,8)."'";
					if ($kfeature != 'options_longdescript')
						$resql = $this->db->query($sql);
					if ($resql) {
						while ($res = $this->db->fetch_object($resql))
						{
							$res_extrafield=$res->idpresta;
							if ($res_extrafield > 0) {
								$features[$res_extrafield]['id_feature'] = $res_extrafield;
								$features[$res_extrafield]['id_feature_value'] = $feature;
								$features[$res_extrafield]['feature_value_lang'] = $feature;
							}
						}
					}
				}

				$newobject->load_stock();
				$stock = 0;
				$stock0 = 0;
				//$warehouse = $conf->global->MYCYBEROFFICE_warehouse;
				foreach ($newobject->stock_warehouse as $key => $value) {
					$stock_real_theorique = ($conf->global->MYCYBEROFFICE_stock_theorique == 1?$value->stock_theorique:$value->real);
					/*if ($key == $conf->global->MYCYBEROFFICE_warehouse || $conf->global->MYCYBEROFFICE_warehouse == 0)
                        $stock += $stock_real_theorique;*/
					$stock0 += $stock_real_theorique;
				}

				$cats = array();
				$cats[] = array('id'    =>  $newobject->id,
					'import_key'        =>  $newobject->import_key,
					'ref'		=>  $newobject->ref,
					'label'		=>  (version_compare(DOL_VERSION, '3.8.0', '<')?$newobject->libelle:$newobject->label),
					'description'	=>  dol_trunc($newobject->description,800),
					'longdescript'	=>  $newobject->array_options['options_longdescript'],
					'reel'		=>  $stock,
					//'cyberprice'	=>  $newobject->array_options['options_cyberprice'],//php8
					'price'		=>  $price,//($price && $price > 0?$price:'cybernull'),
					'tva_tx'		=>  $tva_tx,
					'status'		=>  $newobject->status,
					'ean13'		=>  ($newobject->barcode_type==2?$newobject->barcode:'cybernull'),
					'upc'		=>  ($newobject->barcode_type==3?$newobject->barcode:'cybernull'),
					'isbn'		=>  ($newobject->barcode_type==4?$newobject->barcode:'cybernull'),
					'weight'		=>  $newobject->weight,
					'height'            =>  $newobject->height,//hauteur
					'width'             =>  $newobject->width,//largeur
					'length'            =>  $newobject->length,//longueur profondeur
                    'cost_price' => $newobject->cost_price,
					'imageDel'		=>  'cybernull',
					'cat'		=>  $import_key,
					'action'		=>  'add',
					'reel0'		=>  $stock0,
					'features'          =>  $features
				);
            } else {
                $newobject = new Product($this->db);
				$newobject->fetch($mycontext->id);
				$newimport_key = $mycontext->import_key;
				$price=array();
                $tva_tx=array();
                //print __LINE__.$newobject->rowid.$newobject->rowid.$object->price;
                if (empty($conf->global->PRODUIT_MULTIPRICES) || $conf->global->PRODUIT_MULTIPRICES == 0) {
                    $price[] = $newobject->price;
                    //$tva_tx[] = $conf->global->{"MYCYBEROFFICE_tax".(float)$newobject->tva_tx};
                } else {
                    for ($i = 1; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i ++)
                    {
                        $price[] = $newobject->multiprices[$i];
                        //$tva_tx[] = $conf->global->{"MYCYBEROFFICE_tax".(float)$newobject->multiprices_tva_tx[$i]};
                    }
                }
                $features = array();
                foreach($newobject->array_options as $kfeature => $feature) {
                    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cyberoffice c WHERE c.active=1 AND c.extrafield='".substr($kfeature,8)."'";
                    if ($kfeature != 'options_longdescript')
                        $resql = $this->db->query($sql);
                    if ($resql) {
                        while ($res = $this->db->fetch_object($resql))
                        {
                            $res_extrafield=$res->idpresta;
                            if ($res_extrafield > 0) {
                                $features[$res_extrafield]['id_feature'] = $res_extrafield;
                                $features[$res_extrafield]['id_feature_value'] = $feature;
                                $features[$res_extrafield]['feature_value_lang'] = $feature;
                            }
                        }
                    }
                }
		$cats = array();
		$cats[] = array('id'    =>  $mycontext->id,
                    'import_key'	=>  $newimport_key,
                    'ref'		=>  $mycontext->ref,
                    'label'		=>  (version_compare(DOL_VERSION, '3.8.0', '<')?$newobject->libelle:$newobject->label),
                    'description'	=>  dol_trunc($newobject->description, 800),
                    'longdescript'	=>  $newobject->array_options['options_longdescript'],
                    'reel'		=>  'cybernull',
                    //'cyberprice'	=>  $newobject->array_options['options_cyberprice'],
                    'price'		=>  $price,//($price && $price > 0?$price:'cybernull'),
                    'tva_tx'		=>  $tva_tx,
                    'status'		=>  $newobject->status,
                    'ean13'		=>  ($newobject->barcode_type==2?$newobject->barcode:'cybernull'),
                    'upc'		=>  ($newobject->barcode_type==3?$newobject->barcode:'cybernull'),
                    'isbn'		=>  ($newobject->barcode_type==4?$newobject->barcode:'cybernull'),
                    'weight'		=>  $newobject->weight,
                    'height'            =>  $newobject->height,//hauteur
                    'width'             =>  $newobject->width,//largeur
                    'length'            =>  $newobject->length,//longueur profondeur
                    'cost_price' => $newobject->cost_price,
                    'imageDel'		=>  'cybernull',
                    'cat'		=>  $import_key,
                    'action'		=>  'add',
                    'reel0'		=>  'cybernull',
                    'features'          =>  $features
                );
            }

            /*print "<pre>";print_r($cats);print '</pre>';exit;*/
            $this->MyProduct($cats,$mycontext, 'CATEGORY_LINK');//CATEGORY_LINK
            dol_syslog("Trigger for action '$action' id=".$mycontext->id.' add '.$import_key,6,0, '_cyber');

            if (!$mycontext->import_key) {
                $result=$this->runTrigger('PRODUCT_MODIFY',$newobject,$user,$langs,$conf);
            }
        }
		elseif ($action == 'CATEGORY_UNLINK' || ($action == 'CATEGORY_MODIFY' && $object->context['unlinkoff'] && $object->context['unlinkoff']->element == 'product')) {
                $mycontext = $object->context['unlinkoff'];
            if ($mycontext->element == 'product') {
                $product_id = (int) $mycontext->id;
                $newobject = new Product($this->db);
		$newobject->fetch($product_id);
                $sql = "SELECT fk_categorie FROM ".MAIN_DB_PREFIX."categorie_product ";
                $sql.= " WHERE fk_product=".(int)$product_id;
                $resql=$this->db->query($sql);
                if ($resql)
                {
                    if ($this->db->num_rows($resql)==0)
                        return -1;
                }
                $sql = "SELECT rowid, import_key";
                $sql.= " FROM ".MAIN_DB_PREFIX."categorie";
                $sql.= " WHERE rowid = '".$object->id."'";
                $resql = $this->db->query($sql);
                if ($resql) {
                    $res = $this->db->fetch_array($resql);
                    $import_key = $res['import_key'];
                }
                $cats = array();
                $cats[] = array('id'    =>	$mycontext->id,
                    'import_key'        =>  $mycontext->import_key,
                    'ref'               =>	$mycontext->ref,
                    'reel'              =>	'cybernull',
                    //'cyberprice'        =>	'cybernull',
                    'price'             =>	'cybernull',
                    'ean13'             =>	($object->barcode_type==2?$object->barcode:'cybernull'),
                    'upc'               =>	($object->barcode_type==3?$object->barcode:'cybernull'),
                    'isbn'              =>	($object->barcode_type==4?$object->barcode:'cybernull'),
                    'cat'               =>	$import_key,
                    'action'            => 'remove',
                    'imageDel'          =>	'cybernull',
                    'reel0'             =>  'cybernull',
                );
                if (substr($cats['0']['import_key'], -1, 1) != '-' && $cats['0']['import_key'] != '') {
                    $this->MyProduct($cats,$mycontext, 'CATEGORY_UNLINK');//CATEGORY_UNLINK
                    $result=$this->runTrigger('PRODUCT_MODIFY',$newobject,$user,$langs,$conf);
                }
                dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$mycontext->id);
            }
        }
		elseif ($action == 'CATEGORY_CREATE' || $action == 'CATEGORY_MODIFY') {
            $sql = "SELECT c.*, c2.import_key as c2_import_key
            	FROM ".MAIN_DB_PREFIX."categorie c
                LEFT JOIN ".MAIN_DB_PREFIX."categorie c2 ON c.fk_parent = c2.rowid
		WHERE c.rowid = ".$object->id;
            $resql = $this->db->query($sql);
            $cats = array();
            if ($resql) {
                $res = $this->db->fetch_array($resql);
		$import_key = $res['import_key'];
		$cats[] = array('id'    =>  $res['rowid'],
                    'import_key'	=>  $res['import_key'],
                    'parent'		=>  $res['c2_import_key'],
                    'name'		=>  $res['label'],
                    'description'	=>  $res['description'],
                    'action'		=>  $action,
                );
		if (substr($res['c2_import_key'],0,1)=='P') $this->MyCategory($cats,$action);
            }
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__." id=".$object->id.$action,6,0, '_cyber');
	}
		elseif ($action == 'CATEGORY_DELETE') {
            /*dol_syslog("mycyberoffice ".__LINE__.$object->description);
            if ($object->description == 'mycyberoffice') return 0;
            $this->db->rollback();
            dol_syslog("mycyberoffice ".__LINE__.$object->description);*/
            $sql = "SELECT c.*
                FROM ".MAIN_DB_PREFIX."categorie c
		WHERE c.rowid = ".$object->id;
            $resql = $this->db->query($sql);
            if ($resql) {
                $res = $this->db->fetch_array($resql);
		$import_key = $res['import_key'];
            }

            /*$category_del = new Categorie($this->db);
            $category_del->fetch($object->id);
            $category_del->description = 'mycyberoffice';
            $category_del->update($user);

            $category_del->delete($user);*/

            dol_syslog("CATEGORY_DELETE::".__LINE__.$import_key).$res['rowid'];

            $cats[] = array('id'    =>  $object->id,
		'import_key'        =>  $import_key,
		'parent'            =>	$import_key,
		'name'              =>	'',
		'description'       =>	'',
		'action'            =>	$action,
            );

            if ($import_key)
                $this->MyCategory($cats, $action);
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__." id=".$object->id);
        }
        elseif ($action == 'ORDER_CLOSE') {
            $this->MyOrder($object->id, $action);
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'PAYMENT_ADD_TO_BANKxxx') {
            $this->MyOrder($cats);
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'SHIPPING_MODIFY' || $action == 'SHIPPING_CLOSED' || $action == 'SHIPPING_VALIDATE') {
            $this->MyOrder($object->id, $action);
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'BILL_PAYED' || $action == 'BILL_VALIDATE') {
            $object->fetchObjectLinked();
            if (is_countable($object->linkedObjectsIds['commande']) && count($object->linkedObjectsIds['commande']) > 0)
            {
            	foreach ($object->linkedObjectsIds['commande'] as $key => $value)
                {
                    $originid = $value;
                    $this->MyOrder($originid, $action);
                }
            }
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        return 0;
    }

    function MyOrder($cats, $action) {
    	global $conf, $db;
    	//require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
    	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
    	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
    	//require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
    	dol_syslog("mycyberoffice8_Cust.class " . $action . __LINE__ . '::Commande=' . $cats,6,0, '_cyber');
        if ($action=='ORDER_CLOSE' || $action == 'BILL_PAYED' || $action == 'BILL_VALIDATE') {
            $order = new Commande($this->db);
            $order->fetch($cats);
            $ref_customer=$order->ref_client;
    	} else {
            /*if ($action == 'SHIPPING_VALIDATE' && !empty($conf->workflow->enabled) && !empty($conf->global->WORKFLOW_ORDER_CLASSIFY_SHIPPED_SHIPPING)) {
                $action = 'SHIPPING_VALIDATE_workflow';
            }*/
	    require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
            dol_syslog("mycyberoffice8_Cust.class " . $action . __LINE__ . '::Expedition=' . $cats,6,0, '_cyber');
            $expedition = new Expedition($this->db);
            $expedition->fetch($cats);
            $expedition->fetchObjectLinked();
            //dol_syslog("mycyber Trigger for action ".$action." InvoiceNumber=".$conf->global->MYCYBEROFFICE_InvoiceNumber." count=".count($expedition->linkedObjectsIds['commande']));
            if (is_countable($expedition->linkedObjectsIds['commande']) && count($expedition->linkedObjectsIds['commande']) > 0)
            {
            	//dol_syslog("mycyber Trigger ".$this->name.' for action '.$action.' line='.__LINE__);
            	foreach ($expedition->linkedObjectsIds['commande'] as $key => $value)
                {
                    $originforcontact = 'commande';
                    $originidforcontact = $value;
                	//$this->origin_id = $value;
                    break; // We take first one
                }
            }
            //dol_syslog("mycyber Trigger ".$this->name." for action ".$action." order=".$originidforcontact);
	    $order = new Commande($this->db);
            if($originidforcontact) $order->fetch($originidforcontact);
            $ref_customer=$order->ref_client;
        }
        //dol_syslog("Trigger '".$this->name."' for action '$action' LINE ".__LINE__);
        $RefInvoice = 'cybernull';
        if ($order->id && $order->id > 0)
        {
            $note ='';//ycf
            /*foreach ($order->lines as $myline)
            {
                foreach ($myline->array_options as $key => $value)
                {
                    if ($key=='options_batch') $note.= $myline->ref .' : Frame/Engine '.$value."\r\n";
                }
            }*/
            $order->fetchObjectLinked();
            if (is_countable($order->linkedObjectsIds['facture']) && count($order->linkedObjectsIds['facture']) > 0  && $conf->global->MYCYBEROFFICE_InvoiceNumber==1)
	    {
                    	//dol_syslog("Trigger '".$this->name."' for action '$action' LINE ".__LINE__);
	        foreach ($order->linkedObjectsIds['facture'] as $key => $value)
	        {
                    $originidInvoice = $value;
	            break; // We take first one
	        }
	        $objectInvoice = new Facture($db);
	        if($originidInvoice)
                    $objectInvoice->fetch($originidInvoice);
	        $RefInvoice = preg_replace('/[^0-9]/','',$objectInvoice->ref);
            }
	   		//dol_syslog("Trigger '".$this->name."' for action '$action' LINE ".__LINE__);
	    $pos1 = strrpos($ref_customer, ":");
            if ($pos1 === false) {
                $pos1 = 0;
            } else $pos1 = $pos1 +1;
            $pos2 = strrpos($ref_customer, "/");
            if ($pos2 === false) {
                $pos2 = strlen($ref_customer);
            }
            $myref = substr($ref_customer, $pos1, $pos2 - $pos1);
            $ref_customer = $myref;
            if ($conf->global->CYBEROFFICE_desctorefcustomer) {
                $sql = "SELECT * FROM ".MAIN_DB_PREFIX."commande WHERE rowid =".$order->id;
                $resql1309 = $db->query($sql);
                if ($resql1309) {
                    while ($obj1309 = $db->fetch_object($resql1309))
                    {
                        $ref_customer = substr($obj1309->import_key, 4);
                    }
                }
            }
	    dol_syslog("MyCyberoffice_Trigger_MyOrder:: ".__LINE__.'::'.$action.' ref_customer='.$ref_customer.' cde='.$order->id.' fac='.$RefInvoice);
			/*
	    	print '<pre>';
	    	print_r($expedition);
	    	print '</pre>';
	    	exit;
	    	*/

	    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."const WHERE name LIKE 'CYBEROFFICE_SHOP%' ORDER BY name";
            $resql1758 = $db->query($sql);
            if ($resql1758) {
                while ($obj = $db->fetch_object($resql1758))
		{
                    				/*
						$h = (int)substr($obj->name,-2);
						$head[$h][0] = $_SERVER["PHP_SELF"]."?shop=".substr($obj->name,-2);
						$head[$h][1] = $langs->trans("Shop").substr($obj->name,-2);
						$head[$h][2] = $langs->trans("Shop").substr($obj->name,-2);
						$head[$h][3] = substr($obj->name,-2);//shop
						$head[$h][4] = $obj->value;//indice
						$head[$h][5] = $obj->note;//path
						*/
                    $myglobalkey = "MYCYBEROFFICE_key" . substr($obj->name,-2);
                    $myglobalshop = "MYCYBEROFFICE_shop" . substr($obj->name,-2);
                    $mygloballang = "MYCYBEROFFICE_lang" . substr($obj->name,-2);
                    $myglobalSexpedie = "MYCYBEROFFICE_Sexpedie" . substr($obj->name,-2);
                    $myglobalSlivre = "MYCYBEROFFICE_Slivre" . substr($obj->name,-2);
                    $myglobalSpaye = "MYCYBEROFFICE_Spaye" . substr($obj->name,-2);

                    dol_syslog("MyCyberoffice_Trigger_MyOrder::shop=".$myglobalshop.'::'.$obj->note);

                    $ws_dol_url = $obj->note.'modules/mycyberoffice/server_order_soap.php';
                    $ws_method  = 'Create';
                    $ns = 'http://www.lvsinformatique.com/ns/';
                    			// Set the WebService URL
                    $options = array('location' =>  $obj->note.'modules/mycyberoffice/server_order_soap.php',
                        'uri'                   =>  $obj->note);
                    $soapclient = new SoapClient(NULL,$options);

					// Call the WebService method and store its result in $result.
                    $authentication = array(
                        'dolibarrkey'       =>  (isset($conf->global->$myglobalkey) ? htmlentities($conf->global->$myglobalkey, ENT_COMPAT, 'UTF-8') : 0),
			'sourceapplication' =>  'LVSInformatique',
			'login'             =>  '',
			'password'          =>  '',
                        'shop'              =>  (isset($conf->global->$myglobalshop) ? $conf->global->$myglobalshop : 1),
			'myurl'             =>  $_SERVER["PHP_SELF"]
                    );

                    $myparam = array(
			'commande'          =>  $ref_customer,
                        'expedie'           =>  (isset($conf->global->$myglobalSexpedie) ? $conf->global->$myglobalSexpedie : 0),
                        'livre'             =>  (isset($conf->global->$myglobalSlivre) ? $conf->global->$myglobalSlivre : 0),
                        'paye'              =>  (isset($conf->global->$myglobalSpaye) ? $conf->global->$myglobalSpaye : 0),
			'action'            =>  $action,
                        'tracking_number'   =>  (isset($expedition->tracking_number) ? $expedition->tracking_number : ''),
			'RefInvoice'        =>  ($conf->global->MYCYBEROFFICE_InvoiceNumber==1?$RefInvoice:'cybernull'),
                        'note'              =>  $note,//ycf
                    );
                    if(isset($conf->global->$myglobalkey) && htmlentities($conf->global->$myglobalkey, ENT_COMPAT, 'UTF-8'))
                    {
                        try
    			{
                            $result = $soapclient->create($authentication, $myparam, $ns, '');
    			}

    			catch(SoapFault $fault)
    			{
                            if($fault->faultstring != 'Could not connect to host')
                            {
                            		        	/*
    					        	print '<pre>';
    			    				print_r($fault);
    			    				print '</pre>';
    			    				exit;
    					          throw $fault;
    					          */
                            }
			}

                        if (!isset($result) || !$result) {
					$result = array(
						'result'        =>  array('result_code' => 'KO', 'result_label' => 'KO'),
						'repertoire'    =>  $obj->note,
						'repertoireTF'  =>  'KO',
						'webservice'    =>  'KO',
						'dolicyber'     =>  'KO',
						'indice'        =>  -1
                    );
    			}
    			if (isset($conf->global->MYCYBEROFFICE_debug) && $conf->global->MYCYBEROFFICE_debug && $conf->global->MYCYBEROFFICE_debug == 1) {
                    print 'MyOrder::'.$action;
                    print '<pre>';
                    print_r($result );
    			    print_r($fault);
    			    print_r($myparam);
    			    print '</pre>';
    			    exit;
    			}
                    }
		}
            }
	}
    }

    function MyProduct($cats, $object, $action, $warehouse=0, $levelpriceuse = 0) {
    	global $conf, $db;
        dol_syslog("mycyberoffice8_Cust.class ".$action.__LINE__.'::'.$object->id,6,0, '_cyber');
        $newwarehouse = $warehouse;
        //dol_syslog("MyCyberoffice_Trigger_MyProduct::id=".$object->id.' '.$action);
        //$old_object = $object;
        /*$object = $object->context['linkto'];*/
        /*print '<pre>';print_r($cats);print_r($object);print '</pre>';die();*/
    	if(isset($object->entrepot_id) && $object->entrepot_id && $object->entrepot_id > 0)
            $product_id = (int)$object->product_id;
    	else
            $product_id = (int) $object->id;

        $parent = 0;
        if ($conf->global->CYBEROFFICE_variant == 1) {
            require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
            $productcombination = new ProductCombination($db);
            $parent = $productcombination->fetchByFkProductChild($product_id);
        }
        $sql = "SELECT distinct SUBSTRING(c.import_key,2,2) as numshop"
            . " FROM " . MAIN_DB_PREFIX . "categorie_product as ct, " . MAIN_DB_PREFIX . "categorie as c"
            . " WHERE ct.fk_categorie = c.rowid"
            . " AND (ct.fk_product = " . $product_id . " OR ct.fk_product = ". $parent .")"
            . " AND c.type = 0"
            . " AND c.entity IN (" . getEntity( 'category', 1 ) . ") AND c.import_key is not null";
        //dol_syslog("MyCyberoffice_Trigger_MyProduct::sql numshop=".$sql);
        dol_syslog("mycyberoffice8_Cust.class ".$action.__LINE__.'numshop='.$sql,6,0, '_cyber');
        if (!isset($cats[0]['action'])) {
            $cats[0]['action'] = 'add';
        }
        if (isset($cats[0]['action']) && ($cats[0]['action'] == 'add' || $cats[0]['action'] == 'remove')) {
            $numshops = "'".substr($cats[0]['cat'],1,2)."'";
        } else {
            $numshops = "'00'";
        }
		$res = $db->query($sql);
		if ($res)
		{
            while ($obj = $db->fetch_object($res))
            {
                if ($obj->numshop) $numshops.= ",'".$obj->numshop."'";
				//dol_syslog("MyCyberoffice_Trigger_MyProduct::sql numshop=".$numshops);
                dol_syslog("mycyberoffice8_Cust.class ".$action.__LINE__.'numshop='.$numshops,6,0, '_cyber');
            }
		}

    	if($conf->global->CYBEROFFICE_SHIPPING != $product_id
            &&  $conf->global->CYBEROFFICE_DISCOUNT != $product_id
            &&  $conf->global->CYBEROFFICE_wrapping != $product_id)
        {
            if (isset($cats[0]['import_key'])) {
                $posD = strrpos($cats[0]['import_key'], "-");
                //$posP = strpos($cats[0]['import_key'], "-");
                $indiceid = substr($cats[0]['import_key'],$posD + 1);
            } else {
                $posD = 0;
                $indiceid = 0;
            }
            /* TODO
             * prendre l'entrepot de la boutique associé à la categorie
             * SELECT c1.*, c2.* FROM llx_categorie c1
                left join llx_const c2 ON (c2.note = c1.label AND c2.name LIKE 'CYBEROFFICE_SHOP%')
                where c1.fk_parent = 0 and c1.type=0 AND c2.rowid IS NOT NULL
             */

            if(isset($object->entrepot_id) && $object->entrepot_id && $object->entrepot_id > 0)
                $sql = "SELECT c.name as name, c.note as note, c.value, c1.value as warehouse
                    FROM ".MAIN_DB_PREFIX."const c
                    left join ".MAIN_DB_PREFIX."const c1 on (RIGHT(c.name,2) = RIGHT(c1.name,2) AND c1.name LIKE 'MYCYBEROFFICE_warehouse%')
                    WHERE c.name LIKE 'CYBEROFFICE_SHOP%' AND c1.entity IN (0,".$conf->entity.")
                    AND c.value in (".$numshops.")";
            else
                $sql = "SELECT *, 999 as warehouse FROM ".MAIN_DB_PREFIX."const WHERE name LIKE 'CYBEROFFICE_SHOP%' AND value in (".$numshops.") ORDER BY name";

            $resql1 = $db->query($sql);
            if ($resql1) {
                $myi=0;
                while ($obj = $db->fetch_object($resql1))
                {
                    $myi++;
                    //dol_syslog("Cyberoffice_Trigger_MyProduct:: myi".$myi);
                    dol_syslog("mycyberoffice8_Cust.class ".$action.__LINE__.'myi='.$myi,6,0, '_cyber');
                    $mywarehouse = $conf->global->{'MYCYBEROFFICE_warehouse'.substr($obj->name,-2)};
                    if ($newwarehouse == -1) {
                        require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
                        $Myobject = new Product($db);
                        $Myresult = $Myobject->fetch($product_id);
                        $Myobject->load_stock();
                        $stock = 0;
                        $stock0 = 0;
                        foreach ($Myobject->stock_warehouse as $key => $value) {
                            $stock_real_theorique = ($conf->global->MYCYBEROFFICE_stock_theorique == 1?$value->stock_theorique:$value->real);
                            $arraywarehouseSelected = [];
                            if (isset($conf->global->{"MYCYBEROFFICE_arraywarehouse".substr($obj->name,-2)})) {
                                $arraywarehouseSelected = json_decode($conf->global->{"MYCYBEROFFICE_arraywarehouse".substr($obj->name,-2)});
                                if (in_array($key, $arraywarehouseSelected)) {
                                $stock += $stock_real_theorique;
                            $stock0 += $stock_real_theorique;
                        }
                            } else {
                                if ($key == $mywarehouse) {
                                    $stock += $stock_real_theorique;
                                }
                                $stock0 += $stock_real_theorique;
                            }
                        }
                        $cats['0']['reel']=$stock;
                        $cats['0']['reel0']=$stock0;
                        $warehouse=$mywarehouse;
                    }
                    dol_syslog("mycyberoffice8_Cust.class ".$action.__LINE__.'warehouse='.substr($obj->name,-2).'. '.$mywarehouse.'. '.$warehouse .'. '.(int)$obj->warehouse,6,0, '_cyber');
                    //dol_syslog('MyCyberoffice_Trigger_MyProduct::warehouse'.substr($obj->name,-2).'. '.$mywarehouse.'. '.$warehouse .'. '.(int)$obj->warehouse);
                    if ($warehouse != (int)$obj->warehouse && ((int)$obj->warehouse != 0 && (int)$obj->warehouse != 999)) {
                        //dol_syslog("MyCyberoffice_Trigger_MyProduct::".__LINE__);
                        dol_syslog("mycyberoffice8_Cust.class ".$action.__LINE__,6,0, '_cyber');
                        continue;
                    }
                    /*print __LINE__.$cats[0]['action'];*/
                    if ((isset($cats[0]['action']) && $cats[0]['action'] == 'add') || $conf->global->CYBEROFFICE_variant == 1) {
                        dol_syslog("mycyberoffice8_Cust.class ".$action.__LINE__,6,0, '_cyber');
                        $newobject = new Product($db);
                        $newobject->fetch($product_id);
                        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
                        $sortorder="ASC";
                        $sortfield="name";
                        if (! empty($conf->product->enabled))
                            $upload_dir = $conf->product->multidir_output[$newobject->entity].'/'.dol_sanitizeFileName($newobject->ref);
                        elseif (! empty($conf->service->enabled))
                            $upload_dir = $conf->service->multidir_output[$newobject->entity].'/'.dol_sanitizeFileName($newobject->ref);
                        $filesarray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
                        $array_picture = array();
                        foreach($filesarray as $filearray)
                        {
                            if( $filearray['name'] ) {
                                $pos_point = strrpos($filearray['name'], '.');
                                $nom = substr($filearray['name'], 0, $pos_point);
                                $picture = $filearray['fullname'];
                                $name = explode("/",$picture);
                                $name = $name[sizeof($name)-1];
                                if (preg_match('/(\.gif|\.jpg|\.jpeg|\.png|\.bmp)$/i',$name,$reg)) {
                                    $ext = '';
                                } else {
                                    $ext='nok';
                                }
                                $imgfonction='';
                                if ($ext!= 'nok' && $reg[1]) {
                                if (strtolower($reg[1]) == '.gif')  $ext= 'gif';
                                if (strtolower($reg[1]) == '.png')  $ext= 'png';
                                if (strtolower($reg[1]) == '.jpg')  $ext= 'jpeg';
                                if (strtolower($reg[1]) == '.jpeg') $ext= 'jpeg';
                                if (strtolower($reg[1]) == '.bmp')  $ext= 'wbmp';
                                }
                                if ($ext != 'nok') {
									copy($picture, DOL_DOCUMENT_ROOT."/custom/mycyberoffice8/images_temp/$product_id$nom.$ext");
//                                    $file = array("tmp_name"=>DOL_DOCUMENT_ROOT."/custom/mycyberoffice8/images_temp/$product_id$nom.$ext","name"=>$name);
//                                    $img = @call_user_func_array("imagecreatefrom".$ext,array($picture));
//                                    @call_user_func_array("image$ext",array($img,$file['tmp_name']));
//                                    @imagedestroy($img);
                                    array_push($array_picture,array("name" => $nom, "url" => 'https://erpi.loucreezart.fr/abs'.'/custom/mycyberoffice8/images_temp/'.$product_id.$nom.'.'.$ext, "nom" => $nom.'.'.$ext));
                                }
                            }
                        }
                        $cats['0']['image'] = $array_picture;
                        /*print_r($cats['0']['image']);*/
                    }
                    if ($conf->global->MAIN_MODULE_PRICELIST == 1) {
                        $pricelist_array = [];
                        $sql = "SELECT pl.rowid, pl.fk_product, pl.fk_soc, pl.from_qty, pl.price, pl.tx_discount, pl.fk_cat "
                            . ", s.import_key as s_import_key "
                            . ", p.import_key as p_import_key "
                            . "FROM ".MAIN_DB_PREFIX."pricelist pl "
                            . "LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = pl.fk_soc "
                            . "LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = pl.fk_product "
                            . "WHERE fk_product = ".$product_id . " AND ISNULL(pl.fk_cat)";
                        $resql=$db->query($sql);
                        if ($resql) {
                            while ($myobj = $db->fetch_object($resql))
                            {
                                $p_import_key = (!empty($myobj->p_import_key) ? explode('-',$myobj->p_import_key) : []);
                                $s_import_key = (!empty($myobj->s_import_key) ? explode('-',$myobj->s_import_key) : []);
                                $socid = (isset($s_import_key[1]) ? $s_import_key[1] : 0);
                                $id_product = (isset($p_import_key[1]) ? $p_import_key[1] : 0);
                                $id_product_attribute= (isset($p_import_key[2]) ? $p_import_key[2] : 0);
                                if (isset($myobj->price) || isset($myobj->tx_discount)) {
                                    $pricelist_array[] = array (
                                        'product_id' => $id_product,
                                        'socid' => $socid,
                                        'from_qty' => $myobj->from_qty,
                                        'price' => $myobj->price,
                                        'tx_discount' => $myobj->tx_discount,
                                        'product_attribute' => $id_product_attribute,
                                    );
                                }
                            }
                        }
                        $cats[0]['pricelist'] = $pricelist_array;
                    }
                    if (get_class($object) == 'Product') {
                        // Multilangs
                        if (!empty($conf->global->MAIN_MULTILANGS)) {
                            $object->getMultiLangs();
                            $cats['0']['multilangs'] = $object->multilangs;
                        }
                        $cats['0']['seuil_stock_alerte'] = $object->seuil_stock_alerte;
                    }
                    $cats[0]['variant'] = null;
                    /** VARIANT **/
                    if ($conf->global->CYBEROFFICE_variant == 1) {
                        if (isset($conf->global->PRODUIT_ATTRIBUTES_SEPARATOR)) {
                            $separator = $conf->global->PRODUIT_ATTRIBUTES_SEPARATOR;
                        } else {
                            $separator = "_";
                        }

                        require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination2ValuePair.class.php';
                        require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
                        $productcombination = new ProductCombination($db);
                        $productcombination2 = new ProductCombination2ValuePair($db);
                        $product = new Product($db);
                        $parent = $productcombination->fetchByFkProductChild($product_id);
                        $myidparent = $parent;
                        dol_syslog("mycyberoffice8_Cust.class ".$action.__LINE__.":: P".$myidparent .'-> C'.$product_id,6,0, '_cyber');
                        //dol_syslog("Cyberoffice_Trigger_MyProduct:: P".$myidparent .'-> C'.$product_id);
                        //print "<br>Cyberoffice_Trigger_MyProduct:: P".$myidparent .'-> C'.$object->id;
                        /*if ($parent == $object->id)
                            continue;
                        dol_syslog("Cyberoffice_Trigger_MyProduct::ok P".$parent.' -> C'.$object->id);*/
                        if ($parent > 0) {
                            $product->fetch($myidparent);
                            $allparent = $productcombination->fetchAllByFkProductParent($myidparent);
                            foreach($allparent as $combination)
                            {
                                if ($combination->fk_product_child != $product_id)
                                    continue;
                                $declinaison = array();
                                $attributes = $productcombination2->fetchByFkCombination($combination->id);
                                foreach($attributes as $attribute)
                                {
                                    $indshop = substr($obj->name,-2);
                                    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cyberoffice3"
                                        ." WHERE shop LIKE '".$indshop."' AND variant = ".$attribute->fk_prod_attr_val;
                                    $resql = $db->query($sql);
                                    if ($resql)
                                    {
                                        while ($row = $db->fetch_array($resql))
                                        {
                                            $declinaison[] = $row['attribut'];
                                        }

                                    }
                                }

                                if (empty($conf->global->PRODUIT_MULTIPRICES) || $conf->global->PRODUIT_MULTIPRICES == 0) {
                                    $pricelevel0 = 1;
                                    $price0 = $product->price;
                                    if ($combination->variation_price_percentage == true) {
                                        $variation_price = $product->price * $combination->variation_price / 100;
                                    } else {
                                        $variation_price = $combination->variation_price;
                                    }
                                } else {
                                    $pricelevel0 = (int)$conf->global->{"MYCYBEROFFICE_pricelevel".substr($obj->name,-2)};
                                    $price0 = $product->multiprices[$pricelevel0];
                                    if ($combination->combination_price_levels[$pricelevel0]->variation_price_percentage) {
                                        $variation_price = $price0 * $combination->combination_price_levels[$pricelevel0]->variation_price / 100;
                                    } else {
                                        $variation_price = $combination->combination_price_levels[$pricelevel0]->variation_price;
                                    }
                                    if ($levelpriceuse!=0 && $pricelevel0 != $levelpriceuse) {
                                        dol_syslog("mycyberoffice8_Cust.class ".$action.__LINE__."::pricelevel0=".$pricelevel0 .' levelpriceuse='.$levelpriceuse,6,0, '_cyber');
                                        return 1;
                                    }
                                }
                                $cats[0]['variant']=array(
                                    'parent'                        => $parent,
                                    'declinaison'                   => $declinaison,
                                    'ref'                           => $product->ref,
                                    'import_key'                    => $product->import_key,
                                    'label'                         => $product->label,
                                    'variation_price'               => $variation_price,
                                    'variation_weight'              => $combination->variation_weight,
                                    'price'                         => $price0,
                                    'weight'                        => $product->weight);
                            }
                        }
                    }
                    dol_syslog("mycyberoffice8_Cust.class ".$action.__LINE__.':'.$obj->value.':'. (isset($cats[0]['cat']) ? $cats[0]['cat'] : '') .':'.$cats[0]['action'],6,0, '_cyber');
                    //dol_syslog("Cyberoffice_Trigger_MyProduct:: ".__LINE__.':'.$obj->value.':'. (isset($cats[0]['cat']) ? $cats[0]['cat'] : '') .':'.$cats[0]['action']);
                    if (((isset($cats[0]['cat']) && $obj->value == substr($cats[0]['cat'], 1, 2))
                            || $cats[0]['action'] != 'add'
                            || $cats[0]['action'] != 'remove'
                        ) && (
                            !is_array($cats[0]['variant'])
                            || (isset($cats[0]['variant']['declinaison']) && count($cats[0]['variant']['declinaison']) > 0)
                        )
                    ) {
                        dol_syslog("mycyberoffice8_Cust.class ".$action.__LINE__.':' . $obj->value . ':' . (isset($cats[0]['cat']) ? substr($cats[0]['cat'],1,2) : null) . ':' . (isset($cats[0]['action']) ? $cats[0]['action'] : null),6,0, '_cyber');
                        //dol_syslog("Cyberoffice_Trigger_MyProduct:: " . __LINE__ . ':' . $obj->value . ':' . (isset($cats[0]['cat']) ? substr($cats[0]['cat'],1,2) : null) . ':' . (isset($cats[0]['action']) ? $cats[0]['action'] : null));
                        /*print '<pre>';print_r( $cats);print '</pre>';print 'warehouse='.(int)$obj->warehouse;*/

                        if ((int) $obj->warehouse == 0 || $mywarehouse == 0) {
                            $cats['0']['reel']=$cats['0']['reel0'];
                        }


                        if ($conf->global->MYCYBEROFFICE_stock_theorique == 1 && ($cats['0']['reel'] != 'cybernull' || $cats['0']['reel']==0))
                        {
                            require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
                            $Myobject = new Product($db);
                            $Myresult = $Myobject->fetch($product_id);
                            $Myobject->load_stock();
                            $cats['0']['reel']=$Myobject->stock_theorique;
                        }
                        /*print '<pre>';print_r($cats);print '</pre>';exit;*/
                        $myglobalkey = "MYCYBEROFFICE_key" . substr($obj->name,-2);
                        $myglobalshop = "MYCYBEROFFICE_shop" . substr($obj->name,-2);
                        $mygloballang = "MYCYBEROFFICE_lang" . substr($obj->name,-2);
                        //dol_syslog("MyCyberoffice_Trigger_MyProduct::shop=".$myglobalshop.'::'.$obj->note);
                        dol_syslog("mycyberoffice8_Cust.class ".$action.__LINE__.':shop='.$myglobalshop.'::'.$obj->note,6,0, '_cyber');

                        //$ws_dol_url = $obj->note.'modules/mycyberoffice/server_product_soap.php';
                        //$ws_method  = 'Create';
                        $ns = 'http://www.lvsinformatique.com/ns/';

                                                    // Set the WebService URL
                        /*$options = array('location' => $obj->note.'modules/mycyberoffice/server_product_soap.php',
                            'uri'                   => $obj->note);*/
                        $options = ['location' => $obj->note.'modules/mycyberoffice/server_product_soap.php',
                            'uri' => $obj->note,
                            'wsdl_cache' => 0,
                            'exceptions' => true,
                            'trace' => 1,
                        ];
                        //@ini_set('default_socket_timeout', 320);
                        //@ini_set('soap.wsdl_cache_enabled', '0');
                        //@ini_set('soap.wsdl_cache_ttl', '0');
                        try {
                            $soapclient = new SoapClient(null, $options);
                        } catch(Throwable $e) {
                            print 'Exception Error!';
                            print var_dump($e->getMessage());
                        }

                                                    // Call the WebService method and store its result in $result.
                        $authentication = array(
                            'dolibarrkey'           =>  (isset($conf->global->$myglobalkey) ? htmlentities($conf->global->$myglobalkey, ENT_COMPAT, 'UTF-8') : 0),
                            'sourceapplication'     =>  'LVSInformatique',
                            'login'                 =>  '',
                            'password'              =>  '',
                            'shop'                  =>  (isset($conf->global->$myglobalshop) ? $conf->global->$myglobalshop : 1),
                            'lang' => isset($conf->global->$mygloballang) ? $conf->global->$mygloballang : 1,
                            'myurl'                 =>  $_SERVER["PHP_SELF"],
                            'OnlyStock'             =>  $conf->global->{"MYCYBEROFFICE_OnlyStock".substr($obj->name,-2)},
                            'Label'                 =>  $conf->global->{"MYCYBEROFFICE_Label".substr($obj->name,-2)}
                        );

                        $myparam = $cats;

                        /*print '<pre>';print_r($myparam);print '</pre>';*/

                        if (is_array($cats['0']['price']))
                        {
                            if (empty($conf->global->PRODUIT_MULTIPRICES) || $conf->global->PRODUIT_MULTIPRICES == 0) {
                                $pricelevel = 0;
                            } else {
                                $pricelevel = (int)$conf->global->{"MYCYBEROFFICE_pricelevel".substr($obj->name,-2)} - 1;
                            }
                            $myparam['0']['price'] = $cats['0']['price'][$pricelevel];
                            $myparam['0']['tva_tx'] = $conf->global->{"MYCYBEROFFICE_tax".substr($obj->name,-2).(float)$object->tva_tx};
                        } else {
                            $myparam['0']['price'] = 'cybernull';
                        }
                        //dol_syslog("MyCyberoffice_Trigger_MyProduct::cat=".$myparam[0]['default_cat'].':'.$myparam['0']['cat'].$myparam['0']['action']);
                        dol_syslog("interface_90_mycyberoffice8_Cust.class::Prestashop Synchronization==> pricelevel ".$action.__LINE__.":".$myglobalkey,6,0, '_cyber');
                        if (isset($conf->global->$myglobalkey) && htmlentities($conf->global->$myglobalkey, ENT_COMPAT, 'UTF-8')) {
                            dol_syslog("interface_90_mycyberoffice8_Cust.class ".__LINE__.':'.print_r($myparam, true),6,0, '_cyber');
                            try {
								BimpDebug::addDebug('api', 'API "presta" - Action "'.$action.'"', '<pre>'.print_r($myparam,1).'</pre>');

								$result0 = $soapclient->create($authentication, $myparam, $ns, '');
                            } catch(SoapFault $fault) {
                                if($fault->faultstring != 'Could not connect to host') {
									BimpDebug::addDebug('api', 'API "presta" - Action "'.$action.'" erreur', '<pre>'.print_r($result,1).print_r($fault,1).'</pre>');

                                                            //print_r($fault);
                                                            //throw $fault;
                                }
                            }

                            if (!isset($result0) || !$result0) {
                                dol_syslog("interface_90_mycyberoffice8_Cust.class::Prestashop Synchronization==> NOK ".$action.__LINE__.print_r($authentication, true).print_r($myparam, true),6,0, '_cyber');
                                /*var_dump(htmlspecialchars($soapclient->__getLastResponse()));
                                echo "getLastResponse: " . $soapclient->__getLastResponse();
                                echo "<br/>getLastRequest: " . $soapclient->__getLastRequest();
                                echo "<br/>getLastResponseHeaders: " . $soapclient->__getLastResponseHeaders();

                                print '<pre>';print_r($result); print '</pre>';
                                // show soap request and response
                                echo "<h2>Request</h2>";
                                echo "<pre>" . htmlspecialchars($soapclient->request, ENT_QUOTES) . "</pre>";
                                echo "<h2>Response</h2>";
                                echo "<pre>" . htmlspecialchars($soapclient->response, ENT_QUOTES) . "</pre>";
                                exit;*/
                                $result = array(
                                    'result'        =>  array('result_code' => 'KO', 'result_label' => 'KO'),
                                    'repertoire'    =>  $obj->note,
                                    'repertoireTF'  =>  'KO',
                                    'webservice'    =>  'KO',
                                    'dolicyber'     =>  'KO',
                                    'lang'          => isset($conf->global->$mygloballang) ? $conf->global->$mygloballang : 1,//php8
                                    'Result'        => null//php8
                                );
                                //setEventMessages("Prestashop Synchronization==> ".$fault->faultstring, array(), 'errors');
                            } else {
                                $result = array();
                                dol_syslog("interface_90_mycyberoffice8_Cust.class::Prestashop Synchronization==> OK ".__LINE__,6,0, '_cyber');
                                //setEventMessages("Prestashop Synchronization==> OK", array(), 'mesgs');
                            }
                            dol_syslog("mycyberoffice8_Cust.class ".$action.__LINE__,6,0, '_cyber');
                            if (isset($result0) && isset($result0['result']['result_label']) && ($result0['result']['result_label'] != 'NOK' && $result0['result']['result_label'] != 'OK'))//php8
                            {
                                dol_syslog("mycyberoffice8_Cust.class ".$action.__LINE__,6,0, '_cyber');
                                $num = $obj->value;
                                $myid = explode(":", $result0['result']['result_label']);//php8

                                $import_key = 'P'.$num.'-'.$myid[0];
                                $sql = "UPDATE ".MAIN_DB_PREFIX."product SET";
                                $sql .= " import_key='".$import_key."'";
                                $sql.= " WHERE rowid=".$product_id;
                                dol_syslog("mycyberoffice8_Cust.class ".$action.__LINE__.':avant maj import_key=='.$import_key.'/'.$num.'/'.$myid[0],6,0, '_cyber');
                                //dol_syslog("MyCyberoffice_Trigger_MyProduct::avant maj import_key=".$import_key.'/'.$num.'/'.$myid[0]);
                                if (substr($import_key, -1, 1) != '-' && $num != 0 && $myid[0] && $myid[0]>0) {
                                    dol_syslog("MyCyberoffice_Trigger_MyProduct::maj import_key ok".$sql);
                                    $db->begin();
                                    $reqsql = $db->query($sql);
                                                                    //dol_syslog("Cyberoffice_Trigger_MyProduct:: ".__LINE__);
                                    $db->commit();
                                                                    //dol_syslog("Cyberoffice_Trigger_MyProduct:: ".__LINE__);
                                }
                            }
                            dol_syslog("mycyberoffice8_Cust.class ".$action.__LINE__,6,0, '_cyber');
                            // dol_syslog("Cyberoffice_Trigger_MyProduct:: ".__LINE__.'::'.$conf->global->MYCYBEROFFICE_debug);
                            if (isset($conf->global->MYCYBEROFFICE_debug) && $conf->global->MYCYBEROFFICE_debug && $conf->global->MYCYBEROFFICE_debug == 1) {
                                dol_syslog("mycyberoffice8_Cust.class ".$action.__LINE__,6,0, '_cyber');
                                print 'MyProduct::'.$action;
                                print '<pre>';
                                isset($result0) ? print_r($result0) : print 'No result0';
                                print_r($result );
                                print_r($fault);
                                //print 'old_object';
                                //print_r($old_object);
                                print 'myparam';
                                print_r($myparam);
                                print 'object';
                                print_r($object);
                                print '</pre>';
                                exit;
                            }
                        }
                        /*print '<pre>';print_r($myparam);print '</pre>';*/
                    }
                }
            }
        }	//fin test service
		return 1;
    }

    function MyCategory($cats, $action)
    {
        global $conf, $db;
        dol_syslog("MyCyberoffice_Trigger_MyCategory::id=" . $cats[0]['id'],6,0, '_cyber');
        $numshop = substr($cats[0]['parent'],1,2);
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "const WHERE name LIKE 'CYBEROFFICE_SHOP%' AND value = '" . $numshop . "' ORDER BY name";

        $resql = $db->query($sql);

        if ($resql) {
            while ($obj = $db->fetch_object($resql))
            {
                $myglobalkey = "MYCYBEROFFICE_key" . substr($obj->name,-2);
                $myglobalshop = "MYCYBEROFFICE_shop" . substr($obj->name,-2);
                $mygloballang = "MYCYBEROFFICE_lang" . substr($obj->name,-2);

                dol_syslog("MyCyberoffice_Trigger_MyCategory::shop=" . $myglobalshop . '::' . $obj->note,6,0, '_cyber');

                $ws_dol_url = $obj->note.'modules/mycyberoffice/server_category_soap.php';
                $ws_method  = 'Create';
                $ns = 'http://www.lvsinformatique.com/ns/';

                 $options = array('location' => $obj->note . 'modules/mycyberoffice/server_category_soap.php',
                    'uri' => $obj->note);
                $soapclient = new SoapClient(NULL,$options);

                $authentication = array(
                    'dolibarrkey' => htmlentities($conf->global->$myglobalkey, ENT_COMPAT, 'UTF-8'),
                    'sourceapplication' => 'LVSInformatique',
                    'login' => '',
                    'password' => '',
                    'shop' => (isset($conf->global->$myglobalshop) ? $conf->global->$myglobalshop : 1),
                    'lang' => isset($conf->global->$mygloballang) ? $conf->global->$mygloballang : 1,
                    'myurl' => $_SERVER["PHP_SELF"],
                );
                $myparam = $cats;

                try
                {
                    $result0 = $soapclient->create($authentication, $myparam, $ns, '');
                } catch(SoapFault $fault) {
                    if($fault->faultstring != 'Could not connect to host') {
                        $result = array(
                            'result' => array('result_code' => 'KO', 'result_label' => 'KO'),
                            'repertoire' => $obj->note,
                            'repertoireTF' => 'KO',
                            'webservice' => 'KO',
                            'dolicyber' => 'KO',
                            'lang' => isset($conf->global->$mygloballang) ? $conf->global->$mygloballang : 1,
                            'Result' => (isset($result0)?$result0:'KO:'.$fault->faultstring),
                        );
                    }
                }

                if (!isset($result0) || !$result0) {
                    $result = array(
                        'result' => array('result_code' => 'KO', 'result_label' => 'KO'),
                        'repertoire' => $obj->note,
                        'repertoireTF' => 'KO',
                        'webservice' => 'KO',
                        'dolicyber' => 'KO',
                        'lang' => isset($conf->global->$mygloballang) ? $conf->global->$mygloballang : 1,
                        'Result' => 'KO',
                    );
                }
                dol_syslog("MyCyberoffice_Trigger_MyCategory::avant maj result=".(isset($result0)?$result0['result']['result_label']:''),6,0, '_cyber');
                if (isset($result0) && $result0['result']['result_label'] != 'NOK' && $result0['result']['result_label'] != 'OK' && $result0['result']['result_label'] != 'KO') {
                    $num = $obj->value;
                    $myid = explode(":", $result0['result']['result_label']);
                    $import_key = 'P' . $num . '-' . $myid[0];
                    $sql = "UPDATE " . MAIN_DB_PREFIX . "categorie SET";
                    $sql .= " import_key='" . $import_key . "'";
                    $sql.= " WHERE rowid=".$cats[0]['id'];
                    dol_syslog("MyCyberoffice_Trigger_MyCategory::avant maj import_key=" . $import_key . '/' . $result0['result']['result_label'],6,0, '_cyber');
                    if (substr($import_key, -1, 1) != '-' && $num != 0 && $myid[0] && $myid[0] > 0) {
                        dol_syslog("MyCyberoffice_Trigger_MyCategory::maj import_key ok " . $sql,6,0, '_cyber');
                        $db->begin();
                        $reqsql = $db->query($sql);
                        $db->commit();
                    }
                }
                if (isset($conf->global->MYCYBEROFFICE_debug) && $conf->global->MYCYBEROFFICE_debug && $conf->global->MYCYBEROFFICE_debug == 1) {
                    print 'MyCategorie::'.$action;
                    print '<pre>';
                    print_r($result0);
                    print_r($result );
                    print_r($fault);
                    print_r($myparam);
                    print_r($object);
                    print '</pre>';
                    exit;
                }
                $result='';
                $result0='';
            }
        } // if
    }
}
