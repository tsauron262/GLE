<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 13 juil. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : index.php
  * GLE-1.2
  */


    require_once("./pre.inc.php");
//Auth ajax local
if ($_COOKIE['logged'] != "OK")
{
        $conf->css  = "theme/".$conf->theme."/".$conf->theme.".css";
        // Si feuille de style en php existe
        if (file_exists(DOL_DOCUMENT_ROOT.'/'.$conf->css.".php")) $conf->css.=".php";

        header('Cache-Control: Public, must-revalidate');

        // Ce DTD est KO car inhibe document.body.scrollTop
        //print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
        // Ce DTD est OK
        print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">'."\n";

        // En tete html
        print "<html>\n";
        print "<head>\n";
        print '<meta name="robots" content="noindex,nofollow">'."\n";      // Evite indexation par robots
        print "<title>GLE login</title>\n";

$header = <<<EOJS

      <script type="text/javascript" src="jquery/jquery-1.3.2.js" ></script>
      <script type="text/javascript" src="jquery/md5.js" ></script>

    <script type='text/javascript'>
jQuery(document).ready(function() {

    jQuery("#password").keypress(function(event) {
          if (event.keyCode == '13') {
            var login = jQuery('#username').val();
            var passs = jQuery('#password').val();
            var pass = MD5(passs);
            jQuery.ajax({
                   type: "POST",
                   url: "ajax/login.php",
                   data: "login="+login+"&pass="+pass,
                   success: function(msg){
                        if (jQuery(msg).find('logged').text()=="OK")
                        {
                            //login
                            //Set cookie
                            Set_Cookie("logged","OK");
                            Set_Cookie("soccode",jQuery(msg).find('soccode').text());
                            location.reload();
                        } else {
                            //try again
                            jQuery("#message").replaceWith("<div id='message' style='display: block; background-color: #FF0000;'>Login/Mot de passe incorrect</div>");
                        }
                   },
                   error: function()
                   {
                            jQuery("#message").replaceWith("<div id='message' style='display: block; background-color: #FF0000;'>Login/Mot de passe incorrect</div>");
                   }
           });
          }
    });

    jQuery("#login_btn").click(function(){
        var login = jQuery('#username').val();
        var passs = jQuery('#password').val();
        var pass = MD5(passs);
        jQuery.ajax({
               type: "POST",
               url: "ajax/login.php",
               data: "login="+login+"&pass="+pass,
               success: function(msg){
                    if (jQuery(msg).find('logged').text()=="OK")
                    {
                        //login
                        //Set cookie
                        Set_Cookie("logged","OK");
                        Set_Cookie("soccode",jQuery(msg).find('soccode').text());
                        location.reload();
                    } else {
                        //try again
                        jQuery("#message").replaceWith("<div id='message' style='display: block; background-color: #FF0000;'>Login/Mot de passe incorrect</div>");
                    }
               },
               error: function()
               {
                        jQuery("#message").replaceWith("<div id='message' style='display: block; background-color: #FF0000;'>Login/Mot de passe incorrect</div>");
               }
       });
   });
});

function Set_Cookie( name, value, expires, path, domain, secure )
{
    // set time, it's in milliseconds
    var today = new Date();
    today.setTime( today.getTime() );

    /*
    if the expires variable is set, make the correct
    expires time, the current script below will set
    it for x number of days, to make it for hours,
    delete * 24, for minutes, delete * 60 * 24
    */
    if ( expires )
    {
    expires = expires * 1000 * 60 * 60 * 24;
    }
    var expires_date = new Date( today.getTime() + (expires) );

    document.cookie = name + "=" +escape( value ) +
    ( ( expires ) ? ";expires=" + expires_date.toGMTString() : "" ) +
    ( ( path ) ? ";path=" + path : "" ) +
    ( ( domain ) ? ";domain=" + domain : "" ) +
    ( ( secure ) ? ";secure" : "" );
}

</script>
EOJS;
llxHeader($header,"",1);

    //print jquery login form

    //HERE


        print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/'.$conf->css.'">'."\n";
        // This one is required for all Ajax features
        print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/lib/prototype.js"></script>'."\n";
        // This one is required fox boxes
        print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/scriptaculous.js"></script>'."\n";

//Google analitics for the demo
//print <<<EOF
//<script type="text/javascript">
//var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
//document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
//</script>
//<script type="text/javascript">
//try {
//var pageTracker = _gat._getTracker("UA-8944436-2");
//pageTracker._trackPageview();
//} catch(err) {}</script>
//EOF;
        // PWC css
        print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/alert.css">'."\n";
        // Scriptaculous used by PWC
        print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/effects.js"></script>'."\n";
        print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/scriptaculous/src/controls.js"></script>'."\n";
        // PWC js
        print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/pwc/window.js"></script>'."\n";

        print '<style type="text/css">'."\n";
        print '<!--'."\n";
        print '#login {';
        print '  margin-top: 70px;';
        print '  margin-bottom: 30px;';
        print '  text-align: center;';
        print '  font: 12px arial,helvetica;';
        print '}'."\n";
        print '#login table {';
        print '  border: 1px solid #C0C0C0;';
        if (file_exists(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/login_background.png'))
        {
            print 'background: #F0F0F0 url('.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/login_background.png) repeat-x;';
        }
        else
        {
            print 'background: #F0F0F0 url('.DOL_URL_ROOT.'/theme/login_background.png) repeat-x;';
        }
        print 'font-size: 12px;';
        print '}'."\n";
        print '-->'."\n";
        print '</style>'."\n";
        print '<script language="javascript" type="text/javascript">'."\n";
        print "function donnefocus() {\n";
        if (! $_REQUEST["username"]) print "document.getElementById('username').focus();\n";
        else print "document.getElementById('password').focus();\n";
        print "}\n";
        print '</script>'."\n";
        print '</head>'."\n";

        // Body
        print '<body class="body" style="max-width: 400px; overflow: hidden;" onload="donnefocus(); moveLogin()">';
print <<<EOF
    <script type="text/javascript">
        function moveLogin()
        {
            var width = getsize("w");
            var loginFormWidth = 450;
            width = width/2 - (loginFormWidth/2);
            Effect.MoveBy(document.getElementById("toMove"), 150, width);
        }
        function getsize(i) {
          var myWidth = 0, myHeight = 0;
          if( typeof( window.innerWidth ) == 'number' ) {
            //Non-IE
            w = window.innerWidth;
            h = window.innerHeight;
          } else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
            //IE 6+ in 'standards compliant mode'
            w = document.documentElement.clientWidth;
            h = document.documentElement.clientHeight;
          } else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
            //IE 4 compatible
            w = document.body.clientWidth;
            h = document.body.clientHeight;
          }
          if (i=="w")
          {
              return (w);
          } else {
            return (h);
          }
    }
    </script>
EOF;
        $urllogo=DOL_URL_ROOT.'/theme/'.$conf->theme.'/Logo-72ppp.png';
    //detection du browser => si GSM switch


        print "<a href='http://www.synopsis-erp.com style='border:0px; position: fixed;' ><img width=300px style='position: fixed; bottom: 0px;border:0px;' src='".$urllogo."' /></a>";

        print "<div id='toMove' style='left: 0; top: 100px; width:450px; height: 300px; position: fixed;'>";
        // Start Form
        print '<form id="login" name="login" method="post" action="';
        print $_SERVER['PHP_SELF'];
        print $_SERVER["QUERY_STRING"]?'?'.$_SERVER["QUERY_STRING"]:'';
        print '" style="width:450px; max-height: 450px;">';

        // Table 1
        print '<table cellpadding="0" cellspacing="0" border="0"  width="450" style="-moz-border-radius-bottomleft:6px;
-moz-border-radius-bottomright:6px; -moz-border-radius-topleft:6px;
-moz-border-radius-topright:6px;
-webkit-border-top-left-radius:6px; -webkit-border-top-right-radius:6px;-webkit-border-bottom-left-radius:6px;-webkit-border-bottom-right-radius:6px ">';

            print '<tr class="vmenu"><td align="center">GLE ++ - Acc&egrave;s client</td></tr>';
        print '</table>';
        print '<br>';
    //    var_dump($conf->global);
        //MAIN_INFO_SOCIETE_LOGO
        //$conf->societe->
        //print "<img src='' >";

        // Table 2

        print '<table cellpadding="2" width="450" style="-moz-border-radius-bottomleft:6px;
-moz-border-radius-bottomright:6px; -moz-border-radius-topleft:6px;
-moz-border-radius-topright:6px; -webkit-border-top-left-radius:6px; -webkit-border-top-right-radius:6px;-webkit-border-bottom-left-radius:6px;-webkit-border-bottom-right-radius:6px">';

        print '<tr><td colspan="3">&nbsp;</td></tr>';

        print '<tr>';

        // Login field
        // Show logo (search in order: small company logo, large company logo, theme logo, common logo)
        $width=0;
        $urllogo=DOL_URL_ROOT.'/theme/login_logo.png';
        if (! empty($mysoc->logo_small) && is_readable($conf->societe->dir_logos.'/thumbs/'.$mysoc->logo_small))
        {
            $urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('/thumbs/'.$mysoc->logo_small);
        }
        elseif (! empty($mysoc->logo_small) && is_readable($conf->societe->dir_logos.'/'.$mysoc->logo))
        {
            $urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode($mysoc->logo);
            $width=96;
        }
        elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/login_logo.png'))
        {
            $urllogo=DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/login_logo.png';
        }
        print '<td rowspan="2" align="center"><img title="'.$title.'" src="'.$urllogo.'"';
        if ($width) print ' width="'.$width.'"';
        print '></td><td>';
    print "<div id='message' style='display: none;'></div>";
    print '<div class="border" id="login">
  <table><tr><td>
  Nom du compte:
  </td><td>  <input type="text" size="15" name="username" id="username" />

    </td></tr><tr><td>
    Mot de passe:</td>
    <td><input type="password" size="15" name="password" id="password" /></td></tr>
</table>
    <br />
    <input type="button" accesskey="l" id="login_btn" name="login" value="Login" />

  </p>
</div>  ';


        print '</td></tr>'."\n";


        print '<tr><td colspan="3">&nbsp;</td></tr>'."\n";
        print '</table>';
        // Message
        if ($_SESSION["dol_loginmesg"])
        {
            print '<center><table style="border:0px" width="60%"><tr><td align="center" class="small"><div class="error ui-state-error">';
            print $_SESSION["dol_loginmesg"];
            $_SESSION["dol_loginmesg"]="";
            print '</div></td></tr></table></center>';
        }
        print "</div>";

        print '</form>';

    //fin login
        // Fin entete html
        print "\n</body>\n</html>";


} else {
    header('Location: clientAccess.php#ui-tab');
}


?>
