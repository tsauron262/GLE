<?php

class Bimp_User extends BimpObject
{

    public static $status_list = array(
        0 => array('label' => 'Désactivé', 'icon' => 'fas_times', 'classes' => array('danger')),
        1 => array('label' => 'Actif', 'icon' => 'fas_check', 'classes' => array('success'))
    );
    public static $genders = array(
        ''      => '',
        'man'   => 'Homme',
        'woman' => 'Femme'
    );

    // Gestion des droits: 

    public function canView()
    {
        global $user;

        if ((int) $user->id === (int) $this->id) {
            return 1;
        }

        if ($user->admin || $user->rights->user->user->lire) {
            return 1;
        }

        return 0;
    }

    public function canCreate()
    {
        global $user;

        if ($user->admin || $user->rights->user->user->creer) {
            return 1;
        }

        return 0;
    }

    public function canEdit()
    {
        global $user;

        if ($this->id == $user->id) {
            return 1;
        }

        return $this->canCreate();
    }

    public function canDelete()
    {
        return $this->canCreate();
    }

    // Getters: 

    public function getName($withGeneric = true)
    {
        return $this->getInstanceName();
    }

    public function getInstanceName()
    {
        if ($this->isLoaded()) {
            return dolGetFirstLastname($this->getData('firstname'), $this->getData('lastname'));
        }

        return ' ';
    }

    public function getPageTitle()
    {
        return $this->getInstanceName();
    }

    // Getters params: 

    public function getEditFormName()
    {
        global $user;

        if ($user->admin || $user->rights->user->user->creer) {
            return 'default';
        }

        if ((int) $user->id === (int) $this->id) {
            return 'light';
        }

        return null;
    }

    public function getDefaultListHeaderButton()
    {
        $buttons = array();

        $buttons[] = array(
            'classes'     => array('btn', 'btn-default'),
            'label'       => 'Générer export des congés',
            'icon_before' => 'fas_file-excel',
            'attr'        => array(
                'type'    => 'button',
                'onclick' => $this->getJsActionOnclick('exportConges', array(
                    'types_conges' => json_encode(array(0, 1, 2))
                        ), array(
                    'form_name' => 'export_conges'
                ))
            )
        );

        return $buttons;
    }

    // Affichage: 

    public function displayCountry()
    {
        $id = $this->getData('fk_country');
        if (!is_null($id) && $id) {
            return $this->db->getValue('c_country', 'label', '`rowid` = ' . (int) $id);
        }
        return '';
    }

    public function actionExportConges($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        $date_from = (isset($data['date_from']) ? $data['date_from'] : '');
        $date_to = (isset($data['date_to']) ? $data['date_to'] : '');

        if (!$date_from) {
            $errors[] = 'Veuillez indiquer une date de début';
        }

        if (!$date_to) {
            $errors[] = 'Veuillez indiquer une date de fin';
        }

        if ($date_to < $date_from) {
            $errors[] = 'La date de fin est inférieure à la date de début';
        }

        $types_conges = (isset($data['types_conges']) ? $data['types_conges'] : array());

        if (empty($types_conges)) {
            $errors[] = 'Veuillez sélectionner au moins un type de congé';
        }

        if (!count($errors)) {
            $where = 'date_debut <= \'' . $date_to . '\'';
            $where .= ' AND date_fin >= \'' . $date_from . '\'';
            $where .= ' AND statut = 6';
            $where .= ' AND type_conges IN (' . implode(',', $types_conges) . ')';

            $rows = $this->db->getRows('holiday', $where, null, 'array', null, 'rowid', 'desc');

            if (empty($rows)) {
                $warnings[] = 'Aucun congé trouvé';
            } else {
                require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
                
                $data = array();

                $userCP = new User($this->db->db);
                $typesCongesLabels = array(
                    0 => 'Congés payés',
                    1 => 'Absence exceptionnelle',
                    2 => 'RTT'
                );

                foreach ($rows as $r) {
                    if ($r['date_debut'] < $date_from) {
                        $r['date_debut'] = $date_from;
                    }
                    if ($r['date_fin'] > $date_to) {
                        $r['date_fin'] = $date_to;
                    }

                    $date_debut_gmt = $this->db->db->jdate($r['date_debut'], 1);
                    $date_fin_gmt = $this->db->db->jdate($r['date_fin'], 1);
                    $nbJours = num_open_dayUser((int) $r['fk_user'], $date_debut_gmt, $date_fin_gmt, 0, 1, (int) $r['halfday']);
                    $userCP->fetch((int) $r['fk_user']);

                    if (!BimpObject::objectLoaded($userCP)) {
                        $warnings[] = 'L\'utilisateur #' . $r['fk_user'] . ' n\'existe plus - non inclus dans le fichier';
                        continue;
                    }

                    $dt_from = new DateTime($r['date_debut']);
                    $dt_to = new DateTime($r['date_fin']);

                    $data[] = array(
                        $userCP->lastname,
                        $userCP->firstname,
                        $this->db->getValue('user', 'matricule', 'rowid = ' . (int) $userCP->id),
                        $userCP->town,
                        $typesCongesLabels[(int) $r['type_conges']],
                        str_replace(';', ',', str_replace("\n", ' ', $r['description'])),
                        $dt_from->format('d / m / Y'),
                        $dt_to->format('d / m / Y'),
                        $nbJours
                    );
                }

                $str = 'NOM;PRENOM;MATRICULE;VILLE;TYPE CONGES;INFOS;DATE DEBUT;DATE FIN;NOMBRE JOURS' . "\n";

                foreach ($data as $line) {
                    $str .= implode(';', $line) . "\n";
                }

                if (file_put_contents(DOL_DATA_ROOT . '/bimpcore/export_conges.csv', $str)) {
                    $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . urlencode('export_conges.csv');
                    $success_callback = 'window.open(\'' . $url . '\');';
                } else {
                    $errors[] = 'Echec de la création du fichier';
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    // Overrides

    public function update(&$warnings = array(), $force_update = false)
    {
        if ($this->isLoaded()) {
            $this->dol_object->oldcopy = clone $this->dol_object;
        }

        return parent::update($warnings, $force_update);
    }
}
