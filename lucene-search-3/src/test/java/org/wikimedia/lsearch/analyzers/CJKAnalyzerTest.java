package org.wikimedia.lsearch.analyzers;

import java.io.IOException;
import java.io.StringReader;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashSet;

import org.apache.lucene.analysis.Analyzer;
import org.apache.lucene.analysis.Token;
import org.apache.lucene.analysis.TokenStream;
import org.apache.lucene.analysis.cjk.CJKAnalyzer;
import org.apache.lucene.queryParser.ParseException;
import org.apache.lucene.queryParser.QueryParser;
import org.apache.lucene.search.Query;
import org.junit.Before;
import org.junit.Test;
import org.wikimedia.lsearch.analyzers.Aggregate.Flags;
import org.wikimedia.lsearch.config.Configuration;
import org.wikimedia.lsearch.config.GlobalConfiguration;
import org.wikimedia.lsearch.config.IndexId;
import org.wikimedia.lsearch.ranks.StringList;
import org.wikimedia.lsearch.test.AbstractWikiTestCase;

public class CJKAnalyzerTest extends AbstractWikiTestCase {

	Analyzer a = null;
	Configuration config = null;

	@Before
	protected void setUp() {
		super.setUp();
		if(config == null){
			config = Configuration.open();
			GlobalConfiguration.getInstance();
		}
	}

	public String tokens(String text){
		try{
			return Arrays.toString(tokensFromAnalysis(a,text,"contents"));
		} catch(IOException e){
			fail(e.getMessage());
			return null;
		}
	}
	
	@Test
	public void testCJKAnalyzer(){
		a = new CJKAnalyzer();
		assertEquals(
				"[(いわ,0,2,type=double), (わさ,1,3,type=double), (さき,2,4,type=double), (ic,4,6,type=single), (カー,6,8,type=double), (ード,7,9,type=double)]",
				tokens("いわさきicカード"));
	}

}