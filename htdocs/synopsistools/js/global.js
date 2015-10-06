$(window).load(function () {
    heightDif = $(".fiche").innerHeight() - $(".tabBar").height(); //hauteur du rest (ne change pas
    if ($("div.tmenudiv").is(':visible')) {
        $(window).resize(function () {
            traiteScroll(heightDif);
        });
        $("a").click(function () {
            setTimeout(function () {
                traiteScroll(heightDif);
            }, 100);
        });
        traiteScroll(heightDif);
    }

    $("#mainmenua_SynopsisTools.tmenudisabled").parent().parent().hide();


    ajNoteAjax();

//    setTimeout(function() {
////        initSynchServ(idActionMax);
//    }, 5000);


    if (window.location.pathname.indexOf("societe/soc.php") > -1 || window.location.pathname.indexOf("contact/card.php") > -1) {
        $("body").keypress(function (e) {
            if (e.which == 13)
                return false
        });
    }





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

    $(".ui-search-toolbar input").focusout(function () {
        setTimeout(function () {
            var e = jQuery.Event("keypress", {
                keyCode: 13
            });
            $(".ui-search-toolbar input").trigger(e);
        }, 1000);
    });


    //    if ((navigator.appName).search(/Safari.+/) != -1){
    $(".formdoc a").each(function () {
        if ($(this).attr('href').search(/.pdf/i) >= 0)
            $(this).attr("target", "");
    });
    //    }
//    initFormChrono();



    //    $(".nonactif").hide();

    $(".syntab li").click(function () {
        $(".syntab li").removeClass("actif");
        $(this).addClass("actif");
        $(".syntabelem").fadeOut();
        $(".syntabelem." + $(this).find("a").attr("href").replace("#", "")).fadeIn();
    });
    $(".syntab .default").click();

    $(".editDate").click(function () {
        $(".editDateDiv").fadeIn();
        $(this).fadeOut();
    });



    //Cacher prix quand ligne de text
    $("#select_type").change(function () {
        elems = $(this).parent().parent().parent().find("#tva_tx, input[name|='price_ht'], input[name|='qty'],  input[name|='remise_percent']");
        if ($(this).val() >= 100) {
            $("input[name|='price_ht']").val("0");
            elems.hide();
        }
        else
            elems.show();
    });





    //Cacher tva.. quand privé
    $("#radioprivate, #radiocompany").click(function () {
        cible = $("input[name='idprof1']");
        tabCible = Array();
        tabCible.push(cible.parent().parent());
        tabCible.push(cible.parent().parent().next("tr"));
        tabCible.push(cible.parent().parent().next("tr").next("tr"));
        tabCible.push(cible.parent().parent().next("tr").next("tr").next("tr"));
        tabCible.push(cible.parent().parent().next("tr").next("tr").next("tr").next("tr"));
        tabCible.push(cible.parent().parent().next("tr").next("tr").next("tr").next("tr").next("tr"));

        for (var i in tabCible)
            if ($(this).attr('id') == "radioprivate")
                tabCible[i].fadeOut();
            else
                tabCible[i].fadeIn();
    });

    selector = "input[name='viewday'], input[name='viewweek'], input[name='viewcal']";
    $(selector).prev().hide();
    $(selector).next().hide();
    $(selector).hide();

    $(".butCache").click(function () {
        $(".panCache#" + $(this).attr("id").replace("but", "pan")).show();
        $(this).hide();
    });


    $(".datePicker").datepicker();




    /*rsponsive victor*/
    
    /*responsive victor*/




    $(".disableadOnClick").click(function(){
        $(this).attr("disabled", "disabled");
    });
});

function ajoutNotification(id, titre, msg) {
    $("div.notificationText").append("<div class='oneNotification'><input type='hidden' class='idNotification' value='" + id + "'/><div class='titreNotification'><table style='width:100%'><tr><td>" + titre + "</td><td style='text-align:right;'><span class='marqueVueNotification editable'>X</span></td></tr></table></div>" + msg + "</div>");
    $("span.marqueVueNotification").click(function () {
        notifHtml = $(this).parent().parent().parent().parent().parent().parent();
        datas = 'idVue=' + notifHtml.find(".idNotification").val();
        jQuery.ajax({
            url: DOL_URL_ROOT + '/synopsistools/ajax/synch.php',
            data: datas,
            datatype: "xml",
            type: "POST",
            cache: false,
            success: function (msg) {
                if (msg.indexOf('<ok>ok</ok>') !== -1)
                    notifHtml.fadeOut();
            }
        });
    });
}

function CallBackPlusObj() {
    this.tabCallBack = Array();
    this.popOjectAffiche = popOjectAffiche;
    this.popIFrame = popIFrame;
}

function initSynchServ(idAction) {
    CallBackPlus = new CallBackPlusObj();
    iframePrinc.CallBackPlus = CallBackPlus;
    url = window.location.hash.replace("#", "");
    if (url != '')
        iframePrinc.location = window.location.hash.replace("#", "");
    iframePrinc.initLienConnect();
    initTransmission("iframePrinc");
//    $(".fullScreen").load(function() {
//        iframePrinc.CallBackPlus = CallBackPlus;
//    });
//    $(window).on('hashchange', function() {
////        iframePrinc.location.replace(window.location.hash.replace("#", ""));
//    });

    timeTentative = 1;
    boucleSynchServ(idAction);
}
function initSynchClient(optioncss) {
    $(document).bind("ajaxComplete", function () {
        initLienConnect(optioncss);
    });
    initLienConnect(optioncss);
}

function boucleSynchServ(idAction) {
    datas = 'idPagePrinc=' + idPagePrinc + '&idMax=' + idAction;
    jQuery.ajax({
        url: DOL_URL_ROOT + '/synopsistools/ajax/synch.php',
        data: datas,
        datatype: "xml",
        type: "POST",
        cache: false,
        success: function (msg) {
            modif = false;
            idActionT = $(msg).find("idAction").html();
            if (idActionT > 0) {
                idAction = idActionT;
                ajoutNotification($(msg).find("idAction").html(), $(msg).find("titreAction").html(), $(msg).find("msgAction").html());
                modif = true;
            }


//            idElemNotifT = $(msg).find("idElemNotif").html();
//            if (idElemNotifT > 0) {
//                idElemNotif = idElemNotifT;
//                popOjectAffiche(idElemNotif, $(msg).find("typeElemNotif").html(), function() {
//                    CallBackPlus.tabCallBack[idElemNotif]();
//                }, $(msg).find("titreNotif").html());
//                modif = true;
//            }

            echoT = $(msg).find("echo").html();
            if (echoT != undefined) {
                alert(echoT);
                modif = true;
            }

            if (modif)
                boucleSynchServ(idAction);
            else {
                setTimeout(function () {
                    boucleSynchServ(idAction);
                }, 10 * 1000);
            }
        },
        error: function () {
            setTimeout(function () {
                boucleSynchServ(idAction);
                timeTentative = timeTentative * 1.3;
            }, timeTentative * 1000);
        }
    });
}

function dialogConfirm(url, titre, yes, no, id) {
    if (confirm(titre)) {
        window.location = url;
    }
}

function initScroll() {
    scrollY = $(window).scrollTop();
    $(".reglabe, .reglabe2").each(function () {
        scrollY += $(this).scrollTop();
        $(this).height('auto');
//        if ($(this).attr("old-width"))
//            $(this).width($(this).attr("old-width"));
//        else
        $(this).width('auto');
        if ($(this).attr("old-padding-right"))
            $(this).css("padding-right", $(this).attr("old-padding-right") + "px");
        $(this).removeClass("reglabe");
        $(this).removeClass("reglabe2");
    });
    $(window).scrollTop(scrollY);
    return scrollY;
}

function largeur_fenetre()
{
    if (window.innerWidth)
        return window.innerWidth;
    else if (document.body && document.body.offsetWidth)
        return document.body.offsetWidth;
    else
        return 0;
}

function hauteur_fenetre()
{
    if (window.innerHeight)
        return window.innerHeight;
    else if (document.body && document.body.offsetHeight)
        return document.body.offsetHeight;
    else
        return 0;
}

function traiteScroll(heightDif) {
    scrollY = initScroll();
//        alert(scrollY);
    hauteurMenu = parseInt($("div.vmenu").innerHeight()) + parseInt($("#tmenu_tooltip").innerHeight()) + 30;
    height = parseInt(window.innerHeight);
//    width = parseInt(window.innerWidth);
    width = largeur_fenetre();
//    grandeTaille = parseInt($("body").innerHeight());
    grandeTaille = parseInt($("body").innerHeight());
    minimuAGagne = grandeTaille - height;
    appli = false;
    newTaille = 0;
    elem = null;
    if (hauteurMenu < height && (0 || minimuAGagne > 0)) {
        $("#id-right div").each(function () {
//                if($(this).attr("class") == "fiche")
//                alert($(this).attr("class")+" | ");
            if (!$(this).is(".fichehalfright, .fichehalfleft") && !$(this).is(".fichehalfleft")) {
                taille = $(this).innerHeight();
                newTailleT = taille - minimuAGagne - 10;
                reductionVisibilite = height / newTailleT;
                nbPages = taille / newTailleT;
//                if($(this).attr("class") == "fiche"){
//                alert($(this).attr("class")+" | "+newTailleT+" | "+grandeTaille);
//            alert(hauteur_fenetre());
//                }
                if ($(this).is(":visible")
                        && newTailleT > 300 & (nbPages * reductionVisibilite * reductionVisibilite) < 300) {
                    newTaille = newTailleT;
                    elem = $(this);
                    appli = true;
                }
            }
        });

        if (appli) {
//            alert("appli");
            if (!$(elem).attr("old-width"))
                $(elem).attr("old-width", $(elem).css("width"));
            oldPadding = parseInt($(elem).css("padding-right").replace("px", ""));
            if (!$(elem).attr("old-padding-right"))
                $(elem).attr("old-padding-right", oldPadding);
            else
                oldPadding = parseInt($(elem).attr("old-padding-right"));

            $(elem).addClass("reglabe2");
            if ($(elem).is(".fiche"))
                $(elem).css("margin-right", "0");
//            margin = parseInt($(elem).css("margin-top").replace("px", ""))+parseInt($(elem).css("margin-bottom").replace("px", ""));
            padding = parseInt($(elem).css("padding-top").replace("px", "")) + parseInt($(elem).css("padding-bottom").replace("px", ""));
//            alert(margin+padding);
            $(elem).height(newTaille - padding);
//            $(elem).width($(elem).width() - 20);
            $(elem).css("padding-right", (oldPadding + 15) + "px");
            if (scrollY > 250)
                scrollY = scrollY - 250;


            largeurMenu = 180;
            widthBody1 = $("#id-right").innerWidth() + largeurMenu;
            widthBody2 = parseInt($("body").innerWidth());
            if (widthBody1 > widthBody2)
                widthBody = widthBody1;
            else
                widthBody = widthBody2;
//            
//            alert(parseInt($("body").innerWidth()));
//            alert(widthBody);
//            alert("Fenetre : "+width);

            //Test
            if (parseInt($("body").innerHeight()) > (height - 5) || widthBody > width) {
                scrollY = initScroll();
//                alert("reinit");
            }

//            if(!window.chrome)
            $(elem).scrollTop(scrollY);
//        else
//            alert("chrome");
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

//function findPos(el) {
//    var _x = 0;
//    var _y = 0;
//    el = $(el);
//    while (el && typeof (el.offset()) == "object" && !isNaN(el.offset().left) && !isNaN(el.offset().top)) {
//        _x += el.position().left + el.parent().scrollLeft();
//        _y += el.position().top + el.parent().scrollTop();
//        el = el.parent();
//    }
//    return {
//        y: _y,
//        x: _x
//    };
//}


function ajNoteAjax() {
    fermable = true;
    var datas = 'url=' + window.location;
    datas = datas + '&type=note';
    jQuery.ajax({
        url: DOL_URL_ROOT + '/synopsistools/ajax/note_ajax.php',
        data: datas,
        datatype: "xml",
        type: "POST",
        cache: false,
        success: function (msg) {
            if (msg != "0") {
                if ($("a#note").size() > 0)
                    tab = "note";
                else
                    tab = "info";
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
                $('a#' + tab + ', .noteAjax').hover(shownNote, hideNote);
                $('a#' + tab + '').addClass("lienNote");

                //                $(".controlBut").click(function(){
                //                    if($(this).val() == "<"){
                //                        $(this).val(">");
                //                    }
                //                    else{
                //                        $(this).val("<");
                //                    }     
                //                    shownHideNote();   
                //                })

                editAjax(jQuery('#notePublicEdit'), datas, function () {
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
            setTimeout(function () {
                if (fermer)
                    $(".noteAjax .note").slideUp({
                        width: 'toggle'
                    });
            }, 500);
        }
    }
}


function editAjax(elem, datas, callOut) {
    elem.click(function () {
        if (fermable) {
            fermable = false;
            var text = jQuery(elem).html().split('<br>').join('\n');
            if (text == "Cliquez pour éditer")
                text = '';
            $(elem).html('<textarea class="editableTextarea" style="width:99%;height:99%; background:none;">' + text + "</textarea>");
            $(elem).removeClass('editable');
            $(elem).find(".editableTextarea").focus();
            $(elem).find(".editableTextarea").val($(".editableTextarea").val() + "\n");
            $(elem).find(".editableTextarea").focusout(function () {
                fermable = true;
                if (callOut)
                    callOut();
                datas = datas + '&note=' + $(".editableTextarea").val();
                jQuery.ajax({
                    url: DOL_URL_ROOT + '/synopsistools/ajax/note_ajax.php',
                    data: datas,
                    datatype: "xml",
                    type: "POST",
                    cache: false,
                    success: function (msg) {
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

//var tabCallBack = Array();
//function appelPop(id, type, callBack) {
//    CallBackPlus.tabCallBack[id] = callBack;
//    datas = 'idPagePrinc=' + idPagePrinc + '&ouvreType=' + type + '&ouvreId=' + id;
//    callBack2 = callBack;
//    jQuery.ajax({
//        url: DOL_URL_ROOT + '/synopsistools/ajax/synch.php',
//        data: datas,
//        datatype: "xml",
//        type: "POST",
//        cache: false,
//        success: function(msg) {
////            callBack(msg);
//        }
//    });
//}


function dispatchePopObject(id, type, callBack, titre, nbLoad) {//Affiche ici ou dans page princ si existe
    if (typeof (CallBackPlus) != 'undefined')
        CallBackPlus.popOjectAffiche(id, type, callBack, titre, nbLoad);
//        appelPop(id, 'chrono', callBack);
    else
        popOjectAffiche(id, type, callBack, titre, nbLoad);

}
function popOjectAffiche(id, type, callBack, titreNotif, nbLoad) {//Affiche ici
    if (type == 'chrono')
        urlT = DOL_URL_ROOT + "/synopsischrono/fiche-nomenu.php?action=Modify&id=";
    else if (type == 'commande')
        urlT = DOL_URL_ROOT + "/Synopsis_PrepaCommande/prepacommande.php?optioncss=print&id=";
    else if (type == 'newContact')
        urlT = DOL_URL_ROOT + "/contact/card.php?action=create&optioncss=print&socid=";
    else if (type == 'newSoc')
        urlT = DOL_URL_ROOT + "/societe/soc.php?leftmenu=customers&action=create&type=c&optioncss=print&cc=";
    else if (type == 'sms')
        urlT = DOL_URL_ROOT + "/smsdecanet/send.php?optioncss=print&id=";
    popIFrame(urlT + id, callBack, titreNotif, nbLoad);
}
function ajoutPictoConnect() {
    $("a").each(function () {
        ok = false;
        if ($(this).attr('onclick') == undefined && $(this).attr('href') != undefined) {
            $(this).attr("target", "iframePrinc");
            if ($(this).find("img").size() > 0) {
                parent = $(this).parent();
                if (parent.find("a.popConnect").size() == 0) {
                    if (parent.find("a").size() == 2 && parent.find("a").last().find("img").size() == 0) {
                        ref = parent.find("a").last().html();
                        ok = true;
                    }


                    if (parent.find("a").size() == 1) {
                        tabT = $(this).html().split(">");
                        if (tabT.length == 2 && tabT[1] != '') {
                            ref = tabT[1];
                            ok = true;
                        }
                    }
                }
            }
            if (ok == true) {
                lien = parent.find("a").last().attr("href");
                if (lien.indexOf("card.php") > 0 ||
                        lien.indexOf("listDetail.php") > 0 ||
                        lien.indexOf("prepacommande.php") > 0)
                    parent.prepend('<a class="popConnect pasTraiter" id="connectUrl-' + $(this).attr("href") + '--' + ref + '"><img src="' + DOL_URL_ROOT + '/synopsistools/img/connect.png" border="0" alt="Ouvrir en mode connect" title="Ouvrir en mode connect&amp;mainmenu=commercial&amp;leftmenu=orders"></a>');
            }
        }
    });
}
function initLienConnect(optioncss) {
    if (optioncss == "print") {
        $("form").each(function () {
            if ($(this).attr("action").indexOf("?") > 0)
                $(this).attr("action", $(this).attr("action") + "&optioncss=print");
            else
                $(this).attr("action", $(this).attr("action") + "?optioncss=print");
        });
    }


    ajoutPictoConnect();
    $(".pasTraiter.popConnect").each(function () {
        $(this).click(function () {
            tabT = $(this).attr("id").split("-");
            nom = $(this).attr("id").replace(tabT[0] + "-" + tabT[1] + "-" + tabT[2] + "-", "");
            if (tabT[0] == 'connect')
                dispatchePopObject(tabT[2], tabT[1], function () {
                }, nom, 100);
            else if (tabT[0] == 'connectUrl') {
                if (tabT[1].indexOf("?") > 0)
                    tabT[1] = tabT[1] + "&optioncss=print"
                else
                    tabT[1] = tabT[1] + "?optioncss=print"
                dispatchePopIFrame(tabT[1], function () {
                }, nom, 100);
            }

            return false;
        });
        $(this).removeClass("pasTraiter");
    });
}

function initTransmission(elem) {
    if (typeof (CallBackPlus) != 'undefined') {
        $("iframe[name='" + elem + "']").load(function () {
            eval(elem + '.CallBackPlus = CallBackPlus;');
//            eval(elem + '.initLienConnect();');
            if (elem != "iframePrinc")
                eval(elem + '.initSynchClient("print");');
            else
                eval(elem + '.initSynchClient("");');
        });
//            eval(elem + '.initLienConnect();');


        $("iframe.fullScreen").load(function () {
            iFramePrinc(false);
            eval('url = document.location.href;' +
                    'newUrl = url.replace(window.location.hash, "") + "#" +' + elem + '.location;' +
                    'history.replaceState("{}", "",newUrl);');
        });
    }
}

function dispatchePopIFrame(urlIF, callBack, titreNotif, nbLoad) {
    if (typeof (CallBackPlus) != 'undefined')
        CallBackPlus.popIFrame(urlIF, callBack, titreNotif, nbLoad);
    else
        popIFrame(urlIF, callBack, titreNotif, nbLoad);
}

var nbIframe = 0;
function popIFrame(urlIF, callBack, titreNotif, nbLoad) {
    nbIframe++;
    $("iframe.fullScreen").fadeOut();
    //    window.open(DOL_URL_ROOT+"/synopsischrono/fiche-nomenu.php?action=Modify&id="+id,'nom_de_ma_popup','menubar=no, scrollbars=yes, top=100, left=100, width=600, height=600');
    $("body").append("<div class='fullScreen' id='iFrame" + nbIframe + "'><span class='fermer' onclick=''>X</span><span class='petit' onclick=''>_</span><iframe src='" + urlIF + "' name='iFrame" + nbIframe + "'></iframe></div>");
//    $("#iFrame" + nbIframe+" iframe").load(function() {
//        eval("iFrame" + nbIframe + ".CallBackPlus = CallBackPlus;");
//    });

    initTransmission("iFrame" + nbIframe);
    $(".bottomObj").removeClass("actif");
    $("div.notificationObj").append("<div class='bottomObj actif' id='lienIFrame" + nbIframe + "'><span>" + titreNotif + "</span></div>");
//    $("#id-container").hide();

    iFrame = $("#iFrame" + nbIframe + "");

    iFrame.find("span.fermer").click(function () {
        fermerIframe($(this).parent(), callBack);
    });
    var i = 0;
    iFrame.find("iframe").load(function () {
        if ($(this).contents().find("#username").size() > 0)
            $nbLoad++;



        $(this).contents().find("a.butActionDelete, input[name='cancel'], input[name='edit'], input[value='Ajouter'], input[value='Ajouter'], div.ui-dialog-buttonset span.ui-button-text").click(function () {
//            fermerIframe($(this).parent(), callBack);
            nbLoad = 1;
        });

        $(this).contents().find("input[name='updateassignedtouser'], input[name='addassignedtouser'], input[name='addGroupMember']").click(function () {
            nbLoad = 100;
        });
        i++;
        urlStr = iFrame.find("iframe").get(0).contentWindow.location.toString();
        if (i > nbLoad && urlStr.indexOf("delete", 0) === -1 && urlStr.indexOf("assignedtouser", 0) === -1) {
//        $("#id-container").show();
            fermerIframe($(this).parent(), callBack);
        }
    });
    iFrame.find("span.petit").click(function () {
//        id = $(this).parent().attr("id").replace("iFrame", "");
        iFramePrinc(true);
    });
    $(".bottomObj").click(function () {
        return false;
    });
    $("#lienIFrame" + nbIframe).click(function () {
        $(".bottomObj").removeClass("actif");
        $(this).addClass("actif");
        id = $(this).attr("id").replace("lienIFrame", "");
        $("div.fullScreen").fadeOut();
        $("div.fullScreen#iFrame" + id).fadeIn();
        $("iframe.fullScreen").fadeOut();
//            cacherSuppr($(this));
    });

}
function fermerIframe(elem, callBack) {
    id = $(elem).attr("id").replace("iFrame", "");
    cacherSuppr($("#lienIFrame" + id));
    cacherSuppr($(elem));
    callBack();
    iFramePrinc(false);
}
function iFramePrinc(createIfNotExist) {
    $(".bottomObj").removeClass("actif");
    if ($("iframe.fullScreen").size() == 0 && createIfNotExist) {
        $("body").append("<iframe class='fullScreen'  name='iframePrinc'></iframe>");
        if (document.location.href.indexOf("?") > 0)
            src = document.location.href + "&inut";
        else
            src = document.location.href + "?inut";
        $("iframe.fullScreen").attr("src", src);
        $("div.fullScreen").fadeOut();
    }
    else
        $("iframe.fullScreen").fadeIn();
}


function addLienHtml(idIncr, id, nom, model, cible) {
    if (id > 0)
        $(cible).prepend("<div class='elem'>" + model.replace("replaceId", idIncr).replace("replaceValue", id).replace("replaceValue", id).replace("replaceValue", id).replace("replaceValue", id).replace("replaceValue", id).replace("replaceNom", nom).replace("replaceNom", nom).replace("replaceNom", nom) + "</div>");
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
        url: DOL_URL_ROOT + "/synopsischrono/ajax/addChrono.php",
        type: "POST",
        datatype: "xml",
        data: "model=" + model_refid + "&socid=" + socid + champSup,
        success: function (msg) {
            callBack(msg);
        }
    });
}

function ajaxManipElementElement(action, sourcetype, targettype, idsource, idtarget, ordre, callBack) {
    jQuery.ajax({
        url: DOL_URL_ROOT + "/synopsistools/ajax/manipElementElement.php",
        type: "POST",
        datatype: "xml",
        data: "action=" + action + "&sourcetype=" + sourcetype + "&targettype=" + targettype + "&idsource=" + idsource + "&idtarget=" + idtarget + "&ordre=" + ordre,
        success: function (msg) {
            callBack(msg);
        }
    });
}
function addChrono(element, socid, callBack) {
    parentDiv = $(element).parent();
    model = $(element).parent().find(".model").html();
    model_refid = $(element).attr("id").replace("addChrono", "");
    ajaxAddChrono(model_refid, socid, new Array(), callBack);
}

function supprLigne(element) {
    firstParent = $(element).parent();
    parentDiv = $(firstParent).parent();
    callBack = function (ok) {
        if (ok == "ok")
            $(firstParent).fadeOut();
    };
    if ($(parentDiv).hasClass("formAjax"))
        ajaxManipElementElement("rm", $(parentDiv).find(".sourcetype").val(), $(parentDiv).find(".targettype").val(), $(parentDiv).find(".sourceid").val(), $(firstParent).find("input").val(), $(parentDiv).find(".ordre").val(), callBack);
    else
        cacherSuppr(firstParent);
    return false;
}


function cacherSuppr(element) {
    $(element).fadeOut(function () {
        $(element).remove();
    });
}



function autoSave(actionSave) {
    jQuery("*").click(function () {
        initTimeSave();
    });
    jQuery("*").keypress(function () {
        initTimeSave();
    });
    enreg = false;
    function boucleSave() {
        if (new Date().getTime() > timeMax && enreg == false) {
            enreg = true;
            actionSave();
        }
        setTimeout(function () {
            boucleSave();
        }, 1000);
    }
    timeMax = null;
    function initTimeSave() {
        if (timeMax == null) {
            timeMax = new Date().getTime() + 30000;
            boucleSave();
        }
        else
            timeMax = new Date().getTime() + 30000;
    }
    
    
}
