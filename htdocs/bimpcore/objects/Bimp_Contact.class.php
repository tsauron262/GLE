<?php

class Bimp_Contact extends BimpObject
{

    public static $civilities_list = null;

    public function getName()
    {
        $lastname = $this->getData('lastname');
        $firstname = $this->getData('firstname');

        return $lastname . (!is_null($firstname) && $firstname ? ' ' . $firstname : '');
    }

    public function getCivilitiesArray()
    {
        if (is_null(self::$civilities_list)) {
            $sql = 'SELECT `rowid` as id, `label` FROM ' . MAIN_DB_PREFIX . 'c_civility WHERE `active` = 1';
            $rows = $this->db->executeS($sql, 'array');

            self::$civilities_list = array();
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$civilities_list[(int) $r['id']] = $r['label'];
                }
            }
        }

        return self::$civilities_list;
    }

    public function displayCountry()
    {
        $id = $this->getData('fk_pays');
        if (!is_null($id) && $id) {
            return $this->db->getValue('c_country', 'label', '`rowid` = ' . (int) $id);
        }
        return '';
    }

    public function displayDepartement()
    {
        $fk_dep = (int) $this->getData('fk_departement');
        if ($fk_dep) {
            return $this->db->getValue('c_departements', 'nom', '`rowid` = ' . $fk_dep);
        }

        return '';
    }
    
    public function getDolObjectUpdateParams()
    {
        global $user;
        if ($this->isLoaded()) {
            return array($this->id, $user);
        }
        
        return array(0, $user);
    }
}
