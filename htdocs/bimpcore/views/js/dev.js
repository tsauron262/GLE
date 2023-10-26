function BimpModuleConf() {
    var bmc = this;
    this.$container = null;
    this.$entity_select = null;
    this.$module_params = null;
    this.$module_select = null;
    this.$modules_search = null;
    this.$modules_search_result = null;
    this.lock_params_load = false;

    this.init = function () {
        bmc.$container = $('#moduleConf');

        if (bmc.$container.length) {
            if (!parseInt(bmc.$container.data('events_init'))) {
                bmc.$entity_select = bmc.$container.find('select[name="entity_select"]');
                bmc.$module_select = bmc.$container.find('select[name="module_select"]');
                bmc.$modules_search = bmc.$container.find('input[name="all_modules_search"]');
                bmc.$module_params = bmc.$container.find('div.module_params_container');
                bmc.$modules_search_result = bmc.$container.find('div.all_modules_search_result_container');

                if (bmc.$modules_search.length) {
                    bmc.$modules_search.keydown(function (e) {
                        if (e.key === 'Enter') {
                            $('#all_modules_search_submit').click();
                        }
                    });
                }

                if (bmc.$module_params.length) {
                    if (bmc.$entity_select.length) {
                        bmc.$entity_select.change(function () {
                            BimpModuleConf.loadModuleConfForm();
                        });
                    }

                    if (bmc.$module_select.length) {
                        bmc.$module_select.change(function () {
                            BimpModuleConf.loadModuleConfForm($(this).val());
                        });
                    }
                }

                bmc.onModuleConfFormLoaded();
                bmc.$container.data('events_init', 1);
            }
        }
    };

    this.loadModuleConfForm = function (module_name) {
        if (bmc.lock_params_load) {
            return;
        }

        if (typeof (module_name) === 'undefined') {
            if (bmc.$module_select.length) {
                module_name = bmc.$module_select.val();
            }
        }

        if (!module_name) {
            return;
        }

        var entity_type = 'all';

        if (bmc.$entity_select.length) {
            entity_type = bmc.$entity_select.val();
        }

        BimpAjax('loadModuleConfForm', {
            module_name: module_name,
            entity_type: entity_type
        }, bmc.$module_params, {
            display_success: false,
            display_processing: true,
            processing_msg: 'Chargement',
            append_html: true,
            success: function (result, bimpAjax) {
                BimpModuleConf.onModuleConfFormLoaded();
            }
        });
    };

    this.reloadModuleConfForm = function () {
        if (bmc.$module_select.length) {
            bmc.loadModuleConfForm(bmc.$module_select.val());
        }
    };

    this.onModuleConfFormLoaded = function () {
        var $form = bmc.$container.find('.module_params_form');

        if ($form.length) {
            $form.each(function () {
                $(this).find('table.module_conf_params').each(function () {
                    var $curForm = $(this);
                    if (!parseInt($curForm.data('module_params_form_events_init'))) {
                        $curForm.find('input.cur_module_search_input').keyup(function (e) {
                            bmc.searchInCurModule();
                        });
                        $curForm.find('.module_conf_input').each(function () {
                            $(this).change(function () {
                                BimpModuleConf.onParamChange($(this));
                            });
                        });
                        $form.data('module_params_form_events_init', 1);
                    }

                    bmc.checkAllSubParamsDisplay(bmc.$module_select.val(), $curForm);
                });
            });
        }
    };

    this.onParamChange = function ($input) {
        var $form = bmc.$container.find('.module_params_form');

        if (!$.isOk($form)) {
            bimp_msg('Une erreur est survenue - impossible d\'enregistrer le paramètre');
            console.error('module_conf.js - BimpModuleConf.onParamChange() - $form absent ou invalide');
            return;
        }


        var module_name = $form.data('module_name');

        var $table = $input.findParentByClass('module_conf_params');
        if ($.isOk($table)) {
            module_name = $table.data('module');
        }

        var param_name = $input.attr('name');
        var value = $input.val();
        var id_entity = parseInt($input.data('id_entity'));

        if (typeof (id_entity) === 'undefined' || isNaN(id_entity)) {
            bimp_msg('Entité absente ou invalide - impossible d\'enregistrer le paramètre');
            return;
        }

        if (typeof (module_name) === 'undefined' || !module_name) {
            bimp_msg('Nom du module absent - impossible d\'enregistrer le paramètre');
            return;
        }

        // Save: 
        bmc.saveParam(module_name, param_name, value, id_entity);
    };

    this.checkAllSubParamsDisplay = function (module, $container) {
        $container.find('tr.sub_params_row').each(function () {
            var param_name = $(this).data('parent_param');

            if (param_name) {
                bmc.checkSubParamsDisplay(module, param_name);
            }
        });
    };

    this.checkSubParamsDisplay = function (module, param_name) {
        var $form = bmc.$container.find('.module_params_form');

        if ($form.length) {
            var show_container = false;
            $form.find('tr.sub_params_row[data-parent_param="' + param_name + '"]').each(function () {
                var $tr = $(this);
                var if_values = $(this).data('if');

                if (typeof (if_values) !== 'undefined') {
                    if (typeof (if_values) === 'string') {
                        if_values = if_values.split(',');
                    } else {
                        if_values = [if_values];
                    }

                    var $inputs = $form.find('input.module_conf_input[data-module="' + module + '"][name="' + param_name + '"]');
                    if ($inputs.length) {
                        $inputs.each(function () {
                            var check = false;
                            var id_entity = parseInt($(this).data('id_entity'));
                            if (typeof (id_entity) !== 'undefined' && !isNaN(id_entity)) {
                                var value = $(this).val();
                                for (var i in if_values) {
                                    if (!check) {
                                        if (value == if_values[i]) {
                                            check = true;
                                            $tr.show().children('td').children('.sub_params_container').stop().slideDown(250);
                                        }
                                    }
                                }

                                if (check) {
                                    show_container = true;
                                    $tr.find('tr.entity_param_row[data-id_entity="' + id_entity + '"]').stop().slideDown(250);
                                } else {
                                    $tr.find('tr.entity_param_row[data-id_entity="' + id_entity + '"]').stop().slideUp(250);
                                }
                            }
                        });
                    }

                    if (!show_container) {
                        $tr.children('td').children('.sub_params_container').stop().slideUp(250, function () {
                            $tr.hide();
                        });
                    }
                }
            });
        }
    };

    this.saveParam = function (module, param_name, value, id_entity) {
        if (typeof (id_entity) === 'undefined') {
            id_entity = 0;
        }

        var entity_type = 'all';
        if (bmc.$entity_select.length) {
            entity_type = bmc.$entity_select.val();
        }

        BimpAjax('saveModuleConfParam', {
            module: module,
            param_name: param_name,
            value: value,
            id_entity: id_entity,
            entity_type: entity_type
        }, null, {
            module: module,
            param_name: param_name,
            display_errors_in_popup_only: true,
            success_msg: 'Paramètre enregistré',
            error_msg: 'Echec enregistrement du paramètre - l\'opération n\'a pas aboutie',
            success: function (result, BimpAjax) {
                if (result.param_row) {
                    bmc.setParamHtml(BimpAjax.module, BimpAjax.param_name, result.param_row, result.has_errors);
                }

                bmc.checkSubParamsDisplay(BimpAjax.module, BimpAjax.param_name);
            }
        });
    };

    this.removeParam = function (module, param_name, id_entity) {
        if (typeof (id_entity) === 'undefined') {
            id_entity = 0;
        }

        var entity_type = 'all';
        if (bmc.$entity_select.length) {
            entity_type = bmc.$entity_select.val();
        }

        BimpAjax('removeModuleConfParam', {
            module: module,
            param_name: param_name,
            id_entity: id_entity,
            entity_type: entity_type
        }, null, {
            module: module,
            param_name: param_name,
            display_errors_in_popup_only: true,
            success_msg: 'Paramètre supprimé',
            error_msg: 'Echec suppression du paramètre - l\'opération n\'a pas aboutie',
            success: function (result, bimpAjax) {
                if (result.param_row) {
                    bmc.setParamHtml(bimpAjax.module, bimpAjax.param_name, result.param_row, result.has_errors);
                }

                bmc.checkSubParamsDisplay(bimpAjax.module, bimpAjax.param_name);
            }
        });
    };

    this.setParamHtml = function (module, param_name, html, has_errors) {
        var $tr = bmc.$container.find('tr.param_row[data-param_name="' + param_name + '"][data-module="' + module + '"]');

        if ($tr.length) {
            if (has_errors) {
                $tr.addClass('has_errors');
            } else {
                $tr.removeClass('has_errors');
            }

            $tr.html(html);

            setCommonEvents($tr);
            setInputsEvents($tr);

            $tr.find('.module_conf_input').each(function () {
                $(this).change(function () {
                    bmc.onParamChange($(this));
                });
            });
        }
    };

    this.searchInCurModule = function () {
        var $form = bmc.$container.find('.module_params_form');

        if ($form.length) {
            var search = '';
            var $input = $form.find('input.cur_module_search_input');

            if ($input.length) {
                search = $input.val();
            }

            if (search && !/[a-zA-Z0-9\-\_ ]+$/.test(search)) {
                bimp_msg('Caractères interdits. Merci de n\'utiliser que des caractères aplha-numériques ainsi que "-" ou "_"', 'danger', null, true);
                search = '';
            }

            if (search) {
                search = search.replace(/[-_]/g, ' ');
                var search_words = search.split(' ');
                var regexs = [];

                for (var i in search_words) {
                    regexs.push(new RegExp('^(.*)' + search_words[i] + '(.*)$', 'i'));
                }
                var counts = {};

                $form.find('td.param_label').each(function () {
                    // Tous les mots de la recherche doivent être trouvés pour afficher le param
                    // (soit dans le label soit dans le nom)

                    var $tr = $(this).findParentByClass('param_row');
                    if ($.isOk($tr)) {
                        var show = false;
                        var check = true;

                        var $span = $(this).find('span.param_label');
                        if ($span.length) {
                            var label = $span.text();

                            if (label) {
                                for (var i in regexs) {
                                    if (check) {
                                        if (!regexs[i].test(label)) {
                                            check = false;
                                        }
                                    }
                                }

                                if (check) {
                                    show = true;
                                }
                            }
                        }

                        if (!show) {
                            $span = $(this).find('span.param_name');

                            if ($span.length) {
                                var name = $span.text();

                                if (name) {
                                    check = true;
                                    for (var i in regexs) {
                                        if (check) {
                                            if (!regexs[i].test(name)) {
                                                check = false;
                                            }
                                        }
                                    }

                                    if (check) {
                                        show = true;
                                    }
                                }
                            }
                        }

                        if (show) {
                            $tr.removeClass('hidden');
                            var $div = $tr.findParentByClass('tab-pane');
                            if ($.isOk($div)) {
                                var tab_id = $div.attr('id');

                                if (tab_id) {
                                    if (typeof (counts[tab_id]) === 'undefined') {
                                        counts[tab_id] = 0;
                                    }

                                    counts[tab_id]++;
                                }
                            }
                        } else {
                            $tr.addClass('hidden');
                        }
                    }
                });

                $form.find('li.param_category_title').each(function () {
                    var tab_id = $(this).data('navtab_id');
                    if (typeof (counts[tab_id]) !== 'undefined' && counts[tab_id] > 0) {
                        if ($(this).find('span.search_count').length) {
                            $(this).find('span.search_count').text(counts[tab_id]);
                        } else {
                            $(this).find('a').append('<span class="badge badge-info search_count">' + counts[tab_id] + '</span>');
                        }
                    } else {
                        $(this).find('span.search_count').remove();
                    }
                });
            } else {
                $form.find('li.param_category_title').find('span.search_count').remove();
                $form.find('tr.param_row.hidden').each(function () {
                    $(this).removeClass('hidden');
                });
            }
        }
    };

    this.eraseCurModuleSearch = function () {
        var $form = bmc.$container.find('.module_params_form');

        if ($form.length) {
            var $input = $form.find('input.cur_module_search_input');
            if ($input.length) {
                $input.val('');
                bmc.searchInCurModule();
            }
        }
    };

    this.searchInAllModules = function () {
        if ($.isOk(bmc.$modules_search)) {
            var search = bmc.$modules_search.val();

            if (!search) {
                bimp_msg('Veuillez saisir un terme de recherche', 'warning', null, true);
                return;
            }

            var entity_type = 'all';

            if (bmc.$entity_select.length) {
                entity_type = bmc.$entity_select.val();
            }

            bmc.lock_params_load = true;

            bmc.$module_params.stop().slideUp(250, function () {
                $(this).html('');
            });

            bmc.$modules_search_result.stop().slideDown(250);

            BimpAjax('searchModulesConfParams', {
                search: search,
                entity_type: entity_type
            }, bmc.$modules_search_result.children('.all_modules_search_result_content'), {
                display_success: false,
                display_processing: true,
                display_warnings_in_popup_only: false,
                append_html: true,
                processing_msg: 'Recherche en cours',
                processing_padding: 100,
                success: function (result, bimpAjax) {
                    bmc.onModuleConfFormLoaded();
                }
            });
        }
    };

    this.closeModulesSearchResult = function () {
        bmc.lock_params_load = false;
        bmc.$modules_search_result.slideUp(250, function () {
            $(this).children('.all_modules_search_result_content').html('');
        });
        bmc.$module_params.stop().slideDown(250, function () {
            bmc.loadModuleConfForm(bmc.$module_select.val());
        });
    };
}

function BimpYMLManager() {
    var bym = this;
    this.$container = null;
    this.$content = null;

    // Chargement fichier: 
    this.init = function () {
        bym.$container = $('#bimpYmlManager');
        if (bym.$container.length) {
            bym.$content = bym.$container.children('.fileYmlManagerContent');

            if (!parseInt(bym.$container.data('events_init'))) {
                bym.$container.find('select[name="yml_type_select"]').add('select[name="yml_module_select"]').change(function () {
                    bym.reloadFilesSelect();
                });

                bym.setFileSelectEvents();
                bym.$container.data('events_init', 1);
            }
        }
    };
    this.reloadFilesSelect = function () {
        var type = bym.$container.find('select[name="yml_type_select"]').val();
        var module = bym.$container.find('select[name="yml_module_select"]').val();

        BimpAjax('loadYmlFilesSelect', {
            type: type,
            module: module
        }, bym.$container.find('.fileSelectContainer'), {
            display_success: false,
            display_processing: true,
            display_warnings_in_popup_only: false,
            append_html: true,
            processing_msg: '',
            processing_padding: 0,
            success: function (result, bimpAjax) {
                bym.setFileSelectEvents();
            }
        });
    };
    this.setFileSelectEvents = function () {
        var $select = bym.$container.find('select[name="yml_file_select"]');

        if ($select.length) {
            if (!parseInt($select.data('yml_events_init'))) {
                $select.data('yml_events_init', 1);
                $select.change(function () {
                    bym.loadYmlFileManagerContent();
                });
            }
        }
    };

    this.loadYmlFileManagerContent = function () {
        var $select = bym.$container.find('select[name="yml_file_select"]');

        if ($select.length) {
            var file = $select.val();
            if (file) {
                BimpAjax('loadYmlFileManagerContent', {
                    file_data: file
                }, bym.$content, {
                    display_success: false,
                    display_processing: true,
                    display_warnings_in_popup_only: false,
                    append_html: true,
                    processing_msg: 'Chargement',
                    success: function (result, bimpAjax) {
                        bym.setYmlAnalyserEvents();
                    }
                });
            } else {
                bym.$content.slideUp(250, function () {
                    $(this).html('');
                });
            }
        }
    };

    // Gestion analyseur:
    this.setYmlAnalyserEvents = function () {
        var $container = bym.$container.find('.yml_analyser_params_container');

        if ($container.length) {
            if (!parseInt($container.data('yml_analyser_events_init'))) {
                $container.data('yml_analyser_events_init', 1);

                $container.find('.yml_analyser_section').click(function () {
                    bym.showYmlAnalyserSection($(this).data('section'));
                });
            }
        }
    };

    this.toggleYmlAnalyserFileDisplay = function () {

    };

    this.showYmlAnalyserSection = function (section) {
        var $active_section = bym.$container.find('.yml_analyser_section.active');

        if ($active_section.length) {
            var active_section = $active_section.data('section');
            if (active_section === section) {
                return;
            }
            $active_section.removeClass('active');
            bym.$container.find('.yml_analyser_section[data-section="' + section + '"]').addClass('active');

            bym.$container.find('.yml_analyser_section_' + active_section + '_params').stop().fadeOut(250, function () {
                bym.$container.find('.yml_analyser_section_' + section + '_params').stop().fadeIn(250);
            });
        } else {
            bym.$container.find('.yml_analyser_section_params').hide();
            bym.$container.find('.yml_analyser_section[data-section="' + section + '"]').addClass('active');
            bym.$container.find('.yml_analyser_section_' + section + '_params').stop().fadeIn(250);
        }
    };
}

var BimpModuleConf = new BimpModuleConf();
var BimpYMLManager = new BimpYMLManager();

$(document).ready(function () {
    BimpModuleConf.init();
    BimpYMLManager.init();
});