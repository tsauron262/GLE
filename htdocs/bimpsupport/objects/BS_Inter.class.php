<?php

class BS_Inter extends BimpObject
{

    const BS_INTER_OPEN = 1;
    const BS_INTER_CLOSED = 2;

    public static $priorities = array(
        1 => array('label' => 'Non urgent', 'classes' => array('success'), 'icon' => 'hourglass-start'),
        2 => array('label' => 'Urgent', 'classes' => array('warning'), 'icon' => 'hourglass-half'),
        3 => array('label' => 'Très urgent', 'classes' => array('danger'), 'icon' => 'hourglass-end'),
    );
    public static $status = array(
        1 => array('label' => 'Ouvert', 'classes' => array('success')),
        2 => array('label' => 'Fermé', 'classes' => array('danger'))
    );

    // Getters: 

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
        if (!isset($this->id) || !$this->id) {
            return BimpRender::renderAlerts('intervention non enregistrée');
        }

        $timer = BimpObject::getInstance('bimpcore', 'BimpTimer');

        if (!$timer->find(array(
                    'obj_module' => $this->module,
                    'obj_name'   => $this->object_name,
                    'id_obj'     => (int) $this->id,
                    'field_name' => 'timer'
                ))) {
            if (!$timer->setObject($this, 'timer')) {
                return BimpRender::renderAlerts('Echec de la création du timer');
            }
        }

        if (!isset($timer->id) || !$timer->id) {
            return BimpRender::renderAlerts('Echec de l\'initialisation du timer');
        }

        $html = $timer->render('Chrono Intervention');

        return $html;
    }

    // Overrides

    public function create(&$warnings, $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            if ((int) BimpTools::getValue('start_timer', 0)) {
                $timer = BimpObject::getInstance('bimpcore', 'BimpTimer');
                if (!$timer->setObject($this, 'timer', true)) {
                    $warnings[] = 'Echec de l\'initialisation du chrono appel payant';
                }
            }
        }
    }

    public function update(&$warnings, $force_update = false)
    {
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
                    }
                }
            }
        }
    }

    public function delete($force_delete = false)
    {
        $id = (int) $this->id;

        $errors = parent::delete($force_delete);

        if (!count($errors)) {
            $timer = $this->getTimer();
            if (BimpObject::objectLoaded($timer)) {
                $timer->delete(true);
            }
        }

        return $errors;
    }
}
