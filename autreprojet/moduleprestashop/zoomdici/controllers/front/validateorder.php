<?php

if (!defined('_PS_VERSION_'))
    exit;

class zoomdiciValidateOrderModuleFrontController extends ModuleFrontController {

//http://192.168.0.78/~tilito/prestashop/index.php?fc=module&module=zoomdici&controller=validateorder&id_order=18 TODO remove
    public function initContent() {

        global $smarty;
        $id_order = $_GET['id_order'];
        $order = new Order((int) $id_order);

        $script = '<script>var id_order = 0; var id_prods = new Array(); var products = new Array();';
        $html = "<div id='zoneRetour'></div>";

        $is_valid = Db::getInstance()->getValue('SELECT valid FROM ps_orders WHERE id_order=' . $id_order) == '1';
        $is_good_user = Db::getInstance()->getValue('SELECT id_customer FROM ps_orders WHERE id_order=' . $id_order) == $this->context->customer->id;

        $products = $order->getProducts();

        $smarty = array();
        foreach ($products as $product) {
            if ($is_valid and $is_good_user) {
                $script .= "id_prods.push(" . $product['product_id'] . ");";
                $script .= "products.push({id: " . $product['product_id'] . ", qty: " . $product['product_quantity'] . "});";
                $script .='id_order = ' . $order->id . ';';
                $smarty[] = array(
                    'qty' => $product['product_quantity'],
                    'id' => $product['product_id'],
                    'name' => $product['product_name'],
                    'price' => $product['price']
                );

            } elseif (!$is_valid) {
                $html .= '<div class="alert alert-danger"><strong style="font-size: 16px; text-aligne: center">' .
                        '<img src="img/admin/error2.png" style="width: 16px; height: 16px; margin-bottom: 4px"> ' .
                        'Les tickets ne seront disponibles qu\'une fois que le paiement sera effectué.</strong>' .
                        '</div>';
            } elseif (!$is_good_user) {
                $html .= '<p>Piratage</p>';
            }
        }
        $script .= "</script>";
        $this->context->smarty->assign('products', $smarty);
        $this->context->smarty->assign('order_id', $order->id);

        $this->context->smarty->assign(array('html' => ''));
        parent::initContent();
        $this->setTemplate('module:zoomdici/views/templates/front/validateorder.tpl');
        echo $script . $html;
    }

    public function setMedia() {
        parent::setMedia();
        $this->addJS(getcwd() . '/modules/zoomdici/views/js/validate_order.js');
        $this->addCSS(getcwd() . '/modules/zoomdici/views/css/validate_order.css');
    }

}
