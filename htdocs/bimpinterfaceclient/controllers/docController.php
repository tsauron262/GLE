<?php

class docController extends BimpPublicController
{

    public static $user_client_required = true;

    public function display()
    {
        $doc = BimpTools::getValue('doc', '');
        $id = BimpTools::getValue('docid', 0);
        $ref = BimpTools::getValue('docref', '');

        if (!$doc) {
            $this->errors[] = 'Type de document absent';
        }

        if (!$id) {
            $this->errors[] = 'Identifiant du document absent';
        }

        if (!$ref) {
            $this->errors[] = 'Reférrence du document absente';
        }

        global $userClient;

        if (!BimpObject::objectLoaded($userClient)) {
            $this->errors[] = 'Vous n\'avez pas la permission d\'accéder à ce document';
        }

        if (!count($this->errors)) {
            $module_part = '';
            $file_name = '';

            switch ($doc) {
                case 'bl':
                case 'bl_signed':
                    $shipment = BimpCache::getBimpObjectInstance('bimplogistique', 'BL_CommandeShipment', $id);

                    if (!BimpObject::objectLoaded($shipment)) {
                        $this->errors[] = 'Identifiant du document invalide';
                    } elseif ($shipment->getData('num_livraison') != $ref) {
                        $this->errors[] = 'Référence du document invalide';
                    } elseif (!$shipment->can('view')) {
                        $this->errors[] = 'Vous n\'avez pas la permission de voir ce document';
                    }

                    $dir = $shipment->getFilesDir();
                    $file_name = $shipment->getSignatureDocFileName('bl', ($doc == 'bl_signed'));

                    if (!file_exists($dir . $file_name)) {
                        $this->errors[] = 'Ce document n\'existe pas';
                    } else {
                        $module_part = 'bimpcore';
                        $file_name = 'bimplogistique/BL_CommandeShipment/' . $shipment->id . '/' . $file_name;
                    }
                    break;

                case 'devis':
                case 'devis_signed':
                    $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $id);
                    $propal_ref = $propal->getRef();

                    if (!BimpObject::objectLoaded($propal)) {
                        $this->errors[] = 'Identifiant du document invalide';
                    } elseif ($propal_ref != $ref) {
                        $this->errors[] = 'Référence du document invalide';
                    } elseif (!$propal->can('view')) {
                        $this->errors[] = 'Vous n\'avez pas la permission de voir ce document';
                    }

                    $dir = $propal->getFilesDir();
                    $file_name = dol_sanitizeFileName($propal_ref) . ($doc == 'devis_signed' ? '_signe' : '') . '.pdf';

                    if (!file_exists($dir . $file_name)) {
                        $this->errors[] = 'Ce document n\'existe pas (' . $dir . $file_name . ')';
                    } else {
                        $module_part = 'propal';
                        $file_name = dol_sanitizeFileName($propal_ref) . '/' . $file_name;
                    }
                    break;

                case 'sav_pc':
                case 'sav_pc_signed':
                    $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id);
                    $sav_ref = $sav->getRef();

                    if (!BimpObject::objectLoaded($sav)) {
                        $this->errors[] = 'Identifiant du document invalide';
                    } elseif ($sav_ref != $ref) {
                        $this->errors[] = 'Référence du document invalide';
                    } elseif (!$sav->can('view')) {
                        $this->errors[] = 'Vous n\'avez pas la permission de voir ce document';
                    }

                    $dir = $sav->getFilesDir();
                    $file_name = $sav->getSignatureDocFileName('sav_pc', ($doc == 'sav_pc_signed' ? true : false));

                    if (!file_exists($dir . $file_name)) {
                        $this->errors[] = 'Ce document n\'existe pas (' . $dir . $file_name . ')';
                    } else {
                        $module_part = 'bimpcore';
                        $file_name = 'sav/' . $sav->id . '/' . $file_name;
                    }
                    break;
            }

            if (!count($this->errors)) {
                if ($module_part && $file_name) {
                    $_GET = array(
                        'modulepart' => $module_part,
                        'file'       => $file_name
                    );

                    if ($this->displayFile($module_part, $file_name)) {
                        exit;
                    }
                } else {
                    $this->errors[] = 'Ce document n\'existe pas';
                }
            }
        }

        parent::display();
    }

    protected function displayFile($modulepart, $original_file)
    {
        // Code repris depuis document.php (Epuré: les contrôles de sécurité ont déjà été fait)
        global $userClient, $conf;

        if (!BimpObject::objectLoaded($userClient)) {
            $this->errors[] = 'Aucun utilisateur connecté';
            return false;
        }

        if (empty($modulepart)) {
            $this->errors[] = 'Nom du module absent';
            return false;
        }

        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        $encoding = '';

        // Define attachment (attachment=true to force choice popup 'open'/'save as')
        $attachment = true;
        if (preg_match('/\.(html|htm)$/i', $original_file))
            $attachment = false;
        if (isset($_GET["attachment"]))
            $attachment = GETPOST("attachment", 'alpha') ? true : false;
        if (!empty($conf->global->MAIN_DISABLE_FORCE_SAVEAS))
            $attachment = false;
        if (preg_match('/\.(zip|exe)$/i', $original_file))
            $attachment = true;

        // Define mime type
        $type = dol_mimetype($original_file);

        // Security: Delete string ../ into $original_file
        $original_file = str_replace("../", "/", $original_file);

        // Find the subdirectory name as the reference
//        $refname = basename(dirname($original_file) . "/");

        $check_access = dol_check_secure_access_document($modulepart, $original_file, 0);
        $fullpath_original_file = $check_access['original_file'];               // $fullpath_original_file is now a full path name
        // On interdit les remontees de repertoire ainsi que les pipe dans les noms de fichiers.
        if (preg_match('/\.\./', $fullpath_original_file) || preg_match('/[<>|]/', $fullpath_original_file)) {
            $this->errors[] = 'Le nom du fichier contient des caractères interdits';
        }

        clearstatcache();

        if (DOL_DATA_ROOT != PATH_TMP) {
            $inTmpPath = str_replace(DOL_DATA_ROOT, PATH_TMP, $fullpath_original_file);
            if (file_exists($inTmpPath)) {
                $fullpath_original_file = $inTmpPath;
            }
        }

        $filename = basename($fullpath_original_file);

        // Output file on browser
        dol_syslog("document.php download $fullpath_original_file filename=$filename content-type=$type");
        $fullpath_original_file_osencoded = dol_osencode($fullpath_original_file); // New file name encoded in OS encoding charset
        // This test if file exists should be useless. We keep it to find bug more easily
        if (!file_exists($fullpath_original_file_osencoded)) {
            $this->errors[] = 'Le fichier n\'existe pas';
            return false;
        }

        // Permissions are ok and file found, so we return it
        top_httphead($type);
        header('Content-Description: File Transfer');

        if ($encoding)
            header('Content-Encoding: ' . $encoding);

        // Add MIME Content-Disposition from RFC 2183 (inline=automatically displayed, attachment=need user action to open)
        if ($attachment)
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        else
            header('Content-Disposition: inline; filename="' . $filename . '"');

        header('Content-Length: ' . dol_filesize($fullpath_original_file));

        // Ajout directives pour resoudre bug IE
        header('Cache-Control: Public, must-revalidate');
        header('Pragma: public');

        readfile($fullpath_original_file_osencoded);

        global $db;
        if (is_object($db))
            $db->close();

        return true;
    }
}
