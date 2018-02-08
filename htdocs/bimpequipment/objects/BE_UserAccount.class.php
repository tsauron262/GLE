<?php

class BE_UserAccount extends BimpObject
{

    public function getClient_contactsArray()
    {
        $contacts = array(
            '' => ''
        );
        $id_client = $this->getData('id_client');
        if (!is_null($id_client) && $id_client) {
            $where = '`fk_soc` = ' . (int) $id_client;
            $rows = $this->db->getRows('socpeople', $where, null, 'array', array('rowid', 'firstname', 'lastname'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $contacts[(int) $r['rowid']] = BimpTools::ucfirst($r['firstname']) . ' ' . strtoupper($r['lastname']);
                }
            }
        }

        return $contacts;
    }
}
