<?php

class BC_VentePaiement extends BimpObject
{

    public static $codes = array(
        'LIQ' => array('label' => 'Paiement Liquide', 'icon' => 'fas_money-bill-wave'),
        'CB'  => array('label' => 'Paiement CB', 'icon' => 'fas_credit-card'),
        'CHQ' => array('label' => 'Paiement Chèque', 'icon' => 'fas_pencil-alt'),
        'AE'  => array('label' => 'Paiement American Express', 'icon' => 'fab_cc-amex'),
        'CG'  => array('label' => 'Chèque galerie', 'icon' => 'fas_money-check'),
        'FIN'  => array('label' => 'Financement', 'icon' => 'fas_hand-holding-usd'),
        'FIN_YC'  => array('label' => 'Financement Younited', 'icon' => 'fas_hand-holding-usd')
    );

}
