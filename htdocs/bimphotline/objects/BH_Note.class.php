<?php

class BH_Note extends BimpObject
{

    public static $visibilities = array(
        1 => 'Membres Bimp et client',
        2 => 'Membres BIMP',
        3 => 'Auteur seulement'
    );

    public function getInterventionsArray()
    {
        $array = array(
            0 => '-'
        );

        $id_parent = $this->getData('id_ticket');

        if (!is_null($id_parent) && $id_parent) {
            $rows = $this->db->getValues('bh_inter', 'id', '`id_ticket` = ' . (int) $id_parent);
            if (!is_null($rows)) {
                foreach ($rows as $id_inter) {
                    $array[(int) $id_inter] = 'Intervention n°' . $id_inter;
                }
            }
        }

        return $array;
    }
}
