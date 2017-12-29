<?php

class Equipment extends BimpObject
{

    public static $warranty_types = array(
        0 => ' - ',
        1 => 'Type 1',
        2 => 'Type 2'
    );

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

    public function defaultDisplayContratsItem($id_contrat)
    {
        $contrat = BimpObject::getDolInstance('contrat');
        if ($contrat->fetch((int) $id_contrat) > 0) {
            $label = $contrat->ref;
            if (isset($contrat->societe) && is_a($contrat->societe, 'Societe')) {
                $label .= ' (client: ' . $contrat->societe->nom . ')';
            } elseif (isset($contrat->socid) && $contrat->socid) {
                global $db;
                $client = new Societe($db);
                if ($client->fetch($contrat->socid) > 0) {
                    $label .= ' (client: ' . $client->nom . ')';
                }
                unset($client);
            }
            unset($contrat);
            return $label;
        }
        return BimpRender::renderAlerts('Le contrat d\'ID ' . $id_contrat . ' semble ne plus exister');
    }
}
