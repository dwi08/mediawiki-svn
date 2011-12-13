-- Table to handle resource locking (EX) with row-level locking
CREATE TABLE /*_*/filelocks_exclusive (
	fle_key binary(40) NOT NULL default '' PRIMARY KEY
) ENGINE=InnoDB, CHECKSUM=0;

-- Table to handle resource locking (SH) with row-level locking
CREATE TABLE /*_*/filelocks_shared (
	fls_key binary(40) NOT NULL default '',
	fls_session integer unsigned NOT NULL default 0,
	PRIMARY KEY (fls_key,fls_session)
) ENGINE=InnoDB, CHECKSUM=0;
