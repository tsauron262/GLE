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
class magento_sales extends magento_soap {

    function magento_sales($conf) {
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
    public function sales_list_incId_gt($incId=0)
    {
        $result = $this->call_magento("sales_order.list",array(array('increment_id' => array('gt'=>$incId))));
        return ($result);
    }
    public function sales_list_updated_gt($incId=0)
    {
        $result = $this->call_magento("sales_order.list",array(array('updated_at' => array('gt'=>$incId))));
        return ($result);
    }
}
?>