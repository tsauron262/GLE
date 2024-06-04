ALTER table llx_societe_atradius ADD outstanding_limit_manuel_old DECIMAL(24,8) DEFAULT 0 NOT NULL;
UPDATE llx_societe_atradius SET outstanding_limit_manuel_old = outstanding_limit_manuel;