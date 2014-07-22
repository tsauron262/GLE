<?php

class partsCart {

    protected $db;
    public $serial;
    public $chronoId;
    public $partsCart = array();
    public $cartRowId = null;
    public $confirmNumber = null;

    public function __construct($db, $serial = null, $chronoId = null) {
        $this->serial = $serial;
        $this->chronoId = $chronoId;
        $this->db = $db;
        $this->loadCartRowId();
    }

    protected function loadCartRowId() {
        if (isset($this->cartRowId))
            return true;

        $sql = 'SELECT `rowid` as id FROM ' . MAIN_DB_PREFIX . 'synopsis_apple_parts_cart WHERE ';
        if (isset($this->serial)) {
            $sql .= '`serial_number` = \'' . $this->serial . '\'';
        } else if (isset($this->chronoId)) {
            $sql .= '`chrono_id` = ' . $this->chronoId;
        } else {
            return false;
        }

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

    public function addToCart($partNumber, $comptiaCode, $comptiaModifier, $qty) {
        $this->partsCart[] = array(
            'partNumber' => $partNumber,
            'comptiaCode' => $comptiaCode,
            'comptiaModifier' => $comptiaModifier,
            'qty' => $qty
        );
    }

    public function setPartsCart(array $partsCart) {
        $this->partsCart = $partsCart;
    }

    public function saveCart() {
        if (!isset($this->serial))
            return '<p class="error">Echec: numéro de série absent</p>';

        if (!isset($this->db))
            return '<p class="error">Impossible d\'accéder à la base de données.</p>';

        if (!$this->loadCartRowId()) {
            $sql = 'INSERT INTO `' . MAIN_DB_PREFIX . 'synopsis_apple_parts_cart` (`serial_number`, `chrono_id`) ';
            $sql .= 'VALUES (';
            $sql .= '"' . $this->serial . '", ';
            $sql .= (isset($this->chronoId) ? '"' . $this->chronoId . '"' : 'NULL');
            $sql .= ')';

            if (!$this->db->query($sql)) {
                return '<p class="error">Echec de l\'enregistrement en base de données<br/>
                        Erreur SQL : ' . $this->db->lasterror() . '</p>';
            }
            $this->cartRowId = $this->db->last_insert_id(MAIN_DB_PREFIX . 'synopsis_apple_parts_cart');
        } else {
            $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'synopsis_apple_parts_cart_detail WHERE `cart_rowid` = ' . $this->cartRowId;
            if (!$this->db->query($sql))
                return '<p class="error">Erreur: ' . $this->db->lasterror() . '</p>';
        }

        if (count($this->partsCart)) {
            $html = '';
            $check = true;
            foreach ($this->partsCart as $part) {
                $sql = 'INSERT INTO `' . MAIN_DB_PREFIX . 'synopsis_apple_parts_cart_detail` ';
                $sql .= '(`cart_rowid`, `part_number`, `comptia_code`, `comptia_modifier`, `qty`)';
                $sql .= 'VALUES (';
                $sql .= $this->cartRowId . ', ';
                $sql .= '"' . $part['partNumber'] . '", ';
                $sql .= '"' . $part['comptiaCode'] . '", ';
                $sql .= '"' . $part['comptiaModifier'] . '", ';
                $sql .= $part['qty'];
                $sql .= ')';

                if (!$this->db->query($sql)) {
                    $html .= '<p class="error">Erreur: ' . $this->db->lasterror() . '</p>';
                    $check = false;
                }
            }
            if ($check)
                return '<p class="confirmation">Le panier a été correctement enregistré (' . count($this->partsCart) . ' produits)</p>';
            else
                return $html;
        } else {
            return '<p class="error">Echec (panier vide ou erreur lors de la récupération des éléments du panier)</p>';
        }
    }

    public function loadCart() {
        $this->loadCartRowId();
        if ($this->cartRowId != "" && $this->cartRowId > 0) {
            $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX . 'synopsis_apple_parts_cart_detail WHERE `cart_rowid` = ' . $this->cartRowId;
            $result = $this->db->query($sql);
            if (isset($result) && $result) {
                $this->partsCart = array();
                while ($part = $this->db->fetch_object($result)) {
                    $this->addToCart($part->part_number, $part->comptia_code, $part->comptia_modifier, $part->qty);
                }
                return true;
            }
        }
        return false;
    }

}

?>
