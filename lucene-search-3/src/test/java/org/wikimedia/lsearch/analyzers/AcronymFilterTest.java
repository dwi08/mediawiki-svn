package org.wikimedia.lsearch.analyzers;

import static org.junit.Assert.assertFalse;
import static org.junit.Assert.assertTrue;

import org.apache.lucene.analysis.miscellaneous.EmptyTokenStream;
import org.junit.Before;
import org.junit.Test;

/**
 * The class <code>AcronymFilterTest</code> contains tests for the class {@link
 * <code>AcronymFilter</code>}
 * 
 * @pattern JUnit Test Case
 * @author oren
 * @version $Revision$
 */
public class AcronymFilterTest {

	AcronymFilter af;

	@Before
	public void Setup() {
		af = new AcronymFilter(new EmptyTokenStream());

	}

	@Test
	public void isAcronymTest() {

		assertTrue(af.isAcronym(new char[] { 'a', '.', 'n', '.', 't' }));
	}

	@Test
	public void testAcronymFilter() {

		assertTrue(af.isAcronym(new char[] { 'a', '.', '1', '2', '1' }));

	}

	@Test
	public void testAcronymFilter_2() {

		assertTrue(af.isAcronym(new char[] { '.', 'b', 'c', 'd', 'a' }));

	}

	@Test
	public void testAcronymFilter_3() {

		AcronymFilter af = new AcronymFilter(new EmptyTokenStream());
		assertTrue(af.isAcronym(new char[] { 'a', 'b', 'c', 'd', '.' }));

	}

	@Test
	public void testAcronymFilter_4() {

		assertFalse(af.isAcronym(new char[] { '1', '.', '2', '3', '4' }));

	}

	@Test
	public void testAcronymFilter_5() {

		assertFalse(af.isAcronym(new char[] { '1', '2', '2', '3', '4' }));

	}

	@Test
	public void testAcronymFilter_6() {

		assertFalse(af.isAcronym(new char[] { 'a', '1', '2', '3', 'a' }));

	}

	@Test
	public void testAcronymFilter_7() {

		assertFalse(af.isAcronym(new char[] { 'a', '1', '2', '3', 'a' }));

	}

	@Test
	public void testAcronymFilter_8() {

		assertFalse(af.isAcronym(new char[] { 'a', 'a', 'b', 'c', 'd' }));
	}

}
