<?php

class SecurePoll_Question extends SecurePoll_Entity {
	var $options;

	function __construct( $id, $options ) {
		parent::__construct( 'question', $id );
		$this->options = $options;
	}

	function getMessageNames() {
		return array( 'text' );
	}

	function getChildren() {
		return $this->options;
	}
}
