/*
 * Created on Feb 9, 2007
 *
 */
package org.wikimedia.lsearch.config;

import java.io.File;
import java.util.ArrayList;
import java.util.Hashtable;
import java.util.Properties;
import java.util.regex.Pattern;

import org.junit.After;
import org.junit.Before;
import org.junit.Test;
import org.wikimedia.lsearch.search.NamespaceFilter;
import org.wikimedia.lsearch.test.AbstractWikiTestCase;
import org.wikimedia.lsearch.util.StringUtils;

/**
 * @author rainman
 *
 */
public class GlobalConfigurationTest extends AbstractWikiTestCase { // NOPMD by OrenBochman on 1/15/12 3:38 AM
	
	// private static final Logger LOGGER =
	// Logger.getLogger(GlobalConfigurationTest.class.getName());
	private transient GlobalConfiguration global = null;
	private transient IndexId frTest=null;
	
	private transient Hashtable<String, Hashtable<String, Hashtable<String, String>>>  database;
	
	@Override
	@Before
	public void setUp() {
		
		super.setUp();
		if(global == null)
			global = GlobalConfiguration.getInstance();
		
		database = global.database;
		frTest = IndexId.get("frtest");		
	}

	@Override
	@After
	public void tearDown() {
		// XXX: is this method really needed?
		
		database=null;
		frTest=null;		
	}

	@Test
	public void testPreprocessLine(){
		
		String winPathFixer; 
		if(System.getProperty("os.name").startsWith("Windows")){
			winPathFixer = "/";
		}else{
			winPathFixer="";
		}
		
		String text = "entest: (mainsplit)";
		assertEquals("preprocessLine() failed - ",text,global.preprocessLine(text));

		final StringBuilder dburl = new StringBuilder("file://")
				.append(winPathFixer).append(System.getProperty("user.dir"))
				.append(File.separator).append("src").append(File.separator)
				.append("test").append(File.separator)
				.append("resources").append(File.separator).append("dbs.test");
		text = "{"+dburl+"}: (mainsplit)";
		
		assertEquals("preprocessLine() failed - ","entest,rutest,srtest,kktest: (mainsplit)",global.preprocessLine(text));
	}

	 
	@Test
	public void testRoles(){
		// database
		
		Hashtable<String, Hashtable<String, String>>  roles = database.get("entest");
		
		assertNotNull(roles.get("mainsplit"));
		assertNotNull(roles.get("mainpart"));
		assertNotNull(roles.get("restpart"));

		Hashtable<String, String> mainpart = roles.get("mainpart");
		assertEquals("false",mainpart.get("optimize"));
		assertEquals("2",mainpart.get("mergeFactor"));
		assertEquals("10",mainpart.get("maxBufDocs"));
	}
	
	@Test
	public void testReadURLSplitRoles(){
		
		Hashtable<String, Hashtable<String, String>>  splitroles = database.get("frtest");
		assertNotNull(splitroles.get("split"));
		assertNotNull(splitroles.get("part1"));
		assertNotNull(splitroles.get("part2"));
		assertNotNull(splitroles.get("part3"));
	}
	
	@Test
	public void testParts(){
		Hashtable<String, String> nspart1 = database.get("njawiki").get("nspart1");
		assertEquals("false",nspart1.get("optimize"));
		assertEquals("5",nspart1.get("mergeFactor"));

	}
	
	@Test
	public void testReadURLAdress(){
		// search
		Hashtable<String,ArrayList<String>>  search = global.search;
		ArrayList<String> sr =  search.get("192.168.0.2");

		String[] ssr = (String[]) sr.toArray(new String [] {} );

		assertEquals("entest.mainpart",ssr[0]);
		assertEquals("entest.restpart",ssr[1]);
		assertEquals("rutest",ssr[2]);
		assertEquals(6,ssr.length);
	}
	
	@Test
	public void testSearchGroups(){
		// search groups
		Hashtable<Integer,Hashtable<String,ArrayList<String>>> searchGroups = global.searchGroup;

		Hashtable<String,ArrayList<String>> group0 = searchGroups.get(Integer.valueOf(0));
		assertEquals("{192.168.0.5=[entest.mainpart, entest.restpart], 192.168.0.2=[entest.mainpart]}",group0.toString());
		Hashtable<String,ArrayList<String>> group1 = searchGroups.get(Integer.valueOf(1));
		assertEquals("{192.168.0.6=[frtest.part3, detest], 192.168.0.4=[frtest.part1, frtest.part2]}",group1.toString());
	}

	@Test
	public void testIndex(){
		// index
		Hashtable<String,ArrayList<String>> index = global.index;
		ArrayList<String> ir =  index.get("192.168.0.5");

		String[] sir = (String[]) ir.toArray(new String [] {} );

		assertEquals("entest",sir[0]);
		assertEquals("entest.mainpart",sir[1]);
		assertEquals("entest.restpart",sir[2]);
		assertEquals("detest",sir[3]);
		assertEquals("rutest",sir[4]);
		assertEquals("frtest",sir[5]);
		assertTrue(ir.contains("entest.mainpart.sub1"));
		assertTrue(ir.contains("entest.mainpart.sub2"));
		assertTrue(ir.contains("entest.mainpart.sub3"));
		assertEquals(17,sir.length);
	}
	
	public void testIndexLocation(){
		// indexLocation
		Hashtable<String,String> indexLocation = global.indexLocation;

		assertEquals("192.168.0.5",indexLocation.get("entest.mainpart"));
		assertEquals("192.168.0.2",indexLocation.get("entest.ngram"));
	}
	
	
	public void testPrefixes(){

		// test prefixes
		Hashtable<String,NamespaceFilter> prefixes = global.namespacePrefix;
		assertEquals(17,prefixes.size());
		
	}
	
	@Test
	public void testGlobalProperties(){

		// check global properties
		Properties prop = global.globalProperties;
		assertEquals("wiki wiktionary test",prop.get("Database.suffix"));
		assertEquals("wiki rutest",prop.get("KeywordScoring.suffix"));
	}
	
	@Test
	public void testLanguages(){
		
		// check languages and keyword stuff
		assertEquals("en",global.getLanguage("entest"));
		assertEquals("sr",global.getLanguage("srwiki"));
		assertFalse(global.useKeywordScoring("frtest"));
		assertTrue(global.useKeywordScoring("srwiki"));
		assertTrue(global.useKeywordScoring("rutest"));
	}

	@Test
	public void testOaiRepository(){
		
		// test oai repo stuff
		Hashtable<String,String> oairepo = global.oaiRepo;
		assertEquals("http://$lang.wiktionary.org/w/index.php",oairepo.get("wiktionary"));
		assertEquals("http://localhost/wiki-lucene/phase3/index.php",oairepo.get("frtest"));
		assertEquals("http://$lang.wikipedia.org/w/index.php",oairepo.get("<default>"));

		assertEquals("http://sr.wikipedia.org/w/index.php?title=Special:OAIRepository",global.getOAIRepo("srwiki"));
		assertEquals("http://localhost/wiki-lucene/phase3/index.php?title=Special:OAIRepository",global.getOAIRepo("frtest"));
	}

	@Test
	public void testInitiazeSettings(){
		//FIXME: try to add InitialiseSettings.php to testdata/
		// InitialiseSettings test
		assertEquals("sr",global.getLanguage("rswikimedia"));
		assertEquals("http://rs.wikimedia.org/w/index.php?title=Special:OAIRepository",global.getOAIRepo("rswikimedia"));
		assertEquals("http://commons.wikimedia.org/w/index.php?title=Special:OAIRepository",global.getOAIRepo("commonswiki"));
	}

	@Test
	public void testSuggestTags(){
		// test suggest tag
		Hashtable<String,String> sug = global.getDBParams("entest","spell");
		assertEquals("1",sug.get("wordsMinFreq"));
		assertEquals("2",sug.get("phrasesMinFreq"));

	}

	@Test
	public void testOrphans() {
		
		IndexId enw = IndexId.get("enwiktionary");
		
		assertTrue(enw.getSearchHosts().contains("oblak2"));
		assertEquals("[oblak2]", enw.getSearchHosts().toString());

		IndexId enIndexId = IndexId.get("entest.mainpart");
		assertFalse(enIndexId.getSearchHosts().contains("oblak2"));
	}

	@Test
	public void testIndexIds(){

		IndexId entest = IndexId.get("entest");

		assertTrue(entest.isMainsplit());
		assertFalse(entest.isSingle());
		assertTrue(entest.isLogical());

		assertEquals("entest",entest.getDBname());
		assertEquals("entest",entest.toString());
		assertEquals("192.168.0.5",entest.getIndexHost());
		assertFalse(entest.isMyIndex());
	
	}

	@Test
	public void testgetType(){

		IndexId entest = IndexId.get("entest");	
		
		//assertEquals(null,entest.getSnapshotPath());
		assertEquals("mainsplit",entest.getType());
	}		

	@Test
	public void testgetRsyncSnapshotPath(){

		IndexId entest = IndexId.get("entest");	

		assertEquals("/mwsearch2/snapshot/entest",entest.getRsyncSnapshotPath());

		IndexId enrest = IndexId.get("entest.restpart");

		assertSame(enrest,entest.getRestPart());
		assertTrue(enrest.isMainsplit());
		assertFalse(enrest.isLogical());
		assertEquals("entest",enrest.getDBname());
		assertEquals("entest.restpart",enrest.toString());
		assertEquals("/mwsearch2/snapshot/entest.restpart",enrest.getRsyncSnapshotPath());
		assertFalse(enrest.isMyIndex());
		assertEquals("mainsplit",enrest.getType());
		//assertEquals(null,enrest.getIndexPath());

	}		

	@Test
	public void testGetFrench(){		
		
		
		assertTrue(frTest.isSplit());
		assertTrue(frTest.isLogical());
		assertEquals("frtest",frTest.getDBname());
		assertEquals("frtest",frTest.toString());
		assertFalse(frTest.isMyIndex());
		assertEquals(3,frTest.getSplitFactor());

	}		

	@Test
	public void testGetFrenchPart(){
		
		
		IndexId frpart2 = IndexId.get("frtest.part2");
		assertSame(frpart2,frTest.getPart(2));
		assertTrue(frpart2.isSplit());
		assertFalse(frpart2.isLogical());
		assertEquals(2,frpart2.getPartNum());
		assertEquals(3,frpart2.getSplitFactor());

		IndexId detest = IndexId.get("detest");
		assertFalse(detest.isLogical());

	}		

	/**
	 * check nssplit configuration option.
	 * this configuration option should split the index into servral parts
	 */
	@Test
	public void testGetNjPart(){

		IndexId njawiki = IndexId.get("njawiki");
		assertTrue(njawiki.isLogical());
		assertFalse(njawiki.isSplit());
		assertTrue(njawiki.isNssplit());
		assertEquals(3,njawiki.getSplitFactor());
		assertEquals("njawiki.nspart3",njawiki.getPartByNamespace("4").toString());
		assertEquals("njawiki.nspart1",njawiki.getPartByNamespace("0").toString());
		assertEquals("njawiki.nspart2",njawiki.getPartByNamespace("12").toString());
		assertEquals("[192.168.0.1]",njawiki.getSearchHosts().toString());

		IndexId njawiki2 = IndexId.get("njawiki.nspart2");
		assertFalse(njawiki2.isLogical());
		assertFalse(njawiki2.isSplit());
		assertTrue(njawiki2.isNssplit());
		assertEquals(3,njawiki2.getSplitFactor());
		assertEquals(2,njawiki2.getPartNum());
		assertEquals("[192.168.0.1]",njawiki2.getSearchHosts().toString());
	}

	@Test
	public void testEnSpell(){

		IndexId sug = IndexId.get("entest.spell");
		assertTrue(sug.isSpell());
		assertFalse(sug.isLogical());
		assertEquals(sug,sug.getSpell());
	}

	@Test
	public void testEnSubdivided(){

		IndexId sub1 = IndexId.get("entest.mainpart.sub1");
		assertFalse(sub1.isLogical());
		assertEquals(3,sub1.getSubdivisionFactor());
		assertFalse(sub1.isFurtherSubdivided());
		assertTrue(sub1.isSubdivided());
		assertEquals(1,sub1.getSubpartNum());
		//assertNull(sub1.getImportPath());

		IndexId enmain = IndexId.get("entest.mainpart");
		assertEquals(sub1,enmain.getSubpart(1));
		assertTrue(enmain.isFurtherSubdivided());
		assertFalse(enmain.isSubdivided());
		assertEquals(3,enmain.getSubdivisionFactor());
		//assertNull(enmain.getImportPath());

	}

	@Test
	public void testHmSubdivided(){

		
		IndexId hmpart1 = IndexId.get("hmwiki.nspart1");
		assertTrue(hmpart1.isFurtherSubdivided());
		//assertNull(hmpart1.getImportPath());

		assertEquals("[hmwiki.nspart1.sub1, hmwiki.nspart1.sub2]",hmpart1.getPhysicalIndexIds().toString());
		assertEquals("[hmwiki.nspart2, hmwiki.nspart1.sub1, hmwiki.nspart3, hmwiki.nspart1.sub2]",IndexId.get("hmwiki").getPhysicalIndexIds().toString());

		IndexId hmsub1 = IndexId.get("hmwiki.nspart1.sub1");
		assertTrue(hmsub1.isSubdivided());
		//assertNotNull(hmsub1.getImportPath());
		assertEquals(2,hmsub1.getSubdivisionFactor());
		assertEquals("192.168.0.2",hmsub1.getIndexHost());

		IndexId hhl1 = IndexId.get("hmwiki.nspart1.sub1.hl");
		assertTrue(hhl1.isSubdivided());
		assertTrue(hhl1.isHighlight());
		assertEquals(hhl1,IndexId.get("hmwiki.nspart1.hl").getSubpart(1));
		assertEquals("[192.168.0.1]",hhl1.getSearchHosts().toString());

	}

	@Test
	public void testEnTitles(){

		
		IndexId ent = IndexId.get("en-titles");
		assertTrue(ent.isTitlesBySuffix());
		assertEquals(2,ent.getSplitFactor());
		//assertEquals("[en-titles.tspart2, en-titles.tspart1]",ent.getPhysicalIndexes().toString());
	}

	@Test
	public void testEnTitlesParts(){

		IndexId ents1 = IndexId.get("en-titles.tspart1");
		assertTrue(ents1.isTitlesBySuffix());
		assertEquals("w",ents1.getInterwikiBySuffix("wiki"));
		assertEquals(ents1,IndexId.get("enwiki").getTitlesIndex());
		assertEquals("en",global.getLanguage(ents1.getDBname()));
		assertEquals("{wiki=enwiki}",ents1.getSuffixToDbname().toString());
		IndexId ents2 = IndexId.get("en-titles.tspart2");
		//assertEquals("{wikisource=enwikisource, wiktionary=enwiktionary, test=entest}",ents2.getSuffixToDbname().toString());

		assertEquals("en-titles.tspart2",IndexId.get("enwiktionary").getTitlesIndex().toString());

		IndexId mw = IndexId.get("mediawikiwiki");
		IndexId mwt = IndexId.get("mw-titles.tspart1");
		assertEquals("mediawikiwiki",mw.getTitlesSuffix());
		assertEquals("mw-titles.tspart1",mw.getTitlesIndex().toString());
		assertEquals("mw",mwt.getInterwikiBySuffix("mediawikiwiki"));
		assertEquals("{mediawikiwiki=mediawikiwiki, metawiki=metawiki}",mwt.getSuffixToDbname().toString());
	}

	@Test
	public void testEnSpellPrecursor(){
		
		IndexId ep = IndexId.get("entest.spell.pre");
		assertTrue(ep.isPrecursor());
		assertFalse(ep.isSpell());
		assertEquals("entest.spell",ep.getPrecursorTarget().toString());
		assertEquals("192.168.0.2",ep.getIndexHost());
		//assertEquals("/usr/local/var/mwsearch/snapshot/entest.spell.pre",ep.getSnapshotPath());

		//IndexId tn = IndexId.get("entest.title_ngram");
		//assertTrue(tn.isTitleNgram());

	}

	@Test
	public void testComplexWildcard(){
		Pattern p = Pattern.compile(StringUtils.wildcardToRegexp("(?!(enwiki.|dewiki.|frwiki.|itwiki.|nlwiki|.))*.spell"));
		assertFalse(p.matcher("enwiki.spell").matches());
		assertTrue(p.matcher("enwikibooks.spell").matches());
		assertFalse(p.matcher("dewiki.spell").matches());
		assertTrue(p.matcher("srwiki.spell").matches());
		assertFalse(p.matcher("srwiki").matches());
		assertFalse(p.matcher("enwiki").matches());
	}
}
