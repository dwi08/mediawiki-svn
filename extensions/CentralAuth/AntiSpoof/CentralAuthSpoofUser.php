<?php

class CentralAuthSpoofUser extends SpoofUser {
	/**
	 * @return DatabaseBase
	 */
	protected static function getDBSlave() {
		return CentralAuthUser::getCentralSlaveDB();
	}

	/**
	 * @return DatabaseBase
	 */
	protected static function getDBMaster() {
		return CentralAuthUser::getCentralDB();
	}

	/**
	 * @return string
	 */
	protected function getTableName() {
		return 'globaluser';
	}

	/**
	 * @return string
	 */
	protected function getUserColumn() {
		return 'gu_name';
	}
}
