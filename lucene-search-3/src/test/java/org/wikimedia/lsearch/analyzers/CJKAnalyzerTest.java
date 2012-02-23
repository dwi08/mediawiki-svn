package org.wikimedia.lsearch.analyzers;

import org.apache.lucene.analysis.Analyzer;
import org.apache.lucene.analysis.cjk.CJKAnalyzer;
import org.apache.lucene.util.Version;
import org.junit.Before;
import org.junit.Test;
import org.wikimedia.lsearch.config.Configuration;
import org.wikimedia.lsearch.config.GlobalConfiguration;
import org.wikimedia.lsearch.test.AbstractWikiTestCase;

/**
 * tests for the CJKAnalyzer.
 *
 * @author oren
 *
 */
public class CJKAnalyzerTest extends AbstractWikiTestCase {

    private Analyzer a = null;
    private Configuration config = null;

    /**
     * setup the fixtures.
     */
    @Before
    public final void setUp() {
        super.setUp();
        if (config == null) {
			config = Configuration.open();
			GlobalConfiguration.getInstance();
		}
	}

	/**
	 * tests CJKAnalyzer.
	 */
	@Test
	public final void testCJKAnalyzer() {
		a = new CJKAnalyzer(Version.LUCENE_24);
		assertEquals(
				"[(いわ,0,2,type=double), (わさ,1,3,type=double), (さき,2,4,type=double), (ic,4,6,type=single), (カー,6,8,type=double), (ード,7,9,type=double)]",
				tokens("いわさきicカード"));
	}

}
