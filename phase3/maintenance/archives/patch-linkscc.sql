--
-- linkscc table used to cache link lists in easier to digest form
-- November 2003
--

DROP TABLE IF EXISTS linkscc;
CREATE TABLE IF NOT EXISTS linkscc (
  lcc_pageid INT UNSIGNED NOT NULL UNIQUE KEY,
  lcc_title VARCHAR(255) binary NOT NULL UNIQUE KEY,
  lcc_cacheobj MEDIUMBLOB NOT NULL) TYPE=InnoDB;

