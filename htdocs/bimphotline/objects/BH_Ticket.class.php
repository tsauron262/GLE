<?php

class BH_Ticket extends BimpObject
{

    public static $impacts = array(
        1 => array('label' => 'Faible', 'classes' => array('info'), 'icon' => 'star-o'),
        2 => array('label' => 'Moyen', 'classes' => array('warning'), 'icon' => 'star-half-o'),
        3 => array('label' => 'Haut', 'classes' => array('danger'), 'icon' => 'star'),
    );
    public static $cover_types = array(
        1 => 'Couvert',
        2 => 'Payant',
        3 => 'Non couvert'
    );
    public static $status_list = array(
        1 => 'En cours',
        2 => 'En attente client',
        3 => 'En attente commercial',
        4 => 'En attente technicien',
        5 => 'En attente prestataire',
        6 => 'Clôt'
    );

    public function getPrioritiesArray()
    {
        BimpObject::getInstance('bimphotline', 'BH_Inter');
        return BH_Inter::$priorities;
    }

    public function create()
    {
        global $user;
        $this->data['ticket_number'] = 'BH' . date('ymdhis');
        $this->data['id_user_resp'] = (int) $user - id;

        return parent::create();
    }

    public function onInterUpdate()
    {
        if (!isset($this->id) || !$this->id) {
            return false;
        }

        $id_user = 0;
        $inters = $this->getChildrenObjects('inters');
        $best_timer = 0;
        $best_prio = 1;
        foreach ($inters as $inter) {
            $inter_timer = (int) $inter->getData('timer');
            if (!$best_timer) {
                $best_timer = $inter_timer;
                $id_user = (int) $inter->getData('tech_id_user');
            } elseif ((int) $inter_timer > (int) $best_timer) {
                $best_timer = $inter_timer;
                $id_user = (int) $inter->getData('tech_id_user');
            }
            if ((int) $inter->getData(('status')) !== 2) {
                $inter_prio = $inter->getData('priorite');
                if ($inter_prio > $best_prio) {
                    $best_prio = $inter_prio;
                }
            }
        }
        if (!$id_user) {
            $id_user = $this->getData('user_create');
        }

        $update = false;

        if ((int) $id_user !== (int) $this->getData('id_user_resp')) {
            $this->set('id_user_resp', (int) $id_user);
            $update = true;
        }
        if ((int) $best_prio !== (int) $this->getData('priorite')) {
            $this->set('priorite', (int) $best_prio);
            $update = true;
        }

        return $update;
    }

    public function onChildSave(BimpObject $child)
    {
        if ($child->object_name === 'BH_Inter') {
            if ($this->onInterUpdate()) {
                $this->update();
            }
        }
    }

    public function onChildDelete(BimpObject $child)
    {
        if (!isset($this->id) || !$this->id) {
            return;
        }

        if ($child->object_name === 'BH_Inter') {
            if ($this->onInterUpdate()) {
                $this->update();
            }
        }
    }

    public function getClientFormInput()
    {
        $contrat = $this->getChildObject('contrat');
        $id_client = 0;
        $nom_url = '';
        if (!is_null($contrat)) {
            if (isset($contrat->societe) && is_a($contrat->societe, 'Societe')) {
                if (isset($contrat->societe->id) && $contrat->societe->id) {
                    $id_client = $contrat->societe->id;
                    $nom_url = $contrat->getNomUrl(1);
                }
            } elseif (isset($contrat->socid) && $contrat->socid) {
                $id_client = $contrat->socid;
                global $db;
                $soc = new Societe($db);
                if ($soc->fetch($contrat->socid) > 0) {
                    $nom_url = $soc->getNomUrl(1);
                }
            }
        }

        return '<input type="hidden" value="' . $id_client . '" name="id_client"/>' . $nom_url;
    }

    public function getClient_contactsArray()
    {
        $contacts = array();

        $contrat = $this->getChildObject('contrat');
        if (!is_null($contrat)) {
            if (isset($contrat->socid) && $contrat->socid) {
                $where = '`fk_soc` = ' . (int) $contrat->socid;
                $rows = $this->db->getRows('socpeople', $where, null, 'array', array('rowid', 'firstname', 'lastname'));
                if (!is_null($rows)) {
                    foreach ($rows as $r) {
                        $contacts[(int) $r['rowid']] = BimpTools::ucfirst($r['firstname']) . ' ' . strtoupper($r['lastname']);
                    }
                }
            }
        }

        return $contacts;
    }

    public function getEquipmentsArray()
    {
        $equipments = array();
        $id_contrat = (int) $this->getData('id_contrat');
        if (!is_null($id_contrat) && $id_contrat) {
            $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
            $bimpAsso = new BimpAssociation($equipment, 'contrats');
            $equipments = $bimpAsso->getObjectsList($id_contrat);
        }

        return $equipments;
    }

    public function defaultDisplayEquipmentsItem($id_equipment)
    {
        $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
        if ($equipment->fetch($id_equipment)) {
            $label = '';
            $product = $equipment->config->getObject('', 'product');
            if (!is_null($product) && isset($product->id) && $product->id) {
                $label = $product->label;
            } else {
                return BimpRender::renderAlerts('Equipement ' . $id_equipment . ': Produit associé non trouvé');
            }

            $label .= ' - N° série: ' . $equipment->getData('serial');

            return $label;
        }
        return BimpRender::renderAlerts('Equipement non trouvé (ID ' . $id_equipment . ')', 'warning');
    }

    public function getDureeTotale()
    {
        if (!isset($this->id) || !$this->id) {
            return 0;
        }

        $time = 0;

        $inters = $this->getChildrenObjects('inters');
        foreach ($inters as $inter) {
            $time += (int) $inter->getData('timer');
        }

        return $time;
    }

    public function displayDureeTotale()
    {
        return BimpTools::displayTimefromSeconds($this->getDureeTotale());
    }

    public function getNotificationsList($operation)
    {
        switch ($operation) {
            case 'create':
                return array(
                    array('label' => 'Commercial du client', 'value' => 'comm'),
                    array('label' => 'Client', 'value' => 'client'),
                    array('label' => 'Technicien', 'value' => 'tech')
                );
        }
    }
    
    public function renderChronoView()
    {
        if (!isset($this->id) || !$this->id) {
            return BimpRender::renderAlerts('Ticket non enregistré');
        }

        $timer = BimpObject::getInstance('bimpcore', 'BimpTimer');

        if (!$timer->find(array(
                    'obj_module' => $this->module,
                    'obj_name'   => $this->object_name,
                    'id_obj'     => (int) $this->id,
                    'field_name' => 'appels_timer'
                ))) {
            if (!$timer->setObject($this, 'appels_timer')) {
                return BimpRender::renderAlerts('Echec de la création du timer');
            }
        }

        if (!isset($timer->id) || !$timer->id) {
            return BimpRender::renderAlerts('Echec de l\'initialisation du timer');
        }

        $html = $timer->render('Chrono appels payants');

        return $html;
    }
}
