$(window).load(function(){
    heightDif = $(".fiche").innerHeight() - $(".tabBar").height(); //hauteur du rest (ne change pas
    if($("div.tmenudiv").is(':visible')){
    $(window).resize(function(){
        traiteScroll(heightDif);
    });
    traiteScroll(heightDif);
}
    
    $("#mainmenua_SynopsisTools.tmenudisabled").parent().parent().hide();
    
    
    ajNoteAjax();   
});

function dialogConfirm(url, titre, yes, no, id){
    if (confirm(titre)) {
        window.location = url;
    } 
}

function traiteScroll(heightDif){
    coef = 1;
    scrollY  = $(window).scrollTop() * coef - 100;
    if($(".fiche").scrollTop()> scrollY)
        scrollY  = $(".fiche").scrollTop();
    if($(".tabBar").scrollTop()> scrollY)
        scrollY  = $(".tabBar").scrollTop();
    height =parseInt(window.innerHeight);
    var i = 0;
    $(".fiche").parent().parent().children("td").each(function(){
        i = i+1;
    });
    //    if(height > 560 && i < 3)
    hauteurMenu = parseInt($("div.vmenu").innerHeight()) + parseInt($("#tmenu_tooltip").innerHeight())+30;
    if(height > hauteurMenu && i < 3){//On active le scroll 2
        $(".fiche").addClass("reglabe");
        heightNew = ($(".fiche").innerHeight() - heightDif) - 20;
        if(heightNew > 300){//On active le scroll 3 (le scroll 2 ne doit plus étre utile
            $(".tabBar").height(heightNew);
            $(".tabBar").addClass("reglabe2");
            $(".tabBar").scrollTop(scrollY);
        }
        else{//On désactive le scroll 3 (le scroll 2 doit prendre le relai)
            $(".tabBar").removeClass("reglabe2");
            $(".tabBar").height('auto');
            $(".fiche").scrollTop(scrollY);
        }
    //        alert(heightAv+" "+heightAp);
    }
    else{//On desactive les scroll2 et 3
        $(".fiche").removeClass("reglabe");
        $(".tabBar").removeClass("reglabe2");
        $(".tabBar").height('auto');
        $(window).scrollTop(scrollY);
    }
//    document.body.scrollTop = scrollY;
}


function ajNoteAjax(){
    fermable = true;
    datas = 'url='+window.location;
    jQuery.ajax({
        url:DOL_URL_ROOT+'/Synopsis_Tools/ajax/note_ajax.php',
        data:datas,
        datatype:"xml",
        type:"POST",
        cache: false,
        success:function(msg){
            if(msg != "0"){
                //                var htmlDiv  = '<div class="noteAjax"><div class="control"><input class="controlBut" type="button" value="<"/></div><div class="note">Note (publique) :<br><div class="editable" id="notePublicEdit" title="Editer">'+msg+'</div></div></div>';
                //                $('.tabBar').append(htmlDiv);
                classEdit = "";
                if(msg.indexOf("[1]") > -1){
                    classEdit = "editable";
                    msg = msg.replace("[1]", "");
                }
                var htmlDiv  = '<div class="noteAjax"><div class="note">Note (publique) :<br><div class="'+classEdit+'" id="notePublicEdit" title="Editer">'+msg+'</div></div></div>';
                $('.fiche').append(htmlDiv);
                //                var htmlDiv  = '<div class="control"><input class="controlBut" type="button" value="<"/></div>';
                //                $('a#note').append(htmlDiv);
                //                $('.tabBar > table > tbody').first("td").append('<td rowspan"9">'+htmlDiv+'</td>');
                $('a#note, .noteAjax').hover(shownNote, hideNote);
                $('a#note').addClass("lienNote");
                
                //                $(".controlBut").click(function(){
                //                    if($(this).val() == "<"){
                //                        $(this).val(">");
                //                    }
                //                    else{
                //                        $(this).val("<");
                //                    }     
                //                    shownHideNote();   
                //                })
                
                jQuery('.editable').click(function(){
                    fermable = false;
                    $(this).parent().find('.editable').html('<textarea class="editableTextarea" style="width:99%;height:99%; background:none;">'+jQuery(this).html().split('<br>').join('\n')+"</textarea>");
                    jQuery(this).removeClass('editable');
                    $(".editableTextarea").focus();
                    $(".editableTextarea").val($(".editableTextarea").val()+"\n");
                    $(".editableTextarea").focusout(function(){
                        fermable = true;
                        hideNote();
                        datas = datas+'&note='+$(".editableTextarea").val();
                        jQuery.ajax({
                            url:DOL_URL_ROOT+'/Synopsis_Tools/ajax/note_ajax.php',
                            data:datas,
                            datatype:"xml",
                            type:"POST",
                            cache: false,
                            success:function(msg){
                                if(msg.indexOf("[1]") > -1){
                                    msg = msg.replace("[1]", "");
                                }
                                $("#notePublicEdit").html(msg);
                                $("#notePublicEdit").addClass("editable");
                            }
                        });
                    });
                });
            }
        }
    });
    
    function shownHideNote(){
        $(".noteAjax .note").animate({
            width: 'toggle'
        });
    }
    function shownNote(){
        $(".noteAjax .note").slideDown({
            width: 'toggle'
        });
        fermer = false;
    }
    function hideNote(){
        if(fermable){
            fermer = true;
            setTimeout(function(){
                if(fermer)
                    $(".noteAjax .note").slideUp({
                        width: 'toggle'
                    });
            }, 500);
        }
    }
}
