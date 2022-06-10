<?php

class BimpCron
{

    public $db;
    public $current_cron_name = '';
    public static $timeout = 3600; // 1h

    public function __construct($db)
    {
        $this->db = $db;

        BimpCore::setMaxExecutionTime(static::$timeout);
        
        register_shutdown_function(array($this, 'onExit'));
    }

    public function onExit()
    {
        $error = error_get_last();

        if (isset($error['type']) && in_array($error['type'], array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR))) {
            if (function_exists('mailSyn2')) {
                $txt = '';
                $txt .= '<strong>ERP:</strong> ' . DOL_URL_ROOT . "\n";

                if (isset($_SERVER['HTTP_REFERER'])) {
                    $txt .= '<strong>Page:</strong> ' . $_SERVER['HTTP_REFERER'] . "\n";
                }

                $txt .= "\n";

                $txt .= 'Le <strong>' . date('d / m / Y') . ' à ' . date('H:i:s') . "\n\n";
                $txt .= 'Classe cron: ' . get_class($this) . "\n";
                if ($this->current_cron_name) {
                    $txt .= 'Tâche cron: ' . $this->current_cron_name . "\n";
                }

                $txt .= $error['file'] . ' - Ligne ' . $error['line'] . "\n\n";
                $txt .= $error['message'];

                echo '<br/><br/>' . str_replace("\n", '<br/>', $txt);

                mailSyn2('ERREUR FATALE CRON - ' . str_replace('/', '', DOL_URL_ROOT), BimpCore::getConf('devs_email'), null, $txt);
            }
        }
    }
}
