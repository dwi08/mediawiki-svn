/**
 * 
 */
package org.wikimedia.lsearch.util;

import static org.junit.Assert.assertEquals;

import java.net.URI;
import java.net.URISyntaxException;
import java.util.Iterator;
import java.util.Set;

import org.junit.Test;

/**
 * @author oren
 */

public class QueryStringMapTest {

	@Test
	public void JustAPath() throws URISyntaxException {
		
		String uri="/x";
		QueryStringMap map = new QueryStringMap(new URI(uri));
		assertEquals(0, map.size());
	}
	
	@Test
	public void SimpleQuery01() throws URISyntaxException {
		
		String uri="/x?foo=bar";
		QueryStringMap map = new QueryStringMap(new URI(uri));
		assertEquals(1, map.size());
		assertEquals("bar", map.get("foo"));

	}

	@Test
	public void SimpleQuery02() throws URISyntaxException {

		String uri = "/x?foo=bar&biz=bax";
		QueryStringMap map = new QueryStringMap(new URI(uri));
		assertEquals(2, map.size());
		assertEquals("bar", map.get("foo"));
		assertEquals("bax", map.get("biz"));

	}

	/**
	 * The %26 should _not_ split 'foo' from 'bogo'
	 * 
	 * @throws URISyntaxException
	 */
	@Test
	public void SpacedParamQuery01() throws URISyntaxException {

		String uri = "/x?foo=bar+%26bogo&next=extreme";
		QueryStringMap map = new QueryStringMap(new URI(uri));
		assertEquals((int) 2, map.size());
		assertEquals("bar &bogo", map.get("foo"));
		assertEquals("extreme", map.get("next"));

	}

	/**
	 * UTF-8 good encoding
	 * 
	 * @throws URISyntaxException
	 */
	@Test
	public void UTF8EncodingQuery01() throws URISyntaxException {

		String uri = "/x?serveuse=%c3%a9nid";
		QueryStringMap map = new QueryStringMap(new URI(uri));
		assertEquals((int) 1, map.size());
		assertEquals("énid", map.get("serveuse"));
	}

	/**
	 * UTF-8 bad encoding
	 * 
	 * @throws URISyntaxException
	 */
	@Test
	public void BadUTF8EncodingQuery01() throws URISyntaxException {

		String uri = "/x?serveuse=%e9nid";
		QueryStringMap map = new QueryStringMap(new URI(uri));
		assertEquals((int) 1, map.size());
		assertEquals("�nid", map.get("serveuse"));
	}

	/**
	 * missing params
	 * 
	 * @throws URISyntaxException
	 */
	@Test
	public void CornerCase01() throws URISyntaxException {

		String uri = "/x?foo";
		QueryStringMap map = new QueryStringMap(new URI(uri));
		assertEquals((int) 1, map.size());
		assertEquals("", map.get("foo"));
	}

	@Test
	public void CornerCase02() throws URISyntaxException {

		String uri = "/x?foo&bar=baz";
		QueryStringMap map = new QueryStringMap(new URI(uri));
		assertEquals((int) 2, map.size());
		assertEquals("", map.get("foo"));
		assertEquals("baz", map.get("bar"));
	}

	@Test
	public void CornerCase03() throws URISyntaxException {

		String uri = "/x?foo&&bar";
		QueryStringMap map = new QueryStringMap(new URI(uri));
		assertEquals((int) 2, map.size());
		assertEquals("", map.get("foo"));
		assertEquals("", map.get("bar"));
	}

	@Test
	public void CornerCase04() throws URISyntaxException {

		String uri = "/x?&";
		QueryStringMap map = new QueryStringMap(new URI(uri));
		assertEquals(0, map.size());
	}

	@Test
	public void CornerCase05() throws URISyntaxException {

		String uri = "/x?=";
		QueryStringMap map = new QueryStringMap(new URI(uri));
		assertEquals((int) 0, map.size());
	}

	@Test
	public void CornerCase06() throws URISyntaxException {

		String uri = "/x?==&";
		QueryStringMap map = new QueryStringMap(new URI(uri));
		assertEquals(0, map.size());
	}

	@Test
	public void RealQuery01() throws URISyntaxException {

		String uri = "/updatePage?db=wikilucene&namespace=6&title=Nick+Gorton5+8+05.jpg";
		QueryStringMap map = new QueryStringMap(new URI(uri));
		assertEquals((int) 3, map.size());
		assertEquals("wikilucene", map.get("db"));
		assertEquals("6", map.get("namespace"));
		assertEquals("Nick Gorton5 8 05.jpg", map.get("title"));
	}
	
	@Test
	public void RealQuery02() throws URISyntaxException {

		String uri = "/getStatus";
		QueryStringMap map = new QueryStringMap(new URI(uri));
		assertEquals((int) 0, map.size());
	}

	
	/**
	 * little debugger for query strings.
	 * result is sent to console
	 * 
	 * @param args
	 */
	public static void main(String[] args) {

		try {
			testURI("/updatePage?db=wikilucene&namespace=6&title=Nick+Gorton5+8+05.jpg");

		} catch (URISyntaxException e) {
			e.printStackTrace();
		}
	}

	private static void testURI(String uri) throws URISyntaxException {
		QueryStringMap map = new QueryStringMap(new URI(uri));
		System.out.println(uri);
		Set<String> keys = map.keySet();
		for (Iterator<String> i = keys.iterator(); i.hasNext();) {
			String key = i.next();
			System.out.println("  \"" + key + "\" => \"" + map.get(key) + "\"");
		}
	}
}
