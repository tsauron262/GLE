<?php
require_once('pre.inc.php');
$url =  $conf->global->GLE_RT_ROOT;
$rtUrl = $url .'/index.html';
$rtUser=$user->login;
$rtPass=$user->pass_indatabase;

$tmp = md5(time());
$expire = time()+60*60;
setcookie("loginCookieValue",$tmp,$expire,"/");
$requete = "DELETE FROM Babel_GMAO_login WHERE userid =".$user->id;
$sql = $db->query($requete);
$requete = "INSERT INTO Babel_GMAO_login (userid,cookieVal) VALUES (".$user->id.",'".$tmp."')";
$sql = $db->query($requete);

$rtUrl .= '?user='.$rtUser."&pass=".$rtPass;
//print $rtUrl;
//require_once('../main.inc.php');
$js = '<style type="text/css">.vmenu{ display: none;}</style>';
$js .= "<script type='text/javascript' src='".DOL_URL_ROOT."/Synopsis_Common/jquery/jquery.ba-resize.min.js'></script>";
$js .= <<<EOF
<script>
jQuery(document).ready(function(){
  // Append an iFrame to the page.
  var iframe = jQuery('#iframeRT');

  // Called once the Iframe's content is loaded.
  iframe.load(function(){
    // The Iframe's child page BODY element.
    var iframe_content = iframe.contents().find('body');

    // Bind the resize event. When the iframe's size changes, update its height as
    // well as the corresponding info div.
    iframe_content.resize(function(){
      var elem = jQuery(this);
      var size = elem.outerHeight( true );
      if (size < 610) size = 610;
      // Resize the IFrame.
      iframe.css({ height: size });

    });

    // Resize the Iframe and update the info div immediately.
    iframe_content.resize();
  });
});
    function iFrameHeight()
    {
        //find the height of the internal page
        var  h = jQuery('#iframeRT')[0].contentWindow.document.body.scrollHeight;
        var  w = document.documentElement.clientWidth;
        //change the height of the iframe
        jQuery('#iframeRT').css('height',h);
        jQuery('#iframeRT').css('width',w);
    }

</script>
EOF;
llxHeader($js, "Ticket interface",1);

print "<span><a href='index.php'>Retour</a></span><br/>";
print "<iframe scrolling='NO'  frameborder='0'  onLoad='iFrameHeight();' src='".$rtUrl."' id='iframeRT' style='width:1050px; '>";
print "</iframe>";

?>
