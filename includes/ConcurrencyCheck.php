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
	/**
	 * Constructor
	 * 
	 * @var $resourceType String The calling application or type of resource, conceptually like a namespace
	 * @var $user User object, the current user
	 * @var $expirationTime Integer (optional) How long should a checkout last, in seconds
	 */
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
		$memc = wfGetMainCache();
		$this->validateId( $record );
		$dbw = $this->dbw;
		$userId = $this->user->getId();
		$cacheKey = wfMemcKey( $this->resourceType, $record );

		// when operating with a single memcached cluster, it's reasonable to check the cache here.
		global $wgConcurrencyTrustMemc;
		if( $wgConcurrencyTrustMemc ) {
	        $cached = $memc->get( $cacheKey );
			if( $cached ) {
				if( ! $override && $cached['userId'] != $userId && $cached['expiration'] > time() ) {
					// this is already checked out.
					return false;
				}
			}
		}
		
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
			// delete any existing cache key.  can't create a new key here
			// since the insert didn't happen inside a transaction.
			$memc->delete( $cacheKey );
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
			// this was a cache miss.  populate the cache with data from the db.
			// cache is set to expire at the same time as the checkout, since it'll become invalid then anyway.
			// inside this transaction, a row-level lock is established which ensures cache concurrency
			$memc->set( $cacheKey, array( 'userId' => $row->cc_user, 'expiration' => $row->cc_expiration ), $row->cc_expiration - time() );
			$dbw->rollback();
			return false;
		}

		$expiration = time() + $this->expirationTime;

		// execute a replace
		$res = $dbw->replace(
			'concurrencycheck',
			array( array('cc_resource_type', 'cc_record') ),
			array(
				'cc_resource_type' => $this->resourceType,
				'cc_record' => $record,
				'cc_user' => $userId,
				'cc_expiration' => $expiration,
			),
			__METHOD__
		);

		// cache the result.
		$memc->set( $cacheKey, array( 'userId' => $userId, 'expiration' => $expiration ), $expiration - time() );
		
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
		$memc = wfGetMainCache();
		$this->validateId( $record );		
		$dbw = $this->dbw;
		$userId = $this->user->getId();
		$cacheKey = wfMemcKey( $this->resourceType, $record );
		
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
			$memc->delete( $cacheKey );
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
		$memc = wfGetMainCache();
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
		
		// build a list of rows to delete.
		$toExpire = array();
		while( $res && $record = $res->fetchRow() ) {
			$toExpire[] = $record['cc_record'];
		}
			
		// remove the rows from the db
		$dbw->delete(
			'concurrencycheck',
			array(
				'cc_expiration <= ' . $now,
			),
			__METHOD__,
			array()
		);
		
		// delete all those rows from cache
		// outside a transaction because deletes don't require atomicity.
		foreach( $toExpire as $expire ) {
			$memc->delete( wfMemcKey( $this->resourceType, $expire ) );
		}

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
		$memc = wfGetMainCache();
		$dbw = $this->dbw;
		$now = time();

		$checkouts = array();
		$toSelect = array();

		// maybe run this here?
		//$this->expire();
		
		// validate keys, attempt to retrieve from cache.
		foreach( $keys as $key ) {
			$this->validateId( $key );
			
			$cached = $memc->get( wfMemcKey( $this->resourceType, $key ) );
			if( $cached && $cached['expiration'] > $now ) {
				$checkouts[$key] = array(
					'status' => 'valid',
					'cc_resource_type' => $this->resourceType,
					'cc_record' => $key,
					'cc_user' => $cached['userId'],
					'cc_expiration' => $cached['expiration'],
					'cache' => 'cached',
				);
			} else {
				$toSelect[] = $key;
			}
		}

		// if there were cache misses...
		if( $toSelect ) {
			// the transaction seems incongruous, I know, but it's to keep the cache update atomic.
			$dbw->begin();
			$res = $dbw->select(
				'concurrencycheck',
				array( '*' ),
				array(
					'cc_resource_type' => $this->resourceType,
					'cc_record IN (' . implode( ',', $toSelect ) . ')',
					'cc_expiration > unix_timestamp(now())'
				),
				__METHOD__,
				array()
			);
		
			while( $res && $record = $res->fetchRow() ) {
				$record['status'] = 'valid';
				$checkouts[ $record['cc_record'] ] = $record;
				
				// safe to store values since this is inside the transaction
				$memc->set(
					wfMemcKey( $this->resourceType, $record['cc_record'] ),
					array( 'userId' => $record['cc_user'], 'expiration' => $record['cc_expiration'] ),
					$record['cc_expiration'] - time()
				);
			}

			// end the transaction.
			$dbw->rollback();
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
	
	public function listCheckouts() {
		// fill in the function that lets you get the complete set of checkouts for a given application.
	}
	
	public function setUser ( $user ) {
		$this->user = $user;
	}
	
	public function setExpirationTime ( $expirationTime = null ) {
		global $wgConcurrencyExpirationDefault, $wgConcurrencyExpirationMax, $wgConcurrencyExpirationMin;

		// check to make sure the time is digits only, so it can be used in queries
		// negative number are allowed, though mostly only used for testing
		if( $expirationTime && preg_match('/^[\d-]+$/', $expirationTime) ) {
			if( $expirationTime > $wgConcurrencyExpirationMax ) {
				$this->expirationTime = $wgConcurrencyExpirationMax; // if the number is too high, limit it to the max value.
			} elseif ( $expirationTime < $wgConcurrencyExpirationMin ) {
				$this->expirationTime = $wgConcurrencyExpirationMin; // low limit, default -1 min
			} else {
				$this->expirationTime = $expirationTime; // the amount of time before a checkout expires.
			}
		} else {
			$this->expirationTime = $wgConcurrencyExpirationDefault; // global default is 15 mins.
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
