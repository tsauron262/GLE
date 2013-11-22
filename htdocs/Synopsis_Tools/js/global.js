$(window).load(function() {
    heightDif = $(".fiche").innerHeight() - $(".tabBar").height(); //hauteur du rest (ne change pas
    if ($("div.tmenudiv").is(':visible')) {
        $(window).resize(function() {
            traiteScroll(heightDif);
        });
        $("a").click(function(){
            setTimeout(function(){
                traiteScroll(heightDif);
            }, 500);
        });
        traiteScroll(heightDif);
    }
    
    $("#mainmenua_SynopsisTools.tmenudisabled").parent().parent().hide();
    
    
    ajNoteAjax();
    
    
    var datas = 'url=' + window.location;
    datas += '&type=consigne';
    editAjax(jQuery('.consigne.editable'), datas);
    
    //    $(".ui-search-toolbar input").keypress(function(e) {
    //	//alert(e.keyCode);
    //	if(e.keyCode == 13) {
    //		alert('Enter key was pressed.');
    //	}
    //        else
    //		alert('Enter oser    was pressed.'+e.keyCode );
    //    });
    
    $(".ui-search-toolbar input").focusout(function() {
        setTimeout(function() {
            var e = jQuery.Event("keypress", {
                keyCode: 13
            });
            $(".ui-search-toolbar input").trigger(e);
        }, 500);
    });
    
    
//    if ((navigator.appName).search(/Safari.+/) != -1){
        $(".formdoc a").each(function(){
            if($(this).attr('href').search(/.pdf/i) >= 0)
                $(this).attr("target", "");
        });
//    }
    initFormChrono();
    
    
    
    //    $(".nonactif").hide();
    
    $(".syntab li").click(function(){
        $(".syntab li").removeClass("actif");
        $(this).addClass("actif");
        $(".syntabelem").fadeOut();
        $(".syntabelem."+$(this).find("a").attr("href").replace("#", "")).fadeIn();
    });
    $(".syntab .default").click();
});

function dialogConfirm(url, titre, yes, no, id) {
    if (confirm(titre)) {
        window.location = url;
    }
}

function initScroll(){
    scrollY = $(window).scrollTop();
    $(".reglabe, .reglabe2").each(function(){
        scrollY += $(this).scrollTop();
        $(this).height('auto');
        if($(this).attr("old-width"))
            $(this).width($(this).attr("old-width"));            
        else
            $(this).width('auto');
        if($(this).attr("old-padding-right"))
            $(this).css("padding-right", $(this).attr("old-padding-right")+"px");
        $(this).removeClass("reglabe");
        $(this).removeClass("reglabe2");
    });
    $(window).scrollTop(scrollY);
    return scrollY;
}

function traiteScroll(heightDif) {
    scrollY = initScroll();
    //    alert("ee");
    hauteurMenu = parseInt($("div.vmenu").innerHeight()) + parseInt($("#tmenu_tooltip").innerHeight()) + 30;
    height = parseInt(window.innerHeight);
    grandeTaille = parseInt($("body").innerHeight());
    minimuAGagne = grandeTaille - height;
    appli = false;
    newTaille = 0;
    elem = null;
    if(hauteurMenu < height && (0 || minimuAGagne > 0)){
        $("div").each(function(){
            taille = $(this).height();
            newTailleT = taille - minimuAGagne - 5;
            reductionVisibilite = height/newTailleT;
            nbPages = taille / newTailleT;
            if($(this).is(":visible") & newTailleT > 300 & (nbPages * reductionVisibilite * reductionVisibilite) < 30){
                newTaille = newTailleT;
                elem = $(this);
                appli = true;
            }
        });
        
        if(appli){
            if(!$(elem).attr("old-width"))
                $(elem).attr("old-width", $(elem).css("width"));
            oldPadding = parseInt($(elem).css("padding-right").replace("px", ""));
            if(!$(elem).attr("old-padding-right"))
                $(elem).attr("old-padding-right", oldPadding);
            else
                oldPadding = parseInt($(elem).attr("old-padding-right"));
            
            $(elem).addClass("reglabe2");
            $(elem).height(newTaille);
            if($(elem).is(".fiche"))
                $(elem).css("margin-right", "0");
            $(elem).width($(elem).width()-17);
            $(elem).css("padding-right", (oldPadding+15)+"px");
            
            //Test
//            if(parseInt($("body").innerHeight()) > height)
//                initScroll();
        }
    }
}


//function traiteScroll2(heightDif) {
//    coef = 1;
//    scrollY = $(window).scrollTop() * coef;
//    //    if ($(".fiche").scrollTop() > scrollY)
//    //        scrollY = $(".fiche").scrollTop();
//    //    if ($(".tabBar").scrollTop() > scrollY)
//    //        scrollY = $(".tabBar").scrollTop();
//    $(".reglabe, .reglabe2").each(function(){
//        scrollY += $(this).scrollTop();
//    });
//    
//    $(".reglabe, .reglabe2").height('auto');
//    $(".reglabe").removeClass("reglabe");
//    $(".reglabe2").removeClass("reglabe2");
//    $(".tabBar").height('auto');
//    $(window).scrollTop(scrollY);
//    //    alert("ee");
//    height = parseInt(window.innerHeight);
//    var i = 0;
//    var j = 0;
//    var h = 0;
//    $(".fiche").parent().parent().children("td").each(function() {
//        i = i + 1;//Nb d'element dans parent dans div principale'
//    });
//    $(".fiche").parent().children("div").each(function() {
//        h = h + 1; //nb element div ds fiche
//    });
//    //    if(height > 560 && i < 3)
//    hauteurMenu = parseInt($("div.vmenu").innerHeight()) + parseInt($("#tmenu_tooltip").innerHeight()) + 30;
//    if (height > hauteurMenu && i < 3 && h == 1) {//On active le scroll 2
//        heightDif = $(".fiche").innerHeight() - $(".tabBar").height();
//        $(".fiche").addClass("reglabe");
//        heightNew = ($(".fiche").innerHeight() - heightDif) - 20;
//        if (heightNew > 400) {//On active le scroll 3 (le scroll 2 ne doit plus étre utile
//            tailleGr = $(".tabBar").innerHeight();
//            $(".tabBar").find(".ui-tabs").children(".ui-tabs-panel").each(function(){
//                elem2 = $(this);
//                //            heightNew2 = heightNew - (findPos(elem2).y - findPos($(".tabBar")).y)-20;
//                heightDif2 = tailleGr - elem2.innerHeight();
//                heightNew2 = heightNew - heightDif2 -10;
//                //                alert(elem2.innerHeight());
//                if(heightNew2 > 300 ){
//                    elem2.height(heightNew2);
//                    elem2.addClass("reglabe2");
//                    elem2.scrollTop(scrollY);
//                }
//                elem2.find("div").resize(function() {
//                    alert("44");
//                });
//            });
//            $(".tabBar").height(heightNew);
//            $(".tabBar").addClass("reglabe2");
//            $(".tabBar").scrollTop(scrollY);
//        }
//        else {//On désactive le scroll 3 (le scroll 2 doit prendre le relai)
//            $(".reglabe2").removeClass("reglabe2");
//            $(".tabBar").height('auto');
//            $(".fiche").scrollTop(scrollY);
//        }
//    //        alert(heightAv+" "+heightAp);
//    }
//    else {//On desactive les scroll2 et 3
//    }
//    $("#id-right").width("98%");
//    $("div#id-right").width("99%");
////    document.body.scrollTop = scrollY;
//}

function findPos(el) {
    var _x = 0;
    var _y = 0;
    el = $(el);
    while( el && typeof(el.offset()) == "object" && !isNaN( el.offset().left ) && !isNaN( el.offset().top ) ) {
        _x += el.position().left + el.parent().scrollLeft();
        _y += el.position().top + el.parent().scrollTop();
        el = el.parent();
    }
    return {
        y: _y, 
        x: _x
    };
}


function ajNoteAjax() {
    fermable = true;
    var datas = 'url=' + window.location;
    datas = datas + '&type=note';
    jQuery.ajax({
        url: DOL_URL_ROOT + '/Synopsis_Tools/ajax/note_ajax.php',
        data: datas,
        datatype: "xml",
        type: "POST",
        cache: false,
        success: function(msg) {
            if (msg != "0") {
                //                var htmlDiv  = '<div class="noteAjax"><div class="control"><input class="controlBut" type="button" value="<"/></div><div class="note">Note (publique) :<br><div class="editable" id="notePublicEdit" title="Editer">'+msg+'</div></div></div>';
                //                $('.tabBar').append(htmlDiv);
                classEdit = "";
                if (msg.indexOf("[1]") > -1) {
                    classEdit = "editable";
                    msg = msg.replace("[1]", "");
                }
                var htmlDiv = '<div class="noteAjax"><div class="note">Note (publique) :<br><div class="' + classEdit + '" id="notePublicEdit" title="Editer">' + msg + '</div></div></div>';
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
                
                editAjax(jQuery('#notePublicEdit'), datas, function() {
                    hideNote()
                });
            
            }
        }
    });
    
    
    
    function shownHideNote() {
        $(".noteAjax .note").animate({
            width: 'toggle'
        });
    }
    function shownNote() {
        $(".noteAjax .note").slideDown({
            width: 'toggle'
        });
        fermer = false;
    }
    function hideNote() {
        if (fermable) {
            fermer = true;
            setTimeout(function() {
                if (fermer)
                    $(".noteAjax .note").slideUp({
                        width: 'toggle'
                    });
            }, 500);
        }
    }
}


function editAjax(elem, datas, callOut) {
    elem.click(function() {
        if (fermable) {
            fermable = false;
            var text = jQuery(elem).html().split('<br>').join('\n');
            if (text == "Cliquez pour éditer")
                text = '';
            $(elem).html('<textarea class="editableTextarea" style="width:99%;height:99%; background:none;">' + text + "</textarea>");
            $(elem).removeClass('editable');
            $(elem).find(".editableTextarea").focus();
            $(elem).find(".editableTextarea").val($(".editableTextarea").val() + "\n");
            $(elem).find(".editableTextarea").focusout(function() {
                fermable = true;
                if (callOut)
                    callOut();
                datas = datas + '&note=' + $(".editableTextarea").val();
                jQuery.ajax({
                    url: DOL_URL_ROOT + '/Synopsis_Tools/ajax/note_ajax.php',
                    data: datas,
                    datatype: "xml",
                    type: "POST",
                    cache: false,
                    success: function(msg) {
                        if (msg.indexOf("[1]") > -1) {
                            msg = msg.replace("[1]", "");
                        }
                        $(elem).html(msg);
                        $(elem).addClass("editable");
                    }
                });
            });
        }
    });
}


function popChrono(id, callBack) {
    //    window.open(DOL_URL_ROOT+"/Synopsis_Chrono/fiche-nomenu.php?action=Modify&id="+id,'nom_de_ma_popup','menubar=no, scrollbars=yes, top=100, left=100, width=600, height=600');
    $("body").append("<div class='fullScreen'><span class='fermer' onclick=''>X</span><span class='petit' onclick=''>_</span><iframe src='" + DOL_URL_ROOT + "/Synopsis_Chrono/fiche-nomenu.php?action=Modify&id=" + id + "'></iframe></div>");
    
    $("#id-container").hide();
    
    $(".fullScreen span.fermer").click(function() {
        $("#id-container").show();
        cacherSuppr($(this).parent());
        callBack();
    });
    var i = 0;
    $(".fullScreen iframe").on("load", function(){
        i++;
        if(i > 1){
            $("#id-container").show();
            cacherSuppr($(this).parent());
            callBack();
        }
    });
    $(".fullScreen span.petit").click(function() {
        if($("iframe.fullScreen").size() == 0)
            $("body").append("<iframe src='" + document.location.href + "' class='fullScreen'></iframe>");
        else
            $("iframe.fullScreen").fadeIn();
        $("body").append("<div class='bottomObj'><span>Chrono</span></div>");
        //        $(this).parent().fadeOut();
        $(".bottomObj").click(function() {
            //            $("div.fullScreen").fadeIn();
            $("iframe.fullScreen").fadeOut();
            cacherSuppr($(this));
        });
    });

}
function addLienHtml(idIncr, id, nom, model, cible) {
    $(cible).prepend("<div class='elem'>" + model.replace("replaceId", idIncr).replace("replaceValue", id).replace("replaceValue", id).replace("replaceNom", nom) + "</div>");
}
function ajaxAddChrono(model_refid, socid, tabChamp, callBack) {
    champSup = '';
    valSup = '';
    for (var i in tabChamp) {
        if (tabChamp[i]) {
            champSup = champSup + "-" + (i);
            valSup = valSup + "-" + tabChamp[i];
        }
    }
    champSup = "&champSup=" + champSup + "&champSupVal=" + valSup;
    jQuery.ajax({
        url: DOL_URL_ROOT + "/Synopsis_Chrono/ajax/addChrono.php",
        type: "POST",
        datatype: "xml",
        data: "model=" + model_refid + "&socid=" + socid + champSup,
        success: function(msg) {
            callBack(msg);
        }
    });
}

function ajaxManipElementElement(action, sourcetype, targettype, idsource, idtarget, ordre, callBack) {
    jQuery.ajax({
        url: DOL_URL_ROOT + "/Synopsis_Tools/ajax/manipElementElement.php",
        type: "POST",
        datatype: "xml",
        data: "action=" + action + "&sourcetype=" + sourcetype + "&targettype=" + targettype + "&idsource=" + idsource + "&idtarget=" + idtarget + "&ordre=" + ordre,
        success: function(msg) {
            callBack(msg);
        }
    });
}
function addChrono(element, socid, callBack) {
    parent = $(element).parent();
    model = $(element).parent().find(".model").html();
    model_refid = $(element).attr("id").replace("addChrono", "");
    ajaxAddChrono(model_refid, socid, new Array(), callBack);
}

function supprLigne(element) {
    firstParent = $(element).parent();
    parent = $(firstParent).parent();
    callBack = function(ok) {
        if (ok == "ok")
            $(firstParent).fadeOut();
    };
    if ($(parent).hasClass("formAjax"))
        ajaxManipElementElement("rm", $(parent).find(".sourcetype").val(), $(parent).find(".targettype").val(), $(firstParent).find("input").val(), $(parent).find(".targetid").val(), $(parent).find(".ordre").val(), callBack);
    else
        cacherSuppr(firstParent);
    return false;
}


function cacherSuppr(element) {
    $(element).fadeOut(function() {
        $(element).remove();
    });
}

function addLienAj(firstParent){
    parent = $(firstParent).parent();
    model = $(parent).find(".model").html();
    select = $(parent).find("select");
    selectId = $(select).val();
    idIncr++;
    selectNom = $(select).find("option:selected").text();
    ajaxManipElementElement("add", $(parent).find(".sourcetype").val(), $(parent).find(".targettype").val(), selectId, $(parent).find(".targetid").val(), $(parent).find(".ordre").val(), function(ok) {
        if (ok == "ok")
            addLienHtml(idIncr, selectId, selectNom, model, parent);
    });    
}

function addChronoAj(firstParent){
    parent = $(firstParent).parent();
    model = $(parent).find(".model").html();
    addChrono(firstParent, $("#socid").val(), function(valReturn) {
        ajaxManipElementElement("add", $(parent).find(".sourcetype").val(), $(parent).find(".targettype").val(), valReturn, $(parent).find(".targetid").val(), $(parent).find(".ordre").val(), function(ok) {
            if (ok == "ok") {
                idIncr = idIncr + 1;
                addLienHtml(idIncr, valReturn, "Nouvellement crée", model, parent);
                popChrono(valReturn, function() {
                    });
            }
        });
    });
}

function initFormChrono(){
    $(".showFormChrono").each(function(){
        $(this).parent().find(".chronoForm").hide();
        $(this).click(function(){
            $(this).parent().find(".chronoForm").show();
            $(this).remove();
        });
    });
    
    
    
    
    idIncr = 100;
    $(".formAjax .addLien").click(function() {
        addLienAj($(this));
        return false;
    });
    $(".formAjax .changeLien").click(function() {
        $(this).parent().find("div.elem").each(function(){
            if(!$(this).hasClass("model"))
                $(this).remove();
        });
        addLienAj($(this));
        return false;
    });
    $(".formAjax .addChrono").click(function() {
        addChronoAj($(this));
        return false;
    });
    $(".formAjax .changeChrono").click(function() {
        $(this).parent().find("div.elem").each(function(){
            if(!$(this).hasClass("model"))
                $(this).remove();
        });
        addChronoAj($(this));
        return false;
    });
    $("#chronoTable .addChrono").click(function() {
        parent = $(this).parent();
        model = $(parent).find(".model").html();
        socid = $("#socid").parent().find("select").val();
        addChrono(this, socid, function(valReturn) {
            idIncr = idIncr + 1;
            addLienHtml(idIncr, valReturn, "Nouvellement crée", model, parent);
            popChrono(valReturn, function() {});
        });
        return false;
    });
    $("#chronoTable .changeChrono").click(function() {
        $(this).parent().find("div.elem").each(function(){
            if(!$(this).hasClass("model"))
                $(this).remove();
        });
        parent = $(this).parent();
        model = $(parent).find(".model").html();
        socid = $("#socid").parent().find("select").val();
        addChrono(this, socid, function(valReturn) {
            idIncr = idIncr + 1;
            addLienHtml(idIncr, valReturn, "Nouvellement crée", model, parent);
            popChrono(valReturn, function() {});
        });
        return false;
    });
    
    $("#chronoTable .addLien").click(function(){
        model = $(this).parent().find(".model").html();
        select = $(this).parent().find("select");
        selectId = $(select).val();
        idIncr++;
        selectNom = $(select).find("option:selected").text();
        addLienHtml(idIncr, selectId, selectNom, model, $(this).parent());
        return false;
    });
    $("#chronoTable .changeLien").click(function(){
        $(this).parent().find("div.elem").each(function(){
            if(!$(this).hasClass("model"))
                $(this).remove();
        });
        model = $(this).parent().find(".model").html();
        select = $(this).parent().find("select");
        selectId = $(select).val();
        idIncr++;
        selectNom = $(select).find("option:selected").text();
        addLienHtml(idIncr, selectId, selectNom, model, $(this).parent());
        return false;
    });
}