<?php

class public_ticketsController extends BimpPublicController {
    public function renderHtml() {
        global $userClient;
        $html = '';
        
        $html .= BimpRender::renderAlerts('<b>Pour créer un ticket support, merci de sélectionner un contrat sur <a href="?">la page d\'accueil</a></b>', 'warning', false);
        
        $html .= '<div class="page_content container-fluid">';
        $instance = BimpObject::getInstance('bimpinterfaceclient', 'BIC_UserClientTicket');
        $list = new BC_ListTable($instance, 'pageClient', 1, null, 'Liste des tickets support', 'fas_ticket-alt');
        $list->addFieldFilterValue('id_client', (int) $userClient->getData('id_client'));
        $html .= $list->renderHtml();
        $html .= '</div>';
        return $html;
    }
}
