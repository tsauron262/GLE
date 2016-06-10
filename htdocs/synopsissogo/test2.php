


  <!DOCTYPE html>
  
    <html lang="en" xmlns="http://www.w3.org/1999/xhtml">
      <head>
        <title>
          SOGo
        </title>
        <meta name="hideFrame" content="0" />
        <meta name="description" content="SOGo Web Interface" />
        <meta name="author" content="Inverse inc." />
        <meta name="robots" content="stop" />
        <meta name="build" content="@shiva.inverse 201603051728" />
        <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1" />
        <link rev="made" href="mailto:support@inverse.ca" />
        <link href="/SOGo.woa/WebServerResources/img/sogo.ico?lm=1461336459" rel="shortcut icon" type="image/x-icon" />
        <link href="/SOGo.woa/WebServerResources/css/styles.css" rel="stylesheet" type="text/css" />
        
        
      </head>

      <body class="main" ng-app="SOGo.MainUI">
        
          
          <script type="text/javascript">
            
          </script>

          
          
  
  <main class="view layout-fill layout-padding md-default-theme md-background md-hue-1 md-bg" ng-controller="LoginController as app" ui-view="login" layout-align="center start" layout="row">
    <md-content ng-show="app.showLogin" layout="column" md-scroll-y="true" layout-align="space-between center" layout-gt-sm="row" class="ng-cloak md-whiteframe-z1" layout-align-gt-sm="start center">
      <div id="logo" class="md-padding">
        <img src="https://mailhost.synopsis-erp.com/SOGo.woa/WebServerResources/img/sogo-full.svg" id="splash" alt="*" />
      </div>
      <div class="sg-login md-padding md-default-theme md-bg md-accent">
        <script type="text/javascript">
          var cookieUsername = '';
        </script>
        <div id="login">

          <form method="post" ng-cloak="ng-cloak" ng-submit="app.login()" layout="column" name="loginForm">
            
            <md-input-container class="md-block">
              <label>Nom d'utilisateur</label>
              <md-icon>person</md-icon>
              <input type="text" name="3.1.1.3.1.1.6.2.1.3.3.1.3.5" value="" ng-model="app.creds.username" ng-required="true" autocapitalize="off" />
            </md-input-container>
            <md-input-container class="md-block">
              <label>Mot de passe</label>
              <md-icon>email</md-icon>
              <input type="password" name="3.1.1.3.1.1.6.2.1.3.3.1.5.5" value="" ng-model="app.creds.password" ng-required="true" />
            </md-input-container>

            
            <div layout="row" layout-align="end center">
              <md-button class="md-raised md-accent md-hue-2" ng-disabled="app.loginForm.$invalid" type="submit">
                Connexion
              </md-button>
            </div>

            
            <div layout="row" layout-align="start end">
              <md-icon>language</md-icon>
              <md-input-container class="md-flex">
                <label>Choisir ...</label>
                <md-select ng-model="app.creds.language">
                  
                    <md-option value="Arabic">
                      &#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;
                    </md-option>
                  
                    <md-option value="Basque">
                      Euskara
                    </md-option>
                  
                    <md-option value="Catalan">
                      Catal&#224;
                    </md-option>
                  
                    <md-option value="ChineseTaiwan">
                      Chinese (Taiwan)
                    </md-option>
                  
                    <md-option value="Croatian">
                      Hrvatski
                    </md-option>
                  
                    <md-option value="Czech">
                      &#268;esky
                    </md-option>
                  
                    <md-option value="Dutch">
                      Nederlands
                    </md-option>
                  
                    <md-option value="Danish">
                      Dansk (Danmark)
                    </md-option>
                  
                    <md-option value="Welsh">
                      Cymraeg
                    </md-option>
                  
                    <md-option value="English">
                      English
                    </md-option>
                  
                    <md-option value="SpanishSpain">
                      Espa&#241;ol (Espa&#241;a)
                    </md-option>
                  
                    <md-option value="SpanishArgentina">
                      Espa&#241;ol (Argentina)
                    </md-option>
                  
                    <md-option value="Finnish">
                      Suomi
                    </md-option>
                  
                    <md-option value="French">
                      Fran&#231;ais
                    </md-option>
                  
                    <md-option value="German">
                      Deutsch
                    </md-option>
                  
                    <md-option value="Icelandic">
                      &#205;slenska
                    </md-option>
                  
                    <md-option value="Italian">
                      Italiano
                    </md-option>
                  
                    <md-option value="Macedonian">
                      Macedonian
                    </md-option>
                  
                    <md-option value="Hungarian">
                      Magyar
                    </md-option>
                  
                    <md-option value="Portuguese">
                      Portuguese
                    </md-option>
                  
                    <md-option value="BrazilianPortuguese">
                      Portugu&#234;s brasileiro
                    </md-option>
                  
                    <md-option value="NorwegianBokmal">
                      Norsk bokm&#229;l
                    </md-option>
                  
                    <md-option value="NorwegianNynorsk">
                      Norsk nynorsk
                    </md-option>
                  
                    <md-option value="Polish">
                      Polski
                    </md-option>
                  
                    <md-option value="Russian">
                      &#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;
                    </md-option>
                  
                    <md-option value="Slovak">
                      Slovensky
                    </md-option>
                  
                    <md-option value="Slovenian">
                      Sloven&#353;&#269;ina
                    </md-option>
                  
                    <md-option value="Ukrainian">
                      &#1059;&#1082;&#1088;&#1072;&#1111;&#1085;&#1089;&#1100;&#1082;&#1072;
                    </md-option>
                  
                    <md-option value="Swedish">
                      Svenska
                    </md-option>
                  
                </md-select>
              </md-input-container>
            </div>

            
            
            
            <div layout="row" layout-align="center center">
              <md-switch arial-label="Se souvenir de moi" ng-model="app.creds.rememberLogin">
                Se souvenir de moi
              </md-switch>
            </div>
          </form>
          <div ng-cloak="ng-cloak" layout-align="end end" layout="row">
            <md-button aria-label="&#192; propos" class="md-icon-button" ng-click="app.showAbout()">
              <md-icon class="md-fg">info</md-icon>
            </md-button>
          </div>
        </div>
      </div>
    </md-content>
  </main>

  <script id="aboutBox.html" type="text/ng-template">
    <md-dialog flex="50" flex-xs="100">
      <md-dialog-content class="md-dialog-content">
        <p><a href="http://sogo.nu/" target="_new">sogo.nu</a></p>
        <p>Version 3.0.2 (@shiva.inverse 201603051728)</p>
        <br />
        <p>Développé par la compagnie Inverse, SOGo est un collecticiel complet mettant l'emphase sur la simplicité et l'extensibilité.<br/><br/>
SOGo propose une interface Web moderne basée sur AJAX ainsi qu'un accès par de nombreux clients natifs (comme Mozilla Thunderbird et Lightning et Apple iCal) par l'utilisation de protocoles standards tel que CalDAV et CardDAV.<br/><br/>
Ce programme est un logiciel libre ; vous pouvez le redistribuer et/ou le modifier conformément aux dispositions de la <a href="http://gnu.org/licenses/gpl.html">Licence Publique Générale GNU</a>, telle que publiée par la Free Software Foundation ; version 2 de la licence, ou encore (à votre choix) toute version ultérieure. Ce programme est distribué dans l’espoir qu’il sera utile, mais SANS AUCUNE GARANTIE.<br/><br/>
Plusieurs <a href="http://www.sogo.nu/en/support/community.html">types de soutien</a> sont offerts.</p>
        
      </md-dialog-content>
      <md-dialog-actions>
        <md-button ng-click="about.closeDialog()">Fermer</md-button>
      </md-dialog-actions>
    </md-dialog>
  </script>


          
          <script type="text/javascript">
            var ApplicationBaseURL = '/SOGo/so/SOGo';
            var ResourcesURL = '/SOGo.woa/WebServerResources';
            var minimumSearchLength = 2;
            var minimumSearchLengthLabel = 'Enter at least 2 characters';
            
            
              var DebugEnabled = false;
            
            
            
              var IsSuperUser = false;
            
            
            
              var usesCASAuthentication = false;
            
            

            // This is the equivalent of an AJAX call to /SOGo/so/_UserLogin_/date
            var currentDay = {"year": "2016", "weekday": "Jeudi", "secondsBeforeTomorrow": 11547, "abbr": {"month": "Jun", "weekday": "Jeu"}, "day": "09", "month": "Juin"};
            var clabels = {"Any user with an account on this system will be able to access your mailbox \"%{0}\". Are you certain you trust them all?": "Tout utilisateur ayant un compte sur ce système aura accès à votre boîte «%{0}». Voulez-vous vraiment faire confiance à tous ces utilisateurs?", "a2_Friday": "Ve", "May": "Mai", "Help": "Aide", "Important": "Important", "Log Console (dev.)": "Journal (dév.)", "Owner": "Propriétaire", "a2_Wednesday": "Me", "Reminder": "Rappel", "delegate is organizer": "L'adresse spécifiée correspond à l'organisateur. Veuillez entrer un autre délégué.", "Close": "Fermer", "Jul": "Jul", "a2_Monday": "Lu", "The user rights cannot be edited for this object!": "Les droits sur cet objet ne peuvent pas être édités.", "Jan": "Jan", "Toggle Menu": "Menu bascule", "To Do": "À faire", "Potentially anyone on the Internet will be able to access your calendar \"%{0}\", even if they do not have an account on this system. Is this information suitable for the public Internet?": "N'importe quel internaute aura potentiellement accès à votre calendrier «%{0}», même s'il n'a pas de compte sur ce système. Est-ce que le contenu de votre calendrier est adapté à une telle visibilité?", "Add User": "Ajouter un utilisateur", "Unable to rename that folder!": "Impossible de renommer ce dossier.", "Preferences": "Préférences", "Snooze for ": "Rappel dans ", "You cannot (un)subscribe to a folder that you own!": "Vous ne pouvez pas vous (dés)abonner à vos propres dossiers!", "Edit User Rights": "Édition des droits", "noEmailForDelegation": "Vous devez spécifier l'adresse de la personne à qui vous voulez déléguez votre invitation.", "Sep": "Sep", "July": "Juillet", "5 minutes": "5 minutes", "Any user with an account on this system will be able to access your calendar \"%{0}\". Are you certain you trust them all?": "Tout utilisateur ayant un compte sur ce système aura accès à votre calendrier «%{0}». Voulez-vous vraiment faire confiance à tous ces utilisateurs?", "September": "Septembre", "Work": "Travail", "a2_Thursday": "Je", "Unable to subscribe to that folder!": "Impossible de s'abonner à ce dossier.", "10 minutes": "10 minutes", "Aug": "Aoû", "Add...": "Ajouter...", "You have already subscribed to that folder!": "Vous êtes déja abonné à ce dossier.", "Can't contact server": "Une erreur est survenue lors de la connexion au serveur. Veuillez réessayer plus tard.", "Start": "Début", "June": "Juin", "Access Rights": "Droits d'accès", "Sorry, the user rights can not be configured for that object.": "Désolé, les droits d'accès ne peuvent être configurés pour cet objet.", "March": "Mars", "Mail": "Courrier", "You cannot subscribe to a folder that you own!": "Impossible de vous abonner à un dossier qui vous appartient.", "Mar": "Mar", "Yes": "Oui", "Publish the Free\/Busy information": "Publier l'occupation du temps", "Potentially anyone on the Internet will be able to access your address book \"%{0}\", even if they do not have an account on this system. Is this information suitable for the public Internet?": "N'importe quel internaute aura potentiellement accès à votre carnet d'adresses «%{0}», même s'il n'a pas de compte sur ce système. Est-ce que le contenu de votre calendrier est adapté à une telle visibilité?", "45 minutes": "45 minutes", "Nov": "Nov", "PM": "PM", "Anybody accessing this resource from the public area": "Quiconque accède à cette ressource via l'espace public", "Apr": "Avr", "SOGo": "SOGo", "Home": "Accueil", "Give Access": "Appliquer les droits", "No": "Non", "Unable to unsubscribe from that folder!": "Impossible de se désabonner de ce dossier.", "Warning": "Avertissement", "1 hour": "1 heure", "Any user not listed above": "Tout utilisateur du système non-listé ci-dessus", "Later": "Peut attendre", "Calendar": "Agenda", "30 minutes": "30 minutes", "Loading": "Chargement", "August": "Août", "Keep Private": "Garder privé", "Administration": "Administration", "Any Authenticated User": "Tout utilisateur identifié", "Right Administration": "Partage", "Save": "Enregistrer", "Any user with an account on this system will be able to access your address book \"%{0}\". Are you certain you trust them all?": "Tout utilisateur ayant un compte sur ce système aura accès à votre carnet d'adresses «%{0}». Voulez-vous vraiment faire confiance à tous ces utilisateurs?", "a2_Saturday": "Sa", "Modules": "Modules", "delegate is a participant": "Le délégué est déjà un participant.", "November": "Novembre", "AM": "AM", "a2_Tuesday": "Ma", "April": "Avril", "1 day": "1 jour", "delegate is a group": "L'adresse spécifiée correspond à un groupe. Vous ne pouvez déléguer qu'à une personne.", "noJavascriptError": "SOGo requiert l'utilisation de Javascript. Veuillez vous assurer que cette option est disponible et activée dans votre fureteur.", "Due Date": "Échéance", "Feb": "Fév", "January": "Janvier", "Oct": "Oct", "Remove": "Enlever", "Location": "Lieu", "Dec": "Déc", "Cancel": "Annuler", "You are not allowed to access this module or this system. Please contact your system administrator.": "Vous n'êtes pas autorisé à accéder à ce module ou ce système. Veuillez contacter votre administrateur système.", "a2_Sunday": "Di", "Address Book": "Carnet d'adresses", "A folder by that name already exists.": "Un dossier du même nom existe déjà.", "Jun": "Jun", "No such user.": "Aucun utilisateur.", "You cannot unsubscribe from a folder that you own!": "Impossible de vous désabonner d'un dossier qui vous appartient.", "December": "Décembre", "noJavascriptRetry": "Réessayer", "Public Access": "Accès public", "Subscribe User": "Abonner l'utilisateur", "You don't have the required privileges to perform the operation.": "Vous n'avez pas les privilèges requis pour effectuer cette opération.", "OK": "OK", "Personal": "Personnel", "October": "Octobre", "February": "Février", "User": "Utilisateur", "Disconnect": "Quitter", "You cannot create a list in a shared address book.": "Impossible de créer une liste dans un dossier partagé.", "Vacation message is enabled": "Votre message d'absence prolongée est activé", "15 minutes": "15 minutes"};
            var labels = {"Your account was locked due to too many failed attempts.": "Votre compte a été bloqué suite à un nombre élevé de tentatives d'authentification infructueuses.", "Icelandic": "Íslenska", "NorwegianNynorsk": "Norsk nynorsk", "Swedish": "Svenska", "Missing search parameter": "Paramètre de recherche manquant", "English": "English", "Authentication Failed": "L'authentification a échoué", "The passwords do not match. Please try again.": "Les mots de passe ne sont pas identiques. Essayez de nouveau.", "BrazilianPortuguese": "Português brasileiro", "Close": "Fermer", "Domain": "Domaine", "AboutBox": "Développé par la compagnie Inverse, SOGo est un collecticiel complet mettant l'emphase sur la simplicité et l'extensibilité.<br\/><br\/>\nSOGo propose une interface Web moderne basée sur AJAX ainsi qu'un accès par de nombreux clients natifs (comme Mozilla Thunderbird et Lightning et Apple iCal) par l'utilisation de protocoles standards tel que CalDAV et CardDAV.<br\/><br\/>\nCe programme est un logiciel libre ; vous pouvez le redistribuer et\/ou le modifier conformément aux dispositions de la <a href=\"http:\/\/gnu.org\/licenses\/gpl.html\">Licence Publique Générale GNU<\/a>, telle que publiée par la Free Software Foundation ; version 2 de la licence, ou encore (à votre choix) toute version ultérieure. Ce programme est distribué dans l’espoir qu’il sera utile, mais SANS AUCUNE GARANTIE.<br\/><br\/>\nPlusieurs <a href=\"http:\/\/www.sogo.nu\/en\/support\/community.html\">types de soutien<\/a> sont offerts.", "cookiesNotEnabled": "Vous ne pouvez vous authentifier car les témoins (cookies) de votre navigateur Web sont désactivés. Activez les témoins dans votre navigateur Web et essayez de nouveau.", "Croatian": "Hrvatski", "Password must not be empty.": "Le mot de passe ne doit pas être vide.", "Please wait...": "Veuillez patienter...", "minutes": "minutes", "Password about to expire": "Expiration du mot de passe", "Download": "Télécharger", "About": "À propos", "Missing type parameter": "Paramètre de type manquant", "Wrong username or password.": "Mauvais nom d'utilisateur ou mot de passe.", "Your account was locked due to an expired password.": "Votre compte a été bloqué car votre mot de passe est expiré.", "Language": "Langue", "Password Grace Period": "Période de grâce pour le mot de passe", "Change your Password": "Changez votre mot de passe", "Arabic": "العربية", "alternativeBrowserSafari": "Comme alternative, vous pouvez aussi utiliser Safari.", "Slovak": "Slovensky", "hours": "heures", "You have %{0} logins remaining before your account is locked. Please change your password in the preference dialog.": "Vous avez %{0} connexions restantes avant que votre compte ne soit bloqué. Veuillez changer votre mot de passe à partir du panneau de préférences.", "Username": "Nom d'utilisateur", "SpanishArgentina": "Español (Argentina)", "Unhandled HTTP error code: %{0}": "Code HTTP non-géré: %{0}", "Finnish": "Suomi", "Unhandled policy error: %{0}": "Erreur inconnue pour le ppolicy: %{0}", "Unhandled error response": "Erreur inconnue", "Password change failed - Password is too short": "Échec au changement - mot de passe trop court", "browserNotCompatible": "La version de votre navigateur Web n'est présentement pas supportée par ce site. Nous recommandons d'utiliser Firefox. Vous trouverez un lien vers la plus récente version de ce navigateur ci-dessous:", "alternativeBrowsers": "Comme alternative, vous pouvez aussi utiliser les navigateurs suivants:", "Password change failed - Permission denied": "Échec au changement - mauvaises permissions", "Welsh": "Cymraeg", "Polish": "Polski", "Confirmation": "Confirmation", "Password change is not supported.": "Changement de mot de passe non-supporté.", "title": "SOGo", "Czech": "Česky", "Hungarian": "Magyar", "Your password has expired, please enter a new one below": "Votre mot de passe est expiré, veuillez entrer un nouveau mot de passe", "Remember username": "Se souvenir de moi", "Connect": "Connexion", "Password change failed - Insufficient password quality": "Échec au changement - qualité insuffisante", "Login failed due to unhandled error case": "Authentification a échouée pour une raison inconnue", "German": "Deutsch", "Danish": "Dansk (Danmark)", "Slovenian": "Slovenščina", "SpanishSpain": "Español (España)", "seconds": "secondes", "Password change failed - Password is in history": "Échec au changement - mot de passe dans l'historique", "French": "Français", "Password": "Mot de passe", "Password change failed": "Échec au changement", "Your password is going to expire in %{0} %{1}.": "Votre mot de passe va expirer dans %{0} %{1}.", "New password": "Nouveau mot de passe", "Password change failed - Password is too young": "Échec au changement - mot de passe trop récent", "Dutch": "Nederlands", "days": "jours", "Cancel": "Annuler", "Ukrainian": "Українська", "Russian": "Русский", "ChineseTaiwan": "Chinese (Taiwan)", "choose": "Choisir ...", "The password was changed successfully.": "Votre mot de passe a bien été changé.", "Basque": "Euskara", "NorwegianBokmal": "Norsk bokmål", "Italian": "Italiano", "Catalan": "Català"};
          </script>

          
          <script type="text/javascript" src="https://mailhost.synopsis-erp.com/SOGo.woa/WebServerResources/js/vendor/lodash.min.js"></script>
          <script type="text/javascript" src="https://mailhost.synopsis-erp.com/SOGo.woa/WebServerResources/js/vendor/angular.min.js"></script>
          <script type="text/javascript" src="https://mailhost.synopsis-erp.com/SOGo.woa/WebServerResources/js/vendor/angular-animate.min.js"></script>
          <script type="text/javascript" src="https://mailhost.synopsis-erp.com/SOGo.woa/WebServerResources/js/vendor/angular-sanitize.min.js"></script>
          <script type="text/javascript" src="https://mailhost.synopsis-erp.com/SOGo.woa/WebServerResources/js/vendor/angular-aria.min.js"></script>
          <script type="text/javascript" src="https://mailhost.synopsis-erp.com/SOGo.woa/WebServerResources/js/vendor/angular-messages.min.js"></script>
          <script type="text/javascript" src="https://mailhost.synopsis-erp.com/SOGo.woa/WebServerResources/js/vendor/angular-material.js"></script>
          <script type="text/javascript" src="https://mailhost.synopsis-erp.com/SOGo.woa/WebServerResources/js/vendor/angular-ui-router.min.js"></script>

          
          
          
            <script type="text/javascript" src="https://mailhost.synopsis-erp.com/SOGo.woa/WebServerResources/js/Main.js?lm=1461336459"></script>
          
            <script type="text/javascript" src="https://mailhost.synopsis-erp.com/SOGo.woa/WebServerResources/js/Common.js?lm=1461336459"></script>
          
          
        
        

        <noscript>
          <div class="javascriptPopupBackground">
          </div>
          <div class="javascriptMessagePseudoWindow noJavascriptErrorMessage">
            SOGo requiert l'utilisation de Javascript. Veuillez vous assurer que cette option est disponible et activ&#233;e dans votre fureteur.
            <br />
            <br />
            <a class="button">
              R&#233;essayer
            </a>
          </div>
        </noscript>

      </body>
    </html>
  

  
