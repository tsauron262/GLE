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
