<?php

if (!defined('_PS_VERSION_'))
    exit;

include 'param.inc.php';

class ZoomDici extends Module {

    public function __construct() {
        $this->install();
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
//        echo "Retour installation du module cron : " . (int) $this->registerHook('actionCronJob') . '<br/>';
        return $this->registerHook('actionCronJob') &&
                $this->registerHook('displayOrderDetail') &&
                $this->registerHook('displayOrderConfirmation') &&
                Configuration::updateValue('ZOOM_DICI', 'zoomdici') && parent::install();
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
//        Hook::exec('actionCronJob');
        $script = 'var base_url ="' . _PS_BASE_URL_ . __PS_BASE_URI__ . '";';
        $script .= 'var id_order ="' . $_GET['id_order'] . '";';
        $this->context->controller->addJS($this->_path . 'views/js/add_link.js');
        return '<script>' . $script . '</script>';
    }

    public function hookActionCronJob() {
        $id_lang = Context::getContext()->language->id;
        $product_obj = new Product();
        $products = $product_obj->getProducts($id_lang, 0, 0, 'id_product', 'DESC');
        foreach ($products as $product_array) {
            $product = new Product($product_array['id_product']);
            $features = $product->getFrontFeatures($id_lang);
            if ($product->active) {
                foreach ($features as $feature) {
                    if ($feature['id_feature'] == ID_FEATURE_DATE_END_SALE) {
                        preg_match_all('!\d+!', $feature['value'], $matches);
                        $today = getdate();
                        if (
                                (int) $matches[0][0] == (int) $today['year'] and
                                (int) $matches[0][1] == (int) $today['mon'] and
                                (int) $matches[0][2] == (int) $today['mday'] and
                                (int) $matches[0][3] == (int) $today['hours']) {
                            $product->active = false;
                            $product->update();
                        }
                        break;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Information sur la fréquence des taches cron du module
     * Granularité maximume à l'heure
     */
    public function getCronFrequency() {
        return array(
            'hour' => -1, // -1 equivalent à * en cron normal
            'day' => -1,
            'month' => -1,
            'day_of_week' => -1
        );
    }

}
