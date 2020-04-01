<?php

class BS_Remote_Token extends BimpObject
{
//    public function validate()
//    {
//        $errors = parent::validate();
//
//        if (!count($errors)) {
//            $product = $this->getChildObject('product');
//
//            if (!BimpObject::objectLoaded($product)) {
//                $errors[] = 'Le produit d\'ID ' . $this->getData('id_product') . ' n\'existe pas';
//            } else {
//                if ((int) $product->getData('fk_product_type') !== 0) {
//                    $errors[] = 'Vous ne pouvez pas sélectionner un produit de type "' . Bimp_Product::$product_type[(int) $product->getData('fk_product_type')]['label'] . '"';
//                } elseif ($product->isSerialisable()) {
//                    $errors[] = 'Produit sérialisable. Veuillez sélectionner un équipement';
//                }
//            }
//        }
//
//        return $errors;
//    }

    public function create(&$warnings = array(), $force_create = false)
    {
        global $user;
        $errors = array();
        
        $port = $this->getData("port");
        if($port < 1){
            $portMin = 25901;
            $portMax = 26899;
            $port = $portMin;
            $sql = $this->db->db->query("SELECT port FROM `llx_bs_remote_token` WHERE port >= ".$portMin." AND port <= ".$portMax." GROUP BY id DESC LIMIT 0,1");
            if($this->db->db->num_rows($sql) > 0){
                $ln = $this->db->db->fetch_object($sql);
                $port = $ln->port+1;
            }
            if($port > $portMax)
                $port = $portMin;
            $this->set('port', $port);
        }
        if($this->getData("token") == '')
            $this->set('token', $this->getToken());
        if($this->getData("mdp") == '')
            $this->set('mdp', $this->genererChaineAleatoire());
        
        if($this->getData('id_user') < 1)
            $this->set('id_user', $user->id);
        if($this->getData('date_valid') == '')
            $this->set('date_valid', date('Y-m-d H:i:s', strtotime(' + 30 MINUTES')));
        if(!count($errors))
            $errors = parent::create ($warnings, $force_create);

        return $errors;
    }
    
    public function genererChaineAleatoire($longueur = 8){
     $caracteres = '23456789abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ';
     $longueurMax = strlen($caracteres);
     $chaineAleatoire = '';
     for ($i = 0; $i < $longueur; $i++)
        $chaineAleatoire .= $caracteres[rand(0, $longueurMax - 1)];
     return $chaineAleatoire;
    }
    
    public function getToken($nb = 0){
        if($nb > 1000)
            die("boucle454235435");
        $tokenMin = 100001;
        $tokenMax = 999999;
        $token = rand($tokenMin, $tokenMax);
        $sql = $this->db->db->query("SELECT port FROM `llx_bs_remote_token` WHERE token = '".$token."' AND date_valid >= DATE_SUB(now(),INTERVAL 1 DAY) GROUP BY id DESC LIMIT 0,1");
        if($this->db->db->num_rows($sql) > 0){
            $token = $this->getToken($nb+1);
        }
        return $token;
    }
//
//    public function update(&$warnings = array(), $force_update = false)
//    {
//        $errors = array();
//
//        $pret = $this->getParentInstance();
//        if (!BimpObject::objectLoaded($pret)) {
//            $errors[] = 'ID du prêt absent';
//            return $errors;
//        }
//
//        $init_qty = (float) $this->getInitData('qty');
//
//        $errors = parent::update($warnings, $force_update);
//
//        if (!count($errors)) {
//            if (!(int) $pret->getData('returned')) {
//                $qty_diff = (float) $this->getData('qty') - $init_qty;
//
//                if ($qty_diff) {
//                    $stock_errors = array();
//                    if ($qty_diff > 0) {
//                        $stock_errors = $this->decreaseStock($qty_diff, 'Mise à jour des quantités');
//                    } else {
//                        $stock_errors = $this->increaseStock(abs($qty_diff), 'Mise à jour des quantités');
//                    }
//
//                    if (count($stock_errors)) {
//                        $warnings[] = BimpTools::getMsgFromArray($stock_errors, 'Erreurs lors de la correction des stocks');
//                    }
//                }
//            }
//        }
//
//        return $errors;
//    }
//
//    public function delete(&$warnings = array(), $force_delete = false)
//    {
//        $errors = array();
//
//        $pret = $this->getParentInstance();
//        if (BimpObject::objectLoaded($pret)) {
//            if (!(int) $pret->getData('returned')) {
//                $stock_errors = $this->increaseStock(null, 'Suppression du prêt');
//                if (count($stock_errors)) {
//                    $errors[] = BimpTools::getMsgFromArray($stock_errors, 'Erreurs lors de la correction des stocks. Prêt de produit non supprimé');
//                }
//            }
//        } else {
//            $errors[] = 'ID du prêt absent';
//        }
//
//        if (!count($errors)) {
//            $errors = parent::delete($warnings, $force_delete);
//        }
//
//        return $errors;
//    }
}
