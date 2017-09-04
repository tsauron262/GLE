<?php

class BDS_TechData_ImportProcess extends BDS_ImportProcess
{

    public static $files_dir_name = 'TechData';
    public $ftp = null;

    public function __construct($processDefinition, $user, $params = null)
    {
        parent::__construct($processDefinition, $user, $params);
    }

    protected function initFtpConnexionTest(&$data, &$errors)
    {
        $data['steps'] = array();
        $data['use_report'] = false;

        if (!$this->parameters_ok) {
            $errors[] = 'Certains paramètres sont invalides. Veuillez vérifier la configuration';
        }

        $html = '';

        $this->ftp = $this->ftpConnect($this->parameters['ftp_server'], $this->parameters['ftp_user'], $this->parameters['ftp_pword'], true, $errors);

        if ($this->ftp && !count($errors)) {
            $html .= '<p class="alert alert-success">La connexion au serveur FTP "' . $this->parameters['ftp_server'] . '" a été effectuée avec succès</p>';

            $files = ftp_nlist($this->ftp, '/');

            if ($files === false) {
                $html .= '<p class="alert alert-danger">Echec de la récupération de la liste des fichiers présents sur le serveur</p>';
            } elseif (self::$debug_mod) {
                echo 'Fichiers présents sur le serveur FTP: <pre>';
                print_r($files);
                echo '</pre>';
            }

//            foreach ($this->parameters as $name => $value) {
//                if (preg_match('/^ftp_file_(.+)$/', $name)) {
//                    
//                }
//            }
        }

        ftp_close($this->ftp);
        $this->ftp = null;

        $data['result_html'] = $html;
    }

    protected function initFtpUpdates(&$data, &$errors)
    {
        $data['steps'] = array();
        $data['use_report'] = true;

        if (!$this->parameters_ok) {
            $errors[] = 'Certains paramètres sont invalides. Veuillez vérifier la configuration du processus';
            return;
        }

        if (!$this->options_ok) {
            $errors[] = 'Options invalides ou manquantes.';
            return;
        }

        $this->ftp = $this->ftpConnect($this->parameters['ftp_server'], $this->parameters['ftp_user'], $this->parameters['ftp_pword'], false, $errors);

        if ($this->ftp && !count($errors)) {
            $dir = $this->filesDir . 'ftp_files/';
            
            if (isset($this->options['update_prices']) && $this->options['update_prices']) {
                if (ftp_get($this->ftp, $dir.$this->parameters['ftp_file_prices'], $this->parameters['ftp_file_prices'], FTP_ASCII)) {
                    $data['steps']['process_update_prices'] = array(
                        'name' => 'process_update_prices',
                        'label' => 'Mise à jour des prix des produits',
                        'on_error' => 'continue'
                    );
                } else {
                    $msg = 'Echec du téléchargement du fichier "'.$this->parameters['ftp_file_prices'].'"';
                    $this->Error($msg);
                    $errors[] = $msg;
                }
            }

            if (isset($this->options['update_infos']) && $this->options['update_infos']) {
                if (ftp_get($this->ftp, $dir.$this->parameters['ftp_file_infos'], $this->parameters['ftp_file_infos'], FTP_BINARY)) {
                    $data['steps']['process_update_infos'] = array(
                        'name' => 'process_update_infos',
                        'label' => 'Mise à jour des informations produits',
                        'on_error' => 'continue'
                    );
                } else {
                    $msg = 'Echec du téléchargement du fichier "'.$this->parameters['ftp_file_infos'].'"';
                    $this->Error($msg);
                    $errors[] = $msg;
                }
            }

            if (isset($this->options['update_stocks']) && $this->options['update_stocks']) {
                if (ftp_get($this->ftp, $dir.$this->parameters['ftp_file_stock'], $this->parameters['ftp_file_stock'], FTP_BINARY)) {
                    $data['steps']['process_update_stocks'] = array(
                        'name' => 'process_update_stocks',
                        'label' => 'Mise à jour des stocks des produits',
                        'on_error' => 'continue'
                    );
                } else {
                    $msg = 'Echec du téléchargement du fichier "'.$this->parameters['ftp_file_stock'].'"';
                    $this->Error($msg);
                    $errors[] = $msg;
                }
            }

            if (isset($this->options['update_taxes']) && $this->options['update_taxes']) {
                if (ftp_get($this->ftp, $dir.$this->parameters['ftp_file_taxes'], $this->parameters['ftp_file_taxes'], FTP_BINARY)) {
                    $data['steps']['process_update_taxes'] = array(
                        'name' => 'process_update_taxes',
                        'label' => 'Mise à jour des taxes sur les produits',
                        'on_error' => 'continue'
                    );
                } else {
                    $msg = 'Echec du téléchargement du fichier "'.$this->parameters['ftp_file_taxes'].'"';
                    $this->Error($msg);
                    $errors[] = $msg;
                }
            }

            ftp_close($this->ftp);
        }

        $this->ftp = null;
    }
}
