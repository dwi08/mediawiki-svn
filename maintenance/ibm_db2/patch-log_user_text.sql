CREATE TABLE logging (
  log_id			BIGINT      NOT NULL PRIMARY KEY GENERATED BY DEFAULT AS IDENTITY (START WITH 1),
  --PRIMARY KEY DEFAULT nextval('log_log_id_seq'),
  log_type			VARCHAR(32)         NOT NULL,
  log_action		VARCHAR(32)         NOT NULL,
  log_timestamp		TIMESTAMP(3)  NOT NULL,
  log_user			BIGINT NOT NULL DEFAULT 0,
  --                REFERENCES user(user_id) ON DELETE SET NULL,
  -- Name of the user who performed this action
  log_user_text		VARCHAR(255) NOT NULL default '',
  log_namespace		SMALLINT     NOT NULL,
  log_title			VARCHAR(255)         NOT NULL,
  log_page			BIGINT,
  log_comment		VARCHAR(255),
  log_params		CLOB(64K) INLINE LENGTH 4096,
  log_deleted		SMALLINT     NOT NULL DEFAULT 0
);
