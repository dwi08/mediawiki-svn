package de.brightbyte.wikiword.model;


public abstract class WikiWordConceptReference<T extends WikiWordConcept> extends WikiWordReference<T> {

	public WikiWordConceptReference(int id, String name, int cardinality, double relevance) {
		super(id, name, cardinality, relevance);
	}

}
