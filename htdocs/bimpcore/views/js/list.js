// Traitements Ajax:

var object_labels = {};
var bimp_is_checkboxes_toggling = false;

function getListData($list, params) {
	if (typeof (params) === 'undefined') {
		params = {};
	}

	var object_name = $list.data('object_name');
	var id_parent_object = parseInt($list.find('#' + object_name + '_id_parent').val());

	if ((typeof (id_parent_object) === 'undefined') || !id_parent_object) {
		id_parent_object = 0;
	}

	var list_id = $list.attr('id');
	var data = {};

	// Données de base:
	if (typeof (params['list_data']) === 'undefined' || params['list_data']) {
		data = {
			'list_name': $list.data('name'),
			'list_id': list_id,
			'module': $list.data('module'),
			'object_name': object_name,
			'id_parent': id_parent_object
		};

		var extra_data = $list.data('extra_data');

		if (typeof (extra_data) !== 'undefined') {
			data.extra_data = extra_data;
		}

		var id_config = $list.data('id_config');
		var type = $list.data('type');

		if (typeof (id_config) !== 'undefined' && typeof (type) !== 'undefined') {
			data['id_current_' + type + '_config'] = id_config;
		}
	}

	// Champs de recherche:
	if (typeof (params['search_filters']) === 'undefined' || params['search_filters']) {
		var $row = $list.find('#' + list_id + '_searchRow');
		data['search_fields'] = getListSearchFilters($row);
	}

	// Lignes sélectionnées:
	if (typeof (params['selected_rows']) === 'undefined' || params['selected_rows']) {
		data['selected_rows'] = [];
		$list.find('tbody.listRows').find('input.item_check:checked').each(function () {
			data['selected_rows'].push($(this).data('id_object'));
		});
	}

	// Lignes modifiées:
	if (typeof (params['new_values']) === 'undefined' || params['new_values']) {
		var $rows = $list.find('tbody.listRows').find('tr.modified');
		if ($rows.length) {
			data['new_values'] = {};
			$rows.each(function () {
				var id_object = $(this).data('id_object');
				$(this).find('.inputContainer').each(function () {
					var field_name = $(this).data('field_name');
					if (field_name) {
						var $input = $(this).find('[name="' + field_name + '"]');
						if ($input.length) {
							if ($input.hasClass('modified')) {
								if (typeof (data['new_values'][id_object]) === 'undefined') {
									data['new_values'][id_object] = {};
								}
								data['new_values'][id_object][field_name] = $input.val();
							}
						}
					}
				});
			});
		}
	}

	// Options de trie:
	if (typeof (params['sort']) === 'undefined' || params['sort']) {
		var sort_col = $list.find('input[name=param_sort_field]').val();
		var sort_way = $list.find('input[name=param_sort_way]').val();
		var sort_option = $list.find('input[name=param_sort_option]').val();
		if (sort_col) {
			data['param_sort_field'] = sort_col;
		}
		if (sort_way) {
			data['param_sort_way'] = sort_way;
		}
		if (sort_option) {
			data['param_sort_option'] = sort_option;
		}
	}

	// Pagination:
	if (typeof (params['pagination']) === 'undefined' || params['pagination']) {
		var n = $list.find('input[name=param_n]').val();
		var p = $list.find('input[name=param_p]').val();

		if (n) {
			data['param_n'] = n;
		}
		if (p) {
			data['param_p'] = p;
		}
	}

	// Jointures de base:
	if (typeof (params['list_joins']) === 'undefined' || params['list_joins']) {
		var joins = $list.find('input[name=param_joins]').val();
		if (joins) {
			data['param_joins'] = joins;
		}
	}

	// Filtres prédéfinis:
	if (typeof (params['list_filters']) === 'undefined' || params['list_filters']) {
		if ($list.find('input[name=param_list_filters]').length) {
			data['param_list_filters'] = $list.find('input[name=param_list_filters]').val();
		}
		if ($list.find('input[name=param_association_filters]').length) {
			data['param_association_filters'] = $list.find('input[name=param_association_filters]').val();
		}
	}

	// Panneau Filtres utilisateur:
	if (typeof (params['filters_panel']) === 'undefined' || params['filters_panel']) {
		var $listFilters = $list.find('.object_filters_panel');
		if ($listFilters.length) {
			if ($listFilters.data('list_identifier') === $list.attr('id')) {
				data['filters_panel_values'] = getAllListFieldsFilters($listFilters);

				// ID Config de filtres:
				var $input = $listFilters.find('select[name="id_filters_config_to_load"]');
				if ($input.length) {
					var id_filters_config = parseInt($input.val());
					if (id_filters_config && !isNaN(id_filters_config)) {
						data['id_current_filters_panel_config'] = id_filters_config;
					}
				}

				// ID Filtres enregistrés:
				$input = $listFilters.find('select[name="id_filters_to_load"]');
				if ($input.length) {
					var id_list_filters = parseInt($input.val());
					if (id_list_filters && !isNaN(id_list_filters)) {
						data['id_current_list_filters'] = id_list_filters;
					}
				}
			}
		}
	}

	return data;
}

function getListSearchFilters($row) {
	var filters = {};

	if ($row.length) {
		$row.find('.searchInputContainer').each(function () {
			var search_type = $(this).data('search_type');
			var field_name = $(this).data('field_name');
			if (field_name) {
				var field = field_name.replace(/^search_(.+)$/, '$1');
				var search_data = '';
				switch (search_type) {
					case 'time_range':
					case 'date_range':
					case 'datetime_range':
						search_data = {};
						var $from = $(this).find('[name=' + field_name + '_from]');
						var $to = $(this).find('[name=' + field_name + '_to]');
						var from = '';
						var to = '';

						if ($from.length) {
							from = $from.val();
						}
						if ($to.length) {
							to = $to.val();
						}

						if (from || to) {
							search_data['from'] = from;
							search_data['to'] = to;
						}
						break;

					case 'values_range':
						search_data = {};
						var $min = $(this).find('[name=' + field_name + '_min]');
						var $max = $(this).find('[name=' + field_name + '_max]');
						var min = '';
						var max = '';

						if ($min.length) {
							min = $min.val();
						}
						if ($max.length) {
							max = $max.val();
						}

						if (min || max) {
							search_data['min'] = min;
							search_data['max'] = max;
						}

						break;

					default:
						var $input = $(this).find('[name=' + field_name + ']');
						if ($input.length) {
							search_data = $input.val();
						}
				}

				if (search_data !== '') {
					filters[field] = search_data;
				}
			}
		});
	}

	return filters;
}

function reloadObjectList(list_id, callback, full_reload, id_config, modeAppend, temporisation = 0, refresh_idx = 0) {
	var $list = $('#' + list_id);

	if (!$list.length) {
		console.error('reloadObjectList(): identifiant de la liste invalide (' + list_id + ').');
		return;
	}

	if (typeof (full_reload) === 'undefined') {
		full_reload = false;
	}

	if (!refresh_idx) {
		refresh_idx = parseInt($list.data('refresh_idx'));
		if (isNaN(refresh_idx)) {
			refresh_idx = 0;
		}
		refresh_idx++;
		$list.data('refresh_idx', refresh_idx);
	} else {
		var cur_refresh_idx = parseInt($list.data('refresh_idx'));

		if (!isNaN(cur_refresh_idx) && cur_refresh_idx > refresh_idx) {
			return;
		}
	}

	if (temporisation > 0) {
		setTimeout(function () {
			reloadObjectList(list_id, callback, full_reload, id_config, modeAppend, 0, refresh_idx);
		}, temporisation);
		return;
	}

	$list.find('.headerTools').find('.loadingIcon').css('opacity', 1);

	var $resultContainer = $('#' + list_id + '_result');
	var object_name = $list.data('object_name');

	var data = getListData($list);

	if (full_reload) {
		data['full_reload'] = 1;
		$list.find('.list_table_cols_change_notification').slideUp(250);
	}
	if (modeAppend) {
		data['modeAppend'] = 1;
	}
	if (typeof (id_config) !== 'undefined') {
		data['id_list_table_config'] = id_config;
	}

	// Envoi requête:
	var error_msg = 'Une erreur est survenue. La liste des ';
	if (typeof (object_labels[object_name].name_plur) !== 'undefined') {
		error_msg += object_labels[object_name].name_plur;
	} else {
		error_msg += 'objets "' + object_name + '"';
	}
	error_msg += ' n\'a pas pu être rechargée';

	BimpAjax('loadObjectList', data, null, {
		$list: $list,
		refresh_idx,
		full_reload: full_reload,
		$resultContainer: $resultContainer,
		display_success: false,
		display_errors_in_popup_only: true,
		display_warnings_in_popup_only: true,
		error_msg: error_msg,
		success: function (result, bimpAjax) {
			bimpAjax.$list.find('.headerTools').find('.loadingIcon').css('opacity', 0);
			if (result.rows_html) {
				var cur_idx = parseInt(bimpAjax.$list.data('refresh_idx'));

				if (!isNaN(cur_idx) && cur_idx > bimpAjax.refresh_idx) {
					return;
				}

				if (typeof (result.id_config) !== 'undefined') {
					$list.data('id_config', result.id_config);
				}

				hidePopovers($list);

				if (result.modeAppend)
					bimpAjax.$list.find('tbody.listRows').append(result.rows_html);
				else
					bimpAjax.$list.find('tbody.listRows').html(result.rows_html);


				if (result.pagination_html) {
					bimpAjax.$list.find('.listPagination').each(function () {
						$(this).data('event_init', 0);
						$(this).html(result.pagination_html).parent('td').parent('tr.paginationContainer').show();
					});
					setPaginationEvents(bimpAjax.$list);
				} else {
					bimpAjax.$list.find('.listPagination').each(function () {
						$(this).html('').parent('td').parent('tr.paginationContainer').hide();
					});
				}

				if (result.active_filters_html) {
					bimpAjax.$list.find('.list_active_filters').each(function () {
						$(this).html(result.active_filters_html).show();
					});
				} else {
					bimpAjax.$list.find('.list_active_filters').each(function () {
						$(this).hide().html('');
					});
				}

				if (result.filters_panel_html) {
					bimpAjax.$list.find('.listFiltersPanelContainer').each(function () {
						$(this).html(result.filters_panel_html);
					});
					bimpAjax.$list.find('.headerTools').find('.openFiltersPanelButton').show();
				} else {
					bimpAjax.$list.find('.listFiltersPanelContainer').each(function () {
						$(this).hide();
					});
					bimpAjax.$list.find('.headerTools').find('.openFiltersPanelButton').removeClass('action-close').addClass('action-open').hide();
				}

				if (typeof (result.before_html) === 'string') {
					bimpAjax.$list.find('.before_list_content').each(function () {
						if (parseInt($(this).data('refresh'))) {
							$(this).html(result.before_html);
						}
					});
				}

				if (typeof (result.after_html) === 'string') {
					bimpAjax.$list.find('.after_list_content').each(function () {
						if (parseInt($(this).data('refresh'))) {
							$(this).html(result.after_html);
						}
					});
				}

				if (bimpAjax.full_reload) {
					if (result.thead_html) {
						bimpAjax.$list.find('thead.listTableHead').html(result.thead_html);
					}
				}

				if (typeof (result.colspan) !== 'undefined') {
					var colspan = parseInt(result.colspan);

					if (!isNaN(colspan)) {
						$list.find('td.fullrow').attr('colspan', colspan);
					}
				}

				if (bimpAjax.full_reload) {
					bimpAjax.$list.data('loaded_event_processed', 0);
					onListLoaded(bimpAjax.$list);
				} else {
					onListRefeshed(bimpAjax.$list);
				}

				if (typeof (callback) === 'function') {
					callback(true);
				}
			} else {
				if (typeof (callback) === 'function') {
					callback(false);
				}
			}
		},
		error: function () {
			$list.find('.headerTools').find('.loadingIcon').css('opacity', 0);
			if (typeof (callback) === 'function') {
				callback(false);
			}
		}
	});
}

function loadListConfig($button, id_config) {
	var $list = $button.findParentByClass('object_list_table');

	if (!$.isOk($list)) {
		bimp_msg('Erreur: liste non trouvée', 'danger', null, true);
		return;
	}

	if ($button.hasClass('disabled')) {
		return;
	}

	$button.addClass('disabled');

	reloadObjectList($list.attr('id'), null, true, id_config);
}

function loadModalList(module, object_name, list_name, id_parent, $button, title, extra_data, extra_filters, extra_joins) {
	if (typeof (title) === 'undefined' || !title) {
		title = '<i class="fa fa-bars iconLeft"></i>Liste des ';
		if (typeof (object_labels[object_name].name_plur) !== 'undefined') {
			title += object_labels[object_name].name_plur;
		} else {
			title += 'Objet "' + object_name + '"';
		}
	}

	if (typeof (id_parent) === 'undefined') {
		id_parent = 0;
	}
	var data = {
		'module': module,
		'object_name': object_name,
		'list_name': list_name,
		'id_parent': id_parent
	};

	if (extra_data) {
		data['extra_data'] = extra_data;
	}

	if (extra_filters) {
		data['extra_filters'] = extra_filters;
	}

	if (extra_joins) {
		data['extra_joins'] = extra_joins;
	}

	bimpModal.loadAjaxContent($button, 'loadObjectListFullPanel', data, title, '', function (result, bimpAjax) {
		var $new_list = bimpAjax.$resultContainer.find('#' + result.list_id);
		if ($new_list.length) {
			$new_list.data('modal_idx', bimpAjax.$resultContainer.data('idx'));
			bimpModal.removeComponentContent($new_list.attr('id'));
			onListLoaded($new_list);
		}
	}, {}, 'large');
}

function loadModalFormFromList(list_id, form_name, $button, id_object, id_parent, title, on_save) {
	var $list = $('#' + list_id);
	if (!$list.length) {
		bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
		return;
	}

	if (typeof (id_object) === 'undefined') {
		id_object = 0;
	}

	var data = {
		'module': $list.data('module'),
		'object_name': $list.data('object_name'),
		'form_name': form_name,
		'id_object': id_object,
		'id_parent': id_parent
	};

	if (!id_object) {
		var $values_input = $list.find('input[name=' + form_name + '_add_form_values]');
		if ($values_input.length) {
			data['param_values'] = $values_input.val();
		}
	}

	var $asso_filters_input = $list.find('input[name=param_associations_filters]');
	if ($asso_filters_input.length) {
		data['param_associations_params'] = $asso_filters_input.val();
	}

	if (typeof (on_save) !== 'string') {
		on_save = '';
	}

	loadModalForm($button, data, title, null, on_save);
}

function updateObjectFromRow(list_id, id_object, $button) {
	var $list = $('#' + list_id);

	if (!$list.length) {
		bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
		return;
	}

	var object_name = $list.data('object_name');

//    var $resultContainer = $('#' + list_id + '_result');
	var $row = $list.find('tbody.listRows').find('#' + object_name + '_row_' + id_object);

	if (!$row.length) {
		bimp_msg('Erreur technique: liste non trouvée', 'danger', null, true);
		return;
	}

	var data = {
		'list_name': $list.data('name'),
		'module': $list.data('module'),
		'object_name': object_name,
		'id_object': id_object
	};

	$row.removeClass('modified');
	$row.find('.inputContainer').each(function () {
		var field_name = $(this).data('field_name');
		if (field_name) {
			var val = $(this).find('[name=' + field_name + ']').val();
			if (typeof (val) !== 'undefined') {
				data[field_name] = val;
			}
		}
	});

	BimpAjax('saveObject', data, null, {
		success: function (result) {
			$('body').trigger($.Event('objectChange', {
				module: result.module,
				object_name: result.object_name,
				id_object: result.id_object
			}));
		}
	});
}

function saveAllRowsModifications(list_id, $button) {
	var $list = $('#' + list_id);

	if (!$list.length) {
		bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
		return;
	}

	$list.find('tbody.listRows').children('tr.modified').each(function () {
		var id_object = $(this).data('id_object');
		var $button = $(this).find('.updateButton');
		updateObjectFromRow(list_id, id_object, $button);
	});
}

function addObjectFromList(list_id, $button, $add_form_name = '', extra_data = {}) {
	var $list = $('#' + list_id);

	if (!$list.length) {
		bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
		return;
	}

	var $result = $('#' + list_id + '_result');
	var $row = $('#' + list_id + '_addObjectRow');

	if (!$row.length) {
		if ($result.length) {
			bimp_msg('Aucun formulaire trouvé', 'danger', null, true);
		}
		return;
	}

	var data = {
		'list_name': $list.data('name'),
		'module': $list.data('module'),
		'object_name': $list.data('object_name'),
		'id_object': 0
	};
	
	if (typeof(extra_data) !== 'undefined') {
		for (var key in extra_data) {
			data[key] = extra_data[key];
		}
	}

	$row.find('.inputContainer').each(function () {
		var field_name = $(this).data('field_name');
		if (field_name) {
			data[field_name] = getInputValue($(this));
		}
	});

	var $values_input = $list.find('input[name=' + $add_form_name + '_add_form_values]');
	if ($values_input.length) {
		
		var values = JSON.parse($values_input.val());
		console.log('VALUES');
		console.log(values);
		if (typeof(values.fields) !== 'undefined') {
			console.log('FIELDS');
			console.log(values.fields);
			for (var field in values.fields) {
				data[field] = values.fields[field];
			}
		} else {
			bimp_msg('FAIL 2', 'danger');
		}
	} else {
		bimp_msg('FAIL 1', 'danger');
	}

	console.log('DATA');
	console.log(data);

	BimpAjax('saveObject', data, null, {
		$button: $button,
		success: function (result) {
			resetListAddObjectRow(list_id);
			$('body').trigger($.Event('objectChange', {
				module: result.module,
				object_name: result.object_name,
				id_object: result.id_object
			}));
		}
	});
}

function deleteObjects(list_id, objects_list, $button) {
	if ($button.hasClass('disabled')) {
		return;
	}

	if (!objects_list.length) {
		return;
	}

	var $list = $('#' + list_id);
	if (!$list.length) {
		bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
		return;
	}

	var object_name = $list.data('object_name');

	var msg = 'Voulez-vous vraiment supprimer les ';

	if (typeof (object_labels[object_name]) !== 'undefined') {
		msg += object_labels[object_name]['name_plur'];
		if (object_labels[object_name]['is_female']) {
			msg += ' sélectionnées?';
		} else {
			msg += ' sélectionnés?';
		}
	} else {
		msg += 'objets sélectionnés?';
	}

	if (confirm(msg)) {
		var $resultContainer = $('#' + list_id + '_result');
		var data = {
			'module': $list.data('module'),
			'object_name': object_name,
			'objects': objects_list
		};

		BimpAjax('deleteObjects', data, null, {
			$button: $button,
			success: function (result) {
//                for (var i in result.objects_list) {
				$('body').trigger($.Event('objectDelete', {
					module: result.module,
					object_name: result.object_name,
					id_object: 0
//                        id_object: result.objects_list[i]
				}));
//                }
			}
		});
	}
}

function deleteSelectedObjects(list_id, $button) {
	var $list = $('#' + list_id);

	if (!$list.length) {
		bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
		return;
	}

	var $resultContainer = $('#' + list_id + '_result');
	var $selected = $list.find('tbody').find('input.item_check:checked');
	var object_name = $list.data('object_name');

	if (!$selected.length) {
		var msg = '';
		if (object_labels[object_name]['is_female']) {
			msg = 'Aucune ' + object_labels[object_name]['name'] + ' sélectionnée';
		} else {
			msg = 'Aucun ' + object_labels[object_name]['name'] + ' sélectionné';
		}
		bimp_msg(msg, 'danger', null, true);
	} else {
		var objects_list = [];
		$selected.each(function () {
			objects_list.push(parseInt($(this).data('id_object')));
		});
		deleteObjects(list_id, objects_list, $button, $resultContainer);
	}
}

function saveObjectPosition(list_id, id_object, position) {
	var $list = $('#' + list_id);
	if (!$list.length) {
		bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
		return;
	}

	var data = {
		'list_id': list_id,
		'module': $list.data('module'),
		'object_name': $list.data('object_name'),
		'id_object': id_object,
		'position': position
	};

	BimpAjax('saveObjectPosition', data, null, {
		success: function (result) {
			var $list = $('#' + result.list_id);
			sortListByPosition($list.attr('id'));
		},
		error: function (result) {
			var $list = $('#' + result.list_id);
			sortListByPosition($list.attr('id'));
		}
	});
}

function toggleSelectedItemsAssociation(list_id, operation, association, id_associate) {
	var $list = $('#' + list_id);

	if (!$list.length) {
		bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
		return;
	}

	var $resultContainer = $('#' + list_id + '_result');
	var $selected = $list.find('tbody').find('input.item_check:checked');
	var module = $list.data('module');
	var object_name = $list.data('object_name');

	if (!$selected.length) {
		var msg = '';
		if (object_labels[object]['is_female']) {
			msg = 'Aucune ' + object_labels[object_name]['name'] + ' sélectionnée';
		} else {
			msg = 'Aucun ' + object_labels[object_name]['name'] + ' sélectionné';
		}
		bimp_msg(msg, 'danger', null, true);
	} else {
		var associations = [];

		$selected.each(function () {
			associations.push({
				module: module,
				object_name: object_name,
				association: association,
				id_associate: id_associate,
				id_object: parseInt($(this).data('id_object'))
			});
		});

		saveAssociations(operation, associations, $resultContainer);
	}
}

function setSelectedObjectsNewStatus($button, list_id, new_status, extra_data, confirm_msg) {
	if ($button.hasClass('disabled')) {
		return;
	}

	var $list = $('#' + list_id);

	if (!$list.length) {
		bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
		return;
	}

	if (typeof (extra_data) === 'undefined') {
		extra_data = {};
	}

	var $selected = $list.find('tbody').find('input.item_check:checked');
	var object_name = $list.data('object_name');

	if (!$selected.length) {
		var msg = '';
		if (object_labels[object_name]['is_female']) {
			msg = 'Aucune ' + object_labels[object_name]['name'] + ' sélectionnée';
		} else {
			msg = 'Aucun ' + object_labels[object_name]['name'] + ' sélectionné';
		}
		bimp_msg(msg, 'danger', null, true);
	} else {
		if (typeof (confirm_msg) === 'string') {
			if (!confirm(confirm_msg.replace(/&quote;/g, '"'))) {
				return;
			}
		}
		$button.addClass('disabled');
		var ids = [];
		$selected.each(function () {
			var id_object = $(this).data('id_object');
			if (id_object) {
				ids.push(id_object);
			} else {
				var msg = '';
				if (object_labels[object_name]['is_female']) {
					msg = 'ID ' + object_labels[object_name]['of_the'] + ' sélectionnée n° ' + i + ' absent';
				} else {
					msg = 'ID ' + object_labels[object_name]['of_the'] + ' sélectionné n° ' + i + ' absent';
				}
				bimp_msg(msg, 'danger', null, true);
			}
		});
		if (ids.length) {
			setObjectNewStatus(null, {
				module: $list.data('module'),
				object_name: object_name,
				id_object: ids
			}, new_status, extra_data, null, null, null);
		}

		$button.removeClass('disabled');
	}
}

function setSelectedObjectsAction($button, list_id, action, extra_data, form_name, confirm_msg, single_action, on_form_submit, success_callback, $resultContainer, options) {
	if ($button.hasClass('disabled')) {
		return;
	}

	if (typeof (confirm_msg) === 'string' && confirm_msg) {
		if (!confirm(confirm_msg.replace(/&quote;/g, '"'))) {
			return;
		}
	}

	if (typeof ($resultContainer) === 'undefined') {
		$resultContainer = null;
	}

	if (typeof (single_action) === 'undefined') {
		single_action = false;
	}

	var $list = $('#' + list_id);

	if (!$list.length) {
		bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
		return;
	}

	if (typeof (extra_data) === 'undefined') {
		extra_data = {};
	}

	var $selected = $list.find('tbody').find('input.item_check:checked');
	var object_name = $list.data('object_name');

	if (!$selected.length) {
		var msg = '';
		if (object_labels[object_name]['is_female']) {
			msg = 'Aucune ' + object_labels[object_name]['name'] + ' sélectionnée';
		} else {
			msg = 'Aucun ' + object_labels[object_name]['name'] + ' sélectionné';
		}
		bimp_msg(msg, 'danger', null, true);
	} else {
		if (typeof (form_name) === 'string' && form_name) {
			extra_data['id_objects'] = [];
			$selected.each(function () {
				var $input = $(this);
				extra_data['id_objects'].push($input.data('id_object'));
			});
			var title = '';
			if ($.isOk($button)) {
				title = $button.text();
			}
			loadModalForm($button, {
				module: $list.data('module'),
				object_name: $list.data('object_name'),
				id_object: 0,
				form_name: form_name,
				extra_data: extra_data,
				param_values: {
					fields: extra_data
				}
			}, title, function ($form) {
				if ($.isOk($form)) {
					var modal_idx = parseInt($form.data('modal_idx'));
					if (!modal_idx) {
						bimp_msg('Erreur technique: index de la modale absent', null, true);
						return;
					}
				}
				if ($form.length) {
					for (var field_name in extra_data) {
						var $input = $form.find('[name="' + field_name + '"]');
						if ($input.length) {
							$input.val(extra_data[field_name]);
						}
					}
					bimpModal.$footer.find('.save_object_button.modal_' + modal_idx).remove();
					bimpModal.$footer.find('.objectViewLink.modal_' + modal_idx).remove();
					bimpModal.addButton('Valider<i class="fa fa-arrow-circle-right iconRight"></i>', '', 'primary', 'set_action_button', modal_idx);

					bimpModal.$footer.find('.set_action_button.modal_' + modal_idx).click(function () {
						if (validateForm($form)) {
							$form.find('.inputContainer').each(function () {
								field_name = $(this).data('field_name');
								if ($(this).data('multiple')) {
									field_name = $(this).data('values_field');
								}
								if (field_name) {
									extra_data[field_name] = getInputValue($(this));
								}
							});
							if (typeof (on_form_submit) === 'function') {
								extra_data = on_form_submit($form, extra_data);
							}

							setSelectedObjectsAction($button, list_id, action, extra_data, null, null, true, null, function (result) {
								if (typeof (result.warnings) !== 'undefined' && result.warnings && result.warnings.length) {
									bimpModal.$footer.find('.set_action_button.modal_' + $form.data('modal_idx')).remove();
								} else {
									bimpModal.clearAllContents();
								}
								if (typeof (successCallback) === 'function') {
									successCallback(result);
								}
							}, $form.find('.ajaxResultContainer'), options);
						}
					});
				}
			});
		} else {
			if (single_action) {
				extra_data['id_objects'] = [];
				$selected.each(function () {
					var $input = $(this);
					extra_data['id_objects'].push($input.data('id_object'));
				});
				setObjectAction($button, {
					module: $list.data('module'),
					object_name: object_name,
					id_object: 0
				}, action, extra_data, $resultContainer, success_callback, options);
			} else {
				$button.addClass('disabled');
				var i = 1;
				$selected.each(function () {
					elem = $(this);
//                    setTimeout(function(elem, $list, object_name){
					var id_object = elem.data('id_object');
					if (id_object) {
						setObjectAction(null, {
							module: $list.data('module'),
							object_name: object_name,
							id_object: id_object
						}, action, extra_data, $resultContainer, success_callback, options);
					} else {
						var msg = '';
						if (object_labels[object_name]['is_female']) {
							msg = 'ID ' + object_labels[object_name]['of_the'] + ' sélectionnée n° ' + i + ' absent';
						} else {
							msg = 'ID ' + object_labels[object_name]['of_the'] + ' sélectionné n° ' + i + ' absent';
						}
						bimp_msg(msg, 'danger', null, true);
					}
//                    }, i * 5000, elem, $list, object_name);
					i++;
				});
				$button.removeClass('disabled');
			}
		}
	}
}

function setFilteredListObjectsAction($button, list_id, action, extra_data, form_name, confirm_msg, on_form_submit, success_callback, $resultContainer) {
	if ($button.hasClass('disabled')) {
		return;
	}

	if ((typeof (confirm_msg) !== 'string' || !confirm_msg)) {
		confirm_msg = 'Attention, ce processus est irréversible. Etes-vous sûr d\'avoir sélectionné les bons filtres?';
	}

	if (typeof ($resultContainer) === 'undefined') {
		$resultContainer = null;
	}

	var $list = $('#' + list_id);

	if (!$list.length) {
		bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
		return;
	}

	if (typeof (extra_data) === 'undefined') {
		extra_data = {};
	}

	if (typeof (form_name) === 'string' && form_name) {
		var title = '';
		if ($.isOk($button)) {
			title = $button.text();
		}
		loadModalForm($button, {
			module: $list.data('module'),
			object_name: $list.data('object_name'),
			id_object: 0,
			form_name: form_name,
			extra_data: extra_data,
			param_values: {
				fields: extra_data
			}
		}, title, function ($form) {
			if ($.isOk($form)) {
				var modal_idx = parseInt($form.data('modal_idx'));
				if (!modal_idx) {
					bimp_msg('Erreur technique: index de la modale absent', null, true);
					return;
				}
			}
			if ($form.length) {
				for (var field_name in extra_data) {
					var $input = $form.find('[name="' + field_name + '"]');
					if ($input.length) {
						$input.val(extra_data[field_name]);
					}
				}
				bimpModal.$footer.find('.save_object_button.modal_' + modal_idx).remove();
				bimpModal.$footer.find('.objectViewLink.modal_' + modal_idx).remove();
				bimpModal.addButton('Valider<i class="fa fa-arrow-circle-right iconRight"></i>', '', 'primary', 'set_action_button', modal_idx);

				var $validate_btn = bimpModal.$footer.find('.set_action_button.modal_' + $form.data('modal_idx'));

				$validate_btn.click(function () {
					if ($validate_btn.hasClass('disabled')) {
						return;
					}
					if (confirm(confirm_msg)) {
						if (validateForm($form)) {
							$form.find('.inputContainer').each(function () {
								field_name = $(this).data('field_name');
								if ($(this).data('multiple')) {
									field_name = $(this).data('values_field');
								}
								if (field_name) {
									extra_data[field_name] = getInputValue($(this));
								}
							});
							if (typeof (on_form_submit) === 'function') {
								extra_data = on_form_submit($form, extra_data);
							}

							$validate_btn.addClass('disabled');
							setFilteredListObjectsAction($button, list_id, action, extra_data, null, 'no_confirm_msg', null, function (result) {
								if (typeof (result.warnings) !== 'undefined' && result.warnings && result.warnings.length) {
									$validate_btn.remove();
								} else {
									bimpModal.clearAllContents();
								}
								if (typeof (successCallback) === 'function') {
									successCallback(result);
								}
							}, $form.find('.ajaxResultContainer'));
						}
					}
				});
			}
		});
	} else {
		if (confirm_msg !== 'no_confirm_msg') {
			if (!confirm(confirm_msg.replace(/&quote;/g, '"'))) {
				return;
			}
		}

		var data = getListData($list);
		data['action_name'] = action;
		data['extra_data'] = extra_data;
		data['param_n'] = 0;
		data['param_p'] = 1;

		$button.addClass('disabled');

		BimpAjax('setFilteredListObjectsAction', data, $resultContainer, {
			$list: $list,
			$resultContainer: $resultContainer,
			display_success: true,
			display_success_in_popup_only: true,
			success_callback: success_callback,
			display_processing: true,
			processing_padding: 20,
			success: function (result, bimpAjax) {
				if (typeof (bimpAjax.success_callback) === 'function') {
					bimpAjax.success_callback(result);
				}

				reloadObjectList(bimpAjax.$list.attr('id'));
				$button.removeClass('disabled');
			},
			error: function () {
				$button.removeClass('disabled');
			}
		});
	}
}

// Actions:

function cancelObjectRowModifications(list_id, id_object, $button) {
	var $list = $('#' + list_id);
	if (!$list.length) {
		bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
		return;
	}
	var object_name = $list.data('object_name');

	var $row = $list.find('tbody.listRows').find('tr#' + object_name + '_row_' + id_object);
	if (!$row.length) {
		bimp_msg('Erreur technique: ligne correspondante non trouvée', 'danger', null, true);
		return;
	}

	$row.find('.inputContainer').each(function () {
		var field_name = $(this).data('field_name');
		var $input = $(this).find('[name="' + field_name + '"]');
		var initial_value = $(this).data('initial_value');

		if (typeof (initial_value) === 'string' && initial_value) {
			initial_value = bimp_htmlDecode(initial_value);
		}

		if ($input.length) {
			$input.val(initial_value).change();
		}
		$input.removeClass('modified');
	});

	$row.removeClass('modified');
	$row.find('.cancelModificationsButton').hide();
	$row.find('.updateButton').hide();

	checkRowsModifications($list);
}

function cancelAllRowsModifications(list_id, $button) {
	var $list = $('#' + list_id);

	if (!$list.length) {
		bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
		return;
	}

	$list.find('tbody.listRows').children('tr.modified').each(function () {
		var id_object = $(this).data('id_object');
		$button = $(this).find('.cancelModificationsButton');
		cancelObjectRowModifications(list_id, id_object, $button);
	});
}

function checkRowModifications($row) {
	if ($row.length) {
		var modified = false;
		$row.find('.inputContainer').each(function () {
			var field_name = $(this).data('field_name');
			if (field_name) {
				var $input = $(this).find('[name="' + field_name + '"]');
				var initial_value = $(this).data('initial_value');

				if (typeof (initial_value) === 'string') {
					initial_value = bimp_htmlDecode(initial_value);
				}

				if ($input.length) {
					if (initial_value != $input.val()) {
						$input.addClass('modified');
					} else {
						$input.removeClass('modified');
					}
				}
				if ($input.length) {
					if ($input.hasClass('modified')) {
						modified = true;
					}
				}
			}
		});
		if (modified) {
			$row.addClass('modified');
			$row.find('.cancelModificationsButton').removeClass('hidden').show();
			$row.find('.updateButton').removeClass('hidden').show();
		} else {
			$row.removeClass('modified');
			$row.find('.cancelModificationsButton').hide();
			$row.find('.updateButton').hide();
		}
		var $list = findParentByClass($row, 'object_list_table');
		if ($list.length) {
			checkRowsModifications($list);
		}
	}
}

function checkRowsModifications($list) {
	if ($list.length) {
		var hasModifications = false;
		$list.find('tbody.listRows').children('tr').each(function () {
			if ($(this).hasClass('modified')) {
				hasModifications = true;
			}
		});
		var $container = $('#' + $list.attr('id') + '_container');
		if (hasModifications) {
			$container.find('.modifiedRowsActions').show();
		} else {
			$container.find('.modifiedRowsActions').hide();
		}
	}
}

function toggleCheckAll(list_id, $input) {
	var $inputs = $('#' + list_id).find('tbody').find('input.item_check');
	if ($input.prop('checked')) {
		$inputs.each(function () {
			$(this).prop('checked', true).change();
		});
	} else {
		$inputs.each(function () {
			$(this).removeAttr('checked').prop('checked', false).change();
		});
	}
}

function sortList(list_id, col_name) {
	var $row = $('#' + list_id).find('.headerRow');
	if ($row.length) {
		var $span = $row.find('#' + col_name.replace(/:/g, '___') + '_sortTitle');
		if ($span.length) {
			var $list = $('#' + list_id);
			var prev_sort_field = $list.find('input[name=param_sort_field]').val();
			var prev_sort_way = $list.find('input[name=param_sort_way]').val();
			var prev_sort_option = $list.find('input[name=param_sort_option]').val();
			$list.find('input[name=param_sort_field]').val($span.parent('th').data('col_name'));
			if ($span.hasClass('sorted-asc')) {
				$list.find('input[name=param_sort_way]').val('desc');
			} else {
				$list.find('input[name=param_sort_way]').val('asc');
			}
			var sort_option = $span.data('sort_option');
			if (!sort_option) {
				sort_option = '';
			}
			$list.find('input[name=param_sort_option]').val(sort_option);
			reloadObjectList(list_id, function (success) {
				if (success) {
					$row.find('.sortTitle').each(function () {
						if ($(this).parent('th').data('col_name') !== col_name) {
							$(this).removeClass('active').removeClass('sorted-desc').addClass('sorted-asc');
						}
					});
					$span.addClass('active');
					if ($span.hasClass('sorted-desc')) {
						$span.removeClass('sorted-desc').addClass('sorted-asc');
					} else {
						$span.removeClass('sorted-asc').addClass('sorted-desc');
					}
				} else {
					$list.find('input[name=param_sort_field]').val(prev_sort_field);
					$list.find('input[name=param_sort_way]').val(prev_sort_way);
					$list.find('input[name=param_sort_option]').val(prev_sort_option);
				}
			});
		}
	}
}

function sortListByPosition(list_id, first_page) {
	if (typeof (first_page) === 'undefined') {
		first_page = false;
	}
	var $list = $('#' + list_id);

	if ($list.length) {
		var position_field = $list.data('position_field');

		if (!position_field) {
			position_field = 'position';
		}
		$list.find('input[name=param_sort_field]').val(position_field);
		$list.find('input[name=param_sort_way]').val('asc');
		$list.find('input[name=param_sort_option]').val('');
		if (!first_page) {
			var $pagination = $('#' + list_id + '_pagination');
			if ($pagination.length) {
				var p = parseInt($pagination.find('.pageBtn.active').data('p'));
				if (p) {
					loadPage($list, p);
					return;
				}
			}
		}
		reloadObjectList($list.attr('id'));
	}
}

function loadPage($list, page, modeAppend) {
	if (!$list.length) {
		bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger', null, true);
		return;
	}

	$list.find('input[name=param_p]').val(page);

	reloadObjectList($list.attr('id'), undefined, undefined, undefined, modeAppend, 250);
}

function deactivateSorting($list) {
	var $row = $list.find('thead').find('tr.headerRow');
	$row.find('.sortTitle').addClass('deactivated').removeClass('active');
}

function activateSorting($list) {
	var $row = $list.find('thead').find('tr.headerRow');
	$row.find('.sortTitle').removeClass('deactivated');
}

function checkListWidth($list) {
	var $panelBody = $list.findParentByClass('panel-body');

	if (!$.isOk($panelBody)) {
		return;
	}

	if ($(window).width() > 1270) {
		var $filters = $list.find('.listFiltersPanelContainer');
		var $table = $list.find('.objectlistTableContainer').children('.objectlistTable');

		var width = 0;

		if ($table.length) {
			$table.parent().removeAttr('style');
			width += $table.width() + 12;
		}

		if ($filters.length && $filters.css('display') !== 'none') {
			$filters.removeAttr('style');

			if ($filters.width() > 300) {
				$filters.attr('style', 'width: 300px!important');
			}

			width += $filters.width() + 12;

			$list.css({
				'width': width + 'px'
			});

			if ($table.length) {
				var table_width = width - $filters.width() - 24;
				$table.parent().attr('style', 'width: ' + table_width + 'px!important');
			}
		} else {
			$list.css({
				'width': 'auto'
			});
		}
	}
}

// Gestion des inputs:

function resetListAddObjectRow(list_id) {
	var $row = $('#' + list_id + '_addObjectRow');
	if ($row.length) {
		var $containers = $row.find('.inputContainer');
		$containers.each(function () {
			var field_name = $(this).data('field_name');
			if (field_name) {
				var $input = $(this).find('[name=' + field_name + ']');
				if ($input.length) {
					var defval = $(this).data('initial_value');
					if (defval !== 'undefined') {
						$input.val(defval).change();
					} else {
						if ($input.hasClass('switch')) {
							$(this).val(0);
						} else {
							$(this).val('');
						}
					}
				}
			}

		});
		$row.find('.ui-autocomplete-input').val('');
		$row.find('.search_list_input').val('');
		$row.find('.select2-chosen').text('');
		$row.find('.bs_datetimepicker').each(function () {
			$(this).data('DateTimePicker').clear();
			$(this).parent().find('.datepicker_value').val('');
		});
		$row.find('.ui-autocomplete-input').val('');
	}
}

function resetListSearchInputs(list_id, reload_list) {
	if (typeof (reload_list) === 'undefined') {
		reload_list = true;
	}

	var $row = $('#' + list_id + '_searchRow');
	if ($row.length) {
		$row.find('.searchInputContainer').each(function () {
			var field_name = $(this).data('field_name');
			var search_type = $(this).data('search_type');
			if (field_name) {
				if (search_type === 'values_range') {
					$(this).find('[name=' + field_name + '_min]').val('');
					$(this).find('[name=' + field_name + '_max]').val('');
				} else {
					$(this).find('[name=' + field_name + ']').val('');
				}
			}
		});
		$row.find('.ui-autocomplete-input').val('');
		$row.find('.search_list_input').val('');
		$row.find('.select2-selection__rendered').html('');
		$row.find('.bs_datetimepicker').each(function () {
			$(this).data('DateTimePicker').clear();
			$(this).parent().find('.datepicker_value').val('');
		});
		$row.find('.search_input_selected_label').html('').hide();
		$row.find('.search_object_input').find('input').val('');
	}

	if (reload_list) {
		reloadObjectList(list_id, null, false, undefined, 0, 250);
	}
}

// Gestion des événements:

function gestionParentLine($list) {
	var idsParent = new Object();
	$list.find('.objectListItemRow').each(function () {
		if ($(this).data('id_parent_line') > 0) {
			idsParent[$(this).data('id_parent_line')] = $(this).data('id_parent_line');
		}
	});

	for (const id_parent_line in idsParent) {
		var $parentLineTd = $list.find('[data-id_object=' + id_parent_line + ']').children('td').first();

		if ($.isOk($parentLineTd)) {
			$list.find('.objectListItemRow[data-id_parent_line=' + id_parent_line + ']').hide();

			$parentLineTd.append('<a class="btn btn-info detail_' + id_parent_line + '">Detail<i class="fa fa-plus-circle iconRight"></i></a><a class="btn btn-info cache_detail_' + id_parent_line + '">Détail<i class="fa fa-minus-circle iconRight"></i></a>');
			$list.find('.cache_detail_' + id_parent_line + '').hide();
			$list.find('.detail_' + id_parent_line + '').click(function () {
				$list.find('[data-id_parent_line=' + id_parent_line + ']').show();
				$list.find('.detail_' + id_parent_line + '').hide();
				$list.find('.cache_detail_' + id_parent_line + '').show();
			});
			$list.find('.cache_detail_' + id_parent_line + '').click(function () {
				$list.find('[data-id_parent_line=' + id_parent_line + ']').hide();
				$list.find('.detail_' + id_parent_line + '').show();
				$list.find('.cache_detail_' + id_parent_line + '').hide();
			});
		}
	}
}

function onListLoaded($list) {
	if (!$list.length) {
		return;
	}


	if (!parseInt($list.data('loaded_event_processed'))) {
		$list.data('loaded_event_processed', 1);

		gestionParentLine($list);

		var $table = $list.find('table.objectlistTable');
		var $tbody = $list.find('tbody.listRows');
		var list_id = $list.attr('id');

		if ($tbody.find('tr.objectListItemRow').length > 10) {
			$list.find('tr.listFooterButtons').show();
		} else {
			$list.find('tr.listFooterButtons').hide();
		}


		resetListSearchInputs(list_id, false);

//        $tbody.find('a').each(function () {
////        $(this).attr('target', '_blank');
//            var link_title = $(this).attr('title');
//            if (link_title) {
//                $(this).popover('destroy');
//                $(this).removeClass('classfortooltip');
//                $(this).removeAttr('title');
//                $(this).popover({
//                    trigger: 'hover',
//                    content: link_title,
//                    placement: 'bottom',
//                    html: true,
//                    container: '#' + list_id,
////                    viewport: {
////                        selector: 'body',
////                        padding: 0
////                    }
//                });
//            }
//        });

		$list.find('input[name="param_n"]').change(function () {
			reloadObjectList($list.attr('id'), null, false, undefined, 0, 250);
		});

		var $tools = $list.find('.headerTools');
		if ($tools.length) {
			$tools.find('.openFiltersPanelButton').click(function () {
				var $filtersPanel = $list.find('.listFiltersPanelContainer');
				if ($filtersPanel.length) {
					if ($(this).hasClass('action-open')) {
						$table.findParentByClass('objectlistTableContainer').removeClass('col-sm-12').removeClass('col-md-12').removeClass('col-lg-12').addClass('col-sm-8').addClass('col-md-9').addClass('col-lg-10');
						$filtersPanel.stop().fadeIn(150);
						$(this).removeClass('action-open').addClass('action-close');
						checkListWidth($list);
					} else {
						$filtersPanel.stop().fadeOut(150, function () {
							$table.findParentByClass('objectlistTableContainer').removeClass('col-sm-8').removeClass('col-md-9').removeClass('col-lg-10').addClass('col-sm-12').addClass('col-md-12').addClass('col-lg-12');
							checkListWidth($list);
						});
						$(this).removeClass('action-close').addClass('action-open');
					}
				}
			});
			$tools.find('.openSearchRowButton').click(function () {
				var $searchRow = $list.find('.listSearchRow');
				if ($searchRow.length) {
					if ($(this).hasClass('action-open')) {
						$searchRow.stop().fadeIn(150, function () {
							checkListWidth($list);
						});
						$(this).removeClass('action-open').addClass('action-close');
					} else {
						$searchRow.stop().fadeOut(150, function () {
							checkListWidth($list);
						});
						$(this).removeClass('action-close').addClass('action-open');
					}
				}
			});
			$tools.find('.openAddObjectRowButton').click(function () {
				var $addRow = $list.find('.addObjectRow');
				if ($addRow.length) {
					if ($(this).hasClass('action-open')) {
						$addRow.stop().fadeIn(150, function () {
							checkListWidth($list);
						});
						$(this).removeClass('action-open').addClass('action-close');
					} else {
						$addRow.stop().fadeOut(150, function () {
							checkListWidth($list);
						});
						$(this).removeClass('action-close').addClass('action-open');
					}
				}
			});
			$tools.find('.activatePositionsButton').click(function () {
				var $handles = $list.find('.positionHandle');
				if ($handles.length) {
					if ($(this).hasClass('action-open')) {
						$handles.show();
						$(this).removeClass('action-open').addClass('action-close');
						deactivateSorting($list);
						$list.find('input[name=param_p]').val('1');
						sortListByPosition($list.attr('id'), true);
					} else {
						$handles.hide();
						$(this).removeClass('action-close').addClass('action-open');
						activateSorting($list);
					}
				}
			});
			$tools.find('input[name="select_n"]').change(function () {
				var n = parseInt($(this).val());
				$list.find('input[name="param_n"]').val(n).change();
			});

			$tools.find('.refreshListButton').click(function () {
				reloadObjectList($list.attr('id'), null, false, undefined, 0, 250);
			});

			$list.find('input[name="param_n"]').change(function () {
				var val = parseInt($(this).val());
				var select_val = parseInt($tools.find('input[name="select_n"]').val());
				if (val !== select_val) {
					$tools.find('input[name="select_n"]').val(val).change();
				}
			});
		}

		$list.find('tr.listSearchRow').each(function () {
			$(this).find('.searchInputContainer').each(function () {
				var field_name = $(this).data('field_name');
				if (field_name) {
					$(this).find('[name=' + field_name + ']').val('');
				}
			});
			$(this).find('.ui-autocomplete-input').val('');
			setSearchInputsEvents($list);
		});

//        $list.find('tbody').find('a').each(function () {
//            $(this).attr('target', '_blank');
//        });

		$list.find('tbody.listRows').children('tr.objectListItemRow').each(function () {
			var $checkbox = $(this).find('input.item_check');

			if ($checkbox.length) {
				setItemCheckboxEvents($checkbox);
			}

			checkRowModifications($(this));
		});

		setCommonEvents($('#' + $list.attr('id') + '_container'));
		setInputsEvents($list);
		setListEditInputsEvents($list);
		setPositionsHandlesEvents($list);
		setPaginationEvents($list);

		var $filters = $list.find('.object_filters_panel');
		if ($filters.length) {
			$filters.each(function () {
				onListFiltersPanelLoaded($(this));
			});
		}

		if (!$list.data('object_change_event_init')) {
			var module = $list.data('module');
			var object_name = $list.data('object_name');

			var objects = $list.data('objects_change_reload');
			if (objects) {
				objects = objects.split(',');
			} else {
				objects = [];
			}

			if (!$('body').data($list.attr('id') + '_object_events_init')) {
				$('body').on('objectChange', function (e) {
//                    bimp_msg($list.attr('id') + ' => ' + e.module + ', ' + module + ', ' + e.object_name + ', ' + object_name);
					if ((e.module === module) && (e.object_name === object_name)) {
						reloadObjectList($list.attr('id'), null, false, undefined, 0, 250);
					} else if (objects && objects.length) {
						for (var i in objects) {
							if (e.object_name === objects[i]) {
								reloadObjectList($list.attr('id'), null, false, undefined, 0, 250);
							}
						}
					}
				});
				$('body').on('objectDelete', function (e) {
					if ((e.module === module) && (e.object_name === object_name)) {
						reloadObjectList($list.attr('id'), null, false, undefined, 0, 250);
					} else if (objects.length) {
						for (var i in objects) {
							if (e.object_name === objects[i]) {
								reloadObjectList($list.attr('id'), null, false, undefined, 0, 250);
							}
						}
					}
				});
				$('body').on('listFiltersChange', function (e) {
					if (e.$filters.data('list_identifier') === $list.attr('id')) {
						reloadObjectList($list.attr('id'), null, false, undefined, 0, 1000);
					}
				});
				$('body').on('listTableColsChange', function (e) {
					var id_config = $list.data('id_config');
					if (id_config === e.id_config) {
						$list.find('.list_table_cols_change_notification').slideDown(250);
					}
				});
				$('body').data($list.attr('id') + '_object_events_init', 1);
			}

			$list.data('object_change_event_init', 1);
		}

		$('body').trigger($.Event('listLoaded', {
			$list: $list
		}));
	}

	checkListWidth($list);
}

function onListRefeshed($list) {
//    $list.find('tbody').find('a').each(function () {
//        $(this).attr('target', '_blank');
//    });

	gestionParentLine($list);

	var list_id = $list.attr('id');

	var $tbody = $list.find('tbody.listRows');

	if ($tbody.find('tr.objectListItemRow').length > 10) {
		$list.find('tr.listFooterButtons').show();
	} else {
		$list.find('tr.listFooterButtons').hide();
	}

//    $tbody.find('a').each(function () {
////        $(this).attr('target', '_blank');
//        var link_title = $(this).attr('title');
//        if (link_title) {
//            $(this).popover('destroy');
//            $(this).removeClass('classfortooltip');
//            $(this).removeAttr('title');
//            $(this).popover({
//                trigger: 'hover',
//                content: link_title,
//                placement: 'bottom',
//                html: true,
//                container: '#' + list_id,
////                viewport: {
////                    selector: 'body',
////                    padding: 0
////                }
//            });
//        }
//    });

	$list.find('tbody.listRows').children('tr.objectListItemRow').each(function () {
		var $checkbox = $(this).find('input.item_check');

		if ($checkbox.length) {
			setItemCheckboxEvents($checkbox);
		}

		checkRowModifications($(this));
	});

	setCommonEvents($tbody);
	setInputsEvents($tbody);

	setCommonEvents($list.find('.list_active_filters'));
	setInputsEvents($list.find('.list_active_filters'));

	var $content = $list.find('.before_list_content');

	if ($content.length && parseInt($content.data('refresh'))) {
		setCommonEvents($content);
		setInputsEvents($content);
	}

	var $content = $list.find('.after_list_content');

	if ($content.length && parseInt($content.data('refresh'))) {
		setCommonEvents($content);
		setInputsEvents($content);
	}

	setListEditInputsEvents($list);
	setPositionsHandlesEvents($list);

	var $filters = $list.find('.object_filters_panel');
	if ($filters.length) {
		$filters.each(function () {
			onListFiltersPanelLoaded($(this));
		});
	}

	$list.trigger('listRefresh');

	checkListWidth($list);
}

function setItemCheckboxEvents($checkbox) {
	if (!parseInt($checkbox.data('bimp_list_chebox_events_init'))) {
		$checkbox.change(function () {
			if (bimp_is_checkboxes_toggling) {
				return;
			}
			var $tr = $(this).findParentByClass('objectListItemRow');
			var $list = $(this).findParentByClass('object_list_table');

			if ($.isOk($tr) & $.isOk($list)) {
				var last_item_toggled_id = parseInt($list.data('last_item_toggled_id'));
				var item_id = parseInt($tr.data('id_object'));

				if (shift_down && last_item_toggled_id && last_item_toggled_id !== item_id) {
					bimp_is_checkboxes_toggling = true;
					var obj_name = $list.data('object_name');
					if (obj_name) {
						var $last_toggle_checkbox = $list.find('#' + obj_name + '_check_' + last_item_toggled_id);

						if ($.isOk($last_toggle_checkbox)) {
							var checked = $last_toggle_checkbox.prop('checked');
							var toggle = false;
							$list.find('tr.' + obj_name + '_row').each(function () {
								var $row = $(this);
								var row_id_obj = parseInt($row.data('id_object'));

								if (row_id_obj === last_item_toggled_id) {
									toggle = !toggle;
								} else {
									if (toggle) {
										$row.find('input[name="' + obj_name + '_check"]').prop('checked', checked).change();
									}

									if (row_id_obj === item_id) {
										toggle = !toggle;
									}
								}
							});
						}
					}

					bimp_is_checkboxes_toggling = false;
				}

				if (item_id) {
					$list.data('last_item_toggled_id', item_id);
				}
			}

		});
		$checkbox.data('bimp_list_chebox_events_init', 1);
	}
}

function setSearchInputsEvents($list) {
	if ($list.length) {
		$list.find('.searchInputContainer').each(function () {
			if (!parseInt($(this).data('event_init'))) {
				$(this).data('event_init', 1);
				var field_name = $(this).data('field_name');
				var search_type = $(this).data('search_type');
				if (field_name) {
					switch (search_type) {
						case 'value_part':
							var $input = $(this).find('[name=' + field_name + ']');
							if ($input.length) {
								var search_on_key_up = $(this).data('search_on_key_up');
								if (typeof (search_on_key_up) === 'undefined') {
									search_on_key_up = 0;
								}
								if (parseInt(search_on_key_up)) {
//                                    var min_chars = $(this).data('min_chars');
//                                    if (typeof (min_chars) === 'undefined') {
//                                        min_chars = 0;
//                                    }
									$input.keyup(function () {
//                                        var val = '' + $input.val();

//                                        if (val.length >= min_chars) {
										reloadObjectList($list.attr('id'), null, false, undefined, 0, 500);
//                                        }
									});
								} else {
									$input.change(function () {
										reloadObjectList($list.attr('id'), null, false, undefined, 0, 500);
									});
								}
							}
							break;

						case 'values_range':
							var $inputs = $(this).find('[name=' + field_name + '_min]').add('[name=' + field_name + '_max]');
							if ($inputs.length) {
								$inputs.change(function () {
									reloadObjectList($list.attr('id'), null, false, undefined, 0, 500);
								});
							}
							break;

						case 'time_range':
						case 'date_range':
						case 'datetime_range':
							setDateRangeEvents($(this), field_name);
							var $from = $(this).find('[name=' + field_name + '_from]');
							var $to = $(this).find('[name=' + field_name + '_to]');
							$from.add($to).change(function () {
								reloadObjectList($list.attr('id'), null, false, undefined, 0, 500);
							});
							break;

						case 'field_value':
						default:
							var $input = $(this).find('[name=' + field_name + ']');
							if ($input.length) {
								$input.change(function () {
									reloadObjectList($list.attr('id'), null, false, undefined, 0, 500);
								});
							}
							break;
					}
				}
			}
		});
	}
}

function setListEditInputsEvents($list) {
	var $rows = $list.find('tbody.listRows').find('.objectListItemRow');
	if ($rows.length) {
		$rows.each(function () {
			var $row = $(this);
			$row.find('.item_check').change(function () {
				if ($(this).prop('checked')) {
					$row.addClass('selected');
				} else {
					$row.removeClass('selected');
				}
			});

			$(this).find('.inputContainer').each(function () {
				var field_name = $(this).data('field_name');
				if (field_name) {
					var $input = $(this).find('[name="' + field_name + '"]');
					if ($input.length) {
						if (!$input.data('list_row_change_event_init')) {
							$input.change(function () {
								checkRowModifications($row);
							});
							$input.keyup(function () {
								$(this).change();
							});
							$input.data('list_row_change_event_init', 1);
						}
					}
				}
			});
		});
	}
}

function setPaginationEvents($list) {
	if (!$list.length) {
		return;
	}

	var $container = $('#' + $list.attr('id') + '_container');
	if (!$container.length) {
		return;
	}

	$container.find('div.listPagination').each(function () {
		var $pagination = $(this);
		if (!parseInt($(this).data('event_init'))) {
			$(this).data('event_init', 1);
		}
		var p = $(this).find('.pageBtn.active').data('p');
		if (p) {
			p = parseInt(p);
		} else {
			p = 1;
		}
		var $prev = $((this)).find('.prevButton');
		if ($prev.length) {
			if (!$prev.hasClass('disabled')) {
				$prev.click(function () {
					if ($(this).hasClass('processing') || $(this).hasClass('disabled')) {
						return;
					}

					if (p <= 1) {
						return;
					}

					$(this).addClass('selected');
					setPaginationLoading($pagination);
					loadPage($list, p - 1);
				});
			}
		}

//        var $listPagination = $(this);
//        $(window).scroll(function(){
//            var span = $listPagination.find("span.loadIfVisible");//renvoie la position par rapport au haut de page et la gauche de la page
//            var positionVisbleBAs = ($(window).scrollTop()+window.innerHeight);
//            if(span.length > 0 && positionVisbleBAs > span.offset().top){
//                $listPagination.find('.nextButtonAppend').click();
//            }
//        });


		var $next = $((this)).find('.nextButtonAppend');
		if ($next.length) {
			if (!$next.hasClass('disabled')) {
				$next.click(function () {
					if ($(this).hasClass('processing') || $(this).hasClass('disabled')) {
						return;
					}

					$(this).addClass('selected');
					setPaginationLoading($pagination);
					loadPage($list, p + 1, true);
				});
			}
		}

		var $next = $((this)).find('.nextButton');
		if ($next.length) {
			if (!$next.hasClass('disabled')) {
				$next.click(function () {
					if ($(this).hasClass('processing') || $(this).hasClass('disabled')) {
						return;
					}

					$(this).addClass('selected');
					setPaginationLoading($pagination);
					loadPage($list, p + 1);
				});
			}
		}
		$(this).find('.pageBtn').each(function () {
			if (!$(this).hasClass('active')) {
				$(this).click(function () {
					if ($(this).hasClass('processing') || $(this).hasClass('active')) {
						return;
					}

					$(this).addClass('selected');
					setPaginationLoading($pagination);
					loadPage($list, parseInt($(this).data('p')));
				});
			}
		});
	});
}

function setPaginationLoading($pagination) {
	var $btn = $pagination.find('.prevButton');
	if (!$btn.hasClass('disabled')) {
		$btn.addClass('processing');
	}

	$btn = $pagination.find('.nextButton');
	if (!$btn.hasClass('disabled')) {
		$btn.addClass('processing');
	}

	$btn = $pagination.find('.nextButtonAppend');
	if (!$btn.hasClass('disabled')) {
		$btn.addClass('processing');
	}

	$pagination.find('.pageBtn').each(function () {
		if (!$btn.hasClass('active')) {
			$(this).addClass('processing');
		}
	});
}

function setPositionsHandlesEvents($list) {
	var $tbody = $list.find('tbody.listRows');
	var $handles = $tbody.find('td.positionHandle');
	if ($list.find('.headerTools').find('.activatePositionsButton').hasClass('action-close')) {
		$handles.show();
	}
	var $rows = $handles.parent('tr');
	var first_position = 1;
	if ($handles.length) {
		$tbody.sortable({
			appendTo: $tbody,
			axis: 'y',
			cursor: 'move',
			handle: 'td.positionHandle',
			items: $rows,
			opacity: 0.75,
			start: function (e, ui) {
				var $list = ui.item.findParentByClass('object_list_table');
				first_position = parseInt($list.find('tbody.listRows').find('tr.objectListItemRow').first().data('position'));
			},
			update: function (e, ui) {
				var id_object = parseInt(ui.item.data('id_object'));
				var $list = ui.item.findParentByClass('object_list_table');
				var position = 0;
				if (id_object && $list.length) {
					var current_position = first_position;
					var check = true;
					$list.find('tbody.listRows').find('tr.objectListItemRow').each(function () {
						if (check) {
							if (parseInt($(this).data('id_object')) === id_object) {
								position = current_position;
							} else {
								current_position++;
							}
						}
					});
					if (position) {
						saveObjectPosition($list.attr('id'), id_object, position);
						return;
					}
				}
			}
		});
	}
}

function onGenerateCsvFormSubmit($form, extra_data) {
	if ($.isOk($form)) {
		var cols_options = {};
		var $container = $form.find('.cols_options_inputContainer');
		if ($.isOk($container)) {
			$container.find('select.col_option').each(function () {
				var col_name = $(this).attr('name').replace(/^col_(.+)_option$/, '$1');
				cols_options[col_name] = $(this).val();
			});
			extra_data['cols_options'] = cols_options;
		}
	}

	var $list = null;
	if (typeof (extra_data['list_id']) !== 'undefined' && extra_data['list_id']) {
		var $list = $('#' + extra_data['list_id']);
		if ($.isOk($list)) {
			extra_data['list_data'] = getListData($list);
		}
	}

	return extra_data;
}

function updateGraph(list_id, idGraph, list_name) {
	var extra_data = {};
	extra_data['list_name'] = list_name;
	extra_data['list_id'] = list_id;

	var $list = null;

	if (typeof (list_id) !== 'undefined' && list_id) {
		var $list = $('#' + list_id);
		var $conteneur = $('#' + list_id + '_' + idGraph + '_chartContainer');
		var $option_conteneur = $('#' + list_id + '_' + idGraph + '_chartOption');

		if ($.isOk($list) && $.isOk($conteneur)) {
			extra_data['list_data'] = getListData($list);
			extra_data['idGraph'] = idGraph;
			if ($option_conteneur.find('form').length > 0) {
				extra_data['form'] = $option_conteneur.find('form').serialize();
			}

			setObjectAction(null, {
				module: $list.data('module'),
				object_name: $list.data('object_name'),
				id_object: 0
			}, 'getGraphData', extra_data, null, null, {
				no_triggers: true,
				display_processing: false,

			});
		}
	}

}

// Bimp List Table (Liste libre)

function BimpListTable() {
	var blt = this;

	this.setEvents = function ($table) {
		if ($.isOk($table)) {
			if (!parseInt($table.data('bimp_list_table_events_init'))) {
				$table.data('bimp_list_table_events_init', 1);


				if (parseInt($table.data('checkboxes'))) {
					// Check All:
					var $input = $table.children('thead').find('input.bimp_list_table_check_all');
					if ($input.length) {
						$input.change(function () {
							blt.toggleCheckAll($table, $input);
						});
					}

					// Rows check:
					$table.children('tbody').children('tr.bimp_list_table_row').find('input.bimp_list_table_row_check').each(function () {
						$(this).change(function () {
							var $tr = $(this).findParentByClass('bimp_list_table_row');
							if ($(this).prop('checked')) {
								if (!$tr.hasClass('selected')) {
									$tr.addClass('selected');
								}
							} else {
								$tr.removeClass('selected');
							}
						});
					});
				}

				// Sort:
				if (parseInt($table.data('sortable'))) {
					$table.children('thead').children('tr.header_row').find('span.sortTitle').each(function () {
						$(this).click(function () {
							var $th = $(this).parent('th.col_header');
							var col_name = $th.data('col_name');
							var sort_way = 'asc';
							if ($(this).hasClass('sorted-asc')) {
								sort_way = 'desc';
							}
							blt.sort($table, col_name, sort_way);
						});
					});
				}

				// Search:
				if (parseInt($table.data('sortable'))) {
					$table.children('thead').children('tr.listSearchRow').find('.bimp_list_table_search_input').each(function () {
						switch ($(this).tagName()) {
							case 'input':
								$(this).keyup(function () {
									blt.search($table);
								});
								break;

							case 'select':
								$(this).change(function () {
									blt.search($table);
								});
								break;
						}
					});
				}
			}
		}
	};

	this.toggleCheckAll = function ($table, $input) {
		var $inputs = $table.find('tbody').find('input.bimp_list_table_row_check');
		if ($input.prop('checked')) {
			$inputs.each(function () {
				var $row = $(this).findParentByClass('bimp_list_table_row');

				if (!$.isOk($row) || (!$row.hasClass('deactivated') && $row.css('display') !== 'none')) {
					$(this).prop('checked', true).change();
				}
			});
		} else {
			$inputs.each(function () {
				$(this).removeAttr('checked').prop('checked', false).change();
			});
		}
	};

	this.uncheckAll = function ($table) {
		$table.find('input.bimp_list_table_check_all').removeAttr('checked').prop('checked', false).change();
		$table.find('tbody').find('input.bimp_list_table_row_check').each(function () {
			$(this).removeAttr('checked').prop('checked', false).change();
		});
	};

	this.sort = function ($table, sort_col, sort_way) {
		var $headers = $table.children('thead').children('tr.header_row').find('th.col_header');
		$headers.each(function () {
			if ($(this).data('col_name') === sort_col) {
				$(this).find('span.sortTitle').addClass('active');
				if (sort_way === 'asc') {
					$(this).find('span.sortTitle').addClass('sorted-asc').removeClass('sorted-desc');
				} else {
					$(this).find('span.sortTitle').addClass('sorted-desc').removeClass('sorted-asc');
				}
			} else {
				$(this).find('span.sortTitle').removeClass('active');
			}
		});


		var $tbody = $table.children('tbody');
		var switching = true;
		while (switching) {
			// Start by saying: no switching is done:
			switching = false;
			var shouldSwitch = false;
			var $rows = $tbody.children('tr.bimp_list_table_row');
			var $prev_row = null;
			var $cur_row = null;
			var x, y;

			$rows.each(function () {
				if (!shouldSwitch) {
					$cur_row = $(this);
					if ($prev_row) {
						x = $prev_row.find('.col_' + sort_col).data('value');
						if (typeof (x) === 'undefined') {
							x = $prev_row.find('.col_' + sort_col).text();
						}
						y = $cur_row.find('.col_' + sort_col).data('value');
						if (typeof (y) === 'undefined') {
							y = $cur_row.find('.col_' + sort_col).text();
						}

						if (sort_way === 'asc') {
							if (x.toLowerCase() > y.toLowerCase()) {
								shouldSwitch = true;
							}
						} else {
							if (x.toLowerCase() < y.toLowerCase()) {
								shouldSwitch = true;
							}
						}

						if (!shouldSwitch) {
							$prev_row = $cur_row;
						}
					} else {
						$prev_row = $(this);
					}
				}
			});

			if (shouldSwitch) {
				var prev_row = $prev_row.get(0);
				var cur_row = $cur_row.get(0);
				prev_row.parentNode.insertBefore(cur_row, prev_row);
				switching = true;
			}
		}
	};

	this.search = function ($table) {
		if (!$.isOk($table)) {
			return;
		}

		var $inputs = $table.children('thead').children('tr.listSearchRow').find('.bimp_list_table_search_input');
		if (!$inputs.length) {
			return;
		}

		var $rows = $table.children('tbody').children('tr.bimp_list_table_row');
		if (!$rows.length) {
			return;
		}

		var search_mode = $table.data('search_mode');

		switch (search_mode) {
			case 'lighten':
				$rows.removeClass('deactivated');
				break;

			case 'show':
				$rows.show();
				break;

			default:
				return;
		}

		$inputs.each(function () {
			var $input = $(this);
			var col_name = $input.data('col');
			var search_value = $input.val();

			if (search_value !== '') {
				var input_tag = $input.tagName();
				var regex = new RegExp('^(.*)' + search_value + '(.*)$', 'i');

				$rows.each(function () {
					var $row = $(this);
					if ((search_mode === 'lighten' && !$row.hasClass('deactivated')) ||
						(search_mode === 'show') && $row.css('display') !== 'none') {
						var check = false;

						switch (input_tag) {
							case 'input':
								var text = $row.find('.col_' + col_name).text();

								if (regex.test(text)) {
									check = true;
								}
								break;

							case 'select':
								var value = $row.find('.col_' + col_name).data('value');

								if (typeof (value) !== 'undefined') {
									if (value == search_value) {
										check = true;
									}
								}
								break;
						}

						if (!check) {
							switch (search_mode) {
								case 'lighten':
									$row.addClass('deactivated');
									break;

								case 'show':
									$row.hide();
									break;
							}
						}
					}
				});
			}
		});
	};

	this.getSelectedRows = function ($table) {
		var rows = [];

		if ($.isOk($table)) {
			var $rows = $table.children('tbody').children('tr');

			if ($.isOk($rows)) {
				$rows.each(function () {
					var $input = $(this).find('input[name="row_check"]');
					if ($input.length) {
						if ($input.prop('checked')) {
							rows.push($(this));
						}
					}
				});
			}
		}

		return rows;
	};
}

var BimpListTable = new BimpListTable();

$(document).ready(function () {
	$('body').on('bimp_ready', function () {
		$('.object_list_table').each(function () {
			onListLoaded($(this));
		});
	});

	$('body').on('contentLoaded', function (e) {
		if (e.$container.length) {
			e.$container.find('.object_list_table').each(function () {
				onListLoaded($(this));
			});
		}
	});

	$(window).resize(function () {
		$('.object_list_table').each(function () {
			checkListWidth($(this));
		});
	});
});
