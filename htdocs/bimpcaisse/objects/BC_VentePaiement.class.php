<?php

class BC_VentePaiement extends BimpObject {
     public static $codes = array(
         'LIQ' => array('label' => 'Paiement Liquide', 'icon' => 'money'),
         'CB' => array('label' => 'Paiement CB', 'icon' => 'credit-card'),
         'CHQ' => array('label' => 'Paiement Chèque', 'icon' => 'pencil'),
         'AE' => array('label' => 'Paiement American Express', 'icon' => 'pencil'),
         'CG' => array('label' => 'Chèque gallerie', 'icon' => 'envelope'),
         'no' => array('label' => 'Restera a payé', 'icon' => 'times-circle')
     );
}