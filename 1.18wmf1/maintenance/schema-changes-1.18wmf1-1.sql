-- patch-up_property.sql
ALTER TABLE /*$wgDBprefix*/user_properties
	MODIFY up_property varbinary(255);
	
