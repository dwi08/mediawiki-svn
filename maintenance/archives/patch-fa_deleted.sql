-- Adding fa_deleted field for additional content suppression
ALTER TABLE /*$wgDBprefix*/filearchive 
  ADD fa_deleted tinyint(1) unsigned NOT NULL default '0';
