<?php
# Database load balancing object

require_once( "Database.php" );

# Valid database indexes
# Operation-based indexes
define( "DB_SLAVE", -1 );     # Read from the slave (or only server)
define( "DB_MASTER", -2 );    # Write to master (or only server)
define( "DB_LAST", -3 );     # Whatever database was used last

# Obsolete aliases
define( "DB_READ", -1 );
define( "DB_WRITE", -2 );

# Task-based indexes
# ***NOT USED YET, EXPERIMENTAL***
# These may be defined in $wgDBservers. If they aren't, the default reader or writer will be used
# Even numbers are always readers, odd numbers are writers
define( "DB_TASK_FIRST", 1000 );  # First in list
define( "DB_SEARCH_R", 1000 );    # Search read
define( "DB_SEARCH_W", 1001 );    # Search write
define( "DB_ASKSQL_R", 1002 );    # Special:Asksql read
define( "DB_WATCHLIST_R", 1004 ); # Watchlist read
define( "DB_TASK_LAST", 1004) ;   # Last in list

define( "MASTER_WAIT_TIMEOUT", 15 ); # Time to wait for a slave to synchronise

class LoadBalancer {
	/* private */ var $mServers, $mConnections, $mLoads;
	/* private */ var $mFailFunction;
	/* private */ var $mForce, $mReadIndex, $mLastConn;
	/* private */ var $mWaitForFile, $mWaitForPos;

	function LoadBalancer()
	{
		$this->mServers = array();
		$this->mConnections = array();
		$this->mFailFunction = false;
		$this->mReadIndex = -1;
		$this->mForce = -1;
		$this->mLastConn = false;
	}

	function newFromParams( $servers, $failFunction = false )
	{
		$lb = new LoadBalancer;
		$lb->initialise( $servers, $failFunction = false );
		return $lb;
	}

	function initialise( $servers, $failFunction = false )
	{
		$this->mServers = $servers;
		$this->mFailFunction = $failFunction;
		$this->mReadIndex = -1;
		$this->mWriteIndex = -1;
		$this->mForce = -1;
		$this->mConnections = array();
		$this->mLastConn = false;
		$this->mLoads = array();
		$this->mWaitForFile = false;
		$this->mWaitForPos = false;

		foreach( $servers as $i => $server ) {
			$this->mLoads[$i] = $server['load'];
		}
	}
	
	# Given an array of non-normalised probabilities, this function will select
	# an element and return the appropriate key
	function pickRandom( $weights )
	{
		if ( !is_array( $weights ) || count( $weights ) == 0 ) {
			return false;
		}

		$sum = 0;
		foreach ( $weights as $w ) {
			$sum += $w;
		}
		$max = mt_getrandmax();
		$rand = mt_rand(0, $max) / $max * $sum;
		
		$sum = 0;
		foreach ( $weights as $i => $w ) {
			$sum += $w;
			if ( $sum >= $rand ) {
				break;
			}
		}
		return $i;
	}

	function &getReader()
	{
		if ( $this->mForce >= 0 ) {
			$conn =& $this->getConnection( $this->mForce );
		} else {
			if ( $this->mReadIndex >= 0 ) {
				$conn =& $this->getConnection( $this->mReadIndex );
			} else {
				# $loads is $this->mLoads except with elements knocked out if they
				# don't work
				$loads = $this->mLoads;
				do {
					$i = $this->pickRandom( $loads );
					if ( $i !== false ) {
						wfDebug( "Using reader #$i: {$this->mServers[$i]['host']}\n" );

						$conn =& $this->getConnection( $i );
						if ( !$conn->isOpen() ) {
							unset( $loads[$i] );
						}
					}
				} while ( $i !== false && !$conn->isOpen() );
				if ( $conn->isOpen() ) {
					$this->mReadIndex = $i;
				}
			}
		}
		if ( $conn === false || !$conn->isOpen() ) {
			$this->reportConnectionError( $conn );
			$conn = false;
		}
		return $conn;
	}

	# Set the master wait position
	# If a DB_SLAVE connection has been opened already, waits
	# Otherwise sets a variable telling it to wait if such a connection is opened
	function waitFor( $file, $pos ) {
		$this->mWaitForFile = false;
		$this->mWaitForPos = false;

		if ( count( $this->mServers ) == 1 ) {
			return;
		}
		
		$this->mWaitForFile = $file;
		$this->mWaitForPos = $pos;

		if ( $this->mReadIndex > 0 ) {
			$this->doWait( $this->mReadIndex );
		} 
	}

	# Wait for a given slave to catch up to the master pos stored in $this
	function doWait( $index ) {
		global $wgMemc;
		
		$key = "masterpos:" . $index;
		$memcPos = $wgMemc->get( $key );
		if ( $memcPos ) {
			list( $file, $pos ) = explode( ' ', $memcPos );
			# If the saved position is later than the requested position, return now
			if ( $file == $this->mWaitForFile && $this->mWaitForPos <= $pos ) {
				return;
			}
		}

		$conn =& $this->getConnection( $index );
		$result = $conn->masterPosWait( $this->mWaitForFile, $this->mWaitForPos, MASTER_WAIT_TIMEOUT );
		if ( $result == -1 ) {
			# Timed out waiting for slave, use master instead
			# This is not the ideal solution. If there are a large number of slaves, a slow
			# replicated write query will cause the master to be swamped with reads. However
			# that's a relatively graceful failure mode, so it will do for now.
			$this->mReadIndex = 0;
		} 
	}		

	function &getConnection( $i, $fail = false )
	{
		/*
		# Task-based index
		if ( $i >= DB_TASK_FIRST && $i < DB_TASK_LAST ) {
			if ( $i % 2 ) {
				# Odd index use writer
				$i = DB_MASTER;
			} else {
				# Even index use reader
				$i = DB_SLAVE;
			}
		}*/

		# Operation-based index
		# Note, getReader() and getWriter() will re-enter this function
		if ( $i == DB_SLAVE ) {
			$this->mLastConn =& $this->getReader();
		} elseif ( $i == DB_MASTER ) {
			$this->mLastConn =& $this->getWriter();
		} elseif ( $i == DB_LAST ) {
			# Just use $this->mLastConn, which should already be set
			if ( $this->mLastConn === false ) {
				# Oh dear, not set, best to use the writer for safety
				$this->mLastConn =& $this->getWriter();
			}
		} else {
			# Explicit index
			if ( !array_key_exists( $i, $this->mConnections ) || !$this->mConnections[$i]->isOpen() ) {
				$this->mConnections[$i] = $this->makeConnection( $this->mServers[$i] );
				if ( $i != 0 && $this->mWaitForFile ) {
					$this->doWait( $i );
				}
			}
			if ( !$this->mConnections[$i]->isOpen() ) {
				wfDebug( "Failed to connect to database $i at {$this->mServers[$i]['host']}\n" );
				if ( $fail ) {
					$this->reportConnectionError( $this->mConnections[$i] );
				}
				$this->mConnections[$i] = false;
			}
			$this->mLastConn =& $this->mConnections[$i];
		}
		return $this->mLastConn;
	}

	/* private */ function makeConnection( &$server ) {
			extract( $server );
			# Get class for this database type
			$class = 'Database' . ucfirst( $type );
			if ( !class_exists( $class ) ) {
				require_once( "$class.php" );
			}

			# Create object
			return new $class( $host, $user, $password, $dbname, 1 );
	}
	
	function reportConnectionError( &$conn )
	{
		if ( !is_object( $conn ) ) {
			$conn = new Database;
		}
		if ( $this->mFailFunction ) {
			$conn->setFailFunction( $this->mFailFunction );
		} else {
			$conn->setFailFunction( "wfEmergencyAbort" );
		}
		$conn->reportConnectionError();
	}
	
	function &getWriter()
	{
		$c =& $this->getConnection( 0 );
		if ( $c === false || !$c->isOpen() ) {
			$this->reportConnectionError( $c );
			$c = false;
		}
		return $c;
	}

	function force( $i )
	{
		$this->mForce = $i;
	}

	function haveIndex( $i )
	{
		return array_key_exists( $i, $this->mServers );
	}

	# Get the number of defined servers (not the number of open connections)
	function getServerCount() {
		return count( $this->mServers );
	}

	# Save master pos to the session and to memcached, if the session exists
	function saveMasterPos() {
		global $wgSessionStarted;
		if ( $wgSessionStarted && count( $this->mServers ) > 1 ) {
			# If this entire request was served from a slave without opening a connection to the 
			# master (however unlikely that may be), then we can fetch the position from the slave.
			if ( empty( $this->mConnections[0] ) ) {
				$conn =& $this->getConnection( DB_SLAVE );
				list( $file, $pos ) = $conn->getSlavePos();
			} else {
				$conn =& $this->getConnection( 0 );
				list( $file, $pos ) = $conn->getMasterPos();
			}
			if ( $file !== false ) {
				$_SESSION['master_log_file'] = $file;
				$_SESSION['master_pos'] = $pos;
			}
		}
	}

	# Loads the master pos from the session, waits for it if necessary
	function loadMasterPos() {
		if ( isset( $_SESSION['master_log_file'] ) && isset( $_SESSION['master_pos'] ) ) {
			$this->waitFor( $_SESSION['master_log_file'], $_SESSION['master_pos'] );
		}
	}
}
