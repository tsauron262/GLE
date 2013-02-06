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
   $sql = "SELECT id,
                       month(periode) as mperiode,
                       year(periode) as yperiode,
                       periode as rperiode,
                       UNIX_TIMESTAMP(periode) as tsperiode,
                       fk_user_author,
                       fk_user_valid,
                       statut,
                       total ,
                       total_km ,
                       total_ttc ,
                       date_valid as date_valid
                       ";
        $sql.= " FROM Babel_ndf ";
        $sql.= " WHERE id = ".$id  ;
print $sql;
        dol_syslog("Ndf::fetch sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql) ;
        if ( $result )
        {
            $obj = $this->db->fetch_object($result);

            $this->id       = $obj->id;
            $this->periode     = $obj->rperiode;
            $this->tsperiode     = $obj->tsperiode;
            $this->periode_month     = $obj->mperiode;
            $this->periode_year     = $obj->yperiode;
            $this->fk_user_author  = $obj->fk_user_author;
            $this->fk_user_valid  = $obj->fk_user_valid;
            $this->total       = $obj->total;
            $this->total_km       = $obj->total_km;
            $this->total_ttc       = $obj->total_ttc;
            $this->ref = $obj->fk_user_author."_".$obj->yperiode.$obj->mperiode;
            $this->statut     = $obj->statut;

}
?>
