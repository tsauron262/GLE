<?php

class revision_merlot {

    public $db;

    public function revision_merlot($DB) {
        $this->db = $DB;
        $this->nom = 'Merlot';
        $this->description = 'R&eacute;vision alphab&eacute;tique';
    }

    public function convert_revision($rev) {
        return ($this->num2alpha($rev));
    }

    public function inv_convert_revision($rev) {
        return ($this->alpha2num($rev));
    }

    private function num2alpha($n) {
        for ($r = ""; $n >= 0; $n = intval($n / 26) - 1)
            $r = chr($n % 26 + 0x41) . $r;
        return $r;
    }

    /*
     * Convert a string of uppercase letters to an integer.
     */

    private function alpha2num($a) {
        if($a == "0")
            $a = "A";
        $r = 0;
        $l = strlen($a);
        for ($i = 0; $i < $l; $i++) {
            $r += pow(26, $i) * (ord($a[$l - $i - 1]) - 0x40);
        }
        return $r - 1;
    }

}

?>