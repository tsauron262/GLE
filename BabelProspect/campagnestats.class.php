<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */

/**
        \file       htdocs/comm/propal/stats/Campagnestats.class.php
        \ingroup    Campagnes
        \brief      Fichier de la classe de gestion des stats des Campagnes
        \version    $Revision: 1.13 $
*/

include_once DOL_DOCUMENT_ROOT . "/stats.class.php";


/**
        \class      CampagneStats
        \brief      Classe permettant la gestion des stats des Campagnes
*/

class CampagneStats extends Stats
{
  var $db ;
  var $campagneid ;
  var $id ;

  function CampagneStats($DB,$pId)
    {
      $this->db = $DB;
      $this->campagneid = $pId;
      $this->id = $this->campagneid;

    }


  /**
   * Renvoie le nombre de proposition par mois pour une annee donnee
   *
   */
  function getNbByMonth()
  {
    global $user;

    $sql = " SELECT  date_format(p.date_prisecharge,'%d') as dm, count(*) FROM Babel_campagne_societe as p WHERE campagne_refid =".$this->campagneid;
    $sql .= " GROUP BY dm DESC";
//print $sql;
    return $this->_getNbByMonth($sql);
  }
  function _getNbByMonth($sql)
  {
        $result = array();

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $row = $this->db->fetch_row($resql);
                $j = $row[0]; //dm
                $result[$j] = $row[1]; //count
                $i++;
            }
            $this->db->free($resql);
        }

        $data = array();


        return $data;
    }
  }
  /**
   * Renvoie le nombre de Campagne par annee
   *
   */
  function getNbByYear()
  {
    global $user;

    $sql = "SELECT date_format(p.datep,'%Y') as dm, count(*)";
    if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
    if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " WHERE p.fk_statut > 0";
    if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
    if($user->societe_id)
    {
      $sql .= " AND p.fk_soc = ".$user->societe_id;
    }
    $sql .= " GROUP BY dm DESC";

    return $this->_getNbByYear($sql);
  }
  /**
   * Renvoie le nombre de Campagne par mois pour une annee donne
   *
   */
  function getAmountByMonth($year)
  {
    global $user;

    $sql = "SELECT date_format(p.datep,'%m') as dm, sum(p.total_ht)";
    if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
    if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " WHERE date_format(p.datep,'%Y') = $year AND p.fk_statut > 0";
    if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
    if($user->societe_id)
    {
      $sql .= " AND p.fk_soc = ".$user->societe_id;
    }
    $sql .= " GROUP BY dm DESC";

    return $this->_getAmountByMonth($year, $sql);
  }
  /**
   *
   *
   */
  function getAverageByMonth($year)
  {
    global $user;

    $sql = "SELECT date_format(p.datep,'%m') as dm, avg(p.total_ht)";
    if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
    if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " WHERE date_format(p.datep,'%Y') = $year AND p.fk_statut > 0";
    if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
    if($user->societe_id)
    {
      $sql .= " AND p.fk_soc = ".$user->societe_id;
    }
    $sql .= " GROUP BY dm DESC";

    return $this->_getAverageByMonth($year, $sql);
  }
}

?>
