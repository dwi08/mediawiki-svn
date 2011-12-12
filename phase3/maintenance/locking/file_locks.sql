-- Table to handle resource locking (EX) with row-level locking
CREATE TABLE /*_*/file_locks_exclusive (
	fle_key binary(40) NOT NULL default '' PRIMARY KEY
) ENGINE=InnoDB, DEFAULT CHARSET=binary;

-- Table to handle resource locking (SH) with row-level locking
CREATE TABLE /*_*/file_locks_shared (
	fls_key binary(40) NOT NULL default ''
) ENGINE=InnoDB, DEFAULT CHARSET=binary;
CREATE INDEX /*i*/fls_key ON /*_*/file_locks_shared (fls_key);
