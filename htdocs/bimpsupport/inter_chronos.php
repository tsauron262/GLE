<?php

$timer = BimpObject::getInstance('bimpcore', 'BimpTimer');

$timers = $timer->getList(array(
    'obj_module' => 'bimpsupport',
    'obj_name'   => array(
        'in' => array('\'BS_Ticket\'', '\'BS_Inter\'')
    ),
    'session'    => array(
        'or' => array(
            'time_session'  => array(
                'operator' => '>',
                'value'    => 0
            ),
            'session_start' => array(
                'operator' => '>',
                'value'    => 0
            )
        )
    )
        ), null, null, 'id', 'desc', 'array', array('id', 'obj_name'));

displayTimers($timer, $timers);

function displayTimers($timer, $timers)
{
    if (!count($timers)) {
        return;
    }

    global $bimp_fixe_tabs, $user;

    if (!isset($user->id) || !$user->id) {
        return;
    }

    $bimp_fixe_tabs->addJsFile('/bimpcore/views/js/BimpTimer.js');

    $ticket = BimpObject::getInstance('bimpsupport', 'BS_Ticket');
    $inter = BimpObject::getInstance('bimpsupport', 'BS_Inter');

    foreach ($timers as $t) {
        $timer->reset();
        $ticket->reset();
        $inter->reset();
        if (!isset($t['id']) || !(int) $t['id']) {
            continue;
        }

        if (!$timer->fetch((int) $t['id'])) {
            $errors[] = 'Echec du chargement du timer d\'ID ' . $t['id'];
            continue;
        }

        if ($t['obj_name'] === 'BS_Inter') {
            $id_inter = $timer->getData('id_obj');
            if (is_null($id_inter) || !$id_inter) {
                $errors[] = 'ID de l\'intervention absent pour le timer d\'ID ' . $timer->id;
                continue;
            }

            if (!$inter->fetch((int) $timer->getData('id_obj'))) {
                $errors[] = 'Echec du chargement de l\'intervention n°' . $timer->getData('id_obj');
                continue;
            }

            $id_tech = (int) $inter->getData('tech_id_user');
            if (is_null($id_tech) || !$id_tech || ((int) $id_tech !== (int) $user->id)) {
                continue;
            }
            $id_ticket = $inter->getData('id_ticket');
            if (is_null($id_ticket) || !$id_ticket) {
                $errors[] = 'ID du ticket absent pour l\'intervention n°' . $inter->id;
                continue;
            }

            if (!$ticket->fetch((int) $id_ticket)) {
                $errors[] = 'Echec du chargement du ticket n°' . $id_ticket;
                continue;
            }

            $id = 'inter_chrono_' . $inter->id . '_fixe_tab';
            $times = $timer->getTimes($inter);
            $caption = 'Inter hotline ' . $inter->id;
            $caption .= '&nbsp;&nbsp;<span class="BS_Inter_' . $inter->id . '_timer_total_time chrono bold">';
            $caption .= $timer->renderTime(BimpTools::getTimeDataFromSeconds($times['total']));
            $caption .= '</span>';

            $timer_title = 'Inter ' . $inter->id;
            $ticket_url = DOL_URL_ROOT . '/bimpsupport/index.php?fc=ticket&id=' . $ticket->id;
            $timer_title .= '&nbsp;&nbsp;<a style="float: right" class="btn btn-primary" href="' . $ticket_url . '"><i class="fa fa-file-o iconLeft"></i>Afficher</a>';

            $client = $ticket->getChildObject('client');
            if (!is_null($client) && isset($client->id) && $client->id) {
                $timer_title .= '<br/>Client: <span class="bold">' . $client->nom . '</span>';
            }
            $timer_title .= '<br/>Ticket: <span class="bold">' . $ticket->getData('ticket_number') . '</span>';
            $content = $timer->render($timer_title, true);

            $bimp_fixe_tabs->addTab($id, $caption, $content);
        } elseif ($t['obj_name'] === 'BS_Ticket') {

            $id_ticket = $timer->getData('id_obj');
            if (is_null($id_ticket) || !$id_ticket) {
                $errors[] = 'ID du ticket absent pour l\'intervention n°' . $inter->id;
                continue;
            }

            if (!$ticket->fetch((int) $id_ticket)) {
                $errors[] = 'Echec du chargement du ticket n°' . $id_ticket;
                continue;
            }

            $id_user = (int) $timer->getData('id_user');

            if (is_null($id_user) || !$id_user || $id_user !== (int) $user->id) {
                continue;
            }

            $id = 'appel_chrono_' . $ticket->id . '_fixe_tab';
            $times = $timer->getTimes($ticket);
            $caption = 'Appel hotline - Ticket ' . $ticket->id;
            $caption .= '&nbsp;&nbsp;<span class="BS_Ticket_' . $ticket->id . '_timer_total_time chrono bold">';
            $caption .= $timer->renderTime(BimpTools::getTimeDataFromSeconds($times['total']));
            $caption .= '</span>';

            $timer_title = 'Appel Ticket ' . $ticket->id;
            $ticket_url = DOL_URL_ROOT . '/bimpsupport/index.php?fc=ticket&id=' . $ticket->id;
            $timer_title .= '&nbsp;&nbsp;<a style="float: right" class="btn btn-primary" href="' . $ticket_url . '"><i class="fa fa-file-o iconLeft"></i>Afficher</a>';

            $client = $ticket->getChildObject('client');
            if (!is_null($client) && isset($client->id) && $client->id) {
                $timer_title .= '<br/>Client: <span class="bold">' . $client->nom . '</span>';
            }
            $timer_title .= '<br/>Ticket: <span class="bold">' . $ticket->getData('ticket_number') . '</span>';
            $content = $timer->render($timer_title, true);

            $bimp_fixe_tabs->addTab($id, $caption, $content);
        }
    }

    $bimp_fixe_tabs->errors = BimpTools::merge_array($bimp_fixe_tabs->errors, $errors);
}
