<?php

if(isset($_POST['action'])) {
    define("NO_REDIRECT_LOGIN", 1);
    $data = (object) $_POST;
    require_once '../../bimpcore/main.php';
    require_once DOL_DOCUMENT_ROOT . '/bimpinterfaceclient/objects/BIC_UserTickets.class.php';
    global $userClient, $db;
    $bimp = new BimpDb($db);
    switch($data->action) {
        case 'createTicketFromAll':
            $id_contrat = getContratFromSerial($data->serial, $data->socid);
            if($id_contrat > 0) {
                $dataCreate = [
                    'contrat' => $id_contrat,
                    'client' => $data->socid,
                    'serial' => $data->serial,
                    'userClient' => $data->userClient,
                    'utilisateur' => $data->utilisateur,
                    'adresse_envois' => $data->adresse_envois,
                    'email_retour' => $data->email_retour,
                    'description' => $data->description
                ];
                echo createTicket($dataCreate);
            } else {
                echo 0;
            }
            break;
        default:
            break;
    }
} else {
    define("NO_REDIRECT_LOGIN", 0);
}

function getContratFromSerial($serial, $socid) {
    $have_serial = false;
    $have_serial_contrat = 0;
    $contrats = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
    $list = $contrats->getList(['fk_soc' => $socid, 'statut' => 11]);
    foreach($list as $num => $row) {
        $lignes = BimpObject::getInstance('bimpcontract', 'BContract_contratLine');
        $listLine = $lignes->getList(['fk_contrat' => $row['rowid']]);
        foreach($listLine as $numero => $r) {
            $serials = json_decode($r['serials']);
            if(in_array($serial, $serials)) {
                $have_serial = true;
                $have_serial_contrat = $row['rowid'];
            }
            if($have_serial)
                break;;
        }
        if($have_serial)
            break;
    }
    return $have_serial_contrat;
}
// cdscdscdscds
function createTicket($data) {
    global $userClient;
    $data = (object) $data;
    
    $sujet = '------------------------------<br />';
    $sujet.= '<b>N° de série: </b>' . $data->serial . '<br />';
    
    if(!empty($data->adresse_envois)) {
        $sujet.= "<b>Adresse d'envois: </b> ".$data->adresse_envois." <br />";
    }
    if(!empty($data->utilisateur)) {
        $sujet.= "<b>Utilisateur: </b> ".$data->utilisateur." <br />";
    }
    if(!empty($data->email_retour)) {
        $sujet.= "<b>Adresse email pour envoi du bon de retour:: </b> ".$data->email_retour." <br />";
    }
    
    $sujet.= '------------------------------<br />';
    
    if(!empty($data->description)) {
        $sujet .= '<br /><br />' . $data->description;
    }
    
    
    $new = BimpObject::getInstance('bimpsupport', 'BS_Ticket');
    $new->set('id_contrat', $data->contrat);
    $new->set('id_client', $data->client);
    $new->set('sujet', $sujet);
    $new->set('status', 20);
    $new->set('id_user_resp', 0);
    $new->set('id_user_client', $data->userClient);
    //return print_r($new, 1);
    
    if($new->create($warnings, false) > 0) {
        
        $tmpContrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat', $data->contrat);
        $tmpUserClient = BimpObject::getInstance('bimpinterfaceclient', 'BIC_UserClient', $data->userClient);
        
        $liste_destinataires = Array($tmpUserClient->getData('email'));
        $liste_destinataires = BimpTools::merge_array($liste_destinataires, Array('hotline@bimp.fr'));
        $liste_destinataires = BimpTools::merge_array($liste_destinataires, $tmpUserClient->get_dest('admin'));
        $liste_destinataires = BimpTools::merge_array($liste_destinataires, $tmpUserClient->get_dest('commerciaux'));        
        
        $liste_destinataire_interne_contrat_spare = '';
        if($tmpContrat->getData('objet_contrat') == 'CSP') {
            $liste_destinataire_interne_contrat_spare = 'j.garnier@bimp.fr, l.gay@bimp.fr, tt.cao@bimp.fr';
        }

        mailSyn2('BIMP-CLIENT : Création Ticket Support N°' . $new->getData('ticket_number'), implode(', ', $liste_destinataires), '',
                '<h3>Ticket support numéro : '.$new->getData('ticket_number').'</h3>'
                . 'Sujet du ticket : ' . $sujet . '<br />'
                . 'Demandeur : ' . $tmpUserClient->getData('email') . '<br />'
                . 'Contrat : ' . $tmpContrat->getData('ref') . '<br />'
                , array(), array(), array(), $liste_destinataire_interne_contrat_spare);
        $tmpContrat = null;
        $tmpUserClient = null;
        
        return $new->id;
    }

    
}