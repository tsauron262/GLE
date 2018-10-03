<?php

class BC_VentePaiement extends BimpObject {
     public static $codes = array(
         'LIQ' => array('label' => 'Paiement Liquide', 'icon' => 'money'),
         'CB' => array('label' => 'Paiement CB', 'icon' => 'credit-card'),
         'CHQ' => array('label' => 'Paiement Chèque', 'icon' => 'pencil'),
         'AE' => array('label' => 'Paiement American Express', 'icon' => 'pencil')
     );
}