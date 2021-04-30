<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpsupport/objects/BS_Ticket.class.php';

class BIC_UserTickets extends BS_Ticket
{

    public $arrayTypeSerialImei = array(
        "serial" => "N° de série",
        "imei"   => "N° IMEI",
        "serv"   => "Service");

    public function getListFiltersInterface($filter_send = null)
    {
        global $userClient;


        if (BimpTools::getContext() == 'public') {
            $filter = Array(Array('name' => 'id_client', 'filter' => $userClient->getData('attached_societe')));
        }

        if ($filter_send == 'contrat') {
            $filter = BimpTools::merge_array($filter, Array(Array('name' => 'id_contrat', 'filter' => $_REQUEST['id'])));
//            $userContrat = BimpT

            if (!$userClient->it_is_admin()) {
                $userContrats = $userClient->getChildrenObjects('user_client_contrat');
                $forceFiltreUser = true;
                foreach ($userContrats as $userContrat) {
                    if ($userContrat->getData('id_contrat') == $_REQUEST['id'] && $userContrat->getData('read_ticket_in_contrat'))
                        $forceFiltreUser = false;
                }
                if ($forceFiltreUser)
                    $filter = BimpTools::merge_array($filter, Array(Array('name' => 'id_user_client', 'filter' => $userClient->id)));
            }
        }
        if ($filter_send == 'user') {
            $idUser = 0;
            if (BimpTools::getValue("fc") == 'pageUser' && BimpTools::getValue("id") > 0)
                $idUser = BimpTools::getValue("id");
            if ($idUser < 1)
                $idUser = $userClient->id;
            $filter = BimpTools::merge_array($filter, Array(Array('name' => 'id_user_client', 'filter' => $idUser)));
        }
        return $filter;
    }

    public function userClient($field)
    {
        global $userClient;
        if (isset($userClient)) {
            return $userClient->getData($field);
        }
    }

    public function currentContrat()
    {
        return $_REQUEST['id'];
    }

    public function create(&$warnings = Array(), $force_create = false)
    {
        global $userClient;

        // Vérification que le numéro de série est bien dans le contrat
//        $id_contrat = $_REQUEST['id'];
//        $in_contrat = (count($this->db->getRow('bcontract_serials', BimpTools::getValue('choix') . ' = "'.BimpTools::getValue('serial_imei').'"'))) ? true : false;
//        if($in_contrat){
        $errors = parent::create($warnings, $force_create);

        if (empty($errors)) {
            $this->updateField('impact_demande_client', BimpTools::getValue('impact'));
            $this->updateField('priorite_demande_client', BimpTools::getValue('priorite'));
            $this->updateField('cover', 1);
            $this->updateField('id_user_resp', 0);

            $label_serial_imei = $this->arrayTypeSerialImei[BimpTools::getValue('choix')];
            $add_sujet = "------------------------------<br />";

            $add_sujet .= "<b>" . $label_serial_imei . ":</b> " . BimpTools::getValue('serial_imei') . "<br />";

            if (BimpTools::getValue('adresse_envois')) {
                $add_sujet .= "<b>Adresse d'envoi:</b> " . BimpTools::getValue('adresse_envois') . "<br />";
            }

            if (BimpTools::getValue('contact_in_soc')) {
                $add_sujet .= "<b>Utilisateur:</b> " . BimpTools::getValue('contact_in_soc') . "<br />";
            }

            if (BimpTools::getValue('adress_bon_retour')) {
                $add_sujet .= "<b>Adresse email pour envoi du bon de retour:</b> " . BimpTools::getValue('adress_bon_retour') . "<br />";
            }

            $add_sujet .= "------------------------------<br /><br />";

            $add_sujet .= $this->getData('sujet');
            $this->updateField('sujet', $add_sujet);

            $liste_destinataires = Array($userClient->getData('email'));
            $liste_destinataires = BimpTools::merge_array($liste_destinataires, Array('hotline@bimp.fr'));
            $liste_destinataires = BimpTools::merge_array($liste_destinataires, $userClient->get_dest('admin'));
            $liste_destinataires = BimpTools::merge_array($liste_destinataires, $userClient->get_dest('commerciaux'));

            $prio = 'Non Urgent';
            $prio = ($this->getData('priorite') == 2) ? 'Urgent' : $prio;
            $prio = ($this->getData('priorite') == 3) ? 'Très Urgent' : $prio;
            $impact = 'Faible';
            $impact = ($this->getData('priorite') == 2) ? 'Moyen' : $impact;
            $impact = ($this->getData('priorite') == 3) ? 'Haut' : $impact;
            $tmpContrat = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('id_contrat'));
            $liste_destinataire_interne_contrat_spare = '';
            if ($tmpContrat->getData('objet_contrat') == 'CSP') {
                $liste_destinataire_interne_contrat_spare = 'j.garnier@bimp.fr, l.gay@bimp.fr, tt.cao@bimp.fr';
            }
            mailSyn2('BIMP-CLIENT : Création Ticket Support N°' . $this->getData('ticket_number'), implode(', ', $liste_destinataires), '', '<h3>Ticket support numéro : ' . $this->getData('ticket_number') . '</h3>'
                    . 'Sujet du ticket : ' . $this->getData('sujet') . '<br />'
                    . 'Demandeur : ' . $userClient->getData('email') . '<br />'
                    . 'Contact dans la société : ' . $this->getData('contact_in_soc') . '<br />'
                    . 'Contrat : ' . $tmpContrat->getData('ref') . '<br />'
                    . 'Priorité : ' . $prio . '<br />'
                    . 'Impact : ' . $impact . '<br />'
                    , array(), array(), array(), $liste_destinataire_interne_contrat_spare);
            $tmpContrat = null;
        }
//        } else {
//            return BimpRender::renderAlerts("Le numéro <b>".BimpTools::getValue('serial_imei')."</b> n'est pas r'attaché à ce contrat", 'danger', false);
//        }

        return $errors;
    }
}
