<?php

class Bimp_Commande extends BimpObject {
     public function actionRemoveProducts($data, &$success)
     {
         $success = 'Produits retirés de la commande avec succès';
         $errors = array();
         $warnings = array();
         
         
         
         return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
     }
}