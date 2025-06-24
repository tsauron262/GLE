var valDef = {};
var valSpe = {};
var oldHash = '';

$(document).ready(function () {
	$('body').on('bimp_ready', function () {
		valSpe = getHashObj();
	});

	$(window).on('hashchange', function (event) {
		if('#'+oldHash != window.location.hash) {
			var infos = getHashObj(true);
			for (const [key, value] of Object.entries(infos)) {
				if(typeof value === 'object') {
					var $list = $('#' + key);
					traiteList($list, value);
				}
				else {
					const aTag = document.querySelector('li[data-navtab_id="' + value + '"] a');
					if (aTag) {
						$(aTag).tab('show'); // Affiche l'onglet correspondant
					}
				}
			}
		}
	});

	$('body').on('listLoaded', function (event) {
		var $list = event.$list;
		//voir si info dans getHashTabs
		
		//sinon ecrire dans valDef
		if(valDef[$list.attr('id')] === undefined) {
			valDef[$list.attr('id')] = {};
		}
		valDef[$list.attr('id')]['fl'] = getFiltersId($list);
		valDef[$list.attr('id')]['pa'] = getPageCourante($list);
		

		var infos = getHashObj();
		if(infos[$list.attr('id')] !== undefined) {
			traiteList($list, infos[$list.attr('id')]);
		}
	});
	$('body').on('listRefresh', function (event) {
		var $list = event.$list;
		if(valSpe[$list.attr('id')] === undefined) {
			valSpe[$list.attr('id')] = {};
		}
		valSpe[$list.attr('id')]['fl'] = getFiltersId($list);
		valSpe[$list.attr('id')]['pa'] = getPageCourante($list);
		ecrireHash2();
	});
	$('body').on('navTabsLoaded', function (event) {
		//voir si info dans getHashTabs
		var infos = getHashObj();
		var idNavTabs = event.$nav_tabs.attr('id').replace('navtabs_', 'navtab-');
		var idNavTab = event.$nav_tabs.find('li.active').data('navtab_id');
		if(valDef[idNavTabs] === undefined)
			valDef[idNavTabs] = idNavTab;//ecrire dans valDef
		if (infos[idNavTabs] !== undefined){//on doit aller a un onglet
			event.$nav_tabs.find('li[data-navtab_id="' + infos[idNavTabs] + '"] a').tab('show');
		}
	});
	$('body').on('navTabShow', function (event) {
		var idNavTab = event.$nav_tab.data('navtab_id');
		var idNavTabs = event.$nav_tab.parent('ul').attr('id').replace('navtabs_', 'navtab-');
		valSpe[idNavTabs] = idNavTab;
		ecrireHash2();
	});
});


function getHashObj(withDef = false){
	var $return = {};
	if (window.location.hash) {
		var hash = window.location.hash.replaceAll('#', '');
		var elements = hash.split('&');
		const values = Object.values(elements);
		values.forEach(value => {
			var parts = value.split('=');
			if (parts[1] !== undefined && parts[1].indexOf('{') > -1) {
				$return [parts[0]] = JSON.parse(decodeURIComponent(parts[1]));
			}
			else
				$return [parts[0]] = parts[1];
		});
	}
	if(!withDef) {
		return $return;
	}
	else {
		return Object.assign(structuredClone(valDef), $return);
	}
}

function ecrireHash2() {
	var newHash = '';
	for (const [key, value] of Object.entries(valSpe)) {

		if(typeof value === 'object'){
			if(JSON.stringify(valDef[key]) !== JSON.stringify(value)){
				newHash += key + '=' + JSON.stringify(value) + '&';
			}
		}
		else{
			if(valDef[key] !== value) {
				newHash += key + '=' + value + '&';
			}
		}
	}
	oldHash = newHash;
	window.location.hash = newHash;
}

function getFiltersId($list){
	var id = $list.find('#id_filters_to_load').val();
	if(id <= 0 || id === undefined || id === null) {
		id = 0;
	}
	return parseInt(id);
}
function getPageCourante($list){
	return $list.find('input[name="param_p"]').val();
}

function traiteList($list, value){
	var filters_id = $list.find('.object_filters_panel').attr("id");
	var filter_id = value['fl'];
	if(filters_id && filter_id != getFiltersId($list)) {
		loadSavedFilters(filters_id, filter_id, 1);
	}
	if(value['pa']  != getPageCourante($list)) {
		loadPage($list, value['pa']);
	}
}
