package org.wikimedia.lsearch.test;

import java.io.File;
import java.io.IOException;
import java.io.StringReader;
import java.util.ArrayList;
import java.util.Arrays;

import junit.framework.TestCase;

import org.apache.lucene.analysis.Analyzer;
import org.apache.lucene.analysis.Token;
import org.apache.lucene.analysis.TokenStream;
import org.junit.Before;
import org.wikimedia.lsearch.analyzers.WikiQueryParser;
import org.wikimedia.lsearch.config.Configuration;
import org.wikimedia.lsearch.config.GlobalConfiguration;


/**
 * parent class for test that require that the configuration files be initialized
 * 
 * @author rainman
 *
 */
public abstract class AbstractWikiTestCase extends TestCase {
	
	public Configuration getConfig() {
		return config;
	}


	
	public void setConfig(final Configuration config) {
		this.config = config;
	}


	public GlobalConfiguration getGlobal() {
		return global;
	}


	public void setGlobal(final GlobalConfiguration global) {
		this.global = global;
	}


	protected Configuration config = null;
	
	protected GlobalConfiguration global = null;
	
	@Override
	@Before
	protected void setUp()  {
		if(config == null)
		{			
			String winPathFixer; 
			if(System.getProperty("os.name").startsWith("Windows")){
				winPathFixer = File.separator;
			}else{
				winPathFixer="";
			}
				
			Configuration.setConfigFile(
					System.getProperty("user.dir")+
					winPathFixer+
					File.separator+"src"+
					File.separator+"test"+
					File.separator+"resources"+
					File.separator+"lsearch.conf.test");

			Configuration.setGlobalConfigUrl(
					"file://"+
					winPathFixer+
					System.getProperty("user.dir")+
					File.separator+"src"+
					File.separator+"test"+
					File.separator+"resources"+
					File.separator+"lsearch-global.test");
			
			config = Configuration.open();
			global = GlobalConfiguration.getInstance();
			WikiQueryParser.TITLE_BOOST = 2;
			WikiQueryParser.ALT_TITLE_BOOST = 6;
			WikiQueryParser.CONTENTS_BOOST = 1;
		}
	}

	
	
	protected Analyzer a = null;
	
	public static Token[] tokensFromAnalysis(Analyzer analyzer, String text, String field) throws IOException {
		TokenStream stream = analyzer.tokenStream(field, new StringReader(text));
		ArrayList<Token> tokenList = new ArrayList<Token>();
		while (true) {
			Token token = stream.next();
			if (token == null) break;
			tokenList.add(token);
		}
		return (Token[]) tokenList.toArray(new Token[0]);
	}

	public String tokens(String text){
		try{
			return Arrays.toString(tokensFromAnalysis(a,text,"contents"));
		} catch(IOException e){
			fail(e.getMessage());
			return null;
		}
	}
	
}
