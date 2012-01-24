package org.wikimedia.lsearch.search;

import java.io.IOException;
import java.util.BitSet;

import org.apache.lucene.index.IndexReader;
import org.apache.lucene.search.Filter;

public class SuffixNamespaceWrapper extends Filter {
	/**
	 * 
	 */
	private static final long serialVersionUID = -424940152787744368L;
	SuffixNamespaceFilter filter = null;
		
	public SuffixNamespaceWrapper(SuffixNamespaceFilter filter) {
		this.filter = filter;
	}

	@Override
	public BitSet bits(IndexReader reader) throws IOException {
		return SuffixNamespaceCache.bits(filter,reader);
	}

	@Override
	public String toString() {
		return "wrap: "+filter;
	}

}
