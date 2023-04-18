<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class BimpCommandeCronExec extends BimpCron
{

    public function remindEndLine($days = 60)
    {
        $user_line = $this->getLinesToRemind($days);
        $this->sendRappel($user_line);
        return 0;
    }

    /**
     * 
     * @param int $days nombre de jour avant de considérer une ligne de commande comme expirée
     * @return array $user_line[$id_user][$id_commande][$id_dol_line]
     */
    public function getLinesToRemind($days)
    {

        $user_line = array();
        $date_limit_expire = new DateTime();
        $date_limit_expire->add(new DateInterval('P' . $days . 'D'));

        $sql .= BimpTools::getSqlSelect('a.rowid as id_dol_line, a.date_start as date_start, a.date_end as date_end,'
                        . 'b.id as id_bimp_line, c.rowid as id_c, c.fk_user_author as user_create');
        $sql .= BimpTools::getSqlFrom('commandedet', array(
                    'b' => array(
                        'table' => 'bimp_commande_line',
                        'alias' => 'b',
                        'on'    => 'a.rowid = b.id_line'
                    ),
                    'c' => array(
                        'table' => 'commande',
                        'alias' => 'c',
                        'on'    => 'a.fk_commande = c.rowid')
        ));
        $sql .= ' WHERE a.date_end != "" AND a.date_end < "' . $date_limit_expire->format('Y-m-d H:i:s') . '" AND c.rappel_service_expire > 0';
        $sql .= BimpTools::getSqlOrderBy("a.date_end", 'ASC');
        $rows = $this->bdb->executeS($sql);

        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $r->id_c);
                $id_commercial = $commande->getCommercialId();

                if ($id_commercial)
                    $id_user = $id_commercial;
                else
                    $id_user = $r->user_create;

                if (!isset($user_line[$id_user]))
                    $user_line[$id_user] = array();

                if (!isset($user_line[$id_user][$commande->id]))
                    $user_line[$id_user][$commande->id] = array();

                $user_line[$id_user][$commande->id][$r->id_dol_line] = array(
                    'id_bimp_line' => $r->id_bimp_line,
                    'date_start'   => $r->date_start,
                    'date_end'     => $r->date_end);
            }
        }

        return $user_line;
    }

    public function sendRappel($user_line)
    {

        $errors = array();
        // A terminer de débuguer
         
         
//        $warnings = array();
//        $now = new DateTime();
//        $tot_l = 0;
//
//        $id_user_def = (int) BimpCore::getConf('id_user_mail_comm_line_expire', null, 'bimpcommercial');
//
//        // User
//        foreach ($user_line as $id_user_in => $commandes) {
//
//            $u_init = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user_in);
//            $u_a = Bimp_User::getUsersAvaible(array($id_user_in, 'parent', $id_user_def), $errors, $warnings, 1, false, true);
//
//            $m = '';
//
//            // L'utilisateur disponible n'est ni le commercial, ni le créateur de la pièce
//            if ((int) $u_a->id != (int) $id_user_in) {
//
//
//                // Supérieur hiérarchique
//                if ((int) $u_init->getData('fk_user') == (int) $u_a->id) {
//                    $m .= "Vous recevez ce message car vous être le supérieur hiérarchique de ";
//                    $m .= $u_init->getData('firstname') . ' ' . $u_init->getData('lastname') . ' qui n\'est pas disponible<br/>';
//
//                    // Chargé des commandes
//                } elseif ((int) $u_a->id == (int) $id_user_def) {
//                    $m .= "Vous recevez ce message car vous êtes la personne en charge des";
//                    $m .= " relances de commandes pour les clients sans commerciaux disponible.<br/>";
//                    $m .= " (ici celles de " . $u_init->getData('firstname') . ' ' . $u_init->getData('lastname') . ')<br/>';
//                }
//            }
//
//            $l_user = 0;
//
//            $m .= 'Bonjour ' . $u_a->getData('firstname') . ',<br/><br/>';
//            $m .= 'Voici la liste de vos commandes contenant des lignes arrivant à expiration:<br/>';
//
//            // Commande
//            foreach ($commandes as $id_c => $c) {
//                $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $id_c);
//                $client = $commande->getChildObject('client');
//                
//                $m .= $commande->getLink() . ' ' . $commande->getData('label');
//                $m .= $client->getLabel() . ': ' . $client->getRef() . ' - ' . $client->getName() . ':<br/>';
//
//                $nb_l = count($c);
//
//                // Lignes
//                foreach ($c as $id_dol_line => $data) {
//                    $l = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $data['id_bimp_line']);
//
//                    $product = $l->getProduct();
//                    if (BimpObject::objectLoaded($product))
//                        $product_label = BimpTools::cleanString($product->getData('label'));
//                    else
//                        $product_label = "Produit non renseigné";
//
//                    $date_start = new DateTime(substr($data['date_start'], 0, 10));
//                    $date_end = new DateTime(substr($data['date_end'], 0, 10));
//
//                    $day_until_expire = $now->diff($date_end)->format('%r%a');
//
//                    $m .= '- Quantité: ' . $l->getFullQty() . ', libellé: ' . $product_label . ' ';
//                    $m .= $date_start->format('d/m/Y') . ' - ' . $date_end->format('d/m/Y');
//
//                    if ((int) $day_until_expire > 0)
//                        $m .= ' <strong>expire dans ' . $day_until_expire . ' jours</strong><br/>';
//                    else
//                        $m .= ' <strong style="color: #b50000">expiré depuis ' . str_replace('-', '', $day_until_expire) . ' jours</strong><br/>';
//                }
//
//                $m .= '<br/>';
//                $l_user += $nb_l;
//
//                $commande->updateField('rappel_service_expire', ($commande->getInitData('rappel_service_expire') - 1));
//            }
//
//            $subject = $l_user . " ligne" . (($l_user > 1) ? 's' : '') . " de commande arrivant à expiration";
//
//            $this->output .= 'Sujet:' . $subject . '<br/>' . $m;
//
//            mailSyn2($subject, $u_a->getData('email'), '', $m);
//            $tot_l += $l_user;
//        }
//
//        $this->output .= $tot_l . " Lignes de commandes arrivent a expirations (ou sont expirées).";
//        
//        if (!empty($errors)) {
//            $this->output .= BimpRender::renderAlerts($errors);
//        }
//        
//        if (!empty($warnings)) {
//            $this->output .= BimpRender::renderAlerts($warnings, 'warning');
//        }


        return !count($errors);
    }
}
