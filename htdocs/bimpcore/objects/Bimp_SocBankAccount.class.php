<?php

class Bimp_SocBankAccount extends BimpObject
{

    public function displayRib()
    {
        $html = '';

        $html .= '<table class="objectSubList">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Banque</th>';
        $html .= '<th>Guichet</th>';
        $html .= '<th>N°</th>';
        $html .= '<th>Clé</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';
        $html .= '<tr>';
        $html .= '<td>' . $this->getData('code_banque') . '</td>';
        $html .= '<td>' . $this->getData('code_guichet') . '</td>';
        $html .= '<td>' . $this->getData('number') . '</td>';
        $html .= '<td>' . $this->getData('cle_rib') . '</td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }
    
    // return boolean:
    
    public function isValid(Array &$errors):bool {

        $rib = Array(
            "banque"        => (int) $this->getData('code_banque'),
            "agence"        => (int) $this->getData('code_guichet'),
            "compte"        => (string) $this->getData('number'),
            "compte_strtr"  => (int) strtr(strtoupper($this->getData("number")), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", '12345678912345678923456789'),
            "clerib"        => (int) $this->getData('cle_rib')
        );
        
        $cbX89 = 89 * $rib['banque'];
        $cgX15 = 15 * $rib['agence'];
        $ncX3 = 3 * $rib['compte_strtr'];
        
        $verif_key = (int) 97 - (($cbX89 + $cgX15 + $ncX3) % 97);
        
        if((int) $verif_key !== $rib['clerib']) {
            $errors[] = "Le RIB n'est pas valide";
            return (bool) 0;
        }
        
        return (bool) 1;
    }

    // overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            // Le create du dol_object n'insert pas les valeurs...
            $errors = $this->update($warnings, $force_create);
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $def = (int) $this->getData('default_rib');

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if ($def) {
                $this->db->update($this->getTable(), array(
                    'default_rib' => 0
                        ), 'fk_soc = ' . (int) $this->getData('fk_soc') . ' AND type = \'' . $this->getData('type') . '\' AND rowid != ' . (int) $this->id);
            }
        }

        return $errors;
    }
}
