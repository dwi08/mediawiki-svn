package org.wikimedia.lsearch.ranks;

import org.wikimedia.lsearch.analyzers.ArticlesParser;
import org.wikimedia.lsearch.analyzers.FastWikiTokenizerEngine;
import org.wikimedia.lsearch.analyzers.TestArticle;

public class ContextParserTest {
	
	/**
	 * gets articles stored in the test file using ArticlesParse 
	 * these are parsed by ContextParser which uses the containing
	 * sentence as the context of the link.
	 * 
	 * @param args - ignored
	 */
	public static void main(String[] args){
		ArticlesParser ap = new ArticlesParser("./src/test/resources/indexing-articles.test");
		for(TestArticle a : ap.getArticles()){
			ContextParser p = new ContextParser(a.content,null,null,null);
			System.out.println("ORIGINAL ARTICLE:");
			System.out.println(a.content);
			System.out.println();
			for(ContextParser.Context c : p.getContexts()){
				System.out.println(FastWikiTokenizerEngine.stripTitle(c.get(a.content)));
				System.out.println("----------------------------------------------------------------------------------------------");
			}
		}
	}
}
