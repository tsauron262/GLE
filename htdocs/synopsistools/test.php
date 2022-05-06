<?php


//$chaine = file_get_contents("/data/synchro/test.txt");
//
//        $chaine = str_replace("\x0D\x0A\x20", '', $chaine);
//
////$chaine = str_replace(array("\x0A\x20", "\x0D\x0A\x20"), "", $chaine);
//
//echo "<textarea>".$chaine."</textarea>";
//
//
//die('ll');
//
//
//$file = file_get_contents("/Users/tommy/Downloads/2f332e25-97d0-bc4b-b143-a4af33e58bd8.ics");
//$file = str_replace("\x0A\x20", '', $file);
//die ($file);

require("../main.inc.php");
llxHeader();

//Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36
//Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.4 Safari/605.1.15
//Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:98.0) Gecko/20100101 Firefox/98.0
?>

<script>
    jQuery("document").ready(function(){
        var src = "https://erp.bimp.fr/b/"+window.location.search.replace("?", "");
        var ua = navigator.userAgent;
        if(ua.indexOf("Chrome") == -1 && ua.indexOf("Firefox") == -1){
            window.location = src;
            
            jQuery(".div_iframe").append("Votre navigateur n\'est pas compatible. <a href='"+src+"'>Merci de cliquer ici</a>");
           
        }
        else{
            var iframe = document.createElement("iframe");
            iframe.src = src;
            /* style peut être modifiée */
            iframe.style["width"] = "100%";
            iframe.style["min-height"] = "650px";
            iframe.style["margin-top"] = "0";
            /* fin style */
            jQuery(".div_iframe").each(function(){
                this.appendChild(iframe);
            });
        }
    });
</script>
<div class="div_iframe"></div>

<script>
    jQuery("document").ready(function(){
        var src = "https://erp2.bimp.fr/bimpinv01072020//bimpinterfaceclient/client.php?"+window.location.search.replace("?", "");
        var ua = navigator.userAgent;
        if(ua.indexOf("Chrome") == -1 && ua.indexOf("Firefox") == -1){
            window.location = src;
            
            jQuery(".div_iframe").append("Votre navigateur n\'est pas compatible. <a href='"+src+"'>Merci de cliquer ici</a>");
           
        }
        else{
            var iframe = document.createElement("iframe");
            iframe.src = src;
            /* style peut être modifiée */
            iframe.style["width"] = "100%";
            iframe.style["min-height"] = "650px";
            iframe.style["margin-top"] = "0";
            /* fin style */
            jQuery(".div_iframe").each(function(){
                this.appendChild(iframe);
            });
        }
    });
</script>
<div class="div_iframe"></div>