<?php
/*
  * GLE by Babel-Services
  *
  * Author: Jean-Marc LE FEVRE <jm.lefevre@babel-services.com>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.babel-services.com
  *
  *//*
 */

/**
        \file       htdocs/core/boxes/zimbra.php
        \ingroup    external_rss
        \brief      Fichier de gestion d'une box pour le module external_rss
        \version    $Id: zimbra.php,v 1.24 2008/05/02 20:24:38 eldy Exp $
*/

include_once(MAGPIERSS_PATH."rss_fetch.inc");
include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");


class box_zimbra extends ModeleBoxes {

    var $boxcode="lastrssinfos";
    var $boximg="object_rss";
    var $boxlabel;
    var $depends = array();

    var $db;
    var $param;

    var $info_box_head = array();
    var $info_box_contents = array();

    /**
     *      \brief      Constructeur de la classe
     */
    function box_zimbra($DB,$param)
    {
        global $langs;
        $langs->load("boxes");

        $this->db=$DB;
        $this->param=$param;

        $this->boxlabel=$langs->trans("BoxLastRssInfos");
    }

    /**
     *      \brief      Charge les donnees en memoire pour affichage ulterieur
     *      \param      $max        Nombre maximum d'enregistrements a charger
     */
    function loadBox($max=5)
    {
        global $user, $langs, $conf;
        $langs->load("boxes");
        // On recupere numero de param de la boite
        preg_replace('/^([0-9]+) /',$this->param,$reg);
        $site=$reg[1];

        // Creation rep (pas besoin, on le cree apres recup flux)
        // documents/rss is created by module activation
        // documents/rss/tmp is created by magpie
        //$result=create_exdir($conf->externalrss->dir_temp);


        // Recupere flux RSS definie dans EXTERNAL_RSS_URLRSS_$site
        $url=@constant("EXTERNAL_RSS_URLRSS_".$site);
        //define('MAGPIE_DEBUG',1);

        if ($this->param == "ZimbraRSS"){
            $url = "http://eos:redalert@10.91.130.6/zimbra/user/eos/inbox.rss";
            $site = "Zimbra";
        }
        $rss=fetch_rss($url);
        if (! is_object($rss))
        {
            dolibarr_syslog("FETCH_RSS site=".$site);
            dolibarr_syslog("FETCH_RSS url=".$url);
            return -1;
        }

        // INFO sur le channel
        $description=$rss->channel['tagline'];
        $link=$rss->channel['link'];

        $title=$langs->trans("BoxTitleLastRssInfos",$max, @constant("EXTERNAL_RSS_TITLE_". $site));
        if ($rss->ERROR)
        {
            //var_dump($rss);
            // Affiche warning car il y a eu une erreur
            $title.=" ".img_error($langs->trans("FailedToRefreshDataInfoNotUpToDate",(isset($rss->date)?dol_print_date($rss->date,"dayhourtext"):$langs->trans("Unknown"))));
            $this->info_box_head = array('text' => $title,'limit' => 0);
        }
        else
        {
            $this->info_box_head = array('text' => $title,
                'sublink' => $link, 'subtext'=>$langs->trans("LastRefreshDate").': '.(isset($rss->date)?dol_print_date($rss->date,"dayhourtext"):$langs->trans("Unknown")), 'subpicto'=>'object_bookmark');
        }

        // INFO sur l'elements
        for($i = 0; $i < $max && $i < sizeof($rss->items); $i++)
        {
            $item = $rss->items[$i];

            // Magpierss common fields
            $href  = $item['link'];
            $title = urldecode($item['title']);
            $date  = $item['date_timestamp'];    // date will be empty if conversion into timestamp failed
            if ($rss->is_rss())        // If RSS
            {
                if (! $date && isset($item['pubdate']))    $date=$item['pubdate'];
                if (! $date && isset($item['dc']['date'])) $date=$item['dc']['date'];
                //$item['dc']['language']
                //$item['dc']['publisher']
            }
            if ($rss->is_atom())    // If Atom
            {
                if (! $date && isset($item['issued']))    $date=$item['issued'];
                if (! $date && isset($item['modified']))  $date=$item['modified'];
                //$item['issued']
                //$item['modified']
                //$item['atom_content']
            }
            if (is_numeric($date)) $date=dol_print_date($date,"dayhour");
            $result = $this->utf8_check($title);
            if ($result)
            {
                $title=utf8_decode($title);
            }
            $title=preg_replace("/([[:alnum:]])\?([[:alnum:]])/","\\1'\\2",$title);   // Gere probleme des apostrophes mal codee/decodee par utf8
            $title=preg_replace("/^\s+/","",$title);                                  // Supprime espaces de debut
            $this->info_box_contents["$href"]="$title";
            $this->info_box_contents[$i][0] = array('align' => 'left',
            'logo' => $this->boximg,
            'text' => $title,
            'url' => $href,
            'maxlength' => 64,
            'target' => 'newrss');
            $this->info_box_contents[$i][1] = array('align' => 'right',
            'text' => $date,
            'td' => 'nowrap="1"');
        }
    }

    /**
     *      \brief      Verifie si le flux est en UTF8
     *      \param      $Str        chaine a verifier
     */
    function utf8_check($Str) {
        for ($i=0; $i<strlen($Str); $i++) {
            if (ord($Str[$i]) < 0x80) continue; # 0bbbbbbb
            elseif ((ord($Str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
            elseif ((ord($Str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
            elseif ((ord($Str[$i]) & 0xF8) == 0xF0) $n=3; # 11110bbb
            elseif ((ord($Str[$i]) & 0xFC) == 0xF8) $n=4; # 111110bb
            elseif ((ord($Str[$i]) & 0xFE) == 0xFC) $n=5; # 1111110b
            else return false; # Does not match any model
            for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
                if ((++$i == strlen($Str)) || ((ord($Str[$i]) & 0xC0) != 0x80))
                return false;
            }
        }
        return true;
    }

    function showBox($head = null, $contents = null)
    {
        $html = parent::showBox($this->info_box_head, $this->info_box_contents);
        return ($html);
    }

}

?>
