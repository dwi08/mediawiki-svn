package org.wikimedia.lsearch.test;

import java.io.File;

import junit.framework.TestCase;

import org.wikimedia.lsearch.analyzers.WikiQueryParser;
import org.wikimedia.lsearch.config.Configuration;
import org.wikimedia.lsearch.config.GlobalConfiguration;

public abstract class WikiTestCase extends TestCase {
	
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
					File.separator+
					"test-data"+
					File.separator+
					"lsearch.conf.test");

			Configuration.setGlobalConfigUrl(
					"file://"+
					winPathFixer+
					System.getProperty("user.dir")+					
					File.separator+"test-data"+
					File.separator+"lsearch-global.test");
			
			config = Configuration.open();
			global = GlobalConfiguration.getInstance();
			WikiQueryParser.TITLE_BOOST = 2;
			WikiQueryParser.ALT_TITLE_BOOST = 6;
			WikiQueryParser.CONTENTS_BOOST = 1;
		}
	}

}
