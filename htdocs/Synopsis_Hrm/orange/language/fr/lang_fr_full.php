<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 *
 */

    /**
    * Error messages
    *
    */

    $lang_Error_PleaseCorrectTheFollowing             = "SVP faire la correction suivante";
    $lang_Error_SelectAtLeastOneRecordToDelete         = "Choisir au moins un élément à supprimer";
    $lang_Error_DoYouWantToDelete                     = "Désirez-vous supprimer";
    $lang_Error_EnterDate                            = "Introduire la date ?";

    $lang_Error_PleaseSelectAYear                     = "SVP choisir une année";
    $lang_Error_PleaseSelectAnEmployee                 = "SVP choisir un employé";

    $lang_Error_DoYouWantToContinue                 = "Désirez-vous continuer?";

    $lang_Error_PleaseSelectAnEmployee                = "SVP choisir un employé";

    $lang_Error_ShouldBeNumeric                     = "Doit être numérique";
    $lang_Error_FollowingErrorsWereFound             = "Les erreurs suivantes ont été identifiées";
    $lang_Error_AreYouSureYouWantToDelete             = "Désirez-vous supprimer?";
    $lang_Error_AccessDenied                        = "Accès Refusé";

    //leave
    $lang_Error_PleaseSelectAValidFromDate             = "Choisir une date valide de début";
    $lang_Error_PleaseSelectAValidToDate             = "Choisir une date valide de fin";
    $lang_Error_PleaseSelectALeaveType                 = "Choisir un type d'absence";

    $lang_Error_LeaveDateCannotBeABlankValue         = "Le champ date d'absence ne peut être vide!";
    $lang_Error_NameOfHolidayCannotBeBlank             = "Le champ nom du congé ne peut être vide";

    $lang_Error_NoLeaveTypes                         = "Pas de type d'absence";
    $lang_Error_NoRecordsFound                         = "Aucun élément trouvé!";

    $lang_Error_InvalidDate                         = "Date invalide";

    $lang_Error_NonNumericHours                        = "Le nombre d\'heure doit être numérique";
    $lang_Error_EmailConfigConfirm                        = "Un problème de configuration est apparu. Voulez-vous continuer ?";
    $lang_Error_EmailConfigError_SendmailNotFound                = "Le chemin de sendmail est incorrect. ";
    $lang_Error_EmailConfigError_SendmailNotExecutable            = "SendMail n\'est pas ex&eacute;cutable";
    $lang_Error_EmailConfigError_SmtpHostNotDefined                = "L\'h&ocirc;te SMTP n\'est pas d&eacute;fini";

    //PIM
    $lang_Error_LastNameNumbers                        = "Le nom de famille contient des chiffres. Désirez-vous continuer?";
    $lang_Error_FirstNameNumbers                    = "Le prénom contient des chiffres. Désirez-vous continuer?";
    $lang_Error_MiddleNameNumbers                    = "L\'initiale du nom contient des chiffres. Désirez-vous continuer?";
    $lang_Error_MiddleNameEmpty                        = "Il n\'y a pas d\'initiale. Désirez-vous continuer?";
    $lang_Error_LastNameEmpty                        = "Le nom de famille est vide!";
    $lang_Error_FirstNameEmpty                        = "Le prénom est vide!";
    $lang_Error_ChangePane                            = "Merci de sauvegarder les modifications avant de changer d\'onglet!";

    $lang_Error_UploadFailed                        = "Chargement échoué;!";
    $lang_Errro_WorkEmailIsNotValid                 = "L\'email de travail n\'est pas valide";
    $lang_Errro_OtherEmailIsNotValid                = "L\'autre email n\'est pas valide";

    $lang_Error_DependantNameEmpty                    = "Le nom du dépendant est vide";

    // Company Structure
    $lang_Error_Company_General_Undefined                 = "Merci de définir les informations générales concernant l\'entreprise";
    $lang_Error_CompStruct_UnitCount                     = "Les filiales #children sous #parent seront supprimées";
    $lang_Error_ItCouldCauseTheCompanyStructureToChange = "Ceci pourrait modifier la structure de l\'entreprise";

    $lang_Error_SubDivisionNameCannotBeEmpty             = "Le nom d\'une filiale ne peut être vide";
    $lang_Error_PleaseSelectATypeOrDefineACustomType     = "Merci de choisir ou de définir un type personalisé";
    $lang_Error_CompStruct_LocEmpty                     = "Merci de choisir une succursale ou de définir une nouvelle succursale et appuyer sur Choisir";
    $lang_Error_CompStruct_Dept_Id_Invalid                = "L'\ID du département existe déjà. Merci de saisir un ID différent";

    $lang_Error_LocationNameEmpty                         = "Le nom de la succursale est vide";
    $lang_Error_CountryNotSelected                         = "Le pays n\'a pas été choisi";
    $lang_Error_StateNotSelected                         = "Aucune province ou état n\'a été choisi";
    $lang_Error_CityCannotBeEmpty                         = "La ville ne peut être vide";
    $lang_Error_AddressEmpty                             = "L\'adresse ne peut être vide";
    $lang_Error_ZipEmpty                                 = "Le code postal ne peut être vide";
    $lang_Error_CompStruct_ZipInvalid                     = "Le code postal contient des caractères non numériques. Voici les #characterList";

    $lang_Error_InvalidEmail                             = "Adresse Email non valide";

    $lang_Error_InvalidDescription                        = "Description non valide";

    $lang_Error_FieldShouldBeNumeric                    = "Le champ doit être numérique";

    $lang_Error_FieldShouldBeSelected                    = "Au moins un champ est manquant";
    $lang_Error_SelectAtLeastOneCheckBox                 = "Merci de choisir au moins un élément";

    /**
    * Menu Items
    *
    */

    // Home
    $lang_Menu_Home                             = "Accueil";

    $lang_Menu_Home_Support                     = "Support";
    $lang_Menu_Home_Forum                         = "Forum";
    $lang_Menu_Home_Blog                         = "Blog";

    $lang_Menu_Ess                                = "Ma fiche personnelle";
    $lang_Menu_Pim                                 = "Dossier des employ&eacute;s";

    // Admin Module
    $lang_Menu_Admin                             = "Admin";

    $lang_Menu_Admin_CompanyInfo                 = "Info entreprise";
    $lang_Menu_Admin_CompanyInfo_Gen             = "G&eacute;n&eacute;ral";
    $lang_Menu_Admin_CompanyInfo_CompStruct     = "Structure de l'entreprise";
    $lang_Menu_Admin_Company_Property           = "Biens de l'entreprise";
    $lang_Menu_Admin_CompanyInfo_Locations         = "Succursales";

    $lang_Menu_Admin_Job                         = "Gestion des postes";
    $lang_Menu_Admin_Job_JobTitles                 = "D&eacute;finir les postes";
    $lang_Menu_Admin_Job_JobSpecs               = "Description des t&acirc;ches";
    $lang_Menu_Admin_Job_PayGrades                 = "D&eacute;finir les &eacute;chelles salariales";
    $lang_Menu_Admin_Job_EmpStatus                 = "D&eacute;finir les statuts d'emploi";
    $lang_Menu_Admin_Job_EEO                     = "D&eacute;finir les cat&eacute;gories de poste";

    $lang_Menu_Admin_Quali                         = "Qualifications";
    $lang_Menu_Admin_Quali_Education             = "Formation";
    $lang_Menu_Admin_Quali_Licenses             = "Permis et licenses";

    $lang_Menu_Admin_Skills                     = "Comp&eacute;tences";
    $lang_Menu_Admin_Skills_Skills                 = "Comp&eacute;tences";
    $lang_Menu_Admin_Skills_Languages             = "Langues";

    $lang_Menu_Admin_Memberships                     = "Adh&eacute;sions";
    $lang_Menu_Admin_Memberships_Memberships         = "Adh&eacute;sions";
    $lang_Menu_Admin_Memberships_MembershipTypes     = "Type d'adh&eacute;sion";

    $lang_Menu_Admin_NationalityNRace                 = "Nationalit&eacute;";
    $lang_Menu_Admin_NationalityNRace_Nationality     = "Nationalit&eacute;";
    $lang_Menu_Admin_NationalityNRace_EthnicRaces     = "Origines ethniques";

    $lang_Menu_Admin_Users                             = "Utilisateurs";
    $lang_Menu_Admin_Users_UserGroups                 = "Groupe utilisateurs Admin";
    $lang_Menu_Admin_Users_HRAdmin                     = "Utilisateurs admin";
    $lang_Menu_Admin_Users_ESS                         = "Utilisateurs simple";

    $lang_Menu_Admin_EmailNotifications = "Notifications Email";
    $lang_Menu_Admin_EmailConfiguration = "Configuration";
    $lang_Menu_Admin_EmailSubscribe = "S'abonner &agrave; la notification";
    $lang_Menu_Admin_ProjectInfo = "Info Projet";
    $lang_Menu_Admin_Customers = "Clients";
    $lang_Menu_Admin_Projects = "Projets";
    $lang_Menu_Admin_DataImportExport = "Import/Export Donn&eacute;es";
    $lang_Menu_Admin_DataExport = "Export";
    $lang_Menu_Admin_DataExportDefine = "D&eacute;finir Export Personalis&eacute;";
    $lang_Menu_Admin_DataImport = "Import";
    $lang_Menu_Admin_DataImportDefine = "D&eacute;finir Import Personalis&eacute;";
    $lang_Menu_Admin_CustomFields = "Champs Personnalisables";

    // LDAP Module
    $lang_LDAP_Configuration     = "#ldapType Configuration";
    $lang_Menu_LDAP_Configuration = "LDAP Configuration";
    $lang_LDAP_Server            = "LDAP Serveur";
    $lang_LDAP_Port                = "LDAP Port";
    $lang_LDAP_Domain_Name        = "LDAP Nom de Domaine";
    $lang_LDAP_Suffix            = "LDAP Suffix Utilisateur";
    $lang_LDAP_Type                = "LDAP Type";
    $lang_LDAP_Enable            = "Activer Authentication LDAP";
    $lang_LDAP_Error_Server_Empty    = "SVP saisir le nom du serveur LDAP";
    $lang_LDAP_Error_Domain_Empty    = "SVP saisir le nom de domaine LDAP";
    $lang_LDAP__Error_Extension_Disabled = "Vous n\'avez pas active l\'extension LDAP dans PHP. SVP allez dans guide LDAP d\'OrangeHRM livre avec le plugin pour plus d\'information";
    $lang_LDAP_Invalid_Port            = "Port LDAP non valide";

    // Leave Module
    $lang_Menu_Leave                                     = "Gestion des absences";
    $lang_Menu_Leave_PersonalLeaveSummary                 = "Sommaire absence personnelle";
    $lang_Menu_Leave_EmployeeLeaveSummary                 = "Sommaire des absences employ&eacute;";
    $lang_Menu_Leave_LeaveSummary                         = "Sommaire des absences";
    $lang_Menu_Leave_LeavesList                         = "Liste des absences";
    $lang_Menu_Leave_ApproveLeave                         = "Absence approuv&eacute;e";
    $lang_Menu_Leave_LeaveTypes                         = "D&eacute;finir absences";
    $lang_Menu_Leave_Apply                                 = "Appliquer";
    $lang_Menu_Leave_Assign                                = "Assigner absence";
    $lang_Menu_Leave_LeaveList                             = "Liste des absences";
    $lang_Menu_Leave_MyLeave                             = "Mes Absences";
    $lang_Menu_Leave_DefineDaysOff                         = "D&eacute;finir les cong&eacute;s";
    $lang_Menu_Leave_DefineDaysOff_Weekends             = "Fins de semaine / Weekend";
    $lang_Menu_Leave_DefineDaysOff_SpecificHolidays     = "Cong&eacute;s f&eacute;ri&eacute;s";

    $lang_Leave_Title_Apply_Leave                         = "Appliquer l'absence";
    $lang_Leave_Title_Assign_Leave                         = "Assigner une absence &agrave; un employ&eacute;";
    $lang_Leave_APPLY_SUCCESS                         = "Modification r&eacute;ussies";
    $lang_Leave_APPLY_FAILURE                         = "Erreur - modification &eacute;chou&eacute;e";
    $lang_Leave_APPROVE_SUCCESS                         = "Absence assign&eacute;e";
    $lang_Leave_APPROVE_FAILURE                         = "Assignation de l\'absence &eacute;chou&eacute;e";
    $lang_Leave_CANCEL_SUCCESS = "Annulation r&eacute;ussie";
    $lang_Leave_CANCEL_FAILURE = "Annulation &eacute;chou&eacute;e";
    $lang_Leave_CHANGE_STATUS_SUCCESS = "Le statut d'absence a &eacute;t&eacute; chang&eacute; avec succ&egrave;s";
    $lang_Leave_BALANCE_ZERO = "Votre balance de cong&eacute; est &agrave; z&eacute;ro";

    // Report
    $lang_Menu_Reports = "Rapports";
    $lang_Menu_Reports_ViewReports = "Visualiser rapports";
    $lang_Menu_Reports_DefineReports = "D&eacute;finir rapports";

    // Time module
    $lang_Menu_Time = "Horaire";
    $lang_Menu_Time_Timesheets = "Horaire";
    $lang_Menu_Time_PersonalTimesheet = "Horaire personnel";
    $lang_Menu_Time_EmployeeTimesheets = "Horaire de l'employ&eacute;";

    // Recruitment module
    $lang_Menu_Recruit = "Recrutement";
    $lang_Menu_Recruit_JobVacancies = "Postes Vacants";
    $lang_Menu_Recruit_JobApplicants = "Candidats";

    /**
    * Common
    */
    $lang_Common_ConfirmDelete                = "Désirez-vous supprimer?";
    $lang_Common_FieldEmpty                 = "Champ vide";
    $lang_Common_SelectDelete              = "Choisir au moins un élément à supprimer";
    $lang_Common_SelectField              = "Choisir un champ à rechercher!";
    $lang_Commn_RequiredFieldMark             = "Les champs identifiés par un astérisque #star sont obligatoires";
    $lang_Commn_code                         = "Code";
    $lang_Commn_description                 = "Description";
    $lang_Commn_title                         = "Titre";
    $lang_Commn_name                         = "Nom";
    $lang_Commn_PleaseWait                    = "Merci de patienter";
    $lang_Common_Select                 = "Choisir";
    $lang_Commn_Email                        = "Email";
    $lang_Common_Loading                = "Chargement";
    $lang_Common_LoadingPage                        = "Chargement Page";
    $lang_Common_NotApplicable                      = "N/A";
    $lang_Common_Male                              = "Masculin";
    $lang_Common_Female                            = "F&eacute;minin";
    $lang_Common_TypeHereForHints         = "Saisir un texte pour suggestion...";

    $lang_Common_Edit = "Modifier";
    $lang_Common_New = "Nouveau";
    $lang_Common_Save = "Enregistrer";
    $lang_Common_Back = "Retour";
    $lang_Common_Add = "Ajouter";
    $lang_Common_Delete = "Supprimer";

    $lang_Common_Time = "Heure";
    $lang_Common_Note = "Commentaire";

    $lang_Common_AccessDenied = "Acc&egrave;s Refus&eacute;";

    //days
    $lang_Common_Monday                     = "Lundi";
    $lang_Common_Tuesday                     = "Mardi";
    $lang_Common_Wednesday                     = "Mercredi";
    $lang_Common_Thursday                     = "Jeudi";
    $lang_Common_Friday                     = "Vendredi";
    $lang_Common_Saturday                     = "Samedi";
    $lang_Common_Sunday                     = "Dimanche";

    $lang_Common_Sort_ASC = "Ordre Croissant";
    $lang_Common_Sort_DESC = "Ordre D&eacute;croissant";
    $lang_Common_EncounteredTheFollowingProblems = "Les problèmes suivants ont été rencontrés";

    $lang_Common_ADD_SUCCESS                 = "Ajout R&eacute;ussi";
    $lang_Common_UPDATE_SUCCESS             = "Modification R&eacute;ussie";
    $lang_Common_DELETE_SUCCESS             = "Suppression R&eacute;ussie";
    $lang_Common_ADD_FAILURE                 = "Echec de l'ajout";
    $lang_Common_UPDATE_FAILURE             = "Echec de la mofification";
    $lang_Common_DELETE_FAILURE             = "Echec de la suppression";
    $lang_Common_UNKNOWN_FAILURE            = "Echec de l'op&eacute;ration";
    $lang_Common_DUPLICATE_NAME_FAILURE     = "Nom d&eacute;j&agrave; utilis&eacute;";
    $lang_Common_COMPULSARY_FIELDS_NOT_ASSIGNED_FAILURE = "Champs obligatoires non affect&eacute;s";
    $lang_Common_IMPORT_FAILURE = "Echec de l'import";

    $lang_Leave_Common_Weekend                 = "Fin de semaine";
    // admin module
    $lang_Admin_Common_Institute             = "Nom de l'&eacute;tablissement";
    $lang_Admin_Common_Course                 = "Cours";
    $lang_Admin_education_InstituteCannotBeBlank = "L\'établissement ne peut être vide!";
    $lang_Admin_CourseCannotBeBlank         = "Le cours ne peut être vide!";
    $lang_Admin_License_DescriptionCannotBeBlank = "La description de la licence ne peut être vide!";

    // leave module
    $lang_Leave_Title = "OrangeHRM - Module Absence";
    $lang_Leave_Common_Date                 = "Date";

    $lang_Leave_Common_FromDate             = "De";
    $lang_Leave_Common_ToDate                 = "&Agrave;";

    $lang_Leave_Common_LeaveQuotaNotAllocated = "Le quota d'absence n'est pas allou&eacute;. Merci de contacter le service RH";

    $lang_Leave_Common_LeaveType             = "Type d'absence";
    $lang_Leave_Common_Status                 = "Statut";
    $lang_Leave_Common_Length                 = "Journ&eacute;e compl&egrave;te/Demi-journ&eacute;e";
    $lang_Leave_Common_Range                 = "&Eacute;tendu";
    $lang_Leave_Common_Comments             = "Commentaires";
    $lang_Leave_Common_Comment                 = "Commentaire";
    $lang_Leave_Common_Approved             = "Approuv&eacute;";
    $lang_Leave_Common_Cancelled             = "Annul&eacute;";
    $lang_Leave_Common_Cancel                 = "Annuler";
    $lang_Leave_Common_PendingApproval         = "Approbation en attente";
    $lang_Leave_Common_Rejected             = "Refus&eacute;";
    $lang_Leave_Common_Taken                 = "Pris";
    $lang_Leave_Common_InvalidStatus         = "Statut invalide";
    $lang_Leave_Common_StatusDiffer         = "Statut Diff&eacute;r&eacute;";
    $lang_Leave_Common_FullDay                 = "Journ&eacute;e compl&egrave;te";
    $lang_Leave_Common_HalfDayMorning         = "Demi-journ&eacute;e - AM";
    $lang_Leave_Common_HalfDayAfternoon     = "Demi--journ&eacute;e - PM";
    $lang_Leave_Common_HalfDay                 = "Demi-journ&eacute;e";
    $lang_Leave_Common_LeaveTaken             = "Absences prises";
    $lang_Leave_Common_LeaveRemaining         = "Absences restantes";
    $lang_Leave_Common_LeaveScheduled         = "Absences pr&eacute;vues";
    $lang_Leave_Common_LeaveTypeName         = "Type d'absence";
    $lang_Leave_Common_LeaveTypeId             = "ID absence";
    $lang_Leave_Common_Select                 = "Choisir";
    $lang_Leave_Common_oldLeaveTypeName     = "Type d\'absence actuelle";
    $lang_Leave_Common_newLeaveTypeName     = "Nouveau type d\'absence";
    $lang_Leave_Common_EmployeeName         = "Nom de l'employ&eacute;";
    $lang_Leave_Common_LeaveEntitled         = "Absences en banque";
    $lang_Leave_Common_Year                 = "Ann&eacute;e";
    $lang_Leave_Common_ListOfTakenLeave     = "Liste des absences prises";
    $lang_Leave_Common_Remove                 = "Enlever";
    $lang_Leave_Common_AllEmployees            = "Tous les employ&eacute;s";
    $lang_Leave_Common_All                    = "Tous";
    $lang_Leave_Common_InvalidDateRange     = "Intervalle de date non valide";

    $lang_Leave_Common_NameOfHoliday         = "Nom du jour f&eacute;ri&eacute;";
    $lang_Leave_Common_Recurring             = "A chaque ann&eacute;e";

    $lang_Leave_Leave_list_Title1             = "Les absences approuv&eacute;es";
    $lang_Leave_all_emplyee_leaves            = "Liste d'absences";
    $lang_Leave_all_emplyee_taken_leaves    = "Absences prises";
    $lang_Leave_Leave_Requestlist_Title1     = "Approuv&eacute; la demande d'absence pour #employeeName";
    $lang_Leave_Leave_Requestlist_Title2     = "Changer demande d'absence pour #employeeName";
    $lang_Leave_Leave_list_Title2             = "Absence prise par #employeeName en #dispYear";
    $lang_Leave_Leave_list_Title3             = "Liste d'absences";
    $lang_Leave_Leave_list_Title4             = "Absence pr&eacute;vue";
    $lang_Leave_Leave_list_TitleAllEmployees= "Liste d'absences (Tous les employ&eacute;s)";
    $lang_Leave_Leave_list_TitleMyLeaveList = "Ma Liste d'absences";
    $lang_Leave_Leave_list_Title5             = "Absences prises";
    $lang_Leave_Leave_list_ShowLeavesWithStatus = "Montrer les absences et leur statut";
    $lang_Leave_Leave_list_SelectAtLeastOneStatus = "Choisir au moins un statut d\'absence";
    $lang_Leave_Leave_list_From             = "De";
    $lang_Leave_Leave_list_To               = "&Agrave;";
    $lang_Leave_Select_Employee_Title         = "Choisir l'employ&eacute;";
    $lang_Leave_Leave_Summary_Title         = "Sommaire d'absences";
    $lang_Leave_Leave_Summary_EMP_Title     = "Sommaire d'absences pour #employeeName en #dispYear";
    $lang_Leave_Select_Employee_Title         = "Choisir l'employ&eacute; ou le type d'absence";
    $lang_Leave_Leave_Summary_EMP_Title     = "Sommaire d'absences en #dispYear";
    $lang_Leave_Leave_Summary_SUP_Title     = "Sommaire d'absences pour #employeeName en #dispYear";
    $lang_Leave_Define_leave_Type_Title     = "D&eacute;finir type d'absence ";
    $lang_Leave_Leave_Type_Summary_Title     = "Type d'absence";
    $lang_Leave_Leave_Holiday_Specific_Title = "D&eacute;finir les journ&eacute;es de cong&eacute; : Jours f&eacute;ri&eacute;s";
    $lang_Leave_Leave_Holiday_Weeked_Title     = "D&eacute;finir les journ&eacute;es de cong&eacute; : Fin de semaine";
    $lang_Leave_Summary_Deleted_Types_Shown = "D&eacute;noter les types d\'absence supprim&eacute;s";
    $lang_Leave_Summary_Deleted_Types_MoreInfo = "La liste ci-dessous montre les types d'absences qui ont &eacute;t&eacute; supprim&eacute; et pr&eacute;c&eacute;demmment utilis&eacute;s par les employ&eacute;s. Les types d\'absences supprim&eacute;s sont retenus dans le syst&egrave;me mais ne peuvent &ecirc;tre utilis&eacute; pour les nouvelles demandes de cong&eacute;.";

    $lang_Leave_Holiday = "Cong&eacute; f&eacute;ri&eacute;";
    $lang_Leave_NoOfDays = "Nb de jours";
    $lang_Leave_NoOfHours = "Nb d'heures";
    $lang_Leave_Period = "P&eacute;riode d'absence";
    $lang_Leave_Closed = "Ferm&eacute;";

    $lang_Leave_Define_IsDeletedName      = "Supprimer les types d\'absence de m&ecirc;me nom.";
    $lang_Leave_Define_UndeleteLeaveType    = "Pour r&eacute;utiliser les types d\'absence supprim&eacute;s les resaisir au lieu de cr&eacute;er un nouveau type, puis cliquer:";
    $lang_Leave_NAME_IN_USE_ERROR         = "Ce type d'absence est en utilisation. Merci d'en choisir un autre.";
    $lang_Leave_ADD_FAILURE            = "Echec de l'ajout";
    $lang_Leave_ADD_SUCCESS         = "Ajout R&eacute;ussi";
    $lang_Leave_LEAVE_TYPE_NOT_FOUND_ERROR     = "Type d\'absence introuvable";
    $lang_Leave_UNDELETE_SUCCESS        = "Type d\'absence reconstituer avec succ&egrave;s.";
    $lang_Leave_DUPLICATE_LEAVE_TYPE_ERROR  = "Duplication de type d\'absence trouv&eacute;. Le type d\'absence doit &ecirc;tre unique.";
    $lang_Leave_LEAVE_TYPE_EDIT_ERROR       = "Erreur d\'enregistrement des modifications";
    $lang_Leave_LEAVE_TYPE_EDIT_SUCCESS     = "Modification enregistr&eacute;e avec succ&egrave;s";
    $lang_Leave_NO_CHANGES_TO_SAVE_WARNING  = "Pas de changement &agrave; enregistrer";
    $lang_Leave_Undelete            = "Reconstituer";

    $lang_Leave_Summary_Error_CorrectLeaveSummary    = "Erreur(s) dans le resum&eacute; d\'absence !\\n Merci de corriger les quotas d\'absence en surbrillance.";
    $lang_Leave_Summary_Error_NonNumericValue        = "Non num&eacute;rique";
    $lang_Leave_Summary_Error_InvalidValue            = "Valeur non-valide";

    $lang_bankInformation_code                 = "Code";
    $lang_bankInformation_description         = "Description";

    $lang_compstruct_add             = "Ajouter";
    $lang_compstruct_delete         = "Supprimer";
    $lang_compstruct_clear             = "Effacer les champs";
    $lang_compstruct_hide             = "Cacher";
    $lang_compstruct_save             = "Sauvegarder";

    $lang_comphire_heading                             = "Structure hi&eacute;archique de l'entreprise: Information sur l'entreprise";
    $lang_comphire_relationalhierarchy                 = "Relation hi&eacute;rarchique";
    $lang_comphire_employee                         = "Employ&eacute;";
    $lang_comphire_definitionlevel                     = "D&eacute;finition du niveau";
    $lang_comphire_telephone                         = "T&eacute;l&eacute;phone";
    $lang_comphire_fax                                 = "Fax";
    $lang_comphire_email                            = "Email";
    $lang_comphire_url                                 = "Adresse web";
    $lang_comphire_logo                             = "Logo";
    $lang_comphire_selecthie                         = "Choisir la hi&eacute;rarchie";
    $lang_comphire_selectdef                         = "Choisir la d&eacute;finition du niveau";
    $lang_compstruct_heading                         = "Information sur la structure de l'entreprise";
    $lang_compstruct_Dept_Id                        = "ID du d&eacute;partement:";
    $lang_compstruct_frmSub_divisionHeadingAdd         = "Ajouter une filiale &agrave;";
    $lang_compstruct_frmSub_divisionHeadingEdit     = "&Eacute;diter";
    $lang_compstruct_Name                             = "Nom";
    $lang_compstruct_Type                             = "Type";
    $lang_compstruct_Division                         = "D&eacute;partement";
    $lang_compstruct_Description                     = "Description";
    $lang_compstruct_Department                     = "D&eacute;partement";
    $lang_compstruct_Team                             = "&Eacute;quipe";
    $lang_compstruct_Other                             = "Autre";
    $lang_compstruct_Location                         = "Succursale";
    $lang_compstruct_frmNewLocation                 = "D&eacute;finir une nouvelle succursale";
    $lang_compstruct_Address                         = "Adresse";
    $lang_compstruct_country                        = "Pays";
    $lang_compstruct_state                             = "Province / &Eacute;tat";
    $lang_compstruct_city                             = "Ville";
    $lang_compstruct_ZIP_Code                         = "Code postal";
    $lang_compstruct_Phone                             = "T&eacute;l&eacute;phone";
    $lang_compstruct_no_root                         = "Racine non trouvée! Merci de définir la racine.";

    $lang_corptit_heading                             = "Titre: Information sur le poste";
    $lang_corptit_topinhierachy                     = "Sommet de la hi&eacute;rarchie";
    $lang_corptit_multipleheads                     = "Plusieurs t&ecirc;tes";
    $lang_corptit_headcount                         = "Nombre de t&ecirc;tes";
    $lang_corptit_nextlevelupgrade                     = "Mise &agrave; jour prochain niveau";
    $lang_corptit_selectcor                         = "Choisir le titre";
    $lang_corptit_salarygrade                         = "&Eacute;chelle salariale";
    $lang_corptit_selectsal                         = "Choisir l\'&eacute;chelle salariale";

    $lang_costcenters_heading                         = "Centre des co&ucirc;ts : Information sur l'entreprise";

    $lang_countryinformation_heading                 = "Information sur le pays : Information G&eacute;ographique";

    $lang_currencytypes_heading                     = "Type de devises : Information sur le poste";


    $lang_districtinformation_heading                 = "Information sur la ville : Information G&eacute;ographique";
    $lang_districtinformation_selectcounlist         = "Choisir le pays";
    $lang_districtinformation_selstatelist             = "Choisir la province / l'&Eacute;tat";

    $lang_eeojobcat_heading                         = "Gestion des postes : D&eacute;finir les cat&eacute;gories de poste";
    $lang_eeojobcat_description                     = "Titre";
    $lang_eeojobcat_TitleContainsNumbers            = "Le titre contient des chiffres. Voulez-vous continuer ?";
    $lang_eeojobcat_TitleMustBeSpecified            = "Le titre doit être défini.";

    $lang_electorateinformation_heading             = "Information sur l\'&eacute;lectorat : Information G&eacute;ographique";
    $lang_emprepinfo_heading                        = "D&eacute;finir les rapports employ&eacute;s";

    $lang_emptypes_heading                     = "Type d\'employ&eacute; : Information Nexus";
    $lang_emptypes_datelimited                 = "Date limit&eacute;e";
    $lang_emptypes_prefix                     = "Pr&eacute;fixe";

    $lang_empview_heading                     = "Gestion des postes : D&eacute;finir les statuts d'emploi";
    $lang_empview_EmpID                     = "ID Empl.";
    $lang_empview_EmpFirstName                 = "Pr&eacute;nom Empl.";
    $lang_empview_EmpLastName                 = "Nom de famille Empl.";
    $lang_empview_EmpMiddleName             = "Initiale Empl.";
    $lang_empview_search                    = "Recherche";
    $lang_empview_searchby                     = "Recherche par:";
    $lang_empview_description                 = "Recherche:";
    $lang_empview_norecorddisplay             = "Pas d'&eacute;l&eacute;ment &agrave; afficher";
    $lang_empstatus_PleaseEnterEmploymentStatus = "Merci de saisir la description du statut de l\'employé";
    $lang_empview_SelectField               = "Merci de choisir le champ de recherche!";//Select the field to search!";

    $lang_empview_last                         = "Dernier";
    $lang_empview_next                         = "Suivant";
    $lang_empview_previous                     = "Pr&eacute;c&eacute;dent";
    $lang_empview_first                        = "D&eacute;but";

    $lang_empview_employeeid                 = "ID Employ&eacute;";
    $lang_empview_employeename                 = "Nom de l'employ&eacute;";
    $lang_empview_ADD_SUCCESS                 = "Ajout r&eacute;ussi";
    $lang_empview_UPDATE_SUCCESS             = "Mise &agrave; jour r&eacute;ussie";
    $lang_empview_DELETE_SUCCESS             = "Suppression r&eacute;ussie";
    $lang_empview_ADD_FAILURE                 = "&Eacute;chec de l'ajout";
    $lang_empview_DUPLICATE_EMPCODE_FAILURE    = "&Eacute;chec de l'ajout. Code employ&eacute; en doublon"; //Failed to Add. Duplicate employ&eacute;e Code Entered.";
    $lang_empview_SELF_SUPERVISOR_FAILURE     = "Vous ne pouvez pas vous ajouter vous m&ecirc;me comme superviseur";//You can not add yourself as a supervisor to you";
    $lang_empview_UPDATE_FAILURE             = "&Eacute;chec de la mise &agrave; jour";
    $lang_empview_DELETE_FAILURE             = "&Eacute;chec de la suppression";
    $lang_empview_Language                     = "Langue";
    $lang_empview_WorkExperience             = "Exp&eacute;rience de travail";
    $lang_empview_Payment                     = "Paiement";
    $lang_empview_Skills                     = "Comp&eacute;tences";
    $lang_empview_Licenses                     = "Permis ou Licences";
    $lang_empview_EmployeeInformation         = "Information sur l'employ&eacute;";
    $lang_empview_Memberships                 = "Adh&eacute;sion";
    $lang_empview_Report                     = "Sup&eacute;rieur hi&eacute;rarchique";
    $lang_empview_ReportTo                    = "Se rapporte &agrave;";
    $lang_empview_SubDivision                = "D&eacute;partement";
    $lang_empview_JobTitle                    = "Titre du poste";
    $lang_empview_Supervisor                 = "Superviseur";
    $lang_empview_EmploymentStatus            = "Statut d'emploi";
    $lang_emppop_title                      = "Recherche d'employ&eacute;s";

    $lang_ethnicrace_heading                 = "Nationalit&eacute;";
    $lang_ethnicrace_NameShouldBeSpecified  = "Origines ethniques doit &ecirc;tre d&eacute;fini"; // Ethnic Race Name should be specified";

    $lang_extracurractcat_heading             = "Autre cat&eacute;gories d\'activit&eacute;s externes : Information sur les comp&eacute;tences acquises";

    $lang_extracurractinfo_heading             = "Activit&eacute; externes : Information sur les comp&eacute;tences acquises";
    $lang_extracurractinfo_extracuaccat     = "Ajout de cat&eacute;gories : Activit&eacute;s externes";
    $lang_extracurractinfo_selectsecucat     = "Choisir cat. act. ext.";

    $lang_geninfo_heading                     = "Information g&eacute;n&eacute;rale sur l'entreprise";
    $lang_geninfo_compname                     = "Nom de l'entreprise";
    $lang_geninfo_numEmployees                = "Nombre d'employ&eacute;s";
    $lang_geninfo_taxID                     = "Num&eacute;ro de taxes";
    $lang_geninfo_naics                     = "Code SIC";
    $lang_geninfo_err_CompanyName             = "Le nom de l\'entreprise ne peut être vide";
    $lang_geninfo_err_Phone                 = "Téléphone / Fax n\'est pas valide";
    $lang_geninfo_err_CommentLengthWarning    = "La longueur du commentaire exc&egrave;de la limite. Le texte &agrave; la fin du commentaire sera perdu.";

    $lang_hierarchydef_heading                 = "Information hi&eacute;rarchique : Information sur l\'entreprise";

    $lang_hremp_EmpFirstName                 = "Pr&eacute;nom";
    $lang_hremp_EmpLastName                 = "Nom de famille";
    $lang_hremp_EmpMiddleName                 = "Initiale";
    $lang_hremp_nickname                     = "Alias";
    $lang_hremp_photo                         = "Photo ";
    $lang_hremp_ssnno                         = "Num de s&eacute;cu :";
    $lang_hremp_nationality                 = "Nationnalit&eacute;";
    $lang_hremp_sinno                         = "Autre pi&egrave;ce ID :";
    $lang_hremp_dateofbirth                 = "Date de naissance";
    $lang_hremp_otherid                     = "Autre pi&egrave;ce ID2 :";
    $lang_hremp_maritalstatus                 = "Statut marital";
    $lang_hremp_selmarital                    = "-- Choisir --";
    $lang_hremp_selectnatio                 = "Choisir la nationnalit&eacute;";
    $lang_hremp_selethnicrace                 = "";
    $lang_hremp_smoker                         = "";
    $lang_hremp_gender                         = "Sexe";
    $lang_hremp_dlicenno                     = "Permis de conduire : ";
    $lang_hremp_licexpdate                     = "Expiration du permis ";
    $lang_hremp_militaryservice             = "Service militaire ";
    $lang_hremp_ethnicrace                     = "Origine ethnique ";
    $lang_hremp_jobtitle                     = "Titre du poste";
    $lang_hremp_selempstat                     = "Choisir statut empl.";
    $lang_hremp_jobspec                     = "Description de poste";
    $lang_hremp_jobspecduties               = "Description de t&acirc;che";
    $lang_hremp_eeocategory                 = "Cat&eacute;gorie";
    $lang_hremp_seleeocat                     = "Choisir la cat&eacute;gogie de poste";
    $lang_hremp_joindate                     = "Date d'embauche";
    $lang_hremp_termination_date            = "Date de d&eacute;part";
    $lang_hremp_termination_reason            = "Raison de d&eacute;part";
    $lang_hremp_SelectJobTitle                = "Choisir le titre du poste";
    $lang_hremp_EmpStatus                    = "Statut de l'emploi";
    $lang_hremp_Workstation                    = "Poste de travail";
    $lang_hremp_Subdivision                    = "D&eacute;partement";
    $lang_hremp_Locations                   = "Succursales";

    $lang_hremp_dependents                     = "D&eacute;pendants";
    $lang_hremp_children                     = "Enfants";
    $lang_hremp_relationship                 = "Lien de parent&eacute;";

    $lang_hremp_street1                        = "Adresse1";
    $lang_hremp_street2                     = "Adresse2";

    $lang_hremp_hmtele                         = "T&eacute;l. - R&eacute;sidence";
    $lang_hremp_mobile                         = "T&eacute;l. - Mobile";
    $lang_hremp_worktele                     = "T&eacute;l. - Travail";
    $lang_hremp_city                         = "Ville";
    $lang_hremp_workemail                     = "Email - Travail";
    $lang_hremp_otheremail                     = "Email - Autre";
    $lang_hremp_passport                     = "Passeport";
    $lang_hremp_visa                         = "Visa";
    $lang_hremp_citizenship                 = "Nationnalit&eacute;";
    $lang_hremp_passvisano                     = "Num. de passeport/Visa";
    $lang_hremp_issueddate                     = "&Eacute;mission";
    $lang_hremp_i9status                     = "Statut I9";
    $lang_hremp_dateofexp                     = "Expiration";
    $lang_hremp_i9reviewdate                 = "R&eacute;vision I9";
    $lang_hremp_path                         = "Chemin";
    $lang_hremp_filename                     = "Nom de fichier";
    $lang_hremp_size                         = "Taille";
    $lang_hremp_type                         = "Type";
    $lang_hremp_name                         = "Nom";
    $lang_hremp_InvalidPhone                = "N\'est pas un num&eacute;ro de t&eacute;l&eacute;phone/fax valide";
    $lang_hremp_largefileignore                = "Taille maximum 1 mega, les autres seront ignor&eacute;s";
    $lang_hremp_PleaseSelectFile                = "Merci de choisir un fichier";
    $lang_hremp_ShowFile                    = "Afficher Fichier";
    $lang_hremp_Save                    = "Enregistrer";
    $lang_hremp_Delete                    = "Supprimer";
    $lang_lang_uploadfailed                    = "Le t&eacute;l&eacute;chargement a &eacute;chou&eacute;";

    $lang_hremp_browse                        = "Parcourir";

    $lang_hremp_AreYouSureYouWantToDeleteThePhotograph = "D&eacute;sirez-vous supprimer cette photo ?";

    $lang_hremp_SelectAPhoto                = "Choisir une photo";
    $lang_hremp_PhotoMaxSize                = "1 mega Max";
    $lang_hremp_PhotoDimensions                = "Dimensions 100x120";

    $lang_hremp_IssedDateShouldBeBeforeExp    = "La date &eacute;mise devrait &ecirc;tre avant la date d\'expiration";
    $lang_hremp_FromDateShouldBeBeforeToDate= "La date actuelle devrait &ecirc;tre avant la date future";
    $lang_hremp_StaringDateShouldBeBeforeEnd= "La date de d&eacute;but devrait &ecirc;tre avant la date de fin";

    $lang_hremp_ContractExtensionStartDate    = "D&eacute;but du contrat";
    $lang_hremp_ContractExtensionEndDate    = "Fin du contrat";
    $lang_hremp_EmployeeContracts             = "Contrats de l\'employ&eacute;";
    $lang_hremp_AssignedContracts            = "Historique des contrats assign&eacute;s";

    $lang_hremp_ShowEmployeeContracts             = "Afficher les contrats de l\'employ&eacute;s";
    $lang_hremp_HideEmployeeContracts             = "Cacher les contrats de l\'employ&eacute;";

    $lang_hremp_ShowEmployeeJobHistory      = "Afficher l'historique l'employ&eacute;";
    $lang_hremp_HideEmployeeJobHistory      = "Cacher l'historique de l'employ&eacute;";

    $lang_hremp_EmployeeJobHistory          = "Historique de l'empoy&eacute;";
    $lang_hremp_EmployeePreviousPositions   = "Postes pr&eacute;c&eacute;dents";
    $lang_hremp_EmployeePreviousSubUnits    = "Pr&eacute;c&eacute;dents Services";
    $lang_hremp_EmployeePreviousLocations   = "Pr&eacute;c&eacute;dentes Succursales";
    $lang_hremp_EmployeeHistoryFrom         = "De";
    $lang_hremp_EmployeeHistoryTo           = "&Agrave;";
    $lang_hremp_EmployeeHistoryNoItemsFound = "Aucun &eacute;l&eacute;ment trouv&eacute;";
    $lang_hremp_EmployeeAddHistoryItem      = "Ajouter historique employ&eacute;";
    $lang_hremp_EmployeeJobTitleOption      = "Titre du poste";
    $lang_hremp_EmployeeSubDivisionOption      = "Division";
    $lang_hremp_EmployeeLocationOption      = "Succursale";
    $lang_hremp_EmployeeHistory_PleaseSelectJobTitle = "Merci de choisir le titre du poste";
    $lang_hremp_EmployeeHistory_PleaseSelectSubDivision = "Merci de choisir une division";
    $lang_hremp_EmployeeHistory_PleaseSelectLocation = "Merci de choisir une succursale";
    $lang_hremp_EmployeeHistory_StartShouldBeforeEnd = "La date d&eacute;but doit &ecirc;tre avant la date fin";
    $lang_hremp_EmployeeHistory_PleaseSpecifyStartDate = "Merci de saisir une date de d&eacute;but valide";
    $lang_hremp_EmployeeHistory_PleaseSpecifyEndDate = "Merci de saisir une date de fin valide";

    $lang_hremp_EmployeeHistory_DatesWrong = "Merci de saisir des dates valides. La date de d&eacute;but ne peut &ecirc;tre apr&egrave;s la date de fin";
    $lang_hremp_EmployeeHistory_ExpectedDateFormat = "Le format de date attendu: ";
    $lang_hremp_EmployeeHistory_DatesWithErrorsHighlighted = "Les dates en erreurs sont en rouge";

    $lang_hremp_AddLocation                 = "Ajouter succursale";
    $lang_hremp_SelectLocation              = "Choisir succursale";
    $lang_hremp_PleaseSelectALocationFirst  = "Merci de choisir un site d\'abord";
    $lang_hremp_ErrorAssigningLocation      = "Erreur d\'affectation de succursale";
    $lang_hremp_ErrorRemovingLocation      = "Erreur de suppression de succursale";

    $lang_hremp_SalaryShouldBeWithinMinAndMa= "Le salaire doit &ecirc;tre entre le minimum et le maximum";

    $lang_hremp_SelectCurrency                = "Choisir la devise";
    $lang_hremp_SelectPayGrade                = "Choisir &eacute;chelle salariale";

    $lang_hremp_ContractExtensionId            = "ID contrat";
    $lang_hremp_ContractStartDate            = "D&eacute;but du contrat";
    $lang_hremp_ContractEndDate                = "Fin du contrat";
    $lang_hremp_FieldShouldBeNumeric        = "Les champs doivent &ecirc;tre num&eacute;riques";

    $lang_hremp_Language                    = "Langue";

    $lang_hremplan_employeelanguageflu         = "Niveau en langues &eacute;trang&eagrave;res";
    $lang_hremplan_fluency                     = "Type";
    $lang_hremplan_ratinggarde                 = "Niveau";
    $lang_hremplan_assignlanguage             = "Langues assign&eacute;es";

    $lang_hremplan_SelectLanguage            = "Choisir la langue";
    $lang_hremplan_SelectFluency            = "Choisir le type";
    $lang_hremplan_SelectRating             = "Choisir le niveau";

    $lang_hremp_PleaseSelectJobTitle= "Merci de choisir d\'abord un poste pour cet employ&eacute; {ici}";

    $lang_hremp_ie_CurrentSupervisors         = "ex: Superviseurs actuels";
    $lang_hremp_ie_CurrentSubordinates         = "ex: Subordonn&eacute;s actuels";

    $lang_hremp_ie_PleaseSpecifyAtLeastOnePhoneNo = "Merci de choisir au moins un numéro de téléphone";

    $lang_hremplicenses_employeelicen         = "Permis ou licences de l\'employ&eacute;";
    $lang_hremplicenses_assignlicenses         = "Permis ou Licenses assign&eacute;s";
    $lang_hremplicenses_licentype             = "Type de permis ou licences";
    $lang_hremplicenses_assignlicen         = "Permis ou licences assign&eacute;s";

    $lang_hremplicenses_NoLicenseSelected    = "Pas de licence choisie";
    $lang_hremplicenses_SelectLicenseType    = "Choisir type de licence";

    $lang_hrempmemberships_NoMembershipTypeSelected            = "Pas de type d\'adh&eacute;sion choisie";
    $lang_hrempmemberships_NoMembershipSelected            = "Pas d\'adh&eacute;sion choisie";
    $lang_hrempmemberships_NoSubscriptionOwnerSelected    = "Pas d\'abonnement choisi";

    $lang_hremp_SelectAtLEastOneAttachment = "Choisir au moins un fichier &agrave; supprimer";

    $lang_hrEmpMain_ratinggarde             = "Niveau de comp&eacute;tence";
    $lang_hrEmpMain_assignlanguage             = "Langue assign&eacute;e";
    $lang_hrEmpMain_Writing                 = "&Eacute;crit";
    $lang_hrEmpMain_Speaking                 = "Parl&eacute;";
    $lang_hrEmpMain_Reading                 = "Lu";
    $lang_hrEmpMain_Poor                     = "Faible";
    $lang_hrEmpMain_Basic                     = "Moyen";
    $lang_hrEmpMain_Good                     = "Bon";
    $lang_hrEmpMain_MotherTongue             = "Langue maternelle";
    $lang_hrEmpMain_Individual                 = "Individuel";
    $lang_hrEmpMain_employeemembership         = "Adh&eacute;sion de l'employ&eacute;";
    $lang_hrEmpMain_subownership             = "Propri&eacute;taire de l'abonnement";
    $lang_hrEmpMain_subamount                 = "Co&ucirc;t de l\'abonnement";
    $lang_hrEmpMain_subcomdate                 = "D&eacute;but";
    $lang_hrEmpMain_subredate                 = "Expiration";
    $lang_hrEmpMain_selmemtype                 = "Choisir le type d'adh&eacute;sion";
    $lang_hrEmpMain_selmemship                 = "Choisir l'adh&eacute;sion";
    $lang_hrEmpMain_selownership             = "Choisir &agrave; qui appartient l'adh&eacute;sion";
    $lang_hrEmpMain_assignmemship             = "Adh&eacute;sion assign&eacute;e";
    $lang_hrEmpMain_paygrade                 = "&Eacute;chelle salariale";
    $lang_hrEmpMain_currency                 = "Devise";
    $lang_hrEmpMain_minpoint                 = "Salaire : Minimum";
    $lang_hrEmpMain_maxpoint                 = "Salaire: Maximum";
    $lang_hrEmpMain_bassalary                 = "Salaire de base";
    $lang_hrEmpMain_assignedsalary             = "Salaire assign&eacute;";
    $lang_hrEmpMain_payfrequency             = "Fr&eacute;quence de paie";

    $lang_hrEmpMain_heading                 = "Employ&eacute; : rapport de supervision";
    $lang_hrEmpMain_supervisorsubordinator     = "Superviseur / Subordonn&eacute;";
    $lang_hrEmpMain_reportingmethod         = "Type de rapport";
    $lang_hrEmpMain_subordinateinfomation     = "Subordonn&eacute;s";
    $lang_hrEmpMain_supervisorinfomation     = "Superviseurs";
    $lang_hrEmpMain_selecttype                 = "Choisir le type de rapport de supervision";
    $lang_hrEmpMain_Direct                     = "Direct";
    $lang_hrEmpMain_Indirect                 = "Indirect";
    $lang_hrEmpMain_employeeskill             = "Capacit&eacute;s et comp&eacute;tences de l\'employ&eacute;";
    $lang_hrEmpMain_yearofex                 = "Ann&eacute;es d'exp&eacute;rience";
    $lang_hrEmpMain_assignskills             = "Comp&eacute;tences assign&eacute;es";
    $lang_hrEmpMain_employerworkex             = "Exp&eacute;rience de travail de l\'employ&eacute;";
    $lang_hrEmpMain_employer                 = "Employeur";
    $lang_hrEmpMain_enddate                 = "Date fin";
    $lang_hrEmpMain_startdate                 = "Date d&eacute;but";
    $lang_hrEmpMain_assignworkex             = "Exp&eacute;rience de travail assign&eacute;";
    $lang_hrEmpMain_workexid                 = "ID Emploi";
    $lang_hrEmpMain_internal                 = "Interne";
    $lang_hrEmpMain_major                     = "Majeur/Sp&eacute;cialisation";
    $lang_hrEmpMain_education                 = "Formation";
    $lang_hrEmpMain_gpa                     = "R&eacute;ultats GPA";
    $lang_hrEmpMain_assigneducation         = "Formation assign&eacute;e";
    $lang_hrEmpMain_assignattach             = "Fichiers au dossier de l'employ&eacute;";

    $lang_hrEmpMain_SelectEducation            = "Choisir Formation";
    $lang_hrEmpMain_YearsOfExperiencCannotBeBlank = "Le nombre d\'années d\'expérience ne peut être vide";
    $lang_hrEmpMain_YearsOfExperiencWrongFormat = "Le champ années d\'expérience doit être numérique ";
    $lang_hrEmpMain_YearsOfExperiencBetween = "Le nombre d\'années d'expérience semble incorrect";
    $lang_hrEmpMain_Skill                     = "Comp&eacute;tences";
    $lang_hrEmpMain_EnterFromDateFirst      = "Saisir la date de début d\'abord";

    $lang_hrEmpMain_subown_Company = 'Entreprise';
    $lang_hrEmpMain_subown_Individual = 'Individuel';

    $lang_hrEmpMain_arrRepType_Supervisor = 'Superviseur';
    $lang_hrEmpMain_arrRepType_Subordinate = 'Subordonn&eacute;';

    $lang_hrEmpMain_arrRepMethod_Direct = 'Direct';
    $lang_hrEmpMain_arrRepMethod_Indirect = 'Indirect';

    $lang_hrEmpMain_SelectMethod = 'Choisir une m&eacute;thode';

    $lang_hrEmpMain_SubscriptionAmountShouldBeNumeric = "Le montant de l\'abonnement doit &ecirc;tre num&eacute;rique";
    $lang_hrEmpMain_memebershipSubAmountIsEmptyContinue = "Le montant de l\'abonnement est vide. D&eacute;sirez-vous continuer ?";

    $lang_hrEmpMain_CommenceDateShouldBeBeforeRenewalDate = "La date de d&eacute;but doit &ecirc;tre avant la date de renouvellement";

    $lang_hrEmpMain_membershiptype = "Type d'adh&eacute;sion";
    $lang_hrEmpMain_membership = "Adh&eacute;sion";

    $lang_hrEmpMain_FederalIncomeTax = "Taxe & imposition";
    $lang_hrEmpMain_TaxStatus = "Statut";
    $lang_hrEmpMain_TaxExemptions = "Exemptions";

    $lang_hrEmpMain_TaxStatusSelect = "--Choisir--";
    $lang_hrEmpMain_TaxStatusMarried = "Mari&eacute;(e)";
    $lang_hrEmpMain_TaxStatusSingle = "C&eacute;libataire";
    $lang_hrEmpMain_TaxStatusNonResidentAlien = "Etranger non-r&eacute;sident";
    $lang_hrEmpMain_TaxStatusNotApplicable = "Pas Applicable";

    $lang_hrEmpMain_StateIncomeTax = "Exemptions Taxe &Eacute;tat/Province";
    $lang_hrEmpMain_TaxState = "&Eacute;tat/Province";
    $lang_hrEmpMain_TaxUnemploymentState = "Ch&ocirc;mage &Eacute;tat/Province";
    $lang_hrEmpMain_TaxWorkState = "Travail &Eacute;tat/Province";

    $lang_hrEmpMain_DirectDebitAccount = "Compte";
    $lang_hrEmpMain_DirectDebitAccountType = "Type de Compte";
    $lang_hrEmpMain_DirectDebitAccountTypeChecking = "Ch&egrave;que";
    $lang_hrEmpMain_DirectDebitAccountTypeSavings = "Epargne";
    $lang_hrEmpMain_DirectDebitRoutingNumber = "Num&eacute;ro de transaction";
    $lang_hrEmpMain_DirectDebitAmount = "Montant";
    $lang_hrEmpMain_DirectDebitTransactionType = "Type de Transaction";

    $lang_hrEmpMain_DirectDebitSelectTransactionType = "--Choisir--";

    $lang_hrEmpMain_DirectDebitTransactionTypeBlank = "N&eacute;ant";
    $lang_hrEmpMain_DirectDebitTransactionTypePercentage = "Pourcentage";
    $lang_hrEmpMain_DirectDebitTransactionTypeFlat = "Flat";
    $lang_hrEmpMain_DirectDebitTransactionTypeFlatMinus = "Flat - ";

    $lang_hrEmpMain_DirectDebitAssigned = "Compte courant assign&eacute;";
    $lang_hrEmpMain_DirectDebitAccountShouldBeSpecified = "Le compte doit être défini";
    $lang_hrEmpMain_DirectDebitRoutingNumberShouldBeSpecified = "Le numéro de transaction doit être défini";
    $lang_hrEmpMain_DirectDebitRoutingNumberShouldBeNumeric = "Le numéro de transaction doit être numérique";
    $lang_hrEmpMain_DirectDebitAmountShouldBeSpecified = "Le montant doit être défini";
    $lang_hrEmpMain_DirectDebitAmountShouldBeNumeric = "Le montant doit être numérique";
    $lang_hrEmpMain_DirectDebitTransactionTypeShouldBeSelected = "Le type de transaction doit être sélectionné";

    $lang_jobtitle_heading                     = "D&eacute;finir les postes de l'entreprise";
    $lang_jobtitle_jobtitid                 = "ID Titre du poste";
    $lang_jobtitle_jobtitname                 = "Nom du poste";
    $lang_jobtitle_jobtitdesc                 = "Description du poste";
    $lang_jobtitle_jobtitcomments             = "Note sur le poste";
    $lang_jobtitle_jobspec                  = "Description de t&acirc;che";
    $lang_jobtitle_addpaygrade                 = "Ajout &eacute;chelle salariale";
    $lang_jobtitle_emstatExpl                 = "D&eacute;finir le statut d\'emploi permis pour ce poste";
    $lang_jobtitle_editpaygrade             = "Mofifier l'&eacute;chelle salariale";
    $lang_jobtitle_addempstat                 = "Ajout statut d'emploi";
    $lang_jobtitle_editempstat                 = "Modifier le statut d'emploi";
    $lang_jobtitle_empstat                     = "Statut d'emploi";
    $lang_jobtitle_NameShouldBeSpecified    = "Le nom du titre de poste doit être défini";
    $lang_jobtitle_DescriptionShouldBeSpecified = "La description de poste doit être définie";
    $lang_jobtitle_PayGradeNotSelected      = "L\'échelle salariale n\'est pas sélectionnée";
    $lang_jobtitle_ShowingSavedValues       = "Ce formulaire montre actuellement les valeurs enregistreés lors de sa dernière édition";
    $lang_jobtitle_NoSelection              = "Merci de choisir une valeur";
    $lang_jobtitle_PleaseSelectEmploymentStatus = "Merci de choisir le statut d\'emploi";
    $lang_jobtitle_EnterEmploymentStatus    = "Merci de saisir une valeur pour le statut d\'emploi.";
    $lang_jobspec_heading                     = "Poste : Description de t&acirc;che";
    $lang_jobspec_id                         = "ID";
    $lang_jobspec_name                         = "Nom";
    $lang_jobspec_desc                         = "Description";
    $lang_jobspec_duties                     = "Responsabilit&eacute;s";
    $lang_jobspec_PleaseSpecifyJobSpecName  = "Merci de saisir un nom de description de t&acirc;che";
    $lang_jobspec_NameInUse_Error           = "Ce nom est en utilisation";

    $lang_languageinformation_heading         = "Capacit&eacute;s : Langues";

    $lang_licenses_heading                     = "Qualification : Permis et Licenses";

    $lang_locations_heading                 = "Info Entreprise : Succursale";
    $lang_locations_NameHasToBeSpecified    = "La succursale doit être définie";
    $lang_locations_CountryShouldBeSelected = "Le pays doit être sélectionné!";
    $lang_locations_AddressShouldBeSpecified = "L\'adresse doit être déterminée";
    $lang_locations_ZipCodeShouldBeSpecified = "Le code postal doit être défini";
    $lang_locations_ZipContainsNonNumericChars = "Le code postal contient des caractères non numériques. Voulez-vous continuer?";
    $lang_locations_InvalidCharsInPhone = "Le numéro de téléphone contient des caractères non valides";
    $lang_locations_InvalidCharsInFax = "Le numéro de fax contient des caractères non valides";

    $lang_membershipinfo_heading             = "Adh&eacute;sion : Adh&eacute;sion";
    $lang_membershipinfo_membershiptype     = "Type Adh&eacute;sion";
    $lang_membershipinfor_selectmember         = "Choisir l'adh&eacute;sion";
    $lang_membershipinfo_MembershipShouldBeSpecified = "Le nom de l\'adhésion doit être défini";
    $lang_membershipinfo_MembershipTypeShouldBeSelected = "Le type d\'adhésion doit être choisi";

    $lang_membershiptypes_heading             = "Adh&eacute;sion : Type Adh&eacute;sion";
    $lang_membershiptypes_NameShouldBeSpecified = "Le nom du type d\'adhésion doit être défini";

    $lang_nationalityinformation_heading     = "Nationnalit&eacute;";
    $lang_nationalityinformation_PleaseEnterNationality = "Merci de saisir le nom d\'une nationalité";

    $lang_provinceinformation_heading         = "Information Province : Information G&eacute;ographique";

    $lang_qualificationtypes_heading         = "Types Qualification : Information Qualifications";
    $lang_repview_ReportID                     = "ID Rapport";
    $lang_repview_ReportName                 = "Nom du rapport";
    $lang_repview_ViewEmployeeReports         = "Afficher rapports des employ&eacute;s";
    $lang_repview_message                     = "La suppression peut affecter la hiérarchie de l\'entreprise";
    $lang_repview_DefineEmployeeReports     = "D&eacute;finir les rapports des employ&eacute;s";
    $lang_repview_message2                     = "La suppression peut affecter l\'affichage de rapports";

    $lang_routeinformation_heading             = "Route Information : Route Information";
    $lang_salarygrades_heading                 = "Poste : &Eacute;chelle salariale";
    $lang_salarygrades_currAss                 = "Devises assign&eacute;es";
    $lang_salarygrades_stepSal                 = "Augmentation";
    $lang_salarygrades_selectcurrency        = "Le champ devise doit être choisi";
    $lang_salarygrades_minSalNumeric        = "Le salaire minimum doit être numérique";
    $lang_salarygrades_maxSalNumeric        = "Le salaire maximum doit être numérique";
    $lang_salarygrades_stepSalNumeric        = "L'augmentation doit être numérique";
    $lang_salarygrades_minGreaterThanMax    = "Le salaire mimimum est supérieur au salaire maximum!";
    $lang_salarygrades_stepPlusMinGreaterThanMax = "L\'augmentation de salaire plus le salaire minimum doit être inférieur au salaire maximum";
    $lang_salarygrades_stepGreaterThanMax = "L\'augmentation de salaire doit être inférieure au salaire maximum";
    $lang_salarygrades_NameCannotBeBlank = "L\'échelle salariale ne peut être vide!";

    $lang_Error_salarygrades_DUPLICATE_NAME_FAILURE        = "Echec de l'ajout de l'&eacute;chelle salariale. On ne peut pas avoir de noms en doublon";
    $lang_Error_salarygrades_ADD_FAILURE                = "Echec de l'ajout de l'&eacute;chelle salariale";

    $lang_salcurrdet_heading                 = "Devise assign&eacute;e &agrave; l\'&eacute;chelle salariale";
    $lang_salcurrdet_assigncurr             = "Devise assign&eacute;e";

    $lang_satutoryinfo_heading                 = "Type de statut: Information Nexus";

    $lang_view_EmploymentStatusID             = "ID Statut d'emploi";
    $lang_view_EmploymentStatusName         = "Nom du statut d'emploi";
    $lang_view_deletePrompt                 = "La suppression ne peut être annulée";
    $lang_view_message                         = "La suppression peut affecter les titres de poste";
    $lang_view_message1                     = "La suppression peut affecter les échelles salariales des employés";
    $lang_view_SkillName                     = "Nom de la comp&eacute;tence";
    $lang_view_SkillID                         = "ID Comp&eacute;tence";
    $lang_view_message3                     = "La suppresion peut affecter l\'information sur les employés";
    $lang_view_LocationName                 = "Nom de la succursale";
    $lang_view_message4                     = "La suppression peut affecter la structure hiérarchique de l\'entreprise. Si la succursale a des associations, la suppression sera impossible";
    $lang_view_CurrencyName                 = "Nom de la devise";
    $lang_view_CurrencyTypes                 = "Type de devises";
    $lang_view_message5                     = "La suppression peut affecter l\'information sur les devises";
    $lang_view_CompanyHierarchy             = "Hi&eacute;rarchie de l'entreprise";
    $lang_view_CompanyHierarchyName         = "Nom de la hi&eacute;charchie de l'entreprise";
    $lang_view_message6                     = "La suppression peut affecter l\'information sur les employés";
    $lang_view_QualificationType             = "Type Qualification";
    $lang_view_QualificationName             = "Nom Qualification";
    $lang_view_message9                     = "La suppression peut affecter les qualifications, la désignation des qualitications, et la qualification des employés";
    $lang_view_RatingMethod                 = "M&eacute;thode d\'&eacute;valuation";
    $lang_view_RatingMethodName             = "Nom de la m&eacute;thode d\'&eacute;valuation";
    $lang_view_message10                     = "La suppression peut affecter les sections Qualification et Langues";
    $lang_view_CorporateTitle                 = "Titre Corporatif";
    $lang_view_CorporateTitleName             = "Nom du Titre Corporatif";
    $lang_view_message11                     = "La suppression peut affecter l\'information des employés et les désignations";
    $lang_view_ExtraCategory                 = "Cat&eacute;gorie des autres activit&eacute;s";
    $lang_view_ExtraCategoryName             = "Nom des autres cat&eacute;gories";
    $lang_view_message12                     = "La suppression peut affecter les autres activités";
    $lang_view_MembershipTypeName             = "Nom du type d'adh&eacute;sion";
    $lang_view_message13                     = "La suppression peut affecter les adhésions des employés";
    $lang_view_EmployeeCategoryName         = "Nom de la cat&eacute;gorie d\'employ&eacute;";
    $lang_view_message14                     = "La suppression peut affecter les informations liées aux employés";
    $lang_view_EmployeeGroupName             = "Nom de groupe Employ&eacute;";
    $lang_view_message15                     = "La suppression peut affecter les informations liées aux employés";
    $lang_view_NationalityName                 = "Nom de la nationnalit&eacute;";
    $lang_view_message16                     = "La suppression peut affecter les informations liées aux employés";
    $lang_view_CountryID                     = "ID Pays";
    $lang_view_CountryName                     = "Nom du pays";
    $lang_view_message17                     = "La suppression peut affecter les informations liées aux employés";
    $lang_view_Hierarchydefinition             = "D&eacute;finition de la hi&eacute;rarchie";
    $lang_view_HierarchydefinitionName         = "D&eacute;finition du nom de la hi&eacute;rarchie";
    $lang_view_message18                     = "La suppression peut affecter la hiéarchie de l\'entreprise";
    $lang_view_StateProvinceName             = "Nom de la province / &Eacute;tat";
    $lang_view_message19                     = "La suppression peut affecter les informations liées aux employés";
    $lang_view_CityName                     = "Nom de la ville";
    $lang_view_message20                     = "La suppression peut affecter les informations liées aux employés";
    $lang_view_LanguagName                     = "Nom de la langue";
    $lang_view_message21                     = "La suppression peut affecter les informations liées aux langues de l\'employé";
    $lang_view_Membership                     = "Adh&eacute;sion";
    $lang_view_MembershipType                 = "Type d'adh&eacute;sion";
    $lang_view_MembershipName                 = "Nom de l'adh&eacute;sion";
    $lang_view_Type                         = "Type";
    $lang_view_message22                     = "La suppression peut affecter les informations d'adhésion des employés";
    $lang_view_ExtraActivities                 = "Autres activit&eacute;s externes";
    $lang_view_ExtraActivitiesName             = "Noms des autres activit&eacute;s externes";
    $lang_view_message23                     = "La suppression peut affecter les autres activités externes";
    $lang_view_PayGradeName                 = "Nom du niveau salarial";
    $lang_view_message24                     = "La suppression peut affecter les informations liées aux employés et aux titres de poste";
    $lang_view_message25                     = "La suppression peut affecter les information de formation";
    $lang_view_EmployeeTypeID                 = "Type ID employ&eacute;s";
    $lang_view_EmployeeTypeName             = "Nom du type d\'employ&eacute;";
    $lang_view_message26                     = "La suppression peut affecter les informations liées aux employés";
    $lang_view_EEOJobCategory                 = "Cat&eacute;gorie de poste";
    $lang_view_EEOJobCategoryid             = "ID Cat&eacute;gorie de poste";
    $lang_view_EEOJobCategoryName             = "Cat&eacute;gorie du poste (ex. cadre, commis, professionnel, etc.)";
    $lang_view_message27                     = "La suppression peut affecter les informations liées aux employés";
    $lang_view_message28                     = "La suppression peut affecter les informations liées aux langues des employés";
    $lang_view_EthnicRace                     = "Origine ethnique";
    $lang_view_EthnicRaceName                 = "Nom de l\'origine ethnique";
    $lang_view_message29                     = "La suppresion peut affecter le dossier employé";
    $lang_view_message30                     = "La suppresion peut affecter les informations liées aux employés";
    $lang_view_message31                     = "La suppresion peut affecter les informations liées aux employés et aux titres corporatifs";
    $lang_view_message32                     = "La suppresion peut affecter les informations liées aux qualifications, désignations et aux qualifications d\'employés;";
    $lang_view_License_ID                     = "ID permis";
    $lang_view_LicenseDescription             = "Nom du permis ou de la license";
    $lang_view_message33                     = "La suppresion peut affecter le dossier employé";
    $lang_view_UserID                         = "ID Utilisateur";
    $lang_view_UserName                     = "Nom de l'utilisateur";
    $lang_view_message34                     = "La suppresion peut rendre le logiciel inutilisable";
    $lang_view_UserGroupID                     = "ID Groupe d'utilisateurs";
    $lang_view_UserGroupName                 = "Nom du Groupe d'utilisateurs";
    $lang_view_message35                     = "La suppresion peut rendre le logiciel inutilisable";

    $lang_view_Users                 = "Utilisateurs";
    $lang_view_UserGroups             = "Groupe d'utilisateurs Admin";
    $lang_view_HRAdmin                 = "Admin RH";
    $lang_view_ESS                     = "Fiche personnelle";
    $lang_view_ID                     = "ID";

    //Customer
    $lang_view_CustomerId = "ID Client";
    $lang_view_CustomerName = "Nom du client";
    $lang_customer_Heading          = "Client";

    $lang_index_Welcomemes             = "Bienvenue #username";
    $lang_index_ChangePassword         = "Changer le mot de passe";
    $lang_index_Logout                 = "Fin de session";

    $lang_MailFrom = "Email envoy&eacute; par";
    $lang_MailSendingMethod = "M&eacute;thode d'envoi";
    $lang_MailTypes_Mail = "Fonction PHP Interne";
    $lang_MailTypes_Sendmailer = "SendMail";
    $lang_MailTypes_Smtp = "SMTP";
    $lang_SendmailPath = "Chemin du Sendmail";
    $lang_SmtpHost = "SMTP Host";
    $lang_SmtpPort = "Port SMTP";
    $lang_SmtpUser = "Utilisateur SMTP";
    $lang_SmtpPassword = "Mot de passe SMTP";
    $lang_SmtpSendTestEmail = "Envoyer Email Test";
    $lang_SmptTestEmailAddress = "Adresse Email de test";
    $lang_SmtpTestEmailSucceeded = "Email de test envoy&eacute; avec succ&egrave;s";
    $lang_SmtpTestEmailFailed = "&Eacute;chec de l\'email de test. Il peut &ecirc;tre d&ucirc; &agrave; une configuration incorrecte";
    $lang_Email_SendMail_Instructions = "Merci de configurer un chemin correct de sendmail en changeant sendmail_path dans php.ini";

    // Email Configuration Validation
    $lang_Error_FromEmailEmpty = "Mail Sent As  ne peut &ecirc;tre nul. Saisir une adresse email valide";
    $lang_Error_FromEmailInvalid = "Mail Sent As doit &ecirc;tre une adresse email valide";
    $lang_Error_SmtpHostEmpty = "SMTP Host ne peut pas &ecirc;tre vide";
    $lang_Error_SmtpPortEmpty = "SMTP Port ne peut pas &ecirc;tre vide";
    $lang_Error_Invalid_Port = "Port SMTP non-valide";
    $lang_Error_SmtpUsernameEmpty = "SMTP User ne peut être vide";
    $lang_Error_SmtpPasswordEmpty = "SMTP Password ne peut être vide";
    $lang_Error_TestEmailEmpty = "L\'adresse de test email est vide";
    $lang_Error_TestEmailValid = "L\'adresse de test email n\'est pas valide";

        //Projects
    $lang_view_Project_Heading = "Projet";
    $lang_view_ProjectId = "ID Projet";
    $lang_view_ProjectName = "Nom du projet";

    // Data Export
    $lang_DataExport_Title = "Export donn&eacute;es RH";
    $lang_DataExport_Type = "Type Export";
    $lang_DataExport_ExportTypeNotSelected = "Type Export non sélectionné";
    $lang_DataExport_Export = "Export";
    $lang_DataExport_PluginsAreMarked = "Les types d'export marqu&eacute;s avec (+) sont d&eacute;finis dans les fichiers de plugin et ne sont pas modifiables via l'UI";
    $lang_DataExport_CustomExportTypesCanBeManaged = "Les types d'export personnalisables peuvent &ecirc;tre g&eacute;r&eacute;s ";
    $lang_DataExport_ClickingHereLink = "ici";

    // Define Custom Data Export
    $lang_DataExport_CustomExportId = "ID";
    $lang_DataExport_CustomExportName = "Nom de l'export";
    $lang_DataExportCustom_Title = "D&eacute;finition d'export personnalis&eacute;";
    $lang_DataExport_DefineCustomField_Heading = "D&eacute;finition d'export personalis&eacute;";
    $lang_DataExport_AssignedFields = "Champs affect&eacute;s";
    $lang_DataExport_AvailableFields = "Champs disponibles";
    $lang_DataExport_Add = "Ajouter";
    $lang_DataExport_Remove = "Enlever";
    $lang_DataExport_PleaseSpecifyExportName = "Merci de définir le nom d'exportation";
    $lang_DataExport_Error_NoFieldSelected = "Aucun champ choisi";
    $lang_DataExport_Error_AssignAtLeastOneField = "Merci affecter au moins un champ";
    $lang_DataExport_Error_NameInUse = "Ce nom est d&eacute;j&agrave; utilis&eacute;.";
    $lang_DataExport_Error_NoFieldSelectedForMove = "Merci de choisir au moins un champ dans les champs affectés pour déplacer";
    $lang_DataExport_MoveUp = "D&eacute;placer vers le haut les champs choisis";
    $lang_DataExport_MoveDown = "D&eacute;placer vers le bas les champs choisis";

    // Data Import
    $lang_DataImport_Title = "Import donn&eacute;es RH";
    $lang_DataImport_Type = "Type Import";
    $lang_DataImport_CSVFile = "Fichier CSV";
    $lang_DataImport_ImportTypeNotSelected = "Type d'import non choisi";
    $lang_DataImport_Import = "Import";
    $lang_DataImport_PluginsAreMarked = "Les types d'import marqu&eacute;s avec (+) sont d&eacute;finis dans les fichiers de plugin et ne sont pas modifiables via l'UI.";
    $lang_DataImport_CustomImportTypesCanBeManaged = "Les types d'import personnalisables peuvent &ecirc;tre g&eacute;r&eacute;s ";
    $lang_DataImport_ClickingHereLink = "ici";
    $lang_DataImport_Error_PleaseSelectFile = "Merci de choisir un fichier CSV";

    // Data Import Status
    $lang_DataImportStatus_Title = "Statut d\'Import donn&eacute;es";
    $lang_DataImportStatus_ContinuingDataImport = "Chargement r&eacute;ussi. Continuation de l\'import des donn&eacute;s";
    $lang_DataImportStatus_Summary = "R&eacute;sum&eacute;";
    $lang_DataImportStatus_Details = "D&eacute;tails des lignes en &eacute;chec";
    $lang_DataImportStatus_NothingImported = "Aucune ligne import&eacute;e";
    $lang_DataImportStatus_ImportSuccess = "Import r&eacute;ussi";
    $lang_DataImportStatus_ImportFailed = "Import &eacute;chou&eacute;, aucune ligne import&eacute;e";
    $lang_DataImportStatus_ImportSomeFailed = "Certaines lignes n\'ont pu &ecirc;tre import&eacute;es";
    $lang_DataImportStatus_NumImported = "Nb. de lignes import&eacute;es";
    $lang_DataImportStatus_NumFailed = "Nb. de lignes qui n\'ont pu &ecirc;tre import&eacute;es";
    $lang_DataImportStatus_NumSkipped = "No. d\'ent&ecirc;te de ligne ignor&eacute;e";
    $lang_DataImportStatus_TimeRemainingSeconds = "secondes";
    $lang_DataImportStatus_FinalResult = "R&eacute;sultat final";
    $lang_DataImportStatus_ImportInProgress = "Import en cours...";
    $lang_DataImportStatus_ImportCompleted = "Import termin&eacute;";
    $lang_DataImportStatus_Progress = "En cours";
    $lang_DataImportStatus_ETA = "ETA";
    $lang_DataImportStatus_Heading_Row = "Ligne";
    $lang_DataImportStatus_Heading_Error = "Erreur";
    $lang_DataImportStatus_Heading_Comments = "Commentaires";

    $lang_DataImportStatus_Error_IMPORT_ERROR = "Erreur Import ";
    $lang_DataImportStatus_Error_INCORRECT_COLUMN_NUMBER = "Nombre de colonnes incorrect";
    $lang_DataImportStatus_Error_MISSING_WORKSTATION = "Poste de travail introuvable";
    $lang_DataImportStatus_Error_COMPULSARY_FIELDS_MISSING_DATA = "Champs obligatoires manquants dans les donn&eacute;es";
    $lang_DataImportStatus_Error_DD_DATA_INCOMPLETE = "Les donn&eacute;es de d&eacute;p&ocirc;t directes sont incompl&egrave;tes";
    $lang_DataImportStatus_Error_INVALID_TYPE = "Type de donn&eacute;es non valide";
    $lang_DataImportStatus_Error_DUPLICATE_EMPLOYEE_ID = "ID employ&eacute; en utilisation";
    $lang_DataImportStatus_Error_DUPLICATE_EMPLOYEE_NAME = "Le nom de l'employ&eacute; est en doublon";
    $lang_DataImportStatus_Error_FIELD_TOO_LONG = "Champ trop long";

    // Define Custom Data Import
    $lang_DataImport_CustomImportId = "ID";
    $lang_DataImport_CustomImportName = "Nom Import";
    $lang_DataImportCustom_Title = "D&eacute;finitions d'import personnalisable";
    $lang_DataImport_DefineCustomField_Heading = "D&eacute;finir l'import personnalisable";
    $lang_DataImport_ContainsHeader = "Contient ent&ecirc;te";
    $lang_DataImport_ContainsHeaderDescription = "Si la case est coch&eacute;e, le logiciel sautera la 1&egrave;re ligne du fichier CVS";
    $lang_DataImport_AssignedFields = "Champs affect&eacute;s";
    $lang_DataImport_AvailableFields = "Champs disponibles";
    $lang_DataImport_Add = "Ajouter";
    $lang_DataImport_Remove = "Enlever";
    $lang_DataImport_PleaseSpecifyImportName = "Merci de définir le nom d'import";
    $lang_DataImport_Error_NoFieldSelected = "Pas de champ choisi";
    $lang_DataImport_Error_AssignAtLeastOneField = "Merci d'affecter au moins un champ";
    $lang_DataImport_Error_NameInUse = "Ce nom est en doublon.";
    $lang_DataImport_Error_NoFieldSelectedForMove = "Merci de choisir au moins un champ dans les champs affectés pour le déplacement ";
    $lang_DataImport_MoveUp = "D&eacute;placer les champs choisis en haut";
    $lang_DataImport_MoveDown = "D&eacute;placer les champs choisis en bas";
    $lang_DataImport_Error_AssignCompulsaryFields = "Les champs obligatoires suivants ne sont pas affectés";
    $lang_DataImport_CompulsaryFields = "Les champs suivants sont obligatoires et doivent être affectés ";
    $lang_DataImport_Error_CantRemoveCompulsaryFields = "Les champs suivants sont obligatoires et ne peuvent être supprimés";

    // Define custom data export CSV headings
    $lang_DataExport_DefineCustomFieldHeadings_Heading = "D&eacute;finir ent&ecirc;te pour un export personnalisable";
    $lang_DataExport_ColumnHeadings = "Colonne ent&ecirc;te CSV";
    $lang_DataExport_EditColumnHeadings = "Modifier colonne ent&ecirc;te si n&eacute;ssaire.";
    $lang_DataExport_Error_AllHeadingsMustBeSpecified = "Toutes les colonnes ent&ecirc;te doivent &ecirc;tre d&eacute;finies";
    $lang_DataExport_Error_CommaNotAllowedInHeadings = "Le caract&egrave;re virgule (,) n\'est pas permis dans les ent&ecirc;tes";

    // Custom Fields
    $lang_CustomFields_Title = "Champs Personnalisables";
    $lang_CustomFields_CustomFieldId = "ID champ personnalisable";
    $lang_CustomFields_CustomFieldName = "Nom champ personnalisable";
    $lang_CustomFields_CustomFieldNumber = "Num&eacute;ro du champ";
    $lang_customeFields_Heading = "Champs personnalisables";
    $lang_customeFields_FieldName = "Nom Champ";
    $lang_customeFields_Type = "Type";
    $lang_customeFields_StringType = "String";
    $lang_customeFields_SelectType = "Liste d&eacute;roulante";
    $lang_customeFields_SelectOptions = "Choisir options";
    $lang_Admin_CustomeFields_PleaseSpecifyCustomFieldName = "Merci de définir le nom du champ personnalisable";
    $lang_Admin_CustomeFields_PleaseSpecifySelectOptions = "Merci de définir les options choisies";
    $lang_Admin_CustomeFields_SelectOptionsHint = "Saisir les options en les s&eacute;parant par des virgules";
    $lang_Admin_CustomeFields_MaxCustomFieldsCreated = "Le nombre maximum de champs personnalisables a &eacute;t&eacute; cre&eacute;&eacute;.";
    $lang_pim_CustomFields_NoCustomFieldsDefined = "Aucun champ personnalisable d&eacute;fini.";

    // PIM tab labels
    $lang_pim_tabs_Personal = "Personnel";
    $lang_pim_tabs_Contact = "Coordonn&eacute;s";
    $lang_pim_tabs_EmergencyContacts = "Contact d'urgence";
    $lang_pim_tabs_Dependents = "Famille";
    $lang_pim_tabs_Immigration = "Immigration";
    $lang_pim_tabs_Job = "Poste";
    $lang_pim_tabs_Payments = "Salaire";
    $lang_pim_tabs_ReportTo = "Rapport hi&eacute;rarchique";
    $lang_pim_tabs_WorkExperience = "Historique d'emploi";
    $lang_pim_tabs_Education = "Formation";
    $lang_rep_Languages = "Langues";
    $lang_pim_tabs_Skills = "Comp&eacute;tences";
    $lang_pim_tabs_Languages = "Langues";
    $lang_pim_tabs_License = "License";
    $lang_pim_tabs_Membership = "Adh&eacute;sion";
    $lang_pim_tabs_Attachments = "Pi&egrave;ces jointes";
    $lang_pim_tabs_Tax = "Exon&eacute;rations fiscales";
    $lang_pim_tabs_DirectDebit = "D&eacute;p&ocirc;t Direct";
    $lang_pim_tabs_Custom = "Personnalis&eacute;";

    // Report module
    $lang_rep_SelectionCriteria = "Crit&egrave;re de s&eacute;lection";
    $lang_rep_EmpNo = "Num. Empl.";
    $lang_rep_Employee = "Employ&eacute;";
    $lang_rep_AgeGroup = "Groupe d'&acirc;ge";
    $lang_rep_PayGrade = "Niveau salarial";
    $lang_rep_Education = "Formation";
    $lang_rep_EmploymentStatus = "Statut d'emploi";
    $lang_rep_ServicePeriod = "Anciennet&eacute;";
    $lang_rep_JoinedDate = "Date d'embauche";
    $lang_rep_JobTitle = "Titre poste";
    $lang_rep_Language = "Langue";
    $lang_rep_Skill = "Comp&eacute;tences";

    $lang_rep_LastName = "Nom";
    $lang_rep_FirstName = "Pr&eacute;nom";
    $lang_rep_Address = "Adresse";
    $lang_rep_TelNo = "Num. T&eacute;l.";
    $lang_rep_DateOfBirth = "Date de naissance";
    $lang_rep_JoinDate = "Date embauche";
    $lang_rep_Qualification = "Qualification";
    $lang_rep_EmployeeStates = "Employee States";
    $lang_rep_JoinedDate = "Date d\'embauche";

    $lang_rep_SelectPayGrade = "Choisir le niveau salarial";
    $lang_rep_SelectEducation = "Choisir le type de formation";
    $lang_rep_SelectEmploymentType = "Choisir le type d`emploi";
    $lang_rep_SelectComparison = "Choisir comparaison";
    $lang_rep_SelectJobTitle = "Choisir le titre du poste";
    $lang_rep_SelectLanguage = "Choisir la langue";
    $lang_rep_SelectSkill = "Choisir la comp&eacute;tence";

    $lang_rep_Field = "Champ";
    $lang_rep_AssignUserGroups = "Assign&eacute; groupe d'utilisateurs";
    $lang_rep_AssignedUserGroups = "Assign&eacute; groupe d'utilisateurs";

    $lang_rep_SelectAtLeastOneCriteriaAndOneField = "Choisir au moins un crit&egrave;re et un champ";
    $lang_rep_SelectTheComparison = "Choisir la comparaison d&eacute;sir&eacute;e";

    $lang_rep_AgeShouldBeNumeric = "L\'&agrave;ge doit &ecirc;tre num&eacute;rique";
    $lang_rep_InvalidAgeRange = "La 2i&egrave;me s&eacute;lection de l\'&acirc;ge doit &ecirc;tre sup&eacute;rieure &agrave; la premi&egrave;re s&eacute;lection";

    $lang_rep_FieldNotSelected = "Champ non s&eacute;lectionn&eacute;";

    $lang_rep_DateShouldBeNumeric = "La date doit &ecirc;tre num&eacute;rique";
    $lang_rep_ValueShouldBeinYears = "La valeur doit &ecirc;tre en anne&eacute;s";
    $lang_rep_InvalidRange = "Intervalle non valide";

    $lang_rep_Report = "Rapport";
    $lang_rep_EmployeeNo = "Num. Employ&eacute;";
    $lang_rep_EmployeeFirstName = "Pr&eacute;nom";
    $lang_rep_EmployeeLastName = "Nom employ&eacute;";
    $lang_rep_ReportTo = "Se rapporte &agrave;";
    $lang_rep_ReportingMethod = "Type rapport hi&eacute;archique";
    $lang_rep_Address = "Adresse";
    $lang_rep_Telephone = "T&eacute;l&eacute;phone";
    $lang_rep_DateOfBirth = "Date de naissance";
    $lang_rep_Skills = "Capacit&eacute;s";
    $lang_rep_SalaryGrade = "Niveau salarial";
    $lang_rep_EmployeeStatus = "Statut de l'employ&eacute;";
    $lang_rep_JoinedDate = "Date d'embauche";
    $lang_rep_SubDivision = "D&eacute;partement";
    $lang_rep_JobTitle = "Titre du poste";
    $lang_rep_YearOfPassing = "Ann&eacute;es d'anciennet&eacute;";
    $lang_rep_Contract = "Contractuel";
    $lang_rep_WorkExperience = "Exp&eacute;rience de travail";

    $lang_rep_SelectAtLeaseOneUserGroupToAssign = "Merci de choisir au moins un groupe d\'utilisateurs à assigner";
    $lang_rep_SelectAtLeaseOneUserGroupToDelete = "Merci de choisir au moins un groupe d\'utilisateurs à supprimer";

    $lang_rep_Reportdefinition = "D&eacute;finition de rapport";
    $lang_rep_AssignUserGroups = "Assigner le groupe d'utilisateurs";
    $lang_rep_UserGroups = "Groupes d'utilisateurs";
    $lang_rep_UserGroup = "Groupe d'utilisateurs";
    $lang_rep_NoUserGroupsAssigned = "Num. du Groupe d'utilisateurs assign&eacute;";
    $lang_rep_SelectUserGroup = "Choisir un groupe utilisateur";
    $lang_rep_NoGroupSelected = "Merci de choisir un groupe d'utilisateurs à assigner";
    $lang_rep_ReportNameEmpty = "Nom de rapport vide";

    $lang_rep_Error_DUPLICATE_NAME_ADDED = "Un rapport du m&ecirc;me nom existe d&eacute;j&agrave;.";
    $lang_rep_Error_ADD_FAILURE = "Le rapport ne peut &ecirc;tre ajout&eacute;.";
    $lang_rep_Error_UPDATED_TO_DUPLICATE_NAME = "Les changements du rapport ne peuvent &ecirc;tre sauvegard&eacute;s. Un rapport du m&ecirc;me nom existe d&eacute;j&agrave;.";
    $lang_rep_Error_UPDATE_FAILURE = "Les changements du rapport n\'ont pas &eacute;t&eacute; sauvegard&eacute;s";

    // Skills
    $lang_Admin_Skill_Errors_NameCannotBeBlank = "Le champ nom ne peut être vide!";


    // Email Notification Subscription
    $lang_Admin_EMX_MailConfiguration = "Configuration e-mail";
    $lang_Admin_SubscribeToMailNotifications = "S'abonner &agrave; la notification par e-mail";
    $lang_Admin_ENS_LeaveApplications = "Demande d'absence";
    $lang_Admin_ENS_LeaveApprovals = "Approbation des absences";
    $lang_Admin_ENS_LeaveCancellations = "Annulation des absences";
    $lang_Admin_ENS_LeaveRejections = "Refus des absences";
    $lang_Admin_ENS_JobApplications = "Nouvelles offres d'emploi";
    $lang_Admin_ENS_HspNotifications = "Notification HSP";
    $lang_Admin_ENS_SeekHireApproval = "Requ&ecirc;te de recrutement des nouveaux employ&eacute;s";
    $lang_Admin_ENS_HiringTasks = "T&acirc;ches envoy&eacute;es en embauchant de nouveaux employ&eacute;s";
    $lang_Admin_ENS_HiringApproved = "Notifications d'embauche de nouveaux employ&eacute;s";

    //Users
    $lang_Admin_Users_Errors_UsernameShouldBeAtleastFiveCharactersLong = "Le nom de l\'utilisateur doit avoir au moins 5 caract&egrave;res";
    $lang_Admin_Users_Errors_PasswordShouldBeAtleastFourCharactersLong = "Le mot de passe doit avoir au moins 4 caract&egrave;res";
    $lang_Admin_Users_ErrorsPasswordMismatch = "Les mots de passe ne sont pas identiques. ";
    $lang_Admin_Users_Errors_EmployeeIdShouldBeDefined = "L'ID employé doit être défini";
    $lang_Admin_Users_Errors_FieldShouldBeSelected = "Au moins un champ est manquant";
    $lang_Admin_Users_Errors_ViewShouldBeSelected = "Afficher doit être sélectionné";
    $lang_Admin_Users_Errors_PasswordsAreNotMatchingRetypeYourNewPassword = "Les mots de passe ne sont pas identiques. Merci de resaisir vos mots de passe";
    $lang_Admin_Users_Errors_SpecialCharacters = "Le nom d\'utilisateur ne doit pas contenir de caractères spéciaux";
    $lang_Admin_Users_WrongPassword = "N\'est pas conforme avec votre ancien mot de passe";

    $lang_Admin_Users_Errors_DoYouWantToClearRights = "Voulez-vous réinitialiser les droits ?";
    $lang_Admin_Users_Errors_SameGroup = "Votre compte d\'accès appartient au même groupe d\'utilisateurs, vous ne pouvez changer les droits d\'accès de ce groupe";
    $lang_Admin_Users_Errors_NameCannotBeBlank = "Le champ nom ne peut être vide";

    $lang_Admin_Users_Modules = "Modules";
    $lang_Admin_Users_Module = "Module";
    $lang_Admin_Users_RightsAssignedToUserGroups = "Droits assign&eacute;s au groupe d'utilisateurs";
    $lang_Admin_Users_UserGroup = "Groupe utilisateurs";
    $lang_Admin_Users_UserGroupId = "ID Groupe utilisateurs";
    $lang_Admin_Users_SelectModule = "Choisir module";

    $lang_Admin_Users_UserName = "Nom utilisateur";
    $lang_Admin_Users_Password = "Mot de passe";
    $lang_Admin_Users_Employee = "Employ&eacute;";

    $lang_Admin_Users_ConfirmPassword = "Confirmer le mot de passe";

    $lang_Admin_Users_Status = "Statut";
    $lang_Admin_Users_Enabled = "Actif";
    $lang_Admin_Users_Disabled = "Inactif";

    $lang_Admin_Users_UserGroup = "Groupe utilisateurs admin";
    $lang_Admin_Users_SelectUserGroup = "Choisir le groupe d'utilisateurs";

    $lang_Admin_Users_NewPassword = "Nouveau mot de passe";
    $lang_Admin_Users_ConfirmNewPassword = "Confirmer le nouveau mot de passe";

    $lang_Admin_Users_add = "Ajout";
    $lang_Admin_Users_delete = "Supprimer";
    $lang_Admin_Users_view = "Afficher";
    $lang_Admin_Users_edit = "Modifier";

    $lang_Admin_Users_AssignedRights = "Droits assign&eacute;s";
    $lang_Admin_Users_DefineReports = "D&eacute;finir les rapports";
    $lang_Admin_Users_Assign_User_Rights = "Assigner les droits utilisateur";
    $lang_Admin_Users_View_User_Rights = "Afficher les droits utilisateur";

    $lang_Admin_Change_Password_OldPassword = "Ancien mot de passe";

    $lang_Admin_Change_Password_Errors_EnterYourOldPassword = "Saisir l\'ancien mot de passe";
    $lang_Admin_Change_Password_Errors_EnterYourNewPassword = "Saisir le nouveau mot de passe";
    $lang_Admin_Change_Password_Errors_RetypeYourNewPassword = "Resaisir le nouveau mot de passe";
    $lang_Admin_Change_Password_Errors_PasswordsAreNotMatchingRetypeYourNewPassword = "Les mots de passe diff&egrave;rent. Merci de resaisir le nouveau mot de passe";
    $lang_Admin_Change_Password_Errors_YourOldNewPasswordsAreEqual = "Le nouveau et l\'ancien mot de passe sont identiques";

    $lang_Admin_Project = "Projet";
    $lang_Admin_Project_CutomerName = "Nom du client";
    $lang_Admin_Project_SelectCutomer = "Choisir le client";
    $lang_Admin_Project_Administrators = "Administrateurs projet";
    $lang_Admin_Project_EmployeeName = "Nom de l'employ&eacute;";
    $lang_Admin_Project_EmployeeAlreadyAnAdmin = "L\'employ&eacute; choisi est déjà administrateur de ce projet.";

    $lang_Admin_Project_Error_PleaseDSpecifyTheProjectId = "Merci de spécifier l\'ID du projet";
    $lang_Admin_Project_Error_PleaseSelectACustomer = "Merci de choisir un client";
    $lang_Admin_Project_Error_PleaseSpecifyTheName = "Merci de spécifier le nom";

    $lang_Admin_Customer_PleaseSpecifyTheCustormerId = "Merci de spécifier l\'ID du client";
    $lang_Admin_Customer_Error_PleaseSpecifyTheName = "Merci de spécifier le nom";

    $lang_Admin_ProjectActivities = "Activit&eacute;s de projet";
    $lang_Admin_Activity = "Activit&eacute;";
    $lang_Admin_Project_Activity_ClickOnActivityToEdit = "Cliquer sur une activit&eacute; pour la renommer";
    $lang_Admin_Project_Activity_Error_PleaseSpecifyTheActivityName = "Merci de définir le nom d\'activité";
    $lang_Admin_Project_Error_NoProjects = "Aucun projet trouv&eacute;.";
    $lang_Admin_Project_NoActivitiesDefined = "Aucune activit&eacute; d&eacute;finie.";
    $lang_Admin_Project_Activity_Error_NameAlreadyDefined = "Une activité avec ce nom existe déjà dans ce projet. Merci de choisir un autre nom.";

    //Company Property
    $lang_Admin_Company_Property = "Biens de l'entreprise";
    $lang_Admin_Company_Property_Title = "Info Entreprise : Biens de l'entreprise";
    $lang_Admin_Property_Name = "Nom du bien";
    $lang_Admin_Prop_Emp_Name = "Employ&eacute;";
    $lang_Admin_Company_Property_Err_Del_Not_Sel = "Merci de choisir un bien pour le supprimer";
    $lang_Admin_Company_Property_Err_Name_Empty = "Le nom du bien est vide!";
    $lang_Admin_Company_Property_Err_Name_Exists = "Le nom du bien existe déjà!";
    $lang_Admin_Company_Property_Warn_Delete = "Voulez-vous supprimer les éléments choisis?";
    $lang_Admin_Property_Please_Select = "N'est pas affect&eacute;";

    //timesheet
    $lang_Time_Module_Title = "Module horaire et pr&eacute;vision";
    $lang_Time_Timesheet_TimesheetForViewTitle = "Horaire pour #periodName d&eacute;butant #startDate";
    $lang_Time_Timesheet_TimesheetForEditTitle = "Modifier l'horaire pour #periodName d&eacute;butant #startDate";

    $lang_Time_Timesheet_TimesheetNameForViewTitle = "Horaire de #name pour #periodName d&eacute;butant #startDate";
    $lang_Time_Timesheet_TimesheetNameForEditTitle = "Modifier l'horaire de #name pour #periodName d&eacute;butant #startDate";

    $lang_Time_Timesheet_Status = "Statut: #status";

    $lang_Time_Timesheet_Status_NotSubmitted = "Nom soumis";
    $lang_Time_Timesheet_Status_Submitted = "Soumis";
    $lang_Time_Timesheet_Status_Approved = "Approuv&eacute;";
    $lang_Time_Timesheet_Status_Rejected = "Refus&eacute;";

    $lang_Time_Timesheet_Customer = "Client";
    $lang_Time_Timesheet_ProjectActivity = "Projet / Activit&eacute;";
    $lang_Time_Timesheet_Project = "Projet";
    $lang_Time_Timesheet_Activity = "Activit&eacute;";

    $lang_Time_Timesheet_Total = "Total";
    $lang_Time_TimeFormat = "Format heure";

    $lang_Time_Errors_SUBMIT_SUCCESS = "Soumis avec succ&egrave;s";
    $lang_Time_Errors_SUBMIT_FAILURE = "Echec de la soumission";
    $lang_Time_Errors_UPDATE_SUCCESS = "Mise &agrave; jour effectu&eacute;e";
    $lang_Time_Errors_UPDATE_FAILURE = "Echec de la mise  &agrave; jour";
    $lang_Time_Errors_CANCEL_SUCCESS = "Annul&eacute;e avec succ&egrave;s";
    $lang_Time_Errors_CANCEL_FAILURE = "Echec de l\'annulation";
    $lang_Time_Errors_APPROVE_SUCCESS = "Appouv&eacute; avec succ&egrave;s";
    $lang_Time_Errors_APPROVE_FAILURE = "Echec de l\'approbation";
    $lang_Time_Errors_REJECT_SUCCESS = "Demande rejet&eacute;e avec succ&egrave;s";
    $lang_Time_Errors_REJECT_FAILURE = "Echec du rejet de la demande";
    $lang_Time_Errors_DELETE_SUCCESS = "Supprim&eacute; avec succ&egrave;s";
    $lang_Time_Errors_DELETE_FAILURE = "Echec de la suppression";
    $lang_Time_Errors_UNAUTHORIZED_FAILURE = "Action non autorisée";
    $lang_Time_Errors_APPROVED_TIMESHEET_FAILURE = "Un événement ne peut être ajouté à un horaire approuvé";
    $lang_Time_Errors_REJECTED_TIMESHEET_FAILURE = "Un év&énement ne peut être ajouté à un horaire rejeté";

    $lang_Time_Errors_NO_TIMESHEET_FAILURE = "Horaire non trouvé";
    $lang_Time_Errors_INVALID_TIME_FAILURE = "Date specifiée non valide";
    $lang_Time_Errors_EVENT_START_AFTER_END_ERROR = "La fin de l\'événement précéde son début";
    $lang_Time_Errors_INVALID_TIMESHEET_PERIOD_ERROR = "Période horaire non valide.";
    $lang_Time_Errors_UNFINISHED_TIMESHEET_FAILURE = "L\'horaire contient des activités non terminées";

    $lang_Time_Errors_EncounteredTheFollowingProblems = "Le problème suivant est survenu. Les lignes avec erreurs sont mises en surbrillance.";
    $lang_Time_Errors_EncounteredFollowingProblems = "Les problèmes suivants ont été rencontrés";
    $lang_Time_Errors_ReportedDateNotSpecified_ERROR = "Date reportée non spécifiée";
    $lang_Time_Errors_ProjectNotSpecified_ERROR = "Projet non spécifié";
    $lang_Time_Errors_CustomerNotSpecified_ERROR = "Client non spécifié";
    $lang_Time_Errors_InvalidTimeOrZeroOrNegativeIntervalSpecified_ERROR = "Durée invalide, nulle ou intervalle négatif specifié";
    $lang_Time_Errors_NotAllowedToSpecifyDurationAndInterval_ERROR = "On ne peut spécifier une durée et un intervalle calendaire";
    $lang_Time_Errors_InvalidReportedDate_ERROR = "Valeur de la date reportée non valide.";
    $lang_Time_Errors_InvalidDuration_ERROR = "La durée doit être un nombre positif valide";
    $lang_Time_Errors_InvalidStartTime_ERROR = "Heure invalide pour le temps de début";
    $lang_Time_Errors_InvalidEndTime_ERROR = "Heure invalide pour le temps de fin";
    $lang_Time_Errors_EVENT_OUTSIDE_PERIOD_FAILURE = "L\'événement doit être contenu dans la période horaire";
    $lang_Time_Errors_NoValidDurationOrInterval_ERROR = "La durée de l\'intervalle spé&eacute; n\'est pas valide";
    $lang_Time_Errors_ZeroOrNegativeIntervalSpecified_ERROR = "Auncun intervalle ou intervalle négatif spécifié";
    $lang_Time_Errors_NO_EVENTS_WARNING = "Aucun événement à enregistrer";

    $lang_Time_Timesheet_StartTime = "Heure d&eacute;but";
    $lang_Time_Timesheet_EndTime = "Heure fin";
    $lang_Time_Timesheet_ReportedDate = "Date report&eacute;e";
    $lang_Time_Timesheet_Duration = "Dur&eacute;e";
    $lang_Time_Timesheet_DurationUnits = "(heures)";
    $lang_Time_Timesheet_Decription = "Description";
    $lang_Time_Timesheet_NoCustomers = "Num clients";
    $lang_Time_Timesheet_NoProjects = "Num projets";
    $lang_Time_Timesheet_IncludeDeleteProjects = "Inclus les projets supprim&eacute;s";

    $lang_Time_Select_Employee_Title = "Choisir employ&eacute;";

    $lang_Time_Select_Employee_SubmittedTimesheetsPendingSupervisorApproval = "Horaire soumis pour approbation par le superviseur";
    $lang_Time_Select_Employee_WeekStartingDate = "Semaine d&eacute;butant le #date";

    $lang_Time_Select_Employee_TimesheetPeriod = "P&eacute;riode de l\'horaire";

    $lang_Time_Errors_PleaseAddAComment = "Merci d\'ajouter un commentaire";

    $lang_mtview_ADD_SUCCESS                 = "Ajout&eacute; avec succ&egrave;s";
    $lang_mtview_UPDATE_SUCCESS             = "Mise &agrave; jour avec succ&egrave;s";
    $lang_mtview_DELETE_SUCCESS             = "Supprim&eacute; avec succ&egrave;s";
    $lang_mtview_ADD_FAILURE                 = "&Eacute;chec de l'ajout";
    $lang_mtview_UPDATE_FAILURE             = "&Eacute;chec de la mise &agrave; jour";
    $lang_mtview_DELETE_FAILURE             = "&Eacute;chec de la suppression";

    // 2.2 Time module
    $lang_Time_PunchInPunchOutTitle = "Pointage";
    $lang_Time_PunchIn = "Pointage de d&eacute;but de journ&eacute;e";
    $lang_Time_PunchOut = "Pointage de fin de journ&eacute;e";

    $lang_Time_LastPunchIn = "Derni&egrave;re heure d'embauche:";
    $lang_Time_LastPunchOut = "Derni&egrave;re heure de sortie:";

    $lang_Time_Errors_InvalidDateOrTime = "Date ou heure non valide";
    $lang_Time_Errors_CurrentPunchTimeBeforeLastPunchTime = "L'heure de rentr&eacute;e ne peut &ecirc;tre avant la derni&egrave;re heure de sortie";
    $lang_Time_Errors_ZeroOrNegativeDurationTimeEventsAreNotAllowed = "Les dur&eacute;es n&eacute;gatives ou nulles ne sont pas permises";
    $lang_Time_Errors_ActivityNotSpecified_ERROR = "Activit&eacute; non specifi&eacute;e";

    $lang_Menu_Time_PunchInOut = "Pointage";

    $lang_Time_Timesheet_SelectProject = "Choisir un projet";

    $lang_Time_Timesheet_UnfinishedActivitiesTitle = "Activit&eacute;s en cours";
    $lang_Time_SubmitTimeEventTitle = "Soummetre &eacute;v&eacute;nement temporel";

    $lang_Time_Timesheet_DateReportedFor = "Date signal&eacute;e pour";

    $lang_Time_UnfinishedActivitiesTitle = "Activit&eacute;s en cours";
    $lang_Time_NewEvent = "Nouvel &eacute;v&eacute;nement";
    $lang_Time_Complete = "Complet&eacute;";

    $lang_Time_DurationFormat = "hh:mm ou 0.00h";
    $lang_Time_InsertTime="Saisir l'heure";

    $lang_Time_Errors_NO_RECORDS_CHANGED_WARNING = "Aucun changement &agrave; enregistrer";
    $lang_Time_Errors_EXCEPTION_THROWN_WARNING = "Probl&egrave;me recontr&eacute;";

    $lang_Menu_Time_ProjectTime = "Heure Projet";

    $lang_Time_DefineTimesheetPeriodTitle = "D&eacute;finir p&eacute;riode horaire";

    $lang_Time_FirstDayOfWeek = "Premier jour de la semaine";

    $lang_Menu_Time_DefineTimesheetPeriod = "D&eacute;finir p&eacute;riode horaire";

    $lang_Time_EmployeeTimeReportTitle = "Rapport horaire employ&eacute;";

    $lang_Time_Common_FromDate = $lang_Leave_Common_FromDate;
    $lang_Time_Common_ToDate = $lang_Leave_Common_ToDate;
    $lang_Time_Common_All = $lang_Leave_Common_All;

    $lang_Time_Errors_EmployeeNotSpecified = "Employé non-défini";

    $lang_Time_Errors_InvalidDateOrZeroOrNegativeRangeSpecified = "Date non saisie, invalide ou intervalle négatif spécifié";

    $lang_Menu_Time_EmployeeReports = "Rapports employ&eacute;";

    $lang_Time_SelectTimesheetsTitle = "Choisir Horaire";
    $lang_Time_Division = $lang_compstruct_Division;
    $lang_Time_Supervisor = $lang_empview_Supervisor;
    $lang_Time_EmploymentStatus = $lang_empview_EmploymentStatus;

    $lang_Time_NoEmploymentStatusDefined = "Aucun statut employ&eacute; d&eacute;fini";

    $lang_Time_SelectWeekStartDay = "Merci de choisir le premier jour de la semaine";
    $lang_Time_ContactAdminForTimesheetPeriodSet = "Le premier jour de la p&eacute;riode horaire n\'est pas d&eacute;fini. Merci de contacter le service RH";
    $lang_Time_ContactAdminForTimesheetPeriodSetComplete = "Le premier jour de la p&eacute;riode horaire a &eacute;t&eacute; sauvegard&eacute;";
    $lang_Time_ProceedWithTimeModule = "Acc&eacute;der au module horaire";

    $lang_Time_PrintTimesheetsTitle = "Imprimer horaires";
    $lang_Time_Print = "Imprimer";

    $lang_Common_Loading = "Chargement";
    $lang_Common_Select = "Choisir";

    $lang_Menu_Time_PrintTimesheets = "Imprimer Horaires";

    $lang_Menu_Time_ProjectReports = "Rapports Projets";
    $lang_Time_ProjectReportTitle = "Rapports Projets";
    $lang_Time_Report_To = "A";
    $lang_Time_Report_From = "De";
    $lang_Time_TimeInHours = "Heure (heures)";
    $lang_Time_ActivityReportTitle = "D&eacute;tails d'activit&eacute;s";
    $lang_Time_Activity_Report_EmployeeName = "Nom Employ&eacute;";
    $lang_Time_Activity_Report_View = "Afficher";
    $lang_Time_Activity_Report_TotalTime = "Temps total";
    $lang_Time_Activity_Report_NoEvents = "Aucun &eacute;v&eacute;nement trouv&eacute;.";

    $lang_Time_Errors_PROJECT_NOT_FOUND_FAILURE = "Projet introuvable.";
    $lang_Time_Errors_ACTIVITY_NOT_FOUND_FAILURE = "Activit&eacute; introuvable.";

    $lang_Common_Yes = "Oui";
    $lang_Common_No = "Non";

    $lang_Leave_DoYouWantToCancelTheLeaveYouJustAssigned = "Voulez-vous annuler l\'absence que vous venez d\'assigner ?";
    $lang_Leave_PleaseProvideAReason = "Merci de saisir une raison";

    $lang_Time_Errors_OVERLAPPING_TIME_PERIOD_FAILURE = "Vous avez un chevauchement de p&eacute;riode dans vos &eacute;v&eacute;nements";

    $lang_view_DUPLICATE_NAME_FAILURE = "Le m&ecirc;me nom est d&eacute;j&agrave; en cours d'utilisation";

    $lang_Leave_CopyLeaveQuotaFromLastYear = "Copier quota &agrave; partir de l'ann&eacute;e pr&eacute;c&eacute;dente";
    $lang_Leave_CopyLeaveBroughtForwardFromLastYear = "Copier les absences report&eacute;es sur l'ann&eacute;e derni&egrave;re";

    $lang_Leave_LEAVE_QUOTA_COPY_SUCCESS = "Quota d'absence copi&eacute; avec succ&egrave;s";
    $lang_Leave_LEAVE_QUOTA_COPY_FAILURE = "&Eacute;chec de la copie du quota d'absence";

    $lang_Leave_LEAVE_BROUGHT_FORWARD_COPY_SUCCESS = "Copie absence report&eacute;e avec succ&egrave;s";
    $lang_Leave_LEAVE_BROUGHT_FORWARD_COPY_FAILURE = "&Eacute;chec de la copie d'absence report&eacute;e";

    // Recruitment module
    $lang_Recruit_VacancyID = 'ID Offre emploi';
    $lang_Recruit_JobTitleName = 'Titre du poste';
    $lang_Recruit_HiringManager = 'Responsable embauche';
    $lang_Recruit_VacancyStatus = 'Statut';
    $lang_Recruit_JobVacancyDeletionMessage = 'La suppression affectera les demandes d\'emploi';
    $lang_Recruit_JobVacancyListHeading = 'Offres d\'emploi';
    $lang_Recruit_JobVacancy_Active = 'Actif';
    $lang_Recruit_JobVacancy_InActive = 'Inactif';
    $lang_Recruit_JobVacancy_Add_Heading = 'Ajouter une offre d\'emploi';
    $lang_Recruit_JobVacancy_Edit_Heading = 'Modifier une offre d\'emploi';
    $lang_Recruit_JobVacancy_JobTitleSelect = 'Choisir';
    $lang_Recruit_JobVacancy_HiringManagerSelect = 'Choisir';
    $lang_Recruit_JobVacancy_PleaseSpecifyJobTitle = 'Merci de définir un titre de poste';
    $lang_Recruit_JobVacancy_PleaseSpecifyHiringManager = 'Merci de spécifier le responsable des embauches';
    $lang_Recruit_AllowedValuesAre = 'Les valeurs autorisées sont: ';
    $lang_Recruit_NoManagersNotice = 'Aucun manager trouvé. Vous devez créer un titre de poste \'Manager\' et l\'assigner aux employés qui sont responsables de l\'encadrement.';
    $lang_Recruit_NoHiringManagersNotice = 'Aucun employé trouvé pour être assigné comme responsable d\'embauche. Vous devez ajouter des employés dans la configuration.';

    $lang_Recruit_JobApplicationList_Heading = 'Candidats';
    $lang_Recruit_JobApplicationList_Name = 'Nom';
    $lang_Recruit_JobApplicationList_PositionApplied = 'Poste';
    $lang_Recruit_JobApplicationList_Actions = 'Actions';
    $lang_Recruit_JobApplicationList_EventHistory = 'Historique';
    $lang_Recruit_JobApplicationList_Details = 'D&eacute;tails';
    $lang_Recruit_JobApplicationList_NoApplications = 'Aucune candiature pour le moment';

    $lang_Recruit_JobApplicationStatus_Submitted = 'Candidature soumise';
    $lang_Recruit_JobApplicationStatus_FirstInterview = '1er entretien';
    $lang_Recruit_JobApplicationStatus_SecondInterview = '2i&egrave;me entretien';
    $lang_Recruit_JobApplicationStatus_JobOffered = 'Accept&eacute;';
    $lang_Recruit_JobApplicationStatus_OfferDeclined = 'Emploi refus&eacute;';
    $lang_Recruit_JobApplicationStatus_PendingApproval = 'Appobation en attente';
    $lang_Recruit_JobApplicationStatus_Hired = 'Embauch&eacute;';
    $lang_Recruit_JobApplicationStatus_Rejected = 'Refus&eacute;';

    $lang_Recruit_JobApplicationAction_Reject = 'Refus&eacute;';
    $lang_Recruit_JobApplicationAction_FirstInterview = 'Horaire 1er entretien';
    $lang_Recruit_JobApplicationAction_SecondInterview = 'Horaire 2i&egrave;me entretien';
    $lang_Recruit_JobApplicationAction_OfferJob = 'Accept&eacute;';
    $lang_Recruit_JobApplicationAction_MarkDeclined = 'Marquer offre refus&eacute;e';
    $lang_Recruit_JobApplicationAction_SeekApproval = 'Obtenir approbation';
    $lang_Recruit_JobApplicationAction_Approve = 'Approuv&eacute;';

    $lang_Recruit_JobApplicationDetails_Heading = 'D&eacute;tails de la candidature';
    $lang_Recruit_JobApplicationDetails_Status = 'Statut de la candidature';
    $lang_Recruit_JobApplicationDetails_Actions = 'Actions';
    $lang_Recruit_JobApplicationHistory_EventHistory = 'Historique';
    $lang_Recruit_JobApplicationHistory_ApplicationForThePositionOf = 'Candidature pour le poste de';
    $lang_Recruit_JobApplicationHistory_DateApplied = 'Date de la candidature';
    $lang_Recruit_JobApplicationHistory_NoEvents = 'Aucun &eacute;v&eacute;nement disponible';
    $lang_Recruit_JobApplicationHistory_FirstInterview = '1er entretien';
    $lang_Recruit_JobApplicationHistory_SecondInterview = '2i&egrave;me entretien';
    $lang_Recruit_JobApplicationHistory_Rejected = 'Refus&eacute;';
    $lang_Recruit_JobApplicationHistory_OfferedJob = 'Offre d\'emploi';
    $lang_Recruit_JobApplicationHistory_OfferMarkedAsDeclined = 'Offre marqu&eacute;e refus&eacute;e';
    $lang_Recruit_JobApplicationHistory_SeekApproval = 'Obtenir approbation';
    $lang_Recruit_JobApplicationHistory_Approved = 'Approuv&eacute;';
    $lang_Recruit_JobApplicationHistory_By = 'Par';
    $lang_Recruit_JobApplicationHistory_ScheduledBy = 'Programm&eacute; par';
    $lang_Recruit_JobApplicationHistory_At = '&Agrave;';
    $lang_Recruit_JobApplicationHistory_InterviewTime = 'Heure entretien';
    $lang_Recruit_JobApplicationHistory_Interviewer = 'Re&ccedil;u par';
    $lang_Recruit_JobApplicationHistory_Status = 'Statut';
    $lang_Recruit_JobApplicationHistory_Notes = 'Commentaires';
    $lang_Recruit_JobApplicationHistory_StatusInterviewScheduled = 'Programm&eacute;';
    $lang_Recruit_JobApplicationHistory_StatusFinished = 'Termin&eacute;';
    $lang_Recruit_JobApplication_ScheduleFirstInterview = 'Programm&eacute; 1er entretien pour';
    $lang_Recruit_JobApplication_ScheduleSecondInterview = 'Programm&eacute; 2i&egrave; entretien pour';
    $lang_Recruit_JobApplication_Schedule_Date = 'Date';
    $lang_Recruit_JobApplication_Schedule_Time = 'Heure';
    $lang_Recruit_JobApplication_Schedule_Interviewer = 'Re&ccedil;u par';
    $lang_Recruit_JobApplication_Schedule_Notes = 'Commentaires';
    $lang_Recruit_JobApplication_Select = 'Choisir';
    $lang_Recruit_JobApplication_PleaseSpecifyDate = 'Merci de spécifier une date d\'entretien';
    $lang_Recruit_JobApplication_PleaseSpecifyTime = 'Merci de spécifier l\'heure d\'entretien';
    $lang_Recruit_JobApplication_PleaseSpecifyentretiener = 'Merci de spécifier le recruteur';
    $lang_Recruit_JobApplication_PleaseSpecifyInterviewer = 'Merci de spécifier le recruteur';
    $lang_Recruit_JobApplication_PleaseSpecifyValidDate = 'La date doit être au format: ';
    $lang_Recruit_JobApplication_PleaseSpecifyValidTime = 'L\'heure doit être au format: ';
    $lang_Recruit_JobApplication_SecondInterviewShouldBeAfterFirst = 'La date du second entretien doit être après la date du premier entretien ';

    $lang_Recruit_JobApplicationConfirm_Heading = 'Confimer action: ';
    $lang_Recruit_JobApplicationConfirm_ApplicantName = 'Nom candidat';
    $lang_Recruit_JobApplicationConfirm_Position = 'Candidat pour le poste de : ';

    $lang_Recruit_JobApplicationConfirm_ConfirmReject = 'Confirmer ou rejeter la candidature ci-dessus';
    $lang_Recruit_JobApplicationConfirm_ConfirmRejectDesc = 'Un e-mail sera envoy&eacute; au candidat pour l\'informer du rejet';
    $lang_Recruit_JobApplicationConfirm_ConfirmOfferJob = 'Confirmer l\'offre d\'emploi pour le candidat ci-dessus.';
    $lang_Recruit_JobApplicationConfirm_ConfirmOfferJobDesc = 'Aucun e-mail ne sera envoy&eacute; par le logiciel au candidat. Le candidat sera contact&eacute; pour le poste.';
    $lang_Recruit_JobApplicationConfirm_ConfirmMarkDeclined = 'Marquer l\'offre comme refus&eacute;e';
    $lang_Recruit_JobApplicationConfirm_ConfirmMarkDeclinedDesc = 'Indiquer que le candidat &agrave; refus&eacute; le poste.';
    $lang_Recruit_JobApplicationConfirm_ConfirmSeekApproval = 'Obtenir l\'approbation';
    $lang_Recruit_JobApplicationConfirm_ConfirmApprove = 'Confirmer l\'approbation pour le candidat ci-dessus';
    $lang_Recruit_JobApplicationConfirm_ConfirmApproveDesc = 'Ceci marquera le candidat comme embauch&eacute; et cr&eacute;era une entr&eacute;e employ&eacute; dans le syst&egrave;me pour le candidat. Le responsable d\'embauche sera averti, mais aucun e-mail ne sera envoy&eacute; au candidat.';

    $lang_Recruit_JobApplication_SeekApproval_Heading = 'Obtenir l\'approbation d\'embauche ';
    $lang_Recruit_JobApplication_SeekApproval_GetApprovedBy = 'Est approuv&eacute; par ';
    $lang_Recruit_NoDirectorsNotice = 'Aucun directeur trouv&eacute;. Il faut cr&eacute;er un titre de poste \'Director\' et l\'assigner aux employ&eacute;s qui sont directeurs.';
    $lang_Recruit_JobApplication_SeekApproval_Notes = 'Commentaires';
    $lang_Recruit_JobApplication_SeekApproval_Desc = 'Un e-mail sera envoy&eacute; &agrave; la personne choisie, contenant la demande l\'approbation pour l\'embauche.';
    $lang_Recruit_JobApplication_PleaseSpecifyDirector = 'Merci de s&eacute;lectionner un directeur pour &ecirc;tre approuv&eacute; par';
    $lang_Recruit_JobApplication_PleaseSpecifyNotes = 'Merci de saisir un commentaire';

    $lang_Recruit_ApplicantVacancyList_Heading = 'Offres d\'emploi';
    $lang_Recruit_ApplicantVacancyList_Title = 'Offres d\'emploi';
    $lang_Recruit_ApplicationForm_Heading = 'Formulaire d\'application pour un poste &agrave; ';
    $lang_Recruit_Application_CompanyNameNotSet = 'Le nom de la compagnie n\'est pas d&eacute;fini';
    $lang_Recruit_ApplicationForm_Position = 'Candidature pour le poste ';
    $lang_Recruit_Applicant_NoVacanciesFound = 'Aucune offre d\'emploi trouv&eacute;e. Merci de revenir plus tard...';

    $lang_Recruit_ApplicationForm_FirstName = 'Nom';
    $lang_Recruit_ApplicationForm_MiddleName = 'Initiale';
    $lang_Recruit_ApplicationForm_LastName = 'Prénom';
    $lang_Recruit_ApplicationForm_Street1 = 'Adresse 1';
    $lang_Recruit_ApplicationForm_Street2 = 'Adresse 2';
    $lang_Recruit_ApplicationForm_City = 'Ville';
    $lang_Recruit_ApplicationForm_StateProvince = 'Etat / Province';
    $lang_Recruit_ApplicationForm_Country = 'Pays';
    $lang_Recruit_ApplicationForm_Zip = 'Code Postal';
    $lang_Recruit_ApplicationForm_Phone = 'T&eacute;l&eacute;phone';
    $lang_Recruit_ApplicationForm_Mobile = 'Portable';
    $lang_Recruit_ApplicationForm_Email = 'E-mail';
    $lang_Recruit_ApplicationForm_Qualifications = 'Qualifications et Expérience';

    $lang_Recruit_ApplicationForm_PleaseSpecify = 'Merci de spécifier : ';
    $lang_Recruit_ApplicationForm_PleaseSpecifyValidEmail = 'Merci de spécifier une adresse e-mail valide pour ';
    $lang_Recruit_ApplicationForm_PleaseSpecifyValidPhone = 'Merci de spécifier un numéro de téléphone valide pour ';
    $lang_Recruit_ApplicationForm_PleaseSelect = 'Merci de sélectionner ';

    $lang_Recruit_ApplicationStatus_SuccessHeading = 'Demande d\'emploi re&ccedil;ue';
    $lang_Recruit_ApplicationStatus_FailureHeading = 'Erreur demande d\'emploi';
    $lang_Recruit_ApplySuccess = 'Votre application pour la position de #jobtitle# a &eacute;t&eacute; re&ccedil;ue. Un e-mail de confirmation sera envoy&eacute; &agrave; l\'adresse suivante : #email#';
    $lang_Recruit_ApplyFailure = 'Une erreur inattendue s\'est produite. Merci de r&eacute;essayer.';

// login page
    $lang_login_title = "Gestion de Ressource Humaine";
    $lang_login_UserNameNotGiven = "Saisir le nom d\'utilisateur!";
    $lang_login_PasswordNotGiven = "Saisir le mot de passe!";
    $lang_login_NeedJavascript = "Vous avez besoin d\'un navigateur supportant JavaScript. Ex. ";
    $lang_login_MozillaFirefox = "Mozilla Firefox";
    $lang_login_YourSessionExpired = "Votre session a expir&eacute; car vous &eacute;tiez inactif. Merci de vous reconnecter.";
    $lang_login_LoginName = "Nom de login";
    $lang_login_Password = "Mot de passe";
    $lang_login_Login = "Login";
    $lang_login_Clear = "Effacer";
    $lang_login_InvalidLogin = "Login invalide";
    $lang_login_UserDisabled = "Utilisateur d&eacute;sactiv&eacute;";
    $lang_login_EmployeeTerminated = "Login refus&eacute;";
    $lang_login_NoEmployeeAssigned = "Aucun employ&eacute; assign&eacute; &agrave; ce compte utilisateur";
    $lang_login_temporarily_unavailable = "Service temporairement indisponible";
    $lang_login_OrangeHRMDescription = " vient en tant qu\'une solution globale pour la gestion efficace et le d&eacute;veloppement de vos ressources humaines. Il vous assistera dans le processus strat&eacute;gique et complexe de gestion de cette ressource cruciale de votre entreprise. Bas&eacute; sur une architecture modulaire, il facilite un vaste &eacute;tail d\'activit&eacute;s de Ressources Humaines, avec des fonctions qui prennent en compte les principales activit&eacute;s de gestion de Ressources Humaines. Il se pr&eacute;sente comme une application web, en consid&eacute;rant sa souplesse, OrangeHRM est une plate-forme id&eacute;ale pour votre r&eacute;ing&eacute;nerie des processus RH et t\'atteindre un autre niveau de gestion des ressources humaines";

    $lang_Leave_Common_FromTime = "Heure d&eacute;but";
    $lang_Leave_Common_ToTime = "Heure fin";
    $lang_Leave_Common_TotalHours = "Total heures";
    $lang_Leave_Error_ToTimeBeforeFromTime = "L'heure de fin est apr&egrave;s l'heure de d&eacute;but";
    $lang_Leave_Error_ZeroLengthHours = "La dur&eacute;e de l\'absence est nulle";
    $lang_Leave_Error_TotalTimeMoreThanADay = "Le nombre total d'heures est plus grand que la durée de rotation";
    $lang_Leave_Common_WorkshiftLengthIs = "La durée de la rotation est  ";
    $lang_Leave_Error_PleaseSpecifyEitherTotalTimeOrTheTimePeriod = "Merci de spécifier soit le total d\'heures, soit l\'intervalle de temps";

    $lang_Leave_Error_DuplicateLeaveError = "Votre demande d'absence chevauche d'autres demandes d'absence.";
    $lang_Leave_Error_DuplicateLeaveErrorInstructions = "Merci d'annuler les demandes d'absence qui se chevauchent ou de modifier les demandes de cong&eacute;s ci-dessus et r&eacute;essayer.";

    $lang_Leave_Error_DuplicateLeaveWarning = "Les demandes d'absences suivantes sont d&eacute;finies pendant des absences programm&eacute;es";
    $lang_Leave_Error_DuplicateLeaveWarningInstructions = "Merci de corriger les demandes d'absences actuelles et soummetez les &agrave; nouveau pour confirmer ou changer la demande d'absence au besoin.";

    $lang_Leave_Duration = "Dur&eacute;e(heures)";
    $lang_Common_Hours = "heures";
    $lang_Common_Days = "jours";

    $lang_Time_WorkShifts = "Rotation de travail";
    $lang_Time_ShiftName = "Nom rotation";
    $lang_Time_HoursPerDay = "Heures par jour";
    $lang_Time_AvailableEmployees = "Employ&eacute;s disponibles";
    $lang_Time_AssignedEmployees = "Employ&eacute;s affect&eacute;s";
    $lang_Time_AssignEmployeesTitle = " Modifier la rotation";

    $lang_Time_Error_SpecifyWorkShiftName = "Merci de spécifier le nom de la rotation";
    $lang_Time_Error_SpecifyHoursPerDay = "Merci de spécifier le nombre d'heures par jour";
    $lang_Time_Error_DecimalNotAllowed = "Nombre décimal interdit";
    $lang_Time_Error_HoursPerDayShouldBePositiveNumber = "Le nombre d'heures par jour doit être un nombre positif ";
    $lang_Time_Error_HoursPerDayShouldBeLessThan24 = "Le nombre d'heures par jour doit être inférieur à 24";
    $lang_Time_Error_NoEmployeeSelected = "Pas d\'employé choisi";

    $lang_Time_Errors_INVALID_WORK_SHIFT_FAILURE = "Il y a un probl&egrave;me dans le d&eacute;tail de la rotation";
    $lang_Time_Errors_NO_RECORDS_SELECTED_FAILURE = "Aucun enregistrement n'a &eacute;t&eacute;choisi pour la suppression";
    $lang_Time_Errors_UNKNOWN_ERROR_FAILURE = "Une erreur a &eacute;t&eacute; trouv&eacute;e";
    $lang_Time_Errors_INVALID_ID_FAILURE = "Id Invalide";

    $lang_Menu_Time_WorkShifts = "Rotation de travail";

    /*
    * Update of Mark Translation
    * Translation all the mmodule except the Benifits Module
    * Date : 2009-21-01
    * By : Jean Came POulard (jcpoulard)
    * Email : jcpulard@logipam.org
    * Web : http://logipam.org
    */
    $lang_Admin_Activity = "Activit&eacute;";

    $lang_Common_To = "&agrave;";

    $lang_Define_Health_Savings_Plans="Mutuelle santé d&eacute;finie";
    $lang_Defined_Hsp="d&eacute;j&agrave; d&eacute;fini ";

    $lang_HSP_Plan_Not_Selected = "Merci de sélectioner un plan HSP avant de sauvegarder";
    $lang_Hsp_Current_HSP_is = "Le HSP courant est";
    $lang_Hsp_Key_Fsa="FSA";
    $lang_Hsp_Key_Hra="HRA";
    $lang_Hsp_Key_Hra_Fsa="HRA+FSA";
    $lang_Hsp_Key_Hsa="HSA";
    $lang_Hsp_Key_Hsa_Fsa="HSA+FSA";
    $lang_Hsp_Key_Hsa_Hra="HSA+HRA";
    $lang_Hsp_No_HSP_defined = "Aucun HSP d&eacute;fini";
    $lang_Hsp_Saving_Error="Erreur de sauvegarde du HSP";
    $lang_Hsp_Succesfully_Saved="Sauvegarde du HSP correctement effectu&eacute;e";
   /*
    * Update of Mark Translation
    * Translation all the module except the Benifits Module
    * Date : 2009-04-14
    * GLE by Synopsis et DRSI
    * Author: Tommy SAURON <tommy@drsi.fr>
    * Licence : Artistic Licence v2.0
    * Version 1.0
    * Create on : 4-1-2009
    *
    * Infos on http://www.synopsis-erp.com
    */


?>
