<?php

class BS_Inter extends BimpObject
{

    const BS_INTER_OPEN = 1;
    const BS_INTER_CLOSED = 2;

    public static $status = array(
        1 => array('label' => 'En cours', 'classes' => array('success')),
        2 => array('label' => 'Terminée', 'classes' => array('danger'))
    );

    // Droits users: 

    public function canClientView()
    {
        if ($this->isLoaded()) {
            return (int) $this->getData('is_public');
        }
        return 1;
    }

    // Getters booléens: 

    public function isCreatable($force_create = false, &$errors = array())
    {
        $parent = $this->getParentInstance();
        if (BimpObject::objectLoaded($parent) && is_a($parent, 'BS_Ticket')) {
            if ((int) $parent->getData('status') !== BS_Ticket::BS_TICKET_CLOT) {
                return 1;
            } else {
                $errors[] = 'Le ticket est clos';
            }
        } else {
            $errors[] = 'Le ticket n\'existe plus';
        }
        return 0;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'close':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if ($this->getData('status') === self::BS_INTER_CLOSED) {
                    $errors[] = 'Intervention déjà fermée';
                    return 0;
                }
                return 1;
        }
        parent::isActionAllowed($action, $errors);
    }

    // Getters données: 

    public function getUserCurrentIntersFilters()
    {
        global $user;
        if (isset($user->id) && $user->id) {
            return array(
                array(
                    'name'   => 'or_user',
                    'filter' => array(
                        'or' => array(
                            'a.tech_id_user' => (int) $user->id,
                            'a.user_create'  => (int) $user->id
                        )
                    )
                ),
                array(
                    'name'   => 'a.status',
                    'filter' => array(
                        'operator' => '!=',
                        'value'    => 2
                    )
                )
            );
        }

        return array(
            'a.id' => 0
        );
    }

    public function getTimer()
    {
        if ($this->isLoaded()) {
            $timer = BimpObject::getInstance('bimpcore', 'BimpTimer');
            if ($timer->find(array(
                        'obj_module' => $this->module,
                        'obj_name'   => $this->object_name,
                        'id_obj'     => $this->id,
                        'field_name' => 'timer'
                            ), false, true)) {
                return $timer;
            }
        }
        return null;
    }

    public function getListFiltersInterfaceClient()
    {
        return Array(
            Array(
                'name'   => 'id_ticket',
                'filter' => $_REQUEST['id']
            )
        );
    }

    // Rendus HTML:

    public function renderDefaultView()
    {
        $status = (int) $this->getData('status');

        if ($status !== self::BS_INTER_CLOSED) {
            $tech_id_user = (int) $this->getData('tech_id_user');
            global $user;

            if (isset($user->id) && $user->id && !is_null($tech_id_user) && $tech_id_user) {
                if ($tech_id_user === (int) $user->id) {
                    return $this->renderView('full', false);
                }
            }
        }

        return $this->renderView('data_only', false);
    }

    public function renderChronoView()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('intervention non enregistrée');
        }

        if ((int) $this->getData('status') === self::BS_INTER_CLOSED) {
            return '';
        }

        if ((int) $this->getData('status') === self::BS_INTER_CLOSED) {
            return '';
        }

        global $user;

        if ((int) $user->id !== (int) $this->getData('tech_id_user')) {
            return '';
        }

        $timer = $this->getTimer();

        if (!BimpObject::objectLoaded($timer)) {
            if (is_null($timer)) {
                $timer = BimpObject::getInstance('bimpcore', 'BimpTimer');
            }

            if (!$timer->setObject($this, 'timer')) {
                return BimpRender::renderAlerts('Echec de la création du timer');
            }
        }

        if (!BimpObject::objectLoaded($timer)) {
            return BimpRender::renderAlerts('Echec de l\'initialisation du timer');
        }

        $html = $timer->render('Chrono Intervention');

        return $html;
    }

    // Actions: 
    public function actionClose($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Intervention fermée avec succès';

        if (isset($data['resolution'])) {
            $this->set('resolution', $data['resolution']);
        }
        $this->set('status', self::BS_INTER_CLOSED);
        $errors = $this->update($warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides

    public function create(&$warnings = array(), $force_create = false)
    {
        if (!$this->isCreatable($force_create)) {
            return array('Ticket clos. Impossible de créer une nouvelle intervention');
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $ticket = $this->getParentInstance();
            if ($ticket->getData('id_user_client') > 0) {
                $url = $ticket->getPublicUrl(false);
                $userClient = BimpObject::getInstance('bimpinterfaceclient', 'BIC_UserClient', $ticket->getData('id_user_client'));
                $to = $userClient->getData('email');
                $cc = implode(',', $userClient->get_dest('admin'));
                $subject = 'Intervention sur votre ticket ' . $ticket->getData('ticket_number');

                $msg = 'Bonjour,<br/><br/>';
                $msg .= 'Une intervention a été créée sur votre ';

                if ($url) {
                    '<a href="' . $url . '">';
                }

                $msg .= 'ticket support N° ' . $ticket->getData('ticket_number');

                if ($url) {
                    '</a>';
                }

                $bimpMail = new BimpMail($this, $subject, $to, '', $msg, '', $cc);
                $mail_errors = array();
                $bimpMail->send($mail_errors);

                if (count($mail_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($mail_errors, 'Echec de l\'envoi de l\'e-mail de confirmation au client');
                }

                $to = implode(',', $userClient->get_dest('commerciaux'));

                if ($to) {
                    $link = $ticket->getLink(array(), 'private');
                    $msg = 'Une intervention a été créée sur le ticket support ' . $link;
                    mailSyn2("BIMP - Intervention sur le ticket : " . $ticket->getData('ticket_number'), $to, '', $msg);
                }
            }

            if ((int) BimpTools::getValue('start_timer', 0)) {
                $timer = BimpObject::getInstance('bimpcore', 'BimpTimer');
                if (!$timer->setObject($this, 'timer', true, (int) $this->getData('tech_id_user'))) {
                    $warnings[] = 'Echec de l\'initialisation du chrono appel payant';
                }
            }
        }
        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = array();

        if ($this->getData('status') === self::BS_INTER_OPEN) {
            $ticket = $this->getParentInstance();
            if (BimpObject::objectLoaded($ticket)) {
                if ((int) $ticket->getData('status') === BS_Ticket::BS_TICKET_CLOT) {
                    $errors[] = 'Cette intervention ne peut pas être ouverte car le ticket hotline est clos';
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }
        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if ((int) $this->getData('status') === self::BS_INTER_CLOSED) {
                $timer = $this->getTimer();
                if (BimpObject::objectLoaded($timer)) {
                    $timer_errors = $timer->hold();
                    if (count($timer_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($timer_errors, 'Echec de l\'arrêt du chronomètre');
                    } else {
                        $times = $timer->getTimes($this);
                        $this->updateField('timer', (int) $times['total']);
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
}
