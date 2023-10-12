SELECT

c.rowid as idCommande,

c.ref as ref_commande,

c.date_commande,

cl.id_line as idLigne_commande,

p.ref as ref_produit,

f.ref as ref_facture,

f.datef as date_facture,

s.id as id_livraison,

s.date_shipped as date_livraison,

sl.qty as qte_livraison,

fd.qty as qte_facture,

cd.total_ht as 'Total HT commande',

cl.qty_shipped_not_billed,

s.id_facture,

fd.total_ht as 'Total HT facture'

FROM llx_bimp_commande_line cl

LEFT JOIN llx_commande c ON cl.id_obj = c.rowid

LEFT join llx_commandedet cd on cl.id_line = cd.rowid

LEFT join llx_product p on cd.fk_product = p.rowid

LEFT JOIN llx_bl_shipment_line sl on sl.id_commande_line = cl.id

LEFT JOIN llx_bl_commande_shipment s ON s.id = sl.id_shipment

left join llx_bimp_facture_line fl on cl.id = fl.linked_id_object

left join llx_facture f on fl.id_obj = f.rowid

left join llx_facturedet fd on fd.rowid = fl.id_line

WHERE

cl.type IN (1,3)

AND c.fk_statut > 0 

AND DATE(s.date_shipped) <= '2023-09-30 23:59:59'

AND (DATE(f.datef) > '2023-09-30'or f.datef is null)

AND c.ref = 'CO2309-00478';