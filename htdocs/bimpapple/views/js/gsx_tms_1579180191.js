var gsx_is_logged = true;
var gsx_login_url = '';
var gsx_nologged_requests = [];
var use_gsx = false;
var use_gsx_v2 = false;
var next_gsx_ajax_id = 1;
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

// GSX V2:

function GsxAjax(method, data, $resultContainer, params) {

    if (typeof (params.confirm_msg) !== 'undefined' && params.confirm_msg) {
        if (!confirm(params.confirm_msg)) {
            return;
        }
    }

    var gsxAjax = this;
    this.id = next_gsx_ajax_id;
    next_gsx_ajax_id++;

    if (typeof (params.url) === 'undefined' || !params.url) {
        params.url = dol_url_root + '/bimpapple/index.php';
    }

    this.action = 'gsxRequest';

    if (method !== 'gsxProcessRequestForm' && method !== 'gsxFetchRepairEligibility') {
        this.data = {
            gsx_method: method,
            gsx_params: data
        };
    } else {
        this.data = data;
    }

    this.$resultContainer = $resultContainer;
    this.params = params;

    if (typeof (params.success) === 'function') {
        this.params.request_success = params.success;
    }

    this.params.success = function (result, bimpAjax) {
        if (typeof (result.gsx_no_logged) !== 'undefined' && parseInt(result.gsx_no_logged)) {
            bimpAjax.gsxAjax.nologged();
        } else if (typeof (bimpAjax.gsxAjax.params.request_success) === 'function') {
            bimpAjax.gsxAjax.params.request_success(result, bimpAjax);
        }
    };

    this.params.gsxAjax = this;

    this.send = function () {
        if (gsx_is_logged) {
            BimpAjax(gsxAjax.action, gsxAjax.data, gsxAjax.$resultContainer, gsxAjax.params);
        } else {
            gsxAjax.nologged();
        }
    };

    this.nologged = function () {
        gsx_is_logged = false;
        gsx_nologged_requests[gsxAjax.id] = gsxAjax;

//        window.open(gsx_login_url);
        bimp_msg('Veuillez vous authentifier sur la plateforme GSX', 'warning', null, true);
        window.open(gsx_login_url, 'Authentification GSX', "menubar=no, status=no, width=800, height=600");

        setObjectAction($(''), {
            module: 'bimpsupport',
            object_name: 'BS_SAV'
        }, 'setGsxActiToken', {}, 'gsx_token', null, function () {
            gsx_on_login_success();
        });
    };

    this.send();
}

function gsx_on_login_success() {
    if (gsx_is_logged) {
        return;
    }

    gsx_is_logged = true;

    for (var id in gsx_nologged_requests) {
        if (typeof (gsx_nologged_requests[id]) === 'object') {
            gsx_nologged_requests[id].send();
        }
    }

    gsx_nologged_requests = [];
}

function gsx_loadRequestModalForm($button, title, requestName, data, params) {
    if ($.isOk($button) && $button.hasClass('disabled')) {
        return;
    }

    data.requestName = requestName;

    if (requestName === 'repairCreate') {
        var $repairForm = $('#createRepairForm');
        if ($.isOk($repairForm)) {
            data.repairType = $repairForm.find('[name="repairType"]').val();
            if (!data.repairType) {
                bimp_msg('Veuillez sélectionner un type de réparation', 'warning', null, true);
                return;
            }
        }
    }

    params.$button = $button;

    if (typeof (params.success) === 'function') {
        params.load_request_form_success = params.success;
    }

    bimpModal.newContent(title, '', false, '', null, 'medium');
    bimpModal.removeComponentContent('repairForm_' + requestName);
    var modal_idx = bimpModal.idx;
    var $container = bimpModal.$contents.find('#modal_content_' + modal_idx);

    params.success = function (result, bimpAjax) {
        if (typeof (result.html) !== 'undefined' && result.html) {
            var $content = bimpModal.$contents.find('#modal_content_' + bimpAjax.modal_idx);
            if ($.isOk($content)) {
                var $form = $content.find('.request_form');
                if ($form.length) {
                    onFormLoaded($form);
                    $form.data('modal_idx', bimpAjax.modal_idx);
                    if (typeof (result.button) === 'object') {
                        var label = result.button.label;
                        var onclick = result.button.onclick;
                    } else {
                        var label = 'Envoyer<i class="fa fa-arrow-circle-right iconRight"></i>';
                        var onclick = 'gsx_processRequestForm($(this))';
                    }
                    bimpModal.addButton(label, onclick, 'primary', 'save_object_button', bimpAjax.modal_idx);
                }
            }
        }
    };

    params.modal_idx = modal_idx;
    params.append_html = true;
    params.display_processing = true;
    params.processing_msg = 'Chargement du formulaire';
    params.display_success = false;

    GsxAjax('gsxLoadRequestForm', data, $container, params);
}

function gsx_processRequestForm($button) {
    if ($.isOk($button)) {
        if ($button.hasClass('disabled')) {
            return;
        }

        var modal_idx = parseInt($button.data('modal_idx'));

        if (modal_idx && !isNaN(modal_idx)) {
            var $container = bimpModal.getContent(modal_idx);

            if ($.isOk($container)) {
                var $form = $container.find('form.request_form');
                if ($form.length) {
                    var data = new FormData($form.get(0));
                    GsxAjax('gsxProcessRequestForm', data, $form.find('.ajaxResultContainer'), {
                        modal_idx: modal_idx,
                        $button: $button,
                        display_processing: true,
                        processing_padding: 20,
                        append_html: true,
                        processData: false,
                        contentType: false,
                        modal_scroll_bottom: true,
                        success: function (result, bimpAjax) {
                            if (typeof (result.button) === 'object') {
                                bimpModal.$footer.find('.extra_button.modal_' + bimpAjax.modal_idx).remove();
                                bimpModal.addButton(result.button.label, result.button.onclick, 'primary', 'save_object_button', bimpAjax.modal_idx);
                            }
                        }
                    });
                    return;
                }
            }
        }
    }

    bimp_msg('Une erreur est survenue. Envoi du formulaire impossible', 'danger');
}

function gsx_FetchRepairEligibility($button) {
    if ($.isOk($button)) {
        if ($button.hasClass('disabled')) {
            return;
        }

        var modal_idx = parseInt($button.data('modal_idx'));

        if (modal_idx && !isNaN(modal_idx)) {
            var $container = bimpModal.getContent(modal_idx);

            if ($.isOk($container)) {
                var $form = $container.find('form.request_form');
                if ($form.length) {
                    $form.find('[name="gsx_requestForm"]').val(0);
                    var $input = $form.find('[name="gsx_fetchRepairEligibility"]');
                    if (!$input.length) {
                        $form.append('<input type="hidden" name="gsx_fetchRepairEligibility" value="1"/>');
                    } else {
                        $input.val(1);
                    }

                    $form.find('[name="gsx_fetchRepairEligibility"]').val(1);
                    bimpModal.$footer.find('.extra_button.create_repair_button.modal_' + modal_idx).remove();

                    var data = new FormData($form.get(0));
                    GsxAjax('gsxFetchRepairEligibility', data, $form.find('.ajaxResultContainer'), {
                        $form: $form,
                        modal_idx: modal_idx,
                        $button: $button,
                        display_success: false,
                        display_processing: true,
                        processing_padding: 20,
                        append_html: true,
                        processData: false,
                        contentType: false,
                        modal_scroll_bottom: true,
                        success: function (result, bimpAjax) {
                            if (typeof (result.repair_ok) !== 'undefined' && result.repair_ok) {
                                bimpAjax.$form.find('[name="gsx_requestForm"]').val(1);
                                bimpAjax.$form.find('[name="gsx_fetchRepairEligibility"]').val(0);

                                var label = 'Créer la réparation<i class="fa fa-arrow-circle-right iconRight"></i>';
                                var onclick = 'gsx_processRequestForm($(this))';
                                bimpModal.addButton(label, onclick, 'primary', 'save_object_button create_repair_button', bimpAjax.modal_idx);
                            }
                        }
                        , error: function (result, bimpAjax) {
                            bimpAjax.$form.find('[name="gsx_requestForm"]').val(1);
                            bimpAjax.$form.find('[name="gsx_fetchRepairEligibility"]').val(0);

                            var label = 'Créer la réparation<i class="fa fa-arrow-circle-right iconRight"></i>';
                            var onclick = 'gsx_processRequestForm($(this))';
                            bimpModal.addButton(label, onclick, 'primary', 'save_object_button create_repair_button', bimpAjax.modal_idx);
                        }
                    });
                    return;
                }
            }
        }
    }

    bimp_msg('Une erreur est survenue. Envoi du formulaire impossible', 'danger');
}

function gsx_loadAddIssueForm($button, id_sav) {
    if ($.isOk($button) && $button.hasClass('disabled')) {
        return;
    }

    GsxAjax('gsx_loadAddIssueForm', {
        'id_sav': id_sav
    }, null, {
        $button: $button,
        display_success: false,
        success: function (result, bimpAjax) {
            if (typeof (result.html) !== 'undefined' && result.html) {
                appendModalForm(result.html, result.form_id, 'default', 'Ajouter un problème composant');
            } else {
                bimp_msg('Echec du chargement du formulaire pour une raison inconnue', 'danger');
            }
        }
    });
}

function gsx_loadAddPartsForm($button, id_issue) {
    if ($.isOk($button) && $button.hasClass('disabled')) {
        return;
    }

    bimpModal.newContent('Ajout de composant Apple', '', false, '', null, 'large');
    bimpModal.removeComponentContent('partsListContainer_issue_' + id_issue);
    var modal_idx = bimpModal.idx;
    var $container = bimpModal.$contents.find('#modal_content_' + modal_idx);

    GsxAjax('gsx_loadAddPartsForm', {
        'id_issue': id_issue
    }, $container, {
        $button: $button,
        modal_idx: modal_idx,
        id_issue: id_issue,
        display_success: false,
        append_html: true,
        display_processing: true,
        processing_padding: 20,
        processing_msg: 'Chargement en cours',
        success: function (result, bimpAjax) {
            bimpModal.addButton('<i class="fas fa5-save iconLeft"></i>Enregistrer', 'gsx_saveAppleParts($(this), ' + bimpAjax.id_issue + ', ' + bimpAjax.modal_idx + ')', 'primary', 'save_object_button', bimpAjax.modal_idx);
            setCommonEvents(bimpAjax.$resultContainer);
            PM['parts' + '_issue_' + id_issue] = new PartsManager('_issue_' + bimpAjax.id_issue);
        }
    });
}

function gsx_saveAppleParts($button, id_issue, modal_idx) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $container = bimpModal.$contents.find('#modal_content_' + modal_idx);

    if ($.isOk($container)) {
        var $inputs = $container.find('input[name="parts[]"]:checked');

        if (!$inputs.length) {
            bimp_msg('Aucun composant sélectionné', 'warning', null, true);
            return;
        }

        var parts = [];

        $inputs.each(function () {
            var $row = $(this).findParentByClass('partRow');

            if ($.isOk($row)) {
                parts.push({
                    part_number: $row.data('num'),
                    new_part_number: $row.data('newNum'),
                    label: $row.data('name'),
                    stock_price: $row.data('stock_price'),
                    exchange_price: $row.data('exchange_price'),
                    price_options: $row.data('price_options')
                });
            }
        });

        if (parts.length) {
            setObjectAction($button, {
                module: 'bimpsupport',
                object_name: 'BS_Issue',
                id_object: id_issue
            }, 'addParts', {
                parts: parts
            }, null, null, function () {
                bimpModal.removeContent(modal_idx);
            });
        } else {
            bimp_msg('Aucun composant sélectionné', 'warning', null, true);
        }
        return;
    }

    bimp_msg('Une erreur est survenue.', 'danger', null, true);
}

function gsx_findRepairsToImport($button, id_sav) {
    if ($.isOk($button) && $button.hasClass('disabled')) {
        return;
    }

    var $form = $('#findRepairsToImportForm');

    if (!$form.length) {
        bimp_msg('Erreur: formulaire non trouvé', 'danger');
    } else {

        var identifier = $form.find('[name="identifier"]').val();
        if (identifier === '') {
            bimp_msg('Veuillez saisir un identifiant', 'warning', null, true);
            return;
        }
        var type = $form.find('[name="identifier_type"]').val();

        bimpModal.newContent('Import de réparation depuis GSX', '', false, '', null, 'medium');
        bimpModal.removeComponentContent('repairs_import_' + id_sav);
        var modal_idx = bimpModal.idx;
        var $container = bimpModal.$contents.find('#modal_content_' + modal_idx);

        GsxAjax('gsxFindRepairsToImport', {
            id_sav: id_sav,
            identifier: identifier,
            identifier_type: type
        }, $container, {
            $button: $button,
            modal_idx: modal_idx,
            id_sav: id_sav,
            display_success: false,
            append_html: true,
            display_processing: true,
            processing_padding: 20,
            processing_msg: 'Recherche en cours',
            success: function (result, bimpAjax) {
                bimpModal.addButton('<i class="fas fa5-cloud-download-alt iconLeft"></i>Importer', 'gsx_importRepairs($(this), ' + bimpAjax.id_sav + ', ' + bimpAjax.modal_idx + ')', 'primary', 'save_object_button', bimpAjax.modal_idx);
                setCommonEvents(bimpAjax.$resultContainer);
            }
        });
    }
}

function gsx_importRepairs($button, id_sav, modal_idx) {
    if ($.isOk($button) && $button.hasClass('disabled')) {
        return;
    }

    var $container = bimpModal.$contents.find('#modal_content_' + modal_idx);

    if (!$container.length) {
        bimp_msg('Erreur: liste des réparations à importer non trouvée', 'danger');
        return;
    }

    var $selected = $container.find('[name="repairs[]"]:checked');
    if (!$selected.length) {
        bimp_msg('Aucune réparation à importer sélectionnée', 'warning', null, true);
        return;
    }

    var repairs = [];

    $selected.each(function () {
        var $row = $(this).findParentByClass('repairRow');

        if ($.isOk($row)) {
            repairs.push({
                repair_number: $row.data('repair_number'),
                repair_type: $row.data('repair_type')
            });
        }
    });

    GsxAjax('gsxImportRepairs', {
        id_sav: id_sav,
        repairs: repairs
    }, $container.find('.ajaxResults'), {
        $button: $button,
        modal_idx: modal_idx,
        id_sav: id_sav,
        display_success: true,
        display_processing: true,
        processing_padding: 20,
        success: function (result, bimpAjax) {
            if (typeof (result.warnings) === 'undefined' || !result.warnings.length) {
                bimpModal.removeContent(bimpAjax.modal_idx);
                reloadRepairsViews(bimpAjax.id_sav);
            }
        }
    });
}

function gsx_diagnosticSuites($button, serial) {
    if ($.isOk($button) && $button.hasClass('disabled')) {
        return;
    }

    bimpModal.newContent('Lancer un diagnostic à distance', '', false, '', null, 'medium');
    bimpModal.removeComponentContent('diagnostic_suites_' + serial);
    var modal_idx = bimpModal.idx;
    var $container = bimpModal.$contents.find('#modal_content_' + modal_idx);

    GsxAjax('gsxDiagnosticSuites', {
        serial: serial
    }, $container, {
        $button: $button,
        modal_idx: modal_idx,
        serial: serial,
        display_success: false,
        append_html: true,
        display_processing: true,
        processing_padding: 20,
        processing_msg: 'Chargement en cours',
        success: function (result, bimpAjax) {
            setCommonEvents(bimpAjax.$resultContainer);
        }
    });
}

function gsx_runDiagnostic($button, serial, suite_id) {
    if ($.isOk($button) && $button.hasClass('disabled')) {
        return;
    }

    var $row = $button.parent('td').parent('tr');
    $row.after('<tr><td colspan="3"></td></tr>');

    var $container = $row.next().find('td');

    GsxAjax('gsxRunDiagnostic', {
        serial: serial,
        suite_id: suite_id
    }, $container, {
        $button: $button,
        serial: serial,
        display_success: true,
        success_msg: 'Diagnostic initié avec succès',
        display_processing: true,
        processing_padding: 20,
        processing_msg: 'Initialisation du diagnostic en cours'
    });
}

function gsx_refeshDiagnosticStatus($button, serial) {
    if ($.isOk($button) && $button.hasClass('disabled')) {
        return;
    }

    GsxAjax('gsxRefreshDiagnosticStatus', {
        serial: serial
    }, $('#currentDiagnosticStatus'), {
        $button: $button,
        serial: serial,
        append_html: true,
        display_success: false,
        display_processing: true,
        processing_padding: 0,
        processing_msg: 'Chargement en cours'
    });
}

function gsx_loadDiagnosticsDetails($button, serial) {
    if ($.isOk($button) && $button.hasClass('disabled')) {
        return;
    }

    GsxAjax('gsxLoadDiagnosticsDetails', {
        serial: serial
    }, $('#diagnosticsDetails'), {
        $button: $button,
        serial: serial,
        append_html: true,
        display_success: false,
        display_processing: true,
        processing_padding: 20,
        processing_msg: 'Chargement en cours'
    });
}

function gsx_loadEligibilityDetails($button, id_sav, $resultContainer) {
    if ($.isOk($button) && $button.hasClass('disabled')) {
        return;
    }

    if (!$.isOk($resultContainer)) {
        $resultContainer = $('#gsxEligibilityDetails');
    }

    var data = {
        id_sav: id_sav
    };

    var $container = $button.findParentByClass('testEligibilityFormContent');
    if ($.isOk($container)) {
        var repairType = $container.find('[name="eligibilityRepairType"]').val();
        if (repairType) {
            data['repairType'] = repairType;
        }
    }

    GsxAjax('gsxLoadRepairEligibilityDetails', data, $resultContainer, {
        $button: $button,
        id_sav: id_sav,
        append_html: true,
        display_success: false,
        display_processing: true,
        processing_padding: 20,
        processing_msg: 'Chargement en cours'
    });
}

function gsx_loadUpdatePartKgbForm($button, id_sav, id_repair, part_number, form_values) {
    loadModalForm($button, {
        module: 'bimpapple',
        object_name: 'GSX_Repair',
        id_object: id_repair,
        form_name: 'part_update',
        param_values: form_values
    }, 'Mise à jour du numéro de série', function () {
        bimpModal.$footer.find('.save_object_button.modal_' + bimpModal.idx).attr('onclick', 'gsx_updatePartKgb($(this), ' + id_sav + ',' + id_repair + ', \'' + part_number + '\')');
    });
}

function gsx_updatePartKgb($button, id_sav, id_repair, part_number) {
    var modal_idx = parseInt($button.data('modal_idx'));

    if (modal_idx) {
        var $form = bimpModal.$contents.find('#modal_content_' + modal_idx);
        if ($.isOk($form)) {
            var kgb_number = $form.find('[name="kgb_number"]').val();
            var kbb_number = $form.find('[name="kbb_number"]').val();
            var sequence_number = $form.find('[name="sequence_number"]').val();

            if (!kgb_number) {
                bimp_msg('Veuillez saisir le nouveau numéro de série', 'warning', null, true);
                return;
            }

            GsxAjax('gsxRepairAction', {
                action: 'updatePartNumber',
                id_repair: id_repair,
                part_number: part_number,
                kgb_number: kgb_number,
                kbb_number: kbb_number,
                sequence_number: sequence_number
            }, $form.find('.ajaxResultContainer'), {
                $button: $button,
                id_sav: id_sav,
                modal_idx: modal_idx,
                display_success_in_popup_only: true,
                display_processing: true,
                processing_padding: 20,
                success: function (result, bimpAjax) {
                    bimpModal.removeContent(bimpAjax.modal_idx, true, false);
                    reloadRepairsViews(bimpAjax.id_sav);
                }
            });
        }
    }
}

// GSX V1 / V2:

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
                for (var i in kw) {
                    var str = null;
                    var regex = null;
                    switch (kw[i].type) {
                        case 'eeeCode':
                            regex = new RegExp('^(.*)' + kw[i].txt + '(.*)$', 'i');
                            str = $(this).data('eee_code');
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

    var params = {
        display_success: false,
        display_errors_in_popup_only: true,
        append_html: true,
        display_processing: true,
        processing_padding: 30,
        processing_msg: 'Chargement en cours',
        success: function (result, bimpAjax) {
            $('body').trigger($.Event('objectChange', {
                module: 'bimpsupport',
                object_name: 'BS_ApplePart',
                id_object: 0
            }));
        }
    };

    if (use_gsx_v2) {
        GsxAjax('gsxLoadSavRepairs', {
            id_sav: id_sav
        }, $('#sav_repairs').children('.panel-body'), params);
    } else {
        BimpAjax('loadRepairs', {
            id_sav: id_sav
        }, $('#sav_repairs').children('.panel-body'), params);
    }
}

// GSX V1: 

function loadRepairForm($button, id_sav, serial) {
    if (use_gsx_v2) {
        return;
    }

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
    if (use_gsx_v2) {
        return;
    }

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
    if (use_gsx_v2) {
        return;
    }

    var data = {
        serial: serial,
        id_sav: id_sav,
        sufixe: sufixe
    };

    var params = {
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
    };

    if (!use_gsx_v2) {
        BimpAjax('loadPartsList', data, $('#partsListContainer' + sufixe), params);
    }
}

function addPartToCart($button, id_sav) {
    if (use_gsx_v2) {
        return;
    }
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
    if (use_gsx_v2) {
        return;
    }

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
    if (use_gsx_v2) {
        return;
    }

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
    if (use_gsx_v2) {
        return;
    }

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