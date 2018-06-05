<?php

if (!defined('_PS_VERSION_'))
    exit;

class zoomdiciValidateOrderModuleFrontController extends ModuleFrontController {

    public function initContent() {
        parent::initContent();
        $this->setTemplate('module:zoomdici/views/templates/front/validateorder.tpl');
        global $smarty;
        $id_order = $_GET['id_order'];
        self::$smarty->assign('id_order', $id_order);
        $products = Db::getInstance()->executeS(''
                . 'SELECT `product_id`, `product_quantity`'
                . ' FROM `' . _DB_PREFIX_ . 'order_detail`'
                . ' WHERE `id_order`=' . $id_order);
        self::$smarty->assign('products', $products);
    }

    public function setMedia() {
        parent::setMedia();
        $this->addJS(getcwd() . '/modules/zoomdici/views/js/validate_order.js');
    }

}
