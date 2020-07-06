<?php

class public_indexController extends Bimp_user_client_controller {

    public function renderHtml() {
        global $langs, $userClient;
        if (!is_object($userClient)) {
            return BimpRender::renderAlerts('Problème de contexte', 'danger', false);
        }
        $valide_contrat = $userClient->getContratVisible(true);
        // $valide_contrat = Array(); // Simuler que la société n'est pas sous contrat
        $all_contrat = $userClient->getContratVisible();
        $html = "";
        $soc = $userClient->getDolObjectInstance($userClient->getData('attached_societe'), 'societe');
        $html .= '<div class="row"><div class="col-md-12"><b><div class="header"><h2 class="title" data-color="bimp">' . $langs->trans('bonjour') . '</h2></b></div>';
        $html .= '<div class="content" ><div class="col-md-12">';
        $html .= 'La societé <b><i>' . $soc->nom . '</i></b> est actuellement ';
        $html .= ($valide_contrat) ? '<b style="color:green">sous contrat <i class="fa fa-check" ></i></b>' : '<b style="color:red">hors contrat <i class="fa fa-times" ></i></b>';
        $html .= '</div></div>';

        
        
        $html .= '<br /><br /><h3 style="color:#EF7D00" >Mes contrats en cours <i class="fa fa-arrow-down" ></i> </h3>';
        $html .= '<br /><button class="btn btn-default" id="fadeInAddForm">Créer un ticket avec un numéro de série</button><br />';
        if ($valide_contrat) {
            
            $html .= '<div id="addContratIn">';
                $userTickets = BimpObject::getInstance('bimpinterfaceclient', 'BIC_UserTickets');
                
                $html .= "<p class='alert alert-danger' id='alert_error' ></p>";
                
                $html .= '<p style="display:inline-block"><i class="fa fa-arrow-right" ></i> Créer un ticket par N° de série</p>';
                $html .= '<p><input id="demandeur" content_id="'.$userClient->id.'" style="width:600px; display:none" type="text" value="'.$userClient->getData('email').'" disabled></p>';
                $html .= '<p><input class="want_input" id="theSerial" placeholder="Votre numéro de série (Obligatoire)" style="width:600px" type="text" style="margin-right: 10%"></p>';
                
                $html .= "<textarea class='want_input' id='desc' style='resize:none' placeholder='Description de la demande (Obligatoire)' cols='100' rows='5'></textarea>";
                
                $html .= '<p><input id="utilisateur"style="width:600px" type="text" placeholder="Utilisateur"></p>';
                $html .= '<p><input id="adresse_envois"style="width:600px" type="text" placeholder="Adresse d\'envoi"></p>';
                $html .= '<p><input id="email_retour"style="width:600px" type="text" placeholder="Adresse e-mail bon de retour"></p>';
               
                $html .= '<button id="createTicketFromAll" class="btn btn-info" socid="'.$userClient->getData('attached_societe').'" >Créer le ticket</button>';
                
                
            
            $html .= '</div>';
            
            $html .= $this->display_list_card($valide_contrat, true);
        }
        if ($all_contrat) {
            foreach ($all_contrat as $id_contrat => $ref) {
                if (!array_key_exists($id_contrat, $valide_contrat)) {
                    $contracts[$id_contrat] = $ref;
                }
            }
        }

        if ($contracts) {
            $html .= '</div>';
            $html .= '</div></div>';
            $html .= '<br /><br /><br />';
            $html .= '<br /><br /><h3 style="color:#EF7D00" >Mes contrats en échus <i class="fa fa-arrow-down" ></i> </h3>';
            $html .= '<div class="row">';
            $html .= '<div class="content" >';
            $html .= $this->display_list_card($contracts);
            $html .= '</div></div>';
        }

        return $html;
    }

    public function display_list_card($objects) {
        $return = "";
        foreach ($objects as $id_contrat => $object) { // Display client card dans contrac
            $return .= $object->display_card();
        }
        return $return;
    }

}
