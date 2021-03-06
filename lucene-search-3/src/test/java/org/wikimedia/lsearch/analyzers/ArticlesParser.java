/*
 * Created on Jan 25, 2007
 *
 */
package org.wikimedia.lsearch.analyzers;

import java.io.BufferedReader;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.IOException;
import java.util.ArrayList;


/**
 * Load articles stored in a single file into memory
 * 
 * the format of the file is
 * <code>
 * ### namespace = 0
 * ### title = 1991
 * ### content
 * 
 * article content
 * </code>
 * 
 * @author rainman
 *
 * used exclusively in tests
 *
 */
public class ArticlesParser {

	protected ArrayList<TestArticle> articles;

	/**
	 * Initialize from a file path, open file and read into memory
	 *
	 * @param filename
	 */
	public ArticlesParser(String filename){
		BufferedReader in;
		try {
			articles = new ArrayList<TestArticle>();
			in = new BufferedReader(new FileReader(filename));
			readFromFile(in);
			in.close();
		} catch (FileNotFoundException e) {
			e.printStackTrace();
		} catch (IOException e) {
			e.printStackTrace();
		}
	}

	/**
	 * Read some articles from BufferedReader
	 *
	 * @param in
	 */
	protected void readFromFile(BufferedReader in){
		String str;
		TestArticle a = new TestArticle();
		boolean readingContent = false;
		try {
			while ((str = in.readLine()) != null){
				if(readingContent){
					if(str.startsWith("###")){
						// done
						articles.add(a);
						a = new TestArticle();
						readingContent = false;
					} else{
						a.content +=  str+"\n";
						continue;
					}
				}
				if(str.startsWith("###")){
					String[] s = str.substring(3).split("=");
					String key = s[0].trim();
					String value = "";
					if(s.length>1)
						value = s[1].trim();
					if(key.equals("namespace")){
						a.namespace=Integer.parseInt(value);
					} else if(key.equals("title")){
						a.title = value;
					} else if(key.equals("content")){
						readingContent = true;
					}
				}
			}
			articles.add(a);
		} catch (IOException e) {
			e.printStackTrace();
		}
	}

	/**
	 * @return Returns the articles.
	 */
	public ArrayList<TestArticle> getArticles() {
		return articles;
	}

}
