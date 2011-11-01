/**
 * Creates an es.ParagraphView object.
 * 
 * @class
 * @constructor
 * @extends {es.DocumentViewLeafNode}
 * @param {es.ParagraphModel} model Paragraph model to view
 */
es.ParagraphView = function( model ) {
	// Inheritance
	es.DocumentViewLeafNode.call( this, model );

	// DOM Changes
	this.$.addClass( 'editSurface-paragraphBlock' );
};

/* Inheritance */

es.extendClass( es.ParagraphView, es.DocumentViewLeafNode );
