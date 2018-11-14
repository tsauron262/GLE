<?php

class BS_Ticket extends BimpObject
{

    const BS_TICKET_EN_COURS = 1;
    const BS_TICKET_ATT_CLIENT = 2;
    const BS_TICKET_ATT_COMMERCIAL = 3;
    const BS_TICKET_ATT_TECHNICIEN = 4;
    const BS_TICKET_ATT_PRESTATAIRE = 5;
    const BS_TICKET_CLOT = 999;

    public static $priorities = array(
        1 => array('label' => 'Non urgent', 'classes' => array('success'), 'icon' => 'hourglass-start'),
        2 => array('label' => 'Urgent', 'classes' => array('warning'), 'icon' => 'hourglass-half'),
        3 => array('label' => 'Très urgent', 'classes' => array('danger'), 'icon' => 'hourglass-end'),
    );
    public static $impacts = array(
        1 => array('label' => 'Faible', 'classes' => array('info'), 'icon' => 'star-o'),
        2 => array('label' => 'Moyen', 'classes' => array('warning'), 'icon' => 'star-half-o'),
        3 => array('label' => 'Haut', 'classes' => array('danger'), 'icon' => 'star'),
    );
    public static $cover_types = array(
        1 => array('label' => 'Couvert', 'classes' => array('success'), 'icon' => 'fas_check'),
        2 => array('label' => 'Payant', 'classes' => array('warning'), 'icon' => 'fas_euro-sign'),
        3 => array('label' => 'Non couvert', 'classes' => array('danger'), 'icon' => 'fas_times'),
    );
    public static $status_list = array(
        self::BS_TICKET_EN_COURS        => array('label' => 'En cours', 'icon' => 'fas_cogs', 'classes' => array('info')),
        self::BS_TICKET_ATT_CLIENT      => array('label' => 'En attente client', 'icon' => 'fas_hourglass-start', 'classes' => array('important')),
        self::BS_TICKET_ATT_COMMERCIAL  => array('label' => 'En attente commercial', 'icon' => 'fas_hourglass-start', 'classes' => array('important')),
        self::BS_TICKET_ATT_TECHNICIEN  => array('label' => 'En attente technicien', 'icon' => 'fas_hourglass-start', 'classes' => array('important')),
        self::BS_TICKET_ATT_PRESTATAIRE => array('label' => 'En attente prestataire', 'icon' => 'fas_hourglass-start', 'classes' => array('important')),
        self::BS_TICKET_CLOT            => array('label' => 'Clos', 'icon' => 'fas_times', 'classes' => array('danger')),
    );

    // Getters:

    public function getPostIdClient()
    {
        $id_client = (int) BimpTools::getPostFieldValue('id_client_contrat', 0);
        if (!$id_client) {
            $id_client = (int) BimpTools::getPostFieldValue('id_client', (int) $this->getData('id_client'));
        }
        return $id_client;
    }

    public function getClient_contactsArray()
    {
        $id_client = (int) $this->getPostIdClient();

        if ($id_client) {
            return self::getSocieteContactsArray($id_client, true);
        }

        return array();
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

    public function getClientContratInput()
    {
        $id_client = 0;
        $nom_url = '';
        if ((int) BimpTools::getPostFieldValue('no_contrat', 0)) {
            if (!(int) BimpTools::getPostFieldValue('no_client', 0)) {
                $id_client = BimpTools::getPostFieldValue('id_client', 0);
            }
        } else {
            $contrat = $this->getChildObject('contrat');
            if (BimpObject::objectLoaded($contrat)) {
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
        }

        return '<input type="hidden" value="' . $id_client . '" name="id_client_contrat"/>' . $nom_url . ': ' . $id_client;
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

    public function getInstanceName()
    {
        if ($this->isLoaded()) {
            return 'Ticket ' . $this->id . ' - ' . $this->getData('ticket_number');
        }

        return 'Ticket';
    }

    public function getTimer()
    {
        if ($this->isLoaded()) {
            $timer = BimpObject::getInstance('bimpcore', 'BimpTimer');
            if ($timer->find(array(
                        'obj_module' => $this->module,
                        'obj_name'   => $this->object_name,
                        'id_obj'     => $this->id,
                        'field_name' => 'appels_timer'
                            ), false, true)) {
                return $timer;
            }
        }
        return null;
    }

    public function getOpenIntersArray()
    {
        $inters = array();
        if ($this->isLoaded()) {
            BimpObject::loadClass('bimpsupport', 'BS_Inter');
            foreach ($this->getChildrenList('inters', array(
                'status' => array(
                    'operator' => '!=',
                    'value'    => BS_Inter::BS_INTER_CLOSED
                )
            )) as $id_inter) {
                $inters[(int) $id_inter] = 'Intervention ' . $id_inter;
            }
        }
        return $inters;
    }

    public function getCoverTypesInputArray()
    {
        $covers = self::$cover_types;

        if ((int) BimpTools::getPostFieldValue('no_contrat', 0) || ($this->isLoaded() && !(int) $this->getData('id_contrat'))) {
            unset($covers[1]);
        } else {
            unset($covers[2]);
            unset($covers[3]);
        }

        return $covers;
    }

    public function getHeaderButtons()
    {
        $buttons = array();
        if ($this->isLoaded()) {
            $openInters = $this->getOpenIntersArray();
            if (count($openInters)) {
                $buttons[] = array(
                    'label'   => 'Fermer des interventions',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('closeInter', array(), array(
                        'form_name' => 'close_inters'
                    ))
                );
            }
        }

        return $buttons;
    }

    public function hasNoContrat()
    {
        if ($this->isLoaded()) {
            if (!(int) $this->getData('id_contrat')) {
                return 1;
            }
        }

        return 0;
    }

    public function hasNoClient()
    {
        if ($this->isLoaded()) {
            if (!(int) $this->getData('id_client')) {
                return 1;
            }
        }

        return 0;
    }

    public function getContratInputFilters()
    {
        $filters = array(
            'c.date_cloture' => array(
                'or_field' => array(
                    'IS_NULL',
                    array(
                        'operator' => '>',
                        'value'    => 'NOW()'
                    )
                )
            )
        );

        $key = '(SELECT COUNT(DISTINCT cdet.rowid) FROM llx_contratdet cdet WHERE cdet.fk_contrat = c.rowid AND (cdet.date_cloture IS NULL OR cdet.date_cloture > NOW()))';
        $filters[$key] = array(
            'operator' => '>',
            'value'    => '0'
        );

        return $filters;
    }

    // Affichages: 

    public function displayDureeTotale()
    {
        return BimpTools::displayTimefromSeconds($this->getDureeTotale());
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

    // Rendus HTML:

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

    // Traitements: 

    public function onInterUpdate()
    {
        if (!isset($this->id) || !$this->id) {
            return false;
        }

        $id_user = 0;
        $inters = $this->getChildrenObjects('inters');
        $best_timer = 0;
//        $best_prio = 1;
        foreach ($inters as $inter) {
            $inter_timer = (int) $inter->getData('timer');
            if (!$best_timer) {
                $best_timer = $inter_timer;
                $id_user = (int) $inter->getData('tech_id_user');
            } elseif ((int) $inter_timer > (int) $best_timer) {
                $best_timer = $inter_timer;
                $id_user = (int) $inter->getData('tech_id_user');
            }
//            if ((int) $inter->getData(('status')) !== 2) {
//                $inter_prio = $inter->getData('priorite');
//                if ($inter_prio > $best_prio) {
//                    $best_prio = $inter_prio;
//                }
//            }
        }
        if (!$id_user) {
            $id_user = $this->getData('user_create');
        }

        $update = false;

        if ((int) $id_user !== (int) $this->getData('id_user_resp')) {
            $this->set('id_user_resp', (int) $id_user);
            $update = true;
        }

//        if ((int) $best_prio !== (int) $this->getData('priorite')) {
//            $this->set('priorite', (int) $best_prio);
//            $update = true;
//        }

        return $update;
    }

    public function onChildSave(BimpObject $child)
    {
        if ($child->object_name === 'BS_Inter') {
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

        if ($child->object_name === 'BS_Inter') {
            if ($this->onInterUpdate()) {
                $this->update();
            }
        }
    }

    // Actions:

    public function actionCloseInter($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $inters = array();
        if (isset($data['close_all'])) {
            $success = 'Toutes les interventions on été fermées avec succès';
            foreach ($this->getOpenIntersArray() as $id_inter => $label) {
                $inters[] = (int) $id_inter;
            }
        } elseif (isset($data['inters_to_close'])) {
            $success = 'Interventions sélectionnées fermées avec succès';
            $inters = $data['inters_to_close'];
            if (!count($inters)) {
                $errors[] = 'Aucune intervention sélectionnée';
            }
        }

        if (count($inters) && !count($errors)) {
            $inter = BimpObject::getInstance('bimpsupport', 'BS_Inter');
            foreach ($inters as $id_inter) {
                if ($inter->fetch($id_inter)) {
                    $inter->set('status', BS_Inter::BS_INTER_CLOSED);
                    $inter_errors = $inter->update();
                    if (count($inter_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($inter_errors, 'Echec de la fermeture du statut de l\'intervention ' . $inter->id);
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function validatePost()
    {
        $errors = parent::validatePost();

        if (!count($errors)) {
            if ((int) BimpTools::getPostFieldValue('no_contrat', 0)) {
                $this->set('id_contrat', 0);

                $id_client = (int) BimpTools::getPostFieldValue('id_client', 0);
                if ($id_client) {
                    $this->set('id_client', $id_client);
                }

                if (BimpTools::getPostFieldValue('no_client', 0)) {
                    $this->set('id_client', 0);
                    $this->set('id_contact', 0);
                }
            } else {
                if ((int) $this->getData('id_contrat')) {
                    $id_client = BimpTools::getPostFieldValue('id_client_contrat', 0);
                    if ($id_client) {
                        $this->set('id_client', $id_client);
                    }
                }
            }
        }

        return $errors;
    }

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            if ($this->getData('id_contrat')) {
                $contrat = $this->getChildObject('contrat');
                if (!BimpObject::objectLoaded($contrat)) {
                    $errors[] = 'Le contrat d\'ID ' . $this->getData('id_contrat') . ' n\'existe pas';
                } else {
                    // todo: check validité contrat
                }
            }

            $id_client = (int) $this->getData('id_client');
            if ($id_client) {
                $client = $this->getChildObject('client');
                if (!BimpObject::objectLoaded($client)) {
                    $errors[] = 'Le client d\'ID ' . $id_client . 'n\'existe pas';
                } else {
                    $id_contact = (int) $this->getData('id_contact');
                    if ($id_contact) {
                        $contacts = self::getSocieteContactsArray($id_client);
                        if (!array_key_exists($id_contact, $contacts)) {
                            $this->set('id_contact', 0);
                        }
                    }
                }
            } else {
                $this->set('id_contact', 0);
            }
        }

        return $errors;
    }

    public function create(&$warnings, $force_create = false)
    {
        global $user;
        $this->data['ticket_number'] = 'BH' . date('ymdhis');
        $this->data['id_user_resp'] = (int) $user->id;

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            if ((int) BimpTools::getValue('start_timer', 0)) {
                $timer = BimpObject::getInstance('bimpcore', 'BimpTimer');
                if (!$timer->setObject($this, 'appels_timer', true)) {
                    $warnings[] = 'Echec de l\'initialisation du chrono appel payant';
                }
            }
        }

        return $errors;
    }

    public function update(&$warnings, $force_update = false)
    {
        if ((int) $this->getData('status') === self::BS_TICKET_CLOT) {
            $open_inters = $this->getOpenIntersArray();
            if (count($open_inters)) {
                $msg = 'Il n\'est pas possible de fermer ce ticket car des interventions sont encore ouvertes';
                $msg .= '<span style="display: inline-block; width: 100%; text-align: center; margin: 10px 0;">';
                $msg .= '<button class="btn btn-default" onclick="' . $this->getJsActionOnclick('closeInter', array('close_all' => 1)) . '">';
                $msg .= '<i class="' . BimpRender::renderIconClass('times') . ' iconLeft"></i>Fermer toutes les interventions';
                $msg .= '</button>';
                $msg .= '</span>';
                $errors[] = $msg;
            }
        }

        if (count($errors)) {
            return $errors;
        }

        $errors = parent::update($warnings, $force_update);

        if (!count($errors) && (int) $this->getData('status') === self::BS_TICKET_CLOT) {
            $timer = $this->getTimer();
            if (BimpObject::objectLoaded($timer)) {
                if ((int) $timer->getData('session_start')) {
                    $timer_errors = $timer->hold();
                    if (count($timer_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($timer_errors, 'Echec de l\'arrêt du chronomètre');
                    } else {
                        $times = $timer->getTimes($this);
                        $this->updateField('appels_timer', (int) $times['total']);
                        $timer->updateField('time_session', 0);
                    }
                }
            }
        }
    }

    public function delete($force_delete = false)
    {
        $timer = $this->getTimer();

        $errors = parent::delete($force_delete);

        if (!count($errors) && BimpObject::objectLoaded($timer)) {
            $timer->delete(true);
        }

        return $errors;
    }
}
