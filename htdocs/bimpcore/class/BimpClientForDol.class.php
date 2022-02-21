<?php

require_once(DOL_DOCUMENT_ROOT.'/bimpcore/objects/Bimp_Client.class.php');

class BimpClientForDol extends Bimp_Client{
    
    public function __construct($db) {
        
        require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
        
        return parent::__construct('bimpcore', 'Bimp_Client');
    }
    
    public function rappelValidite($days = 30) {
        $this->error = '';        
        $clients = $this->getClientsFinValidite($days);
        return $this->sendRappel($clients);
    }
    
    private function getClientsFinValidite($days) {
        
        $days += 365; // Valable 1 an
        $date_limit_expire = new DateTime();
        $date_limit_expire->modify('-' . $days . ' day');
                
        $filters = array(
            'date_atradius' => array(
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
    
    private function sendRappel($clients) {
        $nb_rappels = 0;
        
        if(!empty($clients)) {
            
            if(!BimpObject::loadClass('bimpcore', 'BimpNote'))
                $this->addError("Erreur lors du chargement de la classe BimpNote");
        
            foreach ($clients as $c) {
                
                $date_validite_atra = new DateTime($c->getData('date_atradius'));
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
    
    private function addError($error_msg) {
        $this->error .= '<br/><strong style="color: red">' . $error_msg . '</strong>';
    }
    
}