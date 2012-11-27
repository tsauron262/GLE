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

class magento_soap
{
    public $username;
    public $pass;
    public $client;
    public $session;
    private $MagentoSoapUrl ='http://127.0.0.1/magentoGLE/magento/api/soap/?wsdl';
    public $magCustomerList=array();


    public function magento_soap($conf)
    {
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
    public function connect()
    {
        $this->client = new SoapClient($this->MagentoSoapUrl);
        $this->session = $this->client->login($this->username, $this->pass);
    }
    public function disconnect()
    {
        $this->client->endSession($this->session);
    }

    public function call_magento($command,$arr=false)
    {
        try {
            $result = "";
            if ($arr)
            {
              $result = $this->client->call($this->session, $command,$arr);
            } else {
              $result = $this->client->call($this->session, $command);
            }
          return ($result);
        } catch (Exception $e) {
            echo '<textarea>'.'Exception reçue : ',  $e->getMessage(), "\n".'</textarea>';
            print '<textarea>'.print_r($this->client,true).'</textarea>';
        }

    }


/*
    public function cat_prod_list()
    {
        $result = $this->call_magento("catalog_product.list");
        return ($result);
    }
    public function prod_prod_list()
    {
        $result = $this->call_magento("product.list");
        return ($result);
    }
    public function prod_prod_info($prodId)
    {
        $arr = array();
        array_push($arr,$prodId);
        $result = $this->call_magento("product.info",$arr);
        return ($result);
    }


    public function prod_cat_list()
    {
        $result = $this->call_magento("catalog_category.tree");
        return ($result);
    }
    public function prod_in_cat($catId)
    {
        $arr = array();
        array_push($arr,$catId);
        array_push($arr,1);
        $result = $this->call_magento("category.assignedProducts",$arr);
        return ($result);
    }

    public function prod_cat_get_stock($prodSkuOrId)
    {
        $arr = array();
        array_push($arr,$prodSkuOrId);
        $result = $this->call_magento("product_stock.list",$arr);
        return ($result);
    }

    public function prod_cat_updt_stock($prodSkuOrId,$qty,$is_inStock=1)
    {
//        'qty'=>50, 'is_in_stock'=>1
        $arr = array();
        array_push($arr,$prodSkuOrId);
        array_push($arr,array('qty' => $qty, 'is_in_stock' => $is_inStock));
        $result = $this->call_magento("product_stock.update",$arr);
        return ($result);
    }
*/




}
?>