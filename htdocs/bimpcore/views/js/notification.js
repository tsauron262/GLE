/**
 * Utilisation des notification:
 *
 * 1 - ajout d'une entrée dans la tale llx_bimp_notification
 * 2 - créer la function php qui renvoie un tableau du style
 *          $demandes['content'][] = array(  // content est obligatoire et doit être définit ainsi
 *              'id'  => $d->id,             // content est obligatoire dans chaque content
 *                                           // il permet  de récupéré le max id
 *              'ce_que_je_veux_1' => ce_que_je_veux_1(),  // ajout spécifique à l'utilisation 1
 *              'ce_que_je_veux_2' => ce_que_je_veux_2(),  // ajout spécifique à l'utilisation 2
 *              etc...
 *          );
 * 3 - créer le fichier JS avec le même nom (.js) et nom de classe
 *     que le champ "nom" renseigné à l'étape 1 et ajouter les fonctions spécifiques
 *     (displayNotification, formatElement, etc...)
 *
 *
 */

if (typeof (bimp_use_local_storage) === 'undefined') {
	var bimp_use_local_storage = false;
}

if (typeof (bimp_debug_notifs) === 'undefined') {
	var bimp_debug_notifs = false;
}

if (typeof (bimp_notifications_refresh_delay) === 'undefined' || !bimp_notifications_refresh_delay) {
	var bimp_notifications_refresh_delay = 30;
}

if (bimp_use_local_storage && bimp_debug_notifs) {
	console.log('DEBUG NOTIFS ACTIVÉ');
	console.log('STOCKAGE LOCAL ACTIVÉ');
}

class AbstractNotification {

	// Init

	constructor(nom, id, storage_key) {
		this.nom = nom;
		this.id = id;
		this.elements = [];
		this.id_max = 0;
		this.tms = '';
		this.storage_key = storage_key;
		this.dropdown_id = 'dropdown_' + id;
		this.parent_selector = 'div.login_block_other';
		this.content_selector = 'div[aria-labelledby="' + this.dropdown_id + '"] div.notifications-wrap';
		this.max_elements = 30;
		this.sort_way = 'desc';
	}

	init() {
		var ptr = this;

		$('#' + ptr.dropdown_id).click(function (e) {
			ptr.expand();
			e.stopPropagation();
		});

		$(document).click(function (e) {
			if (!$('#page_modal').hasClass('in') && $(e.target).attr('id') != 'page_modal') {
				var $target = $(e.target);
				if (!$target.closest(ptr.parent_selector).length) {
					ptr.collapse();
				}
			}
		});

		$('span[name="reload_notif"][dropdown_id="' + ptr.dropdown_id + '"]').click(function () {
			ptr.refreshElements();
		});

		this.loadStorage();
		if (this.elements.length) {
			this.renderElements();
		}
	}

	// Gestion des élements: 

	setNewElements(data, full = false) {
		if (bimp_debug_notifs) {
			console.log('new elems');
			console.log(data);
		}

		if (typeof (data.tms) !== 'undefined') {
			this.tms = data.tms;
		}

		if (typeof (data.elements) === 'object') {
			if (full) {
				this.elements = [];
			}

			for (var i in data.elements) {
				this.addElement(data.elements[i]);
			}
		}

		this.checkElements();
		this.renderElements();
		this.checkBrowserNotification();
		this.setStorage();
	}

	addElement(element) {
		for (var i in this.elements) {
			if (this.elements[i].id === element.id) {
				this.elements[i] = element;
				return;
			}
		}

		this.elements.push(element);
	}

	checkElements() {
		if (this.elements.length) {
			var notif = this;

			this.elements.sort(function compare(a, b) {
				if (!a || !b) {
					return 0;
				}

				if (notif.isNew(a) < notif.isNew(b)) {
					return 1;
				}
				if (notif.isNew(a) > notif.isNew(b)) {
					return -1;
				}

				if (notif.sort_way === 'asc') {
					if (a.sort_val < b.sort_val) {
						return -1;
					}

					if (a.sort_val > b.sort_val) {
						return 1;
					}
				} else {
					if (a.sort_val > b.sort_val) {
						return -1;
					}

					if (a.sort_val < b.sort_val) {
						return 1;
					}
				}

				return 0;
			});

			if (this.elements.length > this.max_elements) {
				this.elements.splice(this.max_elements, (this.elements.length - this.max_elements));
			}
		}
	}

	renderElements() {
		var $container = $(this.content_selector);

		if ($.isOk($container)) {
			this.emptyContent();
			var nb_unread = 0;

			for (var i in this.elements) {
				if (!this.elements[i]) {
					continue;
				}

				var html = '';
				var is_new = this.isNew(this.elements[i]);
				var key = this.getKey(this.elements[i]);
				var url = this.elements[i].url;

				if (is_new) {
					nb_unread++;
				}

				html += '<div class="list_part ' + (url ? 'notif_link ' : '') + this.elements[i].class + /*(this.elements[i].bg_type ? ' bg-' + this.elements[i].bg_type : '')*/ +'" key="' + this.elements[i].id + '">';

				var header = '';

				if (this.elements[i].date_create) {
					header += '<span class="date_notif">' + this.formatDate(this.elements[i].date_create) + '</span>';
				} else if (this.elements[i].date_str) {
					header += '<span class="date_notif">' + this.elements[i].date_str + '</span>';
				}

				var header_buttons = this.getElementHeaderButtons(this.elements[i], this.elements[i].id);

				if (url) {
					header_buttons += '<span class="rowButton" onclick="window.open(\'' + url + '\')">';
					header_buttons += '<i class="fas fa5-external-link-alt"></i></span>';
				}

				if (header_buttons) {
					header += '<div style="display: inline-block; float: right">';
					header += header_buttons;
					header += '</div>';
				}

				if (header) {
					html += '<div class="part_list_header">' + header + '</div>';
				}

				html += this.formatElement(this.elements[i], key);
				html += '</div>';

				this.appendElement(this.elements[i], key, html);
			}

			$container.find('.bs-popover').each(function () {
				if (!parseInt($(this).data('bs_popover_event_init'))) {
					$(this).popover();
					$(this).click(function () {
						$(this).popover('hide');
					});
					$(this).data('bs_popover_event_init', 1);
				}
			});

			$container.find('.cardPopoverIcon').each(function () {
				if (!parseInt($(this).data('bs_popover_click_event_init'))) {
					$(this).click(function (e) {
						displayObjectLinkCardPopover($(this));
						e.stopPropagation();
					});
					$(this).data('bs_popover_click_event_init', 1);
				}
			});

//            if (is_multiple) { ??? 
//                var id_max_os = parseInt(bimp_storage.get(this.id + 'id_max_notif_os'));
//                if (isNaN(id_max_os) || id_max_os < this.id_max) {
//                    console.log('notifiii');
//                    bimp_storage.set(this.id + 'id_max_notif_os', this.id_max);
//                    this.displayMultipleNotification(content, nb_unread);
//                }
//            }

			this.setCountSpanValue(nb_unread);
		} else {
			console.error(this.getLabel() + ' : content container absent');
		}
	}

	appendElement(element, key, html) {
		var $container = $(this.content_selector);

		if ($.isOk($container)) {
			$container.append(html);
		}
	}

	refreshElements() {
		if (bimp_debug_notifs) {
			console.log('REFRESH : ' + this.nom);
		}
		bimp_notifications.reload(false, this.id);
	}

	emptyContent() {
		var $container = $(this.content_selector);

		if ($.isOk($container)) {
			$container.html('');
		}
	}

	// Gestion du storage: 

	setStorage() {
		if (bimp_use_local_storage && this.storage_key) {
			bimp_storage.set(this.storage_key, {
				'id_max': this.id_max,
				'tms': this.tms,
				'elements': this.elements
			});
		}
	}

	loadStorage() {
		if (bimp_use_local_storage) {
			var data = bimp_storage.get(this.storage_key, 'obj');

			if (data) {
				if (typeof (data.id_max) !== 'undefined' && parseInt(data.id_max) > this.id_max) {
					this.id_max = parseInt(data.id_max);
				}

				this.elements = data.elements;
				this.tms = data.tms;

				if (bimp_debug_notifs) {
					console.log('Notifs "' + this.nom + '" : storage OK');
					console.log(this.elements);
				}
			}
		}
	}

	// Traitements: 

	expand() {
		var instance = this;
		$(this.parent_selector).find('.bimp_notification_dropdown').each(function () {
			if (typeof $(this).attr('is_open') === typeof undefined) {
				$(this).attr('is_open', 0);
			}

			var was_open = parseInt($(this).attr('is_open'));

			// Fermeture
			if (was_open === 1) {
				$(this).slideToggle(200);
				$(this).attr('is_open', 0);
			}

			// Ouverture de la dropdown cliquée si elle n'était pas déjà ouverte
			if ($(this).attr('aria-labelledby') === instance.dropdown_id && was_open === 0) {
				$(this).slideToggle(200);
				$(this).attr('is_open', 1);
			}

		});
	}

	collapse() {
		$(this.parent_selector).find('.bimp_notification_dropdown').each(function () {
			if (typeof $(this).attr('is_open') === typeof undefined) {
				$(this).attr('is_open', 0);
			}

			// Fermeture
			if (parseInt($(this).attr('is_open')) === 1) {
				$(this).slideToggle(200);
				$(this).attr('is_open', 0);
			}
		});
	}

	notificationAction(action, id, data, success_callback = null, refresh_elements = true) {
		var notif = this;
		data['actionNotif'] = action;
		data['id_notification'] = this.id;
		data['id'] = id;

		BimpAjax('notificationAction', data, null, {
			display_success: false,
			notif: notif,
			refresh_elements: refresh_elements,
			success: function (result, bimpAjax) {
				if (typeof (success_callback) === 'function') {
					success_callback(result, bimpAjax);
				}

				if (bimpAjax.refresh_elements) {
					bimpAjax.notif.refreshElements();
				}
			}
		});
	}

	setAsViewed(key, id) {
		var ptr = this;
		this.notificationAction('setAsViewed', id, {});
	}

	isViewed(key) {
		$('div.list_part[key="' + key + '"] span.nonLu').removeClass('nonLu');
	}

	formatDate(input) {
		input = input.replace('-', '/').replace('-', '/');
		var m = new Date(input);
		return ("0" + m.getDate()).slice(-2) + "/" +
			("0" + (m.getMonth() + 1)).slice(-2) + "/" +
			m.getFullYear() + " " +
			("0" + m.getHours()).slice(-2) + ":" +
			("0" + m.getMinutes()).slice(-2);
	}

	setCountSpanValue(count) {
		var $span = $('a#' + this.dropdown_id + ' > span.badge.bg-danger');

		if ($span.length) {
			if (count > 0) {
				$span.html(count);
			} else {
				$span.remove();
			}
		} else if (count > 0) {
			$('a#' + this.dropdown_id).append('<span class="badge bg-danger">' + count + '</span>');
		}
	}

	isNew() {
		return 1;
	}

	getKey(element) {
		return element.id;
	}

	getBoutonReload() {
		return '<span dropdown_id="' + this.dropdown_id + '" name="reload_notif" class="' + this.nom + '_reload_btn reload_notif_btn"><i class="fas fa5-redo-alt"></i></span><span class="' + this.nom + '_loading_spin loading-spin reload_notif_spinner"><i class="fa fa-spinner fa-spin"></i></span>';
	}

	getLabel() {
		return 'Notifications';
	}

	showLoading() {
		$('.' + this.nom + '_reload_btn').hide();
		$('.' + this.nom + '_loading_spin').show();
	}

	hideLoading() {
		$('.' + this.nom + '_reload_btn').show();
		$('.' + this.nom + '_loading_spin').hide();
	}

	checkBrowserNotification() {
		var data = {};

		if (bimp_use_local_storage) {
			var data = bimp_storage.get(this.storage_key, 'obj');

			if (data) {
				if (typeof (data.id_max) !== 'undefined' && parseInt(data.id_max) > this.id_max) {
					this.id_max = parseInt(data.id_max);
				}
			}
		}

		var new_elements = [];
		var new_id_max = 0;

		for (var i in this.elements) {
			if (this.id_max && this.isNew(this.elements[i]) && parseInt(this.elements[i].id) > parseInt(this.id_max)) {
				new_elements.push(this.elements[i]);
			}

			if (parseInt(this.elements[i].id) > parseInt(new_id_max)) {
				new_id_max = parseInt(this.elements[i].id);
			}
		}

		this.id_max = new_id_max;

		if (new_elements.length) {
			this.sendBrowserNotification(new_elements);
		}
	}

	processBrowserNotification(elements) {
	}
}

function BimpNotifications() {
	this.id = getRandomInt(9999999999999);
	this.active = true;
	this.hold = false;
	this.processing = false;
	this.is_first_iteration = true;
	this.nb_iterations = 0;
	this.notifications = [];
	var bn = this;

	// Ajout du panneau rouge si notifications non activée
//    if (Notification.permission !== "granted") {
//        $('div.dropdown.modifDropdown:last').prepend();
//    }

	this.reload = function (reiterate = true, id_notification_type = 0) {
		if (!bn.active || bn.processing || bn.hold) {
			bn.iterate();
			return;
		}
		
		var tms = new Date();
		bimp_storage.set('notifs_last_refresh_tms', Math.floor(tms.getTime() / 1000))

		bn.processing = true;
		var data = {
			randomId: bn.id,
			notifs_data: []
		};

		if (bimp_debug_notifs) {
			if (id_notification_type) {
				console.log('RELOAD USER NOTIFS (ID NOTIF : ' + id_notification_type + ')');	
			} else {
				console.log('RELOAD ALL USER NOTIFS');
			}
		}

		var full_reload = (id_notification_type || bn.is_first_iteration ? true : false);

		if (!full_reload) {
			// Permettre un reload complet toutes les 5 itérations
			bn.nb_iterations++;
			if (bn.nb_iterations >= 5) {
				full_reload = true;
			}
		}

		if (full_reload) {
			bn.nb_iterations = 0;
		}

		for (var i in this.notifications) {
			if (!id_notification_type || (id_notification_type == this.notifications[i].id)) {
				var notif_data = {
					id_notification: this.notifications[i].id
				};

				if (!full_reload) {
					notif_data.tms = this.notifications[i].tms;
				}

				this.notifications[i].showLoading();
				data.notifs_data.push(notif_data);
			}
		}

		data.date_start = date_start;

		BimpAjax('getUserNotifications', data, null, {
			display_success: false,
			display_errors: false,
			display_warnings: false,
			display_debug_content: false,
			reiterate: reiterate,
			full_reload: full_reload,
			success: function (result, bimpAjax) {
				if (bimp_debug_notifs) {
					console.log('--- Reload notifs result : ---');
					console.log(result);
				}

				bn.processing = false;
				bn.is_first_iteration = false;

				if (result.notifications) {
					for (const [id, value] of Object.entries(result.notifications)) {
						bn.notifications[id].hideLoading();
						bn.notifications[id].setNewElements(value, bimpAjax.full_reload);
					}
				}

				if (bimpAjax.reiterate) {
					bn.iterate();
				}
			},
			error: function (result, bimpAjax) {
				bn.processing = false;
				for (var i in this.notifications) {
					this.notifications[i].hideLoading();
				}

				if (bimpAjax.reiterate) {
					bn.iterate();
				}
			}
		});
	};

	this.iterate = function () {
		if (bn.hold || bn.processing || !bn.active) {
			setTimeout(function () {
				bn.iterate();
			}, bimp_notifications_refresh_delay * 1000);
			return;
		}

		var last_refresh_tms = bimp_storage.get('notifs_last_refresh_tms', 'text');

		var tms = new Date();
		tms.setSeconds(tms.getSeconds() - bimp_notifications_refresh_delay);

		if (!last_refresh_tms || last_refresh_tms < Math.floor(tms.getTime() / 1000)) {
			bn.reload();
			return;
		} else {
			var delay = last_refresh_tms - Math.floor(tms.getTime() / 1000) + 1;
			if (bimp_debug_notifs) {
				console.log('RELOAD USER NOTIFS TIMEOUT : ' + delay + ' sec.');
			}

			setTimeout(function () {
				bn.iterate();
			}, delay * 1000);
		}
	};

	this.addButtonAllowNotification = function () {
		if ($('#allow_notif').length === 0) {
			var button = '<span id="allow_notif" class="headerIconButton header-icon bs-popover nav-link" data-toggle="popover" data-trigger="hover" data-container="body" data-placement="bottom" data-content="Autoriser les notifications">';
			button += '<i class="fas fa5-exclamation-triangle"></i></span>';
			$('div.login_block_other').prepend(button);
			$('#allow_notif').popover();
			$(this).click(function () {
				$('#allow_notif').popover('hide');
			});
		}
		$('#allow_notif').click(function () {
			Notification.requestPermission();
		});
	};

	this.removeButtonAllowNotification = function () {
		$('#allow_notif').remove();
	};

	this.onWindowLoaded = function () {
		var bn = this;

		if (typeof navigator.permissions === "object") {
			navigator.permissions.query({name: 'notifications'})
				.then(function (permission_status) {
					permission_status.onchange = function () {
						if (Notification.permission !== "granted")
							bn.addButtonAllowNotification();
						else
							bn.removeButtonAllowNotification();
					};
				});
		}

		var now = new Date();
		date_start = now.getFullYear() + "-" +
			("0" + (now.getMonth() + 1)).slice(-2) + "-" +
			("0" + now.getDate()).slice(-2) + " " +
			("0" + now.getHours()).slice(-2) + ":" +
			("0" + now.getMinutes()).slice(-2) + ":" +
			("0" + now.getSeconds()).slice(-2);


		// Premièrement, vérifions que nous avons la permission de publier des notifications. Si ce n'est pas le cas, demandons-la
		if (window.Notification && Notification.permission !== "granted") {

			this.addButtonAllowNotification();

			// Si l'utilisateur accepte d'être notifié
			if (window.Notification && Notification.permission === "granted") {
				var n = new Notification("Titre", {
					body: 'body'
				});
			}

				// Si l'utilisateur n'a pas choisi s'il accepte d'être notifié
			// Note: à cause de Chrome, nous ne sommes pas certains que la propriété permission soit définie, par conséquent il n'est pas sûr de vérifier la valeur par défaut.
			else if (window.Notification && Notification.permission !== "denied") {
//                    if (Notification.permission !== status) {
//                        Notification.permission = status;
//                    }
//
//                    // Si l'utilisateur est OK
//                    if (status === "granted") {
//                        var n = new Notification("Titre", {
//                            body: 'body2'
//                        });
//                    }
//
//                    // Sinon, revenons en à un mode d'alerte classique
//                    else {
//                        $('div.dropdown.modifDropdown:last').prepend('<span class="headerIconButton bs-popover" data-toggle="popover" data-trigger="hover" data-container="body" data-placement="bottom" data-content="Veuillez autoriser les notifications" data-html="false" data-original-title="" title=""><i style="color: #A00000" class="fas fa-warning"></i></span>');
////                        alert("Vous n'etes pas notifié (pas recommandé), merci d'autoriser les notifications pour ce site");
//                    }
//                });
			}

			// Si l'utilisateur refuse d'être notifié
			else {
				this.addButtonAllowNotification();
//                alert("Vous ne serez pas notifié (pas recommandé)");
			}
		}

		// Variable définie coté PHP (BimpCore::getJsVars())
		if (typeof (bimp_notifications_actives) !== 'undefined') {
			$.ajaxSetup({
				cache: true
			});
			for (const [id_notification, value] of Object.entries(bimp_notifications_actives)) {
				$.getScript(dol_url_root + '/' + value.module + '/views/js/' + value.nom + '.js', function () {
					eval('bimp_notifications.notifications[' + value.id_notification + '] = new ' + value.nom + '(' + value.id_notification + ', \'' + value.storage_key + '\');');
				});
			}
		}

		bn.iterate();

		if (!parseInt($(window).data('focus_bimp_notification_event_init'))) {
			document.addEventListener('visibilitychange', function () {
				if (document.hidden) {
					if (bimp_debug_notifs) {
						console.log('NOTIFS ONGLET DÉSACTIVÉES');
					}
					bn.active = false;

					setTimeout(function () {
						// Aucun autre onglet n'a prit le lead
						if (parseInt(bimp_storage.get('id_bn_actif')) === bn.id) {
							bn.active = true;
						}
					}, 1000);

				} else {
					if (bimp_debug_notifs) {
						console.log('NOTIFS ONGLET ACTIVÉES');
					}
					bn.active = true;
					bn.updateStorage();

					if (bimp_use_local_storage) {
						// Actualisations depuis le storage :
						if (bimp_debug_notifs) {
							console.log('ACTUALISATION NOTIFS DEPUIS STORAGE');
						}
						for (var i in bn.notifications) {
							bn.notifications[i].loadStorage();
							if (bn.notifications[i].elements.length) {
								bn.notifications[i].renderElements();
							}
						}
					}
				}
			});

			$(window).data('focus_bimp_notification_event_init', 1);
		}

		$('body').data('bimp_notification_events_init', 1);
	};

	this.updateStorage = function () {
		bimp_storage.set('id_bn_actif', this.id);
	};
}

var bimp_notifications = new BimpNotifications();
//var bimp_storage = new BimpStorage(); // Déplacé dans bimpcore.js

$(document).ready(function () {
	$('a#notiDropdown').hide();
	bimp_notifications.updateStorage();
	bimp_notifications.onWindowLoaded();
});
