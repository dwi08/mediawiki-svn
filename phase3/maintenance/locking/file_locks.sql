-- Create table to handle resource locking
CREATE TABLE /*$wgDBprefix*/file_locks (
	fl_key binary(40) NOT NULL default '' PRIMARY KEY
) ENGINE=InnoDB, DEFAULT CHARSET=binary;
