<?php
global $user_messages, $types_dest, $canaux_diff_msg;

$canaux_diff_msg = array(
	'mail' => 'Email',
	'msgerp' => 'Message interne'
);

$types_dest = array(
	'commercial_piece' => 'Commercial pièce',
	'commercial_client' => 'Commercial client',
	'acheteur_piece' => 'Acheteur pièce',
	'compta_commercial_societe' => 'Compta + Commercial Client',
	'conf' => 'Email paramétré dans la configuration',
	'text_perso' => 'email(s) perso en dur dans le code (ex : tommy@bimp.fr)',
	'text_generic_commercial_client' => 'email generique en dur + commercial client',
	'tech_sav' => 'Technicien SAV',
	'data_formulaire' => 'Email issu d\'un formulaire',
	'user_erp' => 'Utilisateur de l\'ERP',
	'centre_sav' => 'Centre SAV',
	'usergroup_extrafield' => 'Champ extrafield des groupes utilisateur SAV',
	'tech_sav_commercial_client' => 'Technicien SAV OU Commercial Client',
	'text_perso_tech_sav' => 'technicien SAV et email perso',
);

$user_messages = array(
	'relance_fac_sans_expertise_bimp' => array(		// \BimpFactureFournForDol::sendRelanceExpertiseBimp
		'label' => 'Factures sans expertise BIMP',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpcommercial',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'logistique_ok' => array(	// \Bimp_Commande::checkLogistiqueStatus
		'label' => 'La logistique complétée pour de votre commande XXX du client ...',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpcommercial',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'change_statut_facturation' => array(	// \Bimp_Commande::checkInvoiceStatus
		'label' => 'Changement de statut de facturation',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpcommercial',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'valide_commande_client' => array(	// \Bimp_Commande::checkCommandeStatus
		'label' => 'Commande XXX pour le client ... a été validée',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpcommercial',
		'active' => 0,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'rappel_commande_brouillon' => array(	// \Bimp_Commande::sendRappelCommandesBrouillons
		'label' => 'X factures en brouillon depuis ... jours, merci de les valider',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpcommercial',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'rappel_commande_non_facturee' => array(	// \Bimp_Commande::sendRappelsNotBilled
		'label' => 'La commande XXX créée le ... n\'a pas été facturée. Merci de la régulariser au plus vite.',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpcommercial',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'product_duree_limitee_expire_soon' => array(	// \Bimp_Commande::checkLinesEcheances
		'label' => 'Produits à durée limitée arrivant bientôt à échéance',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpcommercial',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'facture_fourn_brouillon' => array(		// \Bimp_FactureFourn::sendInvoiceDraftWhithMail
		'label' => 'Une facture fournisseur en brouillon depuis X jours. Merci de bien vouloir la régulariser au plus vite.',
		'type_dest' => 'acheteur_piece',
		'module' => 'bimpcommercial',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'creation_accompte_client' => array(	// \BimpComm::createAcompte
		'label' => 'Un acompte de X € a été ajouté à la facture XXX',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpcommercial',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'rappel_facture_brouillon' => array( // \Bimp_Facture::sendRappelFacturesBrouillons
		'label' => 'X factures en brouillon depuis ... jours, merci de les valider',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpcommercial',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'rappel_facture_financement_impayee'	=> array(	// \Bimp_Facture::sendRappelFacturesFinancementImpayees
		'label' => 'La facture xxx dont le mode de paiement est de type "financement" n\'a pas été payée alors que sa date limite de réglement est le d / m / Y',
		'type_dest' => 'compta_commercial_societe',
		'module' => 'bimpcommercial',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'relance_avenant_provisoire'	=> array(	// \cron::relanceAvenantProvisoir
		'label' => 'Abandon d\'un avenant ou relance d\'un avenant non signé',
		'type_dest' => 'commercial_client',
		'module' => 'bimpcontract',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'suspension_contrat_auto'	=> array(	// \cron::relanceActivationProvisoire
		'label' => 'L\'activation provisoire de votre contrat xxx pour le client xxx, vient d\'être suspendue. Il ne sera réactivé que lorsque nous recevrons la version dûment signée par le client',
		'type_dest' => 'commercial_client',
		'module' => 'bimpcontract',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'relance_contrat_provisoire'	=> array(	// \cron::relanceActivationProvisoire
		'label' => 'Votre contrat xxx pour le client xxx est activé provisoirement car il n\'est pas revenu signé. Il sera automatiquement désactivé le d / m / Y si le nécessaire n\'a pas été fait.',
		'type_dest' => 'commercial_client',
		'module' => 'bimpcontract',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'tacit_renewal_contract'	=> array(	// \cron::relanceActivationProvisoire
		'label' => 'Le contrat xxx a été renouvellé tacitement. client xxx',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpcontract',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'relance_contrat_brouillon'	=> array(	// \cron::sendMailCommercial
		'label' => 'Le contrat xxx dont vous êtes le commercial est au statut BROUILLON depuis: x jours',
		'type_dest' => 'commercial_client',
		'module' => 'bimpcontract',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'liste_contrat_activation_cejour'	=> array(	// \cron::mailJourActivation
		'label' => 'Voici la liste des contrats à activer ce jour',
		'type_dest' => 'conf',
		'dest' => 'email_groupe',
		'module' => 'bimpcontract',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'liste_contrat_attente_validation'	=> array(	// \cron::relance_demande
		'label' => 'Liste des contrats en attente de validation de votre part',
		'type_dest' => 'conf',
		'dest' => 'email_groupe',
		'module' => 'bimpcontract',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'avenant_activation_provisoire'	=> array(	// \BContract_avenant::actionActivation
		'label' => 'L\'avenant N° XXX a été activé provisoirement. Vous disposez de 15 jours pour le faire signer par le client, après ce délai, l\'avenant sera abandonné automatiquement.',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpcontract',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'avenant_signe' => array(	// \BContract_avenant::signed
		'label' => 'L\'avenant N°AV XXX sur le contrat XXX a été sigé le d/m/Y.',
		'type_dest' => 'conf',
		'dest' => 'contrat_email',
		'module' => 'bimpcontract',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'avenant_prolongation_signe' => array(	// \BContract_avenant::signedProlongation
		'label' => 'L\'avenant N°AVP XXX sur le contrat XXX a été sigé le d/m/Y.',
		'type_dest' => 'conf',
		'dest' => 'contrat_email',
		'module' => 'bimpcontract',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'avenant_validation' => array(	// \BContract_avenant::actionValidate
		'label' => 'L\'avenant N°AV XXX sur le contrat est en attente de signature',
		'type_dest' => 'conf',
		'dest' => 'contrat_email',
		'module' => 'bimpcontract',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'actionActivateContrat'=> array(	//	\BContract_contrat::actionActivateContrat
		'label' => 'Contrat activé. Action à faire sur le contrat: "Facturer le contrat"',
		'type_dest' => 'conf',
		'dest' => 'email_facturation',
		'module' => 'bimpcontract',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'actionSignedContrat'	=> array(	//	\BContract_contrat::actionSigned
		'label' => 'Contrat signé par le client. Action à faire sur le contrat: "Activer le contrat"',
		'type_dest' => 'conf',
		'dest' => 'email_groupe',
		'module' => 'bimpcontract',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'actionDemandeValidationContrat' => array(	//	\BContract_contrat::actionDemandeValidation
		'label' => 'Contrat en attente de validation. Action à faire sur le contrat: "Valider la conformité du contrat"',
		'type_dest' => 'conf',
		'dest' => 'email_groupe',
		'module' => 'bimpcontract',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'actionValidationContrat'	=> array(	//	\BContract_contrat::actionValidation
		'label' => 'Ce contrat a été validé par le service technique. Vous devez maintenant utiliser l\'action "Créer signature" afin de le faire signer par le client, puis par votre direction commerciale',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpcontract',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'tentativeFermetureAuto_non_ferme_GSX'	=> array( // \test_sav::tentativeFermetureAuto
		'label' => 'Le SAV xxx n\'arrive pas etre fermé',
		'type_dest' => 'tech_sav',
		'module' => 'bimpapple',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'tentativeFermetureAuto_non_RFPU_GSX'	=> array( // \test_sav::tentativeFermetureAuto
		'label' => 'Le SAV xxx n\'arrive pas a être passé a RFPU dans GSX',
		'type_dest' => 'tech_sav',
		'module' => 'bimpapple',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'tentativeARestitueAuto_non_ferme_GSX'	=> array( // \test_sav::tentativeARestitueAuto
		'label' => 'Le SAV xxx n\'arrive pas etre fermé',
		'type_dest' => 'tech_sav',
		'module' => 'bimpapple',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'tentativeARestitueAuto_non_RFPU_GSX'	=> array( // \test_sav::tentativeARestitueAuto
		'label' => 'Le SAV xxx n\'arrive pas a être passé a RFPU dans GSX',
		'type_dest' => 'tech_sav',
		'module' => 'bimpapple',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'erreur_auth_gsx' => array(		// \GSX_v2::authenticate
		'label' => 'Erreur d\'authentification GSX',
		'type_dest' => 'text_perso',
		'dest' => 'tommy@bimp.fr, f.martinez@bimp.fr',
		'module' => 'bimpapple',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),
	'erreur_auth_phantom' => array(	 // \GSX_v2::phantomAuth
		'label' => 'Erreur d\'authentification Phantom',
		'type_dest' => 'conf',
		'dest' => 'debugerp_email',
		'module' => 'bimpapple',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'erreur_stock_consigne_apple' => array(	// \ConsignedStock::actionReceive
		'label' => 'Erreurs stocks consignes apple. X erreur(s) à corriger manuellement  - Voir les logs',
		'type_dest' => 'tech_sav',
		'module' => 'bimpapple',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'user_create_bienvenue' => array(	// \InterfaceBimpbimp::runTrigger
		'label' => 'Nouveau collaborateur',
		'type_dest' => 'conf',
		'dest' => 'all_user_email',
		'module' => 'bimpbimp',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'commande_ldlc_errors'	=> array(	// \Bimp_CommandeFourn_LdlcFiliale::verifMajLdlc
		'label' => 'Commande(s) LDLC avec erreurs',
		'type_dest' => 'conf',
		'dest' => 'mail_achat',
		'module' => 'bimpcommercial',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'commande_ldlc_facture'	=> array(	// \Bimp_CommandeFourn_LdlcFiliale::verifMajLdlc
		'label' => 'La facture XXX de la commande XXX en livraison directe a été téléchargée',
		'type_dest' => 'conf',
		'dest' => 'mail_achat',
		'module' => 'bimpcommercial',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'suppression_paiement' => array(	// \Bimp_Paiement::onDelete
		'label' => 'Suppression de paiement',
		'type_dest' => 'text_perso',
		'dest' => 'tommy@bimp.fr, comptamaugio@bimp.fr',
		'module' => 'bimpcommercial',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),
	'modification_paiement_expot_compta' => array(	// \Bimp_Paiement::actionMoveAmount
		'label' => 'Réference de paiement XXX. Montant initial de paiement : XXX €, nouveau montant de paiement : XXX €',
		'type_dest' => 'conf',
		'dest' => 'email_compta',
		'module' => 'bimpcommercial',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'alerte_abonnement_unpaid' => array(	// \BCT_Contrat::sendAlertUnpaidFacsAbo
		'label' => 'Le client xxx n\'a pas réularisé sa facture. Vous devez procéder à la désinstallation de ses licences et à la résiliation de son contrat.',
		'type_dest' => 'conf',
		'dest' => 'unpaid_factures_abonnement_notification_email',
		'module' => 'bimpcontrat',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'PATH_EXTENDS_a_modifier'	=> array(	// \Bimp_Lib.php
		'label' => 'Dans conf, constante PATH_EXTENDS à remplacer par BIMP_EXTENDS_ENTITY avec la valeur "XXX" ...',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'MAJ_ICBA_encours_non_supprime' => array(	// \BimpClientForDol::updateICBA
		'label' => 'Probléme MAJ ICBA xxx. L\'encours devrait deja être supprimer',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'erreur_cron'	=> array(	// \BimpCoreCronExec::mailCronErreur
		'label' => 'Alerte X crons en erreur',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'bimpcore_to_much_logs_email' => array(	// \BimpCache::getBimpLogsData
		'label' => 'Il y a plus de 500 entrées à traiter dans les logs',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'erreur_fatale' => array(	// \BimpController::handleError
		'label' => 'Erreur fatale ...',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'erreur_fatale_cron' => array(	// \BimpCron::onExit
		'label' => 'ERREUR FATALE CRON - ...',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'page_time_indeterminer' => array(	// \BimpDebug::testLogDebug
		'label' => 'USER XXX. Variable bimp_start_time absente du fichier index.php',
		'type_dest' => 'text_perso',
		'dest'	=> 'tommy@bimp.fr',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),
	'page_tres_lourde' => array(	// \BimpDebug::testLogDebug
		'label' => 'Page trés lourde - X sec.',
		'type_dest' => 'text_perso',
		'dest' => 'tommy@bimp.fr, f.martinez@bimp.fr',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'relance_paiement_acompte' => array(	// \Bimp_Client::relancePaiements
		'label' => 'L\'acompte xxx pour le client xxx est impayé. Merci d\'en vérifier la raison et de procéder à sa régularisation.',
		'type_dest' => 'conf',
		'dest' => 'rappels_factures_financement_impayees_emails',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'relances_deactivated_to_notify'	=> array(	// \Bimp_Client::checkRelancesDeactivatedToNotify
		'label' => 'Les relances du client xxx ont été désactivées le d / m / Y. Il convient de vérifier ce compte et en réactiver les relances dès que possible',
		'type_dest' => 'conf',
		'dest' => 'rappels_factures_financement_impayees_emails',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'send_log_to_dev'	=> array(	// \Bimp_Log::actionSendToDev
		'label' => 'Une nouvelle entrée dans les logs à traiter...',
		'type_dest' => 'conf',
		'dest' => 'BimpCore::$ dev_mails[$ dev]',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'send_log_to_dev_urgent'	=> array(	// \Bimp_Log::create
		'label' => 'Une nouvelle entrée dans les logs à traiter d\'urgence...',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'prod_non_categorise' => array(	// \Bimp_Product::isVendable
		'label' => 'Le produit xxx n\'est pas categorisé comme il faut, il manque...',
		'type_dest' => 'conf',
		'dest' => 'mail_achat',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'code_compta_inconnu_ACHAT'	=> array( // \Bimp_Product::getCodeComptableAchat
		'label' => 'Attention Code comptable inconnu XXX',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),
	'code_compta_inconnu_VENTE'	=> array( // \Bimp_Product::getCodeComptableVente
		'label' => 'Attention Code comptable inconnu XXX',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),
	'product_validated' => array(	// \Bimp_Product::validateProduct
		'label' => 'Le produit XXX a été validé par ...',
		'type_dest' => 'conf',
		'dest' => 'product_validated_notif_email',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'product_validation_urgente_vente_caisse' => array(	// \Bimp_Product::mailValidation
		'label' => 'Le produit XXX a été ajouté à une vente en caisse. Merci de le valider d\'urgence pour finliser la vente.',
		'type_dest' => 'conf',
		'dest' => 'mail_achat',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'encours_client_modif'	=> array(	//\Bimp_Societe::onNewOutstanding_limit
		'label' => 'L\'encours du client a été modifié. Nouvel encours: xxx €. Ancien encours: xxx €',
		'type_dest' => 'commercial_client',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'solvabilite_client_modif'	=> array(	//\Bimp_Societe::onNewSolvabiliteStatus
		'label' => 'Le client XXX a été mis au statur de la solvabilité xxx',
		'type_dest' => 'commercial_client',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'activation_onoff_on_demand' => array(	// \Bimp_Commande::onNewStatus
		'label' => 'Suite à votre demande, le client XXX a été activer/Désactiver par ...',
		'type_dest' => 'commercial_client',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'demande_modif_status_client_fourn'	=> array(	// \Bimp_Commande::actionStatusChangeDemand
		'label' => 'L\'utilisateur xxx demande l\'activation/la désactivation du client/fournisseur xxx',
		'type_dest' => 'conf',
		'dest' => 'emails_notify_status_client_change',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'action_late_paiement'	=> array(	//	\Bimp_Commande::actionSendMailLatePayment
		'label' => 'Ce client présente un retard de paiement de XXX dont détail ci-après... Vos commandes en cours ne peuvent donc pas recevoir la validation financière.',
		'type_dest' => 'data_formulaire',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'valid_paiement_comptant_ou_sepa' => array( // \Bimp_Commande::onValidate
		'label' => 'La commande XXX a été validée financièrement par paiement comptant ou mandat SEPA par XXX. Merci de vérifier le paiement ultérieurement',
		'type_dest' => 'conf',
		'dest' => 'email_valid_paiement_comptant',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'check_validation_solvabilite' => array( 	//	\BimpComm::checkValidationSolvabilite
		'label' => 'Demande de validation d\'une commande dont le client est au statut XXX',
		'type_dest' => 'conf',
		'dest' => 'solvabilite_validation_emails',
		'module' => 'bimpcommercial',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'notif_facturation_contrat_demain'	=> array(	// \cron::notifDemainFacturation
		'label' => 'Pour rappel, le contrat N° xxx doit être facturé le $demain. ref contrat xxx, Commercial suivi de contrat : XXX',
		'type_dest' => 'conf',
		'dest' => 'email_facturation',
		'module' => 'bimpcontract',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'facture_auto'	=> array(	// \cron::facturation_auto
		'label'	=> 'Une facture a été créée automatiquement. Cette facture est encore au statut brouillon. Merci de la vérifier et de la valider. Client : XXX, contrat : XXX, commercial : XXX',
		'type_dest' => 'conf',
		'dest' => 'email_facturation',
		'module' => 'bimpcontract',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'message_ERP_nonlu'	=> array(	// \BimpNote::cronNonLu
		'label' => 'Vous avez X message(s) non lu. Pour désactiver cette relance, vous pouvez soit répondre au message de la pièce émettrice (dans les notes de pied de page) soit cliquer sur la petite enveloppe "Message" en haut à droite de la page ERP.',
		'type_dest' => 'user_erp',
		'module' => 'bimpcore',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'update_prices_file_marge_neg' => array( // \BDS_ImportsLdlcProcess::executeUpdateFromFile
		'label' => 'Voici la liste des produits avec une marge négative',
		'type_dest' => 'text_perso',
		'dest'	=> 'tommy@bimp.fr',
		'module' => 'bimpdatasync',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'notif_commercial_courrier_retard_regl' => array( // \BDS_RelancesClientsProcess::processNotifsCommerciaux
		'label' => 'LE client XXX va recevoir sous 5 jours une letre de rappel / une mise en demeure concernant les retards de réglement ci-après. Si vous pensez que cette relance/mise en demeure n\'a pas lieu d\'être, merci d\'en informer immédiatement Recouvrement en justifiant votre demande (par exemple : règlement en notre possession, litige client, etc.) ...',
		'type_dest' => 'commercial_client',
		'module' => 'bimpdatasync',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'probleme_stock'	=> array( //	\controlStock::go
		'label'	=> 'Entrepot xxx. ATTENTION +/- d\'équipement (X | xxx xxxx) que de prod (X) total des mouvement ("...")',
		'type_dest' => 'text_perso',
		'dest'	=> 'tommy@bimp.fr',
		'module' => 'bimpequipement',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'paiements_non_identif_auto'	=> array( //	\Bimp_ImportPaiement::toCompteAttente
		'label' => 'Les paiements suivants n\'ont pu être identifiés automatiquement par le système',
		'type_dest' => 'conf',
		'dest' => 'email_paync',
		'module' => 'bimpfinanc',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'react_client_prise_rdv_online' => array( //	\savFormController::ajaxProcessSavFormSubmit
		'label' => 'Le client xxx a été réactivé automatiquement suite à sa prise de rendez-vous SAV en ligne',
		'type_dest' => 'conf',
		'dest' => 'react_client_prise_rdv_online',
		'module' => 'bimpinterfaceclient',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'sav_online_by_client' => array( //	\savFormController::ajaxProcessSavFormSubmit
		'label' => 'Un nouveau SAV a été créé en ligne par un client...',
		'type_dest' => 'centre_sav',
		'module' => 'bimpinterfaceclient',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),



/*
	'creation_ticket_support_view'	=> array( //	views\interfaces
		'label' => 'Ticket Support N° XXX. Sujet du ticket : XXX. Demandeur : XXX. Contrat : XXX',
		'type_dest' => '???????????',
		'dest' => '????????',
		'cc' => 'j.garnier@bimp.fr, l.gay@bimp.fr, tt.cao@bimp.fr',
		'module' => 'bimpinterfaceclient',
		'active' => (int) BimpCore::getConf('bimpsupport', null, 'use_tickets')
	),
	'creation_ticket_support_html'	=> array( //	html\interfaces
		'label' => 'Ticket Support N° XXX. Sujet du ticket : XXX. Demandeur : XXX. Contrat : XXX',
		'type_dest' => '???????????',
		'dest' => '????????',
		'cc' => 'j.garnier@bimp.fr, l.gay@bimp.fr, tt.cao@bimp.fr',
		'module' => 'bimpinterfaceclient',
		'active' => (int) BimpCore::getConf('bimpsupport', null, 'use_tickets')
	),
	'creation_ticket_support'	=> array( //	\BS_Ticket::create
		'label' => 'Ticket Support N° XXX. Sujet du ticket : XXX. Demandeur : XXX. Contrat : XXX',
		'type_dest' => '???????????',
		'dest' => '????????',
		'cc' => 'c.conort@bimp.fr, l.gay@bimp.fr, tt.cao@bimp.fr, d.debarnaud@bimp.fr, v.gaillard@bimp.fr',
		'module' => 'bimpsupport',
		'active' => (int) BimpCore::getConf('bimpsupport', null, 'use_tickets')
	),*/




	'inventaire_incoherence'	=> array( //	\Inventory2::renderHeaderExtraLeft
		'label' => 'Inchoérence detecté dans inventaire : Ln Expected : XXX ...',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimplogistique',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),
	'inventaire_incoherence_scan'	=> array( //	\Inventory2::renderHeaderExtraLeft
		'label' => 'Inchoérence detecté dans les scann de l\'inventaire : Ln Expected : XXX ...',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimplogistique',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'inventaire_incoherence_donnees'	=> array( //	\InventoryExpected::renderEquipments
		'label' => 'ATTENTION INCOHERENCE DES DONNEES',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimplogistique',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'code_BIMP'	=> array( //	\securLogSms::createSendCode
		'label' => 'Le code est XXXX',
		'type_dest' => 'user_erp',
		'module' => 'bimpsecurlogin',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'pb_envoi_ecologic' => array( //	\BimpSupportCronExec::sendEcologic
		'label' => 'Probléme envoie ecologic',
		'type_dest' => 'conf',
		'dest' => 'default_sav_email',
		'module' => 'bimpsupport',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'add_note_by_client' => array( //	\BS_Note::sendNotificationEmails
		'label' => 'Un message a été ajouté sur votre ticket hotline XXX. Message : XXX',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpsupport',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'acceptation_devis_sav'	=> array(	// \BS_SAV::onPropalSigned
		'label' => 'Le devis XXX a été accepté par le client XXX',
		'type_dest' => 'centre_sav',
		'module' => 'bimpsupport',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'refus_devis_sav' => array(		// \BS_SAV::sendMsg
		'label' => 'Notre client XXX a refusé le devis réparation sur son XXX pour un montant de X €',
		'type_dest' => 'usergroup_extrafield',
		'module' => 'bimpsupport',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'accompte_sav_enregitre' => array(	// \BS_SAV::actionAddAcompte
		'label' => 'Un acompte de X € du client XXX a été ajouté pour le devis XXX',
		'type_dest' => 'centre_sav',
		'module' => 'bimpsupport',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'sav_non_restitue_pas_email_client'	=> array(	// \BS_SAV::sendAlertesClientsUnrestituteSav
		'label' => 'Aucune adresse e-mail valide enregistrée pour le client du SAV XXX. Il n\'est donc pas possible d\'alerter le client pour la non restitution de son matériel',
		'type_dest' => 'centre_sav',
		'module' => 'bimpsupport',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'ticket_sav_non_couvert_contrat' => array(	// \BS_Ticket::update
		'label' => 'Le ticket SAV XXX n\'est pas couvert par le contrat',
		'type_dest' => 'commercial_client',
		'module' => 'bimpsupport',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'task_not_exist' => array(	// \webservice_kghjddsljbfldfsvl453454kgg.php
		'label' => 'Un mail a était recu avec une tache inexistante : XXX',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimptask',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'relance_FI_brouillon_Jplus1'	=> array(	// \Cron::relanceTechBrouillonJplus1etPlus
		'label' => 'Voici la liste de vos fiches d’interventions en brouillon dont la date d’intervention est dépassée',
		'type_dest' => 'tech_sav',
		'module' => 'bimptechnique',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'relance_FI_aFact_impoderable' => array(	// \Cron::relanceCommercial
		'label' => 'voici la liste de vos fiches d’interventions en attente de facturation/comportant de l\'impodérable',
		'type_dest' => 'commercial_piece',
		'module' => 'bimptechnique',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'fiche_inter_non_liee' => array(	// \BT_ficheInter::setSigned
		'label' => 'Cette fiche d’intervention a été validée, mais n’est liée à aucune commande et à aucun contrat. Merci de faire les vérifications nécessaires et de corriger si cela est une erreur.',
		'type_dest' => 'text_generic_commercial_client',
		'dest' => 'dispatch@bimp.fr',
		'module' => 'bimptechnique',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'envoi_CR_fiche_inter' => array(	// \BT_ficheInter::setSigned
		'label' => 'Pour information, l\'intervention XXX pour le client XXX (en interne a été signé par le technicien. La FI à été marquée comme terminée automatiquement.)/(n\'a pas pu être envoyée par e-mail au client)/(a été envoyée par e-mail au client) (pour signature électronique à distance.)/(suite à sa signature électronique.)/(pour signature papier à renvoyer par e-mail.)',
		'type_dest' => 'tech_sav_commercial_client',
		'module' => 'bimptechnique',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'notification_facturation_signature_FI'	=> array(	// \BT_ficheInter::actionSendfacturation
		'label' => 'Pour information, la FI xxx pour le client XXX a été signée par le client',
		'type_dest' => 'conf',
		'dest' => 'email_facturation',
		'module' => 'bimptechnique',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'notif_create_FI' => array(	// \BT_ficheInter::create
		'label' => 'Une fiche d\'intervention vous a été attribuée. Fiche d\'intervention: XXX, Date prévue de l\'intervention: d/m/Y',
		'type_dest' => 'tech_sav',
		'module' => 'bimptechnique',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'notif_change_tech_FI' => array(	// \BT_ficheInter::update
		'label' => 'La fiche d\'intervention XXX vous a été attribuée. Ref XXX. Client: XXX. Changement par : XXX. Pour plus de détails rendez-vous sur la fiche d\'intervention',
		'type_dest' => 'tech_sav',
		'module' => 'bimptechnique',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'notif_change_horaire_FI' => array(	// \BT_ficheInter::update
		'label' => 'La fiche d\'intervention XXX a été modifiée au niveau des horaires. Nouveaux horaires du ... au ...',
		'type_dest' => 'tech_sav',
		'module' => 'bimptechnique',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'notif_delete_FI' => array(	// \BT_ficheInter::delete
		'label' => 'La fiche d\'intervention XXX a été supprimée par client XXX. Commandes: ...',
		'type_dest' => 'text_perso_tech_sav',
		'dest'	=> 'v.gilbert@bimp.fr',
		'module' => 'bimptechnique',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'depassement_heure_contrat_delegation'	=> array(	// \BT_ficheInter_det::validate
		'label' => 'XXX a renseigné une ligne dans sa fiche d\'intervention qui crée un dépassement des heures prévues dans le contrat.',
		'type_dest' => 'commercial_piece',
		'module' => 'bimptechnique',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'CEGID_ROLLBACK_compta_aucune_action'	=> array(	// 		\Cron::automatique
		'label' => 'ROLLBACK de la compta. Aucune action n\'à été faites pour remonter la compta d\'Aujourd\'hui, les fichiers ont étés transférés  dans le dossier rollback',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimptocegid',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),
	'CEGID_dossier_export_non_vide'	=> array(	// 		\Cron::automatique
		'label' => 'Dossier d\'export non vide. Rien à été fait...',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimptocegid',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),
	'CEGID_fichier_tra_non_conforme' => array(	// \Cron::checkFiles
		'label' => 'Fichier TRA non conforme',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimptocegid',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),
	'CEGID_impossible_ecrire_fichier' => array(	// \Cron::send_rapport
		'label' => 'ous trouverez en pièce jointe le rapport des exports comptable existant, et voici le nouveau : ',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimptocegid',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),
	'CEGID_erreur_copie_fichier' => array(	// \Cron::FTP
		'label' => 'Le fichier xxx ne s\'est pas copié dans le dossier d\'import',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimptocegid',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),
	'CEGID_export_compta_manuel'	=> array(	// \newExportController::displayHeaderInterface
		'label' => 'Liste des pièces exportées manuellement par XXX ... ',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimptocegid',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'demande_validation_commande'	=> array(	// bimpvalidateorder.class.php
		'label' => 'L\'utilisateur XXX souhaite que vous validiez de la commande XXX du client XXX',
		'type_dest' => 'user_erp',
		'module' => 'bimpvalidateorder',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'rappel_demande_validation_encours' => array(	// \BimpValidateOrderCronExec::sendRappel
		'label' => 'Vous avez X commandes en attente de validation, voici le(s) lien(s)',
		'type_dest' => 'user_erp',
		'module' => 'bimpvalidateorder',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'create_demande_validation_comm' => array(	// \DemandeValidComm::onCreate
		'label' => 'Merci de valider la piece XXX',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpvalidateorder',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'onvalidate_demande_validation_piece' => array(	// \DemandeValidComm::onValidate
		'label' => 'La piece XXX du client a été passée au status X par XXX',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpvalidateorder',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'absence_valideur_secteur'	=> array(	// \ValidComm::createDemande
		'label' => 'Aucun utilisateur ne peut valider commercialement/financierement/les impayés de/sur service la piece XXX (pour le secteur XXX, ...)',
		'type_dest' => 'conf',
		'dest' => 'debugerp_email',
		'module' => 'bimpvalidateorder',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'liste_demandes_validees_et_encours' => array(	// \ValidComm::sendMailValidation
		'label' => 'Liste des demandes validées et en attente de validation',
		'type_dest' => 'user_erp',
		'module' => 'bimpvalidateorder',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'general_valid_paiement_comptant_ou_sepa'	=> array(	// \BimpValidation::checkDemandesAutoAccept
		'label' => 'La piece XXX a été validée financièrement par paiement comptant ou mandat SEPA par XXX. Merci de vérifier le paiement ultérieurement',
		'type_dest' => 'conf',
		'dest' => 'notif_paiement_comptant_email',
		'module' => 'bimpvalidation',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'general_valid_auto' => array( // \BimpValidation::checkObjectValidations
		'label' => 'Toutes les demandes de validation de la piece XXX ont été acceptées...',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpvalidation',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'rappel_demandes_validation' => array(	// \BimpValidationCronExec::sendRappels
		'label' => 'X demandes de validation sont toujours en attente d\'acceptation',
		'type_dest' => 'user_erp',
		'module' => 'bimpvalidation',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'validation_piece_acceptee' => array( // \BV_Demande::setAccepted
		'label' => 'La validation de la piece XXX a été acceptée par XXX',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpvalidation',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'check_affected_user' => array( // \BV_Demande::checkAffectedUser
		'label' => 'La validation de la piece PROVXXX est en attente ...',
		'type_dest' => 'user_erp',
		'module' => 'bimpvalidation',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),
	'validation_piece_refusee' => array( // \BV_Demande::actionRefused
		'label' => 'La validation de la piece XXX a été refusée par XXX',
		'type_dest' => 'commercial_piece',
		'module' => 'bimpvalidation',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	),

	'webservice_error_fatal' => array( // \BimpWebservice::handleError
		'label' => 'ERP XXX. Requête: XXX. Utilisateur WS: ...',
		'type_dest' => 'conf',
		'dest' => 'devs_email',
		'module' => 'bimpwebservice',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'devs'
	),

	'create_inter_ticket'	=> array( // \BS_Inter::create
		'label' => 'Un ticket d\'intervention a été créé sur votre ticket N° XXX',
		'type_dest' => 'commerical_client',
		'module' => 'bimpsupport',
		'active' => 1,
		'required' => 1,
		'canal_diffusion' => 'email',
		'type_metier' => 'metier'
	)
);
