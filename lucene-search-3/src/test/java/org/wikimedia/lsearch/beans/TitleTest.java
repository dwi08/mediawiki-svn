package org.wikimedia.lsearch.beans;

import org.wikimedia.lsearch.test.AbstractWikiTestCase;

public class TitleTest extends AbstractWikiTestCase {
	
	public void testStatic(){
		assertEquals(0,Title.namespaceAsInt("0:Title"));
		assertEquals(12,Title.namespaceAsInt("12:Title"));
	}

}
