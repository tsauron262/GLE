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

//$username = "eos";
//$password = "redalert";
//      $client = new SoapClient('http://127.0.0.1/magentoGLE/magento/api/soap/?wsdl');
      // If soap isn't default use this link instead
      // http://youmagentohost/api/soap/?wsdl
      // If somestuff requires api authentification,
      // we should get session token
//      $session = $client->login($username, $password);
//      $result = $client->call($session, 'customer.list');
//var_dump($result);
//      $result = $client->call($session, 'somestuff.method');
//      $result = $client->call($session, 'somestuff.method', 'arg1');
//      $result = $client->call($session, 'somestuff.method', array('arg1', 'arg2', 'arg3'));
//      $result = $client->multiCall($session, array(
//           array('somestuff.method'),
//           array('somestuff.method', 'arg1'),
//           array('somestuff.method', array('arg1', 'arg2'))
//      ));
      // If you don't need the session anymore

      $mag = new magento_soap($conf);
      $mag->connect();
//      $res = $mag->customer_list();
//
//      $res = $mag->customer_info(1);
//      $res = $mag->sales_list();
//      $res = $mag->sales_info("100000002");
//
//      $res = $mag->prod_cat_get_stock("Sku1");
//
//      $res = $mag->prod_prod_list();
//      $res = $mag->create_customer("JM","LF","me@synopsis-erp.com","redalert"); //res = new user Id

//Create chart openflashChart??

$_REQUEST["ofc"]="data.json";
print <<<CHART

<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"
        codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0"
        width="500"
        height="250" id="graph-2" align="middle">

    <param name="allowScriptAccess" value="sameDomain" />
    <param name="movie" value="open-flash-chart.swf" />
    <param name="quality" value="high" />
    <embed src="open-flash-chart/open-flash-chart.swf"
           quality="high"
           bgcolor="#FFFFFF"
           width="500"
           height="250"
           name="open-flash-chart"
           align="middle"
           allowScriptAccess="sameDomain"
           type="application/x-shockwave-flash"
           pluginspage="http://www.macromedia.com/go/getflashplayer" />
</object>

CHART;

//require_once('Var_Dump.php');
//Var_Dump::displayInit(array('display_mode' => 'HTML4_Table'), array('mode' => 'normal','offset' => 4));
//
//      Var_Dump::display($res);


      $mag->disconnect();



class magento_soap
{
    public $username="eos";
    public $pass="redalert";
    public $client;
    public $session;
    private $MagentoSoapUrl ='http://127.0.0.1/magentoGLE/magento/api/soap/?wsdl';
    public $magCustomerList=array();


    public function magento_soap()
    {

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

    private function call_magento($command,$arr=false)
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
    public function customer_list()
    {
        $this->magCustomerList = $this->call_magento("customer.list");
        return ($this->magCustomerList);
    }
    public function customer_info($custId)
    {
        $arr =array();
        array_push($arr,$custId);
        $result = $this->call_magento("customer.info",$arr);
        return ($result);
    }

    public function sales_list()
    {
        $result = $this->call_magento("sales_order.list");
        return ($result);
    }
    public function sales_info($saleId)
    {
        $arr = array();
        array_push($arr,$saleId);
        $result = $this->call_magento("sales_order.info",$arr);
        return ($result);
    }

    public function prod_prod_list()
    {
        $result = $this->call_magento("catalog_product.list");
        return ($result);
    }

    public function prod_cat_get_stock($prodSkuOrId)
    {
        $arr = array();
        array_push($arr,$prodSkuOrId);
        $result = $this->call_magento("product_stock.list",$arr);
        return ($result);
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


}
?>