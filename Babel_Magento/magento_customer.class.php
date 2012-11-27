<?php

/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */

require_once('magento_soap.class.php');
class magento_customer extends magento_soap{

    function magento_customer($conf) {
        $MagentoSoapUrl = $conf->global->MAGENTO_PROTO."://".$conf->global->MAGENTO_HOST;
        if ($conf->global->MAGENTO_PROTO == 'http' && $conf->global->MAGENTO_PORT != 80)
        {
            $MagentoSoapUrl .= ":".$conf->global->MAGENTO_PORT;
        }
        if ($conf->global->MAGENTO_PROTO == 'https' && $conf->global->MAGENTO_PORT != 443)
        {
            $MagentoSoapUrl .= ":".$conf->global->MAGENTO_PORT;
        }
        $MagentoSoapUrl .= $conf->global->MAGENTO_PATH;
        $this->MagentoSoapUrl= $MagentoSoapUrl;
        $this->username = $conf->global->MAGENTO_USER;
        $this->pass = $conf->global->MAGENTO_PASS;
    }
    public function create_customer($firstname,$lastname,$email,$pass,$store_id=1,$website_id=1)
    {
       #
        $newCustomer = array(
            'firstname'  => $firstname,
            'lastname'   => $lastname,
            'email'      => $email,
            'password_hash'   => md5($pass),
            // password hash can be either regular or salted md5:
            // $hash = md5($password);
            // $hash = md5($salt.$password).':'.$salt;
            // both variants are valid
            'store_id'   => $store_id,
            'website_id' => $website_id
            );
        $arr = array();
        array_push($arr,$newCustomer);
        $result = $this->call_magento("customer.create",$arr);
        return ($result);
    }
//customer
    public function customer_info($custId)
    {
        $arr =array();
        array_push($arr,$custId);
        $result = $this->call_magento("customer.info",$arr);
        return ($result);
    }
//Address
    public function customer_address($custId)
    {
        $arr =array();
        array_push($arr,$custId);
        $result = $this->call_magento("customer_address.list",$arr);
        return ($result);

    }
    public function customer_address_info($custId)
    {
        $arr =array();
        array_push($arr,$custId);
        $result = $this->call_magento("customer_address.info",$arr);
        return ($result);

    }
//Sync GLE

    public $isSoc=false;
    public function addToGle($magentoId)
    {
        $arr=$this->customer_info($magentoId);
        $this->createCustomerGle($arr);
    }
    public function createCustomerGle($custInfo,$addInfo,$db)
    {
        $default_billing = $custInfo['default_billing'];
        $addArr=array();
        if ($default_billing."x" != "x")
        {
            $addArr = $this->customer_address_info($default_billing);
        } else {
            $addArr = $addInfo[0];
        }
        $isSoc = true;
        if ($addArr['company']."x" == "x")
        {
            $isSoc=false;
        }
        //base datas:
        $city = $addArr['city'];
        $customer_id = $addArr['customer_id'];
        $increment_id = $addArr['increment_id'];
        $company = $addArr['company'];
        $country_id = $addArr['country_id'];//FR
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."pays WHERE code = ".$country_id;
        $sqlPays = $db->query($requete);
        $res = $db->fetch_object($sqlPays);
        $country_id = $res->rowid;
        $fax = $addArr['fax'];
        $firstname = $addArr['firstname'];
        $lastname = $addArr['lastname'];
        $postcode = $addArr['postcode'];
        $region = $addArr['region']; //Charente
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."departement WHERE ncc = '".strtoupper($region)."'";
        $sqlDep = $db->query($requete);
        $res = $db->fetch_object($sqlDep);
        $region = $res->rowid;
        $street = $addArr['street'];
        $telephone = $addArr['telephone'];
        $dob = $custInfo['dob'];
        $email = $custInfo['email'];
        $taxvat = $custInfo['taxvat'];
        //create Soc
        require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
        $soc = new societe($db);
        $soc->code_client = -1;
        $soc->code_fournisseur=0;
        if ($isSoc)
        {
            $soc->nom=$company;
        } else {
            $soc->nom=$lastname." ".$firstname;
            $soc->typent_id = 8;
            $soc->forme_juridique_code = 99;
            $soc->capital=0;
        }

        $soc->adresse=$street;
        $soc->cp=$postcode;
        $soc->ville=$city;
        $soc->departement_id = $region;
        $soc->pays_id = $country_id;
        $soc->tel = $telephone;
        $soc->fax=$fax;
        $soc->email=$email;
        $soc->tva_intra=$taxvat;
        $soc->note='Client du site';
        $soc->create();


        //Sync soc to magento
        $requete = "INSERT INTO babel_magento_soc
                                (socid, magentoid)
                         VALUES (".$soc->id.", ".$customer_id.")";
        $sqlIns=$db->query($requete);

        //create contact
        require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
        require_once(DOL_DOCUMENT_ROOT."/user/class/user.class.php");
        $tmpuser = new User($db,1);
        $tmpuser->id = 1;
        $contact = new Contact($db);
        $contact->name=$lastname;
        $contact->socid = $soc->id;

        $contact->firstname=$firstname;
        $contact->email = $email;
        $contact->phone = $telephone;
        $contact->phone_pro = $telephone;
        $contact->fax = $fax;
        $contact->cp = $soc->cp;
        $contact->ville = $soc->ville;
        $contact->adresse=$soc->adresse;
        $contact->fk_pays=$soc->pays_id;
        $contact->birthday=$dob;
        $contact->note = $soc->note;
        $contact->priv = 0;

        $contact->create($tmpuser);

        //Sync contact to magento
        $requete = "INSERT INTO babel_magento_contact
                                (contactid, magentoid)
                         VALUES (".$contact->id.", ".$customer_id.")";
        $sqlIns=$db->query($requete);

    }

    public function updateCustomerGle($custInfo,$addInfo,$db)
    {
        $default_billing = $custInfo['default_billing'];
        $addArr=array();
        if ($default_billing."x" != "x")
        {
            $addArr = $this->customer_address_info($default_billing);
        } else {
            $addArr = $addInfo[0];
        }
        $isSoc = true;
        if ($addArr['company']."x" == "x")
        {
            $isSoc=false;
        }
        //base datas:
        $city = $addArr['city'];
        $customer_id = $addArr['customer_id'];
        $increment_id = $addArr['increment_id'];
        $company = $addArr['company'];
        $country_id = $addArr['country_id'];//FR
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."pays WHERE code = ".$country_id;
        $sqlPays = $db->query($requete);
        $res = $db->fetch_object($sqlPays);
        $country_id = $res->rowid;
        $fax = $addArr['fax'];
        $firstname = $addArr['firstname'];
        $lastname = $addArr['lastname'];
        $postcode = $addArr['postcode'];
        $region = $addArr['region']; //Charente
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."departement WHERE ncc = '".strtoupper($region)."'";
        $sqlDep = $db->query($requete);
        $res = $db->fetch_object($sqlDep);
        $region = $res->rowid;
        $street = $addArr['street'];
        $telephone = $addArr['telephone'];
        $dob = $custInfo['dob'];
        $email = $custInfo['email'];
        $taxvat = $custInfo['taxvat'];
        //create Soc
        require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
        $soc = new societe($db);
        $requete = "SELECT * FROM babel_magento_soc WHERE magentoid = ".$customer_id;
        $sqlReq = $db->query($requete);
        $res = $db->fetch_object($sqlReq);
        $soc->fetch($res->socid);
        $soc->code_client = -1;
        $soc->code_fournisseur=0;
        if ($isSoc)
        {
            $soc->nom=$company;
        } else {
            $soc->nom=$lastname." ".$firstname;
            $soc->typent_id = 8;
            $soc->forme_juridique_code = 99;
            $soc->capital=0;
        }

        $soc->adresse=$street;
        $soc->cp=$postcode;
        $soc->ville=$city;
        $soc->departement_id = $region;
        $soc->pays_id = $country_id;
        $soc->tel = $telephone;
        $soc->fax=$fax;
        $soc->email=$email;
        $soc->tva_intra=$taxvat;
        $soc->note='Client du site';
        $soc->update();


        //create contact
        require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
        require_once(DOL_DOCUMENT_ROOT."/user/class/user.class.php");
        $requete = "SELECT *
                      FROM babel_magento_contact
                     WHERE magentoid = ".$customer_id;
        $sqlCont = $db->query($requete);
        $resCont = $db->fetch_object($sqlCont);


        $contact = new Contact($db);
        $contact->id = $resCont->contactid;
        $contact->fetch($contact->id);
        $contact->name=$lastname;
        $contact->socid = $soc->id;

        $contact->firstname=$firstname;
        $contact->email = $email;
        $contact->phone = $telephone;
        $contact->phone_pro = $telephone;
        $contact->fax = $fax;
        $contact->cp = $soc->cp;
        $contact->ville = $soc->ville;
        $contact->adresse=$soc->adresse;
        $contact->fk_pays=$soc->pays_id;
        $contact->birthday=$dob;
        $contact->note = $soc->note;
        $contact->priv = 0;

        $contact->update($contact->id);
    }


    public function setSoc($bool)
    {
        $this->isSoc = $bool;
    }

//Listing
    public function cust_list_incId_gt($incId=0)
    {
        $result = $this->call_magento("customer.list",array(array('increment_id' => array('gt'=>$incId))));
        return ($result);
    }
    public function cust_list_updated_gt($incId=0)
    {
        $result = $this->call_magento("customer.list",array(array('updated_at' => array('gt'=>$incId))));
        return ($result);
    }
    public function customer_list()
    {
        $this->magCustomerList = $this->call_magento("customer.list");
        return ($this->magCustomerList);
    }

}