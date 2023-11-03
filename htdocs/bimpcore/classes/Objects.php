<?php

class ObjectsDef
{

    public static $keywords = array(
        // BimpApple: 
        'Retour groupé Apple'  => array('icon' => 'fas_shipping-fast', 'def' => 'BO/bimpapple/AppleShipment'),
        'Réparation SAV'       => array('icon' => 'fas_tools', 'def' => 'BO/bimpapple/GSX_Repair'),
        // BimpComercial: 
        'Devis'                => array('icon' => 'fas_file-invoice', 'def' => 'BO/bimpcommercial/Bimp_Propal'),
        'Commande'             => array('icon' => 'fas_cart-arrow-down', 'def' => 'BO/bimpcommercial/Bimp_Commande'),
        'Facture'              => array('icon' => 'fas_file-invoice-dollar', 'def' => 'BO/bimpcommercial/Bimp_Facture'),
        'Commande fournisseur' => array('icon' => 'fas_cart-arrow-down', 'def' => 'BO/bimpcommercial/Bimp_CommandeFourn'),
        'Facture fournisseur'  => array('icon' => 'fas_file-invoice-dollar', 'def' => 'BO/bimpcommercial/Bimp_FactureFourn'),
        'Paiement'             => array('icon' => 'fas_euro-sign', 'def' => 'BO/bimpcommercial/Bimp_Paiement'),
        // BimpContract: 
        'Contrat'              => array('icon' => 'fas_file-signature', 'def' => 'BO/bimpcontract/BContract_contrat'),
        'Avenant contrat'      => array('icon' => 'fas_file-medical', 'def' => 'BO/bimpcontract/BContract_avenant'),
        'Echéancier contrat'   => array('icon' => 'fas_clipboard-list', 'def' => 'BO/bimpcontract/BContract_echeancier'),
        // BimpContrat: 
        'Contrat2'             => array('icon' => 'fas_file-signature', 'def' => 'BO/bimpcontrat/BCT_Contrat'),
        // BimpCore: 
        'Hashtag'              => array('icon' => 'fas_hashtag', 'def' => 'BO/bimpcore/BimpHashtag'),
        'Utilisateur'          => array('icon' => 'fas_user', 'def' => 'BO/bimpcore/Bimp_User'),
        'Groupe'               => array('icon' => 'fas_users', 'def' => 'BO/bimpcore/Bimp_UserGroup'),
        'Client'               => array('icon' => 'fas_user-circle', 'def' => 'BO/bimpcore/Bimp_Client'),
        'Fournisseur'          => array('icon' => 'fas_building', 'def' => 'BO/bimpcore/Bimp_Fournisseur'),
        'Societe'              => array('icon' => 'fas_building', 'def' => 'BO/bimpcore/Bimp_Societe'),
        'Contact'              => array('icon' => 'far_id-card', 'def' => 'BO/bimpcore/Bimp_Contact'),
        'Produit'              => array('icon' => 'fas_box', 'def' => 'BO/bimpcore/Bimp_Product'),
        'Entrepôt'             => array('icon' => 'fas_warehouse', 'def' => 'BO/bimpcore/Bimp_Entrepot'),
        'Note'                 => array('icon' => 'fas_sticky-note', 'def' => 'BO/bimpcore/BimpNote'),
        'Fichier'              => array('icon' => 'fas_file', 'def' => 'BO/bimpcore/BimpFile'),
        // BimpEquipment: 
        'Equipement'           => array('icon' => 'fas_desktop', 'def' => 'BO/bimpequipment/Equipment'),
        'Package'              => array('icon' => 'fas_boxes', 'def' => 'BO/bimpequipment/BE_Package'),
        // BimpLogistique: 
        'Expédition'           => array('icon' => 'fas_shipping-fast', 'def' => 'BO/bimplogistique/BL_CommandeShipment'),
        'Réception'            => array('icon' => 'fas_arrow-circle-down', 'def' => 'BO/bimplogistique/BL_CommandeFournReception'),
        'Inventaire'           => array('icon' => 'fas_clipboard-list', 'def' => 'BO/bimplogistique/Inventory2'),
        //BimpReservation:
        'Réservation'          => array('icon' => 'fas_lock', 'def' => 'BO/bimpreservation/BR_Reservation'),
        // BimpSupport: 
        'Ticket hotline'       => array('icon' => 'fas_headset', 'def' => 'BO/bimpsupport/BS_Ticket'),
        'Intervention hotline' => array('icon' => 'fas_user-clock', 'def' => 'BO/bimpsupport/BS_Inter'),
        'SAV'                  => array('icon' => 'fas_wrench', 'def' => 'BO/bimpsupport/BS_SAV'),
        'Prêt matériel'        => array('icon' => 'fas_mobile-alt', 'def' => 'BO/bimpsupport/BS_Pret'),
        //BimpTask: 
        'Tâche'                => array('icon' => 'fas_tasks', 'def' => 'BO/bimptask/Bimp_Task'),
        // BimpTechnique: 
        'Fiche inter'          => array('icon' => 'fas_ambulance', 'def' => 'BO/bimptechnique/BT_ficheInter'),
        // BimpTransfer: 
        'Transfert'            => array('icon' => 'far_arrow-alt-circle-right', 'def' => 'BO/bimptransfer/Transfer')
    );
    public static $aliases = array(
        'repair'               => 'Réparation SAV',
        'gsx'                  => 'Réparation SAV',
        'proposition'          => 'Devis',
        'propale'              => 'Devis',
        'order'                => 'Commande',
        'avoir'                => 'Facture',
        'acompte'              => 'Facture',
        'invoice'              => 'Facture',
        'bill'                 => 'Facture',
        'comm fourn'           => 'Commande fournisseur',
        'fac fourn'            => 'Facture fournisseur',
        'payment'              => 'Paiement',
        'réglement'            => 'Paiement',
        'reglement'            => 'Paiement',
        'versement'            => 'Paiement',
        'virement'             => 'Paiement',
        'chèque'               => 'Paiement',
        'contract'             => 'Contrat',
        'user'                 => 'Utilisateur',
        'membre'               => 'Utilisateur',
        'employé'              => 'Utilisateur',
        'employe'              => 'Utilisateur',
        'customer'             => 'Client',
        'supplier'             => 'Fournisseur',
        'article'              => 'Produit',
        'service'              => 'Produit',
        'product'              => 'Produit',
        'dépôt'                => 'Entrepôt',
        'stock'                => 'Entrepôt',
        'file'                 => 'Fichier',
        'matériel'             => 'Equipement',
        'équipement'           => 'Equipement',
        'equipment'            => 'Equipement',
        'serial'               => 'Equipement',
        'ns'                   => 'Equipement',
        'numéro de série'      => 'Equipement',
        'numero de série'      => 'Equipement',
        'n° de serie'          => 'Equipement',
        'livraison'            => 'Expédition',
        'shipment'             => 'Expédition',
        'bl'                   => 'Expédition',
        'br'                   => 'Réception',
        'support téléphonique' => 'Ticket hotline',
        'task'                 => 'Tâche',
        'fi'                   => 'Fiche inter',
        'intervention'         => 'Fiche inter',
        'pret'                 => 'Prêt matériel',
        'Mot-clé'              => 'Hashtag',
        'keyword'              => 'Hashtag',
        '#'                    => 'Hashtag'
    );
    public static $refs_prefixes = array(
        'FA' => 'Facture',
        'AC' => 'Facture',
        'AV' => 'Facture'
    );

    public static function getObjectsArray()
    {
        $objects = array();

        foreach (self::$keywords as $obj_kw => $def) {
            $label = '';
            $icon = BimpTools::getArrayValueFromPath($def, 'icon', '');

            if ($icon) {
                $label .= BimpRender::renderIcon($icon, 'iconLeft');
            }

            $objects[$obj_kw] = $label;
        }

        ksort($objects);

        return $objects;
    }

    public static function insertObjectsDefData(&$items)
    {
        foreach ($items as &$item) {
            if (isset($item['obj_kw']) && isset(self::$keywords[$item['obj_kw']])) {
                $def = self::$keywords[$item['obj_kw']]['def'];

                $data = explode('/', $def);

                switch ($data[0]) {
                    case 'BO':
                        $item['obj_type'] = 'BO';
                        $item['obj_module'] = (isset($data[1]) ? $data[1] : '');
                        $item['obj_name'] = (isset($data[2]) ? $data[2] : '');
                        break;

                    case 'DO':
                        $item['obj_type'] = 'DO';
                        $item['obj_module'] = (isset($data[1]) ? $data[1] : '');
                        $item['obj_file'] = (isset($data[2]) ? $data[2] : '');
                        $item['obj_name'] = (isset($data[3]) ? $data[3] : '');

                        if (!$item['obj_file']) {
                            $item['obj_file'] = $item['obj_module'];
                        }
                        if (!$item['obj_name']) {
                            $item['obj_name'] = ucfirst($item['obj_file']);
                        }
                        break;
                }
            }
        }
    }
}
