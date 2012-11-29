<?php

class revision_aligote {
    public $db;
    public function revision_aligote($DB) {
        $this->db = $DB;
        $this->nom = 'Aligote';
        $this->description = 'R&eacute;vision num&eacute;rique sur 4 chiffres';
    }
    public function convert_revision($rev)
    {
        $conv = sprintf('%04d',$rev);
        return ($conv);
    }
    public function inv_convert_revision($rev)
    {
        return $rev;
    }
}
?>