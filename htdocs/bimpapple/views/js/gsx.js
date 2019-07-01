var maxProdQty = 99;
var importCart = false;
var partsGroup = {
    0: 'Général',
    1: 'Visuel',
    2: 'Affichage',
    3: 'Stockage',
    4: 'Périphériques d\'entrées',
    5: 'Cartes',
    6: 'Alimentation',
    7: 'Impression',
    8: 'Périphériques multi-fonctions',
    9: 'Périphériques de communication',
    'A': 'Partage',
    'B': 'iPhone',
    'E': 'iPod',
    'F': 'iPad',
    'W': 'Watch'
};

var partDataType = {
    'eeeCode': 'eeeCode',
    'name': 'Nom',
    'num': 'Ref.',
    'type': 'Type',
    'price': 'Prix'
};

var PM = [];
function PartsManager(sufixe) {
    var ptr = this;
    this.sufixe = sufixe;
    this.$container = $('#partsListContainer' + sufixe);

    this.getPartListId = function (gpe, num) {
        if (this.parts[gpe]) {
            for (idx in this.parts[gpe]) {
                if (this.parts[gpe][idx].num == num)
                    return idx;
            }
        }
        return null;
    };

    // Gestion de l'affichage:
    this.resetPartsDisplay = function () {
        this.$container.find('tr.partRow').each(function () {
            $(this).show();
        });
        this.$container.find('.parts_group_panel').each(function () {
            $(this).find('.partsNbr').removeClass('badge-danger').text($(this).find('.partRow').length);
            $(this).addClass('open').removeClass('closed').children('.panel-heading').click();
        });
    };

    // Gestion des filtres et des recherches:
    this.addKeywordFilter = function () {
        var kw = this.$container.find('.keywordFilter').val();
        if (!kw) {
            bimp_msg('Veuillez entrer un mot-clé', 'warning', null, true);
            return;
        }
        if (/ +/.test(kw)) {

            return;
        }
        if (!/^[a-zA-Z0-9\.,\-]+$/.test(kw)) {
            bimp_msg('Caractères interdits. Merci de n\'utiliser que des caractères aplha-numériques', 'danger', null, true);
            return;
        }
        var kwType = this.$container.find('select.keywordFilterType').val();

        var html = '<div class="curKeyword"><span class="keyword">' + kw + '</span>';
        html += '<span class="kwType kwt_' + kwType + '">&nbsp;&nbsp;(' + partDataType[kwType] + ')</span>';
        html += '<span class="removeKeyWord" onclick="PM[\'parts' + ptr.sufixe + '\'].removeKeywordFilter($(this))"><i class="fa fa-trash"></i></span></div>';
        this.$container.find('.curKeywords').append(html);
        this.$container.find('.keywordFilter').val('');
        this.filterByKeywords();
    };

    this.removeKeywordFilter = function ($span) {
        $span.parent('div.curKeyword').remove();
        if (this.$container.find('.curKeywords').find('div').length)
            this.filterByKeywords();
        else
            this.unsetKeywordsFilter();
    };

    this.searchPartByNum = function () {
        var $result = this.$container.find('.partsSearchResult');
        var search = this.$container.find('.searchPartInput').val();
        if (!search) {
            bimp_msg('Veuillez entrer un code produit', 'danger', null, true);
            return;
        }
        if (!/^[a-zA-Z0-9\-\_ ]+$/.test(search)) {
            bimp_msg('Caractères interdits. Merci de n\'utiliser que des caractères aplha-numériques ainsi que "-" ou "_"', 'danger', null, true);
            return;
        }
        this.unsetSearch();
        $result.html('<div class="partSearchNum"><span class="searchNum">' + search + '</span><span class="removeSearch" onclick="PM[\'parts' + ptr.sufixe + '\'].unsetSearch()"><i class="fa fa-trash"></i></span></div>').slideDown(250);
        this.$container.find('.curKeywords').find('div.curKeyword').each(function () {
            $(this).remove();
        });

        var nTotal = 0;
        this.$container.find('.parts_group_panel').each(function () {
            var n = 0;
            $(this).find('tr.partRow').each(function () {
                if (($(this).data('num') === search) || ($(this).data('newNum') === search)) {
                    n++;
                    nTotal++;
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });

            $(this).find('.partsNbr').text(n);
            if (n > 0) {
                $(this).find('.partsNbr').removeClass('badge-danger');
                $(this).addClass('closed').removeClass('open').children('.panel-heading').click();
            } else {
                $(this).find('.partsNbr').addClass('badge-danger');
                $(this).addClass('open').removeClass('closed').children('.panel-heading').click();
            }
        });

        if (!nTotal) {
            $result.append('<p class="alert alert-danger">Aucun composant compatible ne correspond à ce numéro</p>').slideDown(250)
        }
    };
    this.filterByKeywords = function () {
        this.unsetSearch();
        var kw = [];
        this.$container.find('.curKeywords').children('div.curKeyword').each(function () {
            var txt = $(this).find('span.keyword').text();
            var type = $(this).find('.kwType').attr('class').replace(/^kwType kwt_(.*)$/, '$1');
            kw.push({
                'txt': txt,
                'type': type
            });
        });

        this.$container.find('.parts_group_panel').each(function () {
            var n = 0;

            $(this).find('tr.partRow').each(function () {
                var display = false;
                for (i in kw) {
                    var str = null;
                    var regex = null;
                    switch (kw[i].type) {
                        case 'eeeCode':
                            regex = new RegExp('^(.*)' + kw[i].txt + '(.*)$', 'i');
                            str = $(this).data('eeeCode');
                            break;

                        case 'name':
                            regex = new RegExp('^(.*)' + kw[i].txt + '(.*)$', 'i');
                            str = $(this).data('name');
                            break;

                        case 'num':
                            regex = new RegExp('^(.*)' + kw[i].txt + '(.*)$', 'i');
                            str = $(this).data('num') + ' ' + $(this).data('newNum');
                            break;

                        case 'price':
                            kw[i].txt = kw[i].txt.replace(/,/g, '.');
                            regex = new RegExp('^' + kw[i].txt + '\.*\d*$', 'i');
                            str = $(this).data('price');
                            break;

                        case 'type':
                            regex = new RegExp('^(.*)' + kw[i].txt + '(.*)$', 'i');
                            str = $(this).data('type');
                            break;
                    }
                    if (str) {
                        if (regex.test(str)) {
                            display = true;
                        }
                    }
                }
                if (display) {
                    $(this).show();
                    n++;
                } else {
                    $(this).hide();
                }
            });

            $(this).find('.partsNbr').text(n);
            if (n > 0) {
                $(this).find('.partsNbr').removeClass('badge-danger');
                $(this).addClass('closed').removeClass('open').children('.panel-heading').click();
            } else {
                $(this).find('.partsNbr').addClass('badge-danger');
                $(this).addClass('open').removeClass('closed').children('.panel-heading').click();
            }
        });
    };
    this.unsetKeywordsFilter = function () {
        this.$container.find('.curKeywords').find('div').each(function () {
            $(this).remove();
        });
        this.$container.find('.partsSearchResult').html('').hide();
        this.resetPartsDisplay();
    };
    this.unsetSearch = function () {
        if (this.$container.find('.partSearchNum').length) {
            this.$container.find('.partsSearchResult').html('').hide();
            this.resetPartsDisplay();
        }
    };
}


function reloadRepairsViews(id_sav) {
    BimpAjax('loadRepairs', {
        id_sav: id_sav
    }, $('#sav_repairs').children('.panel-body'), {
        display_success: false,
        display_errors_in_popup_only: true,
        append_html: true,
        display_processing: true,
        processing_padding: 30,
        processing_msg: 'Chargement en cours',
        success: function (result, bimpAjax) {
            $('body').trigger($.Event('objectChange', {
                module: 'bimpsupport',
                object_name: 'BS_SavProduct',
                id_object: 0
            }));
            $('body').trigger($.Event('objectChange', {
                module: 'bimpsupport',
                object_name: 'BS_ApplePart',
                id_object: 0
            }));
        }
    });
}

function loadRepairForm($button, id_sav, serial) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $createRepairForm = $('#createRepairForm');

    if (!$createRepairForm.length) {
        bimp_msg('Une erreur est survenue. Opération impossible', 'danger', null, true);
        return;
    }

    var repairType = $createRepairForm.find('[name="repairType"]').val();
    var symptomesCodes = $createRepairForm.find('[name="symptomesCodes"]').val();

    var title = $createRepairForm.find('[name="repairType"]').find('option[value="' + repairType + '"]').text();

    bimpModal.loadAjaxContent($button, 'loadRepairForm', {
        id_sav: id_sav,
        serial: serial,
        repairType: repairType,
        symptomesCodes: symptomesCodes
    }, title, 'Chargement du formulaire', function (result, bimpAjax) {
        var $form = bimpAjax.$resultContainer.find('.request_form');
        if ($form.length) {
            onRepairFormLoaded($form);
            var modal_idx = parseInt(bimpAjax.$resultContainer.data('modal_idx'));
            $form.data('modal_idx', modal_idx);
            var label = 'Envoyer<i class="fa fa-arrow-circle-right iconRight"></i>';
            var onclick = 'sendGsxRequestFromForm($(this), \'repairForm_' + bimpAjax.repairType + '\', ' + bimpAjax.id_sav + ')';
            bimpModal.addButton(label, onclick, 'primary', 'save_object_button', modal_idx);
        }
    }, {
        error_msg: 'Une erreur est survenue. Le formulaire n\'a pas pu être chargé',
        repairType: repairType,
        id_sav: id_sav
    });
}

function loadSerialUpdateForm($button, serial, id_sav, id_repair, request_type, title) {
    if ($button.hasClass('disabled')) {
        return;
    }

    bimpModal.loadAjaxContent($button, 'loadSerialUpdateForm', {
        serial: serial,
        id_sav: id_sav,
        id_repair: id_repair,
        request_type: request_type
    }, title, 'Chargement du formulaire', function (result, bimpAjax) {
        var $form = bimpAjax.$resultContainer.find('.request_form');
        if ($form.length) {
            onRepairFormLoaded($form);
            var modal_idx = parseInt(bimpAjax.$resultContainer.data('modal_idx'));
            $form.data('modal_idx', modal_idx);
            var label = 'Envoyer<i class="fa fa-arrow-circle-right iconRight"></i>';
            var onclick = 'sendGsxRequestFromForm($(this), \'repairForm_' + bimpAjax.request_type + '\', ' + bimpAjax.id_sav + ')';
            bimpModal.addButton(label, onclick, 'primary', 'save_object_button', modal_idx);
        }
    }, {
        id_sav: id_sav,
        id_repair: id_repair,
        request_type: request_type,
        error_msg: 'Une erreur est survenue. Le formulaire n\'a pas pu être chargé'
    });
}

function loadPartsList(serial, id_sav, sufixe) {
    BimpAjax('loadPartsList', {
        serial: serial,
        id_sav: id_sav,
        sufixe: sufixe
    }, $('#partsListContainer' + sufixe), {
        display_success: false,
        display_errors_in_popup_only: true,
        append_html: true,
        display_processing: true,
        processing_padding: 20,
        processing_msg: 'Chargement en cours',
        success: function (result, bimpAjax) {
            $('#loadPartsButtonContainer' + sufixe).slideUp(250, function () {
                $(this).html('');
            });

            PM['parts' + sufixe] = new PartsManager(sufixe);
        }
    });
}

function addPartToCart($button, id_sav) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $row = $button.findParentByClass('partRow');
    if ($.isOk($row)) {
        var data = {
            module: 'bimpsupport',
            object_name: 'BS_ApplePart',
            id_object: 0,
            id_sav: id_sav,
            label: $row.data('name'),
            part_number: $row.data('num'),
            component_code: $row.data('code'),
            stock_price: $row.data('stock_price'),
            exchange_price: $row.data('exchange_price'),
            price_options: $row.data('price_options')
        };

        BimpAjax('saveObject', data, null, {
            success: function (result, bimpAjax) {
                $('body').trigger($.Event('objectChange', {
                    module: 'bimpsupport',
                    object_name: 'BS_ApplePart',
                    id_object: result.id_object
                }));
            },
            error: function (result, bimpAjax) {
                $button.removeClass('disabled');
            }
        });
    } else {
        bimp_msg('Une errur est survenue. Opération impossible', 'danger', null, true);
    }
}

function sendGsxRequestFromForm($button, $form_id, id_sav) {
    var $form = $('#' + $form_id);
    if (!$form.length) {
        bimp_msg('Erreur - formulaire absent', 'danger');
        return;
    }

    var data = new FormData($form.get(0));

    BimpAjax('sendGSXRequest', data, $form.find('.ajaxResultContainer'), {
        $button: $button,
        id_sav: id_sav,
        display_processing: true,
        processing_padding: 20,
        append_html: true,
        processData: false,
        contentType: false,
        success: function (result, bimpAjax) {
            reloadRepairsViews(bimpAjax.id_sav);
        }
    });
}

function sendGsxRequest($button, data, $resultContainer, successCallback) {
    BimpAjax('sendGSXRequest', data, null, {
        $button: $button,
        display_processing: true,
        processing_padding: 20,
        append_html: true,
        success: function (result, bimpAjax) {
            if (typeof (successCallback) === 'function') {
                successCallback(result);
            }
        }
    });
}

function duplicateInput($button, inputName) {
    var $container = $button.findParentByClass('formRowInput');
    if (!$.isOk($container)) {
        bimp_msg('Une erreur est survenue. opération impossible', null, true);
        return;
    }

    var $template = $container.find('div.inputTemplate');
    var $index = $container.find('#' + inputName + '_nextIdx');
    if ($template.length) {
        if ($index.length) {
            var html = $template.html();
            var index = parseInt($index.val());
            var regex = new RegExp(inputName, 'g');
            index++;
            html = html.replace(regex, inputName + '_' + index);
            var $list = $container.find('div.inputsList');
            if ($list.length) {
                $list.append(html);
                $index.val(index);
                return;
            }
        }
    }
}

function duplicateDatasGroup($button, inputName) {
    var $container = $button.findParentByClass('formInputGroup');
    if (!$.isOk($container) || $container.attr('id') !== inputName) {
        bimp_msg('Une erreur est survenue. opération impossible', null, true);
        return;
    }

    var $template = $container.find('div.dataInputTemplate');
    var $index = $container.find('#' + inputName + '_nextIdx');
    if ($template.length) {
        if ($index.length) {
            var html = '<div class="subInputsList">';
            html += $template.html();
            html += '</div>';
            var index = parseInt($index.val());
            var regex = new RegExp('_idx', 'g');
            html = html.replace(regex, '_' + index);
            index++;
            var $list = $container.find('div.inputsList');
            if ($list.length) {
                $list.append(html);
                $index.val(index);
                return;
            }
        }
    }
}

function onRepairFormLoaded($form) {
    $form.find('[name="requestReviewByApple"]').change(function () {
        if (parseInt($(this).val())) {
            $form.find('[name="checkIfOutOfWarrantyCoverage"]').val(0).change();
        }
    });

    $form.find('.inputContainer').each(function () {
        var id = $(this).find('input').attr('id');
        if (id) {
            if (/^replacementSerialNumber(_\d+)*$/.test(id) ||
                    /^replacementIMEINumber(_\d+)*$/.test(id)) {
                $(this).find('input').focusout(function () {
                    id = id.replace('replacementSerialNumber', 'consignmentFlag');
                    id = id.replace('replacementIMEINumber', 'consignmentFlag');
                    $form.find('[name="' + id + '"]').val(1).change();
                });
            }
        }
    });

    $form.find(".replacementSerialNumber, .replacementIMEINumber").focusout(function () {
        var champ = $(this).attr("id");
        var champ = champ.replace("replacementSerialNumber", "consignmentFlag");
        var champ = champ.replace("replacementIMEINumber", "consignmentFlag");
        $("#" + champ + "_yes").click();
    });

    onFormLoaded($form);
}