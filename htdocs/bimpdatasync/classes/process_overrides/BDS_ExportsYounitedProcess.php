<?php
#https://app-pp-resellerpublicapi-weu-01.azurewebsites.net/swagger/index.html
#https://resellerpublic-api.pp-services.younited-credit.com/swagger/index.html

//PROD: 
//Clientid : 4534ffae-f8cb-4bc4-ba62-2152ea0e97a9
//Secret : vD2oGx.-xR_zyJ1~SN612wD13R0aFHJc~L 
//
//Pré-PROD: 
//Clientid : 5723b912-8c85-4bed-9873-d401323174e8
//Secret : C01iQ25Gj~J-6Mne3f0B0kEaS.AA-f.07W

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSExportProcess.php');

class BDS_ExportsYounitedProcess extends BDSExportProcess
{
    public static $default_public_title = 'Exports Younited';
    
    // Opérations: 

    public function initTestAuthentification(&$data, &$errors = array())
    {
        $errors = $this->authenticate(true);

        if (!count($errors)) {
            $data['result_html'] = BimpRender::renderAlerts('Authentification effectuée avec succès', 'success');
        }
    }
    
    public function executeGetProducts($url, &$data, &$errors = array(), $extra_data = array()){
        $ch = curl_init();

        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->params['token'],
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLPROTO_HTTPS, 1);

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $this->DebugData($info, 'Infos CURL');
        $data['result_html'] = 'Réponse: <pre>' . print_r($response, 1) . '</pre>';
    }

    public function initGetProducts(&$data, &$errors = array())
    {
        $errors = $this->authenticate();
        if (!count($errors)) {
//            $ref_prod = (string) $this->options['ref_prod'];

            $url = $this->params['api_url'] . 'own-catalog/products';

            $this->executeGetProducts($url, $data, $errors);
        }
    }

    public function initGetProductsApple(&$data, &$errors = array())
    {
        $errors = $this->authenticate();
        if (!count($errors)) {
//            $ref_prod = (string) $this->options['ref_prod'];

            $url = $this->params['api_url'] . 'provided-catalog/products';

            $this->executeGetProducts($url, $data, $errors);
            
        }
    }

    public function initExportCatalog(&$data, &$errors = array())
    {
        $data['steps'] = array();

        $refs = $this->getRefsToExport($errors);

        if (!count($errors)) {
            if (!count($refs['not_apple']) && !count($refs['apple'])) {
                $data['result_html'] = BimpRender::renderAlerts('Aucun produit à exporter trouvé', 'warning');
            } else {
                if (count($refs['apple'])) {
                    $data['steps']['export_apple_prods'] = array(
                        'label'                  => 'Export des produits Apple',
                        'on_error'               => 'hold',
                        'nbElementsPerIteration' => 20,
                        'elements'               => $refs['apple']
                    );
                }
                
                if (count($refs['not_apple'])) {
                    $data['steps']['export_not_apple_prods'] = array(
                        'label'                  => 'Export des produits non Apple',
                        'on_error'               => 'hold',
                        'nbElementsPerIteration' => 20,
                        'elements'               => $refs['not_apple']
                    );
                }


                $data['steps']['end_export'] = array(
                    'label'                  => 'Finalisation',
                    'on_error'               => 'hold',
                    'nbElementsPerIteration' => 0
                );
            }
        }
    }

    public function executeExportCatalog($step_name, &$errors = array(), $extra_data = array())
    {
        if ($step_name === 'end_export') {
            $err = $this->updateParameter('last_export_tms', time());

            if (count($err)) {
                $errors[] = BimpTools::getMsgFromArray($err, 'Echec de la mise à jour du timestamp du dernier export');
            }
        } else {
            if (!empty($this->references)) {
                $sql = BimpTools::getSqlSelect(array('a.rowid', 'a.ref', 'a.tosell', 'a.label', 'a.price_ttc', 'a.price', 'a.url', 'pef.categorie', 'pef.deee'));
                $sql .= BimpTools::getSqlFrom('product', array('pef' => array(
                                'alias' => 'pef',
                                'table' => 'product_extrafields',
                                'on'    => 'pef.fk_object = a.rowid'
                )));
                $sql .= BimpTools::getSqlWhere(array(
                            'a.ref' => array(
                                'in' => $this->references
                            )
                ));

                $rows = $this->db->executeS($sql, 'array');

                if (is_array($rows)) {
                    $categs = BimpCache::getProductsTagsByTypeArray('categorie', false);
                    $base_url = $this->params['api_url'];
                    $url = '';
                    $prod_instance = BimpObject::getInstance('bimpcore', 'Bimp_Product');
                    $this->setCurrentObject($prod_instance);

                    foreach ($rows as $r) {
                        $ref = $r['ref'];
                        $prod_instance->id = (int) $r['rowid'];
                        $this->incProcessed();

                        switch ($step_name) {
                            case 'export_not_apple_prods':
//                                $ref = str_replace("ZD/A", "B/A", $ref);
                                $url = $base_url . 'own-catalog/product?reference=' . urlencode($ref);
                                $params = array(
                                    'label'      => $r['label'],
                                    'price'     => array(
                                        'includingVat' => round($r['price_ttc'],2),
                                        'excludingVat' => round($r['price'],2),
                                    ),
                                    'weee'     => array(
                                        'includingVat' => ($r['deee'] > 0? round($r['deee']*1.2,2) : 0),
                                        'excludingVat' => ($r['deee'] > 0? round($r['deee'],2) : 0),
                                    ),
                                    'pictureUrl' => $r['url'],
//                                    'accessoryCategory'       => ((int) $r['categorie'] && isset($categs[(int) $r['categorie']]) ? $categs[(int) $r['categorie']] : ''),
                                    'accessoryCategory'       => 'Others',
                                    'isEnabled'  => ((int) $r['tosell'] ? true : false),
                                    'ean'       => $prod_instance->getData('barcode')
                                );
                                if(stripos($ref, "-") == 3)
                                        $params['mpm'] = substr($ref,4);
                                
                                break;

                            case 'export_apple_prods':
                                $part_number = $ref;
                                if (preg_match('/^APP\-(.+)$/', $part_number, $matches)) {
                                    $part_number = $matches[1];
                                }

                                $url = $base_url . 'provided-catalog/product?partnumber=' . urlencode($part_number);
//                                $url = $base_url . 'provided-catalog/product?partnumber=' . urlencode('MUHQ2B/A');

                                $params = array(
                                    'price'     => array(
                                        'includingVat' => round($r['price_ttc'],2),
                                        'excludingVat' => round($r['price'],2),
                                    ),
                                    'weee'     => array(
                                        'includingVat' => ($r['deee'] > 0? round($r['deee']*1.2,2) : 0),
                                        'excludingVat' => ($r['deee'] > 0? round($r['deee'],2) : 0),
                                    ),
                                    'isEnabled' => ((int) $r['tosell'] ? true : false),
                                    'reference' => $ref,
                                    'ean'       => $prod_instance->getData('barcode')
                                );
                                break;
                        }

                        // Vérif de la validité du token et réauthentification si nécessaire: 
                        $auth_errors = $this->authenticate();
                        if (count($auth_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($auth_errors, 'Echec authentification');
                            break;
                        }

                        $retry = true;
                        $nRetries = 0;
                        while ($retry) {
                            $retry = false;

                            $ch = curl_init();
                            $headers = array(
                                'Accept: application/json',
                                'Content-Type: application/json',
                                'Authorization: Bearer ' . $this->params['token']
                            );

                            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLPROTO_HTTPS, 1);

                            $response = curl_exec($ch);
                            $curl_infos = curl_getinfo($ch);
                            curl_close($ch);

                            $code = $curl_infos['http_code'];

                            if ($code === 204) {
                                $this->Success('Mise à jour OK', $prod_instance, $ref);
                                $this->incUpdated();
                                $this->DebugData($params, 'PARAMS');
                            } elseif ($code === 401) {
                                // Forçage de la réauthentification: 
                                $auth_errors = $this->authenticate(true);
                                if (count($auth_errors)) {
                                    $errors[] = BimpTools::getMsgFromArray($auth_errors, 'Echec authentification');
                                    break 2;
                                } elseif ($nRetries < 10) {
                                    $retry = true;
                                    $nRetries++;
                                } else {
                                    // Par précaution, pour évéiter boucles infinies, mais ne devrait jamais arriver. 
                                    $errors[] = 'Trop de tentatives d\'authentification sur une même référence, Erreur 401 plus de 10 fois';
                                    break 2;
                                }
                            } else {
                                $this->DebugData($response, 'Réponse');
                                $this->DebugData($curl_infos, 'CURL INFOS');
                                $this->DebugData($params, 'PARAMS');
                                $this->DebugData(array(
                                    'URL' => $url
                                        ), 'autres INFOS');

                                $this->incIgnored();
                                $msg = 'Echec requête (Code: ' . $code . ')';

                                if (is_string($response) && $response) {
                                    $msg .= '. ' . $response;
                                } elseif (isset($response['detail'])) {
                                    $msg .= '. Détails: ' . $response['detail'];
                                }
                                
                                $msg .= " ".urlencode(str_ireplace("app-","", $ref));

                                $this->Error($msg, $prod_instance, $ref." : ".$r['label']);
                            }
                        }
                    }
                } else {
                    $errors[] = $this->db->err();
                }
            }
        }
    }

    // Traitements: 

    public function getRefsToExport(&$errors = array())
    {
        $refs = array(
            'not_apple' => array(),
            'apple'     => array()
        );

        $filters = array(
            'pef.validate' => 1
        );

        if ($this->options['use_tms']) {
            $last_export_tms = (int) $this->params['last_export_tms'];
            $filters['a.tms'] = array(
                'operator' => '>',
                'value'    => date("Y-m-d H:i:s", $last_export_tms)
            );
        } else {
            $filters['a.tosell'] = 1;
        }

        $joins = array(
            'pef' => array(
                'table' => 'product_extrafields',
                'on'    => 'a.rowid = pef.fk_object',
                'alias' => 'pef'
            )
        );

        $sql = BimpTools::getSqlSelect(array('a.ref'));
        $sql .= BimpTools::getSqlFrom('product', $joins);
        $sql .= BimpTools::getSqlWhere($filters);

        $sql .= ' AND ref NOT LIKE "app-Z%" AND ref NOT LIKE "app-app-%"';
//        $sql .= ' AND ref NOT LIKE "app-Z%" AND ref NOT LIKE "app-app-%" AND ref NOT LIKE "app-3%"';
//        $sql .= ' AND ref NOT LIKE "app-%"  ';
        
        $sql .= ' AND ((pef.gamme != 3241 AND pef.gamme != 3247 AND pef.gamme != 3256 AND pef.gamme != 3238 AND pef.gamme != 3235 AND pef.gamme != 3259 AND pef.gamme != 3265 AND pef.gamme != 3244 AND pef.gamme != 3262) AND (pef.categorie != 2791 AND pef.categorie != 2815 AND pef.categorie != 2806 AND pef.categorie != 2836 AND pef.categorie != 2851 AND pef.categorie != 2833 AND pef.categorie != 2809 AND pef.categorie != 2773 AND pef.categorie != 3139 AND pef.categorie != 3220 AND pef.categorie != 3136 AND pef.categorie != 2824) AND (pef.nature != 2977 AND pef.nature != 3091 AND pef.nature != 3070 AND pef.nature != 3061 AND pef.nature != 3058 AND pef.nature != 2932 AND pef.nature != 3067 AND pef.nature != 3004 AND pef.nature != 2866 AND pef.nature != 2977 AND pef.nature != 3082 AND pef.nature != 3064 AND pef.nature != 3022 AND pef.nature != 3046 AND pef.nature != 2911 AND pef.nature != 3085) AND (pef.famille != 3100 AND pef.famille != 3118 AND pef.famille != 3109 AND pef.famille != 3106 AND pef.famille != 3112 AND pef.famille != 3097 AND pef.famille != 3121 AND pef.famille != 3115 AND pef.famille != 3103)'
                . ' || ref LIKE "app-s%" ||  ref LIKE "zzrecycle%")';
        
//        $sql .= ' AND ref LIKE "zzrecycle%" ';
        
        $sql .= BimpTools::getSqlOrderBy('a.rowid', 'DESC');
//        $sql .= BimpTools::getSqlLimit(3000); // POUR TESTS

        $rows = $this->db->executeS($sql, 'array');

        if (is_array($rows)) {
            foreach ($rows as $r) {
                if (preg_match('/^APP\-.+$/', $r['ref'])) {
                    $refs['apple'][] = $r['ref'];
                } else {
                    $refs['not_apple'][] = $r['ref'];
                }
            }
        } else {
            $errors[] = $this->db->err();
        }

        return $refs;
    }

    public function authenticate($force_reauthenticate = false)
    {
        $errors = array();

        if ($force_reauthenticate) {
            $this->params['token'] = '';
            $this->params['token_expire_tms'] = 0;
        } elseif ($this->params['token'] && (int) $this->params['token_expire_tms']) {
            $cur_tms = time() + 1;

            if ($cur_tms > (int) $this->params['token_expire_tms']) {
                $err = $this->updateParameter('token', '');

                if (count($err)) {
                    $errors[] = BimpTools::getMsgFromArray($err, 'Echec de la mise à jour du paramètre "token"');
                }

                $err = $this->updateParameter('token_expire_tms', 0);
                if (count($err)) {
                    $errors[] = BimpTools::getMsgFromArray($err, 'Echec de la mise à jour du paramètre "token_expire_tms"');
                }
            }
        }

        if (!count($errors)) {
            if (!$this->params['token']) {
                $url = $this->params['token_url'];

                $params = array(
                    'client_id'     => $this->params['client_id'],
                    'client_secret' => $this->params['client_secret'],
                    'scope'         => $this->params['scope'],
                    'grant_type'    => 'client_credentials'
                );

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLPROTO_HTTPS, 1);

                $response = json_decode(curl_exec($ch), 1);

                if (!$response) {
                    $errors[] = 'Echec de la requête d\'authentification';
                } elseif (isset($response['error_description']) && $response['error_description']) {
                    $errors[] = $response['error_description'];
                } elseif (isset($response['access_token']) && $response['access_token']) {
                    $err = $this->updateParameter('token', $response['access_token']);
                    if (count($err)) {
                        $errors[] = BimpTools::getMsgFromArray($err, 'Echec de l\'enregistrement du token');
                    }

                    $tms = time();

                    if (isset($response['expires_in'])) {
                        $tms += (int) $response['expires_in'];
                    }

                    $err = $this->updateParameter('token_expire_tms', $tms);
                    if (count($err)) {
                        $errors[] = BimpTools::getMsgFromArray($err, 'Echec de l\'enregistrement du délai d\'expiration du token');
                    }
                }

                $info = curl_getinfo($ch);
                curl_close($ch);

                $this->DebugData($response, 'Réponse Authentification');
                $this->DebugData($info, 'Infos CURL Authentification');
            }
        }

        return $errors;
    }

    // Install: 

    public static function install(&$errors = array(), &$warnings = array(), $title = '')
    {
        // Process: 

        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'ExportsYounited',
                    'title'       => ($title ? $title : static::$default_public_title),
                    'description' => 'Exports vers Younited via API',
                    'type'        => 'export',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {

            // Params: 

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'token_url',
                'label'      => 'URL Token',
                'value'      => 'https://login.microsoftonline.com/younited-credit.fr/oauth2/v2.0/token'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'scope',
                'label'      => 'Scope',
                'value'      => 'api://app-pp-resellerpublicapi/.default'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'client_id',
                'label'      => 'ID client',
                'value'      => '604dafc6-bc89-4c0a-b127-6571717f2ad4'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'client_secret',
                'label'      => 'Secret client',
                'value'      => '.9Zxaik4.KM9vAA_erewvH8stB2LJSK~~8'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'token',
                'label'      => 'Token',
                'value'      => ''
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'token_expire_tms',
                'label'      => 'Expiration token',
                'value'      => ''
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'last_export_tms',
                'label'      => 'TImestamp dernière export',
                'value'      => 0
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'api_url',
                'label'      => 'Adresse API',
                'value'      => 'https://resellerpublic-api.pp-services.younited-credit.com/api/'
                    ), true, $warnings, $warnings);
            
            
            

            // Options: 

            $options = array();

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Exporter seulement les produits mis à jours depuis le dernier export',
                        'name'          => 'use_tms',
                        'info'          => '',
                        'type'          => 'toggle',
                        'default_value' => 1,
                        'required'      => 1
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            // Opérations: 

            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'    => (int) $process->id,
                        'title'         => 'Export du catalogue',
                        'name'          => 'exportCatalog',
                        'description'   => '',
                        'warning'       => '',
                        'active'        => 1,
                        'use_report'    => 1,
                        'reports_delay' => 60
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($op)) {
                $warnings = array_merge($warnings, $op->addAssociates('options', $options));
            }

            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'  => (int) $process->id,
                        'title'       => 'Test authentification',
                        'name'        => 'testAuthentification',
                        'description' => '',
                        'warning'     => '',
                        'active'      => 1,
                        'use_report'  => 0
                            ), true, $warnings, $warnings);

            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'  => (int) $process->id,
                        'title'       => 'Lister produits envoyés',
                        'name'        => 'getProducts',
                        'description' => '',
                        'warning'     => '',
                        'active'      => 1,
                        'use_report'  => 0
                            ), true, $warnings, $warnings);
            
            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'  => (int) $process->id,
                        'title'       => 'Lister produits apple envoyés',
                        'name'        => 'getProductsApple',
                        'description' => '',
                        'warning'     => '',
                        'active'      => 1,
                        'use_report'  => 0
                            ), true, $warnings, $warnings);
        }
    }
}
