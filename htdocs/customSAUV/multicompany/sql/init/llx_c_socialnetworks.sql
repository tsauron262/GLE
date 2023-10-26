-- Copyright (C) 2009-2021 Regis Houssin  <regis.houssin@inodbox.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--


--
-- llx_c_socialnetworks
--
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, '500px', '500px', '{socialid}', 'fa-500px', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'dailymotion', 'Dailymotion', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'diaspora', 'Diaspora', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'discord', 'Discord', '{socialid}', 'fa-discord', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'facebook', 'Facebook', 'https://www.facebook.com/{socialid}', 'fa-facebook', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'flickr', 'Flickr', '{socialid}', 'fa-flickr', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'gifycat', 'Gificat', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'giphy', 'Giphy', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'googleplus', 'GooglePlus', 'https://www.googleplus.com/{socialid}', 'fa-google-plus-g', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'instagram', 'Instagram', 'https://www.instagram.com/{socialid}', 'fa-instagram', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'linkedin', 'LinkedIn', 'https://www.linkedin.com/{socialid}', 'fa-linkedin', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'mastodon', 'Mastodon', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'meetup', 'Meetup', '{socialid}', 'fa-meetup', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'periscope', 'Periscope', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'pinterest', 'Pinterest', '{socialid}', 'fa-pinterest', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'quora', 'Quora', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'reddit', 'Reddit', '{socialid}', 'fa-reddit', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'slack', 'Slack', '{socialid}', 'fa-slack', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'snapchat', 'Snapchat', '{socialid}', 'fa-snapchat', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'skype', 'Skype', 'https://www.skype.com/{socialid}', 'fa-skype', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'tripadvisor', 'Tripadvisor', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'tumblr', 'Tumblr', 'https://www.tumblr.com/{socialid}', 'fa-tumblr', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'twitch', 'Twitch', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'twitter', 'Twitter', 'https://www.twitter.com/{socialid}', 'fa-twitter', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'vero', 'Vero', 'https://vero.co/{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'viadeo', 'Viadeo', 'https://fr.viadeo.com/fr/{socialid}', 'fa-viadeo', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'viber', 'Viber', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'vimeo', 'Vimeo', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'whatsapp', 'Whatsapp', '{socialid}', 'fa-whatsapp', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'wikipedia', 'Wikipedia', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'xing', 'Xing', '{socialid}', 'fa-xing', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'youtube', 'Youtube', 'https://www.youtube.com/{socialid}', 'fa-youtube', 1);

