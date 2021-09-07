<?php
require_once(DOL_DOCUMENT_ROOT.'/bimpcommercial/objects/Bimp_Commande.class.php');

class BimpCommandeForDol extends Bimp_Commande{
    public function __construct($db) {
        
        require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
        
        return parent::__construct('bimpcommercial', 'Bimp_Commande');
    }
    
    public function remindEndLine($days = 60) {
        $user_line = $this->getLinesToRemind($days);
        $this->sendRappel($user_line);
        return true;
    }
    
    public function getLinesToRemind($days) {
        
        $user_line = array();
        $date = new DateTime();
        $date->add(new DateInterval('P' . $days . 'D'));
        
        $sql .= BimpTools::getSqlSelect('a.rowid as id_dol_line, a.date_end as date_end,'
                . 'b.id as id_bimp_line, c.rowid as id_c');
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
        $sql .= ' WHERE a.date_end != "" AND a.date_end < "' . $date->format('Y-m-d H:i:s') . '"';
        $sql .= BimpTools::getSqlOrderBy("a.date_end", 'ASC');
//        $sql .= BimpTools::getSqlLimit(125, 1); // TODO enlever ?
        $rows = $this->db->executeS($sql);
        
        if (!is_null($rows)) {
            foreach($rows as $r) {
                
                
                $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $r->id_c);
                $id_commercial = $commande->getCommercialId();
                
                if(!isset($user_line[$id_commercial]))
                    $user_line[$id_commercial] = array();
                
                if(!isset($user_line[$id_commercial][$commande->id]))
                    $user_line[$id_commercial][$commande->id] = array();
                
                $user_line[$id_commercial][$commande->id][$r->id_dol_line] = array(
                    'id_bimp_line' => $r->id_bimp_line,
                    'date_start'   => $r->date_start,
                    'date_end'     => $r->date_end);
            }
        }
        
        return $user_line;
        
    }
    
    public function sendRappel($user_line) {
        
        $now = new DateTime();
        $tot_l = 0;
        
        // User
        foreach($user_line as $id_user => $u) {
            
            $m = '';
            
            if(!$id_user) {
                $m .= "Vous recevez ce message car vous êtes la personne en charge des";
                $m .= " relances de commandes pour les clients sans commerciaux<br/>";
                $id_user = (int) BimpCore::getConf('id_user_mail_comm_line_expire');
            }
            
            $l_user = 0;
            
            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);
            
            if(!$user->getData('statut')) {
                $m .= "Vous recevez ce message car vous être le supérieur hiérarchique de ";
                $m .= $user->getData('firstname') . ' ' . $user->getData('lastname') . ' qui n\'est plus actif<br/>';
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $user->getData('fk_user'));
            }
            
            $m .= 'Bonjour ' . $user->getData('firstname') . ',<br/><br/>';
            $m .= 'Voici la liste de vos commandes contenant des lignes arrivant à expiration:<br/>';
            
            // Commande
            foreach($u as $id_c => $c) {
                
                $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $id_c);
                $client = $commande->getChildObject('client');;
                
                // ucfirst($commande->getLabel()) . ': ' .
                $m .=  $commande->getNomUrl(1) . ' ' . $commande->getData('label');
                $m .= $client->getLabel() . ': ' . $client->getRef() . ' - ' . $client->getName() . ':<br/>';
                
                $nb_l = count($c);
                
                // Lignes
                foreach ($c as $id_dol_line => $data) {
                    $l = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $data['id_bimp_line']);

                    $product = $l->getProduct();
                    if (BimpObject::objectLoaded($product))
                        $product_label = BimpTools::cleanString($product->getData('label'));
                    else
                        $product_label = "Produit non renseigné";
                                        
                    $date_start = new DateTime($data['date_start']);
                    $date_end = new DateTime($data['date_end']);
                    
                    $days  = $date_end->diff($now)->format('%a');
                        
                    $m .=  '- Quantité: ' . $l->getFullQty() . ', libellé: ' . $product_label . ' ' ;
                    $m .= $date_start->format('d/m/Y') . ' - ' . $date_end->format('d/m/Y');
                    
                    if($days < 0)
                        $m .= ' <strong>expire dans ' . $days . ' jours</strong><br/>';
                    else
                        $m .= ' <strong style="color: #D20000">expiré depuis ' . $days . ' jours</strong><br/>';
                }
                
                $m .= '<br/>';
                $l_user += $nb_l;
            }
            
            $subject = $l_user . " ligne" . (($l_user > 1) ? 's' : '') . " de commande arrivant à expiration";
            
            
//            mailSyn2($subject, $user->getData('email'), '', $m);
            $tot_l += $l_user;
        }
        
        $this->output = $tot_l . " Lignes de commandes arrivent a expirations (ou sont expiré).";
    }
    
}
