<?php


class bimptocegidLib{
    static function getDirOutput(){
        $dir = PATH_TMP  .'/exportCegid/';
        global $conf;
        if (!empty($conf->multicompany->enabled))
            $dir .= $conf->entity.'/';
        return $dir;
    }
}
