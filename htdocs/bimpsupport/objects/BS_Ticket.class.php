<?php

class BS_Ticket extends BimpObject
{

    const BS_TICKET_EN_COURS = 1;
    const BS_TICKET_ATT_CLIENT = 2;
    const BS_TICKET_ATT_COMMERCIAL = 3;
    const BS_TICKET_ATT_TECHNICIEN = 4;
    const BS_TICKET_ATT_PRESTATAIRE = 5;
    const BS_TICKET_DEMANDE_CLIENT = 20;
    const BS_TICKET_CLOT = 999;

    public static $priorities = array(
        0 => array('label' => ''),
        1 => array('label' => 'Non urgent', 'classes' => array('success'), 'icon' => 'hourglass-start'),
        2 => array('label' => 'Urgent', 'classes' => array('warning'), 'icon' => 'hourglass-half'),
        3 => array('label' => 'Très urgent', 'classes' => array('danger'), 'icon' => 'hourglass-end'),
    );
    public static $impacts = array(
        0 => array('label' => ''),
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
        self::BS_TICKET_DEMANDE_CLIENT  => array('label' => 'Demande client', 'icon' => 'fas_cogs', 'classes' => array('important')),
        self::BS_TICKET_EN_COURS        => array('label' => 'En cours', 'icon' => 'fas_cogs', 'classes' => array('info')),
        self::BS_TICKET_ATT_CLIENT      => array('label' => 'En attente client', 'icon' => 'fas_hourglass-start', 'classes' => array('important')),
        self::BS_TICKET_ATT_COMMERCIAL  => array('label' => 'En attente commercial', 'icon' => 'fas_hourglass-start', 'classes' => array('important')),
        self::BS_TICKET_ATT_TECHNICIEN  => array('label' => 'En attente technicien', 'icon' => 'fas_hourglass-start', 'classes' => array('important')),
        self::BS_TICKET_ATT_PRESTATAIRE => array('label' => 'En attente prestataire', 'icon' => 'fas_hourglass-start', 'classes' => array('important')),
        self::BS_TICKET_CLOT            => array('label' => 'Clos', 'icon' => 'fas_times', 'classes' => array('danger')),
    );
    public static $arrayTypeSerialImei = array(
        "serial" => "N° de série",
        "imei"   => "N° IMEI",
        "serv"   => "Service"
    );

    // Droits users:

    public function canClientView()
    {
        if ($this->isLoaded()) {
            global $userClient;
            if (BimpObject::objectLoaded($userClient) && $userClient->getData("id_client") == $this->getData("id_client")) {
                if (!$userClient->isAdmin() && $userClient->id !== (int) $this->getData('id_user_client')) {
                    return 0;
                }
                return 1;
            }

            return 0;
        }

        return 1;
    }

    public function canClientCreate()
    {
        global $userClient;

        if (BimpObject::objectLoaded($userClient)) {
            $contrats = $userClient->getContratsVisibles(true);

            if (!empty($contrats)) {
                return 1;
            }
        }

        return 0;
    }

    public function canClientEdit()
    {
        if ($this->isLoaded()) {
            if ($this->canClientView() && $this->getData('status') == self::BS_TICKET_DEMANDE_CLIENT) {
                return 1;
            }

            return 0;
        }

        return 1;
    }

    public function canDelete()
    {
        global $user;
        if ($user->admin)
            return 1;

        $dateC = new DateTime($this->getData("date_create"));
        if ($dateC->add(new DateInterval('PT2H')) > new DateTime())
            if (($this->getData("status") == self::BS_TICKET_DEMANDE_CLIENT || $this->getData("status") == self::BS_TICKET_EN_COURS) &&
                    $this->getData("date_create"))
                if ($this->getData("id_user_resp") == $user->id)
                    if ($this->getData("timer") == 0 && $this->getDureeTotale() == 0)
                        return 1;

        return 0;
    }

    // Getters booléens: 

    public function isCreatable($force_create = false, &$errors = array())
    {
        if (!(int) BimpCore::getConf('use_tickets', null, 'bimpsupport')) {
            $errors[] = 'La création des tickets hotline est désactivée';
            return 0;
        }

        if (BimpCore::isContextPublic()) {
//            $id_contrat = (int) $this->getData('id_contrat');
//
//            if (!$id_contrat && BimpTools::getValue("fc", '') == "contrat_ticket" && (int) BimpTools::getValue("id", 0)) {
//                $id_contrat = (int) BimpTools::getValue("id");
//            }
//
//            if ($id_contrat > 0) {
//                $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $id_contrat);
//                if (BimpObject::objectLoaded($contrat)) {
//                    if (in_array($contrat->getData('statut'), array(1, 11))) {
//                        return 1;
//                    }
//                }
//            }
//
//            return 0;
            return 1;
        }

        return parent::isCreatable($force_create, $errors);
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        switch ($field) {
            case 'sujet':
                if (!$this->isLoaded()) {
                    return 1;
                }

                if (BimpCore::isContextPublic()) {
                    if ($this->isProcessing()) {
                        return 0;
                    }

                    if ($this->isUserClientRequest()) {
                        return 1;
                    }
                    return 0;
                }
                return 1;
        }

        return parent::isFieldEditable($field);
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

    public function isProcessing()
    {
        return ($this->getData('status') == self::BS_TICKET_DEMANDE_CLIENT) ? 0 : 1;
    }

    public function isUserClientRequest()
    {
        return ($this->getData('id_user_client') == 0) ? 0 : 1;
    }

    public function isNotUserClientRequest()
    {
        return ($this->isUserClientRequest() == 0) ? 1 : 0;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'prendre_en_compte':
                if (BimpTools::getContext() == 'public') {
                    $errors[] = 'Contexte public';
                    return 0;
                }
                if ((int) $this->getData('status') != self::BS_TICKET_DEMANDE_CLIENT) {
                    $errors[] = 'Statut actuel invalide pour cette opération';
                    return 0;
                }
                return 1;

            case 'closeInter':
                if (BimpTools::getContext() == 'public') {
                    $errors[] = 'Contexte public';
                    return 0;
                }
                $openInters = $this->getOpenIntersArray();
                if (!count($openInters)) {
                    $errors[] = 'Aucune intervention ouverte';
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    // Getters array: 

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
            //unset($covers[3]);
        }

        return $covers;
    }

    public function getNewTicketContratsArray()
    {
        $tickets = array();

        $id_client = (int) $this->getData('id_client');

        if (BimpCore::isContextPublic()) {
            global $userClient;

            if (BimpObject::objectLoaded($userClient)) {
                if ((int) $userClient->getData('id_client') === $id_client) {
                    $userContrats = $userClient->getAssociatedContratsList();
                    $rows = $this->db->getRows('contrat', 'fk_soc = ' . $id_client . ' AND statut = 11', null, 'array', array('rowid', 'ref', 'label'));

                    if (is_array($rows)) {
                        foreach ($rows as $r) {
                            if ($userClient->isAdmin() || in_array((int) $r['rowid'], $userContrats)) {
                                $tickets[(int) $r['rowid']] = $r['ref'] . ($r['label'] ? ' - ' . $r['label'] : '');
                            }
                        }
                    }
                }
            }
        } else {
            if ($id_client) {
                $rows = $this->db->getRows('contrat', 'fk_soc = ' . $id_client . ' AND statut = 11', null, 'array', array('rowid', 'ref', 'label'));

                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        $tickets[(int) $r['rowid']] = $r['ref'] . ($r['label'] ? ' - ' . $r['label'] : '');
                    }
                }
            }
        }

        return $tickets;
    }

    // Getters params: 

    public function getExtraBtnListInterfaceClient()
    {
        $buttons = array();
        $buttons[] = array(
            'label'   => 'Voir le ticket',
            'icon'    => 'fas_file',
            "onclick" => "window.location.href = '" . DOL_URL_ROOT . "/bimpinterfaceclient/?page=ticket&id=" . $this->getData('id') . "'"
        );

        return $buttons;
    }

    public function getHeaderButtons()
    {
        $buttons = array();
        if ($this->isLoaded()) {
            if ($this->isActionAllowed('closeInter') && $this->canSetAction('closeInter')) {
                $buttons[] = array(
                    'label'   => 'Fermer des interventions',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('closeInter', array(), array(
                        'form_name' => 'close_inters'
                    ))
                );
            }
            if ($this->isActionAllowed('prendre_en_compte') && $this->canSetAction('prendre_en_compte')) {
                $buttons[] = array(
                    'label'   => 'Prendre en compte le ticket',
                    'icon'    => 'fas_thumbs-up',
                    'onclick' => $this->getJsActionOnclick('prendre_en_compte', array(), array(
                        'confirm_mag' => 'Veuillez confirmer la prise en compte de ce ticket'
                    ))
                );
            }

            $equipment = $this->getSerialEquipment(true);
            if ($equipment) {
                $sav = BimpObject::getBimpObjectInstance($this->module, 'BS_SAV');
                $values = array(
                    'fields' => array(
                        'id_client'    => (int) $this->getData('id_client'),
                        'id_equipment' => (int) $equipment->id,
                        'symptomes'    => BimpTools::htmlToText($this->getData('sujet')),
                        'pword_admin'  => "X",
                        'id_ticket'    => $this->id
                    )
                );
                $buttons[] = array(
                    'label'   => 'Créer SAV',
                    'icon'    => 'fas_wrench',
                    'onclick' => $sav->getJsLoadModalForm('default', 'Nouveau SAV', $values)
                );
            }
        }
        return $buttons;
    }

    public function getSerialEquipment($in_the_client = false)
    {
        $serial = addslashes($this->getData('serial'));
        if ($serial != '') {
            $equipment = BimpObject::getBimpObjectInstance('bimpequipment', 'Equipment');
            if ($equipment->find(array(
                        'or_serial' => array(
                            'or' => array(
                                'serial' => $serial,
                                'imei'   => $serial,
                                'imei2'  => $serial,
                                'meid'   => $serial
                            )
                        )
                            ), true)) {
                if (!$in_the_client)
                    return $equipment;
                $place = $equipment->getCurrentPlace();
                if ($place && $place->getData('id_client') == $this->getData('id_client'))
                    return $equipment;
            }
        }
        return 0;
    }

    public function displayEquipement()
    {
        $html = '';
        $equipment = $this->getSerialEquipment();
        if ($equipment) {
            $html .= $equipment->getLink();
            $savs = BimpObject::getBimpObjectInstance($this->module, 'BS_SAV');
            $list = $savs->getListObjects(array('id_ticket' => $this->id));
            foreach ($list as $sav) {
                $html .= '<br/>' . $sav->getLink();
            }
        } elseif ($this->getData('serial') != '')
            $html .= 'Serial : ' . $this->getData('serial') . ' inconnue chez le client';

        return $html;
    }

    public function getContratInputFilters()
    {
        BimpObject::loadClass('bimpcontract', 'BContract_contrat');
        $filters = array(
            'a.statut' => BContract_contrat::CONTRAT_STATUS_ACTIVER
        );

        return $filters;
    }

    public function getPublicListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            if ($this->can('view')) {
                $url = $this->getPublicUrl();

                if ($url) {
                    $buttons[] = array(
                        'label'   => 'Voir le détail',
                        'icon'    => 'fas_eye',
                        'onclick' => 'window.location = \'' . $url . '\''
                    );
                }
            }
        }

        return $buttons;
    }

    public function getPublicUrlParams()
    {
        return 'tab=tickets&content=card&id_ticket=' . $this->id;
    }

    public function getPublicListPageUrlParams()
    {
        return 'tab=tickets';
    }

    public function getRefProperty()
    {
        return 'ticket_number';
    }

    // Getters données:

    public function getPostIdClient()
    {
        $id_client = (int) BimpTools::getPostFieldValue('id_client_contrat', 0);
        if (!$id_client) {
            $id_client = (int) BimpTools::getPostFieldValue('id_client', (int) $this->getData('id_client'));
        }
        if (!$id_client) {
            $id_client = (int) BimpTools::getPostFieldValue('id_client_service', (int) $this->getData('id_client_service'));
        }
        return $id_client;
    }

    public function getDureeTotale()
    {
        if (!$this->isLoaded()) {
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

    public function getRef($withGeneric = true)
    {
        return (string) $this->getData('ticket_number');
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

    // Affichages: 

    public function displayDureeTotale()
    {
        return BimpTools::displayTimefromSeconds($this->getDureeTotale());
    }

    public function defaultDisplayEquipmentsItem($id_equipment)
    {
        $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
        if ($equipment->isLoaded()) {
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

    public function displaySujet()
    {
        $data = $dataTrunc = "";
        $longeur = 150;

        if ($this->getData('sujetInterne') != "") {
            $data = $this->getData('sujetInterne');
            $dataTrunc = dol_trunc($this->getData('sujetInterne'), $longeur);
        } else {
            $data = $this->getData('sujet');
            $dataTrunc = dol_trunc($this->getData('sujet'), $longeur);
        }


        global $modeCSV;
        if ($modeCSV || $data == $dataTrunc) {
            return $data;
        } else {
            $return = '<span class=" bs-popover"';
            $return .= BimpRender::renderPopoverData($data, 'top', true);
            $return .= '>';
            $return .= $dataTrunc;
            $return .= '</span>';
        }

        return $return;
    }

    // Rendus HTML:

    public function renderClientContratInput()
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
                        $bimpContrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $contrat->id);
                        if (BimpObject::objectLoaded($bimpContrat)) {
                            $nom_url = $bimpContrat->getLink();
                        } else {
                            $nom_url = $contrat->getNomUrl(1);
                        }
                    }
                } elseif (isset($contrat->socid) && $contrat->socid) {
                    $id_client = $contrat->socid;
                    $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
                    if (BimpObject::objectLoaded($client)) {
                        $nom_url = $client->getLink();
                    } else {
                        global $db;
                        $soc = new Societe($db);
                        if ($soc->fetch($contrat->socid) > 0) {
                            $nom_url = $soc->getNomUrl(1);
                        }
                    }
                }
            }
        }

        return '<input type="hidden" value="' . $id_client . '" name="id_client_contrat"/>' . $nom_url;
    }

    public function renderClientServiceInput()
    {
        $id_client = 0;
        $nom_url = '';
        $service = $this->getChildObject('bimp_service');
        if (BimpObject::objectLoaded($service)) {
            $comm = $service->getParentInstance();
            if (BimpObject::objectLoaded($comm)) {
                $soc = $comm->getChildObject('client');
                if (BimpObject::objectLoaded($soc)) {
                    $id_client = $soc->id;
                    $nom_url = $soc->getLink();
                }
            }
        }

        return '<input type="hidden" value="' . $id_client . '" name="id_client_service"/>' . $nom_url;
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
        $errors = $warnings = array();
        if ($child->object_name === 'BS_Inter') {
            if ($this->onInterUpdate()) {
                $this->update($warnings);
            }
        }
        return $errors;
    }

    public function onChildDelete(BimpObject $child, $id_child_deleted)
    {
        $errors = $warnings = array();
        if (!isset($this->id) || !$this->id) {
            $errors[] = "Pas d'id parent pour onChildDelete";
            return $errors;
        }

        if ($child->object_name === 'BS_Inter') {
            if ($this->onInterUpdate()) {
                $this->update($warnings, true);
            }
        }
        return $errors;
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
            foreach ($inters as $id_inter) {
                $inter = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Inter', (int) $id_inter);
                if ($inter->isLoaded()) {
                    $inter->set('status', BS_Inter::BS_INTER_CLOSED);
                    $inter_errors = $inter->update($warnings);
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

    public function actionPrendre_en_compte($data, &$success)
    {
        $errors = array();
        $warnings = array();

        global $user;

        $this->set('id_user_resp', $user->id);
        $this->set('status', self::BS_TICKET_EN_COURS);

        $errors = $this->update($warnings, true);
        $success = 'Ticket bien pris en compte';

        if (!count($errors)) {
            if ($this->getData('id_user_client') > 0) {
                $userClient = $this->getChildObject('user_client');
                if (BimpObject::objectLoaded($userClient)) {
                    $to = $userClient->getData('email');
                    $cc = implode(',', $userClient->get_dest('admin'));

                    if (!$to && $cc) {
                        $to = $cc;
                        $cc = '';
                    }

                    if ($to) {
                        $subject = 'Prise en compte du ticket ' . $this->getData('ticket_number');
                        $msg = 'Bonjour,<br/><br/>';
                        $msg .= 'Nous vous confirmons que votre ticket support n° ' . $this->getData('ticket_number') . ' a été pris en compte par nos équipes.<br/>';
                        $msg .= '<b>Responsable de votre demande : </b>' . $user->firstname . ' ' . $user->lastname . '<br/><br/>';
                        $url = $this->getPublicUrl(false);

                        if ($url) {
                            $msg .= '<a href="' . $url . '">Cliquez ici</a> pour accéder au détail de ce ticket depuis notre site www.bimp.fr';
                        }

                        $bimpMail = new BimpMail($this, $subject, $to, '', $msg, '', $cc);
                        $mail_errors = array();
                        $bimpMail->send($mail_errors);
                        if (count($mail_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($mail_errors, 'Echec de l\'envoi de l\'e-mail de notification au client');
                        }
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
                } elseif ((int) $this->getData('id_service')) {
                    $id_client = BimpTools::getPostFieldValue('id_client_service', 0);
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

    public function create(&$warnings = array(), $force_create = false)
    {
        global $userClient, $user;
        $isPublic = BimpCore::isContextPublic();

        $errors = array();

        if ($isPublic) {
            if (!(int) $this->getData('id_contrat')) {
                return array('Vous devez obligatoire sélectionner un contrat actif pour ouvrir un nouveau ticket support');
            } else {
                $contrat = $this->getChildObject('bimp_contrat');

                if (!BimpObject::objectLoaded($contrat)) {
                    return array('Le contrat sélectionner n\'existe plus');
                } elseif (!$contrat->isValide()) {
                    return array('Le contrat ' . $contrat->getRef() . ' n\'est plus actif');
                }
            }
            $this->set('id_user_resp', 0);
            $this->set('cover', 1);
            if (!$this->getData('is_user_client')) {
                if (!BimpObject::objectLoaded($userClient)) {
                    $errors[] = 'Aucun utilisateur client connecté';
                } else {
                    if (!(int) $this->getData('id_client')) {
                        $errors[] = 'Client absent';
                    }
                }
            }

            $label_serial_imei = self::$arrayTypeSerialImei[BimpTools::getValue('choix')];
            $sujet = "------------------------------<br />";

            $sujet .= "<b>" . $label_serial_imei . ":</b> " . BimpTools::getValue('serial_imei') . "<br />";

            if (BimpTools::getValue('adresse_envois')) {
                $sujet .= "<b>Adresse d'envoi:</b> " . BimpTools::getValue('adresse_envois') . "<br />";
            }

            if (BimpTools::getValue('contact_in_soc')) {
                $sujet .= "<b>Utilisateur:</b> " . BimpTools::getValue('contact_in_soc') . "<br />";
            }

            if (BimpTools::getValue('email_bon_retour')) {
                $sujet .= "<b>Adresse email pour envoi du bon de retour:</b> " . BimpTools::getValue('email_bon_retour') . "<br />";
            }

            $sujet .= "------------------------------<br /><br />";

            $sujet .= $this->getData('sujet');
            $this->set('sujet', $sujet);
            $this->set('serial', BimpTools::getValue('serial_imei'));
        } else {
            $this->set('id_user_resp', (int) $user->id);
        }

        if (!count($errors)) {
            $this->set('ticket_number', 'BH' . date('ymdhis'));
            $this->set('priorite_demande_client', $this->getData('priorite'));
            $this->set('impact_demande_client', $this->getData('impact'));

            $errors = parent::create($warnings, $force_create);

            if (!count($errors)) {
                $client = $this->getChildObject('client');
                if (BimpObject::objectLoaded($client)) {
                    $client->setActivity('Création ' . $this->getLabel('of_the') . ' {{Ticket hotline:' . $this->id . '}}');
                }

                if ($isPublic) {
                    $liste_destinataires = Array($userClient->getData('email'));
                    $liste_destinataires = BimpTools::merge_array($liste_destinataires, Array('hotline@bimp.fr'));
                    $liste_destinataires = BimpTools::merge_array($liste_destinataires, $userClient->get_dest('admin'));
                    $liste_destinataires = BimpTools::merge_array($liste_destinataires, $userClient->get_dest('commerciaux'));

                    $contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $this->getData('id_contrat'));
                    $liste_destinataire_interne_contrat_spare = '';
                    if (BimpObject::objectLoaded($contrat) && $contrat->getData('objet_contrat') == 'CSP') {
                        $liste_destinataire_interne_contrat_spare = 'c.conort@bimp.fr, l.gay@bimp.fr, tt.cao@bimp.fr, d.debarnaud@bimp.fr, v.gaillard@bimp.fr ';
                    }

                    $subject = 'BIMP-CLIENT : Création Ticket Support N°' . $this->getData('ticket_number');
                    $to = implode(', ', $liste_destinataires);

                    $msg = '<h3>Ticket support numéro : ' . $this->getData('ticket_number') . '</h3>'
                            . 'Sujet du ticket : ' . $this->getData('sujet') . '<br />'
                            . 'Demandeur : ' . $userClient->getData('email') . '<br />'
                            . 'Contact dans la société : ' . $this->getData('contact_in_soc') . '<br />'
                            . 'Contrat : ' . (BimpObject::objectLoaded($contrat) ? $contrat->getData('ref') : 'aucun') . '<br />'
                            . 'Priorité : ' . $this->displayData('priorite', 'default', false, true) . '<br />'
                            . 'Impact : ' . $this->displayData('impact', 'default', false, true) . '<br />';

                    if (!mailSyn2($subject, $to, '', $msg, array(), array(), array(), $liste_destinataire_interne_contrat_spare)) {
                        $warnings[] = 'Echec de l\'envoi de l\'e-mail de confirmation';
                    }
                } else {
                    if ((int) BimpTools::getValue('start_timer', 0)) {
                        $timer = BimpObject::getInstance('bimpcore', 'BimpTimer');
                        if (!$timer->setObject($this, 'appels_timer', true)) {
                            $warnings[] = 'Echec de l\'initialisation du chrono appel payant';
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = array();

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

        if ($this->getData('status') == self::BS_TICKET_DEMANDE_CLIENT && $this->getInitData('status') != self::BS_TICKET_DEMANDE_CLIENT && $this->getData('id_user_client') > 0 && BimpTools::getContext() == 'private') {
            return array('Impossible de repasser le ticket en demande client');
        }

        $init_status = (int) $this->getInitData('status');

        $errors = parent::update($warnings, $force_update);

        if (!count($errors) && (int) $this->getData('id_user_client')) {
            $userClient = $this->getChildObject('user_client');

            if (BimpObject::objectLoaded($userClient)) {
                if ($this->getData('cover') == 3) {
                    // On envois un mail au commercial
                    $destinaitaire_commercial = $userClient->get_dest('commerciaux');
                    $link = $this->getLink(array(), 'private');
                    $msg = 'Bonjour,<br/>';
                    $msg .= 'Le ticket ';
                    if ($link) {
                        $msg .= $link;
                    } else {
                        $msg .= 'n° ' . $this->getData('ticket_number');
                    }
                    $msg .= '<br/><b style="color:red" >N\'est pas couvert par le contrat</b>';

                    mailSyn2('Demande support client non couverte', implode(', ', $destinaitaire_commercial), null, $msg);
                }

                if ($init_status !== (int) $this->getData('status')) {
                    $to = $userClient->getData('email');

                    if ($to) {
                        $subject = 'Mise à jour de votre ticket support n°' . $this->getData('ticket_number');
                        $msg = 'Bonjour,<br/><br/>';
                        $msg .= 'Votre ticket support n°<b>' . $this->getData('ticket_number') . '</b> est passé au statut "' . self::$status_list[(int) $this->getData('status')]['label'] . '".<br/><br/>';
                        $public_url = $this->getPublicUrl(false);
                        if ($public_url) {
                            $msg .= '<a href="' . $public_url . '">Cliquez ici</a> pour accéder au détail de votre ticket support sur notre site www.bimp.fr';
                        }

                        $bimpMail = new BimpMail($this, $subject, $to, '', $msg);
                        $mail_errors = array();
                        $bimpMail->send($mail_errors);

                        if (count($mail_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($mail_errors, 'Echec de l\'envoi de l\'e-mail de notification au client');
                        }
                    }
                }
            }
        }

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

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $timer = $this->getTimer();

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors) && BimpObject::objectLoaded($timer)) {
            $del_warnings = array();
            $timer->delete($del_warnings, true);
        }

        return $errors;
    }

    // Méthodes statiques:
    public static function correctSerialsAll($echo = false)
    {
        $bdb = self::getBdb();

        $where = '(serial IS NULL OR serial = \'\') AND id_user_client > 0 AND sujet LIKE \'%N° de série:%\'';
        $rows = $bdb->getRows('bs_ticket', $where, null, 'array', array('id', 'sujet', 'id_user_client'));

        if (is_array($rows)) {
            if ($echo) {
                echo '<pre>';
                print_r($rows);
                echo '</pre>';
            }

            foreach ($rows as $r) {
                if (preg_match('/.+' . preg_quote('N° de série:</b> ', '/') . '(.+)' . '<br ?\/>/U', $r['sujet'], $matches)) {
                    if ($echo) {
                        echo 'MAJ #' . $r['id'] . ' => ' . $matches[1] . ': ';
                    }
                    if ($bdb->update('bs_ticket', array(
                                'serial' => $matches[1]
                                    ), 'id = ' . (int) $r['id']) <= 0) {
                        if ($echo) {
                            echo '<span class="danger">ECHEC - ' . $bdb->err() . '</span>';
                        }
                    } else {
                        if ($echo) {
                            echo '<span class="success">OK</span>';
                        }
                    }
                    if ($echo) {
                        echo '<br/>';
                    }
                }
            }
        }
    }
}
