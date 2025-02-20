<?php

class BimpUserMsg
{
	public static $canaux_diff_msg = array();

	public static $types_dest = array();
	public static $userMessages = array();

	public static function isMsgActive($code) {}

	public static function sendUsersMsg($code, $users, $subjet, $msg, $params = array()) {
		$errors = array();

//		$users = tableau d'ID users

		// isMsgActive()

		// Foreach users
		// $user->isMsgAllowed()
		// Sinon : $user->getSubstituteUsers() => vérif de chaque retour (si pas déjà dans $users valides) / récup les infos et les ajouter à la fin de $msg.

		// Si à la fin du process, aucun user dispo et si $user_msg['allow_default_user'] => $email = BimpCore::getConf('default_user_email', null);

		// récup e-mail(s)

		// sendEmail()
		return $errors;
	}

	public static function sendUserGroupsMsg($code, $user_groups, $subjet, $msg, $params = array()) {
		$errors = array();

		// $users = tableau d'ID groupes (Bimp_UserGroup)

		// isMsgActive()
		// Pas de vérif pour chaque groupe pour l'instant.
		// récup e-mail(s)
		// sendEmail()

		return $errors;

	}

	public static function sendEmail($code, $subjet, $msg, $to, $params = array())
	{
		// Cas des addresse e-mails en conf ou en dur
		$errors = array();

		// isMsgActive()
		// BimpTools::cleanEmail(to)
		// mailsSyn2()

		// todo flo : gérer les transactions db.

		return $errors;
	}
}
