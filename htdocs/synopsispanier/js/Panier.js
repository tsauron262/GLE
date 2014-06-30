/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
timeAtt = 2000;
tabID = new Array();
markerActif = null;

function modPan(idReferent, type, id,action){
    ajaxPerso(DOL_URL_ROOT + '/synopsispanier/ajax/manipPanier.php' ,'idObject='+id+'&type='+type+'&idReferent='+idReferent+'&action='+action,function(msg) {
                if(msg == "erreur")
                alert("il y a eu une erreur");
            else
                eval(msg);
            if (markerActif && action != 'sup')
                        markerActif.setIcon (DOL_URL_ROOT + '/google/img/imageMarker_green.png' );
            else if (markerActif && action == 'sup')
                        markerActif.setIcon ('http://maps.gstatic.com/intl/fr_ALL/mapfiles/markers/marker_sprite.png' );
            });
}

function ajaxPerso(url, datas, colback){
    jQuery.ajax({
            url: url,
            data: datas,
            datatype: "xml",
            type: "POST",
            cache: false,
            success: colback,
            error: function(){
                setTimeout(function(){
                    timeAtt = timeAtt*2;
                   ajaxPerso(url, datas, colback); 
                }, timeAtt);
            }
            
        });
}
