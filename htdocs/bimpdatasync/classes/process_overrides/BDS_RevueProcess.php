<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_RevueProcess extends BDSProcess
{
    /*
     */

    public static $current_version = 1;
    public static $default_public_title = 'Revue des accées';


    // Install:

    public static function install(&$errors = array(), &$warnings = array(), $title = '')
    {
        // Process:
        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'Revue',
                    'title'       => ($title ? $title : static::$default_public_title),
                    'description' => '',
                    'type'        => 'other',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {
            // Options:

            $options = array();

//            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
//                        'id_process'    => (int) $process->id,
//                        'label'         => 'A partir du',
//                        'name'          => 'date_from',
//                        'info'          => '',
//                        'type'          => 'date',
//                        'default_value' => '',
//                        'required'      => 0
//                            ), true, $warnings, $warnings);
//
//            if (BimpObject::objectLoaded($opt)) {
//                $options[] = (int) $opt->id;
//            }





            // Vérifs restes à payer factures:
            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'  => (int) $process->id,
                        'title'       => 'Lancer la revue des acces',
                        'name'        => 'revue',
                        'description' => '',
                        'warning'     => '',
                        'active'      => 1,
                        'use_report'  => 1,
                        'reports_delay' => 300
                            ), true, $warnings, $warnings);


            $warnings = array_merge($warnings, $op->addAssociates('options', $options));

        }
    }





    public function initRevue(&$data, &$errors = array())
    {
        BimpObject::loadClass('bimpdatasync', 'BDS_Report');
        $this->data_persistante['ok_for_process'] = 1;
        if (!count($errors)) {
            $where = "rowid IN (SELECT fk_usergroup FROM ".MAIN_DB_PREFIX."usergroup_rights)";
            $rows = $this->db->getRows('usergroup', $where, null, 'array', array('rowid'), 'rowid', 'desc');
            $elements = array();

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $elements[] = (int) $r['rowid'];
                }
            }

            if (empty($elements)) {
                $errors[] = 'Aucune group trouvée';
            } else {
                $this->data_persistante['infoRights'] = BimpCache::getRightsDefDataByModules();
            }
        }

        if(!count($errors)){
            $data['steps'] = array(
                'init' => array(
                    'label'                  => 'Vérif avant revue des groups',
                    'on_error'               => 'continue',
                    'elements'               => $elements,
                    'nbElementsPerIteration' => (int) 10
                ),
                'revue' => array(
                    'label'                  => 'Revue des groups',
                    'on_error'               => 'stop',
                    'elements'               => $elements,
                    'nbElementsPerIteration' => (int) 10
                )
            );
        }
    }

    public function executeRevue($step_name, &$errors = array(), $extra_data = array())
    {
        global $langs;
        $result = array();

        switch ($step_name) {
            case 'init':
                foreach ($this->references as $idGr) {
                    $group = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_UserGroup', $idGr);
                    $this->data_persistante['mail'][$idGr]['name'] = $group->getData('nom');
                    if($group->getData('fk_user') < 1){
                        $this->Error('Le group '.$group->getData('nom'). " n'a pas de responsable", $group);
                        $this->data_persistante['ok_for_process'] = false;
                    }
                    else{
                        $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $group->getData('fk_user'));
                        $this->data_persistante['mail'][$idGr]['mail'] = $user->getData('email');
                    }

                    $users = $group->getUserGroupUsers(true);
                    if(count($users)){
                        foreach($users as $user){
                            $this->data_persistante['mail'][$idGr]['users'][] = $user->getFullName();
                        }
                    }
                    else{
                        $this->Error('Le group '.$group->getData('nom'). " n'a pas de membres");
                        $this->data_persistante['ok_for_process'] = false;
                    }

//
                    $rights = $group->getRights();
//                    $rights = BimpCache::getBimpObjectObjects('bimpcore', 'Bimp_UserGroupRight', array('fk_usergroup'=> $group->id));
                    if(count($rights)){
                        foreach ($this->data_persistante['infoRights'] as $module => $module_rights) {
                            foreach ($module_rights as $id_right => $data) {
                                if(in_array($id_right, $rights)){
                                    $this->data_persistante['mail'][$idGr]['rights'][] = $module.' - '.$langs->trans($data['libelle']);

                                }
                            }
                        }
                    }
                    else{
                        $this->Error('Le group '.$group->getData('nom'). " n'a pas de droits");
                        $this->data_persistante['ok_for_process'] = false;
                    }




                    $this->DebugData($this->data_persistante['mail'][$idGr], 'Info : '.$group->getData('nom'));
                }
                break;
            case 'revue':
                if($this->data_persistante['ok_for_process']){
                    foreach ($this->references as $idGr) {
                        $data = $this->data_persistante['mail'][$idGr];
                        if(!is_array($data['users'])){
                            $this->Error('Pas de users');
                        }
                        elseif(!is_array($data['rights'])){
                            $this->Error('Pas de droits');
                        }
                        elseif(!isset($data['name']) || $data['name'] == ''){
                            $this->Error('Pas de nom');
                        }
                        elseif(!isset($data['mail']) || $data['mail'] == ''){
                            $this->Error('Pas de mail');
                        }
                        else{
                            $code = BimpTools::randomPassword(15);
                            $data['code'] = $code;
                            $data['date'] = date('Y-m-d');
                            $html = '';
                            $html .= 'Bonjour merci de confirmer les utilisateurs et droits pour le groupe : '.$data['name'];
                            $html .= '<h2>Utilisateurs : </h2>';
                            $html .= implode('<br/>', $data['users']);
                            $html .= '<h2>Droits : </h2>';
                            $html .= implode('<br/>', $data['rights']);
                            $html .= '<br/>Pour toutes informations ou changement, merci de répondre à ce mail, en revanche si tout est ok pour vous, merci de cliquer sur le lien suivant.';
                            $html .= '<br/><br/><a href="'.DOL_URL_ROOT.'/bimpcore/public/triggers.php?action=confirmGrp&id='.$idGr.'&code='.$code.'">Je confirme que tout est normal</a>';

                            $groupe = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_UserGroup', $idGr);
                            $errors = BimpTools::merge_array($errors, $groupe->appendField('data_revue', array('Y:'.date('Y') => $data)));


                            $bimpMail = new BimpMail($groupe, 'Validation acces groupe ERP', $data['mail'], '', $html);
                            $bimpMail->send($errors);

                            $this->Success('Mail OK : Envoyé a '.$data['mail'].'<br/>'.$html);
                        }
                    }
                }
                else
                    $this->Error('Corrigez dabord les erreurs');
                break;
        }

        return $result;
    }
}
