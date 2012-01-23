/**
 * 
 */
package org.wikimedia.lsearch.util;

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
	public void Test1() throws URISyntaxException {

		// TODO convert main below to Junit Tests

	}

	public static void main(String[] args) {

		try {
			testURI("/x");
			testURI("/x?foo=bar");
			testURI("/x?foo=bar&biz=bax");

			// The %26 should _not_ split 'foo' from 'bogo'
			testURI("/x?foo=bar+%26bogo&next=extreme");

			// UTF-8 good encoding
			testURI("/x?serveuse=%c3%a9nid");

			// bad encoding; you'll see replacement char
			testURI("/x?serveuse=%e9nid");

			// corner cases; missing params
			testURI("/x?foo");
			testURI("/x?foo&bar=baz");
			testURI("/x?foo&&bar");
			testURI("/x?&");
			testURI("/x?=");
			testURI("/x?==&");

			testURI("/updatePage?db=wikilucene&namespace=6&title=Nick+Gorton5+8+05.jpg");
			testURI("/getStatus");
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
