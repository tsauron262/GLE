<?php

require_once(DOL_DOCUMENT_ROOT.'/bimpcore/objects/Bimp_Client.class.php');

class BimpClientForDol extends Bimp_Client{
    
    public function __construct($db) {
        
        require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
        
        return parent::__construct('bimpcore', 'Bimp_Client');
    }
    
    // Envoie des notes pour indiquer les client dont les couvertures ICBA
    // arrivent à expiration
    public function rappelValiditeICBA($days = 30) {
        $this->error = '';        
        $clients = $this->getClientsFinValiditeICBA($days);
        return $this->sendRappelICBA($clients);
    }
    
    private function getClientsFinValiditeICBA($days) {
        
        $days += 365; // Valable 1 an
        $date_limit_expire = new DateTime();
        $date_limit_expire->modify('-' . $days . ' day');
                
        $filters = array(
            'date_depot_icba' => array(
                'and' => array(
                    array(
                        'operator' => '<',
                        'value'    => $date_limit_expire->format('Y-m-d H:i:s')
                    ),
                    'IS_NOT_NULL'
                )
            )
        );
        
        $clients = BimpCache::getBimpObjectObjects('bimpcore', 'Bimp_Client', $filters);
        
        return $clients;
    }
    
    private function sendRappelICBA($clients) {
        $nb_rappels = 0;
        
        if(!empty($clients)) {
            
            if(!BimpObject::loadClass('bimpcore', 'BimpNote'))
                $this->addError("Erreur lors du chargement de la classe BimpNote");
        
            foreach ($clients as $c) {
                
                $date_validite_atra = new DateTime($c->getData('date_depot_icba'));
                $date_validite_atra->add(new DateInterval('P1Y'));
                $msg = "L'encours ICBA pour le client " . $c->getData('code_client') . ' ' . $c->getData('nom');
                $msg .= " n'est valable que jusqu'au " . $date_validite_atra->format("d/m/Y");
                $msg .= "<br/>Il convient de le renouveler avant cette date";
                

                $this->addError(implode('', $c->addNote($msg,
                        BimpNote::BIMP_NOTE_MEMBERS, 0, 1, '',BimpNote::BN_AUTHOR_USER,
                        BimpNote::BN_DEST_GROUP, BimpNote::BN_GROUPID_ATRADIUS)));
                 
                $this->output .= $msg . '<br/>';
                $nb_rappels++;
                }
        }
        
        $this->output .= "<br/><br/>Nombre de rappels envoyé: $nb_rappels";
        return 1;
    }
    
    
    
    // Envoie des notes pour indiquer les client dont les couvertures Atradius
    // arrivent à expiration
    public function rappelValiditeAtradius($days = 30, $interval = 7) {
        $this->error = '';        
        $clients = $this->getClientsFinValiditeAtradius($days, $interval);
        return $this->sendRappelAtradius($clients);
    }
    
    private function getClientsFinValiditeAtradius($days = 30, $interval = 7) {
        
        $date_limit_expire = new DateTime();
        $date_limit_expire->modify('-' . $days . ' day');
        
        $date_rappel = new DateTime();
        $date_rappel->modify('-' . $interval . ' day');
                
        $filters = array(
            'date_atradius' => array(
                'and' => array(
                    array(
                        'operator' => '<',
                        'value'    => $date_limit_expire->format('Y-m-d H:i:s')
                    ),
                    'IS_NOT_NULL'
                )
            ),
            'date_rappel_atradius' => array(
                'or_field' => array(
                    array(
                        'operator' => '<',
                        'value'    => $date_rappel->format('Y-m-d H:i:s')
                    ),
                    'IS_NULL'
                )
            )
        );
        
        $clients = BimpCache::getBimpObjectObjects('bimpcore', 'Bimp_Client', $filters);
        
        return $clients;
    }
    
    private function sendRappelAtradius($clients) {
        $nb_rappels = 0;
        
        if(!empty($clients)) {
            
            if(!BimpObject::loadClass('bimpcore', 'BimpNote'))
                $this->addError("Erreur lors du chargement de la classe BimpNote");
        
            foreach ($clients as $c) {
                                
                $commercial = $c->getCommercial();
                
                $msg = "La limite de crédit autorisée par Atradius arrive à échéance le ";
                $msg .= $c->displayData('date_atradius') . '<br/>';
                $msg .= "Il convient que vous demandiez un renouvellement de cet ";
                $msg .= "encours si vous souhaitez le maintenir";

                // Note
                $this->addError(implode('', $c->addNote($msg,
                        BimpNote::BIMP_NOTE_MEMBERS, 0, 1, '',BimpNote::BN_AUTHOR_USER,
                        BimpNote::BN_DEST_USER, (int) $commercial->id)));
                
                $c->updateField('date_rappel_atradius', date('Y-m-d h:i:s'));
                $this->output .= $msg . '<br/>';
                $nb_rappels++;
            }
        }
        
        $this->output .= "<br/><br/>Nombre de rappels envoyé: $nb_rappels";
        return 1;
    }
    
    private function addError($error_msg) {
        $this->output .= '<br/><strong style="color: red">' . $error_msg . '</strong>';
    }
    

    // Vérifie si les demandes en cours (vérification manuelle) ont été traité
    public function updateAtradius() {
        $this->error = '';
        $success = '';
        BimpObject::loadClass('bimpcore', 'Bimp_Client');
        $errors = $warnings = array();
        $nb_update = Bimp_Client::updateAtradiusStatus($errors, $warnings, $success);
        $this->output .= "Nombre de clients mis à jour : " . $nb_update . "<br/><br/>" . $success;
        $this->addError(implode(',', $errors));
        return $nb_update;
    }    

    // MAJ de tous les profil Atradius de nos clients
    public function syncroAllAtradius() {
        $this->error = '';
        $success = '';
        BimpObject::loadClass('bimpcore', 'Bimp_Client');
        $errors = $warnings = array();
        
        $sql .=" SELECT id_object FROM llx_bimpcore_history WHERE field IN('outstanding_limit_atradius', 'outstanding_limit_credit_check') AND date LIKE '2022-06-29%'";
        $res = $this->db->executeS($sql, 'array');
        $arr = array();
        foreach($res as $r) {
            $arr[] = $r['id_object'];
        }
        
        $filters = array(
            'rowid' => $arr
        );
        
        
        
        $clients = BimpCache::getBimpObjectObjects('bimpcore', 'Bimp_Client', $filters);
        foreach($clients as $c) {
            $s = $c->displayData('date_atradius');
            $c->syncroAtradius();
            $s .= ' => ' . $c->displayData('date_atradius');
            $this->output .= $s . ' ' . $c->getNomUrl() . '<br/>';
        }
        
        return sizeof($clients);
        
        $from = new DateTime();
//        $from->sub(new DateInterval('PT1H')); TODO
        $from->sub(new DateInterval('P2D'));
                
        $nb_update = Bimp_Client::updateAllAtradius($from->format('Y-m-d\TH:i:s'), $errors, $warnings, $success);
        $this->output .= "Nombre de clients mis à jour : " . $nb_update . "<br/><br/>" . $success;
        $this->addError(implode(',', $errors));
        return $nb_update;
    }
}