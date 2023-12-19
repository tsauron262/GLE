<?php



require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/Bimp_CommandeFourn.class.php';

class Bimp_CommandeFourn_LdlcFiliale extends Bimp_CommandeFourn
{
    public function verifMajLdlc(&$success){
        $errors = array();
        
        $tabConvertionStatut = array("processing" => 95, "shipped" => 100, "billing" => 105, "canceled" => -100, "deleted" => -105);


//            error_reporting(E_ALL);
//            ini_set('display_errors', 1);
        $url = BimpCore::getConf('exports_ldlc_ftp_serv');
        $login = BimpCore::getConf('exports_ldlc_ftp_user');
        $mdp = BimpCore::getConf('exports_ldlc_ftp_mdp');
        $folder = "/".BimpCore::getConf('exports_ldlc_ftp_dir')."/tracing/"; 

//            $url = "exportftp.techdata.fr";
//            $login = "bimp";
//            $mdp = "=bo#lys$2003";
//            $folder = "/";
        if ($conn = ftp_connect($url)) {
            if (ftp_login($conn, $login, $mdp)) {
                if (defined('FTP_SORTANT_MODE_PASSIF')) {
                    ftp_pasv($conn, FTP_SORTANT_MODE_PASSIF);
                } else {
                    ftp_pasv($conn, 0);
                }

                // Change the dir
//                    if(ftp_chdir($conn, $folder)){
                $tab = ftp_nlist($conn, $folder);

                foreach ($tab as $fileEx) {
                    if (stripos($fileEx, '.xml') !== false) {
                        $errorLn = array();
                        $dir = PATH_TMP . "/bimpcore/";
                        $file = "tmpftp.xml";
                        if (ftp_get($conn, $dir . $file, $fileEx, FTP_BINARY)) {
                            if (!stripos($fileEx, ".xml"))
                                continue;
                            $data = simplexml_load_string(file_get_contents($dir . $file));

                            if (isset($data->attributes()['date'])) {
                                $date = (string) $data->attributes()['date'];
                                $type = (string) $data->attributes()['type'];
                                $ref = (string) $data->Stream->Order->attributes()['external_identifier'];

                                $commFourn = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn');
                                $commFourn->useNoTransactionsDb();
                                if ($commFourn->find(['ref' => $ref])) {
                                    $statusCode = (isset($data->attributes()['statuscode'])) ? -$data->attributes()['statuscode'] : 0;
                                    if ($statusCode < 0 && isset(static::$edi_status[(int) $statusCode]))
                                        $errorLn[] = 'commande en erreur ' . $ref . ' Erreur : ' . static::$edi_status[(int) $statusCode]['label'];
                                    elseif ($type == "error")
                                        $errorLn[] = 'commande en erreur ' . $ref . ' Erreur Inconnue !!!!!';

                                    if ($type == "acknowledgment")
                                        $statusCode = 91;

                                    $statusCode2 = $data->Stream->Order->attributes()['status'];

                                    if ($statusCode2 != '') {
                                        if (isset($tabConvertionStatut[(string) $statusCode2]))
                                            $statusCode = $tabConvertionStatut[(string) $statusCode2];
                                        else
                                            $errorLn[] = "Statut LDLC inconnue |" . $statusCode2 . "|";
                                    }

                                    $prods = (array) $data->Stream->Order->Products;
                                    $total = 0;

                                    if (!is_array($prods['Item']))
                                        $prods['Item'] = array($prods['Item']);
                                    foreach ($prods['Item'] as $prod) {
                                        $total += (float) $prod->attributes()['quantity'] * (float) $prod->attributes()['unitPrice'];
                                    }
                                    $diference = abs($commFourn->getData('total_ht') - $total);
                                    if ($diference > 0.08) {
                                        $statusCode = -50;
                                    }


                                    if (isset($data->Stream->Order->attributes()['identifier']) && $data->Stream->Order->attributes()['identifier'] != '') {
                                        if (stripos($commFourn->getData('ref_supplier'), (string) $data->Stream->Order->attributes()['identifier']) === false)
                                            $commFourn->updateField('ref_supplier', ($commFourn->getData('ref_supplier') == "" ? '' : $commFourn->getData('ref_supplier') . " ") . $data->Stream->Order->attributes()['identifier']);
                                    }

                                    if (isset($data->Stream->Order->attributes()['invoice']) && $data->Stream->Order->attributes()['invoice'] != '') {
                                        if (stripos($commFourn->getData('ref_supplier'), (string) $data->Stream->Order->attributes()['invoice']) === false)
                                            $errorLn = BimpTools::merge_array($errorLn, $commFourn->updateField('ref_supplier', ($commFourn->getData('ref_supplier') == "" ? '' : $commFourn->getData('ref_supplier') . " ") . $data->Stream->Order->attributes()['invoice']));
                                        $errorLn = BimpTools::merge_array($errorLn, $commFourn->traitePdfFactureFtp($conn, $data->Stream->Order->attributes()['invoice']));
                                    }


                                    $colis = array();
                                    if (isset($data->Stream->Order->Parcels)) {
                                        $parcellesBrut = (array) $data->Stream->Order->Parcels;
                                        if (!is_array($parcellesBrut['Parcel']))
                                            $parcellesBrut['Parcel'] = array($parcellesBrut['Parcel']);
                                        $notes = $commFourn->getNotes();
                                        foreach ($parcellesBrut['Parcel'] as $parcel) {
                                            $text = 'Colis : ' . (string) $parcel->attributes()['code'] . ' de ' . (string) $parcel->attributes()['service'];
                                            if (isset($parcel->attributes()['TrackingUrl']) && $parcel->attributes()['TrackingUrl'] != '')
                                                $text = '<a target="_blank" href="' . (string) $parcel->attributes()['TrackingUrl'] . '">' . $text . "</a>";
                                            $noteOK = false;
                                            foreach ($notes as $note) {
                                                if ($noteOK)
                                                    continue;
                                                if (stripos($note->getData('content'), $text) !== false)
                                                    $noteOK = true;
                                            }
                                            if (!$noteOK)
                                                $commFourn->addNote($text, null, 1);
                                        }
                                    }


                                    if ($commFourn->getData('edi_status') != $statusCode) {
                                        $commFourn->updateField('edi_status', (int) $statusCode);
                                        if (isset(static::$edi_status[(int) $statusCode]))
                                            $commFourn->addObjectLog('Changement de statut EDI : ' . static::$edi_status[(int) $statusCode]['label']);
                                        else
                                            BimpCore::addlog('Status commande LDLC inconue. Code : ' . $statusCode);
                                    }



                                    if (count($colis))
                                        $success .= "<br/>" . count($colis) . " Colis envoyées ";

                                    $success .= "<br/>Comm : " . $ref . "<br/>Status " . static::$edi_status[(int) $statusCode]['label'];
                                } else {
                                    $errorLn[] = 'pas de comm ' . $ref;
                                }
                            } else {
                                $errorLn[] = 'Structure XML non reconnue';
                            }
                            if (!count($errorLn)) {
                                ftp_rename($conn, $fileEx, str_replace("tracing/", "tracing/importedAuto/", $fileEx));
                            } else{
                                $commFourn->addObjectLog('Erreur EDI : ' . print_r($errorLn,1));
                                mailSyn2('Probléme commande LDLC', BimpCore::getConf('mail_achat', '').', debugerp@bimp.fr', null, 'Commande '.$commFourn->getLink().'<br/>'.print_r($errorLn,1));
                                ftp_rename($conn, $fileEx, str_replace("tracing/", "tracing/quarentaineAuto/", $fileEx));
                            }
                        }
                        $errors = BimpTools::merge_array($errors, $errorLn);
                    }
                }
            }
            else
                $errors[] = 'Login impossible';

            ftp_close($conn);
        }
        else
            $errors[] = 'Connexion impossible';;
        return $errors;
    }
    
    

    public function traitePdfFactureFtp($conn, $facNumber)
    {
        $folder = "/".BimpCore::getConf('exports_ldlc_ftp_dir')."/invoices";
        $tab = ftp_nlist($conn, $folder);
        $errors = array();

        $list = $this->getFilesArray();
        $ok = false;
        foreach ($list as $nom) {
            if (stripos($nom, (string) $facNumber))
                $ok = true;
        }
        if (!$ok) {
            $newName = (string) $facNumber . ".pdf";
            foreach ($tab as $fileEx) {
                if (!$ok && stripos($fileEx, (string) $facNumber) !== false) {
                    ftp_get($conn, $this->getFilesDir() . "/" . $newName, $fileEx, FTP_BINARY);
                    $this->addObjectLog('Le fichier PDF fournisseur ' . $newName . ' à été ajouté.');
                    $ok = true;
                    if ($this->getData('entrepot') == 164) {
                        mailSyn2("Nouvelle facture LDLC", BimpCore::getConf('mail_achat'), null, "Bonjour la facture " . $facNumber . " de la commande : " . $this->getLink() . " en livraison direct a été téléchargé");
                    }
                }
            }
            if (!$ok) {
                $errors[] = "Fichier " . $newName . ' introuvable';
//                mailSyn2('fichier pdf introuvable', 'dev@bimp.fr', null, "Fichier " . $newName . ' introuvable');
            }
        }
        return $errors;
    }


    public function actionVerifMajLdlc($data, &$success)
    {
        $success .= '<br/>Commandes MAJ';
        $errors = $this->verifMajLdlc($success);


        return array(
            'errors'           => $errors,
            'warnings'         => array(),
            'success_callback' => ''
        );
    }
    
    
    public function actionMakeOrderEdi($data, &$success)
    {
        $success = "Commande OK";

        $errors = array();

//        $errors = BimpTools::merge_array($errors, $this->verifMajLdlc($data, $success));
        if ($this->getData("fk_soc") != $this->idLdlc)
            $errors[] = "Cette fonction n'est valable que pour LDLC";


        if (!count($errors)) {
            require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDS_ArrayToXml.php';
            $arrayToXml = new BDS_ArrayToXml();

            $products = array();

            $lines = $this->getLines('not_text');
            foreach ($lines as $line) {
                //            $line = new Bimp_CommandeFournLine();
                $prod = $line->getChildObject('product');
                if (is_object($prod) && $prod->isLoaded()) {
                    $diference = 999;
                    $ref = $prod->findRefFournForPaHtPlusProche($line->getUnitPriceHTWithRemises(), $this->idLdlc, $diference);

                    $ref = str_replace(' ', '', $ref);

                    if (strpos($ref, "AR") !== 0 || strlen($ref) > 14)
                        $errors[] = "La référence '" . $ref . "' ne semble pas être une ref LDLC correct  pour le produit " . $prod->getLink();
                    elseif ($diference > 0.08)
                        $errors[] = "Prix de l'article " . $prod->getLink() . " différent du prix LDLC. Différence de " . price($diference) . " € vous ne pourrez pas passer la commande par cette méthode.";
                    else
                        $products[] = array("tag" => "Item", "attrs" => array("id" => $ref, "quantity" => $line->qty, "unitPrice" => round($line->getUnitPriceHTWithRemises(), 2), "vatIncluded" => "false"));
                } else
                    $errors[] = "Pas de produit pour la ligne " . $line->id;
            }


            global $mysoc;
            $adresseFact = array("tag"      => "Address", "attrs"    => array("type" => "billing"),
                "children" => array(
                    "ContactName"  => $mysoc->name,
                    "AddressLine1" => $mysoc->name,
                    "AddressLine2" => $arrayToXml->xmlentities($mysoc->address),
                    "AddressLine3" => "",
//                    "AddressLine3" => "",
                    "City"         => $mysoc->town,
                    "ZipCode"      => $mysoc->zip,
                    "CountryCode"  => "FR",
                )
            );
            $dataLiv = $this->getAdresseLivraison($errors);
//            echo "<pre>";print_r($dataLiv);
//            $name = ($dataLiv['name'] != '' ? $dataLiv['name'] . ' ' : '') . $dataLiv['contact'];
            $name = str_replace('&', 'et', $dataLiv['name']);
            $contact = $dataLiv['contact'];
//            if ($name == "")
//                $name = "BIMP";
            $adresseLiv = array("tag"      => "Address", "attrs"    => array("type" => "shipping"),
                "children" => array(
                    "ContactName"  => substr($name, 0, 49),
                    "AddressLine1" => substr($contact, 0, 49),
                    "AddressLine2" => $arrayToXml->xmlentities($dataLiv['adress']),
                    "AddressLine3" => $arrayToXml->xmlentities($dataLiv['adress2']),
//                    "AddressLine3" => $arrayToXml->xmlentities($dataLiv['adress3']),
                    "City"         => $arrayToXml->xmlentities($dataLiv['town']),
                    "ZipCode"      => $dataLiv['zip'],
                    "CountryCode"  => ($dataLiv['country'] != "FR" && $dataLiv['country'] != "") ? strtoupper(substr($dataLiv['country'], 0, 2)) : "FR",
                )
            );

            $portHt = $portTtc = 0;
            $shipping_mode = "CH6";
            if ($this->getData('methode_liv') == 1)
                $shipping_mode = 'PNS1';
            if ($this->getData('methode_liv') == 2)
                $shipping_mode = 'PNSSI';
//            if (in_array($this->getData('delivery_type'), array(Bimp_CommandeFourn::DELIV_ENTREPOT, Bimp_CommandeFourn::DELIV_SIEGE)))
//                $shipping_mode = "PNS6";
            $tab = array(
                array("tag"      => "Stream", "attrs"    => array("type" => "order", 'version' => "1.0"),
                    "children" => array(
                        array("tag"      => "Order", "attrs"    => array("date" => date("Y-m-d H:i:s"), 'reference' => $this->getData('ref'), "external_identifier" => $this->getData('ref'), "currency" => "EUR", "source" => $this->code_representant, "shipping_vat_on" => $portHt, "shipping_vat_off" => $portTtc, "shipping_mode" => $shipping_mode),
                            "children" => array(
                                array("tag"      => "Customer", "attrs"    => array("identifiedby" => "code", 'linked_entity_code' => "PRO"),
                                    "children" => array(
                                        "Owner"          => "FILI",
                                        "CustomerNumber" => $this->CustomerNumber,
                                        "FirstName"      => "",
                                        "LastName"       => "",
                                        "PhoneNumber"    => "0812211211",
                                        "Email"          => "achat@bimp.fr",
                                        $adresseLiv,
                                        $adresseFact,
                                    )
                                ),
                                array("tag"      => "Products",
                                    "children" => $products
                                ),
                                $adresseLiv,
                                $adresseFact,
                            )
                        )
                    )
                )
            );
        }

        if (!count($errors)) {
            $arrayToXml->writeNodes($tab);

            $url = BimpCore::getConf('exports_ldlc_ftp_serv');
            $login = BimpCore::getConf('exports_ldlc_ftp_user');
            $mdp = BimpCore::getConf('exports_ldlc_ftp_mdp');

            if ($conn = ftp_connect($url)) {
                if (ftp_login($conn, $login, $mdp)) {
                    $localFile = PATH_TMP . '/bimpcore/tmpUpload.xml';
                    if (!file_put_contents($localFile, $arrayToXml->getXml()))
                        $errors[] = 'Probléme de génération du fichier';
                    $dom = new DOMDocument;
                    $dom->Load($localFile);
                    libxml_use_internal_errors(true);
                    if (!$dom->schemaValidate(DOL_DOCUMENT_ROOT . '/bimpcommercial/ldlc.orders.valid.xsd')) {
                        $errors[] = 'Ce document est invalide contactez l\'équipe dév : <a href="'.DOL_URL_ROOT.'/document.php?modulepart=bimpcore&file=tmpUpload.xml">Fichier</a>';
                        BimpCore::addlog('Probléme CML LDLC', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', $this, array(
                            'LIBXML Errors' => libxml_get_errors()
                        ));
                    }

                    if (!count($errors)) {
                        if (defined('FTP_SORTANT_MODE_PASSIF')) {
                            ftp_pasv($conn, FTP_SORTANT_MODE_PASSIF);
                        } else {
                            ftp_pasv($conn, 0);
                        }
                        if (!ftp_put($conn, "/".BimpCore::getConf('exports_ldlc_ftp_dir')."/orders/" . $this->getData('ref') . '.xml', $localFile, FTP_BINARY))
                            $errors[] = 'Probléme d\'upload du fichier';
                        else {
                            //                        global $user;
                            //                        mailSyn2("Commande BIMP", "a.schlick@ldlc.pro, tommy@bimp.fr", $user->email, "Bonjour, la commande " . $this->getData('ref') . ' de chez bimp vient d\'être soumise, vous pourrez la valider dans quelques minutes ?');
                            $this->addObjectLog('Commande passée en EDI');
                        }
                    }
                } else
                    $errors[] = 'Probléme de login LDLC';
            } else
                $errors[] = 'Probléme de connexion LDLC';

//            if(!file_put_contents($remote_file, $arrayToXml->getXml()))
//                    $errors[] = 'Probléme de génération du fichier';
//            die("<textarea>".$arrayToXml->getXml().'</textarea>fin');
        }


        if (!count($errors)) {
            $data['date_commande'] = date('Y-m-d');
            $data['fk_input_method'] = 7;
            return $this->actionMakeOrder($data, $success);
        } else
            return array(
                'errors'           => $errors,
                'warnings'         => array(),
                'success_callback' => ''
            );
    }

    
    
    public function getActionsButtons()
    {
        $buttons = parent::getActionsButtons();

        if ($this->isLoaded()) {
            if ($this->getData('fk_statut') == 3) {
                if ($this->getData('fk_soc') == $this->idLdlc && $this->canSetAction('makeOrder')) {
                    $onclick = $this->getJsActionOnclick('verifMajLdlc', array(), array());
                    $buttons[] = array(
                        'label'   => 'MAJ EDI',
                        'icon'    => 'fas_arrow-circle-right',
                        'onclick' => $onclick,
                    );
                }
            }
            
            // Commander
            if (($this->getData('edi_status') < 0 || $this->isActionAllowed('makeOrder')) && $this->canSetAction('makeOrder')) {
                if ($this->getData('fk_soc') == $this->idLdlc) {
                    $onclick = $this->getJsActionOnclick('makeOrderEdi', array(), array(
                    ));
                    $buttons[] = array(
                        'label'   => 'Commander en EDI',
                        'icon'    => 'fas_arrow-circle-right',
                        'onclick' => $onclick,
                    );
                }
            }
        }

        return $buttons;
    }
}