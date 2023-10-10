<?php

class BimpYmlGenerator
{

    public static function renderObjectYmlGeneratorView($module, $object_name)
    {
        $html = '';

        $html .= '<div class="bimp_yml_object_generator_container"';
        $html .= ' data-module="' . $module . '"';
        $html .= ' data-object_name="' . $object_name . '"';
        $html .= '>';

        $html .= '<div style="padding: 15xp; background-color: #F2F2F2">';
        $html .= '<label>Fonction</label>';
        $html .= BimpInput::renderInput('select', 'yml_object_generator_function_select', '', array(
                    'options' => array(
                        ''                         => '',
                        'numericKeysToLiteralKeys' => 'Remplacement des clés numériques par des clés littérales'
                    )
        ));
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    public static function addParamsToContent($nTabs, &$content, $params)
    {
        if (!is_array($params) || empty($params)) {
            return;
        }

        foreach ($params as $key => $value) {
            self::addTabsToContent((int) $nTabs, $content);
            $content .= $key . ': ';

            if (is_string($value)) {
                $content .= $value . "\n";
            } else {
                $content .= "\n";
                self::addParamsToContent((int) $nTabs + 1, $content, $value);
            }
        }
    }

    public static function addTabsToContent($nTabs, &$content)
    {
        for ($i = 0; $i <= (int) $nTabs; $i++) {
            $content .= '    ';
        }
    }

    public static function numericKeysToLiteralKeys($file, $path, $type = '', &$errors = array())
    {
        $result = '';

        if (!file_exists($file)) {
            $errors[] = 'Fichier inexistant';
        } else {
            $params = spyc_load_file($file);

            if (empty($params)) {
                $errors[] = 'Echec du chargement du fichier';
            } else {
                $params = BimpTools::getArrayValueFromPath($params, $path, null);

                if (!is_array($params) || empty($params)) {
                    $errors[] = 'Paramètres non trouvés pour le chemin "' . $path . '"';
                } else {
                    $i = 1;
                    $new_params = array();
                    foreach ($params as $key => $sub_params) {
                        $new_key = '';
                        if (in_array($type, array('form', 'fields_table'))) {
                            if (isset($sub_params['field'])) {
                                $new_key = $sub_params['field'];
                            } else {
                                if ($type == 'form') {
                                    if (isset($sub_params['custom']) && (int) $sub_params['custom']) {
                                        if (isset($sub_params['input_name'])) {
                                            $new_key = $sub_params['input_name'];
                                        }
                                    }
                                }
                            }
                        }

                        if (!$new_key) {
                            $new_key = 'undef_' . $i;
                            $i++;
                        }

                        if (isset($new_params[$new_key])) {
                            $n = 2;
                            while (1) {
                                if (!isset($new_params[$new_key . $n])) {
                                    $new_key .= $n;
                                    break;
                                }

                                $n++;
                            }
                        }

                        $new_params[$new_key] = $sub_params;
                    }

                    $nTabs = count(explode('/', $path)) - 1;
                    self::addParamsToContent($nTabs, $result, $new_params);
                }
            }
        }

        return $result;
    }
}
