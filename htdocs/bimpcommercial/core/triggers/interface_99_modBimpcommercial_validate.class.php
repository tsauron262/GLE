<?php

/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011      Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013-2014 Marcos García        <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

include_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';
include_once DOL_DOCUMENT_ROOT . '/bimpvalidateorder/class/bimpvalidateorder.class.php';

/**
 *  Class of triggers for validateorder module
 */
class Interfacevalidate extends DolibarrTriggers
{

    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        global $conf, $user;

        if ($action == 'PROPAL_VALIDATE') {
            $bimp_object = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $object->id);

            if (!(int) $bimp_object->lines_locked) {
                $prev_statut = $bimp_object->dol_object->statut;
                $bimp_object->dol_object->statut = 0;
                $bimp_object->checkLines();
                $bimp_object->dol_object->statut = $prev_statut;
            }

            return 1;
        }

        if ($action == 'ORDER_VALIDATE') {
            $bimp_object = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $object->id);

            if (BimpObject::objectLoaded($bimp_object)) {
                // Véfication de la validité des lignes: 
                $errors = array();
                if (!$bimp_object->areLinesValid($errors)) {
                    $object->errors = array_merge($object->errors, $errors);
                    return -1;
                }
            }

            return 1;
        }

        if ($action == 'BILL_VALIDATE') {
            $bimp_object = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $object->id);
            if (BimpObject::objectLoaded($bimp_object)) {
                // Véfication de la validité des lignes: 
                $errors = array();
                if (!$bimp_object->areLinesValid($errors)) {
                    $object->errors = array_merge($object->errors, $errors);
                    return -1;
                }

                $bimp_object->onValidate();
            }

            return 1;
        }

        if ($action == 'BILL_SUPPLIER_CREATE') {
            $bimp_object = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', $object->id);
            if (BimpObject::objectLoaded($bimp_object)) {
                $bimp_object->onCreate();
            }

            return 1;
        }

        if ($action == 'BILL_DELETE') {
            $bimp_object = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $object->id);
            if (BimpObject::objectLoaded($bimp_object)) {
                $bimp_object->onDelete();
            }

            return 1;
        }

        return 0;
    }
}
