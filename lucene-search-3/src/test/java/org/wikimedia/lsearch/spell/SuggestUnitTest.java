package org.wikimedia.lsearch.spell;

import java.io.IOException;
import java.util.Map;
import java.util.TreeMap;

import org.wikimedia.lsearch.config.IndexId;
import org.wikimedia.lsearch.search.NamespaceFilter;
import org.wikimedia.lsearch.spell.dist.EditDistance;
import org.wikimedia.lsearch.test.AbstractWikiTestCase;

public class SuggestUnitTest extends AbstractWikiTestCase 
{
	
	public void testMakeNamespaces() throws IOException {
		IndexId iid = IndexId.get("entest");
		Suggest sug = new Suggest(iid);
		assertNull(sug.makeNamespaces(new NamespaceFilter("0")));
		assertNull(sug.makeNamespaces(new NamespaceFilter("0,2,4,14")));
		assertNotNull(sug.makeNamespaces(new NamespaceFilter("0,2,4,100")));
		assertEquals("[0, 100, 2, 4]",sug.makeNamespaces(new NamespaceFilter("0,2,4,100")).namespaces.toString());
	}

	private Map<Integer,Integer> getSpaceMap(String str1, String str2){
		EditDistance editDistance = new EditDistance(str1);
		int d[][] = editDistance.getMatrix(str2);
		
		// map: space -> same space in edited string
		TreeMap<Integer,Integer> spaceMap = new TreeMap<Integer,Integer>(); 
		new Suggest().extractSpaceMap(d,str1.length(),str2.length(),spaceMap,str1,str2);
		return spaceMap;
	}

	/**
	 * 
	 * @throws IOException
	 */
	public void testExtractSpaceMap1() throws IOException {
		assertEquals("{}",getSpaceMap(".999","0 999").toString());
	}

	/**
	 * 
	 * @throws IOException
	 */
	public void testExtractSpaceMap2() throws IOException {
		assertEquals("{4=3}",getSpaceMap("some string","som estring").toString());		
	}

	/**
	 * 
	 * @throws IOException
	 */
	public void testExtractSpaceMap3() throws IOException {
		assertEquals("",getSpaceMap("               a   ","         b         ").toString());
	}

}
