<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpcore/objects/Bimp_Client.class.php');
require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';

class BimpClientForDol extends Bimp_Client
{

    public function __construct($db)
    {
        require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
        return parent::__construct('bimpcore', 'Bimp_Client');
    }

    public function updateICBA()
    {
        $this->error = '';
        $clients = $this->getClientsFinValiditeICBA(0);

        $nb_rappels = 0;
        $msg .= "Encours ICBA passé à 0 € (expiration après 1 an)";

        if (!empty($clients)) {
            BimpObject::loadClass('bimpcore', 'BimpNote');
            foreach ($clients as $c) {

                // Commercial
                $commercial = $c->getCommercial();
                $this->addError(implode('', $c->addNote($msg,
                                                        BimpNote::BN_MEMBERS, 0, 1, '', BimpNote::BN_AUTHOR_USER,
                                                        BimpNote::BN_DEST_USER, 0, (int) $commercial->getData('id'))));

//                // Groupe Atradius
                $this->addError(implode('', $c->addNote($msg,
                                                        BimpNote::BN_MEMBERS, 0, 1, '', BimpNote::BN_AUTHOR_USER,
                                                        BimpNote::BN_DEST_GROUP, BimpCore::getUserGroupId('atradius'))));

                $c->set('outstanding_limit_icba', 0);
                $c->update();

                $this->output .= $c->getNomUrl() . ' ' . $msg . '<br/>';
                $nb_rappels++;
            }
        }

        $this->output .= "<br/><br/>Nombre d'encours ICBA annulé: $nb_rappels";
        return 0;
    }

    private function getClientsFinValiditeICBA($days_before = 0)
    {
        $jour_avant_rappel = 365 + $days_before;
        $date_depot = new DateTime();
        $date_depot->modify('-' . $jour_avant_rappel . ' day');
        $this->output .= "Considère les clients qui expire le: " . $date_depot->format("d/m/Y") . '<br/><br/>';

        $filters = array(
            'date_depot_icba'        => array(
                'and' => array(
                    array(
                        'operator' => '<',
                        'value'    => $date_depot->format('Y-m-d')
                    ),
                    'IS_NOT_NULL'
                )
            ),
            'outstanding_limit_icba' => array(
                'and' => array(
                    array(
                        'operator' => '>',
                        'value'    => 0
                    ),
                )
            ),
        );

        $clients = BimpCache::getBimpObjectObjects('bimpcore', 'Bimp_Client', $filters);

        return $clients;
    }

    public function rappelICBA($days)
    {
        mailSyn2('EXEC CRON rappelICBA', 'f.martinez@bimp.fr', '', 'Heure: ' . date('d / m / Y H:i:s') . '<br/>SERVER : ' . print_r($_SERVER, 1));

        $this->error = '';
        $clients = $this->getClientsFinValiditeICBA($days);

        $nb_rappels = 0;

        if (!empty($clients)) {
            $bdb = BimpCache::getBdb();
            BimpObject::loadClass('bimpcore', 'BimpNote');

            foreach ($clients as $c) {
                $where = 'obj_module = \'bimpcore\' AND obj_name = \'Bimp_Client\' AND id_obj = ' . $c->id;
                $where .= ' AND content LIKE \'%' . $bdb->db->escape('L\'encours ICBA pour ce client n\'est valable que jusqu\'au') . '%\'';
                $bdb->delete('bimpcore_note', $where);

                $date_validite = new DateTime($c->getData('date_depot_icba'));
                $date_validite->add(new DateInterval('P1Y'));
                $msg = "L'encours ICBA pour ce client n'est valable que jusqu'au " . $date_validite->format("d/m/Y");

//                // Commercial
                $commercial = $c->getCommercial();
                $this->addError(implode('', $c->addNote($msg,
                                                        BimpNote::BN_MEMBERS, 0, 1, '', BimpNote::BN_AUTHOR_USER,
                                                        BimpNote::BN_DEST_USER, 0, (int) $commercial->getData('id'))));

//                // Groupe Atradius
                $this->addError(implode('', $c->addNote($msg,
                                                        BimpNote::BN_MEMBERS, 0, 1, '', BimpNote::BN_AUTHOR_USER,
                                                        BimpNote::BN_DEST_GROUP, BimpCore::getUserGroupId('atradius'))));

                $this->output .= $c->getNomUrl() . ' ' . $msg . '<br/>';
                $nb_rappels++;
            }
        }

        $this->output .= "<br/><br/>Nombre de rappels d'expiration ICBA envoyé: $nb_rappels/" . count($clients);
        return 0;
    }

    // Envoie des notes pour indiquer les client dont les couvertures Atradius
    // arrivent à expiration
    public function rappelValiditeAtradius($days = 30, $interval = 7)
    {
        $this->error = '';
        $clients = $this->getClientsFinValiditeAtradius($days, $interval);
        return $this->sendRappelAtradius($clients);
    }

    private function getClientsFinValiditeAtradius($days = 30, $interval = 7)
    {

        $date_limit_expire = new DateTime();
        $date_limit_expire->modify('-' . $days . ' day');

        $date_rappel = new DateTime();
        $date_rappel->modify('-' . $interval . ' day');

        $filters = array(
            'date_atradius'        => array(
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

    private function sendRappelAtradius($clients)
    {
        $nb_rappels = 0;

        if (!empty($clients)) {
            $bdb = BimpCache::getBdb();
            BimpObject::loadClass('bimpcore', 'BimpNote');

            foreach ($clients as $c) {
                $where = 'obj_module = \'bimpcore\' AND obj_name = \'Bimp_Client\' AND id_obj = ' . $c->id;
                $where .= ' AND content LIKE \'%' . $bdb->db->escape('La limite de crédit autorisée par Atradius arrive à échéance le') . '%\'';
                $bdb->delete('bimpcore_note', $where);

                $commercial = $c->getCommercial();

                $msg = "La limite de crédit autorisée par Atradius arrive à échéance le ";
                $msg .= $c->displayData('date_atradius') . '<br/>';
                $msg .= "Il convient que vous demandiez un renouvellement de cet ";
                $msg .= "encours si vous souhaitez le maintenir";

                // Note
                $this->addError(implode('', $c->addNote($msg,
                                                        BimpNote::BN_MEMBERS, 0, 1, '', BimpNote::BN_AUTHOR_USER,
                                                        BimpNote::BN_DEST_USER, 0, (int) $commercial->id)));

                $c->updateField('date_rappel_atradius', date('Y-m-d h:i:s'));
                $this->output .= $msg . '<br/>';
                $nb_rappels++;
            }
        }

        $this->output .= "<br/><br/>Nombre de rappels envoyé: $nb_rappels";
        return 0;
    }

    private function addError($error_msg)
    {
        if ($error_msg != '')
            $this->output .= '<br/><strong style="color: red">' . $error_msg . '</strong>';
    }

    // Vérifie si les demandes en cours (vérification manuelle) ont été traité
    public function updateAtradius()
    {
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
    public function syncroAllAtradius()
    {
        $this->error = '';
        $success = '';
        BimpObject::loadClass('bimpcore', 'Bimp_Client');
        $errors = $warnings = array();

        $from = new DateTime();
//        $from->sub(new DateInterval('PT1H')); TODO
        $from->sub(new DateInterval('P5D'));

        $nb_update = Bimp_Client::updateAllAtradius($from->format('Y-m-d\TH:i:s'), $errors, $warnings, $success);
        $this->output .= "Nombre de clients mis à jour : " . $nb_update . "<br/><br/>" . $success;
        $this->addError(implode(',', $errors));
        return 0;
    }
}
