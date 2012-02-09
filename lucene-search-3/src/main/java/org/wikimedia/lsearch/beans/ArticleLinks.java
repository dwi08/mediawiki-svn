package org.wikimedia.lsearch.beans;

import java.util.ArrayList;

/** 
 * Class used by XML Importer to keep track of links between
 * articles. This class is a descriptor of links, should be a
 * value for some key (e.g. prefixed article name) in the hashtable. 
 * 
 * @author rainman
 *
 */
public class ArticleLinks {
	/** Number of linking articles */
	public int links;
	/** if this is redirect, point to the target title */
	public ArticleLinks redirectsTo;
	/** all the pages that get redirected here */
	public ArrayList<String> redirected;
	/**
	 * Constructor for a regular article
	 * @param links
	 */
	public ArticleLinks(int links) {
		this.links = links;
		redirectsTo = null;
	}

	/**
	 * Constructor for redirect to an article
	 * @param links
	 * @param redirect
	 */
	public ArticleLinks(int links, ArticleLinks redirect) {
		this.links = links;
		this.redirectsTo = redirect;
	}

	final static int PRIME = 31;
	
	@Override
	public int hashCode() {				
		return  PRIME * (PRIME + links);
	}
	
	
	@Override
	public boolean equals(Object other) {		
		if( other instanceof ArticleLinks ) {			
			ArticleLinks otherArticleLinks = (ArticleLinks)other;			
			return links == otherArticleLinks.links
			&& redirectsTo.equals(otherArticleLinks)
			&& redirected.equals(otherArticleLinks.redirected);			
		}		
		return false;	      
	  }	
}
