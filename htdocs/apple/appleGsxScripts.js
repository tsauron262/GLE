function Part(name, num, type) {
    this.name = name;
    this.num = num;
    this.type = type;
}

function PartsManager() {
    var ptr = this;
    this.groups = {
        0 : 'Général',
        1 :'Visuel',
        2 :'Affichage',
        3 :'Stockage',
        4 :'Périphériques d\'entrées',
        5 :'Cartes',
        6 :'Alimentation',
        7 :'Impression',
        8 :'Périphériques multi-fonctions',
        9 :'Périphériques de communication',
        'A' :'Partage',
        'B' :'iPhone',
        'E' :'iPod',
        'F' :'iPad'
    }
    this.parts = [];

    this.removeParts = function() {
        for (gpe in this.parts) {
            for (id in this.parts[gpe]) {
                delete this.parts[gpe][id];
            }
            delete this.parts[gpe];
        }
        delete this.parts;
        this.parts = [];
    }

    this.addPart = function(group, name, num, type) {
        if (group === ' ')
            group = 0;
        if (!this.parts[group])
            this.parts[group] = [];
        this.parts[group].push(new Part(name, num, type));
    }

    this.displayParts = function() {
        var $container = $('#partsListContainer');
        var ths = '<th style="min-width: 350px">Nom</th>';
        ths += '<th style="min-width: 100px">N°</th>';
        ths += '<th style="min-width: 100px">Type</th>';
        ths += '<th></th>';

        for (gpe in this.parts) {
            $('#typeFiltersContent').append('<input type="checkbox" checked id="typeFilter_'+gpe+'"/><label for="typeFilter_'+gpe+'">'+this.groups[gpe]+'</label><br/>');
            var html = '<div id="partGroup_'+gpe+'" class="partGroup">';
            html += '<div class="partGroupName closed">'+this.groups[gpe]+'<span class="partsNbr">('+this.parts[gpe].length+' produits)</span></div>';
            html += '<div class="partsList">';
            html += '<table><thead>'+ths+'</thead><tbody>';
            var odd = true;
            for (id in this.parts[gpe]){
                html += '<tr class="partRow_'+id+' partRow';
                if (odd)
                    html += '  oddRow';
                html += '">';
                html += '<td>'+this.parts[gpe][id].name+'</td>';
                html += '<td>'+this.parts[gpe][id].num+'</td>';
                html += '<td>'+this.parts[gpe][id].type+'</td>';
                html += '<td><span class="addToOrder">Commander</span></td>';
                html += '</tr>';
                odd = !odd;
            }
            html += '</tbody></table>';
            html += '</div>';
            html += '</div>';
            $container.append(html);
        }
        function openPartsGroup($gpe) {
            $gpe.parent().children('div.partsList').slideDown(250);
            $gpe.attr('class', 'partGroupName opened').off('click').click(function() {
                closePartsGroup($gpe);
            });
        }
        function closePartsGroup($gpe) {
            $gpe.parent().children('div.partsList').slideUp(250);
            $gpe.attr('class', 'partGroupName closed').off('click').click(function() {
                openPartsGroup($gpe);
            });
        }
        $('div.partGroupName.closed').click(function() {
            openPartsGroup($(this));
        });
        $('div.partGroupName.opened').click(function() {
            closePartsGroup($(this));
        });
        $('tr.partRow').mouseover(function() {
            $(this).find('td').css({
                'border-top': '1px solid #74DC97',
                'border-bottom': '1px solid #74DC97',
                'color': '#000',
                'padding': '2px 0'
            });
        }).mouseout(function() {
            $(this).find('td').css({
                'border-top': 'none',
                'border-bottom': 'none',
                'color': '#3C3C3C',
                'padding': '3px 0'
            });
        });
        $('#filterTitle').mouseover(function(){
            showTypeFilters();
        }).mouseout(function() {
            hideTypeFilters();
        });
        $('#typeFiltersContent').mouseover(function(){
            showTypeFilters();
        }).mouseout(function() {
            hideTypeFilters();
        });
        $('#filterCheckAll').click(function() {
            $('#typeFiltersContent').find('input').each(function() {
                $(this).attr('checked', '');
            });
            $('.partGroup').each(function() {
                $(this).stop().css('display', 'none').slideDown(250);
            });
        });
        $('#filterHideAll').click(function() {
            $('#typeFiltersContent').find('input').each(function() {
                $(this).removeAttr('checked');
            });
            $('.partGroup').each(function() {
                $(this).slideUp(250);
            });
        });
        $('#typeFiltersContent input').change(function() {
            var gpe = $(this).attr('id').replace(/^typeFilter_(.+)$/, '$1');
            if ($(this).prop('checked')) {
                $('#partGroup_'+gpe).stop().css('display', 'none').slideDown(250);
            } else {
                $('#partGroup_'+gpe).slideUp(250);
            }
        })
    }

    this.filterByKeywords = function() {
        var kw = [];
        $('#curKeywords').children('div').each(function(){
            kw.push($(this).find('span.keyword').text());
        });
        $('tr.partRow').each(function() {
            $(this).hide();
        });
        for (gpe in ptr.parts) {
            var n = 0;
            for (id in ptr.parts[gpe]) {
                var display = true;
                for (i in kw) {
                    if (ptr.parts[gpe][id].name.search(kw[i]) < 0) {
                        if (ptr.parts[gpe][id].name.search(kw[i].toLowerCase()) < 0) {
                            if (ptr.parts[gpe][id].name.search(kw[i].toUpperCase()) < 0) {
                                display = false;
                            }
                        }
                    }
                }
                if (display) {
                    $('#partGroup_'+gpe).find('tr.partRow_'+id).show();
                    n++;
                }
            }
            $('#partGroup_'+gpe).find('span.partsNbr').html('('+n+' produits)');
        }
    }
}

var PM = new PartsManager();

function showTypeFilters() {
    $('#typeFiltersContent').stop().css('display', 'none').slideDown(250);
}
function hideTypeFilters() {
    $('#typeFiltersContent').stop().slideUp(250);
}
function addKeywordFilter() {
    var kw = $('#keywordFilter').val();
    if (!kw) {
        $('#curKeywords').append('<p class="error">Veuillez entrer un mot-clé</p>');
        $('#curKeywords').find('p.error').fadeOut(2000, function(){
            $(this).remove();
        });
        return;
    }
    if (!/^[a-zA-Z0-9\-\_ ]+$/.test(kw)) {
        $('#curKeywords').append('<p class="error">Caractères interdits. Merci de n\'utiliser que des caractères aplha-numériques ainsi que "-" ou "_"</p>');
        $('#curKeywords').find('p.error').fadeOut(5000, function(){
            $(this).remove();
        });
        return;
    }
    $('#curKeywords').find('p.error').stop().remove();
    $('#curKeywords').append('<div><span class="keyword">'+kw+'</span><span class="removeKeyWord" onclick="removeKeywordFilter($(this))"></span></div>');
    $('#keywordFilter').val('');
    PM.filterByKeywords();
}
function removeKeywordFilter($span) {
    $span.parent('div').remove();
    PM.filterByKeywords();
}
function getXMLHttpRequest() {
    var xhr = null;
    if (window.XMLHttpRequest || window.ActiveXObject) {
        if (window.ActiveXObject) {
            try {
                xhr = new ActiveXObject("msxml2.XMLHTTP");
            }
            catch(e) {
                xhr = new ActiveXObject("Microsoft.XMLHTTP");
            }
        }
        else {
            xhr = new XMLHttpRequest();
        }
    }
    return xhr;
}
function onRequestResponse(xhr, requestType) {
    switch (requestType) {
        case 'newSerial':
            $('#serialResult').slideUp(250);
            $('#productInfos').html(xhr.responseText);
            PM.displayParts();
            $('#productInfos').slideDown(250);
            break;
    }
}
function setGetRequest(requestType, requestParams) {
    var xhr = getXMLHttpRequest();
    xhr.onreadystatechange = function(){
        //alert('state: ' + xhr.readyState + ', status: ' +xhr.status);
        var RT = requestType;
        if((xhr.readyState == 4) && ((xhr.status == 200) || (xhr.status == 0))) {
            onRequestResponse(xhr, RT);
        }
    }
    xhr.open("GET", DOL_URL_ROOT+'/apple/requestProcess.php?action='+requestType+requestParams);
    xhr.send();
}

$(document).ready(function() {
    $('#serialSubmit').click(function() {
        $('#serialResult').slideUp(250);
        $('#productInfos').slideUp(250);
        PM.removeParts();
        if ($('#partsListContainer').length)
            $('#partsListContainer').html('');
        var serial = $('#serialInput').val();
        if (!serial) {
            $('#serialResult').html('<p class="error">Veuillez entrer un numéro de série</p>');
        } else if (!/^[0-9a-zA-Z]+$/.test(serial)) {
            $('#serialResult').html('<p class="error">Le format du numéro de série est incorrect</p>');
        } else {
            setGetRequest('newSerial', '&serial='+serial);
            $('#serialResult').html('<p>Requête en cours de traitement...</p>');
        }
        $('#serialResult').slideDown(250);
    });
});