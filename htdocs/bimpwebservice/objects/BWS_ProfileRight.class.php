<?php

class BWS_ProfileRight extends BimpObject
{

    // Droits users: 

    public function canView()
    {
        return BimpCore::isUserDev();
    }

    public function canCreate()
    {
        return BimpCore::isUserDev();
    }

    public function canEdit()
    {
        return BimpCore::isUserDev();
    }

    public function canDelete()
    {
        return BimpCore::isUserDev();
    }

    public function canSetAction($action): int
    {
        return BimpCore::isUserDev();
    }

    // Getters params: 

    public function getProfileListTitle()
    {
        $profile = $this->getParentInstance();

        if (BimpObject::objectLoaded($profile)) {
            return 'Droits du profile webservice "' . $profile->getData('name') . '"';
        }

        return 'Droits profiles webservice';
    }

    public function getProfileListExtraHeaderButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('addMultiple') && $this->canSetAction('addMultiple')) {
            $buttons[] = array(
                'label'   => 'Ajout de droits multiple',
                'icon'    => 'fas_folder-plus',
                'onclick' => $this->getJsActionOnclick('addMultiple', array(
                    'id_profile' => (int) $this->getData('id_profile')
                        ), array(
                    'form_name' => 'add_muliple'
                ))
            );
        }



        return $buttons;
    }

    // Getters array: 

    public function getRequestsArray()
    {
        if (!defined('BWS_LIB_INIT')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/BWS_Lib.php';
        }

        return BWSApi::getRequestsArray();
    }

    // Actions

    public function actionAddMultiple($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_profile = (int) BimpTools::getArrayValueFromPath($data, 'id_profile', 0, $errors, true, 'Profile absent');
        $requests = BimpTools::getArrayValueFromPath($data, 'requests', array());
        $objects = BimpTools::getArrayValueFromPath($data, 'objects', array());

        if (empty($requests)) {
            $errors[] = 'Aucune requête sélectionnée';
        }

        if (empty($objects)) {
            $errors[] = 'Aucun objet sélectionné';
        }

        if (!count($errors)) {
            $nOk = 0;
            $nExists = 0;
            foreach ($objects as $object_name) {
                if (preg_match('/^(bimp[a-z]+)\-(.+)$/', $object_name, $matches)) {
                    $obj_module = $matches[1];
                    $obj_name = $matches[2];

                    if (!$obj_module) {
                        $warnings[] = 'Objet "' . $object_name . '" : nom du module absent ou invalide';
                        continue;
                    }
                    if (!$obj_name) {
                        $warnings[] = 'Objet "' . $object_name . '" : nom de l\'objet absent ou invalide';
                        continue;
                    }

                    foreach ($requests as $request_name) {
                        $where = 'id_profile = ' . $id_profile;
                        $where .= ' AND request_name = \'' . $request_name . '\'';
                        $where .= ' AND obj_module = \'' . $obj_module . '\'';
                        $where .= ' AND obj_name = \'' . $obj_name . '\'';
                        $id_right = (int) $this->db->getValue('bws_profile_right', 'id', $where);

                        if (!$id_right) {
                            $right_errors = array();
                            BimpObject::createBimpObject('bimpwebservice', 'BWS_ProfileRight', array(
                                'id_profile'   => $id_profile,
                                'request_name' => $request_name,
                                'obj_module'   => $obj_module,
                                'obj_name'     => $obj_name
                                    ), true, $right_errors);

                            if (count($right_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($right_errors, 'Echec ajout droit pour rquête "' . $request_name . '" - Objet: ' . $obj_module . '/' . $obj_name);
                            } else {
                                $nOk++;
                            }
                        } else {
                            $nExists++;
                        }
                    }
                } else {
                    $warnings[] = 'Syntaxe invalide pour l\'objet "' . $object_name . '" - Aucun droit ajouté pour cet objet';
                }
            }

            if ($nOk > 0) {
                if ($nOk > 1) {
                    $success = $nOk . ' droits ajoutés au profile avec succès';
                } else {
                    $success = 'Un droit ajouté au profile avec succès';
                }
            }
            if ($nExists > 0) {
                if ($nExists > 1) {
                    $warnings[] = $nExists . ' droits n\'ont pas été ajoutés car ils existaient déjà';
                } else {
                    $warnings[] = 'Un droit n\'a pas été ajouté car il existait déjà';
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}
