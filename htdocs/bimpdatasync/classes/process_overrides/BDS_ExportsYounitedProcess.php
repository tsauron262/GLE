<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSExportProcess.php');

class BDS_ExportsYounitedProcess extends BDSExportProcess
{

    // Opérations: 

    public function initTest(&$data, &$errors = array())
    {
        $errors = $this->authenticate();
        if (!count($errors)) {
            $base_url = 'https://app-pp-resellerpublicapi-weu-01.azurewebsites.net/api/';
            $url = $base_url . 'own-catalog/product';

            $ref = 'MIC-SK58055';

            $ch = curl_init();

            $headers = array(
                'Accept: application/json',
                'Content-Type: application/json',
//                'Authorization: Bearer ' . $this->params['token'],
                'Authorization: Bearer fkjhdlfkjgdlfkjg'
            );

            $params = array(
                'label'     => 'Microsoft Office 365 E3 Charity Licence d\'abonnement annuel - Association',
                'price'     => 63.64800000,
                'type'      => 'LOGICIELS',
                'isEnabled' => false
            );

            $url .= '?reference=' . urlencode($ref);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLPROTO_HTTPS, 1);

            $response = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            $html = '';
            $html .= 'Response: <pre>';
            $html .= print_r($response, 1);
            $html .= '</pre>';
            $html .= 'Infos: <pre>';
            $html .= print_r($info, 1);
            $html .= '</pre>';
            $data['result_html'] = $html;
        }

//        if (!count($errors)) {
//            $data['result_html'] = BimpRender::renderAlerts('Authentification effectuée avec succès', 'success');
//        }
    }

    public function initExportCatalog(&$data, &$errors = array())
    {
        $data['steps'] = array();

        $refs = $this->getRefsToExport($errors);

        if (!count($errors)) {
            if (!count($refs['not_apple']) && !count($refs['apple'])) {
                $data['result_html'] = BimpRender::renderAlerts('Aucun produit à exporter trouvé', 'warning');
            } else {
                if (count($refs['not_apple'])) {
                    $data['steps']['export_not_apple_prods'] = array(
                        'label'                  => 'Export des produits non Apple',
                        'on_error'               => 'hold',
                        'nbElementsPerIteration' => 10,
                        'elements'               => $refs['not_apple']
                    );
                }

                if (count($refs['apple'])) {
                    $data['steps']['export_apple_prods'] = array(
                        'label'                  => 'Export des produits Apple',
                        'on_error'               => 'hold',
                        'nbElementsPerIteration' => 10,
                        'elements'               => $refs['apple']
                    );
                }
            }
        }
    }

    public function executeExportCatalog($step_name, &$errors = array())
    {
        if (!empty($this->references)) {
            $sql = BimpTools::getSqlSelect(array('a.rowid', 'a.ref', 'a.tosell', 'a.label', 'a.price_ttc', 'pef.categorie'));
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
                $base_url = 'https://app-pp-resellerpublicapi-weu-01.azurewebsites.net/api/';
                $url = '';
                $prod_instance = BimpObject::getInstance('bimpcore', 'Bimp_Product');
                $this->setCurrentObject($prod_instance);

                foreach ($rows as $r) {
                    $ref = $r['ref'];
                    $prod_instance->id = (int) $r['rowid'];
                    $this->incProcessed();

                    switch ($step_name) {
                        case 'export_not_apple_prods':
                            $url = $base_url . 'own-catalog/product?reference=' . $ref;
                            $params = array(
                                'label'      => $r['label'],
                                'price'      => $r['price_ttc'],
                                'pictureUrl' => '',
                                'type'       => ((int) $r['categorie'] && isset($categs[(int) $r['categorie']]) ? $categs[(int) $r['categorie']] : ''),
                                'isEnabled'  => ((int) $r['tosell'] ? true : false)
                            );
                            break;

                        case 'export_apple_prods':
                            $part_number = $ref;
                            if (preg_match('/^APP\-(.+)$/', $part_number, $matches)) {
                                $part_number = $matches[1];
                            }

                            $url = $base_url . 'provided-catalog/product?partnumber=' . $part_number;

                            $params = array(
                                'price'     => $r['price_ttc'],
                                'isEnabled' => ((int) $r['tosell'] ? true : false)
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
                                $errors[] = 'Trop de tentatives d\'authentification sur une même référence';
                                break 2;
                            }
                        } else {
                            $this->DebugData($response, 'Réponse');
                            $this->incIgnored();
                            $msg = 'Echec requête (Code: ' . $code . ')';

                            if (isset($response['detail'])) {
                                $msg .= '. Détails: ' . $response['detail'];
                            }

                            $this->Error($msg, $prod_instance, $ref);
                        }
                    }
                }
            } else {
                $errors[] = $this->db->err();
            }
        }
    }

    // traitements: 

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
                'value'    => $last_export_tms
            );
        } else {
            $filters['a.tosell'] = 1;
        }

        // POUR TESTS: 
        $filters['a.ref'] = array(
            'part'      => 'APP-',
            'part_type' => 'beginning'
        );

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

        $sql .= BimpTools::getSqlLimit(100); // POUR TESTS

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
}
