<?php

/**
 * Class for cooperative locking of web resources
 *
 * Each resource is identified by a combination of the "resource type" (the application, the type
 * of content, etc), and the resource's primary key or some other unique numeric ID.
 *
 * Currently, a resource can only be checked out by a single user.  Other attempts to check it out result
 * in the checkout failing.  In the future, an option for multiple simulataneous checkouts could be added
 * without much trouble.
 *
 * This could be done with named locks, except then it would be impossible to build a list of all the
 * resources currently checked out for a given application.  There's no good way to construct a query
 * that answers the question, "What locks do you have starting with [foo]"  This could be done really well
 * with a concurrent, reliable, distributed key/value store, but we don't have one of those right now.
 *
 * @author Ian Baker <ian@wikimedia.org>
 */
class ConcurrencyCheck {


	// TODO: docblock
	public function __construct( $resourceType, $user, $expirationTime = null ) {

		// All database calls are to the master, since the whole point of this class is maintaining
		// concurrency. Most reads should come from cache anyway.
		$this->dbw = wfGetDb( DB_MASTER );

		$this->user = $user;
		$this->resourceType = $resourceType;
		$this->setExpirationTime( $expirationTime );
	}

	/**
	 * Check out a resource.  This establishes an atomically generated, cooperative lock
	 * on a key.  The lock is tied to the current user.
	 *
	 * @var $record Integer containing the record id to check out
	 * @var $override Boolean (optional) describing whether to override an existing checkout
	 * @return boolean
	 */
	public function checkout( $record, $override = null ) {
		$this->validateId( $record );
		$dbw = $this->dbw;
		$userId = $this->user->getId();

		// attempt an insert, check success (this is atomic)
		$insertError = null;
		$res = $dbw->insert(
			'concurrencycheck',
			array(
				'cc_resource_type' => $this->resourceType,
				'cc_record' => $record,
				'cc_user' => $userId,
				'cc_expiration' => time() + $this->expirationTime,
			),
			__METHOD__,
			array('IGNORE')				
		);

		// if the insert succeeded, checkout is done.
		if( $dbw->affectedRows() === 1 ) {
			//TODO: delete cache key
			return true;
		}

		$dbw->begin();
		$row = $dbw->selectRow(
			'concurrencycheck',
			array( 'cc_user', 'cc_expiration' ),
			array(
				'cc_resource_type' => $this->resourceType,
				'cc_record' => $record,
			),
			__METHOD__,
			array()
		);
		
		// not checked out by current user, checkout is unexpired, override is unset
		if( ! ( $override || $row->cc_user == $userId || $row->cc_expiration <= time() ) ) {
			$dbw->rollback();
			return false;
		}

		// execute a replace
		$res = $dbw->replace(
			'concurrencycheck',
			array( array('cc_resource_type', 'cc_record') ),
			array(
				'cc_resource_type' => $this->resourceType,
				'cc_record' => $record,
				'cc_user' => $userId,
				'cc_expiration' => time() + $this->expirationTime,
			),
			__METHOD__
		);

		// TODO cache the result.
		
		$dbw->commit();
		return true;
		
		// if insert succeeds, delete the cache key.  don't make a new one since they have to be created atomically.
		//
		// if insert fails:
		// begin transaction
		// select where key=key and expiration > now()
		// if row is missing or user matches:
		//   execute a replace()
		//   overwrite the cache key (might as well, since this is inside a transaction)
		// commit
		// if select returned an unexpired row owned by someone else, return failure.
				
		// optional: check to see if the current user already has the resource checked out, and if so,
		// return that checkout information instead. (does anyone want that?)
	}

	/**
	 * Check in a resource. Only works if the resource is checked out by the current user.
	 *
	 * @var $record Integer containing the record id to checkin
	 * @return Boolean
	 */
	public function checkin( $record ) {
		$this->validateId( $record );		
		$dbw = $this->dbw;
		$userId = $this->user->getId();
		
		$dbw->delete(
			'concurrencycheck',
			array(
				'cc_resource_type' => $this->resourceType,
				'cc_record' => $record,
				'cc_user' => $userId,  // only the owner can perform a checkin
			),
			__METHOD__,
			array()
		);
		
		// check row count (this is atomic, select would not be)
		if( $dbw->affectedRows() === 1 ) {
			// TODO: remove record from cache
			return true;
		}
		
		return false;
		
		// delete the row, specifying the username in the where clause (keeps users from checking in stuff that's not theirs).
		// if a row was deleted:
		//   remove the record from memcache.  (removing cache key doesn't require atomicity)
		//   return true
		// else
		//   return false
		
	}

	/**
	 * Remove all expired checkouts.
	 *
	 * @return Integer describing the number of records expired.
	 */
	public function expire() {
		$dbw = $this->dbw;
		
		$now = time();
		
		// get the rows to remove from cache.
		$res = $dbw->select(
			'concurrencycheck',
			array( '*' ),
			array(
				'cc_expiration <= ' . $now,
			),
			__METHOD__,
			array()
		);
				
		// remove the rows from the db
		$dbw->delete(
			'concurrencycheck',
			array(
				'cc_expiration <= ' . $now,
			),
			__METHOD__,
			array()
		);
		
		// TODO: fetch the rows here, remove them from cache.

		// return the number of rows removed.
		return $dbw->affectedRows();

		// grab a unixtime.
		// select all rows where expiration < time
		// delete all rows where expiration < time
		// remove selected rows from memcache
		//
		//     previous idea, probably wrong:
		// select all expired rows.
		// foreach( expired )
		//   delete row where id=id and expiration < now()  (accounts for updates)
		//   if delete succeeded, remove cache key  (txn not required, since removing cache key doesn't require atomicity)
		//   (sadly, this must be many deletes to coordinate removal from memcache)
		//   (is it necessary to remove expired cache entries?)
	}
	
	public function status( $keys ) {
		$dbw = $this->dbw;
		
		// validate keys.
		foreach( $keys as $key ) {
			$this->validateId( $key );
		}

		$checkouts = array();

		// TODO: check cache before selecting

		// check for each of $keys in cache (also check expiration)
		// build a list of the missing ones.
		// run the below select with that list.
		// when finished, re-add any missing keys with the 'invalid' status.

		$this->expire();

		$dbw->begin();
		$res = $dbw->select(
			'concurrencycheck',
			array( '*' ),
			array(
				'cc_resource_type' => $this->resourceType,
				'cc_record IN (' . implode( ',', $keys ) . ')',
				'cc_expiration > unix_timestamp(now())'
			),
			__METHOD__,
			array()
		);
		
		while( $res && $record = $res->fetchRow() ) {
			$record['status'] = 'valid';
			# cache the row.
			$checkouts[ $record['cc_record'] ] = $record;
		}
		
		// if a key was passed in but has no (unexpired) checkout, include it in the
		// result set to make things easier and more consistent on the client-side.
		foreach( $keys as $key ) {
			if( ! array_key_exists( $key, $checkouts ) ) {
				$checkouts[$key]['status'] = 'invalid';
			}
		}
		
		return $checkouts;
		
		// fetch keys from cache or db (keys are an array)
		//
		// for all unexpired keys present in cache, store cached return value for returning later.
		//
		// if some keys remain (missing from cache or expired):
		// execute expire()	to make sure db records are cleared
		// for all remaining keys:
		//  begin transaction
		//  select rows where key in (keys) and expiration > now()
		//  overwrite any memcache entry
		//  commit
		//  return values that were added to cache, plus values pulled from cache
	}
	
	public function list() {
		
	}
	
	public function setUser ( $user ) {
		$this->user = $user;
	}
	
	public function setExpirationTime ( $expirationTime = null ) {
		// check to make sure the time is digits only, so it can be used in queries
		// negative number are allowed, though mostly only used for testing
		// TODO: define maximum and minimum times in configuration, to prevent DoS
		if( $expirationTime && preg_match('/^[\d-]+$/', $expirationTime) ) {
			$this->expirationTime = $expirationTime; // the amount of time before a checkout expires.
		} else {
			$this->expirationTime = 60 * 15; // 15 mins. TODO: make a configurable default for this.
		}
	}

	/**
	 * Check to make sure a record ID is numeric, throw an exception if not.
	 *
	 * @var $record Integer
	 * @throws ConcurrencyCheckBadRecordIdException
	 * @return boolean
	 */
	private static function validateId ( $record ) {
		if( ! preg_match('/^\d+$/', $record) ) {
			throw new ConcurrencyCheckBadRecordIdException( 'Record ID ' . $record . ' must be a positive integer' );
		}
		return true;
	}
}

class ConcurrencyCheckBadRecordIdException extends MWException {};
