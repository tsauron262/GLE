<?php

class BDS_IngramImportProcess extends BDS_ImportProcess {
    public static $id = 2;
    public static $name = 'IngramImport';
    public static $title = 'Imports via le Webservice Ingram';
    public static $active = 1;
    public static $files_dir_name = 'ingram';
    
    public static function getClassName()
    {
        return 'BDS_IngramImportProcess';
    }
}