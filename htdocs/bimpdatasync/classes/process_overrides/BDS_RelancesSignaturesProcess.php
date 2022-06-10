<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_RelancesSignaturesProcess extends BDSProcess
{

    // Init opérations:

    public function initRelances(&$data, &$errors = array())
    {
        $data['steps'] = array();

        $signatures_ids = $this->getSignaturesToRelance();

        if (!empty($signatures_ids)) {
            $data['steps']['send_relances'] = array(
                'label'                  => 'Envoi des relances',
                'on_error'               => 'continue',
                'elements'               => $signatures_ids,
                'nbElementsPerIteration' => 10
            );
        } else {
            $errors[] = 'il n\'y a aucune signature à relancer';
        }
    }

    // Exec opérations:

    public function executeRelances($step_name, &$errors = array(), $extra_data = array())
    {
        $result = array();

        switch ($step_name) {
            case 'send_relances':
                if (!empty($this->references)) {
                    foreach ($this->references as $id_signature) {
                        $this->processRelance($id_signature, $errors);
                    }
                }
                break;
        }

        return $result;
    }

    // Traitement: 

    public function getSignaturesToRelance()
    {
        $return = array();

        $dt = new DateTime();

        $filters = array(
            'type'      => 0,
            'signed'    => 0,
            'date_open' => array(
                'and' => array(
                    array(
                        'operator' => '<',
                        'value'    => $dt->format('Y-m-d')
                    ),
                    array(
                        'operator' => '>',
                        'value'    => '0000-00-00'
                    )
                )
            )
        );

        $def_first_delay = (int) BimpTools::getArrayValueFromPath($this->params, 'default_first_relance_delay', 4);
        $def_next_delay = (int) BimpTools::getArrayValueFromPath($this->params, 'default_next_relance_delay', 3);

        $signatures = BimpCache::getBimpObjectObjects('bimpcore', 'BimpSignature', $filters);

        foreach ($signatures as $signature) {
            $date_open = (string) $signature->getData('date_open');

            if ($date_open) {
                $first_delay = $signature->getRelanceDelay(true);

                if (!$first_delay) {
                    $first_delay = $def_first_delay;
                }

                $dt = new DateTime();
                $dt->sub(new DateInterval('P' . $first_delay . 'D'));

                $relance = false;

                if ($date_open == $dt->format('Y-m-d')) {
                    $relance = true;
                } else {
                    $i = 0;

                    $next_delay = $signature->getRelanceDelay(false);

                    if (!$next_delay) {
                        $next_delay = $def_next_delay;
                    }

                    $interval = new DateInterval('P' . $next_delay . 'D');
                    $dt->sub($interval);

                    while ($date_open <= $dt->format('Y-m-d')) {
                        if ($date_open == $dt->format('Y-m-d')) {
                            $relance = true;
                            break;
                        }

                        $i++;
                        if ($i >= 100) {
                            BimpCore::addlog('Signature non relançable', Bimp_Log::BIMP_LOG_ALERTE, 'bimpcore', $signature);
                            break;
                        }

                        $dt->sub($interval);
                    }
                }

                if ($relance) {
                    $return[] = $signature->id;
                }
            }
        }

        return $return;
    }

    public function processRelance($id_signature, &$errors = array())
    {
        $signature = BimpCache::getBimpObjectInstance('bimpcore', 'BimpSignature', $id_signature);

        if (BimpObject::objectLoaded($signature)) {
            $mail_errors = array();
            $mail_warnings = array();

            $signature->sendRelanceEmail($mail_errors, $mail_warnings);

            if (count($mail_warnings)) {
                $this->Alert(BimpTools::getMsgFromArray($mail_warnings), $signature, '#' . $signature->id);
            }

            if (count($mail_errors)) {
                $this->Error(BimpTools::getMsgFromArray($mail_errors), $signature, '#' . $signature->id);
            } else {
                $this->Success('Relance effectuée', $signature, '#' . $signature->id);
            }
        } else {
            $this->Error('Signature #' . $id_signature . ' non trouvée', null, '#' . $id_signature);
        }
    }

    // Install: 

    public static function install(&$errors = array(), &$warnings = array())
    {
        // Process: 

        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'RelancesSignatures',
                    'title'       => 'Relances signatures',
                    'description' => '',
                    'type'        => 'other',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {
            // Params: 

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'default_first_relance_delay',
                'label'      => 'Délai par défaut pour la première relance',
                'value'      => 4
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'default_next_relance_delay',
                'label'      => 'Délai par défaut pour les relances suivantes',
                'value'      => 3
                    ), true, $warnings, $warnings);

            // Options: 
            // Opérations: 

            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'    => (int) $process->id,
                        'title'         => 'Relance signatures en attente',
                        'name'          => 'relances',
                        'description'   => '',
                        'warning'       => '',
                        'active'        => 1,
                        'use_report'    => 1,
                        'reports_delay' => 365
                            ), true, $warnings, $warnings);
        }
    }
}
