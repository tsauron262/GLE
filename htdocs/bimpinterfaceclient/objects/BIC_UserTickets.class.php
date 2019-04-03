<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class BIC_UserTickets extends BimpObject {

    const BS_TICKET_EN_COURS = 1;
    const BS_TICKET_ATT_CLIENT = 2;
    const BS_TICKET_ATT_COMMERCIAL = 3;
    const BS_TICKET_ATT_TECHNICIEN = 4;
    const BS_TICKET_ATT_PRESTATAIRE = 5;
    const BS_TICKET_DEMANDE_CLIENT = 20;
    const BS_TICKET_CLOT = 999;

    public static $status_list = array(
        self::BS_TICKET_DEMANDE_CLIENT => array('label' => 'Demande client', 'icon' => 'fas_cogs', 'classes' => array('important')),
        self::BS_TICKET_EN_COURS => array('label' => 'En cours', 'icon' => 'fas_cogs', 'classes' => array('info')),
        self::BS_TICKET_ATT_CLIENT => array('label' => 'En attente client', 'icon' => 'fas_hourglass-start', 'classes' => array('important')),
        self::BS_TICKET_ATT_COMMERCIAL => array('label' => 'En attente commercial', 'icon' => 'fas_hourglass-start', 'classes' => array('important')),
        self::BS_TICKET_ATT_TECHNICIEN => array('label' => 'En attente technicien', 'icon' => 'fas_hourglass-start', 'classes' => array('important')),
        self::BS_TICKET_ATT_PRESTATAIRE => array('label' => 'En attente prestataire', 'icon' => 'fas_hourglass-start', 'classes' => array('important')),
        self::BS_TICKET_CLOT => array('label' => 'Clos', 'icon' => 'fas_times', 'classes' => array('danger')),
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
    
    public static $priorities = array(
        1 => array('label' => 'Non urgent', 'classes' => array('success'), 'icon' => 'hourglass-start'),
        2 => array('label' => 'Urgent', 'classes' => array('warning'), 'icon' => 'hourglass-half'),
        3 => array('label' => 'Très urgent', 'classes' => array('danger'), 'icon' => 'hourglass-end'),
    );
    
    public function getCouverture() {
        global $couverture;
        return $couverture;
    }

    public function canClientView() {
        return true;
    }

    public function canClientEdit() {
        return true;
    }

    public function canClientCreate() {
        return true;
    }
    
    

}
