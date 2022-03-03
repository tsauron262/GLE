<?php

class Bimp_ParamsUser extends BimpObject
{

    function __construct($module, $object_name)
    {
        return parent::__construct($module, $object_name);
    }

    // Getters params:
    // récupération des paramètres lié à l'utilisateur en bdd 

    public function getParamValue($defaultParam)
    {
        $entity = 1;
        $params = $this->db->getRows('user_param', 'fk_user = ' . $this->id . ' AND entity = ' . $entity, null, 'array', array('param'));
        foreach ($params as $param) {
            foreach ($param as $key => $value) {
                $p = $value[0];
                if (empty($p)) {
                    return $defaultParam;
                } else {
                    if ($defaultParam === $p) {
                        return $p;
                    } else {
                        return $defaultParam;
                    }
                }
            }
        }
    }

    public function getThemeNames()
    {
        global $conf, $langs, $db;

        $dirthemes = array('/theme');

        if (!empty($conf->modules_parts['theme'])) {
            foreach ($conf->modules_parts['theme'] as $reldir) {
                $dirthemes = array_merge($dirthemes, (array) ($reldir . 'theme'));
            }
        }

        $dirthemes = array_unique($dirthemes);

        $return = array();

        $i = 0;
        foreach ($dirthemes as $dir) {
            $dirtheme = dol_buildpath($dir, 0);
            $urltheme = dol_buildpath($dir, 1);
            if (is_dir($dirtheme)) {
                $handle = opendir($dirtheme);
                if (is_resource($handle)) {
                    while (($subdir = readdir($handle)) !== false) {
                        if (is_dir($dirtheme . "/" . $subdir) && substr($subdir, 0, 1) <> '.' && substr($subdir, 0, 3) <> 'CVS' && !preg_match('/common|phones/i', $subdir)) {
                            if ($subdir == $conf->global->MAIN_THEME)
                                $title = $langs->trans("ThemeCurrentlyActive");
                            else
                                $title = $langs->trans("ShowPreview");

                            $return[$subdir] = $subdir;

                            $i++;
                        }
                    }
                }
            }
            return $return;
        }
    }

    public function getCurrentAttr($param)
    {
        $id_user = $_REQUEST['id'];

        return $this->db->getValue('user_param', 'value', 'param = "' . $param . '" AND fk_user = ' . $id_user);
    }

    public function getTargetPage()
    {

        global $conf, $langs;

        // List of possible landing pages
        $tmparray = array('index.php' => 'Dashboard');
        if (!empty($conf->societe->enabled))
            $tmparray['societe/index.php?mainmenu=companies&leftmenu='] = 'ThirdPartiesArea';
        if (!empty($conf->projet->enabled))
            $tmparray['projet/index.php?mainmenu=project&leftmenu='] = 'ProjectsArea';
        if (!empty($conf->holiday->enabled) || !empty($conf->expensereport->enabled))
            $tmparray['hrm/index.php?mainmenu=hrm&leftmenu='] = 'HRMArea';   // TODO Complete list with first level of menus
        if (!empty($conf->product->enabled) || !empty($conf->service->enabled))
            $tmparray['bimpcore/?fc=products&mainmenu=products'] = 'ProductsAndServicesArea';
        if (!empty($conf->propal->enabled) || !empty($conf->commande->enabled) || !empty($conf->ficheinter->enabled) || !empty($conf->contrat->enabled))
            $tmparray['bimpcommercial/index.php?fc=tabCommercial'] = 'CommercialArea';
        if (!empty($conf->compta->enabled) || !empty($conf->accounting->enabled))
            $tmparray['compta/index.php?mainmenu=compta&leftmenu='] = 'AccountancyTreasuryArea';
        if (!empty($conf->adherent->enabled))
            $tmparray['adherents/index.php?mainmenu=members&leftmenu='] = 'MembersArea';
        if (!empty($conf->agenda->enabled))
            $tmparray['comm/action/index.php?mainmenu=agenda&leftmenu='] = 'Agenda';

        return $tmparray;
    }

    public function getLanguages($filter = null)
    {
        global $langs;

        $langs_available = $langs->get_available_languages(DOL_DOCUMENT_ROOT, 12);

        $returnLangs = array();

        asort($langs_available);

        $i = 0;
        foreach ($langs_available as $key => $value) {
            $valuetoshow = $value;
            $returnLangs[$key] = $valuetoshow;
            $i++;
        }

        return $returnLangs;
    }

    public function insertOrUpdateUserValue($data, $dataname = '', $param = '', $update, $warnings = array(), $errors = array(), $success_callback)
    {
        global $user;

        if ($dataname === 'theme_edit') {
            if (empty($data[$dataname])) {
                $errors;
            } else {
                $update = Array('value' => $data[$dataname]);
                $errors = $this->db->update('user_param', $update, 'fk_user = ' . $_REQUEST['id'] . ' AND param = "' . $param . '"');
                return $success_callback;
            }
        }

        if (empty($data[$dataname])) {
            $errors;
        } else {
            $update = Array('value' => $data[$dataname]);
            $value = $this->db->getValue('user_param', 'value', 'fk_user = ' . (int) $user->id . ' AND param = "' . $param . '"');
            if (empty($value)) {
                $errors = $this->db->insert('user_param', array(
                    'fk_user' => $_REQUEST['id'],
                    'entity'  => 1,
                    'param'   => $param,
                    'value'   => $update['value']));
                return $success_callback;
            } else {
                $errors = $this->db->update('user_param', $update, 'fk_user = ' . $_REQUEST['id'] . ' AND param = "' . $param . '"');
                return $success_callback;
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success'          => $success,
            'success_callback' => $success_callback
        );
    }

    public function actionEditTheme($data, &$success = '')
    {

        global $user, $langs;

        $errors = array();
        $warnings = array();
        $success_callback = 'document.location.href = "' . DOL_URL_ROOT . '/bimpcore/?fc=user&id=' . $_REQUEST['id'] . '&navtab-maintabs=params&navtab-params_tabs=interface_tab"';

        $this->insertOrUpdateUserValue(
                $data,
                'theme_edit',
                'MAIN_THEME',
                $update = Array('value' => $data['theme_edit']),
                $warnings,
                $errors[] = 'Aucun thème sélectionné',
                $success_callback
        );

        $this->insertOrUpdateUserValue(
                $data,
                'languages_edit',
                'MAIN_LANG_DEFAULT',
                $update = Array('value' => $data['languages_edit']),
                $warnings,
                $errors[] = 'Aucune langue sélectionnée',
                $success_callback
        );

        $this->insertOrUpdateUserValue(
                $data,
                'target_page_edit',
                'MAIN_LANDING_PAGE',
                $update = Array('value' => $data['target_page_edit']),
                $warnings,
                $errors[] = 'Aucune page cible sélectionnée',
                $success_callback
        );
    }
}
