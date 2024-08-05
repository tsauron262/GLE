<?php

class BC_VentePaiement extends BimpObject
{

    public static $codes = array(
        'LIQ' => array('label' => 'Paiement Liquide', 'icon' => 'money'),
        'CB'  => array('label' => 'Paiement CB', 'icon' => 'credit-card'),
        'CHQ' => array('label' => 'Paiement Chèque', 'icon' => 'pencil'),
        'AE'  => array('label' => 'Paiement American Express', 'icon' => 'cc-amex'),
        'CG'  => array('label' => 'Chèque galerie', 'icon' => 'fas_money-check'),
        'FIN'  => array('label' => 'Financement', 'icon' => 'fas_hand-holding-usd'),
        'FIN_YC'  => array('label' => 'Financement Younited', 'icon' => 'fas_hand-holding-usd')
    );

}
