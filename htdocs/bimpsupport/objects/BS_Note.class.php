<?php

class BS_Note extends BimpObject
{

    public static $visibilities = array(
        1 => 'Membres Bimp et client',
        2 => 'Membres BIMP',
        3 => 'Auteur seulement'
    );

    // Droits users: 

    public function canView()
    {
        if ($this->isLoaded() && (int) $this->getData('visibility') === 3) {
            global $user;

            if ((int) $user->id !== (int) $this->getData('user_create')) {
                return 0;
            }
        }

        return 1;
    }

    public function canClientView()
    {
        global $userClient;

        if (BimpObject::objectLoaded($userClient)) {
            if ($this->isLoaded()) {
                if ($this->getData('visibility') > 1) {
                    return 0;
                }
            }

            return 1;
        }

        return 0;
    }

    public function canClientCreate()
    {
        global $userClient;

        if (BimpObject::objectLoaded($userClient)) {
            return 1;
        }

        return 0;
    }

    public function canEdit()
    {
        if ((int) $this->getData('id_user_client')) {
            return 0;
        }

        return 1;
    }

    public function canClientEdit()
    {
        global $userClient;

        if ($this->isLoaded()) {
            if (BimpObject::objectLoaded($userClient) && (int) $userClient->id == (int) $this->getData('id_user_client')) {
                // Code non fonctionnel: cette méthode doit pouvoir être appellée dans n'importe quel contexte (pas seulement lorsqu'on est sur la fiche ticket) => Donc $_REQUEST['id'] non valable. 
//                    $list_of_note_for_this_ticket = $this->getList(Array('id_ticket' => $_REQUEST['id']));
//                    $good_array = array('id' => 0, 'date' => '2000-01-01');
//                    foreach ($list_of_note_for_this_ticket as $note) {
//                        if (strtotime($note['date_create']) > strtotime($good_array['date'])) {
//                            $good_array = array('id' => $note['id'], 'date' => $note['date_create']);
//                        }
//                    }
//
//                    if ($this->id == $good_array['id']) {
//                        return 1;
//                    }
                return 1;
            }

            return 0;
        }

        return 1;
    }

    public function canDelete()
    {
        if ((int) $this->getData('id_user_client')) {
            return 0;
        }

        return 1;
    }

    public function canClientDelete()
    {
        global $userClient;

        if ($this->isLoaded()) {
            if (BimpObject::objectLoaded($userClient) && (int) $userClient->id === (int) $this->getData('id_user_client')) {
                return 1;
            }
        }

        return 0;
    }

    // Getters: 

    public function isCreatable($force_create = false, &$errors = array())
    {
        $parent = $this->getParentInstance();
        if (BimpObject::objectLoaded($parent) && $parent->getData('status') < 999) {
            return 1;
        }

        return 0;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if (BimpCore::isContextPublic()) {
            if ($this->isLoaded()) {
                if ($field == 'content') {
                    return ((int) $this->getData('id_user_client') ? 1 : 0);
                }

                return 0;
            }

            return 1;
        }

        return parent::isFieldEditable($field);
    }

    public function getInterventionsArray()
    {
        $array = array(
            0 => '-'
        );

        $id_parent = $this->getData('id_ticket');

        if (!is_null($id_parent) && $id_parent) {
            $rows = $this->db->getValues('bs_inter', 'id', '`id_ticket` = ' . (int) $id_parent);
            if (!is_null($rows)) {
                foreach ($rows as $id_inter) {
                    $array[(int) $id_inter] = 'Intervention n°' . $id_inter;
                }
            }
        }

        return $array;
    }

    public function getListFilterNotesInterface()
    {
        $parent = $this->getParentInstance();
        return Array(
            Array(
                'name'   => 'id_ticket',
                'filter' => $parent->getData('id')
            ),
            Array(
                'name'   => 'visibility',
                'filter' => 1 // A changer après avoir fait le créate
            )
        );
    }

    // Affichages: 

    public function displayAuthor()
    {
        if ((int) $this->getData('id_user_client')) {
            return $this->displayData('id_user_client');
        }

        return $this->displayData('user_create');
    }

    // Traitements: 

    public function sendNotificationEmails()
    {
        global $userClient;

        $ticket = $this->getParentInstance();

        $client = null;
        if (BimpObject::objectLoaded($userClient)) {
            $client = $userClient;
        } elseif ((int) $ticket->getData('id_user_client')) {
            $client = $ticket->getChildObject('user_client');
        }

        $dests = array();
        if (BimpCore::isContextPublic()) {
            if (BimpObject::objectLoaded($client)) {
                $dests = BimpTools::merge_array($dests, $client->get_dest('commerciaux'));
            }

            $resp = $ticket->getChildObject('user_resp');
            if (is_object($resp) && $resp->isLoaded()) {
                $dests[] = $resp->getData('email');
            }

            if (count($dests)) {
                $link = '';
                if (BimpObject::objectLoaded($ticket)) {
                    $link = $ticket->getLink(array(), 'private');
                }

                $msg = 'Un message a été ajouté sur votre ticket hotline ';
                if ($link) {
                    $msg .= $link;
                } else {
                    $msg .= '<b>' . $ticket->getData('ticket_number') . '</b>';
                }

                $msg .= '<br/><br/>';
                $msg .= 'Message : ' . $this->getData('content');

                mailSyn2('BIMP - Message client sur ticket hotline #' . $ticket->id, implode(', ', $dests), '', $msg);
            }
        } elseif (BimpObject::objectLoaded($client) && $this->getData('visibility') == 1) {
            $to = $client->getData('email');
            $cc = implode(',', $client->get_dest('admin'));

            if (!$to && $cc) {
                $to = $cc;
                $cc = '';
            }
            if ($to) {
                $subject = 'Nouveau message sur votre ticket support' . (BimpObject::objectLoaded($ticket) ? ' ' . $ticket->getData('ticket_number') : '');
                $msg = 'Bonjour,<br/><br/>';
                $msg = 'Nouveau message sur votre ticket support n° ' . $ticket->getData('ticket_number') . '<br/><br/>';

                $msg .= '<b>Message : </b><br/>' . $this->getData('content');

                if (BimpObject::objectLoaded($ticket)) {
                    $url = $ticket->getPublicUrl(false);
                }
                if ($url) {
                    $msg .= '<br/>------------------<br/><a href="' . $url . '">Cliquez ici</a> pour accéder au détail de votre ticket support depuis votre espace client';
                }

                $bimpMail = new BimpMail($ticket, $subject, $to, '', $msg, '', $cc);
                $bimpMail->send();
            }
        }
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        global $userClient;

        if (BimpCore::isContextPublic()) {
            if (!BimpObject::objectLoaded($userClient)) {
                $errors[] = 'Compte utilisateur client absent';
            } else {
                $this->set('id_user_client', $userClient->id);
                $this->set('visibility ', 1);
            }
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $this->sendNotificationEmails();
        }

        return $errors;
    }
}
