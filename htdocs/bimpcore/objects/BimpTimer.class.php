<?php

class BimpTimer extends BimpObject
{

    public function setObject(Bimpobject $object, $object_time_field)
    {
        $this->reset();
        if (is_null($object) || !isset($object->id) || !$object->id) {
            return false;
        }

        global $user;

        $this->set('obj_module', $object->module);
        $this->set('obj_name', $object->object_name);
        $this->set('id_obj', $object->id);
        $this->set('field_name', $object_time_field);
        $this->set('time_session', 0);
        $this->set('id_user', $user->id);

        if (count($this->create())) {
            return false;
        }
        return true;
    }

    public function getTimes($object)
    {
        $times = array(
            'start'   => (int) $this->getData('session_start'),
            'session' => (int) $this->getData('time_session'),
            'total'   => $object->getData($this->getData('field_name')),
        );

        if (is_null($times['total'])) {
            $times['total'] = 0;
        }

        if (is_null($times['session'])) {
            $times['session'] = 0;
        }

        $times['total'] += $times['session'];

        if (is_null($times['start'])) {
            $times['start'] = 0;
        }

        return $times;
    }

    public function render($title, $drop_up = false)
    {
        if (!isset($this->id) || !$this->id) {
            return '';
        }

        $module = $this->getData('obj_module');
        $object_name = $this->getData('obj_name');
        $id_object = (int) $this->getData('id_obj');
        $field = $this->getData('field_name');

        $object = BimpObject::getInstance($module, $object_name);
        if (!$object->fetch($id_object)) {
            $msg = BimpTools::ucfirst($object->getLabel('')) . ' d\'ID ' . $id_object . ' non trouvé';
            if ($object->isLabelFemale()) {
                $msg .= 'e';
            }
            return BimpRender::renderAlerts($msg);
        }

        $times = $this->getTimes($object);

        $timer_id = $object_name . '_' . $id_object . '_timer';

        $html = '';
        $html .= '<div class="' . $timer_id . ' bimp_timer" data-timer_id="' . $timer_id . '">';

        $html .= '<div class="bimp_timer_header">';
        $html .= '<h4 class="title">' . $title . '</h4>';
        $html .= '</div>';

        $html .= '<div class="' . $timer_id . '_total_time bimp_timer_total_time bimp_timer_time">';
        $html .= '<div class="title">Durée totale:</div>';
        $html .= $this->renderTime(BimpTools::getTimeDataFromSeconds($times['total']));
        $html .= '</div>';

        $html .= '<div class="' . $timer_id . '_current_time bimp_timer_current_time bimp_timer_time">';
        $html .= '<div class="title">Durée session:</div>';
        $html .= $this->renderTime(BimpTools::getTimeDataFromSeconds($times['session']));
        $html .= '</div>';

        $html .= '<div class="bimp_timer_footer">';

        $buttons = array();
        $button = '<button type="button" class="btn btn-light-default bimp_timer_reset_current_btn" onclick="bimp_timers[\'' . $timer_id . '\'].resetCurrent();">';
        $button .= '<i class="fa fa-history iconLeft"></i>Réinitialiser la durée de la session</button>';
        $buttons[] = $button;
        $button = '<button type="button" class="btn btn-light-default bimp_timer_reset_total_btn" onclick="bimp_timers[\'' . $timer_id . '\'].resetTotal();">';
        $button .= '<i class="fa fa-history iconLeft"></i>Réinitialiser la durée totale</button>';
        $buttons[] = $button;
        $button = '<button type="button" class="btn btn-light-default bimp_timer_cancel_reset_btn"';
        $button .= ' style="display: none" onclick="bimp_timers[\'' . $timer_id . '\'].cancelLastReset();">';
        $button .= '<i class="fa fa-times iconLeft"></i>Annuler la dernière réinitialisation</button>';
        $buttons[] = $button;

        $html .= BimpRender::renderDropDownButton('Actions', $buttons, array('icon' => 'fas_cogs', 'drop_up' => $drop_up));

        $html .= '<button type="button" class="btn btn-primary bimp_timer_save_btn" onclick="bimp_timers[\'' . $timer_id . '\'].save();">';
        $html .= '<i class="fas fa5-save iconLeft"></i>Enregistrer</button>';
        $html .= '<button type="button" class="btn btn-success bimp_timer_start_btn" onclick="bimp_timers[\'' . $timer_id . '\'].start();"><i class="fa fa-play iconLeft"></i>Démarrer</button>';
        $html .= '<button type="button" class="btn btn-warning bimp_timer_pause_btn" style="display: none" onclick="bimp_timers[\'' . $timer_id . '\'].pause();"><i class="fa fa-pause iconLeft"></i>Suspendre</button>';

        $html .= '</div>';
        $html .= '</div>';

        $html .= '<script type="text/javascript">';
        $html .= ' if (typeof(bimp_timers) === \'undefined\') var bimp_timers = [];';
        $html .= ' if (typeof(bimp_timers[\'' . $timer_id . '\']) !== \'undefined\') { bimp_timers[\'' . $timer_id . '\'].is_pause = true; delete bimp_timers[\'' . $timer_id . '\'];}';
        $html .= ' bimp_timers[\'' . $timer_id . '\'] = new BimpTimer(' . $this->id . ', \'' . $timer_id . '\', \'' . $object->module . '\', \'' . $object->object_name . '\', ' . $object->id . ', \'' . $field . '\', ' . $times['total'] . ', ' . $times['session'] . ');';
        if ((int) $times['start']) {
            $html .= ' bimp_timers[\'' . $timer_id . '\'].session_start = ' . (int) $times['start'] . ';';
            $html .= ' bimp_timers[\'' . $timer_id . '\'].start();';
        }
        $html .= '</script>';

        return $html;
    }

    public function renderTime($timer)
    {
        $hideDays = false;
        $hideHours = false;
        $hideMinutes = false;

        if (!isset($timer['days']) || !(int) $timer['days']) {
            $hideDays = true;
            if (!isset($timer['hours']) || !(int) $timer['hours']) {
                $hideHours = true;
                if (!isset($timer['minutes']) || !(int) $timer['minutes']) {
                    $hideMinutes = true;
                }
            }
        }
        $html = '';
        $html .= '<span class="bimp_timer_days bimp_timer_value"' . ($hideDays ? ' style="display: none"' : '') . '>' . $timer['days'] . '</span>';
        $html .= '<span class="bimp_timer_label bimp_timer_days_label"' . ($hideDays ? ' style="display: none"' : '') . '>j</span>';
        $html .= '<span class="bimp_timer_hours bimp_timer_value"' . ($hideHours ? ' style="display: none"' : '') . '>' . $timer['hours'] . '</span>';
        $html .= '<span class="bimp_timer_label bimp_timer_hours_label"' . ($hideHours ? ' style="display: none"' : '') . '>h</span>';
        $html .= '<span class="bimp_timer_minutes bimp_timer_value"' . ($hideMinutes ? ' style="display: none"' : '') . '>' . $timer['minutes'] . '</span>';
        $html .= '<span class="bimp_timer_label bimp_timer_minutes_label"' . ($hideMinutes ? ' style="display: none' : '') . '">min</span>';
        $html .= '<span class="bimp_timer_secondes bimp_timer_value">' . $timer['secondes'] . '</span>';
        $html .= '<span class="bimp_timer_label bimp_timer_secondes_label">sec</span>';
        
        return $html;
    }
}
