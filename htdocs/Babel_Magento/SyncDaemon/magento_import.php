<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 19 juil. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : magento_import.php
  * GLE-1.0
  */
  //receive a call from syncDaemon or wgatever
  //<magentoCall>
  //  <access>
  //   <login>
  //        md5Hash
  //   </login>
  //   <pass>
  //        md5Hash
  //   </pass>
  //  </access>
  //  <message>
  //        syncspool
  //  </message>
  //</magentoCall>

  $get = urldecode($_REQUEST['callSync']);

  $get = "<magentoCall>
    <access>
     <login>
          md5Hash
     </login>
     <pass>
          md5Hash
     </pass>
    </access>
    <message>
          syncspool
    </message>
  </magentoCall>";

$alllog = "/tmp/importGLE.log";
$request = print_r($_REQUEST,true);
file_put_contents($alllog,$request);

//TODO :
//disable session auth
//
//tellthat I'm running
//si other running => sleep and wait 10 times
//commande facture paiement
//customer delete
//product delete
//check categories
//

require_once('../../main.inc.php');
require_once('../magento_sales.class.php');
require_once('../magento_customer.class.php');
require_once('../magento_product.class.php');
global $langs;
$langs->load("synopsisGene@Synopsis_Tools");
$langs->load('companies');
$magSal  = new magento_sales($conf);
$magSal->connect();
$magCust  = new magento_customer($conf);
$magCust->client = $magSal->client;
$magCust->session = $magSal->session;
$magProd  = new magento_product($conf);
$magProd->client= $magSal->client;
$magProd->session= $magSal->session;

 $db1 = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,"gleMagentoSpool",$conf->db->port);

  $doc = new DOMDocument();
  $res = $doc->loadXML($get);
  if ($res)
  {

      $login = $doc->getElementsByTagName("login")->item(0)->firstChild->nodeValue;
      $pass = $doc->getElementsByTagName("pass")->item(0)->firstChild->nodeValue;
      $message = $doc->getElementsByTagName("message")->item(0)->firstChild->nodeValue;
      $message = trim($message);
      $pass = trim($pass);
      $login = trim($login);
        if ($message == "syncspool")
        {
            //Verify user
            $requete = "SELECT *
                          FROM ".MAIN_DB_PREFIX."user
                         WHERE md5(login) = '".$login."'
                           AND md5(pass) = '".$pass."' ";

            $sql = $db->query($requete);
            //if ($db->num_rows($sql) > 0)
            if (true)
            {
                //read spool
                $requete = "SELECT *
                              FROM spool
                             WHERE status = 0
                          ORDER BY mode ";
                $sql1= $db1->query($requete);
                if ($db1->num_rows($sql1) > 0)
                {
                    while ($res=$db->fetch_object($sql1))
                    {
                        //set status
                        //updateStatus(1,$res->id,$db1);
                        //get mode
                        $mode = $res->mode;
                        //get type
                        $type= $res->magento_type;
                        //get magento Id
                        $magento_id = $res->magento_id;
                        //switch type
                        switch($type)
                        {
                            case 'sales':
                            {
                                //Get info on sales on magento
                                $prodInfo=$magSal->sales_info($magento_id);
                                print print_r($prodInfo,true);

                                switch($mode)
                                {
                                    case 0:
                                    {
                                        //new order
                                        require_once('../../commande/class/commande.class.php');
                                        require_once('../../societe.class.php');
                                        $commande = new Commande($db);
                                        //create commande
                                        //need socid
                                        $magento_custId = $prodInfo["customer_id"];

                                        $requete = "SELECT * FROM babel_magento_soc WHERE magentoid = ".$magento_custId;
                                        $sql = $db->query($requete);
                                        $res = $db->fetch_object($sql);
                                        $gleSocId = $res->socid;
                                        $commande->socid = $gleSocId;
                                        $soc = new Societe($db);
                                        $soc->fetch($gleSocId);
//                                        $commande->ref = $commande->getNextNumRef();
                                        $commande->date_commande = date('d/m/Y');
                                        $commande->source = 1; //Internet
                                        $commande->note = "Commande du site ";
                                        $commande->note_public = "Commande du site, ref Magento ".$prodInfo['increment_id'];
                                        $commande->cond_reglement_id = 1;
                                        //$commande->mode_reglement_id //TODO

                                        //ad livraison:
//                                        [shipping_address] => Array
//        (
//            [increment_id] =>
//            [parent_id] => 1
//            [store_id] =>
//            [created_at] => 2009-07-12 15:47:04
//            [updated_at] => 2009-07-12 15:47:04
//            [is_active] => 1
//            [region_id] => 274
//            [address_type] => shipping
//            [firstname] => demo
//            [lastname] => demo
//            [company] =>
//            [street] => demo address
//            [city] => demo
//            [region] => Hauts-de-Seine
//            [postcode] => 13100
//            [country_id] => FR
//            [telephone] => 0123456789
//            [fax] =>
//            [address_id] => 2
//        )
                                        //items de la commande
                                        //
                                        foreach($prodInfo['items'] as $key=>$val)
                                        {
                                            require_once('../../product.class.php');
                                            $prod = new Product($db);
                                            $mag_prod_id = $val['product_id'];
                                            $prod->fetch_magento($mag_prod_id);

                                            $qty = $val['qty_ordered'];
                                            $CommLigne = new CommandeLigne($this->db);
                                            $CommLigne->libelle           = $prod->libelle;
                                            $CommLigne->desc              = preg_replace('/\[[\w]*?\]/','',$prod->description);
                                            $CommLigne->price             = $prod->price;
                                            $CommLigne->subprice          = $prod->price_base_type;
                                            $CommLigne->tva_tx            = $prod->tva_tx;
                                            $CommLigne->qty               = $qty;
                                            $CommLigne->fk_remise_except  = 0;
                                            $CommLigne->remise_percent    = 0;
                                            $CommLigne->fk_product        = $prod->id;

                                            $commande->lines[]=$CommLigne;
                                        }
                                        //frais de livraison

                                        //payement



                                    }
                                    break;
                                    case 1:
                                    {
                                        //update order
                                    }
                                }
                            }
                            break;
                            case 'prod':
                            {
                                global $langs;
                                //Get info on customer on magento
                                $prodInfo=$magCust->product_info($magento_id);

                                //Get info on prod on magento
                                switch($mode)
                                {
                                    case 0:
                                    {
                                        //new prod
                                        $magProd->createProdGle($prodInfo,$db);
                                    }
                                    break;
                                    case 1:
                                    {
                                        //update prod
                                        $magProd->updateProdGle($prodInfo,$db);
                                    }
                                }
                            }
                            break;
                            case 'cust':
                            {
                                global $langs;
                                //Get info on customer on magento
                                $custInfo=$magCust->customer_info($magento_id);
                                $addInfo=$magCust->customer_address($magento_id);
                                switch($mode)
                                {
                                    case 0:
                                    {
                                        //new cust
                                        $magCust->createCustomerGle($custInfo,$addInfo,$db);
                                    }
                                    break;
                                    case 1:
                                    {
                                        //update cust
                                        $magCust->updateCustomerGle($custInfo,$addInfo,$db);
                                    }
                                }
                            }
                            break;

                        }
                        //get product/sales/cust/cat info
                        //insert dans GLE si mode 0
                        //update dans GLE si mode 1

                    }
                }

                //Attn au statut :> si statut 0 null, 1 processing , 2 done aka delete, 3 ou + => error

              //get syncdb datas

              //get sales
              //connect to sync b
              // set status => 1
              // create order
              // si OK set status => 2
              // si KO set status => 3 => manuel :(
              //delete from spooldb

              //get products
              //update gle
              //delete from spooldb
              //get customers
              //update gle
              //delete from spooldb
              //get orders
              //update gle
              //delete from spooldb

              //Process other info like sales update , paiement ...

            }

        }

//    sleep(120);

//



}
function updateStatus($newStatus,$id,$db)
{
    $requete = "UPDATE spool SET status = $newStatus WHERE id = $id";
    return($db->query($requete));
}

?>
