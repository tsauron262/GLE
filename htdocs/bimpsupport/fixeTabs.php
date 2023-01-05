<?php

require_once DOL_DOCUMENT_ROOT . "/bimpcore/classes/FixeTabs_module.class.php";

class FixeTabs_bimpsupport extends FixeTabs_module
{

    function init()
    {
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

        $this->initTimers($timers);
    }

    function can($right)
    {
//            if (userInGroupe(18, $this->user->id))
        return 1;
    }

    function displayHead()
    {
        $html = '<script type="text/javascript" src="' . DOL_URL_ROOT . '/bimpcore/views/js/BimpTimer.js"></script>';
        return $html;
    }

    function initTimers($timers)
    {
        if (!count($timers)) {
            return;
        }

        if (!isset($this->user->id) || !$this->user->id) {
            return;
        }

        foreach ($timers as $t) {
            if (!isset($t['id']) || !(int) $t['id']) {
                continue;
            }
            $timer = BimpCache::getBimpObjectInstance('bimpcore', 'BimpTimer', (int) $t['id']);
            if (!$timer->isLoaded()) {
                $errors[] = 'Echec du chargement du timer d\'ID ' . $t['id'];
                continue;
            }

            if ($t['obj_name'] === 'BS_Inter') {
                $id_inter = $timer->getData('id_obj');
                if (is_null($id_inter) || !$id_inter) {
                    $errors[] = 'ID de l\'intervention absent pour le timer d\'ID ' . $timer->id;
                    continue;
                }

                $inter = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Inter', (int) $timer->getData('id_obj'));
                if (!$inter->isLoaded()) {
                    $errors[] = 'Echec du chargement de l\'intervention n°' . $timer->getData('id_obj');
                    $timer->delete();
                    continue;
                }

                $id_tech = (int) $inter->getData('tech_id_user');
                if (is_null($id_tech) || !$id_tech || ((int) $id_tech !== (int) $this->user->id)) {
                    continue;
                }
                $id_ticket = $inter->getData('id_ticket');
                if (is_null($id_ticket) || !$id_ticket) {
                    $errors[] = 'ID du ticket absent pour l\'intervention n°' . $inter->id;
                    continue;
                }

                $ticket = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Ticket', (int) $id_ticket);
                if (!$ticket->isLoaded()) {
                    $errors[] = 'Echec du chargement du ticket n°' . $id_ticket;
                    $timer->delete();
                    continue;
                }

                $id = 'inter_chrono_' . $inter->id . '_fixe_tab';
                $times = $timer->getTimes($inter);
                $caption = '<i class="' . BimpRender::renderIconClass('fas_user-clock') . ' iconLeft"></i>';
                $caption .= 'Inter ' . $inter->id;
                $caption .= '&nbsp;&nbsp;<span class="BS_Inter_' . $inter->id . '_timer_total_time chrono bold">';
                $caption .= $timer->renderTime(BimpTools::getTimeDataFromSeconds($times['total']));
                $caption .= '</span>';

                $timer_title = 'Inter ' . $inter->id;
                $ticket_url = DOL_URL_ROOT . '/bimpsupport/index.php?fc=ticket&id=' . $ticket->id . '&navtab=inters&id_inter=' . $inter->id;
                $timer_title .= '<div style="float: right">&nbsp;&nbsp;';
                $timer_title .= '<a class="btn btn-default" href="' . $ticket_url . '"><i class="far fa5-file iconLeft"></i>Afficher</a>';
                $timer_title .= '<span class="btn btn-default bs-popover" ';
                $timer_title .= BimpRender::renderPopoverData('Vue rapide', 'top', 'true');
                $timer_title .= ' onclick="' . $inter->getJsLoadModalView() . '"';
                $timer_title .= '><i class="' . BimpRender::renderIconClass('fas_eye') . '"></i></span>';

                if ($inter->isActionAllowed('close') && $inter->canSetAction('close')) {
                    $timer_title .= '<div style="text-align: right; margin-bottom: 5px">';
                    $timer_title .= '<span class="btn btn-danger" ';
                    $timer_title .= 'onclick="' . $inter->getJsActionOnclick('close', array(), array('form_name' => 'resolution')) . '">';
                    $timer_title .= '<i class="' . BimpRender::renderIconClass('fas_times') . ' iconLeft"></i>Clôturer';
                    $timer_title .= '</span>';
                    $timer_title .= '</div>';
                }
                $timer_title .= '</div>';
                $timer_title .= '<div class="clearAfter"></div>';

                $client = $ticket->getChildObject('client');
                if (BimpObject::objectLoaded($client)) {
                    $timer_title .= '<br/>Client: <span class="bold">' . $client->getData('nom') . '</span>';
                }
                $timer_title .= '<br/>Ticket: <span class="bold">' . $ticket->getData('ticket_number') . '</span>';
                $content = $timer->render($timer_title, true);

                $this->bimp_fixe_tabs->addTab($id, $caption, $content);
            } elseif ($t['obj_name'] === 'BS_Ticket') {

                $id_ticket = $timer->getData('id_obj');
                if (is_null($id_ticket) || !$id_ticket) {
                    $errors[] = 'ID du ticket absent pour l\'intervention n°' . $inter->id;
                    continue;
                }


                $ticket = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Ticket', (int) $id_ticket);
                if (!$ticket->isLoaded()) {
                    $errors[] = 'Echec du chargement du ticket n°' . $id_ticket;
                    $timer->delete();
                    continue;
                }

                $id_user = (int) $timer->getData('id_user');

                if (is_null($id_user) || !$id_user || $id_user !== (int) $this->user->id) {
                    continue;
                }

                $id = 'appel_chrono_' . $ticket->id . '_fixe_tab';
                $times = $timer->getTimes($ticket);
                $caption = '<i class="' . BimpRender::renderIconClass('fas_headset') . ' iconLeft"></i>';
                $caption .= 'Ticket ' . $ticket->id;
                $caption .= '&nbsp;&nbsp;<span class="BS_Ticket_' . $ticket->id . '_timer_total_time chrono bold">';
                $caption .= $timer->renderTime(BimpTools::getTimeDataFromSeconds($times['total']));
                $caption .= '</span>';

                $timer_title = 'Appel Ticket ' . $ticket->id;
                $ticket_url = DOL_URL_ROOT . '/bimpsupport/index.php?fc=ticket&id=' . $ticket->id;
                $timer_title .= '&nbsp;&nbsp;<a style="float: right" classclass="btn btn-default" href="' . $ticket_url . '"><i class="fa fa-file-o iconLeft"></i>Afficher</a>';

                $client = $ticket->getChildObject('client');
                if (BimpObject::objectLoaded($client)) {
                    $timer_title .= '<br/>Client: <span class="bold">' . $client->getData('nom') . '</span>';
                }
                $timer_title .= '<br/>Ticket: <span class="bold">' . $ticket->getData('ticket_number') . '</span>';
                $content = $timer->render($timer_title, true);

                $this->bimp_fixe_tabs->addTab($id, $caption, $content);
            }
        }

        if (count($errors)) {
            $this->bimp_fixe_tabs->errors[] = BimpTools::getMsgFromArray($errors);
        }
    }
}
