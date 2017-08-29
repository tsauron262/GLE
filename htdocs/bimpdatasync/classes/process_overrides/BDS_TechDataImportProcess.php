<?php

class BDS_PS_SyncProcess extends BDS_SyncProcess
{

    public static $files_dir_name = 'ps';

    public function __construct($processDefinition, $user, $params = null)
    {
        parent::__construct($processDefinition, $user, $params);
    }
}