CREATE TABLE /*$wgDBprefix*/efact (
	efact_id int NOT NULL auto_increment,
	efact_address varchar(255) NOT NULL,
	efact_by varchar(255) NOT NULL,
	efact_reason TINYBLOB NOT NULL,
	efact_timestamp binary(14) NOT NULL,
	efact_time_start varbinary(32) NOT NULL,
	efact_time_end varbinary(32) NOT NULL,
	
	PRIMARY KEY efact_id (efact_id),

	INDEX efact_address (efact_address),

	INDEX efact_end_time (efact_time_start, efact_time_end),
	INDEX efact_timestamp (efact_timestamp)
) /*$wgDBTableOptions*/;
