/**
 * Creates an es.ListView object.
 * 
 * @class
 * @constructor
 * @extends {es.DocumentViewBranchNode}
 */
es.ListView = function( model ) {
	// Extension
	return $.extend( new es.DocumentViewBranchNode( model ), this );
};
