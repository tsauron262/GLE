var bimp_hashReady = true;

function ecrireHash(paramNav) {
	if (bimp_hashReady) {
		console.log('ecrireHash', paramNav);
		var hashExistant = window.location.hash;
		var newHash = '';
		for (var valueParam of paramNav) {
			if (newHash !== '') {
				newHash += '&';
			}
			newHash += valueParam;
		}
		window.location.hash = newHash;
	}
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

		if(cible) {
			var get = cible.getAttribute('rel').split('%23');
			var newrel = get[0];
			var newhastag = encodeURIComponent(window.location.hash);
			newrel += newhastag;
			cible.setAttribute('rel', newrel);
		}
		else {
			console.log('newbookmark non trouve dans le formulaire actionbookmark.');
		}
	}
}

function reportFilterHash(c, id) {
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
	else {
		var hash = window.location.hash;
		hash = hash.substring(1); // Enlever le #
		var elements = hash.split('&');
		var values = Object.entries(elements);
		var ct = {};
		var indexct = -1;
		values.forEach(([key, value]) => {
			var parts = value.split('=');
			if (parts[0] === 'ct') {
				indexct = key;
				ct = JSON.parse(decodeURIComponent(parts[1]).replaceAll('%22', '"'));
				if (typeof ct[idDiv] === 'undefined') {
					ct[idDiv] = {fl: id};
				}
				else {
					ct[idDiv].fl = id;
				}
			}
		});
		if (indexct !== -1) {
			elements[indexct] = 'ct=' + JSON.stringify(ct).replaceAll('"', '%22');
			hash = elements.join('&');
			window.location.hash = hash;
		}
		else {
			ct[idDiv] = {fl: id};
			elements.push('ct=' + JSON.stringify(ct).replaceAll('"', '%22'));
			hash = elements.join('&');
			window.location.hash = hash;
		}
	}
}

function reportPageHash(c, p) {
	var divId = c[0].id;
	// console.log('reportPageHash', divId, p);
	if (divId) {
		var hash = window.location.hash;
		hash = hash.substring(1); // Enlever le #
		var elements = hash.split('&');
		var values = Object.entries(elements);
		values.forEach(([index, value]) => {
			var parts = value.split('=');
			if (parts[0] === 'ct') {
				var ct = JSON.parse(decodeURIComponent(parts[1]).replaceAll('%22', '"'));
				if (ct[divId]) {
					ct[divId].pa = p;
				} else {
					ct[divId] = {pa: p};
				}
				elements[index] = 'ct=' + JSON.stringify(ct).replaceAll('"', '%22');
			}
		});
		console.log('reportPageHash', elements, p);
	}
}

function traitementHash(hash) {
	hash = window.location.hash.replaceAll('#', '');
	// console.log('traitementHash', hash);
	var elements = hash.split('&');
	const values = Object.values(elements);
	var isCtPresent = false;
	var isFlPresent = false;
	var isPaPresent = false;
	values.forEach(value => {
		var parts = value.split('=');
		if(parts[0] != 'ct') {
			// console.log(parts);
			const aTag = document.querySelector('li[data-navtab_id="' + parts[1] + '"] a');
			if (aTag) {
				bimp_hashReady = false;
				$(aTag).tab('show'); // Affiche l'onglet correspondant
				setTimeout(function() {
					bimp_hashReady = true;
				}, 1000);
			}
		}
		else {
			isCtPresent = true;
			var ct = JSON.parse(decodeURIComponent(parts[1]).replaceAll('%22', '"'));
			// console.log(parts, ct);
			for (const [divId, params] of Object.entries(ct)) {
				var isPaPresent = false;
				for (var [param, id] of Object.entries(params)) {
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
					if (param === 'pa') {
						isPaPresent = true;
						var $list = $('#' + divId);
						// depuis $list, trouver la page max
						var pageButtons = document.getElementById(divId + '_pagination');
						var pageSpans = pageButtons.querySelectorAll('.pageBtn');
						var pageCourante = null;
						var pageMax = null;
						pageSpans.forEach(function(span) {
							var page = parseInt(span.getAttribute('data-p'), 10);
							// Vérifie si c'est la page active (courante)
							if (span.classList.contains('active')) {
								pageCourante = page;
							}
							// pageMax sera mis à jour à chaque itération, le dernier "data-p" est supposé être le max
							if (pageMax === null || page > pageMax) {
								pageMax = page;
							}
						});
						// console.log($list);
						if (id === 0) id = 1;
						if (id > pageMax) id = pageMax;
						if (pageCourante !== id) {
							loadPage($list, id);
						}
					}
				}
				if (!isPaPresent) 	{
					// console.log('Paramètre:', param, ', ID:', id, ', Div ID:', divId, ', isPaPresent:', isPaPresent);
					// Si on n'a pas trouvé de paramètre pa, on charge la première page
					var $list = $('#' + divId);
					if ($list.length) {
						loadPage($list, 1);
					}
				}
			}
		}
	});
	if(!isCtPresent) {
		// Aucun ct trouvé dans le hash on vide les filtres
		viderFiltres();
	}
}

function chargerFiltreSelonHash() {
	if (window.location.hash) {
		var hash = window.location.hash.replaceAll('#', '');
		var elements = hash.split('&');
		const values = Object.values(elements);
		values.forEach(value => {
			var parts = value.split('=');
			if(parts[0] === 'ct') {
				var ct = JSON.parse(parts[1].replaceAll('%22', '"'));
				for (const [divId, params] of Object.entries(ct)) {
					// console.log(divId, params);
					for (const [param, id] of Object.entries(params)) {
						// console.log('Paramètre:', param, ', ID:', id, ', Div ID:', divId);
						if (param === 'fl') {
							var div = document.getElementById(divId);
							// console.log('Div trouvée :', div);
							if (div) {
								var $input = $(div.querySelector('select#id_filters_to_load'));
								if ($input.length) {
									var filters_panel = $input.findParentByClass('object_component object_filters_panel').attr('id');
									$input.val(id).change();
									loadSavedFilters(filters_panel, id, 1);
								}
							}
						}
						else if (param === 'pa') {
							if (id) {
								var pageButtons = document.getElementById(divId + '_pagination');
								if (!pageButtons) {
									// console.warn('Pagination non trouvée pour le div:', divId);
									return;
								}
								var pageSpans = pageButtons.querySelectorAll('.pageBtn');
								var pageCourante = null;
								var pageMax = null;
								pageSpans.forEach(function(span) {
									var page = parseInt(span.getAttribute('data-p'), 10);
									// Vérifie si c'est la page active (courante)
									if (span.classList.contains('active')) {
										pageCourante = page;
									}
									// pageMax sera mis à jour à chaque itération, le dernier "data-p" est supposé être le max
									if (pageMax === null || page > pageMax) {
										pageMax = page;
									}
								});
								if (pageCourante !== id) {
									if (id > pageMax) {
										var p = pageMax;
									}
									else {
										var p = id;
									}
									var $list = $('#' + divId);
									loadPage($list, p);
								}
							}
						}
					}
				}
			}
		});
	}
}

function viderFiltres()	{
	var filters = document.querySelectorAll('.object_component.object_filters_panel');
	filters.forEach(function(filter) {
		var select = filter.querySelector('select#id_filters_to_load');
		if (select) {
			select.value = '';
			select.dispatchEvent(new Event('change'));
		}
	});
}

$(window).on('hashchange', function () {
	// console.log('changement de hash.');
	if( window.location.hash) {
		traitementHash(window.location.hash);
	}
	else {
		ecrireHash(getTabsParams());
		viderFiltres();
	}
	updateBookMark();
});

$(document).ready(function () {
	$('body').on('bimp_ready', function () {
		if (window.location.hash) {
			traitementHash(window.location.hash);
		}
		else ecrireHash(getTabsParams());
		updateBookMark();
	});
	$('body').on('listLoaded', function () {
		chargerFiltreSelonHash();
	});
	
});
