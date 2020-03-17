<?php

class BRH_Frais extends BimpObject
{

    const NOTE_FRAIS_ATT_VALIDATION = 1;
    const NOTE_FRAIS_ATT_REMBOURSEMENT = 2;
    const NOTE_FRAIS_REMBOURSEE = 3;
    const NOTE_FRAIS_REFUSEE = 4;

    const NOTE_FRAIS_RESTAURATION = 1; // Sous-Type : OK
    const NOTE_FRAIS_HEBERGEMENT = 2;
    const NOTE_FRAIS_PARKING = 3;
    const NOTE_FRAIS_ENTRETIEN = 4;
    const NOTE_FRAIS_KILOMETERS = 5;
    const NOTE_FRAIS_TRANSPORT = 6; // Sous-Type : OK
    const NOTE_FRAIS_POSTAL = 7; // Sous-Type : OK
    const NOTE_FRAIS_ABONNEMENT = 8;
    const NOTE_FRAIS_SALON = 9;
    const NOTE_FRAIS_DIVERS = 10;

    public static $status_list = array(
        self::NOTE_FRAIS_ATT_VALIDATION    => array('label' => 'En attente de validation', 'classes' => array('warning'), 'icon' => 'hourglass-start'),
        self::NOTE_FRAIS_ATT_REMBOURSEMENT => array('label' => 'En attente de remboursement', 'classes' => array('important'), 'icon' => 'hourglass-start'),
        self::NOTE_FRAIS_REMBOURSEE        => array('label' => 'Remboursée', 'classes' => array('success'), 'icon' => 'check'),
        self::NOTE_FRAIS_REFUSEE           => array('label' => 'Refusée', 'classes' => array('danger'), 'icon' => 'times'),
    );
    public static $types = array(
        self::NOTE_FRAIS_RESTAURATION   => array('label' => 'Restauration', 'classes' => array(''), 'icon' => 'apple'),
        self::NOTE_FRAIS_HEBERGEMENT    => array('label' => 'Hébergement', 'classes' => array(''), 'icon' => 'hotel'),
        self::NOTE_FRAIS_PARKING        => array('label' => 'Parking', 'classes' => array(''), 'icon' => 'map'),
        self::NOTE_FRAIS_ENTRETIEN      => array('label' => 'Entretien véhicule', 'classes' => array(''), 'icon' => 'cogs'),
        self::NOTE_FRAIS_KILOMETERS     => array('label' => 'Indémnités kilométriques', 'classes' => array(''), 'icon' => 'car'),
        self::NOTE_FRAIS_TRANSPORT      => array('label' => 'Transport', 'classes' => array(''), 'icon' => 'bus'),
        self::NOTE_FRAIS_POSTAL         => array('label' => 'Frais postaux', 'classes' => array(''), 'icon' => 'paper-plane'),
        self::NOTE_FRAIS_ABONNEMENT     => array('label' => 'Abonnement', 'classes' => array(''), 'icon' => 'link'),
        self::NOTE_FRAIS_SALON          => array('label' => 'Salon', 'classes' => array(''), 'icon' => 'users'),
        self::NOTE_FRAIS_DIVERS          => array('label' => 'Frais divers', 'classes' => array(''), 'icon' => 'cogs'),
    );
    public static $periods = array(1 => 'Midi',2 => 'Soir');
    public static $amounts_types = array(
        self::NOTE_FRAIS_RESTAURATION,
        self::NOTE_FRAIS_HEBERGEMENT,
        self::NOTE_FRAIS_PARKING,
        self::NOTE_FRAIS_ENTRETIEN,
        self::NOTE_FRAIS_TRANSPORT,
        self::NOTE_FRAIS_POSTAL,
        self::NOTE_FRAIS_ABONNEMENT,
        self::NOTE_FRAIS_SALON,
        self::NOTE_FRAIS_DIVERS
    );
    public static $kilometers_types = array(self::NOTE_FRAIS_KILOMETERS);
    

    public function hasRestauration() {
        return ((int) $this->getData('type') == 1 ? 1 : 0);
    } 

    public function hasAmounts()
    {
        return (in_array((int) $this->getData('type'), self::$amounts_types) ? 1 : 0);
    }

    public function hasKilometers()
    {
        return (in_array((int) $this->getData('type'), self::$kilometers_types) ? 1 : 0);
    }

    public function hasPeriod()
    {
        return (in_array((int) $this->getData('type'), array(1)) ? 1 : 0);
    }

    public function getTotalAmount()
    {
        $total = 0;
        if ($this->isLoaded()) {
            if ($this->hasAmounts()) {
                foreach ($this->getChildrenObjects('montants') as $montant) {
                    $total += (float) $montant->getData('amount');
                }
            } elseif ($this->hasKilometers()) {
                foreach ($this->getChildrenObjects('kilometers') as $km) {
                    $total += (float) $km->getMontant();
                }
            }
        }
        return $total;
    }

    public function displayTotalAmount()
    {
        return BimpTools::displayMoneyValue($this->getTotalAmount(), 'EUR');
    }

    public function getListExtraBtn()
    {
        $buttons = array();

        $status = (int) $this->getData('status');

        if ($status === self::NOTE_FRAIS_ATT_VALIDATION ||
                $status === self::NOTE_FRAIS_REFUSEE) {
            $buttons[] = array(
                'label'   => 'Valider',
                'icon'    => 'check-circle',
                'onclick' => $this->getJsNewStatusOnclick(self::NOTE_FRAIS_ATT_REMBOURSEMENT)
            );
            if ($status === self::NOTE_FRAIS_ATT_VALIDATION) {
                $buttons[] = array(
                    'label'   => 'Refuser',
                    'icon'    => 'times-circle',
                    'onclick' => $this->getJsNewStatusOnclick(self::NOTE_FRAIS_REFUSEE)
                );
            }
        } elseif ($status === self::NOTE_FRAIS_ATT_REMBOURSEMENT) {
            $buttons[] = array(
                'label'   => 'Remboursement effectué',
                'icon'    => 'check',
                'onclick' => $this->getJsNewStatusOnclick(self::NOTE_FRAIS_REMBOURSEE)
            );
        }
        if ($status !== self::NOTE_FRAIS_ATT_VALIDATION && $status !== self::NOTE_FRAIS_REMBOURSEE) {
            $buttons[] = array(
                'label'   => 'Remettre en attente de validation',
                'icon'    => 'undo',
                'onclick' => $this->getJsNewStatusOnclick(self::NOTE_FRAIS_ATT_VALIDATION)
            );
        }

        return $buttons;
    }

    public function getListBulkActions()
    {
        $actions = array();


        $actions[] = array(
            'label'   => 'Supprimer les Notes de frais sélectionnées',
            'icon'    => 'trash',
            'onclick' => 'deleteSelectedObjects(\'list_id\', $(this));'
        );

        $confirm = 'Veuillez confirmer la validation du ticket de caisse pour les notes de frais sélectionnées';
        $actions[] = array(
            'label'   => 'Valider ticket de caisse',
            'icon'    => 'check-square-o',
            'onclick' => 'setSelectedObjectsAction($(this), \'list_id\', \'validateTicket\', {}, null, \'' . $confirm . '\')'
        );

        $confirm = 'Veuillez confirmer la validation des notes de frais sélectionnées';
        $actions[] = array(
            'label'   => 'Valider',
            'icon'    => 'check-circle',
            'onclick' => 'setSelectedObjectsNewStatus($(this), \'list_id\', ' . self::NOTE_FRAIS_ATT_REMBOURSEMENT . ', {}, \'' . $confirm . '\');'
        );

        $confirm = 'Veuillez confirmer le refus des notes de frais sélectionnées';
        $actions[] = array(
            'label'   => 'Refuser',
            'icon'    => 'times-circle',
            'onclick' => 'setSelectedObjectsNewStatus($(this), \'list_id\', ' . self::NOTE_FRAIS_REFUSEE . ', {}, \'' . $confirm . '\');'
        );

        $confirm = 'Veuillez confirmer le remboursement des notes de frais sélectionnées';
        $actions[] = array(
            'label'   => 'Remboursements effectués',
            'icon'    => 'check',
            'onclick' => 'setSelectedObjectsNewStatus($(this), \'list_id\', ' . self::NOTE_FRAIS_REMBOURSEE . ', {}, \'' . $confirm . '\');'
        );

        $confirm = 'Veuillez confirmer la remise en attente de validation des notes de frais sélectionnées';
        $actions[] = array(
            'label'   => 'Remettre en attente de validation',
            'icon'    => 'undo',
            'onclick' => 'setSelectedObjectsNewStatus($(this), \'list_id\', ' . self::NOTE_FRAIS_ATT_VALIDATION . ', {}, \'' . $confirm . '\');'
        );

        $confirm = 'Veuillez confirmer la remise en attente de validation des notes de frais sélectionnées';
        $actions[] = array(
            'label'   => 'Remettre en attente de validation',
            'icon'    => 'undo',
            'onclick' => 'test();'
        );
        return $actions;
    }

    // Actions: 

    public function actionValidateTicket($data, &$success = '')
    {
        $errors = array();

        $success = 'Ticket de caisse validé avec succès';
        if ($this->isLoaded()) {
            $this->set('ticket_validated', 1);
            $errors = $this->update();
        } else {
            $errors[] = 'ID de la note de frais absent';
        }

        return $errors;
    }

    // Overrides: 

    public function checkSubObjectsPost()
    {
        $result = array(
            'errors'           => array(),
            'success_callback' => ''
        );
        if ($this->isLoaded()) {
            if ($this->hasAmounts()) {
                foreach ($_POST as $key => $value) {
                    if (preg_match('/^kilometers_.*$/', $key)) {
                        unset($_POST[$key]);
                    }
                }
            } elseif ($this->hasKilometers()) {
                foreach ($_POST as $key => $value) {
                    if (preg_match('/^montants_.*$/', $key)) {
                        unset($_POST[$key]);
                    }
                }
            }
            $result = parent::checkSubObjectsPost();
        }
        return $result;
    }
    
    public function checkValidateTickets() {
        if(count($this->getChildrenObjects('montants')) > 0){
            $false = 0;
            foreach ($this->getChildrenObjects('montants') as $line) {
                if($line->getData('validate') == 0)
                    $false++;
           }
           if($false == 0) $this->actionValidateTicket('');
           else {
            $this->set('ticket_validated', 0); $this->update();
           }
       }
    }
}
