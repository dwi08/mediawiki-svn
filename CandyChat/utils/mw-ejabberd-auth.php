#!/usr/bin/env php -q
<?php

/*

Mediawiki extauth script for ejabberd.

By Ian Baker, Mediawiki Foundation <ian@mediawiki.org>

Based on code by:
Tom MacWright of DevelopmentSeed (tom@developmentseed.com)
LISSY Alexandre, "lissyx" <alexandrelissy@free.fr>

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software andassociated documentation files (the "Software"), to deal in the
Software without restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
Software, and to permit persons to whom the Software is furnished to do so,
subject to thefollowing conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

error_reporting(0);
$auth = new MWJabberAuth();
$auth->play();

//TODO: make this code simpler and cleaner
//TODO: don't spam the syslog so much, or at least set the priority of these messages correctly
//TOOD: make this match MW style

class MWJabberAuth {
	var $debug 		= true; 				      /* Debug mode */
	var $debugfile 	= "/var/log/chat/auth-debug.log";  /* Debug output */
	var $logging 	= true; 				      /* Do we log requests ? */
//	var $logfile 	= "/var/log/chat/auth.log" ;   /* Log file ... */
	/*
	 * For both debug and logging, ejabberd has to be able to write.
	 */
	
	var $jabber_user;   /* This is the jabber user passed to the script. filled by $this->command() */
	var $jabber_pass;   /* This is the jabber user password passed to the script. filled by $this->command() */
	var $jabber_host; /* This is the jabber server passed to the script. filled by $this->command(). Useful for VirtualHosts */
	var $jid;           /* Simply the JID, if you need it, you have to fill. */
	var $data;          /* This is what SM component send to us. */
    
	var $dateformat = "M d H:i:s"; /* Check date() for string format. */
	var $command; /* This is the command sent ... */
	var $mysock;  /* MySQL connection ressource */
	var $stdin;   /* stdin file pointer */
	var $stdout;  /* stdout file pointer */

	function __construct()
	{
		@define_syslog_variables();
		@openlog("chat-auth", LOG_NDELAY | LOG_PID , LOG_DAEMON);
		
		if($this->debug) {
			@error_reporting(E_ALL);
//			@ini_set("log_errors", "1");
//			@ini_set("error_log", $this->debugfile);
		}
		$this->logg("Starting mediawiki-auth ..."); // We notice that it's starting ...
		$this->openstd();
	}
	
	function stop()
	{
		$this->logg("Shutting down ..."); // Sorry, have to go ...
		closelog();
		$this->closestd(); // Simply close files
		exit(0); // and exit cleanly
	}
	
	function openstd()
	{
		$this->stdout = @fopen("php://stdout", "w"); // We open STDOUT so we can read
		$this->stdin  = @fopen("php://stdin", "r"); // and STDIN so we can talk !
	}
	
	function readstdin()
	{
		$l      = @fgets($this->stdin, 3); // We take the length of string
		$length = @unpack("n", $l); // ejabberd give us something to play with ...
		$len    = $length["1"]; // and we now know how long to read.
		if($len > 0) { // if not, we'll fill logfile ... and disk full is just funny once
			$this->logg("Reading $len bytes ... "); // We notice ...
			$data   = @fgets($this->stdin, $len+1);
			// $data = iconv("UTF-8", "ISO-8859-15", $data); // To be tested, not sure if still needed.
			$this->data = $data; // We set what we got.
			$this->logg("IN: ".$data);
		}
	}
	
	function closestd()
	{
		@fclose($this->stdin); // We close everything ...
		@fclose($this->stdout);
	}
	
	function out($message)
	{
		@fwrite($this->stdout, $message); // We reply ...
		$dump = @unpack("nn", $message);
		$dump = $dump["n"];
		$this->logg("OUT: ". $dump);
	}
	
	function play()
	{
		do {
			$this->readstdin(); // get data
			$length = strlen($this->data); // compute data length
			if($length > 0 ) { // for debug mainly ...
				$this->logg("GO: ".$this->data);
				$this->logg("data length is : ".$length);
			}
			$ret = $this->command(); // play with data !
			$this->logg("RE: " . $ret); // this is what WE send.
			$this->out($ret); // send what we reply.
			$this->data = NULL; // more clean. ...
		} while (true);
	}
	
	function command()
	{
		$data = $this->splitcomm(); // This is an array, where each node is part of what SM sent to us :
		// 0 => the command,
		// and the others are arguments .. e.g. : user, server, password ...
		
		if(strlen($data[0]) > 0 ) {
			$this->logg("Command was : ".$data[0]);
		}
		
		switch($data[0]) {
			case "isuser": // this is the "isuser" command, used to check for user existance
					$this->jabber_user = $data[1];
					$this->jabber_host = $data[2];
					$parms = $data[1].":".$data[2];  // only for logging purpose
					$return = $this->checkuser();
				break;
				
			case "auth": // check login, password
					$this->jabber_user = $data[1];
					$this->jabber_host = $data[2];
					$this->jabber_pass = $data[3];
					$parms = $data[1].":".$data[2].":".md5($data[3]); // only for logging purpose
					$return = $this->checkpass();
				break;
				
			case "setpass":
					$return = false; // We do not want jabber to be able to change password
				break;
				
			default:
					$this->stop(); // if it's not something known, we have to leave.
					// never had a problem with this using ejabberd, but might lead to problem ?
				break;
		}
		
		$return = ($return) ? 1 : 0;
		
		if(strlen($data[0]) > 0 && strlen($parms) > 0) {
			$this->logg("Command : ".$data[0].":".$parms." ==> ".$return." ");
		}
		return @pack("nn", 2, $return);
	}
	
	function checkpass()
	{
		/*
		 * Put here your code to check password
		 * $this->jabber_user
		 * $this->jabber_pass
		 * $this->jabber_host
		*/
				
		/*	
	    $this->jabber_user = mysql_real_escape_string(str_replace("_", " ", $this->jabber_user));
			$query = 'select * from users where name = "'.$this->jabber_user.'" and pass     = MD5("'.$this->jabber_pass.'") and status = 1;';
	                $res = mysql_query($query);
	                return mysql_fetch_assoc($res);
		*/

		if( preg_match('/^__IdentityApiToken__(.*)/', $this->jabber_pass, $matches ) ) {
			// check the user against an IdentityApi token
			$token = $matches[1];

			// check the user's actual cleartext password
			$request = array(
				'action' => 'verifyidentity',
				'iduser' => $this->jabber_user,
				'idtoken' => $token
			);

			$this->logg( 'Authenticating (token) ' . $this->jabber_user . '@' . $this->jabber_host );

			$out = $this->simpleApiPost( $this->jabber_host, $request );

			if( $out['authentication']['verified'] == true ) {
				$this->logg( 'Auth succeeded for ' . $this->jabber_user . '@' . $this->jabber_host );
				return 1;
			}

			$this->logg( 'Auth failed (token) for ' . $this->jabber_user . '@' . $this->jabber_host );
			return false;			
			
		} else {
			// check the user's actual cleartext password
			$request = array(
				'action' => 'login',
				'lgname' => $this->jabber_user,
				'lgpassword' => $this->jabber_pass
			);

			$this->logg( 'Authenticating (password) ' . $this->jabber_user . '@' . $this->jabber_host );

			$out = $this->simpleApiPost( $this->jabber_host, $request );

			if( $out['login']['result'] == 'NeedToken' ) {
				$request['lgtoken'] = $out['login']['token'];
				$this->logg( 'Got NeedToken, retrying with auth token/sessionid' );
				$out = $this->simpleApiPost( $this->jabber_host, $request, $out['login']['cookieprefix'] . '_session=' . $out['login']['sessionid'] );
			}

			if( $out['login']['result'] == 'Success' ) {
				$this->logg( 'Auth succeeded for ' . $this->jabber_user . '@' . $this->jabber_host );
				return 1;
			}

			$this->logg( 'Auth failed (password) for ' . $this->jabber_user . '@' . $this->jabber_host . ', reason: ' . $out['login']['result'] );
			return false;
		}
		
	}
	
	function checkuser()
	{
		/*
		 * Put here your code to check user
		 * $this->jabber_user
		 * $this->jabber_pass
		 * $this->jabber_host
		 */
		
		/*	
	    $this->jabber_user = mysql_real_escape_string(str_replace("_", " ", $this->jabber_user));
			$query = 'select * from users where name = "'.$this->jabber_user.'" and status = 1;';
	                $res = mysql_query($query);
	                return mysql_fetch_assoc($res);
		*/
		$this->logg( 'Checkuser temporary called' );
		return true; //temporary
	}
	
	function splitcomm() // simply split command and arugments into an array.
	{
		return explode(":", $this->data);
	}
	
	function logg( $message ) {
		if( $this->logging ) {
			syslog( LOG_NOTICE, $message );
		}
	}
	
	function simpleApiPost( $host, $fields, $cookie = false ) {
		// remove the Expect header, Wikipedia Squids can't handle it.
		// add a user-agent
		$headers = array( 
			"Expect:",
			"User-Agent: ejabberdAuth/0.1"
		);
		if( $cookie ) {
			$headers[] = "Cookie: $cookie";
		}

		$fields['format'] = 'json';
		
		$this->logg( 'Starting request' );

		// TODO: restrict the host parameter to a defined whitelist, probably in
		// DefaultSettings.php, which this script can include.
		// Most likely, make an array of host to API url mappings, to support multiple
		// wikis on the same domain.
		//
		//$host = 'en.wikipedia.org';

		$ch = curl_init( "http://$host/w/api.php" );
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, count( $fields ) );
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$out = curl_exec($ch);

		$this->logg( 'Request completed' );

		// This regexp deals gracefully with additional requests (like 100 Continue)
		// as long as they come before the XML payload.
		preg_match('/HTTP\/\d\.\d 200 OK(.*?)\r?\n\r?\n\r?(.*)/ms', $out, $matches);
		$header = $matches[1];
		$body = $matches[2];

		// FIXME: deal with errors here.

		return json_decode( $body, true );
	}
}

?>

