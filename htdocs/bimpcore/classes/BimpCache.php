<?php

class BimpCache
{

    public static $bdb = null;
    protected static $cache = array();

    public static function getBdb()
    {
        if (is_null(self::$bdb)) {
            global $db;
            self::$bdb = new BimpDb($db);
        }

        return self::$bdb;
    }

    public static function getCacheArray($cache_key, $include_empty = false, $empty_value = 0, $empty_label = '')
    {
        if ($include_empty) {
            $return = array(
                $empty_value => $empty_label
            );

            if (isset(self::$cache[$cache_key])) {
                foreach (self::$cache[$cache_key] as $value => $label) {
                    $return[$value] = $label;
                }
            }
            return $return;
        }

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        return array();
    }

    // Objets: 

    public static function getObjectFilesArray($object, $with_deleted = false)
    {
        if (BimpObject::objectLoaded($object)) {
            if (is_a($object, 'BimpObject')) {
                $cache_key = $object->module . '_' . $object->object_name . '_' . $object->id . '_files';
                if ($with_deleted) {
                    $cache_key .= '_with_deleted';
                }
                if (!isset(self::$cache[$cache_key])) {
                    self::$cache[$cache_key] = array();

                    $where = '`parent_module` = \'' . $object->module . '\'';
                    $where .= ' AND `parent_object_name` = \'' . $object->object_name . '\'';
                    $where .= ' AND `id_parent` = ' . (int) $object->id;

                    if (!$with_deleted) {
                        $where .= ' AND `deleted` = 0';
                    }

                    $rows = self::getBdb()->getRows('bimp_file', $where, null, 'array', array('id', 'file_name', 'file_ext'), 'id', 'asc');

                    if (!is_null($rows)) {
                        foreach ($rows as $r) {
                            $file_name = $r['file_name'] . '.' . $r['file_ext'];
                            self::$cache[$cache_key][(int) $r['id']] = BimpRender::renderIcon(BimpTools::getFileIcon($file_name), 'iconLeft') . BimpTools::getFileType($file_name) . ' - ' . $file_name;
                        }
                    }
                }

                return self::$cache[$cache_key];
            }
        }

        return array();
    }

    // Sociétés: 

    public static function getSocieteContactsArray($id_societe, $include_empty = false)
    {
        $cache_key = '';

        if ((int) $id_societe) {
            $cache_key = 'societe_' . $id_societe . '_contacts_array';
            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();
                $where = '`fk_soc` = ' . (int) $id_societe;
                $rows = self::getBdb()->getRows('socpeople', $where, null, 'array', array('rowid', 'firstname', 'lastname'));
                if (!is_null($rows)) {
                    foreach ($rows as $r) {
                        self::$cache[$cache_key][(int) $r['rowid']] = BimpTools::ucfirst($r['firstname']) . ' ' . strtoupper($r['lastname']);
                    }
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getSocieteContratsArray($id_societe)
    {
        if ((int) $id_societe) {
            $cache_key = 'societe_' . $id_societe . '_contrats_array';
            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array(
                    0 => ''
                );

                $where = '`fk_soc` = ' . $id_societe;
                $rows = self::getBdb()->getRows('contrat', $where, null, 'array', array(
                    'rowid', 'ref'
                ));

                if (!is_null($rows)) {
                    foreach ($rows as $r) {
                        self::$cache[$cache_key][(int) $r['rowid']] = $r['ref'];
                    }
                }
            }

            return self::$cache[$cache_key];
        }

        return array(
            0 => ''
        );
    }

    public static function getSocietePropalsArray($id_societe)
    {
        if ((int) $id_societe) {
            $cache_key = 'societe_' . $id_societe . '_propals_array';

            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array(
                    0 => ''
                );
                $where = '`fk_soc` = ' . $id_societe;
                $rows = $this->db->getRows('propal', $where, null, 'array', array(
                    'rowid', 'ref'
                ));

                if (!is_null($rows)) {
                    foreach ($rows as $r) {
                        self::$cache[$cache_key][(int) $r['rowid']] = $r['ref'];
                    }
                }
            }

            return self::$cache[$cache_key];
        }

        return array();
    }

    public static function getSocieteEmails(Societe $societe, $with_contacts = true, $with_societe = true)
    {
        if (!BimpObject::objectLoaded($societe)) {
            echo 'ici';
            exit;
            return array();
        }

        $cache_key = 'societe_emails';
        if ($with_contacts) {
            $cache_key .= '_with_contacts';
        }
        if ($with_societe) {
            $cache_key .= 'with_societe';
        }

        if (!isset(self::$cache[$cache_key])) {
            global $langs;

            if ($with_contacts) {
                self::$cache[$cache_key] = $societe->thirdparty_and_contact_email_array((int) $with_societe);
            } else {
                self::$cache[$cache_key] = array(
                    'thirdparty' => $langs->trans("ThirdParty") . ': ' . dol_trunc($societe->name, 16) . " &lt;" . $societe->email . "&gt;"
                );
            }
        }

        return self::$cache[$cache_key];
    }

    // User: 

    public static function getUserCentresArray()
    {

        $centres = array(
            '' => ''
        );

        global $user;
        if (BimpObject::objectLoaded($user)) {
            $cache_key = 'user_' . $user->id . '_centres_array';
            if (!isset(self::$cache[$cache_key])) {
                $userCentres = explode(' ', $user->array_options['options_apple_centre']);
                $centres = self::getCentres();

                if (count($userCentres)) {
                    foreach ($userCentres as $code) {
                        if (preg_match('/^ ?([A-Z]+) ?$/', $code, $matches)) {
                            if (isset($centres[$matches[1]])) {
                                self::$cache[$cache_key][$matches[1]] = $centres[$matches[1]]['label'];
                            }
                        }
                    }
                }

                if (count($centres) <= 1) {
                    foreach ($centres as $code => $centre) {
                        self::$cache[$cache_key][$code] = $centre['label'];
                    }
                }
            }

            return self::$cache[$cache_key];
        }

        return array();
    }

    // Emails: 

    public static function getEmailTemplatesArray($email_type, $include_empty = false)
    {
        $cache_key = 'email_templates_' . $email_type;
        if (is_null(self::$cache[$cache_key])) {
            global $user;

            self::$cache[$cache_key] = array();
            if ($include_empty) {
                self::$cache[$cache_key][0] = '';
            }

            $where = '`type_template` = \'' . $email_type . '\'';
            $where .= ' AND (`fk_user` IS NULL OR `fk_user` = 0';
            if (BimpObject::objectLoaded($user)) {
                $where .= ' OR `fk_user` = ' . (int) $user->id;
            }
            $where .= ') AND `active` = 1';

            $rows = self::getBdb()->getRows('c_email_templates', $where, null, 'array', array(
                'rowid', 'label'
                    ), 'position', 'asc');

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['rowid']] = $r['label'];
                }
            }
        }

        return self::$cache[$cache_key];
    }

    public static function getEmailTemplateData($id_model)
    {
        if ((int) $id_model) {
            $cache_key = 'email_template_' . $id_model;

            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = self::getBdb()->getRow('c_email_templates', '`rowid` = ' . (int) $id_model, array('label', 'topic', 'content', 'content_lines'), 'array');
            }

            return self::$cache[$cache_key];
        }

        return null;
    }

    // Divers: 

    public static function getTaxes($id_country = 1)
    {
        $id_country = (int) $id_country;
        $cache_key = 'taxes_' . $id_country;

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();
            $rows = self::getBdb()->getRows('c_tva', '`fk_pays` = ' . $id_country . ' AND `active` = 1', null, 'array', array('rowid', 'taux'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['rowid']] = $r['taux'];
                }
            }
        }

        return self::$cache[$cache_key];
    }

    public static function getCentres()
    {
        if (!isset(self::$cache['centres'])) {
            global $tabCentre;

            if (!is_array($tabCentre)) {
                require_once DOL_DOCUMENT_ROOT . '/bimpsupport/centre.inc.php';
            }

            self::$cache['centres'] = array();

            foreach ($tabCentre as $code => $centre) {
                self::$cache['centres'][$code] = array(
                    'code'        => $code,
                    'label'       => $centre[2],
                    'tel'         => $centre[0],
                    'mail'        => $centre[1],
                    'address'     => $centre[7],
                    'zip'         => $centre[5],
                    'town'        => $centre[6],
                    'id_entrepot' => $centre[8]
                );
            }
        }

        return self::$cache['centres'];
    }

    public static function getCentresArray()
    {
        if (!isset(self::$cache['centres_array'])) {
            self::$cache['centres_array'] = array();

            foreach (self::getCentres() as $code => $centre) {
                self::$cache['centres_array'][$code] = $centre['label'];
            }
        }

        return self::$cache['centres_array'];
    }

    public static function getCondReglementsArray()
    {
        if (!isset(self::$cache['cond_reglements_array'])) {
            $rows = self::getBdb()->getRows('c_payment_term', '`active` > 0', null, 'array', array('rowid', 'libelle'), 'sortorder');

            self::$cache['cond_reglements_array'] = array();
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache['cond_reglements_array'][(int) $r['rowid']] = $r['libelle'];
                }
            }
        }

        return self::$cache['cond_reglements_array'];
    }

    public static function getModeReglementsArray($key = 'id', $active_only = true)
    {
        $cache_key = 'mode_reglements_by_' . $key;
        if ($active_only) {
            $cache_key .= '_active_only';
        }

        if (!isset(self::$cache[$cache_key])) {
            if (!class_exists('Form')) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
            }

            $form = new Form(self::getBdb()->db);
            $form->load_cache_types_paiements();

            self::$cache[$cache_key] = array();
            foreach ($form->cache_types_paiements as $id_payment => $payment_data) {
                if (!$active_only || ($active_only && (int) $payment_data['active'])) {
                    switch ($key) {
                        case 'id':
                            self::$cache[$cache_key][(int) $payment_data['id']] = $payment_data['label'];
                            break;

                        case 'code':
                            self::$cache[$cache_key][$payment_data['code']] = $payment_data['label'];
                            break;
                    }
                }
            }
        }

        return self::$cache[$cache_key];
    }

    public static function getAvailabilitiesArray()
    {
        if (!isset(self::$cache['availabilities_array'])) {
            if (!class_exists('Form')) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
            }

            $form = new Form(self::getBdb()->db);
            $form->load_cache_availability();

            self::$cache['availabilities_array'] = array();

            foreach ($form->cache_availability as $id => $availability) {
                self::$cache['availabilities_array'][(int) $id] = $availability['label'];
            }
        }

        return self::$cache['availabilities_array'];
    }

    public static function getDemandReasonsArray()
    {
        if (!isset(self::$cache['demand_reasons_array'])) {
            if (!class_exists('Form')) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
            }

            $form = new Form(self::getBdb()->db);
            $form->loadCacheInputReason();

            self::$cache['demand_reasons_array'] = array(
                0 => ''
            );

            foreach ($form->cache_demand_reason as $id => $dr) {
                self::$cache['demand_reasons_array'][(int) $id] = $dr['label'];
            }
        }

        return self::$cache['demand_reasons_array'];
    }

    public static function getSecteursArray()
    {
        if (!isset(self::$cache['secteurs_array'])) {
            self::$cache['secteurs_array'] = array(
                '' => ''
            );

            $rows = self::getBdb()->getRows('bimp_c_secteur');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache['secteurs_array'][$r->clef] = $r->valeur;
                }
            }
        }

        return self::$cache['secteurs_array'];
    }

    public static function getSystemsArray()
    {
        return array(
            300  => "iOs",
            1013 => "MAC OS 10.13",
            1012 => "MAC OS 10.12",
            1011 => "MAC OS 10.11",
            1010 => "MAC OS 10.10",
            1075 => "MAC OS 10.7.5",
            106  => "MAC OS 10.6",
            107  => "MAC OS 10.7",
            109  => "MAC OS 10.9",
            108  => "MAC OS 10.8",
            9911 => "Windows 10",
            203  => "Windows 8",
            204  => "Windows 7",
            202  => "Windows Vista",
            201  => "Windows XP",
            8801 => "Linux",
            2    => "Indéterminé",
            1    => "Autre"
        );
    }

    public static function getObjectListConfig($module, $object_name, $owner_type, $id_owner, $list_name)
    {
        $cache_key = $module . '_' . $object_name . '_' . $owner_type . '_' . $id_owner . '_' . $list_name . '_list_config';
        if (!isset(self::$cache[$cache_key])) {
            $config = BimpObject::getInstance('bimpcore', 'ListConfig');
            if ($config->find(array(
                        'type'       => $owner_type,
                        'id_object'  => (int) $id_owner,
                        'obj_module' => $module,
                        'obj_name'   => $object_name,
                        'list_name'  => $list_name
                            ), true)) {
                self::$cache[$cache_key] = $config;
            } else {
                self::$cache[$cache_key] = null;
            }
        }

        return $cache_key[$cache_key];
    }
}
