<?php

class BimpUserMsg
{
	public static $userMessages = array(
		'relance_fac_sans_expertise_bimp'            => array(        // \BimpFactureFournForDol::sendRelanceExpertiseBimp
			'label'  => 'Factures sans expertise BIMP',
			'dests'  => 'object::id_user',
			'module' => 'bimpcommercial',
			'params' => array('allow_default' => 1),
		),
		'logistique_commande_ok'                     => array(    // \Bimp_Commande::checkLogistiqueStatus
			'label'  => 'La logistique complétée pour de votre commande XXX du client ...',
			'dests'  => 'object::commercial',
			'module' => 'bimpcommercial',
			'params' => array(
				'check_availability' => 0
			)
		),
		'change_statut_facturation'                  => array(    // \Bimp_Commande::checkInvoiceStatus
			'label'  => 'Changement de statut de facturation',
			'dests'  => 'object::commercial',
			'module' => 'bimpcommercial',
			'params' => array(
				'check_availability' => 0
			)
		),
		'valide_commande_client'                     => array(    // \Bimp_Commande::onValidate
			'label'  => 'Commande XXX pour le client ... a été validée',
			'dests'  => 'object::commercial',
			'module' => 'bimpcommercial',
			'params' => array(
				'check_availability' => 0
			)
		),
		'rappel_commande_brouillon'                  => array(    // \Bimp_Commande::sendRappelCommandesBrouillons
			'label'  => 'X factures en brouillon depuis ... jours, merci de les valider',
			'dests'  => 'object::id_user',
			'params' => array('allow_default' => 1),
			'module' => 'bimpcommercial',
		),
		'rappel_commande_non_facturee'               => array(    // \Bimp_Commande::sendRappelsNotBilled
			'label'  => 'La commande XXX créée le ... n\'a pas été facturée. Merci de la régulariser au plus vite.',
			'dests'  => 'object::id_user',
			'params' => array('allow_default' => 1),
			'module' => 'bimpcommercial',
		),
		'product_duree_limitee_expire_soon'          => array(    // \Bimp_Commande::checkLinesEcheances
			'label'  => 'Produits à durée limitée arrivant bientôt à échéance',
			'dests'  => 'object::user',
			'module' => 'bimpcommercial',
		),
		'facture_fourn_brouillon'                    => array(        // \Bimp_FactureFourn::sendInvoiceDraftWhithMail
			'label'  => 'Une facture fournisseur en brouillon depuis X jours. Merci de bien vouloir la régulariser au plus vite.',
			'dests'  => 'object::id_user',
			'module' => 'bimpcommercial',
		),
		'creation_accompte_client'                   => array(    // \BimpComm::createAcompte
			'label'  => 'Un acompte de X € a été ajouté à la facture XXX',
			'dests'  => 'object::commercial',
			'module' => 'bimpcommercial',
			'params' => array(
				'check_availability' => 0
			)
		),
		'rappel_facture_brouillon'                   => array( // \Bimp_Facture::sendRappelFacturesBrouillons
			'label'  => 'X factures en brouillon depuis ... jours, merci de les valider',
			'dests'  => 'object::id_user',
			'params' => array('allow_superior' => 1),
			'module' => 'bimpcommercial',
		),
		'rappel_facture_financement_impayee'         => array(    // \Bimp_Facture::sendRappelFacturesFinancementImpayees
			'label'  => 'La facture xxx dont le mode de paiement est de type "financement" n\'a pas été payée alors que sa date limite de réglement est le d / m / Y',
			'dests'  => 'conf::rappels_factures_financement_impayees_emails/bimpcommercial,object::client_commerciaux',
			'module' => 'bimpcommercial'
		),
		'relance_avenant_provisoire'                 => array(    // \cron::relanceAvenantProvisoir
			'label'  => 'Abandon d\'un avenant ou relance d\'un avenant non signé',
			'dests'  => 'object::client_commerciaux',
			'params' => array('only_first_commercial' => 1),
			'module' => 'bimpcontract',
		),
		'suspension_contrat_auto'                    => array(    // \cron::relanceActivationProvisoire
			'label'  => 'L\'activation provisoire de votre contrat xxx pour le client xxx, vient d\'être suspendue. Il ne sera réactivé que lorsque nous recevrons la version dûment signée par le client',
			'dests'  => 'object::client_commerciaux',
			'params' => array('only_first_commercial' => 1),
			'module' => 'bimpcontract',
		),
		'relance_contrat_provisoire'                 => array(    // \cron::relanceActivationProvisoire
			'label'  => 'Votre contrat xxx pour le client xxx est activé provisoirement car il n\'est pas revenu signé. Il sera automatiquement désactivé le d / m / Y si le nécessaire n\'a pas été fait.',
			'dests'  => 'object::client_commerciaux',
			'params' => array('only_first_commercial' => 1),
			'module' => 'bimpcontract',
		),
		'tacit_renewal_contract'                     => array(    // \cron::relanceActivationProvisoire
			'label'  => 'Le contrat xxx a été renouvellé tacitement. client xxx',
			'dests'  => 'object::id_user',
			'module' => 'bimpcontract',
		),
		'relance_contrat_brouillon'                  => array(    // \cron::sendMailCommercial
			'label'  => 'Le contrat xxx dont vous êtes le commercial est au statut BROUILLON depuis: x jours',
			'dests'  => 'object::id_user',
			'params' => array('allow_superior' => 1),
			'module' => 'bimpcontract',
		),
		'liste_contrat_activation_cejour'            => array(    // \cron::mailJourActivation
			'label'  => 'Voici la liste des contrats à activer ce jour',
			'dests'  => 'conf::email_groupe/bimpcontract',
			'module' => 'bimpcontract',
		),
		'liste_contrat_attente_validation'           => array(    // \cron::relance_demande
			'label'  => 'Liste des contrats en attente de validation de votre part',
			'dests'  => 'conf::email_groupe/bimpcontract',
			'module' => 'bimpcontract',
		),
		'avenant_activation_provisoire'              => array(    // \BContract_avenant::actionActivation
			'label'  => 'L\'avenant N° XXX a été activé provisoirement. Vous disposez de 15 jours pour le faire signer par le client, après ce délai, l\'avenant sera abandonné automatiquement.',
			'dests'  => 'object::commercial',
			'module' => 'bimpcontract',
			'params' => array(
				'check_availability' => 0
			)
		),
		'avenant_signe'                              => array(    // \BContract_avenant::signed
			'label'  => 'L\'avenant N°AV XXX sur le contrat XXX a été sigé le d/m/Y.',
			'dests'  => 'conf::email_groupe/bimpcontract',
			'module' => 'bimpcontract',
		),
		'avenant_prolongation_signe'                 => array(    // \BContract_avenant::signedProlongation
			'label'  => 'L\'avenant N°AVP XXX sur le contrat XXX a été sigé le d/m/Y.',
			'dests'  => 'conf::email_groupe/bimpcontract',
			'module' => 'bimpcontract',
		),
		'avenant_validation'                         => array(    // \BContract_avenant::actionValidate
			'label'  => 'L\'avenant N°AV XXX sur le contrat est en attente de signature',
			'dests'  => 'conf::email_groupe/bimpcontract',
			'module' => 'bimpcontract',
		),
		'actionActivateContrat'                      => array(    //	\BContract_contrat::actionActivateContrat
			'label'  => 'Contrat activé. Action à faire sur le contrat: "Facturer le contrat"',
			'dests'  => 'conf::email_facturation,object::id_user',
			'module' => 'bimpcontract',
		),
		'actionSignedContrat'                        => array(    //	\BContract_contrat::actionSigned
			'label'  => 'Contrat signé par le client. Action à faire sur le contrat: "Activer le contrat"',
			'dests'  => 'conf::email_groupe/bimpcontract',
			'module' => 'bimpcontract',
		),
		'actionDemandeValidationContrat'             => array(    //	\BContract_contrat::actionDemandeValidation
			'label'  => 'Contrat en attente de validation. Action à faire sur le contrat: "Valider la conformité du contrat"',
			'dests'  => 'conf::email_groupe/bimpcontract',
			'module' => 'bimpcontract',
		),
		'actionValidationContrat'                    => array(    //	\BContract_contrat::actionValidation
			'label'  => 'Ce contrat a été validé par le service technique. Vous devez maintenant utiliser l\'action "Créer signature" afin de le faire signer par le client, puis par votre direction commerciale',
			'dests'  => 'object::id_user',
			'module' => 'bimpcontract',
		),
		'erreur_auth_gsx'                            => array(        // \GSX_v2::authenticate
			'label'       => 'Erreur d\'authentification GSX',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimpapple',
			'type_metier' => 'devs'
		),
		'erreur_auth_phantom'                        => array(     // \GSX_v2::phantomAuth
			'label'       => 'Erreur d\'authentification Phantom',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimpapple',
			'type_metier' => 'devs'
		),
		'erreur_stock_consigne_apple'                => array(    // \ConsignedStock::actionReceive
			'label'  => 'Erreurs stocks consignes apple. X erreur(s) à corriger manuellement  - Voir les logs',
			'dests'  => 'conf::devs_email',
			'module' => 'bimpapple',
			'metier' => 'devs'
		),
		'user_create_bienvenue'                      => array(    // \InterfaceBimpbimp::runTrigger
			'label'  => 'Nouveau collaborateur',
			'dests'  => 'to::bimpgroupe@bimp.fr',
			'module' => 'bimpbimp',
		),
		'commande_ldlc_errors'                       => array(    // \Bimp_CommandeFourn_LdlcFiliale::verifMajLdlc
			'label'  => 'Commande(s) LDLC avec erreurs',
			'dests'  => 'conf::mail_achat::debugerp_email',
			'module' => 'bimpcommercial',
		),
		'commande_ldlc_facture'                      => array(    // \Bimp_CommandeFourn_LdlcFiliale::verifMajLdlc
			'label'  => 'La facture XXX de la commande XXX en livraison directe a été téléchargée',
			'dests'  => 'conf::mail_achat',
			'module' => 'bimpcommercial',
		),
		'suppression_paiement'                       => array(    // \Bimp_Paiement::onDelete
			'label'       => 'Suppression de paiement',
			'dests'       => 'conf::email_compta',
			'module'      => 'bimpcommercial',
			'type_metier' => 'devs'
		),
		'modification_paiement_export_compta'        => array(    // \Bimp_Paiement::actionMoveAmount
			'label'  => 'Réference de paiement XXX. Montant initial de paiement : XXX €, nouveau montant de paiement : XXX €',
			'dests'  => 'conf::email_compta',
			'module' => 'bimpcommercial',
		),
		'modification_paiement_export_compta_urgent' => array(    // \Bimp_Paiement::actionMoveAmount
			'label'  => 'Réference de paiement XXX. Montant initial de paiement : XXX €, nouveau montant de paiement : XXX €',
			'dests'  => 'conf::devs_email',
			'module' => 'bimpcommercial',
		),
		'alerte_abonnement_unpaid'                   => array(    // \BCT_Contrat::sendAlertUnpaidFacsAbo
			'label'  => 'Le client xxx n\'a pas régularisé sa facture. Vous devez procéder à la désinstallation de ses licences et à la résiliation de son contrat.',
			'dests'  => 'conf::unpaid_factures_abonnement_notification_email/bimpcontrat',
			'module' => 'bimpcontrat',
		),
		'PATH_EXTENDS_a_modifier'                    => array(    // \Bimp_Lib.php
			'label'       => 'Dans conf, constante PATH_EXTENDS à remplacer par BIMP_EXTENDS_ENTITY avec la valeur "XXX" ...',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimpcore',
			'type_metier' => 'devs'
		),
		'MAJ_ICBA_encours_non_supprime'              => array(    // \BimpClientForDol::updateICBA
			'label'       => 'Probléme MAJ ICBA xxx. L\'encours devrait deja être supprimer',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimpcore',
			'type_metier' => 'devs'
		),
		'erreur_cron'                                => array(    // \BimpCoreCronExec::mailCronErreur
			'label'       => 'Alerte X crons en erreur',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimpcore',
			'type_metier' => 'devs'
		),
		'bimpcore_to_much_logs_email'                => array(    // \BimpCache::getBimpLogsData
			'label'       => 'Il y a plus de 500 entrées à traiter dans les logs',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimpcore',
			'type_metier' => 'devs'
		),
		'erreur_fatale'                              => array(    // \BimpController::handleError
			'label'       => 'Erreur fatale ...',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimpcore',
			'type_metier' => 'devs'
		),
		'erreur_fatale_cron'                         => array(    // \BimpCron::onExit
			'label'       => 'ERREUR FATALE CRON - ...',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimpcore',
			'type_metier' => 'devs'
		),
		'page_time_indeterminer'                     => array(    // \BimpDebug::testLogDebug
			'label'       => 'USER XXX. Variable bimp_start_time absente du fichier index.php',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimpcore',
			'type_metier' => 'devs'
		),
		'page_tres_lourde'                           => array(    // \BimpDebug::testLogDebug
			'label'       => 'Page trés lourde - X sec.',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimpcore',
			'type_metier' => 'devs'
		),
		'relance_paiement_acompte'                   => array(    // \Bimp_Client::relancePaiements
			'label'  => 'L\'acompte xxx pour le client xxx est impayé. Merci d\'en vérifier la raison et de procéder à sa régularisation.',
			'dests'  => 'conf::rappels_factures_financement_impayees_emails/bimpcommercial',
			'module' => 'bimpcore',
		),
		'relances_deactivated_to_notify'             => array(    // \Bimp_Client::checkRelancesDeactivatedToNotify
			'label'  => 'Les relances du client xxx ont été désactivées le d / m / Y. Il convient de vérifier ce compte et en réactiver les relances dès que possible',
			'dests'  => 'conf::rappels_factures_financement_impayees_emails/bimpcommercial',
			'module' => 'bimpcore',
		),
		'send_log_to_dev'                            => array(    // \Bimp_Log::actionSendToDev
			'label'       => 'Une nouvelle entrée dans les logs à traiter...',
			'dests'       => 'to::obj',
			'module'      => 'bimpcore',
			'type_metier' => 'devs'
		),
		'send_log_to_dev_urgent'                     => array(    // \Bimp_Log::create
			'label'       => 'Une nouvelle entrée dans les logs à traiter d\'urgence...',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimpcore',
			'type_metier' => 'devs'
		),
		'prod_non_categorise'                        => array(    // \Bimp_Product::isVendable
			'label'  => 'Le produit xxx n\'est pas categorisé comme il faut, il manque...',
			'dests'  => 'conf::mail_achat',
			'module' => 'bimpcore',
		),
		'code_compta_inconnu'                        => array( // \Bimp_Product::getCodeComptableAchat
			'label'       => 'Attention Code comptable inconnu XXX',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimpcore',
			'type_metier' => 'devs'
		),
		'product_validated'                          => array(    // \Bimp_Product::validateProduct
			'label'  => 'Le produit XXX a été validé par ...',
			'dests'  => 'conf::product_validated_notif_email',
			'module' => 'bimpcore',
		),
		'product_validation_urgente_vente_caisse'    => array(    // \Bimp_Product::mailValidation
			'label'  => 'Le produit XXX a été ajouté à une vente en caisse. Merci de le valider d\'urgence pour finliser la vente.',
			'dests'  => 'conf::mail_achat::devs_email',
			'module' => 'bimpcore',
		),
		'product_validation'                         => array(    // \Bimp_Product::mailValidation
			'label'  => 'XXX souhaite que vous validiez le produit XXX.',
			'dests'  => 'conf::mail_achat',
			'module' => 'bimpcore',
		),
		'encours_client_modif'                       => array(    //\Bimp_Societe::onNewOutstanding_limit
			'label'  => 'L\'encours du client a été modifié. Nouvel encours: xxx €. Ancien encours: xxx €',
			'dests'  => 'object::client_commerciaux',
			'module' => 'bimpcore',
		),
		'solvabilite_client_modif'                   => array(    //\Bimp_Societe::onNewSolvabiliteStatus
			'label'  => 'Le client XXX a été mis au statur de la solvabilité xxx',
			'dests'  => 'object::client_commerciaux',
			'module' => 'bimpcore',
		),
		'activation_onoff_on_demand'                 => array(    // \Bimp_Commande::onNewStatus
			'label'  => 'Suite à votre demande, le client XXX a été activé/Désactivé par ...',
			'dests'  => 'object::id_user',
			'module' => 'bimpcore',
		),
		'demande_modif_status_client_fourn'          => array(    // \Bimp_Commande::actionStatusChangeDemand
			'label'  => 'L\'utilisateur xxx demande l\'activation/la désactivation du client/fournisseur xxx',
			'dests'  => 'conf::emails_notify_status_client_change',
			'module' => 'bimpcore',
		),
		'action_late_paiement'                       => array(    //	\Bimp_Commande::actionSendMailLatePayment
			'label'  => 'Ce client présente un retard de paiement de XXX dont détail ci-après... Vos commandes en cours ne peuvent donc pas recevoir la validation financière.',
			'dests'  => 'to::obj',
			'module' => 'bimpcore',
		),
		'valid_paiement_comptant_ou_sepa'            => array( // \Bimp_Commande::onValidate
			'label'  => 'La commande XXX a été validée financièrement par paiement comptant ou mandat SEPA par XXX. Merci de vérifier le paiement ultérieurement',
			'dests'  => 'conf::email_valid_paiement_comptant',
			'module' => 'bimpcore',
		),
		'check_validation_solvabilite'               => array(    //	\BimpComm::checkValidationSolvabilite
			'label'  => 'Demande de validation d\'une commande dont le client est au statut XXX',
			'dests'  => 'conf::solvabilite_validation_emails/bimpcommercial',
			'module' => 'bimpcommercial',
		),
		'notif_facturation_contrat_demain'           => array(    // \cron::notifDemainFacturation
			'label'  => 'Pour rappel, le contrat N° xxx doit être facturé le $demain. ref contrat xxx, Commercial suivi de contrat : XXX',
			'dests'  => 'conf::email_facturation',
			'module' => 'bimpcontract',
		),
		'facture_auto'                               => array(    // \cron::facturation_auto
			'label'  => 'Une facture a été créée automatiquement. Cette facture est encore au statut brouillon. Merci de la vérifier et de la valider. Client : XXX, contrat : XXX, commercial : XXX',
			'dests'  => 'conf::email_facturation',
			'module' => 'bimpcontract',
		),
		'message_ERP_nonlu'                          => array(    // \BimpNote::cronNonLu
			'label'  => 'Vous avez X message(s) non lu. Pour désactiver cette relance, vous pouvez soit répondre au message de la pièce émettrice (dans les notes de pied de page) soit cliquer sur la petite enveloppe "Message" en haut à droite de la page ERP.',
			'dests'  => 'object::id_user',
			'module' => 'bimpcore',
		),
		'update_prices_file_marge_neg'               => array( // \BDS_ImportsLdlcProcess::executeUpdateFromFile
			'label'       => 'Voici la liste des produits avec une marge négative',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimpdatasync',
			'type_metier' => 'devs'
		),
		'notif_commercial_courrier_retard_regl'      => array( // \BDS_RelancesClientsProcess::processNotifsCommerciaux
			'label'  => 'Le client XXX va recevoir sous 5 jours une lettre de rappel / une mise en demeure concernant les retards de réglement ci-après. Si vous pensez que cette relance/mise en demeure n\'a pas lieu d\'être, merci d\'en informer immédiatement Recouvrement en justifiant votre demande (par exemple : règlement en notre possession, litige client, etc.) ...',
			'dests'  => 'object::id_user',
			'module' => 'bimpdatasync',
		),
		'probleme_stock'                             => array( //	\controlStock::go
			'label'       => 'Entrepot xxx. ATTENTION +/- d\'équipement (X | xxx xxxx) que de prod (X) total des mouvement ("...")',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimpequipement',
			'type_metier' => 'devs'
		),
		'paiements_non_identif_auto'                 => array( //	\Bimp_ImportPaiement::toCompteAttente
			'label'  => 'Les paiements suivants n\'ont pu être identifiés automatiquement par le système',
			'dests'  => 'conf::email_paync/bimpfinanc',
			'module' => 'bimpfinanc',
		),
		'react_client_prise_rdv_online'              => array( //	\savFormController::ajaxProcessSavFormSubmit
			'label'  => 'Le client xxx a été réactivé automatiquement suite à sa prise de rendez-vous SAV en ligne',
			'dests'  => 'conf::react_client_prise_rdv_online/bimpinterfaceclient',
			'module' => 'bimpinterfaceclient',
		),
		'sav_online_by_client'                       => array( //	\savFormController::ajaxProcessSavFormSubmit
			'label'  => 'Un nouveau SAV a été créé en ligne par un client...',
			'dests'  => 'object::centre_sav',
			'params' => array('allow_shipToUsers' => 1, 'allow_user_default_sav_email' => 1),
			'module' => 'bimpinterfaceclient',
		),
		'inventaire_incoherence'                     => array( //	\Inventory2::renderHeaderExtraLeft
			'label'       => 'Inchoérence detecté dans inventaire : Ln Expected : XXX ...',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimplogistique',
			'type_metier' => 'devs'
		),
		'inventaire_incoherence_scan'                => array( //	\Inventory2::renderHeaderExtraLeft
			'label'       => 'Inchoérence detecté dans les scann de l\'inventaire : Ln Expected : XXX ...',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimplogistique',
			'type_metier' => 'devs'
		),
		'inventaire_incoherence_donnees'             => array( //	\InventoryExpected::renderEquipments
			'label'       => 'ATTENTION INCOHERENCE DES DONNEES',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimplogistique',
			'type_metier' => 'devs'
		),
		'code_erp'                                   => array( //	\securLogSms::createSendCode
			'label'  => 'Le code est XXXX',
			'dests'  => 'mail_secondaire',
			'params' => array(
				'allow_delegations' => 0,
				'allow_superior'    => 1
			),
			'module' => 'bimpsecurlogin'
		),
		'add_note_by_client'                         => array( //	\BS_Note::sendNotificationEmails
			'label'  => 'Un message a été ajouté sur votre ticket hotline XXX. Message : XXX',
			'dests'  => 'object::client_commerciaux::user_resp_ticket',
			'module' => 'bimpsupport',
		),
		'acceptation_devis_sav'                      => array(    // \BS_SAV::onPropalSigned
			'label'  => 'Le devis XXX a été accepté par le client XXX',
			'dests'  => 'object::centre_sav',
			'module' => 'bimpsupport',
		),
		'refus_devis_sav'                            => array(        // \BS_SAV::sendMsg
			'label'  => 'Notre client XXX a refusé le devis réparation sur son XXX pour un montant de X €',
			'dests'  => 'to::obj',
			'module' => 'bimpsupport',
		),
		'accompte_sav_enregitre'                     => array(    // \BS_SAV::actionAddAcompte
			'label'  => 'Un acompte de X € du client XXX a été ajouté pour le devis XXX',
			'dests'  => 'to::obj',
			'module' => 'bimpsupport',
		),
		'sav_non_restitue_pas_email_client'          => array(    // \BS_SAV::sendAlertesClientsUnrestituteSav
			'label'  => 'Aucune adresse e-mail valide enregistrée pour le client du SAV XXX. Il n\'est donc pas possible d\'alerter le client pour la non restitution de son matériel',
			'dests'  => 'object::centre_sav',
			'params' => array('allow_user_default_sav_email' => 1),
			'module' => 'bimpsupport',
		),
		'ticket_sav_non_couvert_contrat'             => array(    // \BS_Ticket::update
			'label'  => 'Le ticket SAV XXX n\'est pas couvert par le contrat',
			'dests'  => 'object::client_commerciaux',
			'module' => 'bimpsupport',
		),
		'task_not_exist'                             => array(    // \webservice_kghjddsljbfldfsvl453454kgg.php
			'label'       => 'Un mail a était recu avec une tache inexistante : XXX',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimptask',
			'type_metier' => 'devs'
		),
		'relance_FI_brouillon_Jplus1'                => array(    // \Cron::relanceTechBrouillonJplus1etPlus
			'label'  => 'Voici la liste de vos fiches d’interventions en brouillon dont la date d’intervention est dépassée',
			'dests'  => 'object::user',
			'module' => 'bimptechnique',
		),
		'relance_FI_aFact_impoderable'               => array(    // \Cron::relanceCommercial
			'label'  => 'voici la liste de vos fiches d’interventions en attente de facturation/comportant de l\'impodérable',
			'dests'  => 'object::user',
			'module' => 'bimptechnique',
		),
		'attribution_FI'                             => array(    // \BT_ficheInter::setSigned
			'label'  => 'L\'intervention xxx vous à été attribuée',
			'dests'  => 'object::id_user',
			'module' => 'bimptechnique',
		),
		'fiche_inter_non_liee'                       => array(    // \BT_ficheInter::setSigned
			'label'  => 'Cette fiche d’intervention a été validée, mais n’est liée à aucune commande et à aucun contrat. Merci de faire les vérifications nécessaires et de corriger si cela est une erreur.',
			'dests'  => 'to::dispatch@bimpf.fr,object::client_commerciaux',
			'params' => array('only_first_commercial' => 1),
			'module' => 'bimptechnique',
		),
		'envoi_CR_fiche_inter'                       => array(    // \BT_ficheInter::setSigned
			'label'  => 'Pour information, l\'intervention XXX pour le client XXX (en interne a été signé par le technicien. La FI à été marquée comme terminée automatiquement.)/(n\'a pas pu être envoyée par e-mail au client)/(a été envoyée par e-mail au client) (pour signature électronique à distance.)/(suite à sa signature électronique.)/(pour signature papier à renvoyer par e-mail.)',
			'dests'  => 'object::client_commerciaux',
			'params' => array('only_first_commercial' => 1),
			'module' => 'bimptechnique',
		),
		'notification_facturation_signature_FI'      => array(    // \BT_ficheInter::actionSendfacturation
			'label'  => 'Pour information, la FI xxx pour le client XXX a été signée par le client',
			'dests'  => 'conf::email_facturation',
			'module' => 'bimptechnique',
		),
		'notif_create_FI'                            => array(    // \BT_ficheInter::create
			'label'  => 'Une fiche d\'intervention vous a été attribuée. Fiche d\'intervention: XXX, Date prévue de l\'intervention: d/m/Y',
			'dests'  => 'object::user',
			'module' => 'bimptechnique',
		),
		'notif_change_tech_FI'                       => array(    // \BT_ficheInter::update
			'label'  => 'La fiche d\'intervention XXX vous a été attribuée. Ref XXX. Client: XXX. Changement par : XXX. Pour plus de détails rendez-vous sur la fiche d\'intervention',
			'dests'  => 'object::user',
			'module' => 'bimptechnique',
		),
		'notif_change_horaire_FI'                    => array(    // \BT_ficheInter::update
			'label'  => 'La fiche d\'intervention XXX a été modifiée au niveau des horaires. Nouveaux horaires du ... au ...',
			'dests'  => 'object::user',
			'module' => 'bimptechnique',
		),
		'notif_delete_FI'                            => array(    // \BT_ficheInter::delete
			'label'  => 'La fiche d\'intervention XXX a été supprimée par client XXX. Commandes: ...',
			'dests'  => 'object::user,to::v.gilbert@bimp.fr',
			'module' => 'bimptechnique',
		),
		'depassement_heure_contrat_delegation'       => array(    // \BT_ficheInter_det::validate
			'label'  => 'XXX a renseigné une ligne dans sa fiche d\'intervention qui crée un dépassement des heures prévues dans le contrat.',
			'dests'  => 'object::client_commerciaux'/*,to::contrat@bimp.fr*/,
			'params' => array('only_first_commercial' => 1),
			'module' => 'bimptechnique',
		),
		'CEGID_ROLLBACK_compta_aucune_action'        => array(    // 		\Cron::automatique
			'label'       => 'ROLLBACK de la compta. Aucune action n\'à été faites pour remonter la compta d\'Aujourd\'hui, les fichiers ont étés transférés  dans le dossier rollback',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimptocegid',
			'type_metier' => 'devs'
		),
		'CEGID_dossier_export_non_vide'              => array(    // 		\Cron::automatique
			'label'       => 'Dossier d\'export non vide. Rien à été fait...',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimptocegid',
			'type_metier' => 'devs'
		),
		'CEGID_fichier_tra_non_conforme'             => array(    // \Cron::checkFiles
			'label'       => 'Fichier TRA non conforme',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimptocegid',
			'type_metier' => 'devs'
		),
		'CEGID_impossible_ecrire_fichier'            => array(    // \Cron::send_rapport
			'label'       => 'Vous trouverez en pièce jointe le rapport des exports comptable existant, et voici le nouveau : ',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimptocegid',
			'type_metier' => 'devs'
		),
		'CEGID_erreur_copie_fichier'                 => array(    // \Cron::FTP
			'label'       => 'Le fichier xxx ne s\'est pas copié dans le dossier d\'import',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimptocegid',
			'type_metier' => 'devs'
		),
		'CEGID_export_compta_manuel'                 => array(    // \newExportController::displayHeaderInterface
			'label'       => 'Liste des pièces exportées manuellement par XXX ... ',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimptocegid',
			'type_metier' => 'devs'
		),
		'demande_validation_commande'                => array(    // bimpvalidateorder.class.php
			'label'  => 'L\'utilisateur XXX souhaite que vous validiez de la commande XXX du client XXX',
			'dests'  => 'object::id_user',
			'module' => 'bimpvalidateorder',
		),
		'rappel_demande_validation_encours'          => array(    // \BimpValidateOrderCronExec::sendRappel
			'label'  => 'Vous avez X commandes en attente de validation, voici le(s) lien(s)',
			'dests'  => 'object::id_user',
			'module' => 'bimpvalidateorder',
		),
		'create_demande_validation_comm'             => array(    // \DemandeValidComm::onCreate
			'label'  => 'Merci de valider la piece XXX',
			'dests'  => 'object::id_user',
			'module' => 'bimpvalidateorder',
		),
		'onvalidate_demande_validation_piece'        => array(    // \DemandeValidComm::onValidate
			'label'  => 'La piece XXX du client a été passée au status X par XXX',
			'dests'  => 'object::id_user',
			'module' => 'bimpvalidateorder',
		),
		'absence_valideur_secteur'                   => array(    // \ValidComm::createDemande
			'label'       => 'Aucun utilisateur ne peut valider commercialement/financierement/les impayés de/sur service la piece XXX (pour le secteur XXX, ...)',
			'dests'       => 'conf::debugerp_email',
			'module'      => 'bimpvalidateorder',
			'type_metier' => 'devs'
		),
		'liste_demandes_validees_et_encours'         => array(    // \ValidComm::sendMailValidation
			'label'  => 'Liste des demandes validées et en attente de validation',
			'dests'  => 'object::id_user',
			'module' => 'bimpvalidateorder',
		),
		'general_valid_paiement_comptant_ou_sepa'    => array(    // \BimpValidation::checkDemandesAutoAccept
			'label'  => 'La piece XXX a été validée financièrement par paiement comptant ou mandat SEPA par XXX. Merci de vérifier le paiement ultérieurement',
			'dests'  => 'conf::notif_paiement_comptant_email/bimpvalidation',
			'module' => 'bimpvalidation',
		),
		'general_valid_auto'                         => array( // \BimpValidation::checkObjectValidations
			'label'  => 'Toutes les demandes de validation de la piece XXX ont été acceptées...',
			'dests'  => 'object::array_id_users',
			'module' => 'bimpvalidation',
		),
		'rappel_demandes_validation'                 => array(    // \BimpValidationCronExec::sendRappels
			'label'  => 'X demandes de validation sont toujours en attente d\'acceptation',
			'dests'  => 'object::id_user',
			'module' => 'bimpvalidation',
		),
		'validation_piece_acceptee'                  => array( // \BV_Demande::setAccepted
			'label'  => 'La validation de la piece XXX a été acceptée par XXX',
			'dests'  => 'object::id_user',
			'module' => 'bimpvalidation',
		),
		'check_affected_user'                        => array( // \BV_Demande::checkAffectedUser
			'label'  => 'La validation de la piece PROVXXX est en attente ...',
			'dests'  => 'object::id_user',
			'module' => 'bimpvalidation',
		),
		'validation_piece_refusee'                   => array( // \BV_Demande::actionRefused
			'label'  => 'La validation de la piece XXX a été refusée par XXX',
			'dests'  => 'object::id_user',
			'module' => 'bimpvalidation',
		),
		'webservice_error_fatal'                     => array( // \BimpWebservice::handleError
			'label'       => 'ERP XXX. Requête: XXX. Utilisateur WS: ...',
			'dests'       => 'conf::devs_email',
			'module'      => 'bimpwebservice',
			'type_metier' => 'devs'
		),
		'create_inter_ticket'                        => array( // \BS_Inter::create
			'label'  => 'Un ticket d\'intervention a été créé sur votre ticket N° XXX',
			'dests'  => 'object::client_commerciaux',
			'params' => array('only_first_commercial' => 1),
			'module' => 'bimpsupport',
		),
		'tentativeFermetureAuto_non_ferme_GSX'       => array( // \test_sav::tentativeFermetureAuto
			'label'  => 'Le SAV xxx n\'arrive pas etre fermé',
			'dests'  => 'object::array_id_users',
			'params' => array('allow_user_default_sav_email' => 1),
			'module' => 'bimpapple',
		),
		'tentativeFermetureAuto_non_RFPU_GSX'        => array( // \test_sav::tentativeFermetureAuto
			'label'  => 'Le SAV xxx n\'arrive pas a être passé a RFPU dans GSX',
			'dests'  => 'object::array_id_users',
			'params' => array('allow_user_default_sav_email' => 1),
			'module' => 'bimpapple',
		),
		'tentativeARestitueAuto_non_ferme_GSX'       => array( // \test_sav::tentativeARestitueAuto
			'label'  => 'Le SAV xxx n\'arrive pas etre fermé',
			'dests'  => 'object::array_id_users',
			'params' => array('allow_user_default_sav_email' => 1),
			'module' => 'bimpapple',
		),
		'tentativeARestitueAuto_non_RFPU_GSX'        => array( // \test_sav::tentativeARestitueAuto
			'label'  => 'Le SAV xxx n\'arrive pas a être passé a RFPU dans GSX',
			'dests'  => 'object::user',
			'params' => array('allow_user_default_sav_email' => 1),
			'module' => 'bimpapple',
		),
		'emailOnMiseEnDemeure'                       => array(
			'label'  => 'Nous venons de mettre en demeure le client XXX. Son compte est désormais bloqué. Voici l\'état actuel des créances y compris les factures non échues ...',
			'dests'  => 'object::client_commerciaux',
			'module' => 'bimpcommercial',
		),
		'notif_signature_signed'                     => array(
			'label'  => 'La signature du document XXX a été effectuée.',
			'dests'  => 'to::obj',
			'module' => 'bimpcore',
		),
		'notif_signature_refused'                    => array(
			'label'  => 'La signature du document XXX a été refusée par le signataire XXX.',
			'dests'  => 'to::obj',
			'module' => 'bimpcore',
		),
		'Activation_contrat_provisoire'              => array(
			'label'  => 'Votre contrat XXX pour le client XXX est activé provisoirement car il n\'est pas revenu signé. Il sera automatiquement désactivé le d / m / Y si le nécessaire n\'a pas été fait.',
			'dests'  => 'object::id_user',
			'module' => 'bimpcontract',
		),
		'valid_factureBrouillon_sur_contrat'         => array(
			'label'  => 'Une facture a été créée sur le contrat XXX. Cette facture est encore au statut brouillon. Merci de la vérifier et de la valider.',
			'dests'  => 'conf::email_facturation',
			'module' => 'bimpcontract',
		),
		'fetchContrat_pb_deplacement_fichier'        => array(
			'label'  => 'Problème de déplacement de dir/file vers newdir/file',
			'dests'  => 'conf::devs_email',
			'module' => 'bimpcontract',
			'metier' => 'devs'
		),
		'product_validated_command'                  => array(
			'label'  => 'Le produit xxx a été validé, la commande xxx est peut-être validable',
			'dests'  => 'to::obj',
			'module' => 'bimpcore',
		),
		'product_validated_propal'                   => array(
			'label'  => 'Le produit xxx a été validé, la propale xxx est peut-être validable',
			'dests'  => 'to::obj',
			'module' => 'bimpcore',
		),
		'product_refused_command'                    => array(
			'label'  => 'le produit xxx a été refusé, la commande xxx doit etre modifiée',
			'dests'  => 'to::obj',
			'module' => 'bimpcore',
		),
		'product_validated_vente'                    => array(
			'label'  => 'Le produit xxx a été validé, la vente xxx est peut-être validée',
			'dests'  => 'to::obj',
			'module' => 'bimpcore',
		),
		'product_refused_propal'                     => array(
			'label'  => 'Le produit xxx a été refusé, la propale xxx doit etre modifiée',
			'dests'  => 'to::obj',
			'module' => 'bimpcore',
		),
		'confirm_sign_docusign'                      => array(
			'label'  => 'La (ou les) signature(s) via DocuSign du document XXX a été complètée le d / m / Y',
			'dests'  => 'to::obj',
			'module' => 'bimpcore',
		),
		'relance_not_sent'                           => array(
			'label'  => 'Le client XXX ne peut pas être relancé car ...',
			'dests'  => 'conf::emails_notify_solvabilite_client_change',
			'module' => 'bimpcore',
			'metier' => 'devs'
		)

		// todo tommy : separer mail client et mail interne :
		/*
			'creation_ticket_support_view'	=> array( //	views\interfaces
				'label' => 'Ticket Support N° XXX. Sujet du ticket : XXX. Demandeur : XXX. Contrat : XXX',
				'dests' => '???????????',
				'dest' => '????????',
				'cc' => 'j.garnier@bimp.fr, l.gay@bimp.fr, tt.cao@bimp.fr',
				'module' => 'bimpinterfaceclient',
				'active' => (int) BimpCore::getConf('bimpsupport', null, 'use_tickets')
			),
			'creation_ticket_support_html'	=> array( //	html\interfaces
				'label' => 'Ticket Support N° XXX. Sujet du ticket : XXX. Demandeur : XXX. Contrat : XXX',
				'dests' => '???????????',
				'dest' => '????????',
				'cc' => 'j.garnier@bimp.fr, l.gay@bimp.fr, tt.cao@bimp.fr',
				'module' => 'bimpinterfaceclient',
				'active' => (int) BimpCore::getConf('bimpsupport', null, 'use_tickets')
			),
			'creation_ticket_support'	=> array( //	\BS_Ticket::create
				'label' => 'Ticket Support N° XXX. Sujet du ticket : XXX. Demandeur : XXX. Contrat : XXX',
				'dests' => '???????????',
				'dest' => '????????',
				'cc' => 'c.conort@bimp.fr, l.gay@bimp.fr, tt.cao@bimp.fr, d.debarnaud@bimp.fr, v.gaillard@bimp.fr',
				'module' => 'bimpsupport',
				'active' => (int) BimpCore::getConf('bimpsupport', null, 'use_tickets')
			),*/
	);

	public static $canaux_diff_msg = array(
		'mail'   => 'Email',
		'msgerp' => 'Message interne'
	);

	public static $default_params = array(
		'active'             => 1,
		'required'           => 1,
		'canal_diffusion'    => 'email',
		'def_abo'            => 1,
		'type_metier'        => 'metier',
		'check_availability' => 0,
		'allow_delegations'  => 1,
		'allow_superior'     => 0,
		'allow_default'      => 0
	);

	public static function getParamsMessage($code, &$errors = array())
	{
		global $user;
		if (!isset(static::$userMessages[$code])) {
			$errors[] = 'Code message inconnu : ' . $code;
		} else {
			$um = static::$userMessages[$code];
			$params = static::$default_params;

			if (isset($um['params'])) {
				$params = BimpTools::overrideArray($params, $um['params']);
			}

			$params['active'] = self::isMsgActive($code);
			$um['params'] = $params;

			if ($user->login == 'f.martinez') {
				echo 'PARAMS : <pre>' . print_r($params, 1) . '</pre>';
			}
			return $um;
		}

		return array();
	}

	public static function getParamsMessageAll()
	{
		$list = array();
		foreach (static::$userMessages as $code => $message) {
			$list[$code] = self::getParamsMessage($code);
		}
		return $list;
	}

	public static function isMsgActive($code)
	{
		return (int) BimpCore::getConf('userMessages__' . $code . '__msgActive', 1);
	}

	public static function envoiMsg($code, $sujet, $contenu, $obj = null, $piecejointe = array(), $debug = false)
	{
		$errors = array();

		if (!isset(static::$userMessages[$code])) {
			$errors[] = 'Code message inconnu : ' . $code;
		} else {
			$id_users = array(); // pour y stocker les id des users
			$userDestinataires = array(); // pour y stocker les objets Bimp_User
			$idsDejaAjoutes = array();
			$to_emails = array(); // Liste finale des emails

			$datasMessage = self::getParamsMessage($code);

			if (!$datasMessage['params']['active']) {
				return array(); // message non actif, pas d'erreur
			}

			$dests = explode(',', $datasMessage['dests']);
			$params = $datasMessage['params'];
			$redir_reasons = array();

			foreach ($dests as $d) {
				$elements = explode('::', $d);
				$destType = $elements[0];
				unset($elements[0]);
				switch ($destType) {
					case 'to':
						foreach ($elements as $e) {
							if ($e == 'obj') {
								$e = $obj;
							} // cas particulier comme send_log_to_dev ou refus_devis_sav ou accompte_sav_enregitre
							if (!in_array($e, $to_emails)) {
								$to_emails[] = $e;
							}
						}
						break;

					case 'conf':
						foreach ($elements as $e) {
							if (preg_match('/^([^\/]+)\/?(.+)?$/', $e, $matches)) {
								$conf_name = $matches[1];
								if (isset($matches[2]) && $matches[2]) {
									$module = $matches[2];
								} else {
									$module = 'bimpcore';
								}

								$email = BimpCore::getConf($conf_name, '', $module);
								if ($email && !in_array($email, $to_emails)) {
									$to_emails[] = $email;
								}
							} else {
								$errors[] = 'Paramètre conf invalide : ' . $e;
							}
						}
						break;

					case 'user_group':
						foreach ($elements as $e) {
							$id_group = BimpCore::getUserGroupId($e);

							if ($id_group) {
								$group = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_UserGroup', $id_group);
								if (BimpObject::objectLoaded($group)) {
									$email = $group->getData('mail');
									if ($email && !in_array($email, $to_emails)) {
										$to_emails[] = $email;
									}
								}
							} else {
								$errors[] = 'ID Groupe non défini en conf : ' . $e;
							}
						}
						break;

					case 'object':
						foreach ($elements as $e) {
							switch ($e) {
								case 'user':
									if ((is_a($obj, 'Bimp_User') || is_a($obj, 'User')) && BimpObject::objectLoaded($obj)) {
										if (!in_array($obj->id, $id_users)) {
											$id_users[] = $obj->id;
										}
									} else {
										$errors[] = 'User : objet invalide';
									}
									break;

								case 'commercial':
									// recup du/des commercial lié à l'obj transmis (devis, commande, etc)
									if (is_object($obj) && method_exists($obj, 'getIdCommercial')) {
										$id_user = $obj->getIdCommercial();

										if ($id_user && !in_array($id_user, $id_users)) {
											$id_users[] = $id_user;
										}
									} else {
										$errors[] = 'Commercial pièce : objet invalide';
									}
									break;

								case 'id_user':
									if ((int) $obj) {
										if (!in_array($obj, $id_users)) {
											$id_users[] = (int) $obj;
										}
									} else {
										$errors[] = 'id_user : id invalide';
									}
									break;

								case 'array_id_users':
									if (is_array($obj)) {
										foreach ($obj as $id_user) {
											if ((int) $id_user) {
												if (!in_array($id_user, $id_users)) {
													$id_users[] = $id_user;
												}
											} else {
												$errors[] = 'array_id_users : id invalide (' . (string) $id_user . ')';
											}
										}
									}
									break;

								case 'client_commerciaux':
									// recup des commerciaux du client lié à l'obj
									if (BimpObject::objectLoaded($obj) && is_a($obj, 'BimpObject')) {
										if (is_a($obj, 'Bimp_Societe')) {
											$soc = $obj;
										} else {
											$soc = $obj->getChildObject('client');
											if (!BimpObject::objectLoaded($soc)) {
												$errors[] = 'Commercial client : client lié invalide';
											}
										}

										if (empty($errors)) {
											$users = $soc->getCommercials($params['allow_default'], (isset($params['only_first_commercial']) ? $params['only_first_commercial'] : false));
											foreach ($users as $u) {
												if (!in_array($u->id, $id_users)) {
													$id_users[] = $u->id;
												}
											}
										}
									} else {
										$errors[] = 'Commercial client : objet invalide';
									}
									break;

								case 'centre_sav':
									$centre = $obj; // attention, obj c'est un array (cf getCentresData() dans BimpCache)
									if (is_array($centre) && isset($centre['mail']) && $centre['mail']) {     // utiliser d'abord l'email du centre
										if (!in_array($centre['mail'], $to_emails)) {
											$to_emails[] = $centre['mail'];
										}
									} elseif (is_a($centre, 'BS_CentreSav')) {
										$email = $centre->getData('email');
										if ($email && !in_array($email, $to_emails)) {
											$to_emails[] = $email;
										}
									}

									if (empty($to_emails) && isset($params['allow_shipToUsers']) && $params['allow_shipToUsers']) {
										if ($debug) {
											echo 'ICI - ';
										}
										// sinon, recup des user du centre et les mettre dans id_users
										BimpObject::loadClass('bimpcore', 'Bimp_User');
										$shipToUsers = Bimp_User::getUsersByShipto($centre['shipTo']);
										if (!empty($shipToUsers)) {
											foreach ($shipToUsers as $u) {
												$id_users[] = $u['id'];
											}
										} else {
											$errors[] = 'PAS DE USERS POUR LE SHIPTO ' . $centre['shipTo'];
										}
									} elseif ($debug) {
										echo 'LA - ';
										echo '<pre>MAILS : ' . print_r($to_emails, 1) . '</pre>';
										echo '<pre>params : ' . print_r($params, 1) . '</pre>';
									}

									if (empty($to_emails) && empty($id_users) && isset($params['allow_user_default_sav_email']) && $params['allow_user_default_sav_email']) {
										if ($debug) {
											echo 'HERE - ';
										}

										$email = BimpCore::getConf('default_sav_email', null, 'bimpsupport');
										if ($email && !in_array($email, $to_emails)) {
											$to_emails[] = $email;
										}
									} elseif($debug) {
										echo 'ok - ';
										echo '<pre>USERS : ' . print_r($id_users, 1) . '</pre>';
									}
									break;

								default:
									$errors[] = 'Type de destinataire imprevu : ' . $e;
									break;
							}
						}
						break;

					case 'mail_secondaire':
						$mail_sec = $obj->traiteMail(); // obj = securLogSms
						if ($obj->isMail($mail_sec)) {
							$to_emails[] = $mail_sec;
						} else {
							if ($params['allow_superior'] && !in_array($obj->user->fk_user, $idsDejaAjoutes)) {
								$superior = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $obj->user->fk_user);
								if (BimpObject::objectLoaded($superior) && (int) $superior->getData('statut')) {
									$userDestinataires[] = $superior;
									$idsDejaAjoutes[] = $superior->id;
									global $langs;
									$redir_reasons[$superior->id] = "Message recu en l\'absence d\'adresse e-mail secondaire enregistreé pour " . $obj->user->getFullName($langs);
								}
							}
						}
						break;

					default:
						$errors[] = 'Type de destinataire inconnu : ' . $destType;
						break;
				}
			}

			if (!empty($id_users)) {
				foreach ($id_users as $id_user) {
					if (!in_array($id_user, $idsDejaAjoutes)) {
						$idsDejaAjoutes[] = $id_user;

						$user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);

						if (BimpObject::objectLoaded($user)) {
							$unallowed_reason = '';
							if ($user->isMsgAllowed($code, $params['check_availability'], $unallowed_reason)) {
								$userDestinataires[] = $user;
							} else {
								if ($params['allow_superior']) {
									// supérieur hiérarchique
									$id_sup = $user->getData('fk_user');

									if (!in_array($id_sup, $idsDejaAjoutes)) {
										$superior = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_sup);
										if (BimpObject::objectLoaded($superior) && $superior->getData('statut') && (!$params['check_availability'] || $superior->isAvailable())) {
											$userDestinataires[] = $superior;
											$idsDejaAjoutes[] = $superior->id;
											$redir_reasons[$superior->id] = 'Message recu par délégation de ' . $user->getFullName();
										}
									}
								}
							}

							if ($params['allow_delegations']) {
								// délégation premise et mise en place :
								$delegations = $user->getData('delegations');
								if (count($delegations)) {
									foreach ($delegations as $id_user_deleg) {
										if (in_array($id_user_deleg, $idsDejaAjoutes) || in_array($id_user_deleg, $id_users)) {
											continue;
										}

										$user_deleg = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user_deleg);
										if (BimpObject::objectLoaded($user_deleg) && (int) $user_deleg->getData('statut')) {
											$userDestinataires[] = $user_deleg;
											$idsDejaAjoutes[] = $id_user_deleg;
											$redir_reasons[$id_user_deleg] = 'Message recu par délégation de ' . $user->getFullName();
										}
									}
								}
							}
						}
					}
				}
			}

			if (!count($errors)) {
				if (count($userDestinataires)) {
					foreach ($userDestinataires as $u) {
						if (!in_array($u->getData('email'), $to_emails)) {
							$to_emails[] = $u->getData('email');
						}
					}
				}

				if (empty($to_emails)) {
					if (isset($params['allow_user_default_sav_email']) && $params['allow_user_default_sav_email']) {
						$email = BimpCore::getConf('default_sav_email', '', 'bimpsupport');
						if ($email) {
							$to_emails[] = $email;
							$redir_reasons[$email] = 'Message recu en tant que destinataire par défaut pour le SAV (aucun autre utilisateur disponible)';
						}
					} elseif ($params['allow_default']) {
						$email = BimpCore::getConf('default_user_email', null);
						if ($email) {
							$to_emails[] = $email;
							$redir_reasons[$email] = 'Message recu en tant que destinataire par défaut (aucun autre utilisateur disponible)';
						}
					}
				}

				if (!empty($to_emails)) {
					foreach ($to_emails as $key => $email) {
						if (!BimpValidate::isEmail($email)) {
							unset($to_emails[$key]);
							$errors[] = 'email non valide ' . $email;
						} else {
							$to_emails[$key] = $email;
						}
					}

					$to = implode(', ', $to_emails);
					$filename_list = ($piecejointe ? $piecejointe[0] : array());
					$mimetype_list = ($piecejointe ? $piecejointe[1] : array());
					$mimefilename_list = ($piecejointe ? $piecejointe[2] : array());

					if (!empty($redir_reasons)) {
						$header = '';

						foreach ($redir_reasons as $key => $redir_reason) {
							if (is_string($key)) {
								$header .= $key . ' : ' . $redir_reason . "\n";
							} elseif (is_int($key)) {
								$user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $key);
								if (BimpObject::objectLoaded($user)) {
									$header .= $user->getData('email') . ' : ' . $redir_reason . "\n";
								}
							}
						}

						$contenu = $header . "\n" . $contenu;
					}

					// todo flo : gérer les transactions db.
					if (!mailSyn2($sujet, $to, null, $contenu, $filename_list, $mimetype_list, $mimefilename_list)) {
						$errors[] = 'Echec de l\'envoi du message par e-mail';
					}
				} else {
					BimpCore::addlog('Message utilisateur "' . $code . '" non envoyé (aucun destinataire)', 2, 'email', (is_a($obj, 'BimpObject') ? $obj : ''));
				}
			}

			if (count($errors)) {
				BimpCore::addlog('Erreurs envoi message utilisateur "' . $code . '"', 3, 'email', (is_a($obj, 'BimpObject') ? $obj : ''), array(
					'Erreurs' => $errors
				));
			}
		}

		return $errors;
	}
}
