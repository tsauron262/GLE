<?php
require_once(DOL_DOCUMENT_ROOT.'/societe.class.php');
class Cessionnaire extends Societe{

    public $db;
    public function Cessionnaire($db) {
        $this->db=$db;
        $this->Societe($db);
    }

}
?>