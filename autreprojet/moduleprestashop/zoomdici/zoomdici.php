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
//                $this->registerHook('actionOrderHistoryAddAfter') &&
                $this->registerHook('header') &&
                Configuration::updateValue('ZOOM_DICI', 'zoomdici');
    }

    public function uninstall() {
        return parent::uninstall() &&
                Configuration::deleteByName('ZOOM_DICI');
    }

    public function hookDisplayOrderConfirmation($params) {
        $this->context->controller->addJS($this->_path . 'views/js/validate_order.js');
        $this->context->controller->addCSS($this->_path . 'views/css/validate_order.css');
        $products = $params['order']->getProducts();

        $html = '';

        foreach ($products as $product) {
            if (Db::getInstance()->getValue('SELECT valid FROM ps_orders WHERE id_order=' . $product['id_order']) == '1') {
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
            }
        }
        return $html;
    }

    public function hookHeader() {
        $this->context->controller->addJS(($this->_path) . 'js/zoomdici.js');
    }

    public function hookDisplayHeader() {
        $this->context->controller->addCSS($this->_path . 'css/zoomdici.css', 'all');
    }

}
