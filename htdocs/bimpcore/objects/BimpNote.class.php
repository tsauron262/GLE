<?php

class BimpNote extends BimpObject
{

    const BIMP_NOTE_AUTHOR = 1;
    const BIMP_NOTE_ADMIN = 2;
    const BIMP_NOTE_MEMBERS = 3;
    const BIMP_NOTE_ALL = 4;

    public static $visibilities = array(
        self::BIMP_NOTE_AUTHOR  => array('label' => 'Auteur seulement', 'classes' => array('danger')),
        self::BIMP_NOTE_ADMIN   => array('label' => 'Administrateurs seulement', 'classes' => array('important')),
        self::BIMP_NOTE_MEMBERS => array('label' => 'Membres', 'classes' => array('warning')),
        self::BIMP_NOTE_ALL     => array('label' => 'Membres et client', 'classes' => array('success')),
    );

}
