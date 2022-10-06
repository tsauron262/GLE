<?php

// Entité: bimp

require_once DOL_DOCUMENT_ROOT . '/bimpapi/classes/apis/ErpAPI.php';

ErpApi::$requests['addDemandeFinancement'] = array(
    'label' => 'Ajouter une demande de financement'
);
ErpApi::$requests['getDemandeFinancementInfos'] = array(
    'label' => 'Obtenir les infos d\'une demande de financement'
);
ErpApi::$requests['setDemandeFinancementDocSigned'] = array(
    'label' => 'Document d\'une demande de financement signé'
);

class ErpAPI_ExtEntity extends ErpAPI
{

    public function __construct($api_idx = 0, $id_user_account = 0, $debug_mode = false)
    {
        static::$requests['addDemandeFinancement'] = array(
            'label' => 'Ajouter une demande de fincancement'
        );

        parent::__construct($api_idx, $id_user_account, $debug_mode);
    }

    public function addDemandeFinancement($propale, $id_client, $id_contact, $extra_data = array(), &$errors = array(), &$warnings = array())
    {
        if (!is_a($propale, 'Bimp_Propal') || !BimpObject::objectLoaded($propale)) {
            $errors[] = 'Devis invalide';
        } else {
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
            if (!BimpObject::objectLoaded($client)) {
                $errors[] = 'Le client #' . $id_client . ' n\'existe pas';
            }

            $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ContactClient', $id_contact);
            if (!BimpObject::objectLoaded($contact)) {
                $errors[] = 'Le contact client #' . $id_contact . ' n\'existe pas';
            }
        }

        if (!count($errors)) {
            $lines = array();
            foreach ($propale->getLines('all') as $line) {
                if ((int) $line->id_remise_except) {
                    continue; // On exclut les accomptes. 
                }
                switch ($line->getData('type')) {
                    case ObjectLine::LINE_PRODUCT:
                        $product = $line->getProduct();
                        if (BimpObject::objectLoaded($product)) {
                            $serialisable = (BimpObject::objectLoaded($product) ? (int) $product->isSerialisable() : 0);
                            $product_type = 1; // Produit
                            if ($product->field_exists('type2') && (int) $product->getData('type2') === 5) {
                                $product_type = 3; // Logiciel
                            } elseif ($product->isTypeService()) {
                                $product_type = 2; // Service
                            }
                            $lines[] = array(
                                'id'           => $line->id,
                                'type'         => 2,
                                'ref'          => $product->getRef(),
                                'label'        => $product->getName(),
                                'product_type' => $product_type,
                                'qty'          => $line->getFullQty(),
                                'pu_ht'        => $line->pu_ht,
                                'tva_tx'       => $line->tva_tx,
                                'pa_ht'        => $line->pa_ht,
                                'remise'       => $line->remise,
                                'serialisable' => $serialisable,
                                'serials'      => ($serialisable ? implode(',', $line->getSerials()) : '')
                            );
                        }
                        break;

                    case ObjectLine::LINE_FREE:
                        $lines[] = array(
                            'id'           => $line->id,
                            'type'         => 2,
                            'label'        => $line->description,
                            'qty'          => $line->getFullQty(),
                            'pu_ht'        => $line->pu_ht,
                            'tva_tx'       => $line->tva_tx,
                            'pa_ht'        => $line->pa_ht,
                            'remise'       => $line->remise,
                            'serialisable' => 0
                        );
                        break;

                    case ObjectLine::LINE_TEXT:
                        $lines[] = array(
                            'id'          => $line->id,
                            'type'        => 3,
                            'description' => $line->desc,
                        );
                        break;
                }
            }

            $commercial = $propale->getCommercial();

            $params = array(
                'fields' => array(
                    'origine'       => 'bimp',
                    'id_propale'    => $propale->id,
                    'propale'       => json_encode(array(
                        'id'   => $propale->id,
                        'data' => array(
                            'ref'       => array('label' => 'Ref. BIMP', 'value' => $propale->getRef()),
                            'libelle'   => array('label' => 'Libellé', 'value' => $propale->getData('libelle')),
                            'total_ht'  => array('label' => 'Total HT', 'value' => $propale->getTotalHt()),
                            'total_ttc' => array('label' => 'Total TTC', 'value' => $propale->getTotalTtc())
                        )
                    )),
                    'propale_lines' => json_encode($lines),
                    'client'        => json_encode(array(
                        'id'   => $client->id,
                        'data' => array(
                            'nom'        => array('label' => 'Nom', 'value' => $client->getData('nom')),
                            'ref'        => array('label' => 'Ref. BIMP', 'value' => $client->getData('code_client')),
                            'alias'      => array('label' => 'Alias', 'value' => $client->getData('name_alias')),
                            'is_company' => array('label' => 'Entreprise', 'value' => (int) $client->isCompany()),
                            'type_ent'   => array('label' => 'Type', 'value' => $client->displayData('fk_typent', 'default', false, true)),
                            'address'    => array('label' => 'Adresse', 'value' => $client->getData('address')),
                            'zip'        => array('label' => 'Code postal', 'value' => $client->getData('zip')),
                            'town'       => array('label' => 'Ville', 'value' => $client->getData('town')),
                            'pays'       => array('label' => 'Pays', 'value' => $client->displayData('fk_pays', 'default', false, true)),
                            'email'      => array('label' => 'E-mail', 'value' => $client->getData('email')),
                            'phone'      => array('label' => 'SIREN', 'value' => $client->getData('phone')),
                            'siren'      => array('label' => 'SIREN', 'value' => $client->getData('siren')),
                            'siret'      => array('label' => 'SIRET', 'value' => $client->getData('siret')),
                            'tva_assuj'  => array('label' => 'Assujetti à la TVA', 'value' => $client->displayData('tva_assuj', 'default', false, true)),
                            'tva_intra'  => array('label' => 'N° TVA', 'value' => $client->getData('tva_intra'))
                        )
                    )),
                    'contact'       => json_encode(array(
                        'id'   => $contact->id,
                        'data' => array(
                            'civility'     => array('label' => 'Civilité', 'value' => $contact->displayData('civility', 'default', false, true)),
                            'lastname'     => array('label' => 'Nom', 'value' => $contact->getData('lastname')),
                            'firstname'    => array('label' => 'Prénom', 'value' => $contact->getData('firstname')),
                            'address'      => array('label' => 'Adresse', 'value' => $contact->getData('address')),
                            'zip'          => array('label' => 'Code postal', 'value' => $contact->getData('zip')),
                            'town'         => array('label' => 'Ville', 'value' => $contact->getData('town')),
                            'pays'         => array('label' => 'Pays', 'value' => $contact->displayData('pays', 'default', false, true)),
                            'email'        => array('label' => 'E-mail', 'value' => $contact->getData('email')),
                            'phone_pro'    => array('label' => 'Tel. pro', 'value' => $contact->getData('phone')),
                            'phone_perso'  => array('label' => 'Tel. perso', 'value' => $contact->getData('phone_perso')),
                            'phone_mobile' => array('label' => 'Tel. mobile', 'value' => $contact->getData('phone_mobile'))
                        )
                    )),
                    'extra_data'    => json_encode($extra_data)
                )
            );

            if (BimpObject::objectLoaded($commercial)) {
                $params['fields']['commercial'] = json_encode(array(
                    'id'   => $commercial->id,
                    'data' => array(
                        'name'      => array('label' => 'Nom', 'value' => $commercial->getName()),
                        'email'     => array('label' => 'E-mail', 'value' => $commercial->getData('email')),
                        'phone_pro' => array('label' => 'Tel. pro', 'value' => $commercial->getData('office_phone'))
                    )
                ));
            }

            if (!count($errors)) {
                $response = $this->execCurl('addDemandeFinancement', $params, $errors);

                if (isset($response['warnings'])) {
                    $warnings = BimpTools::merge_array($warnings, $response['warnings']);
                    unset($response['warnings']);
                }

                if (!count($errors)) {
                    return $response;
                }
            }
        }

        return null;
    }

    public function getDemandeFinancementInfos($id_df, &$errors = array(), &$warnings = array())
    {
        $response = $this->execCurl('getDemandeFinancementInfos', array(
            'fields' => array(
                'id_demande' => $id_df
            )
                ), $errors);
        if (isset($response['warnings'])) {
            $warnings = BimpTools::merge_array($warnings, $response['warnings']);
            unset($response['warnings']);
        }

        if (!count($errors)) {
            return $response;
        }

        return null;
    }

    public function setDemandeFinancementDocSigned($id_df, $doc_type, $doc_content, &$errors = array(), &$warnings = array())
    {
        $response = $this->execCurl('setDemandeFinancementDocSigned', array(
            'fields' => array(
                'id_demande'  => $id_df,
                'doc_type'    => $doc_type,
                'doc_content' => base64_encode($doc_content)
            )
                ), $errors);
        if (isset($response['warnings'])) {
            $warnings = BimpTools::merge_array($warnings, $response['warnings']);
            unset($response['warnings']);
        }

        if (!count($errors)) {
            return $response;
        }

        return null;
    }
}
