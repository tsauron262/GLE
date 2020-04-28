var bds_process_url = dol_url_root + '/bimpdatasync/index.php?fc=process';
var bds_current_operation = null;
var bds_operations = [];

function BDS_OperationStep(name, data, $container) {
    var step = this;

    this.name = name;
    this.label = data.label;
    this.status = 'waiting';
    this.is_done = 0;
    this.nbReset = 0;

    this.on_error = 'stop';
    this.on_cancel = 'stop';

    this.$container = $container;

    if ($.isOk(this.$container)) {
        this.$table = $container.find('#operationStepsTable');
    } else {
        this.$table = null;
    }

    if ($.isOk(this.$table)) {
        this.$row = this.$table.find('#step_' + name);
    } else {
        this.$row = null;
    }

    if (typeof (data.on_error) !== 'undefined') {
        this.on_error = data.on_error;
    }
    if (typeof (data.on_cancel) !== 'undefined') {
        this.on_cancel = data.on_cancel;
    }

    // Gestion de la liste des éléments:

    this.elements = {
        'list': [],
        'nbTotal': 0,
        'nbDone': 0,
        'nbPerIteration': 0
    };

    this.setElements = function (elements) {
        step.elements.list = elements;
        step.elements.nbTotal = elements.length;
        step.elements.nbDone = 0;
    };

    this.addEelements = function (elements) {
        for (var i in elements) {
            step.elements.list.push(elements[i]);
        }
        step.elements.nbTotal += elements.length;
    };

    this.addElement = function (element) {
        step.elements.list.push(element);
        step.elements.nbTotal++;
    };

    this.unsetElements = function (elements) {
        var currents = step.elements.list;
        step.elements.list = [];

        for (var i in currents) {
            var check = true;
            for (var j in elements) {
                if (currents[i] === elements[j]) {
                    check = false;
                    break;
                }
            }
            if (check) {
                step.elements.list.push(currents[i]);
            }
        }

        step.elements.nbTotal = step.elements.list.length;
    };

    this.unsetElement = function (element) {
        for (var i in step.elements.list) {
            if (step.elements.list[i] === element) {
                step.elements.list.splice(i, 1);
                step.elements.nbTotal--;
                break;
            }
        }
    };

    this.setNbElementsPerIteration = function (nbElementsPerIteration) {
        step.elements.nbPerIteration = nbElementsPerIteration;
    };

    this.getIterationElements = function () {
        var elements = [];
        if (!step.elements.list.length) {
            return elements;
        }

        var n = 0;
        for (var i = step.elements.nbDone; i < step.elements.nbTotal; i++) {
            if (step.elements.nbPerIteration > 0) {
                if (n >= step.elements.nbPerIteration) {
                    break;
                }
            }
            elements.push(step.elements.list[i]);
            n++;
        }

        var nbIterations = 1;
        var nbIterationsDone = 0;
        if (step.elements.nbPerIteration > 0) {
            nbIterationsDone = Math.ceil(step.elements.nbDone / step.elements.nbPerIteration);
            nbIterations = Math.ceil(step.elements.nbTotal / step.elements.nbPerIteration);
        } else {
            nbIterationsDone = Math.ceil(step.elements.nbDone / step.elements.nbTotal);
        }

        var msg = 'traitement du paquet ' + (nbIterationsDone + 1) + ' sur ';
        msg += nbIterations + ' en cours...';
        step.$row.find('.stepExtraInfo').text(msg);

        return elements;
    };

    // Gestion du statut: 

    this.start = function () {
        step.is_done = 0;
        step.status = 'processing';
        step.setRowVars('Traitement en cours...');
    };

    this.onAjaxError = function () {
        step.status = 'error';
        step.setRowVars('Une erreur est survenue');
    };

    this.onAjaxSuccess = function () {
        if (step.elements.list.length) {
            if (step.elements.nbPerIteration > 0) {
                step.elements.nbDone += step.elements.nbPerIteration;
            } else {
                step.elements.nbDone += step.elements.nbTotal;
            }
            if (step.elements.nbDone > step.elements.nbTotal) {
                step.elements.nbDone = step.elements.nbTotal;
            }
        } else {
            step.elements.nbDone++;
        }

        if (step.elements.nbDone < step.elements.nbTotal) {
            step.setRowVars('');
            return;
        }

        step.status = 'success';
        step.is_done = 1;
        step.setRowVars('');
    };

    this.hold = function (msg) {
        if (!step.is_done && step.status === 'processing') {
            step.status = 'hold';
            if (typeof (msg) !== 'undefined') {
                step.setRowVars(msg);
            } else {
                step.setRowVars('Opération suspendue');
            }
        }
    };

    this.resume = function () {
        if (!step.is_done) {
            step.status = 'processing';
            step.setRowVars('Taitement en cours');
        }
    };

    this.skip = function () {
        if (!step.is_done) {
            step.status = 'skipped';
            step.setRowVars('Opération ignorée');
        }
    };

    this.cancel = function () {
        if (!step.is_done) {
            step.status = 'cancelled';
            step.setRowVars('Opération annulée');
        }
    };

    this.reset = function () {
        step.is_done = 0;
        step.elements.nbDone = 0;
        step.nbReset++;
    };

    // Traitements HTML:

    this.append = function ($body) {
        if (!$body.length) {
            var msg = 'Une erreur est survenue. Ajout de l\'étape ';
            msg += '"' + step.label + '" impossible (conteneur absent)';
            bimp_display_msg(msg, $('#operationStepAjaxResult'), 'danger');
            return false;
        }

        var html = '<tr id="step_' + step.name + '"';
        html += ' class="operationStepRow ' + step.status + '"';
        html += '>';

        html += '<td class="stepLabel">' + step.label + '</td>';

        html += '<td class="stepCount">';
        if (step.elements.nbTotal > 0) {
            html += '<span class="nbStepElementsDone">';
            html += step.elements.nbDone;
            html += '</span>';
            html += '&nbsp;/&nbsp;'
            html += '<span class="nbStepElementsTotal">';
            html += step.elements.nbTotal;
            html += '</span>';
        }
        html += '</td>';

        html += '<td class="stepProgession">';
        html += '<div class="progessionBar">';
        html += '<div class="progressionDone" style="width: 0%"></div>';
        html += '</div>';
        html += '<div class="stepExtraInfo"></div>';
        html += '</td>';

        html += '<td class="stepStatus"><span></span></td>';

        html += '</tr>';

        $body.append(html);

        step.$row = $body.find('#step_' + step.name);

        if (!step.$row.length) {
            var msg = 'Echec de l\'insertion de l\'étape "' + step.label + '"';
            bimp_display_msg(msg, $('#operationStepAjaxResult'), 'danger');
            return false;
        }

        return true;
    };

    this.setRowVars = function (msg) {
        if (!step.$row || !step.$row.length) {
            return;
        }

        step.$row.attr('class', 'operationStepRow ' + step.status);


        if (step.elements.list.length) {
            step.$row.find('.nbStepElementsDone').text(step.elements.nbDone);
            step.$row.find('.nbStepElementsTotal').text(step.elements.nbTotal);

            if (step.elements.nbTotal > 0) {
                var progression = Math.floor((step.elements.nbDone / step.elements.nbTotal) * 100);
                step.$row.find('.progressionDone').css('width', progression + '%');
            }
        } else {
            if (step.is_done) {
                step.$row.find('.nbStepElementsDone').text(1);
                step.$row.find('.nbStepElementsTotal').text(1);
                step.$row.find('.progressionDone').css('width', '100%');
            } else {
                step.$row.find('.nbStepElementsDone').text(0);
                step.$row.find('.nbStepElementsTotal').text(1);
                step.$row.find('.progressionDone').css('width', '0%');
            }
        }

        if (typeof (msg) !== 'undefined') {
            step.$row.find('.stepExtraInfo').text(msg);
        }
    };

    if (typeof (data.elements) !== 'undefined') {
        this.setElements(data.elements);
        if (typeof (data.nbElementsPerIteration) !== 'undefined') {
            this.setNbElementsPerIteration(data.nbElementsPerIteration);
        } else {
            this.setNbElementsPerIteration(0);
        }
    }

    if (!$.isOk(this.$row)) {
        if ($.isOk(this.$table)) {
            this.append($('#operationStepsTable').find('tbody'));
        } else {
            step.status = 'error';
            step.setRowVars('Une erreur est survenue');
        }
    }
}

function BDS_ProcessOperation(data, options) {
    var operation = this;

    this.id_process = data.id_process;
    this.id_operation = data.id_operation;
    this.options = options;
    this.retry_delay = 0;

    this.$container = $('#process_' + data.id_process + '_operation_' + data.id_operation + '_progress_container');
    this.$notification = this.$container.find('.operationStepAjaxResult');

    if ($.isOk(this.$notification)) {
        this.$notification.show();
    }

    this.steps = [];
    this.status = 'init';

    this.curStep = {
        'step': 0,
        'index': 0,
        'ajax_processing': 0
    };

    this.report = {
        'id': 0,
        'use': 0,
        'is_active': 0,
        'is_loading': 0,
        '$container': null
    };

    if (data.use_report && data.id_report) {
        this.report.use = 1;
        this.report.id = data.id_report;
        this.report.is_active = 1;

        if ($.isOk(this.$container)) {
            this.report.$container = this.$container.find('#processReportContainer');
        }
    }

    this.buttons = {
        '$cancel': $('#bds_cancelOperationButton'),
        '$hold': $('#bds_holdOperationButton'),
        '$resume': $('#bds_resumeOperationButton'),
        '$retry': $('#bds_retryOperationStepButton'),
        '$back': $('#bds_backButton'),
        '$reportOn': $('#bds_enableReportButton'),
        '$reportOff': $('#bds_disableReportButton')
    };

    this.addSteps = function (steps) {
        if (steps) {
            for (var step_name in steps) {
                var step = new BDS_OperationStep(step_name, steps[step_name], operation.$container.find('#stepsProgressionContainer'));
                operation.steps.push(step);
            }
        }
    };

    if (typeof (data.steps) !== 'undefined') {
        this.addSteps(data.steps);
    }

    this.start = function () {
        if (!operation.steps.length) {
            var html = '<div class="alert alert-warning">';
            html += 'Aucune opération à traiter';
            html += '</div>';
            operation.$notification.html(html).show();
            operation.status = 'success';
            operation.hideButtons();
            operation.buttons.$back.show();
        } else {
            operation.status = 'processing';
            operation.retry_delay = 0;
            operation.curStep.step = operation.steps[0];
            operation.curStep.index = 0;
            operation.curStep.step.start();
            operation.hideButtons();
            operation.buttons.$cancel.show();
            operation.buttons.$hold.show();
            operation.processStep();
        }
    };

    this.processStep = function () {
        if (operation.curStep.ajax_processing) {
            return;
        }

        if (operation.status === 'hold') {
            var html = '<div class="alert alert-warning">Opération suspendue</div>';
            operation.$notification.html(html).show();
            operation.curStep.step.hold();
        }

        if (operation.status === 'cancelled') {
            var html = '<div class="alert alert-danger">Opération annulée</div>';
            operation.$notification.html(html).show();
            operation.curStep.step.cancel();
        }

        if ((operation.status !== 'processing') || !operation.curStep.step) {
            return;
        }

        if (operation.retry_delay > 0) {
            var html = '<div class="alert alert-info">';
            html += 'Nouvelle tentative dans ' + operation.retry_delay + ' secondes';
            html += '</div>';
            operation.$notification.html(html).show();
            if (operation.retry_delay === 1) {
                operation.resume();
            } else {
                setTimeout(function () {
                    operation.processStep();
                }, 1000);
                operation.retry_delay--;
            }
            return;
        }

        var nextStep = false;

        if (operation.curStep.step.status === 'hold') {
            operation.hold();
            return;
        }

        if ((operation.curStep.step.is_done) ||
                (operation.curStep.step.status === 'skipped')) {
            nextStep = true;
        }

        if (operation.curStep.step.status === 'cancelled') {
            switch (operation.curStep.step.on_cancel) {
                case 'stop':
                    operation.status = 'stoped';
                    var html = '<div class="alert alert-danger">Opération annulée</div>';
                    operation.$notification.append(html).show();
                    operation.hideButtons();
                    operation.buttons.$back.show();
                    return;

                case 'continue':
                    nextStep = true;
                    break;

                case 'retry':
                    operation.status = 'stoped';
                    var html = '<div class="alert alert-danger">Opération annulée</div>';
                    operation.$notification.append(html).show();
                    operation.hideButtons();
                    operation.buttons.$retry.show();
                    operation.buttons.$back.show();
                    return;
            }
        }

        if (operation.curStep.step.status === 'error') {
            switch (operation.curStep.step.on_error) {
                case 'stop':
                    operation.status = 'stoped';
                    var html = '<div class="alert alert-danger">Une erreur est survenue. Opération abandonnée</div>';
                    operation.$notification.append(html).show();
                    operation.hideButtons();
                    operation.buttons.$back.show();
                    return;

                case 'continue':
                    nextStep = true;
                    break;

                case 'retry':
                    operation.resume();
                    return;

                case 'hold':
                    operation.status = 'hold';
                    var html = '<div class="alert alert-danger">Une erreur est survenue. Opération suspendue</div>';
                    operation.$notification.append(html).show();
                    operation.hideButtons();
                    operation.buttons.$retry.show();
                    operation.buttons.$back.show();
                    return;
            }
        }

        if (nextStep) {
            operation.curStep.index++;
            if (typeof (operation.steps[operation.curStep.index]) !== 'undefined') {
                operation.curStep.step = operation.steps[operation.curStep.index];
                operation.curStep.step.start();
            } else {
                operation.curStep.step = 0;
            }
        }

        if ((typeof (operation.curStep.step) === 'undefined') || 
                !operation.curStep.step) {
            operation.setSuccess('Opération terminée');
            return;
        }

        var data = {
            'ajax': 1,
            'action': 'executeOperationStep',
            'id_process': operation.id_process,
            'id_operation': operation.id_operation,
            'step_name': operation.curStep.step.name,
            'use_report': operation.report.use,
            'id_report': operation.report.id,
            'options': operation.options,
            'step_options': {},
            'elements': {},
            'iteration': 1
        };

        if (typeof (operation.curStep.step.options) !== 'undefined') {
            data.step_options = operation.curStep.step.options;
        }

        if (operation.curStep.step.elements.list.length) {
            data.elements = operation.curStep.step.getIterationElements();
            var iterationsDone = 0;
            if (operation.curStep.step.elements.nbPerIteration > 0) {
                iterationsDone = Math.ceil(operation.curStep.step.elements.nbDone / operation.curStep.step.elements.nbPerIteration);
            } else {
                iterationsDone = Math.ceil(operation.curStep.step.elements.nbDone / operation.curStep.step.elements.nbTotal);
            }
            data['iteration'] = iterationsDone + 1;
        }

        operation.curStep.ajax_processing = 1;

        BimpAjax('bds_executeOperationStep', data, operation.$notification, {
            operation: operation,
            url: bds_process_url,
            display_success: false,
            success: function (result, bimpAjax) {
                bimpAjax.operation.curStep.ajax_processing = 0;
                bimpAjax.operation.refreshReport();
                bimpAjax.operation.$notification.show();

                if (typeof (result.step_result.debug_content) !== 'undefined') {
                    var $content = bimpAjax.operation.$container.find('#processDebugContent').children('.foldable_content').first();
                    if ($.isOk($content)) {
                        $content.append(result.step_result.debug_content);
                        setCommonEvents($content);
                    }
                }

                if (typeof (result.step_result.errors) !== 'undefined' && result.step_result.errors.length) {
                    var msg = '<ul>';
                    for (var e in result.step_result.errors) {
                        msg += '<li>' + result.step_result.errors[e] + '</li>';
                    }
                    msg += '</ul>';
                    bimp_msg(msg, 'danger', bimpAjax.operation.$notification);
                    bimpAjax.operation.$notification.show();
                    bimpAjax.operation.curStep.step.onAjaxError();
                } else {
                    if (typeof (result.step_result.retry) !== 'undefined') {
                        bimpAjax.operation.curStep.step.hold(result.step_result.retry.msg);
                        bimpAjax.operation.retry_delay = parseInt(result.step_result.retry.delay);
                    } else {
                        bimpAjax.operation.curStep.step.onAjaxSuccess();
                    }
                }
                if (typeof (result.step_result.new_steps) !== 'undefined') {
                    bimpAjax.operation.addSteps(result.step_result.new_steps);
                }

                bimpAjax.operation.processStep();
            },
            error: function (result, bimpAjax) {
                bimpAjax.operation.curStep.ajax_processing = 0;
                bimpAjax.operation.curStep.step.onAjaxError();
                bimpAjax.operation.processStep();
            }
        });
    };

    this.hold = function () {
        operation.status = 'hold';
        operation.hideButtons();
        operation.buttons.$resume.show();
        operation.buttons.$cancel.show();

        var msg = '';
        if (operation.curStep.ajax_processing) {
            msg = 'Opération en cours de suspension';
        } else {
            operation.curStep.step.hold();
            msg = 'Opération suspendue';
        }
        var html = '<div class="alert alert-warning">' + msg + '</div>';

        operation.$notification.html(html).show();
    };

    this.resume = function () {
        operation.retry_delay = 0;
        operation.status = 'processing';
        operation.hideButtons();
        operation.buttons.$cancel.show();
        operation.buttons.$hold.show();

        operation.$notification.html('');

        operation.curStep.step.resume();
        if (!operation.curStep.ajax_processing) {
            operation.processStep();
        }
    };

    this.cancel = function () {
        operation.status = 'cancelled';
        operation.hideButtons();
        operation.buttons.$back.show();
        var msg = '';
        if (operation.curStep.ajax_processing) {
            msg = 'Opération en cours d\'annulation';
        } else {
            operation.curStep.step.cancel();
            msg = 'Opération annulée';
        }
        var html = '<div class="alert alert-warning">' + msg + '</div>';
        operation.$notification.html(html).show();

    };

    this.retryOperationStep = function () {
        if (operation.curStep.step) {
//            operation.curStep.step.reset();
            operation.curStep.step.resume();
            operation.hideButtons();
            operation.buttons.$cancel.show();
            operation.buttons.$hold.show();
            operation.status = 'processing';
            operation.$notification.html('').show();
            operation.processStep();
        }
    };

    this.enableReport = function () {
        operation.buttons.$reportOn.hide();

        if ((!operation.report.use) || !operation.report.id) {
            var msg = 'Aucun rapport n\'est disponible pour ce type d\'opération';
            bimp_msg(msg, 'danger');
            return;
        }

        if ($.isOk(operation.report.$container)) {
            operation.report.$container.stop().slideDown(250, function () {
                $(this).attr('style', '');
            });
        } else {
            bimp_msg('Aucun rapport trouvé', 'danger');
        }

        operation.buttons.$reportOff.show();
        operation.report.is_active = true;
        operation.refreshReport();
    };

    this.disableReport = function () {
        operation.buttons.$reportOn.show();
        operation.buttons.$reportOff.hide();
        operation.report.is_active = false;
        if ($.isOk(operation.report.$container)) {
            operation.report.$container.slideUp(250);
        } else {
            bimp_msg('Aucun rapport trouvé', 'danger');
        }
    };

    this.refreshReport = function () {
        if (operation.report.use && operation.report.is_active) {
            if ($.isOk(operation.report.$container)) {
                operation.report.$container.find('.object_list_table').each(function () {
                    reloadObjectList($(this).attr('id'));
                });
            }
        }
    };

    this.displayReport = function () {
        operation.report.is_active = false;
    };

    this.setSuccess = function (msg) {
        operation.status = 'success';
        var html = '<div class="alert alert-success">';
        html += msg;
        html += '</div>';
        operation.$notification.append(html).show();
        operation.hideButtons();
        operation.buttons.$back.show();
    };

    this.setError = function (msg) {
        var html = '<div class="alert alert-danger">';
        html += msg;
        html += '</div>';
        operation.$notification.html(html).show();
        operation.hideButtons();
        operation.buttons.$back.show();
        operation.buttons.$retry.show();
    };

    this.hideButtons = function () {
        operation.buttons.$cancel.hide();
        operation.buttons.$hold.hide();
        operation.buttons.$resume.hide();
        operation.buttons.$retry.hide();
        operation.buttons.$back.hide();
    };

    this.start();
}

function bds_initProcessOperation($button, id_process, id_operation) {
    if ($button.hasClass('disabled')) {
        return;
    }

    if (!id_process) {
        bimp_msg('Opération impossible - ID du processus absent', 'danger');
        return;
    }

    if (!id_operation) {
        bimp_msg('Opération impossible - ID de l\'opération absent', 'danger');
        return;
    }

    var $processContainer = $('#process_' + id_process + '_operations');

    if (!$.isOk($processContainer)) {
        bimp_msg('Une erreur technique est survenue (conteneur absent)', 'danger');
        return;
    }

    var $resultContainer = $('#process_' + id_process + '_operation_' + id_operation + '_ajaxResultContainer');

    var $form = $('#process_' + id_process + '_operation_' + id_operation + '_form');
    if (!$form.length) {
        bimp_msg('Erreur - formulaire absent', 'danger');
        return;
    }

    var data = new FormData($form.get(0));

    $processContainer.find('.executeProcessOperationBtn').addClass('disabled');

    BimpAjax('bds_initProcessOperation', data, $resultContainer, {
        $processContainer: $processContainer,
        id_process: id_process,
        id_operation: id_operation,
        url: bds_process_url,
        display_success: false,
        display_processing: true,
        processing_msg: 'Initialisation de l\'opération en cours',
        processing_padding: 20,
        processData: false,
        contentType: false,
        success: function (result, bimpAjax) {
            if (typeof (result.result_html) !== 'undefined' && result.result_html) {
                bimpAjax.$resultContainer.slideUp(250, function () {
                    $(this).html(result.result_html).slideDown(250, function () {
                        $(this).attr('style', 'display: block;');
                    });
                });
                bimp_msg('Une erreur est survenue. Echec de l\'initialisation de l\'opération', 'danger', bimpAjax.$resultContainer);
                $processContainer.find('.executeProcessOperationBtn').removeClass('disabled');
            } else if (typeof (result.process_html) !== 'undefined' && result.process_html) {
                bimpAjax.$processContainer.slideUp(250, function () {
                    $(this).html(result.process_html);
                    $(this).slideDown(250, function () {
                        $(this).attr('style', '');
                        $('body').trigger($.Event('contentLoaded', {
                            $container: bimpAjax.$processContainer
                        }));

                        bds_operations[bimpAjax.id_operation] = new BDS_ProcessOperation(result.operation_data, result.operation_options);
                        bds_operations[bimpAjax.id_operation].start();
                    });
                });
            } else {
                bimp_msg('Une erreur est survenue. Echec de l\'initialisation de l\'opération', 'danger', bimpAjax.$resultContainer);
                $processContainer.find('.executeProcessOperationBtn').removeClass('disabled');
            }
        },
        error: function (result, bimpAjax) {
            $processContainer.find('.executeProcessOperationBtn').removeClass('disabled');
        }
    });
}

function endOperation() {
    $('#bds_cancelOperationButton').hide();
    $('#bds_holdOperationButton').hide();
    $('#bds_retryOperationStepButton').hide();
    $('#bds_resumeOperationButton').hide();
    $('#bds_backButton').show();
}