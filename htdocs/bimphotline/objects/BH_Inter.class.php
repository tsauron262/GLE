<?php

class BH_Inter extends BimpObject
{

    public static $priorities = array(
        1 => array('label' => 'Urgent', 'classes' => array('danger')),
        2 => array('label' => 'non urgent', 'classes' => array('info')),
    );
    public static $status = array(
        1 => array('label' => 'Ouvert', 'classes' => array('success')),
        2 => array('label' => 'Fermé', 'classes' => array('danger'))
    );

    public function renderDefaultView()
    {
        $status = (int) $this->getData('status');

        if ($status !== 2) {
            $tech_id_user = (int) $this->getData('tech_id_user');
            global $user;

            if (isset($user->id) && $user->id && !is_null($tech_id_user) && $tech_id_user) {
                if ($tech_id_user === (int) $user->id) {
                    return $this->renderView('full', false);
                }
            }
        }

        return $this->renderView('data', false);
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

    public function getUserCurrentIntersFilters()
    {
        global $user;
        if (isset($user->id) && $user - id) {
            return array(
                'or_user' => array(
                    'or' => array(
                        'tech_id_user' => $user->id,
                        'user_create'  => $user->id
                    )
                ),
                'status'  => array(
                    'operator' => '!=',
                    'value'    => 2
                )
            );
        }

        return array(
            'id' => 0
        );
    }
}
