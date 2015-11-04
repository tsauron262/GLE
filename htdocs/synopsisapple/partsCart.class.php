<?php

class partsCart {

    protected $db;
    public $serial;
    public $chronoId;
    public $partsCart = array();
    public $cartRowId = null;
    public $confirmNumber = null;
    public $errors = array();

    public function __construct($db, $serial = null, $chronoId = null) {
        $this->serial = $serial;
        $this->chronoId = $chronoId;
        $this->db = $db;
        $this->loadCartRowId();
    }

    public function addError($msg) {
        $this->errors[] = $msg;
    }

    public function displayErrors() {
        $html = '';
        foreach ($this->errors as $error) {
            $html .= '<p class="error">' . $error . '</p>';
        }
        $this->errors = array();
        return $html;
    }

    public function addThisToPropal($propal) {
        global $db, $langs;

        $this->fraisP = -1;

        if($propal->socid > 0){
            $soc = new Societe($db);
            $soc->fetch($propal->socid);
            $remise = $soc->remise_percent;
        }
        else {
            $remise = 0;
        }
        
        foreach ($this->partsCart as $part) {
            $prix = $this->convertPrix($part['stockPrice'], $part['partNumber'], $part['partDescription']);
            $propal->addline($part['partNumber'] . " - " . $part['partDescription'], round($prix, 2), $part['qty'], "20", 0, 0, 0, $remise, 'HT', 0, 0, 0, 0, 0, 0, 0, round($part['stockPrice'],2));
        }

        if ($this->fraisP > 0) {
            $qte = $this->fraisP;
            $prod = new Product($db);
            $prod->fetch(3436);
            require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.product.class.php");
            $prodF = new ProductFournisseur($db);
            $prodF->find_min_price_product_fournisseur($prod->id, $qte);
            $propal->addline($prod->description, round($prod->price,2), $qte, ($prod->tva_tx > 0) ? $prod->tva_tx : 0, 0, 0, $prod->id, $remise, 'HT', null, null, null, null, null, null, $prodF->product_fourn_price_id, round($prodF->fourn_price, 2));
        }




        $propal->fetch($propal->id);
        require_once(DOL_DOCUMENT_ROOT . "/core/modules/propale/modules_propale.php");
        $propal->generateDocument("azurSAV", $langs);
//        propale_pdf_create($db, $propal, null, $langs);
    }

    private function convertPrix($prix, $ref, $desc) {
        $coefPrix = 1;
        $constPrix = 0;
        $tabCas1 = array("DN661", "FD661", "NF661", "RA", "RB", "RC", "RD", "RE", "RG", "SA", "SB", "SC", "SD", "SE", "X661", "XB", "XC", "XD", "XE", "XF", "ZD661", "ZK661");
        $tabCas2 = array("SVC,IPOD", "Ipod nano");
        $tabCas3 = array("661");
        $tabCas4 = array("iphone", "BAT,IPHONE", "SVC,IPHONE");

        $cas = 0;
        foreach ($tabCas1 as $val)
            if (stripos($ref, $val) === 0)
                $cas = 1;
        foreach ($tabCas2 as $val)
            if (stripos($desc, $val) === 0)
                $cas = 1;
        foreach ($tabCas3 as $val)
            if (stripos($ref, $val) === 0)
                $cas = 2;
        if ($cas == 2)
            foreach ($tabCas4 as $val)
                if (stripos($desc, $val) === 0)
                    $cas = 3;

        if ($cas == 0 || $cas == 2) {
            if ($prix > 300)
                $coefPrix = 0.8;
            elseif ($prix > 150)
                $coefPrix = 0.7;
            elseif ($prix > 50)
                $coefPrix = 0.6;
            else {
                $coefPrix = 0.6;
                $constPrix = 10;
            }
        } elseif ($cas == 1) {
            $constPrix = 45;
        } elseif ($cas == 3) {
            $constPrix = 45;
        }
        $prix = (($prix + $constPrix) / $coefPrix);

        if (($cas == 1 || $cas == 3) && $this->fraisP < 1)
            $this->fraisP = 0;
        else
            $this->fraisP = 1;

        return $prix;
    }

    protected function loadCartRowId() {
        if (isset($this->cartRowId))
            return true;

        $sql = 'SELECT `rowid` as id FROM ' . MAIN_DB_PREFIX . 'synopsis_apple_parts_cart WHERE ';
        if (isset($this->chronoId)) {
            $sql .= '`chrono_id` = ' . $this->chronoId;
        } else if (isset($this->serial)) {
            $sql .= '`serial_number` = \'' . $this->serial . '\'';
        } else {
            return false;
        }
//die($sql);
        if (isset($this->db)) {
            $result = $this->db->query($sql);
            if ($this->db->num_rows($result) > 0) {
                $cartRow = $this->db->fetch_object($result);
                if (isset($cartRow) && $cartRow) {
                    $this->cartRowId = $cartRow->id;
                    return true;
                }
            }
        }
        return false;
    }

    public function addToCart($partNumber, $comptiaCode, $comptiaModifier, $qty, $componentCode, $partDescription, $stockPrice) {
        $this->partsCart[] = array(
            'partNumber' => $partNumber,
            'comptiaCode' => $comptiaCode,
            'comptiaModifier' => $comptiaModifier,
            'qty' => $qty,
            'componentCode' => $componentCode,
            'partDescription' => $partDescription,
            'stockPrice' => $stockPrice
        );
    }

    public function setPartsCart(array $partsCart) {
        $this->partsCart = $partsCart;
    }

    public function saveCart() {
        if (!isset($this->serial) || !isset($this->chronoId)) {
            $this->addError('Echec de l\'enregistrement: numéro de série ou chronoId absent');
            return false;
        }

        if (!isset($this->db)) {
            $this->addError('Impossible d\'accéder à la base de données.');
            return false;
        }

        if (!$this->loadCartRowId()) {
            $sql = 'INSERT INTO `' . MAIN_DB_PREFIX . 'synopsis_apple_parts_cart` (`serial_number`, `chrono_id`) ';
            $sql .= 'VALUES (';
//            $sql .= '123456, 23456';
            $sql .= (isset($this->serial) ? '"' . $this->serial . '"' : '') . ', ';
            $sql .= (isset($this->chronoId) ? '"' . $this->chronoId . '"' : 'NULL');
            $sql .= ')';

            if (!$this->db->query($sql)) {
                $this->addError('Echec de l\'enregistrement en base de données<br/>
                        Erreur SQL : ' . $this->db->lasterror());
                return false;
            }
            $this->cartRowId = $this->db->last_insert_id(MAIN_DB_PREFIX . 'synopsis_apple_parts_cart');
        } else {
            $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'synopsis_apple_parts_cart_detail WHERE `cart_rowid` = ' . $this->cartRowId;
            if (!$this->db->query($sql)) {
                $this->addError('Erreur SQL: ' . $this->db->lasterror());
                return false;
            }
        }

        if (count($this->partsCart)) {
            $check = true;
            foreach ($this->partsCart as $part) {
                $sql = 'INSERT INTO `' . MAIN_DB_PREFIX . 'synopsis_apple_parts_cart_detail` ';
                $sql .= '(`cart_rowid`, `part_number`, `comptia_code`, `comptia_modifier`, `qty`, `componentCode`, `partDescription`, `stockPrice`)';
                $sql .= 'VALUES (';
                $sql .= $this->cartRowId . ', ';
                $sql .= '"' . addslashes($part['partNumber']) . '", ';
                $sql .= '"' . addslashes($part['comptiaCode']) . '", ';
                $sql .= '"' . addslashes($part['comptiaModifier']) . '", ';
                $sql .= '"' . addslashes($part['qty']) . '", ';
                $sql .= '"' . addslashes($part['componentCode']) . '", ';
                $sql .= '"' . addslashes($part['partDescription']) . '", ';
                $sql .= '"' . addslashes($part['stockPrice']) . '"';
                $sql .= ')';

                if (!$this->db->query($sql)) {
                    $this->addError('Echec de l\'enregistrement d\'un élément du panier.<br/>Erreur SQL: ' . $this->db->lasterror());
                    $check = false;
                }
            }
            return $check;
        } else {
            $this->addError('Echec de l\'enregistrement du panier (panier vide ou erreur lors de la récupération des éléments du panier)');
            return false;
        }
    }

    public function loadCart() {
        $this->loadCartRowId();
        if ($this->cartRowId > 0) {
            $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX . 'synopsis_apple_parts_cart_detail WHERE `cart_rowid` = ' . $this->cartRowId;
            $result = $this->db->query($sql);
            if (isset($result) && $result) {
                $this->partsCart = array();
                while ($part = $this->db->fetch_object($result)) {
                    $this->addToCart($part->part_number, $part->comptia_code, $part->comptia_modifier, $part->qty, $part->componentCode, $part->partDescription, $part->stockPrice);
                }
                return true;
            }
        }
        return false;
    }

    public function getJsScript($prodId) {
        $script = '';
        if (count($this->partsCart)) {
            $script = 'if (GSX.products[' . $prodId . ']) {' . "\n";
            $jsCart = 'GSX.products[' . $prodId . '].cart';
            foreach ($this->partsCart as $part) {
                $script .= $jsCart . '.onPartLoad(\'' . addslashes($part['componentCode']) . '\', ';
                $script .= '\'' . addslashes($part['partDescription']) . '\', ';
                $script .= '\'' . addslashes($part['partNumber']) . '\', ';
                $script .= '\'' . addslashes($part['comptiaCode']) . '\', ';
                $script .= '\'' . addslashes($part['comptiaModifier']) . '\', ';
                $script .= '\'' . addslashes($part['qty']) . '\', ';
                $script .= '\'' . addslashes($part['stockPrice']) . '\');' . "\n";
            }
            $script .= '}' . "\n";
        }
        return $script;
    }

}

?>
