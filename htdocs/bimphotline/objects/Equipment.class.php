<?php

class Equipment extends BimpObject
{

    public function getContratsArray()
    {
        $id_soc = isset($this->data['id_soc']) ? $this->data['id_soc'] : 0;

        if (!$id_soc) {
            return array(
                0 => '<span class="warning">Aucun contrat</span>'
            );
        }

        $rows = $this->db->getRows('contrat', '`fk_soc` = ' . (int) $id_soc, null, 'array', array('rowid', 'ref'));

        $return = array(
            0 => '<span class="warning">Aucun contrat</span>',
        );

        if (!is_nan($rows)) {
            foreach ($rows as $r) {
                $return[(int) $r['rowid']] = $r['ref'];
            }
        }

        return $return;
    }
}
