<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class BimpValidationCronExec extends BimpCron
{

    const LIMIT_OBJECT = 3;
    const LIMIT_DAYS = 1;

    public function sendRappels()
    {
        // Envoie un rappel aux valideur d'objet commerciaux (devis, commande, facture ...)
        
//        $nb_mail_envoyer = 0;
//        $nb_validation_rappeler = 0;
//        $now = new DateTime();
//
//        $errors = array();
//        $user_demands = array();
//        if (!BimpObject::loadClass('bimpvalidateorder', 'DemandeValidComm')) {
//            $errors[] = "Impossile de charger la classe DemandeValidComm";
//            return $errors;
//        }
//
//        $sql = BimpTools::getSqlSelect(array('type_de_piece', 'id_piece', 'id_user_ask', 'id_user_affected', 'type', 'date_create'));
//        $sql .= BimpTools::getSqlFrom('demande_validate_comm');
//        $sql .= BimpTools::getSqlWhere(array('status' => 0));
//        $rows = BimpCache::getBdb()->executeS($sql, 'array');
//
//        // Remplissage d'un tableau id_user => array(demande_validation_1, demande_validation_2)
//        if (is_array($rows)) {
//            foreach ($rows as $r) {
//
//                $date_create = new DateTime($r['date_create']);
//                $key = $r['type'] . '_' . $r['id_piece'];
//
//                $interval = date_diff($date_create, $now);
//
//                // Enregistrement du nombre de jour qui sépare aujourd'hui de
//                //  la date de création de la demande
//                $r['diff'] = $interval->format('%d');
//
//                $r['date_create'] = $date_create->format('d/m/yy H:i:s');
//                if (!isset($user_demands[$r['id_user_affected']])) {
//                    $user_demands[$r['id_user_affected']] = array();
//                }
//
//                // Cet utilisateur doit recevoir un mail même si il n'a pas beaucoup 
//                // de demande en cours, car l'un d'entre elles est trop ancienne
//                if (self::LIMIT_DAYS < $r['diff']) {
//                    $user_demands[$r['id_user_affected']]['urgent'] = 1;
//                    $r['urgent'] = 1;
//                }
//
//                $user_demands[$r['id_user_affected']][$key] = $r;
//            }
//        }
//
//        // Foreach sur users
//        foreach ($user_demands as $id_user => $tab_demand) {
//            $s = '';
//            $nb_demand = (int) sizeof($tab_demand);
//            if (isset($tab_demand['urgent']))
//                $nb_demand--;
//
//            // Il y a plus de demande que toléré ou il y a une demande très ancienne
//            if (self::LIMIT_OBJECT <= $nb_demand or isset($tab_demand['urgent'])) {
//
//                if (1 < $nb_demand)
//                    $s = 's';
//
//                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);
//                $subject = $nb_demand . " demande$s de validation en cours";
//                $message = "Bonjour " . $user->getData('firstname') . ",<br/>";
//                $message .= "Vous avez $nb_demand demande$s de validation en cours, voici le$s lien$s<br/>";
//
//                foreach ($tab_demand as $key => $demand) {
//
//                    // Ignorer l'entré pour signaler que cet utilisateur a des demandes urgente à traiter
//                    if ($key == 'urgent')
//                        continue;
//
//                    $obj = DemandeValidComm::getObject($demand['type_de_piece'], $demand['id_piece']);
//                    $message .= $obj->getNomUrl() . ' (demande: ' . $demand['date_create'] . ', ';
//
//                    if (isset($demand['urgent']))
//                        $message .= '<strong color="red">' . $demand['diff'] . ' jour' . ((1 < $demand['diff']) ? 's' : '' ) . ')</strong><br/>';
//                    else
//                        $message .= $demand['diff'] . ' jour' . ((1 < $demand['diff']) ? 's' : '' ) . ')<br/>';
//                }
//
//
//                mailSyn2($subject, $user->getData('email'), null, $message);
//
//                $nb_validation_rappeler += $nb_demand;
//                ++$nb_mail_envoyer;
//            } else
//                $nb_validation_ignorer += $nb_demand;
//        }
//
//
//        $this->output = "Nombre de mails envoyés " . $nb_mail_envoyer . "<br/>";
//        $this->output .= "Nombre de validations rappelés " . $nb_validation_rappeler . "<br/>";
//        $this->output .= "Nombre de validations ignorés " . $nb_validation_ignorer . "<br/>";
//        if (count($errors))
//            $this->output .= "Erreurs " . print_r($errors, 1) . "<br/>";

        return 0;
    }
}
