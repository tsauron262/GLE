<?php

if (!defined('_PS_VERSION_'))
    exit;

class ZoomDici extends Module {

    public function __construct() {
        $this->name = 'zoomdici';
        $this->tab = 'other';
        $this->version = '1.0';
        $this->author = 'Romain';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
//        $this->bootstrap = true;


        parent::__construct();

        $this->displayName = $this->l('Zoom D\'ici');
        $this->description = $this->l('Gestion billeterie');

        $this->confirmUninstall = $this->l('Êtes vous sûr ?');


        if (!Configuration::get('ZOOM_DICI'))
            $this->warning = $this->l('No name provided');
    }

    public function install() {
        return parent::install() &&
                $this->registerHook('orderConfirmation') &&
                $this->registerHook('orderDetailDisplayed') &&
                Configuration::updateValue('ZOOM_DICI', 'zoomdici');
    }

    public function uninstall() {
        return parent::uninstall() &&
                Configuration::deleteByName('ZOOM_DICI');
    }

    public function hookDisplayOrderConfirmation($params) {
        $this->context->controller->addJS($this->_path . 'views/js/validate_order.js');
        $this->context->controller->addCSS($this->_path . 'views/css/validate_order.css');
        if (isset($params['objOrder']))
            $order = $params['objOrder'];
        else
            $order = $params['order'];
        $products = $order->getProducts();

        $script = '<script>var id_order = 0; var id_prods = new Array(); var products = new Array();';
        $html = "<div id='zoneRetour'></div>";

        foreach ($products as $product) {
            $script .= "id_prods.push(" . $product['product_id'] . ");";
            $script .= "products.push({id: " . $product['product_id'] . ", qty: " . $product['product_quantity'] . "});";
            if (Db::getInstance()->getValue('SELECT valid FROM ps_orders WHERE id_order=' . $product['id_order']) == '1') {
                $script .='id_order = ' . $order->id . ';';
                $this->context->smarty->assign(
                        array(
                            'my_module_name' => Configuration::get('MYMODULE_NAME'),
                            'my_module_link' => $this->context->link->getModuleLink('zoomdici', 'display'),
                            'product' => array(
                                'qty' => $product['product_quantity'],
                                'id' => $product['product_id'],
                                'name' => $product['product_name'],
                                'price' => $product['price']
                            ),
                            'order_id' => $product['id_order'])
                );

                $html .= $this->display(__FILE__, 'zoomdici.tpl');
            } else {
                $html .= '<div class="alert alert-danger"><strong style="font-size: 16px; text-aligne: center">' .
                        '<img src="img/admin/error2.png" style="width: 16px; height: 16px; margin-bottom: 4px"> ' .
                        'Les tickets ne seront disponibles qu\'une fois que le paiement sera effectué.</strong>' .
                        '</div>';
            }
        }
        $script .= "</script>";

        return $script . $html;
    }

    public function hookDisplayOrderDetail($params) {

        $order = $params['order'];

        $html .= $this->display(__FILE__, 'orderdetail.tpl');

        var_dump($params);
        echo "OK";
        $html = '<script>alert(1)</script>';
        return $html;
    }

}
