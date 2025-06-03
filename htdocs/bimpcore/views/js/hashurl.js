// function listenIdFiltersToLoad() {
// 	var id = document.getElementById('id_filters_to_load').value;
// 	alert('id_filters_to_load: ' + id);
// 	defHashFiltre('fl', idDiv, id);
// }

function defHashFiltre(param, idDiv=null, idFiltre) {
	var hashExistant = window.location.hash;
	var newHash = '';
	const page = ['mt', 'st'];
	const liste = ['fl', 'pa'];
	var result = {}
	if (page.indexOf(param) > -1) {
		if (hashExistant === '') {
			newHash = param + '=' + idFiltre;
			// window.location.hash = newHash;
		} else {
			result = parseChaineToObjet(hashExistant.substring(1));
			if (param === 'mt' && result['mt'] !== idFiltre) {
				delete result['st'];
				delete result['ct'];
			}
			if( param === 'st' && result['st'] !== idFiltre) {
				delete result['ct'];
			}
			result[param] = idFiltre;
		}
	}
	else if (liste.indexOf(param) > -1) {
		if (hashExistant === '')	{
			var t = new Object();
			t[idDiv] = {[param] : idFiltre};
			const j = JSON.stringify(t);
			newHash = 'ct=' + j;
			// window.location.hash = newHash;
		} else {
			result = parseChaineToObjet(hashExistant.substring(1));
			if (result['ct']) {
				var ct = JSON.parse(result['ct'].replaceAll('%22', '"'));
				if (ct[idDiv]) {
					ct[idDiv][param] = idFiltre;
				} else {
					ct[idDiv] = {[param] : idFiltre};
				}
				const j = JSON.stringify(ct);
				result['ct'] = j;
			}
			else {
				var t = new Object();
				t[idDiv] = {[param] : idFiltre};
				result['ct'] = JSON.stringify(t);
			}
		}
	}


	var tableau = Object.entries(result);
	for (const [key, value] of tableau) {
		if (newHash.length > 0) {
			newHash += '+';
		}
		newHash += key + '=' + value;
	}
	window.location.hash = newHash;
	updateBookMark();
}

function parseChaineToObjet(chaine) {
	if(chaine.substring(0, 1) === '#')	{
		chaine = chaine.substring(1);
	}
	var elements = chaine.split('+');
	var result = {};

	$.each(elements, function(i, value) {
		var parts = value.split('=');
		result[parts[0]] = parts[1];
	});
	return result;
}

function reportHash(c, id) {
	const innerDivId = c.prevObject[0].id;
	const innerDiv = document.getElementById(innerDivId);
	var idDiv = '';

	let parent = innerDiv.parentElement;
	while (parent) {
		if (parent.tagName === 'DIV' && parent.id && parent.id !== innerDivId) {
			idDiv = parent.id;
			break;
		}
		parent = parent.parentElement;
	}
	if (!parent) {
		console.log('Aucune div parente avec un id trouvé.');
	}
	defHashFiltre('fl', idDiv, id);
}

function updateBookMark() {
	if (document.forms['actionbookmark'] && document.forms['actionbookmark'].elements['bookmark']) {
		var selecteur = document.forms['actionbookmark'].elements['bookmark'];
		var cible = '';
		for (var i=0; i<selecteur.length; i++) {
			if(selecteur[i].value === 'newbookmark')	{
				cible = selecteur[i];
			}
		}

		var get = cible.getAttribute('rel').split('%23');
		var newrel = get[0];
		var newhastag = encodeURIComponent(window.location.hash);
		newrel += newhastag;
		cible.setAttribute('rel', newrel);
	}
}

function onClicTab(typeTab, idTab) {
	if (typeTab === 'maintabs') {
		defHashFiltre('mt', null, idTab);
	}
	else {
		defHashFiltre('st', null, idTab);
	}
}

function afficherOngletSelonHash()	{
	var hash = window.location.hash;
	if (hash) {
		const classListToChange = ['active', 'in'];
		var objHash = parseChaineToObjet(hash);
		// console.log(objHash);
		if(objHash['mt'])	{
			// Désactiver l'onglet actuellement actif
			var currentTab = document.querySelector('#navtabs_maintabs li.active');
			var newTab = document.querySelector('#navtabs_maintabs li[data-navtab_id="' + objHash['mt'] + '"]');
			if (currentTab && currentTab !== newTab) {
				currentTab.classList.remove('active');
			}
			// Activer l'onglet objHash['mt']
			
			if (newTab && !newTab.classList.contains('active')) {
				newTab.classList.add('active');
			}
			// desactiver le contenu de l'onglet actuellement actif
			var currentContent = document.querySelector('#navtabs_content_maintabs div.active');
			var newContent = document.querySelector('#navtabs_content_maintabs div[id="' + objHash['mt'] + '"]');
			// const classListToChange = ['active', 'in'];
			if (currentContent && currentContent !== newContent) {
				currentContent.classList.remove(...classListToChange);
			}
			// Activer le contenu de l'onglet objHash['mt']
			if (newContent && !newContent.classList.contains('active')) {
				newContent.classList.add(...classListToChange);
			}
			if(objHash['st'] === undefined) {
				// Si l'onglet secondaire n'est pas défini, on cheche l'onglet secondaire actif
				var ul = newContent.firstChild;
				var activeSubTab = document.querySelector('#navtabs_' + ul.dataset.navtabs_id + ' li.active');
				if (activeSubTab) {
					var activeSubContent = document.getElementById(activeSubTab.dataset.navtab_id);
					if (activeSubContent) {
						activeSubContent.classList.add(...classListToChange);
					}
				}
			}
		}
		if(objHash['st'])	{
			// Désactiver l'onglet actuellement actif
			var subTabName = '#navtabs_' + (objHash['mt'] || 'card') + '_tabs';

			var currentSubTab = document.querySelector(subTabName + ' li.active');
			var newSubTab = document.querySelector(subTabName + ' li[data-navtab_id="' + objHash['st'] + '"]');
			if (!currentSubTab && !newSubTab) {
				subTabName = '#navtabs_' + (objHash['mt'] || 'card') + '_view';
				currentSubTab = document.querySelector(subTabName + ' li.active');
				newSubTab = document.querySelector(subTabName + ' li[data-navtab_id="' + objHash['st'] + '"]');
			}
			if (!currentSubTab && !newSubTab) {
				subTabName = '#navtabs_' + (objHash['mt'] || 'card');
				currentSubTab = document.querySelector(subTabName + ' li.active');
				newSubTab = document.querySelector(subTabName + ' li[data-navtab_id="' + objHash['st'] + '"]');
			}
			if (currentSubTab && currentSubTab !== newSubTab) {
				currentSubTab.classList.remove('active');
			}
			// Activer l'onglet objHash['st']
			if (newSubTab && !newSubTab.classList.contains('active')) {
				newSubTab.classList.add('active');
			}
			// desactiver le contenu de l'onglet actuellement actif
			var subDivName = '#navtabs_content_' + (objHash['mt'] || 'card') + '_tabs';
			var currentSubContent = document.querySelector(subDivName + ' div.active');
			var newSubContent = document.querySelector(subDivName + ' div[id="' + objHash['st'] + '"]');
			if (!currentSubContent && !newSubContent) {
				subDivName = '#navtabs_content_' + (objHash['mt'] || 'card') + '_view';
				currentSubContent = document.querySelector(subDivName + ' div.active');
				newSubContent = document.querySelector(subDivName + ' div[id="' + objHash['st'] + '"]');
			}
			if (!currentSubContent && !newSubContent) {
				subDivName = '#navtabs_content_' + (objHash['mt'] || 'card');
				currentSubContent = document.querySelector(subDivName + ' div.active');
				newSubContent = document.querySelector(subDivName + ' div[id="' + objHash['st'] + '"]');
			}
			// const classListToChange = ['active', 'in'];
			if (currentSubContent && currentSubContent !== newSubContent) {
				currentSubContent.classList.remove(...classListToChange);
			}
			
			// Activer le contenu de l'onglet objHash['st']
			if (newSubContent && !newSubContent.classList.contains('active')) {
				newSubContent.classList.add(...classListToChange);
				if(newSubTab) {
					var a = newSubTab.firstChild;
					var cmd = a.getAttribute('data-ajax_callback');
					if (cmd) {
						cmd = cmd.replaceAll('&quot;', '"');
						eval(cmd);
					}
				}
			}
		}
	}
}

function chargerFiltreSelonHash() {
	if (window.location.hash) {
		// var result = decoupeHash(window.location.hash);
		var result = parseChaineToObjet(window.location.hash);
		if (result.ct) {
			var ct = JSON.parse(result.ct.replaceAll('%22', '"'));
			for (const [divId, params] of Object.entries(ct)) {
				// console.log(divId, params);
				for (const [param, id] of Object.entries(params)) {
					if (param === 'fl') {
						var div = document.getElementById(divId);
						if (div) {
							var $input = $(div.querySelector('select#id_filters_to_load'));
							if ($input.length) {
								var filters_panel = $input.findParentByClass('object_component object_filters_panel').attr('id');
								$input.val(id).change();
								loadSavedFilters(filters_panel, id, 1);
							}
						}
					}
				}
			}
		}
	}
}

$(window).on('hashchange',function(){
	updateBookMark();
	if(window.location.hash) {
		afficherOngletSelonHash();
		// chargerFiltreSelonHash();
	}
});
$(window).on("load", function () {
	updateBookMark();
	if(window.location.hash) {
		afficherOngletSelonHash();
		// chargerFiltreSelonHash();
	}
});

$(window).on();
