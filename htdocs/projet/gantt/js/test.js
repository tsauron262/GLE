var currentUser = 0;
var valueArr = new Array();
var currentType = "User";
var trancheHoraireSav = new Array();
var mod="";


function init2ndPanel(pMod)
{
    mod = pMod;
    $('#SelUser'+mod).attr('disabled',false);
    $('#SelUser'+mod).change(function(){
        var value = $('#SelUser'+mod).find(':selected').val();
        if (value > 0)
        {
            currentUser=value;
            ChangeIt(false);
        } else {
            currentUser = 0;
            $('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'></div>");
            $('#AddToTable'+mod).css('display','none');

        }
    });

    $('#SelUserBut'+mod).click(function(){
        var value = $('#SelUser'+mod).find(':selected').val();
        if (value > 0)
        {
            currentUser=value;
            ChangeIt(false);
        } else {
            currentUser = 0;
            $('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'></div>");
            $('#AddToTable'+mod).css('display','none');

        }
    });
    $('#'+mod+'depend').change(function(){
        //draw a dependancy form
        var longHtml = "<div class='dependChange'><table width=100%><tbody>"
            + "<tr><td colspan=3 align=right><span id='addDep' class='addDep'><img src='"+DOL_URL_ROOT+"/theme/GLE/plus.gif' width=16 height=16></span></tr>"
                    + "<tr><td>Pourcentage d'accomplissement</td>" +
                        "<td><span class='sliderDependSpan'>100</span>%<input type='hidden' class='sliderDepend"+mod+"Input' value=100></input></td>" +
                        "<td width=300><div class='sliderDepend'></div></td></tr>"
                    + "</tbody></table></div>";
        $('.dependChange').replaceWith(longHtml);
        $('.sliderDepend').slider({
            animate: true,
            max: 100,
            step: 10,
            range: 'min',
            value:100,
            change:function(ev,ui){
                var value = ui.value;
                $('.sliderDepend'+mod+'Input').val(ui.value);
                $('.sliderDependSpan').text(ui.value);
            }
        });
        $('.addDep').click(function(){
            var id=$('#'+mod+'depend').find(":selected").val();
            if (id>0)
            {

                var name=$('#'+mod+'depend').find(":selected").text();
                var accomp = $('.sliderDepend'+mod+'Input').val();
                var longHtml = "<tr class='ui-widget-content'>" +
                        "           <td align='center' class='ui-widget-content'>"+id+"</td> \
                                    <td align='center' class='ui-widget-content'>"+name+"</td> \
                                    <td align='center' class='ui-widget-content'>"+accomp+"</td> \
                                    <td align='center' class='ui-widget-content'><span id='Iduser"+currentUser+"' class='delFromDepTable"+mod+"'><img height=16 width=16 src='"+DOL_URL_ROOT+"/theme/'.$conf->theme.'/moins.gif'></span></td> \
                                </tr>";

                $('#Depresult'+mod).append($(longHtml));
                //remove task from list
                $('#'+mod+'depend option').each(function(){
                    if( id == $(this).val()){
                        $(this).remove();
                    }
                });
                //delete
                if ($('#'+mod+'depend option').length == 1)
                {
                    $('#'+mod+'depend').attr('disabled',true);
                } else {
                    $('#'+mod+'depend').attr('disabled',false);
                }
                $('.delFromDepTable'+mod).click(function(){
                    var id = $(this).parent().parent().find('td:nth-child(1)').text();
                    var name = $(this).parent().parent().find('td:nth-child(2)').text();
                    var accomp = $(this).parent().parent().find('td:nth-child(3)').text();

                    $('#'+mod+'depend').append($('<option value="'+id+'">'+name+'</option>'));
                    $('#'+mod+'depend').attr('disabled',false);
                    $(this).parent().parent().remove();

                });
            }

        });
    });
    $('#'+mod+'depend').attr('disabled',false);


    $("#AddToTable"+mod).click(function(){
        saveTrancheHoraire();
        if (currentType == "User")
        {
            if (currentUser > 0)
            {
                getFormValue(currentUser);

                if ('x'+valueArr['userName'] != "x" && valueArr['occupation'] +"x" != "x")
                {

                    var longHtml = "<tr class='ui-widget-content tooltipTranche'>" +
                            "           <td align='center' class='ui-widget-content'>User</td> \
                                        <td align='center' class='ui-widget-content'>"+valueArr['userName']+"</td> \
                                        <td align='center' class='ui-widget-content'>"+valueArr['occupation']+"</td> \
                                        <td align='center' class='ui-widget-content'>"+valueArr['role']+"</td> \
                                        <td align='center' class='ui-widget-content'><span id='Iduser"+currentUser+"' class='delFromTable'><img height=16 width=16 src='"+DOL_URL_ROOT+"/theme/'.$conf->theme.'/moins.gif'></span></td> \
                                    </tr>";
                        $('#result'+mod).append($(longHtml));

                    //Remove from list
                    $('#SelUser'+mod).find('option').each(function(){
                        if ($(this).val() == currentUser)
                        {
                            $(this).remove();
                        }
                    });
                    if ($('#SelUser'+mod).find('option').length == 1)
                    {
                        currentUser = 0;
                        $('#SelUser'+mod).attr('disabled',true);
                        $("#AddToTable"+mod).css('display',"none");
                        ChangeIt(true);
                    } else {
                        $('#SelUser'+mod).attr('disabled',false);
                        $("#AddToTable"+mod).css('display',"block");
                        $('#select'+currentUser).attr('disabled',false);
                        $('.sliderOccupDial').slider( 'enable' );
                        currentUser = 0;
                        ChangeIt(true);
                    }

                    $(".delFromTable").click(function(){
                            delFromTable(this,nextVal);
                    }); //close delete

                    $(".tooltipTranche").tooltip({
                        track: true,
                        delay: 100,
                        showURL: false,
                        showBody: " - ",
                        opacity: 0.95,
                        extraClass: "promoteZZ",
                        fade: 250,
                        bodyHandler: function() {
                            //get Id
                            //
                            var obj = this;
                            return(initToolTipUser(obj));
                        }
                    });

                } //close if value ok
            } else { // close currentUser > 0
                $('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'></div>");
                $('#AddToTable'+mod).css('display','none');

            }
        } else if (currentType == "Group")
        {
            getFormValueGrp(currentGrp);

            if ('x'+valueArr['grpName'] != "x" && valueArr['occupation'] +"x" != "x")
            {
                var longHtml = "<tr class='ui-widget-content'>" +
                        "            <td align='center' class='ui-widget-content'>Group</td> \
                                    <td align='center' class='ui-widget-content'>"+valueArr['grpName']+"</td> \
                                    <td align='center' class='ui-widget-content'>"+valueArr['occupation']+"</td> \
                                    <td align='center' class='ui-widget-content'>"+valueArr['role']+"</td> \
                                    <td align='center' class='ui-widget-content'><span id='IdGrp"+currentGrp+"' class='delFromTable'><img height=16 width=16 src='"+DOL_URL_ROOT+"/theme/'.$conf->theme.'/moins.gif'></span></td> \
                                    </tr>";
                $('#result'+mod).append($(longHtml));

                //Remove from list
                var nextVal = 0;
                var i=0;
                $('#tree'+mod).find('span').each(function(){
                    if ($(this).attr('id') == currentGrp)
                    {
                        $(this).parent().css('cursor','no-drop');
                        $(this).parent().addClass('notSelectable');
                        $('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'></div>");
                        $('#AddToTable'+mod).css('display','none');
                    }

                });

                $(".delFromTable").click(function(){
                    delFromTable(this);
                });
            }
        }
    });
    $('#SubAccordion'+mod).accordion({
        animated: 'slide',
        active: 1,
        autoHeight: false,
        change:function(ev,ui){
            if (currentType == "User")
            {
                currentType ="Group";
                $("#AddToTable"+mod).css('display',"block");
            } else {
                currentType = "User";
                if($('#SelUser'+mod).find('option').length > 1)
                {
                    currentUser = 0;
                    $('#SelUser'+mod).attr('disabled',false);
                    $("#AddToTable"+mod).css('display',"block");
                } else {
                    currentUser = 0;
                    $('#SelUser'+mod).attr('disabled',true);
                    $("#AddToTable"+mod).css('display',"none");
                }
            }
            $('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'></div>");
            $('#AddToTable'+mod).css('display','none');

        }
    });

    $(".treeview").treeview({
        animated: "slow",

    });
    $(".treeview").filter(":has(>ul):not(:has(>a))").find(">span").click().add( $("a", $(".treeview")) ).hoverClass();
    $('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'></div>");
    $('#AddToTable'+mod).css('display','none');

}
var currentGrp = "";

function updateTrancheHoraire()
{
    if (currentType == "User")
    {
        if(trancheHoraireSav[currentModTask][currentType] && currentUser && trancheHoraireSav[currentModTask][currentType][currentUser] )
        {
            for (var i in trancheHoraireSav[currentModTask][currentType][currentUser][1])
            {
                if (trancheHoraireSav[currentModTask][currentType][currentUser][1][i] > 0)
                {
                    $("#subFragment-1").find('tbody').find('tr').find('#tr'+i).find('input').val(trancheHoraireSav[currentModTask][currentType][currentUser][1][i]);
                }
            }
            for (var i in trancheHoraireSav[currentModTask][currentType][currentUser][6])
            {
                if (trancheHoraireSav[currentModTask][currentType][currentUser][6][i] > 0)
                {
                    $("#subFragment-2").find('tbody').find('tr').find('#tr'+i).find('input').val(trancheHoraireSav[currentModTask][currentType][currentUser][6][i]);
                }
            }
            for (var i in trancheHoraireSav[currentModTask][currentType][currentUser][7])
            {
                if (trancheHoraireSav[currentModTask][currentType][currentUser][7][i] > 0)
                {
                    $("#subFragment-3").find('tbody').find('tr').find('#tr'+i).find('input').val(trancheHoraireSav[currentModTask][currentType][currentUser][7][i]);
                }
            }
            for (var i in trancheHoraireSav[currentModTask][currentType][currentUser][8])
            {
                if (trancheHoraireSav[currentModTask][currentType][currentUser][8][i] > 0)
                {
                    $("#subFragment-4").find('tbody').find('tr').find('#tr'+i).find('input').val(trancheHoraireSav[currentModTask][currentType][currentUser][8][i]);
                }
            }
        }
    }else {
        if(trancheHoraireSav[currentModTask][currentType] && currentGrp && trancheHoraireSav[currentModTask][currentType][currentGrp] )
        {
            for (var i in trancheHoraireSav[currentModTask][currentType][currentGrp][1])
            {
                if (trancheHoraireSav[currentModTask][currentType][currentGrp][1][i] > 0)
                {
                    $("#subFragment-1").find('tbody').find('tr').find('#tr'+i).find('input').val(trancheHoraireSav[currentModTask][currentType][currentGrp][1][i]);
                }
            }
            for (var i in trancheHoraireSav[currentModTask][currentType][currentGrp][6])
            {
                if (trancheHoraireSav[currentModTask][currentType][currentGrp][6][i] > 0)
                {
                    $("#subFragment-2").find('tbody').find('tr').find('#tr'+i).find('input').val(trancheHoraireSav[currentModTask][currentType][currentGrp][6][i]);
                }
            }
            for (var i in trancheHoraireSav[currentModTask][currentType][currentGrp][7])
            {
                if (trancheHoraireSav[currentModTask][currentType][currentGrp][7][i] > 0)
                {
                    $("#subFragment-3").find('tbody').find('tr').find('#tr'+i).find('input').val(trancheHoraireSav[currentModTask][currentType][currentGrp][7][i]);
                }
            }
            for (var i in trancheHoraireSav[currentModTask][currentType][currentGrp][8])
            {
                if (trancheHoraireSav[currentModTask][currentType][currentGrp][8][i] > 0)
                {
                    $("#subFragment-4").find('tbody').find('tr').find('#tr'+i).find('input').val(trancheHoraireSav[currentModTask][currentType][currentGrp][8][i]);
                }
            }
        }

    }
}

function saveTrancheHoraire()
{
    if (currentType == "User")
    {
        if (!trancheHoraireSav[currentModTask])
        {
            trancheHoraireSav[currentModTask] = new Array();
        }
        if (!trancheHoraireSav[currentModTask][currentType])
        {
            trancheHoraireSav[currentModTask][currentType] = new Array();
        }
        if (!trancheHoraireSav[currentModTask][currentType][currentUser])
        {
            trancheHoraireSav[currentModTask][currentType][currentUser] = new Array();
        }
        //semaine
        if (!trancheHoraireSav[currentModTask][currentType][currentUser][1])
        {
            trancheHoraireSav[currentModTask][currentType][currentUser][1] = new Array();
        }
        if (!trancheHoraireSav[currentModTask][currentType][currentUser][6])
        {
            trancheHoraireSav[currentModTask][currentType][currentUser][6] = new Array();
        }
        if (!trancheHoraireSav[currentModTask][currentType][currentUser][7])
        {
            trancheHoraireSav[currentModTask][currentType][currentUser][7] = new Array();
        }
        if (!trancheHoraireSav[currentModTask][currentType][currentUser][8])
        {
            trancheHoraireSav[currentModTask][currentType][currentUser][8] = new Array();
        }
        $("#subFragment-1").find('tbody').find('tr').each(function(){
            if ($(this).find('td:nth-child(2)').text() == "-"){
                var id = "";
                $(this).find('td:nth-child(2)').each(function(){
                    id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
                });
                trancheHoraireSav[currentModTask][currentType][currentUser][1][id]=0;
            } else {
                var id = "";
                $(this).find('td:nth-child(2)').each(function(){
                    id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
                });
                trancheHoraireSav[currentModTask][currentType][currentUser][1][id]= ($(this).find('td:nth-child(2)').find('input').val()+"x"=="x"?0:$(this).find('td:nth-child(2)').find('input').val()) ;
            }
        });
        $("#subFragment-2").find('tbody').find('tr').each(function(){
            if ($(this).find('td:nth-child(2)').text() == "-"){
                var id = "";
                $(this).find('td:nth-child(2)').each(function(){
                    id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
                });
                trancheHoraireSav[currentModTask][currentType][currentUser][6][id]=0;
            } else {
                var id = "";
                $(this).find('td:nth-child(2)').each(function(){
                    id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
                });
                trancheHoraireSav[currentModTask][currentType][currentUser][6][id]= ($(this).find('td:nth-child(2)').find('input').val()+"x"=="x"?0:$(this).find('td:nth-child(2)').find('input').val()) ;
            }
        });
        $("#subFragment-3").find('tbody').find('tr').each(function(){
            if ($(this).find('td:nth-child(2)').text() == "-"){
                var id = "";
                $(this).find('td:nth-child(2)').each(function(){
                    id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
                });
                trancheHoraireSav[currentModTask][currentType][currentUser][7][id]=0;
            } else {
                var id = "";
                $(this).find('td:nth-child(2)').each(function(){
                    id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
                });
                trancheHoraireSav[currentModTask][currentType][currentUser][7][id]= ($(this).find('td:nth-child(2)').find('input').val()+"x"=="x"?0:$(this).find('td:nth-child(2)').find('input').val()) ;
            }
        });
        $("#subFragment-4").find('tbody').find('tr').each(function(){
            if ($(this).find('td:nth-child(2)').text() == "-"){
                var id = "";
                $(this).find('td:nth-child(2)').each(function(){
                    id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
                });
                trancheHoraireSav[currentModTask][currentType][currentUser][8][id]=0;
            } else {
                var id = "";
                $(this).find('td:nth-child(2)').each(function(){
                    id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
                });
                trancheHoraireSav[currentModTask][currentType][currentUser][8][id]= ($(this).find('td:nth-child(2)').find('input').val()+"x"=="x"?0:$(this).find('td:nth-child(2)').find('input').val()) ;
            }
        });

    } else {
        if (!trancheHoraireSav[currentModTask])
        {
            trancheHoraireSav[currentModTask] = new Array();
        }
        if (!trancheHoraireSav[currentModTask][currentType])
        {
            trancheHoraireSav[currentModTask][currentType] = new Array();
        }
        if (!trancheHoraireSav[currentModTask][currentType][currentGrp])
        {
            trancheHoraireSav[currentModTask][currentType][currentGrp] = new Array();
        }
        //semaine
        if (!trancheHoraireSav[currentModTask][currentType][currentGrp][1])
        {
            trancheHoraireSav[currentModTask][currentType][currentGrp][1] = new Array();
        }
        if (!trancheHoraireSav[currentModTask][currentType][currentGrp][6])
        {
            trancheHoraireSav[currentModTask][currentType][currentGrp][6] = new Array();
        }
        if (!trancheHoraireSav[currentModTask][currentType][currentGrp][7])
        {
            trancheHoraireSav[currentModTask][currentType][currentGrp][7] = new Array();
        }
        if (!trancheHoraireSav[currentModTask][currentType][currentGrp][8])
        {
            trancheHoraireSav[currentModTask][currentType][currentGrp][8] = new Array();
        }

        $("#subFragment-1").find('tbody').find('tr').each(function(){
            if ($(this).find('td:nth-child(2)').text() == "-"){
                var id = "";
                $(this).find('td:nth-child(2)').each(function(){
                    id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
                });
                trancheHoraireSav[currentModTask][currentType][currentGrp][1][id]=0;
            } else {
                var id = "";
                $(this).find('td:nth-child(2)').each(function(){
                    id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
                });
                trancheHoraireSav[currentModTask][currentType][currentGrp][1][id]= ($(this).find('td:nth-child(2)').find('input').val()+"x"=="x"?0:$(this).find('td:nth-child(2)').find('input').val()) ;
                //alert(trancheHoraireSav[currentModTask][currentType][currentGrp][1][id]);
            }
        });
        $("#subFragment-2").find('tbody').find('tr').each(function(){
            if ($(this).find('td:nth-child(2)').text() == "-"){
                var id = "";
                $(this).find('td:nth-child(2)').each(function(){
                    id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
                });
                trancheHoraireSav[currentModTask][currentType][currentGrp][6][id]=0;
            } else {
                var id = "";
                $(this).find('td:nth-child(2)').each(function(){
                    id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
                });
                trancheHoraireSav[currentModTask][currentType][currentGrp][6][id]= ($(this).find('td:nth-child(2)').find('input').val()+"x"=="x"?0:$(this).find('td:nth-child(2)').find('input').val()) ;
            }
        });
        $("#subFragment-3").find('tbody').find('tr').each(function(){
            if ($(this).find('td:nth-child(2)').text() == "-"){
                var id = "";
                $(this).find('td:nth-child(2)').each(function(){
                    id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
                });
                trancheHoraireSav[currentModTask][currentType][currentGrp][7][id]=0;
            } else {
                var id = "";
                $(this).find('td:nth-child(2)').each(function(){
                    id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
                });
                trancheHoraireSav[currentModTask][currentType][currentGrp][7][id]= ($(this).find('td:nth-child(2)').find('input').val()+"x"=="x"?0:$(this).find('td:nth-child(2)').find('input').val()) ;
            }
        });
        $("#subFragment-4").find('tbody').find('tr').each(function(){
            if ($(this).find('td:nth-child(2)').text() == "-"){
                var id = "";
                $(this).find('td:nth-child(2)').each(function(){
                    id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
                });
                trancheHoraireSav[currentModTask][currentType][currentGrp][8][id]=0;
            } else {
                var id = "";
                $(this).find('td:nth-child(2)').each(function(){
                    id = $(this).attr('id').replace(/[a-zA-Z]*/,"");
                });
                trancheHoraireSav[currentModTask][currentType][currentGrp][8][id]= ($(this).find('td:nth-child(2)').find('input').val()+"x"=="x"?0:$(this).find('td:nth-child(2)').find('input').val()) ;
            }
        });
    }
}

function delFromTable(obj,nextVal)
{
    var id = $(obj).attr('id').replace(/[a-zA-Z]*/,'');
    var type = $(obj).parent().parent().find("td:nth-child(1)").text();
    var userName = $(obj).parent().parent().find("td:nth-child(2)").text();
    var occupation = $(obj).parent().parent().find("td:nth-child(3)").text();
    var role = $(obj).parent().parent().find("td:nth-child(4)").text();

    if (type =='Group')
    {
        valueArr['occupation']=occupation;
        valueArr['role']=role;
        valueArr['grpName']=userName;
        currentGrp=id;
        //Add in treeview
        $("#tree"+mod).find('#'+currentGrp).parent().removeClass('notSelectable');
        $("#tree"+mod).find('#'+currentGrp).parent().css('cursor','pointer');

        //Rem from table
        $(obj).parent().parent().remove();
        setFormValueGrp(currentGrp);
        ChangeIt(true);
    } else if (type == 'User')
    {
        valueArr['occupation']=occupation;
        valueArr['role']=role;
        valueArr['userName']=userName;
        currentUser=id;
        //Add in seletUser
        $('#SelUser'+mod).append('<option SELECTED value="'+id+'">'+userName+'</option>');
        if ($('#SelUser'+mod).find('option').length == 1)
        {
            currentUser = 0;
            $('#SelUser'+mod).attr('disabled',true);
            $("#AddToTable"+mod).css('display',"none");
        } else {
            $('#SelUser'+mod).attr('disabled',false);
            $('#select'+currentUser).attr('disabled',false);
            $('.sliderOccupDial').slider( 'enable' );
            currentUser = nextVal;
        }
        //Rem from table
        $(obj).parent().parent().remove();
        setFormValue(currentUser);
        ChangeIt(true);
    }
}
function secToTime(pSec)
{
    var sec = parseInt(pSec);
    var hour = Math.floor((sec / 3600));
    var min = Math.abs(((hour * 3600) - sec) / 60);
    var minStr = new String(min);
    var hourStr = new String(hour);
    if (minStr.length == 1) { minStr = "0"+minStr;}
    if (hourStr.length == 1) { hourStr = "0"+hourStr;}
    var ret = hourStr + ":"+minStr;
    return ret;
}

function SelectGrp(grpId)
{
    currentGrp = grpId;
    if ($("#tree"+mod).find('li').find('#'+grpId).parent().hasClass('notSelectable'))
    {
        $('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'></div>");
        $('#AddToTable'+mod).css('display','none');

    } else {
        //Get the Form
        var tableauSemaine = "<table><thead><tr><th class='ui-state-default ui-th-column'>Tranche horaire</th><th class='ui-state-default ui-th-column' style='min-width:162px'>Nb heure</th><th class='ui-state-default ui-th-column'>Facteur</th></tr></thead><tbody>";
        var iter = 0;
        var count = 0;
        for (var i in trancheHoraire[1])
        {
            count++;
        }
        var iter1=1;
        if (count == 0)
        {
            tableauSemaine += "<tr><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr0'>-</td></tr>";
        } else {

            for( var i in trancheHoraire[1])
            {
                var debut = trancheHoraire[1][i]['debut'];
                var fin = trancheHoraire[1][i]['fin'];
                var facteur = trancheHoraire[1][i]['facteur'];
                if (iter == 0 && debut > 0)
                {
                    tableauSemaine += "<tr><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; "+secToTime(debut)+" </td><td class='ui-widget-content' id='tr0'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
                    iter ++;
                }
                tableauSemaine += "<tr><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+" </td><td class='ui-widget-content' id='tr"+i+"'><input></input></td><td class='ui-widget-content'>"+facteur+"</td></tr>";
                if (count == iter1 && fin < (23*3600 + 59 * 60))
                {
                    tableauSemaine += "<tr><td class='ui-widget-content'>De "+secToTime(fin)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr999'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
                }
                iter1++;
            }
        }
       tableauSemaine += "</tbody></table>";

       var tableauSamedi = "<table><thead><tr><th class='ui-state-default ui-th-column' >Tranche horaire</th><th class='ui-state-default ui-th-column'  style='min-width:162px'>Nb heure</th><th class='ui-state-default ui-th-column' >Facteur</th></tr></thead><tbody>";
       iter = 0;
       count = 0;
       for (var i in trancheHoraire[6])
       {
           count++;
       }
       iter1=1;
       if (count == 0)
       {
          tableauSamedi += "<tr><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr0'>-</td></tr>";
       } else {

        for( var i in trancheHoraire[6])
        {
            var debut = trancheHoraire[6][i]['debut'];
            var fin = trancheHoraire[6][i]['fin'];
            var facteur = trancheHoraire[6][i]['facteur'];
            if (iter == 0 && debut > 0)
            {
                tableauSamedi += "<tr><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; "+secToTime(debut)+" </td><td class='ui-widget-content' id='tr0'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
                iter ++;
            }
            tableauSamedi += "<tr><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+" </td><td class='ui-widget-content' id='tr"+i+"'><input></input></td><td class='ui-widget-content'>"+facteur+"</td></tr>";
            if (count == iter1 && fin < (23*3600 + 59 * 60))
            {
                tableauSamedi += "<tr><td class='ui-widget-content'>De "+secToTime(fin)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr999'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
            }
            iter1++;
        }
       }
       tableauSamedi += "</tbody></table>";

       var tableauDimanche = "<table><thead><tr><th class='ui-state-default ui-th-column' >Tranche horaire</th><th class='ui-state-default ui-th-column'  style='min-width:162px'>Nb heure</th><th class='ui-state-default ui-th-column' >Facteur</th></tr></thead><tbody>";
       iter = 0;
       count = 0;
       for (var i in trancheHoraire[7])
       {
           count++;
       }
       iter1=1;
       if (count == 0)
       {
        tableauDimanche += "<tr><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr0'>-</td></tr>";
       } else {

        for( var i in trancheHoraire[7])
        {
            var debut = trancheHoraire[7][i]['debut'];
            var fin = trancheHoraire[7][i]['fin'];
            var facteur = trancheHoraire[7][i]['facteur'];
            if (iter == 0 && debut > 0)
            {
                tableauDimanche += "<tr><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; "+secToTime(debut)+" </td><td class='ui-widget-content' id='tr0'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
                iter ++;
            }
            tableauDimanche += "<tr><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+" </td><td class='ui-widget-content' id='tr"+i+"'><input></input></td><td class='ui-widget-content'>"+facteur+"</td></tr>";
            if (count == iter1 && fin < (23*3600 + 59 * 60))
            {
                tableauDimanche += "<tr><td class='ui-widget-content'>De "+secToTime(fin)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr999'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
            }
            iter1++;
        }
       }
       tableauDimanche += "</tbody></table>";

       var tableauFerie = "<table><thead><tr><th class='ui-state-default ui-th-column' >Tranche horaire</th><th class='ui-state-default ui-th-column'  style='min-width:162px'>Nb heure</th><th class='ui-state-default ui-th-column' >Facteur</th></tr></thead><tbody>";
       iter = 0;
       count = 0;
       for (var i in trancheHoraire[8])
       {
           count++;
       }
       iter1=1;
       if (count == 0)
       {
        tableauFerie += "<tr><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr0'>-</td></tr>";
       } else {

            for( var i in trancheHoraire[8])
            {
                var debut = trancheHoraire[8][i]['debut'];
                var fin = trancheHoraire[8][i]['fin'];
                var facteur = trancheHoraire[8][i]['facteur'];
                if (iter == 0 && debut > 0)
                {
                    tableauFerie += "<tr><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; "+secToTime(debut)+" </td><td class='ui-widget-content' id='tr0'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
                    iter ++;
                }
                tableauFerie += "<tr><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+" </td><td class='ui-widget-content' id='tr"+i+"'><input></input></td><td class='ui-widget-content'>"+facteur+"</td></tr>";
                if (count == iter1 && fin < (23*3600 + 59 * 60))
                {
                    tableauFerie += "<tr><td class='ui-widget-content'>De "+secToTime(fin)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr999'>-</td><td >"+facteurDefault+"</td></tr>";
                }
                iter1++;
            }
       }
       tableauFerie += "</tbody></table>";


        longHtml = "<div id='div"+currentGrp+"'>"
                 + "    <table  class='horaire' width='100%' style='margin-left: 10px; border-collapse: collapse;'>"
                 + "        <tr>"
                 + "            <td colspan=3>"
                 + "                 <div class='ui-widget-header ui-widget  ui-helper-reset ui-state-default ui-corner-all ' style='width: 100%; margin: 0; margin-right: 0; padding-left: 10px;padding-top: 10px;padding-bottom: 10px;' id='divTitle'>group</div> "
                 + "            </td>"
                 + "        </tr>"
                 + "        <tr>"
                 + "            <td colspan=3>&nbsp;"
                 + "            </td>"
                 + "        </tr>"
                 + "        <tr style='margin: 10px; font-size: 125%;'>"
                 + "            <td>Occupation</td>"
                 + "            <td width=20>"
                 + "                 <span id='span"+currentGrp+"'>100</span>% "
                 + "            </td>"
                 + "            <td>"
                 + "                    <input type='hidden' value='100' id='input"+currentGrp+"'>"
                 + "                <div class='sliderOccupDial' id='slide"+currentGrp+"'></div>"
                 + "            </td>"
                 + "        </tr>"
                 + "        <tr style='margin: 10px; font-size: 125%;'>"
                 + "            <td>R&ocirc;le</td>"
                 + "            <td colspan=2 align=right>"
                 + "                <SELECT style='width: 304px;' id='select"+currentGrp+"' size=1>"
                 + "                    <OPTION value='actor'>Acteur</OPTION>"
                 + "                    <OPTION value='admin'>Administrateur</OPTION>"
                 + "                    <OPTION value='read'>Reviewer</OPTION>"
                 + "                </SELECT>"
                 + "            </td>"
                 + "        </tr>"
                 + "        <tr>"
                 + "            <td colspan=3>&nbsp;"
                 + "            </td>"
                 + "        </tr>"
//horaire
                 + "        <tr>"
                 + "            <td colspan=3>"
                 + "                 <div class='ui-widget-header ui-widget  ui-helper-reset ui-state-default ui-corner-all ' style='font-width: 120%; width: 100%; margin: 0; margin-right: 0; padding-left: 10px;padding-top: 10px;padding-bottom: 10px;' id=''>Tranche horaire</div> "
                 + "            </td>"
                 + "        </tr>"
                 + "        <tr>"
                 + "            <td colspan=3>&nbsp;"
                 + "            </td>"
                 + "        </tr>"
                 + "        <tr>"
                 + "            <td colspan=3>"
                 + "                 <div id='TRTabs' style='width: 100%;'>"
                 + "                     <ul>"
                 + "                         <li><a href='#subFragment-1'><span>Semaine</span></a></li>"
                 + "                         <li><a href='#subFragment-2'><span>Samedi</span></a></li>"
                 + "                         <li><a href='#subFragment-3'><span>Dimanche</span></a></li>"
                 + "                         <li><a href='#subFragment-4'><span>F&eacute;ri&eacute;</span></a></li>"
                 + "                     </ul>"
                 + "                     <div id='subFragment-1'>"
//par tranche horaire
                 + "                         <p>"+tableauSemaine+"</p>"
                 + "                     </div>"
                 + "                     <div id='subFragment-2'>"
                 + "                         <p>"+tableauSamedi+"</p>"
                 + "                     </div>"
                 + "                     <div id='subFragment-3'>"
                 + "                         <p>"+tableauDimanche+"</p>"
                 + "                     </div>"
                 + "                     <div id='subFragment-4'>"
                 + "                         <p>"+tableauFerie+"</p>"
                 + "                     </div>"
                 + "                 </div> "
                 + "            </td>"
                 + "        </tr>"
                 + "    </table>"
                 + "</div>";
        $form = $(longHtml);

        //draw form
        $('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'>"+$form.html()+"</div>");
        $('#AddToTable'+mod).css('display','block');
        $('#TRTabs').tabs({
            spinner: "Chargement",
            cache: true
        });
        //redraw Slider
        $('.sliderOccupDial').slider({ animate: true,
                              max: 100,
                              step: 5,
                              range: 'min',
                              value:100,
                              change:function(ev,ui){
                                  var value = ui.value;
                                  $('#input'+currentGrp).val(ui.value);
                                  $('#span'+currentGrp).text(ui.value);
                              }
                             });

        //fill the form
        var occupation = 0;
        var role = "";
        if (currentGrp != 0)
        {
            setFormValueGrp(currentGrp);
            updateTrancheHoraire();
        }
    }
}

function ChangeIt(disabled)
{

    var tableauSemaine = "<table><thead><tr class='ui-widget-content'><th class='ui-state-default ui-th-column' >Tranche horaire</th><th class='ui-state-default ui-th-column'  style='min-width:162px'>Nb heure</th><th class='ui-state-default ui-th-column' >Facteur</th></tr></thead><tbody>";
    var iter = 0;
    var count = 0;
    for (var i in trancheHoraire[1])
    {
        count++;
    }
    var iter1=1;
    if (count == 0)
    {
        tableauSemaine += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; 23:59 </td><td id='tr0' class='ui-widget-content'>-</td></tr>";
    } else {
        for( var i in trancheHoraire[1])
        {
            var debut = trancheHoraire[1][i]['debut'];
            var fin = trancheHoraire[1][i]['fin'];
            var facteur = trancheHoraire[1][i]['facteur'];
            if (iter == 0 && debut > 0)
            {
                tableauSemaine += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; "+secToTime(debut)+" </td><td class='ui-widget-content' id='tr0'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
                iter ++;
            }
            tableauSemaine += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+" </td><td class='ui-widget-content' id='tr"+i+"'><input></input></td><td class='ui-widget-content'>"+facteur+"</td></tr>";
            if (count == iter1 && fin < (23*3600 + 59 * 60))
            {
                tableauSemaine += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(fin)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr999'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
            }
            iter1++;
        }
    }
   tableauSemaine += "</tbody></table>";

   var tableauSamedi = "<table><thead><tr class='ui-widget-content'><th class='ui-state-default ui-th-column' >Tranche horaire</th><th class='ui-state-default ui-th-column'  style='min-width:162px'>Nb heure</th><th class='ui-state-default ui-th-column' >Facteur</th></tr></thead><tbody>";
   iter = 0;
   count = 0;
   for (var i in trancheHoraire[6])
   {
       count++;
   }
   iter1=1;

   if (count == 0)
   {
      tableauSamedi += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr0'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
   } else {

    for( var i in trancheHoraire[6])
    {
        var debut = trancheHoraire[6][i]['debut'];
        var fin = trancheHoraire[6][i]['fin'];
        var facteur = trancheHoraire[6][i]['facteur'];
        if (iter == 0 && debut > 0)
        {
            tableauSamedi += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; "+secToTime(debut)+" </td><td class='ui-widget-content' id='tr0'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
            iter ++;
        }
        tableauSamedi += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+" </td><td class='ui-widget-content' id='tr"+i+"'><input></input></td><td class='ui-widget-content'>"+facteur+"</td></tr>";
        if (count == iter1 && fin < (23*3600 + 59 * 60))
        {
            tableauSamedi += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(fin)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr999'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
        }
        iter1++;
    }
   }
   tableauSamedi += "</tbody></table>";

   var tableauDimanche = "<table><thead><tr class='ui-widget-content'><th class='ui-state-default ui-th-column' >Tranche horaire</th><th class='ui-state-default ui-th-column'  style='min-width:162px'>Nb heure</th><th class='ui-state-default ui-th-column' >Facteur</th></tr></thead><tbody>";
   iter = 0;
   count = 0;
   for (var i in trancheHoraire[7])
   {
       count++;
   }
   iter1=1;
   if (count == 0)
   {
    tableauDimanche += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr0'>-</td></tr>";
   } else {
    for( var i in trancheHoraire[7])
    {
        var debut = trancheHoraire[7][i]['debut'];
        var fin = trancheHoraire[7][i]['fin'];
        var facteur = trancheHoraire[7][i]['facteur'];
        if (iter == 0 && debut > 0)
        {
            tableauDimanche += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; "+secToTime(debut)+" </td><td class='ui-widget-content' id='tr0'> - </td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
            iter ++;
        }
        tableauDimanche += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+" </td><td class='ui-widget-content' id='tr"+i+"'><input></input></td><td class='ui-widget-content'>"+facteur+"</td></tr>";
        if (count == iter1 && fin < (23*3600 + 59 * 60))
        {
            tableauDimanche += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(fin)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr999'> - </td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
        }
        iter1++;
    }
   }
   tableauDimanche += "</tbody></table>";

   var tableauFerie = "<table><thead><tr class='ui-widget-content'><th class='ui-state-default ui-th-column' >Tranche horaire</th><th class='ui-state-default ui-th-column'  style='min-width:162px'>Nb heure</th><th class='ui-state-default ui-th-column' >Facteur</th></tr></thead><tbody>";
   iter = 0;
   count = 0;
   for (var i in trancheHoraire[8])
   {
       count++;
   }
   iter1=1;
   if (count == 0)
   {
    tableauFerie += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr0'>-</td></tr>";
   } else {

    for( var i in trancheHoraire[8])
    {
        var debut = trancheHoraire[8][i]['debut'];
        var fin = trancheHoraire[8][i]['fin'];
        var facteur = trancheHoraire[8][i]['facteur'];
        if (iter == 0 && debut > 0)
        {
            tableauFerie += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; "+secToTime(debut)+" </td><td class='ui-widget-content' id='tr0'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
            iter ++;
        }
        tableauFerie += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+" </td><td class='ui-widget-content' id='tr"+i+"'><input></input></td><td class='ui-widget-content'>"+facteur+"</td></tr>";
        if (count == iter1 && fin < (23*3600 + 59 * 60))
        {
            tableauFerie += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(fin)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr999'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
        }
        iter1++;
    }
   }
   tableauFerie += "</tbody></table>";

    //Get the Form
    longHtml = "<div id='div"+currentUser+"'>"
             + "    <table class='horaire' width='350' style='margin-left: 10px; border-collapse: collapse;'>"
             + "        <tr>"
             + "            <td colspan=3>"
             + "                 <div class='ui-widget-header ui-widget  ui-helper-reset ui-state-default ui-corner-all ' style='width: 100%; margin: 0; margin-right: 0; padding-left: 10px;padding-top: 10px;padding-bottom: 10px;' id='divTitle'></div> "
             + "            </td>"
             + "        </tr>"
             + "        <tr>"
             + "            <td colspan=3>&nbsp;"
             + "            </td>"
             + "        </tr>"

             + "        <tr  style='margin: 10px; font-size: 125%;'>"
             + "            <td>Occupation</td>"
             + "            <td width=20>"
             + "                 <span id='span"+currentUser+"'>100</span>% "
             + "            </td>"
             + "            <td>"
             + "                    <input type='hidden' value='100' id='input"+currentUser+"'>"
             + "                <div class='sliderOccupDial' id='slide"+currentUser+"'></div>"
             + "            </td>"
             + "        </tr>"
             + "        <tr style='margin: 10px; font-size: 125%;'>"
             + "            <td>R&ocirc;le</td>"
             + "            <td colspan=2 align=right>"
             + "                <SELECT  style='width: 304px;' id='select"+currentUser+"' size=1>"
             + "                    <OPTION value='actor'>Acteur</OPTION>"
             + "                    <OPTION value='admin'>Administrateur</OPTION>"
             + "                    <OPTION value='read'>Reviewer</OPTION>"
             + "                </SELECT>"
             + "            </td>"
             + "        </tr>"
           //horaire
             + "        <tr>"
             + "            <td colspan=3>&nbsp;"
             + "            </td>"
             + "        </tr>"
             + "        <tr>"
             + "            <td colspan=3>"
             + "                 <div class='ui-widget-header ui-widget  ui-helper-reset ui-state-default ui-corner-all ' style='width: 100%; margin: 0; margin-right: 0; padding-left: 10px;padding-top: 10px;padding-bottom: 10px;' id=''>Tranche horaire</div> "
             + "            </td>"
             + "        </tr>"
             + "        <tr>"
             + "            <td colspan=3>&nbsp;"
             + "            </td>"
             + "        </tr>"

             + "        <tr>"
             + "            <td colspan=3>"
             + "                 <div id='TRTabs'  style='width: 100%;'>"
             + "                     <ul>"
             + "                         <li><a href='#subFragment-1'><span>Semaine</span></a></li>"
             + "                         <li><a href='#subFragment-2'><span>Samedi</span></a></li>"
             + "                         <li><a href='#subFragment-3'><span>Dimanche</span></a></li>"
             + "                         <li><a href='#subFragment-4'><span>F&eacute;ri&eacute;</span></a></li>"
             + "                     </ul>"
             + "                     <div id='subFragment-1'>"
//par tranche horaire
             + "                         <p>"+tableauSemaine+"</p>"
             + "                     </div>"
             + "                     <div id='subFragment-2'>"
             + "                         <p>"+tableauSamedi+"</p>"
             + "                     </div>"
             + "                     <div id='subFragment-3'>"
             + "                         <p>"+tableauDimanche+"</p>"
             + "                     </div>"
             + "                     <div id='subFragment-4'>"
             + "                         <p>"+tableauFerie+"</p>"
             + "                     </div>"
             + "                 </div> "
             + "            </td>"
             + "        </tr>"

             + "    </table>"
             + "</div>";
    $form = $(longHtml);
    if (disabled)
    {
        $('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'></div>");
        $('#AddToTable'+mod).css('display','none');
    } else {
        //draw form
        $('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'>"+$form.html()+"</div>");
        $('#AddToTable'+mod).css('display','block');


        //redraw Slider
        $('.sliderOccupDial').slider({ animate: true,
                              max: 100,
                              step: 5,
                              range: 'min',
                              value:100,
                              change:function(ev,ui){
                                  var value = ui.value;
                                  $('#input'+currentUser).val(ui.value);
                                  $('#span'+currentUser).text(ui.value);
                              }
                             });
        $('#TRTabs').tabs({
            spinner: "Chargement",
            cache: true
        });

        //fill the form
        var occupation = 0;
        var role = "";
        if (currentUser != 0)
        {
            setFormValue(currentUser);
            updateTrancheHoraire();
        }
    }
}
function setFormValue(userId,disabled)
{
        occupation = (valueArr['occupation'] > 0?valueArr['occupation']:100);
        role = valueArr['role'];
        $('#select'+userId).find('option').each(function(){
            if ($(this).val() == role)
            {
                $(this).attr("selected","true");
            }
        });
        $('.sliderOccupDial').slider('value',occupation);
        $('#input'+userId).val(occupation);
        $('#span'+userId).text(occupation);

        var userName = $("#SelUser"+mod).find('option:selected').text();
        $('#divTitle').text(userName);
        //$('#divTitle').animate('hightlight');
        var options = {};
        $("#divTitle").effect("highlight",options,500);
}

function setFormValueGrp(grpId,disabled)
{
        occupation = (valueArr['occupation'] > 0?valueArr['occupation']:100);
        role = valueArr['role'];

        $('.sliderOccupDial').slider('value',occupation);
        $('#input'+grpId).val(occupation);
        $('#span'+grpId).text(occupation);

        var grpName = "";
        $("#tree"+mod).find('span').each(function(){
            if ($(this).attr('id')==grpId)
                grpName = $(this).text();
        });
        $('#divTitle').text(grpName);
        //$('#divTitle').animate('hightlight');
        var options = {};
        $("#divTitle").effect("highlight",options,500);
}


function getFormValue(userId)
{
    //Save old value
    valueArr=new Array();
    var getOccup = $('#input'+userId).val();
    if (! getOccup>0) { getOccup = 0; }
    valueArr['occupation']=getOccup;
    valueArr['role']=$('#select'+userId).find(':selected').val();
    valueArr['userName']=$('#SelUser'+mod).find(':selected').text();
}
function getFormValueGrp(grpId)
{
    //Save old value
    valueArr=new Array();
    var getOccup = $('#input'+grpId).val();
    if (! getOccup>0) { getOccup = 0; }
    valueArr['occupation']=getOccup;
    valueArr['role']=$('#select'+grpId).find(':selected').val();
    valueArr['grpName']=$('#divTitle').text();
}
function initToolTipUser(obj)
{
    var $obj = $(obj);
    var id = $obj.find('span').attr('id').replace(/[a-zA-Z]*/,'');
    // Type
    var type = $obj.find('td:nth-child(1)').text();
    var longHtml = "<div style='background-color:#FFFFFF;'><table><thead><tr><th class='ui-state-default ui-th-column' colspan=3>Semaine</th></thead><tbody></tr>";
    var ArrStr = new Array();
        ArrStr[1]="Semaine";
        ArrStr[6]="Samedi";
        ArrStr[7]="Dimanche";
        ArrStr[8]="F&eacute;ri&eacute;";

        var ArrJour = new Array();
            ArrJour[0]=1;
            ArrJour[1]=6;
            ArrJour[2]=7;
            ArrJour[3]=8;


        var ArrType = new Array();
            ArrType[0]='User';
            ArrType[1]='Group';
//    if (type=='User')
    for (var h in ArrType)
    {
        var type = ArrType[h];
        for (var j in ArrJour)
        {
            for(var iii in trancheHoraireSav[currentModTask][type][id])
            {
                if (iii == ArrJour[j]) // semaine
                {
                    for (var iiii in trancheHoraireSav[currentModTask][type][id][iii]) // Chaque tranche
                    {
                        var tranche = iiii;
                        if (tranche > 0 && tranche < 999)
                        {
                            var qte = trancheHoraireSav[currentModTask][type][id][iii][iiii];
                            var debut = trancheHoraire[iii][tranche]["debut"];
                            var fin = trancheHoraire[iii][tranche]["fin"];
                            var facteur = trancheHoraire[iii][tranche]["facteur"];
                            longHtml+= "<tr><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+"</td><td  class='ui-widget-content'>"+qte+"</td><td class='ui-widget-content'>"+facteur+"%</td></tr>";
                        }
                    }
                }
            }
            if (ArrJour[j] != 1 )
            {
                longHtml += "</tbody><thead><tr><th class='ui-state-default ui-th-column' colspan=3>"+ArrStr[ArrJour[j]]+"</th></thead><tbody></tr>";
            }
            if (ArrJour[j]==8){
                longHtml+= "</tbody></table></div>";
            }

        }
        return $(longHtml);
    }
}