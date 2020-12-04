<?php

class BS_Note extends BimpObject
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
            $rows = $this->db->getValues('bs_inter', 'id', '`id_ticket` = ' . (int) $id_parent);
            if (!is_null($rows)) {
                foreach ($rows as $id_inter) {
                    $array[(int) $id_inter] = 'Intervention n°' . $id_inter;
                }
            }
        }

        return $array;
    }

    public function canClientView()
    {
        return 1;
    }

    public function canClientDelete()
    {
        return $this->canClientEdit();
    }

    public function canClientEdit()
    {
        global $userClient;
        if ($this->canClientCreate() && $this->getData('id_user_client') == $userClient->id) {
            $list_of_note_for_this_ticket = $this->getList(Array('id_ticket' => $_REQUEST['id']));
            $good_array = array('id' => 0, 'date' => '2000-01-01');
            foreach ($list_of_note_for_this_ticket as $note) {
                if (strtotime($note['date_create']) > strtotime($good_array['date'])) {
                    $good_array = array('id' => $note['id'], 'date' => $note['date_create']);
                }
            }

            if ($this->id == $good_array['id']) {
                return 1;
            }
        } elseif (!$this->isLoaded()) {
            return 1;
        }
        return 0;
    }

    public function canClientCreate()
    {
        $parent = $this->getParentInstance();
        if ($parent->getData('status') < 999) {
            return 1;
        }
        return 0;
    }

    public function canCreate()
    {
        return 1;
    }

    public function canEdit()
    {
        if (BimpTools::getContext() == 'public') {
            return 1;
        }
        return $this->is_a_note_of_client() ? 0 : 1;
    }

    public function canDelete()
    {
        return 1;
    }

    public function getListFilterNotesInterface()
    {
        $parent = $this->getParentInstance();
        return Array(
            Array(
                'name'   => 'id_ticket',
                'filter' => $parent->getData('id')
            ),
            Array(
                'name'   => 'visibility',
                'filter' => 1 // A changer après avoir fait le créate
            )
        );
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        global $userClient;
        $parent = $this->getParentInstance();
        $errors = parent::create($warnings, $force_create);
        $urlI = '';
        $liste_destinatairesI = array();
        $urlE = '';
        $liste_destinatairesE = array();
        $client = null;
        
        if($this->getData('visibility') < 1)
            $this->updateField ('visibility', 1);

        if (!$errors) {
            $urlI = $parent->getLink();
            if ($parent->getData('id_user_client') > 0) {
                if (isset($userClient)) {
                    $client = $userClient;
                } else {
                    $client = $this->getInstance('bimpinterfaceclient', 'BIC_UserClient', $parent->getData('id_user_client'));
                }
                $liste_destinatairesI = BimpTools::merge_array($liste_destinatairesI, $client->get_dest('commerciaux'));
            }
            
            
            if (BimpTools::getContext() == 'public') {
                $this->updateField('id_user_client', $userClient->id);
                $this->updateField('visibility', 1);
                $resp = $parent->getChildObject('user_resp');
                if(is_object($resp) && $resp->isLoaded())
                    $liste_destinatairesI[] = $resp->getData('email');
            }
            elseif (/*$parent->getData('status') != $parent::BS_TICKET_DEMANDE_CLIENT && $parent->getData('status') != $parent::BS_TICKET_CLOT && */$this->getData('visibility') == 1) {
                if (is_object($client)) {
                    $liste_destinatairesE = Array($client->getData('email'));
                    $liste_destinatairesE = BimpTools::merge_array($liste_destinatairesE, $client->get_dest('admin'));
                    $clientTicket = BimpCache::getBimpObjectInstance('bimpinterfaceclient', 'BIC_UserTickets', $parent->id);
                    $urlE = $clientTicket->getNomUrl();
                }
            }
            
            if($urlE != '' && count($liste_destinatairesE))
                mailSyn2('BIMP-CLIENT : Note sur votre ticket', implode(', ', $liste_destinatairesE), 'noreply@bimp.fr', 'Une note a été créée sur votre ticket support : ' . $parent->getData('ticket_number').' cliquez ici pour le lire et y répondre : '.$urlE);
            if($urlI != '' && count($liste_destinatairesI))
                mailSyn2('BIMP-CLIENT : Note sur votre ticket', implode(', ', $liste_destinatairesI), 'noreply@bimp.fr', 'Une note a été créée sur votre ticket support : ' . $parent->getData('ticket_number').' cliquez ici pour le lire et y répondre : '.$urlI.'<br/>Message : '.$this->getData('content'));
        }
        return $errors;
    }

    public function isFieldEditable($field, $force_edit = false)
    {

        if ($field == 'content' && BimpTools::getContext() != "public") {
            return $this->is_a_note_of_client() ? 0 : 1;
        }

        return parent::isFieldEditable($field);
    }

    public function is_a_note_of_client()
    {
        if ($this->getData('id_user_client') > 0)
            return 1;

        return 0;
    }
}
