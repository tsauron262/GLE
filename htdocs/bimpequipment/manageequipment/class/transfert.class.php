<?php

include_once '../../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

include_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

/**
 * @deprecated 
 */
class Transfert {

    private $db;
    private $entrepotIdStart;
    private $entrepotIdEnd;
    private $ligneTransfert;    // ligne transfert
    private $user;

    public function __construct($db, $entrepotIdStart, $entrepotIdEnd, $user) {
        $this->db = $db;
        $this->entrepotIdStart = $entrepotIdStart;
        $this->entrepotIdEnd = $entrepotIdEnd;
        $this->user = $user;
    }

    public function addLignes($prodAndEquipment) {
        $this->ligneTransfert = array();
        foreach ($prodAndEquipment as $ligne) {
            if ($ligne['is_equipment'] == 'true') {
                $ligneObject = new LigneTransfert($this->db, intval($ligne['id_product']), 1, $ligne['serial']);
            } else {
                $ligneObject = new LigneTransfert($this->db, intval($ligne['id_product']), intval($ligne['qty']), '');
            }
            $this->ligneTransfert[] = $ligneObject;
        }
    }

    /**
     * @deprecated
     */
    public function execute() {

        $now = dol_now();
        $codemove = dol_print_date($now, '%y%m%d%H%M%S');
        $label = 'Transférer stock Bimp ' . dol_print_date($now, '%Y-%m-%d %H:%M');
        $this->db->begin();
        $errors = array();
        foreach ($this->ligneTransfert as $ligne) { // Loop on each movement to do
            $errors = BimpTools::merge_array($errors, $ligne->transfert($this->entrepotIdEnd));
        }
        if (sizeof($errors) == 0)
            $this->db->commit();
        return $errors;
    }

}

/**
 * @deprecated 
 */
class LigneTransfert {

    private $db;
    public $id_product;
    public $qty;
    public $serial;

    public function __construct($db, $id_product, $qty, $serial) {
        $this->db = $db;
        $this->id_product = $id_product;
        $this->qty = $qty;
        $this->serial = $serial;
    }

    /**
     * @deprecated
     */
    public function transfert($entrepotIdEnd) {
        $errors = array();

        $product = new Product($this->db);
        $product->fetch($this->id_product);

        if ($this->serial != '') {  // if it is an equipment
            $id_equipment = getIdBySerial($this->db, $this->serial);

            $emplacement = BimpObject::getInstance('bimpequipment', 'BE_Place');
            $emplacement->validateArray(array(
                'id_equipment' => $id_equipment,
                'type' => 2, // type entrepot
                'id_entrepot' => $entrepotIdEnd, // si type = 2
                'infos' => '...',
                'date' => dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S') // date et heure d'arrivée TODO
            ));
            $errors = BimpTools::merge_array($errors, $emplacement->create());
        }
        return $errors;
    }

}

/**
 * @deprecated
 */
function getIdBySerial($db, $serial) {
    $sql = 'SELECT id';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment';
    $sql .= ' WHERE serial="' . $serial . '"';

    $result = $db->query($sql);
    if ($result and mysqli_num_rows($result) > 0) {
        while ($obj = $db->fetch_object($result)) {
            $id = $obj->id;
        }
        return $id;
    }
    return 'Aucun équipment correspond à ce numéro de série';
}
