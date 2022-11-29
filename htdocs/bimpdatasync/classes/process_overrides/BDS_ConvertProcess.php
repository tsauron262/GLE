<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_ConvertProcess extends BDSProcess
{

    public static $methods = array(
        'SignaturesToConvert' => 'Conversion des signatures'
    );
    public static $default_public_title = 'Scripts de conversions des données';

    // Process : 
    public function initConvert(&$data, &$errors = array())
    {
        $method = $this->getOption('method', '');
        if (!$method) {
            $errors[] = 'Veuillez saisir la méthode à appeller';
        } else {
            if (!method_exists($this, 'find' . ucfirst($method))) {
                $errors[] = 'La méthode find' . ucfirst($method) . ' n\'existe pas';
            }
            if (!method_exists($this, 'exec' . ucfirst($method))) {
                $errors[] = 'La méthode exec' . ucfirst($method) . ' n\'existe pas';
            }

            $elements = $this->{'find' . $method}($errors);

            if (empty($elements)) {
                $errors[] = 'Aucun élément à convertir trouvé';
            } else {
                $data['steps'] = array(
                    'convert' => array(
                        'label'                  => BimpTools::getArrayValueFromPath(self::$methods, $method, 'Conversion'),
                        'on_error'               => 'continue',
                        'elements'               => $elements,
                        'nbElementsPerIteration' => (int) $this->getOption('nb_elements_per_iteration', 100)
                    )
                );
            }
        }
    }

    public function executeConvert($step_name, &$errors = array(), $extra_data = array())
    {
        $method = $this->getOption('method', '');
        return $this->{'exec' . $method}($errors);
    }

    // Méthodes : 

    public function findSignaturesToConvert(&$errors = array())
    {
        $sql = BimpTools::getSqlSelect(array('a.id'));
        $sql .= BimpTools::getSqlFrom('bimpcore_signature');
        $sql .= ' WHERE (SELECT COUNT(bss.id) FROM ' . MAIN_DB_PREFIX . 'bimpcore_signature_signataire bss WHERE bss.id_signature = a.id) = 0';
        $sql .= ' ORDER BY a.id asc';

        if ((int) $this->getOption('test_one', 0)) {
            $sql .= ' LIMIT 1';
        }

        $rows = $this->db->executeS($sql, 'array');

        $elems = array();
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $elems[] = (int) $r['id'];
            }
        } else {
            $errors[] = $this->db->err();
        }

        return $elems;
    }

    public function execSignaturesToConvert(&$errors = array())
    {
        $rows = $this->db->getRows('bimpcore_signature', 'id IN (' . implode(',', $this->references) . ')', null, 'array');

        if (is_array($rows)) {
            $this->setCurrentObjectData('bimpcore', 'BimpSignataire');
            $signature = BimpObject::getInstance('bimpcore', 'BimpSignature');
            foreach ($rows as $r) {
                $this->incProcessed();
                if ($this->db->insert('bimpcore_signature_signataire', array(
                            'id_signature'              => (int) $r['id'],
                            'status'                    => (int) $r['status'],
                            'type_signature'            => (int) $r['type'],
                            'code'                      => 'default',
                            'label'                     => 'Signataire',
                            'id_client'                 => (int) $r['id_client'],
                            'id_contact'                => (int) $r['id_contact'],
                            'id_user_client_signataire' => (int) $r['id_user_client_signataire'],
                            'nom'                       => $r['nom_signataire'],
                            'email'                     => $r['email_signataire'],
                            'fonction'                  => $r['fonction_signataire'],
                            'date_open'                 => $r['date_open'],
                            'allowed_users_client'      => $r['allowed_users_client'],
                            'date_signed'               => $r['date_signed'],
                            'ip_signataire'             => $r['ip_signataire'],
                            'base_64_signature'         => $r['base_64_signature'],
                            'allow_elec'                => $r['allow_elec'],
                            'allow_dist'                => $r['allow_dist'],
                            'allow_docusign'            => 0,
                            'allow_refuse'              => 0,
                            'need_sms_code'             => $r['need_sms_code'],
                            'code_sms_infos'            => $r['code_sms_infos']
                        )) <= 0) {
                    $this->incIgnored();
                    $signature->id = (int) $r['id'];
                    $this->Error('Echec création du signataire - ' . $this->db->err(), $signature);
                } else {
                    $this->incCreated();
                }
            }
        } else {
            $errors[] = 'Erreur SQL - ' . $this->db->err();
        }

        return array();
    }

    // install : 

    public static function install(&$errors = array(), &$warnings = array(), $title = '')
    {
        // Process:
        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'Convert',
                    'title'       => ($title ? $title : static::$default_public_title),
                    'description' => 'Conversions des données',
                    'type'        => 'other',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {
            // Options: 

            $options = array();

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Méthode à appeller',
                        'name'          => 'method',
                        'info'          => 'Indiquer le nom de la méthode à appeller sans le préfixe find / exec',
                        'type'          => 'text',
                        'default_value' => '',
                        'required'      => 1
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['nb_elements_per_iterations'] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Tester un seul élément',
                        'name'          => 'test_one',
                        'info'          => '',
                        'type'          => 'toggle',
                        'default_value' => '0',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Nb éléments par itérations',
                        'name'          => 'nb_elements_per_iterations',
                        'info'          => '',
                        'type'          => 'text',
                        'default_value' => '100',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            // Opérations: 
            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'    => (int) $process->id,
                        'title'         => 'Conversions / corrections',
                        'name'          => 'convert',
                        'description'   => '',
                        'warning'       => '',
                        'active'        => 1,
                        'use_report'    => 1,
                        'reports_delay' => 15
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($op)) {
                $warnings = array_merge($warnings, $op->addAssociates('options', $options));
            }
        }
    }
}
