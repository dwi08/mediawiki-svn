/*
 * Copyright 2004 Kate Turner
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy 
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights 
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
 * copies of the Software, and to permit persons to whom the Software is 
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in 
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * 
 * $Id: Configuration.java 8330 2005-04-14 02:39:50Z vibber $
 */
package org.wikimedia.lsearch.config;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.net.MalformedURLException;
import java.net.URL;
import java.util.Properties;

import org.apache.log4j.BasicConfigurator;
import org.apache.log4j.Level;
import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.apache.log4j.helpers.LogLog;

/**
 * Singleton configuration class
 * 
 * @author Kate Turner, Brion Vibber
 *
 */
public class Configuration {
	private static Configuration instance;
	private static String configFile = null; 
	private static String globalConfigUrl = null;
	private static boolean verbose = true; // print info and error messages
	
	protected final String CONF_FILE_NAME = "lsearch.conf";
	protected final String TEST_CONF_FILE_NAME = "lsearch.conf.test";
	
	/* os independent path component */
	private final static String ETC_FOLDER = System.getProperty("file.separator") + "etc";
	private final static String TEST_DATA_FOLDER = System.getProperty("file.separator") + "test-data";
	
	
	public static final String PATH_SEP = System.getProperty("file.separator");
	
	public static void setConfigFile(String file) {
		configFile = file;
	}
	
	/** Returns an instance of Configuration singleton class */
	public static synchronized Configuration open() {
		if (instance == null)
			instance = new Configuration();
		return instance;
	}
	
	private Configuration() {
				
		if (configFile == null) {
			String home = System.getProperty("user.home"); 			
			String [] paths;
			String filename = System.getProperty("file.separator") + CONF_FILE_NAME;
			String testFilename = System.getProperty("file.separator") + TEST_CONF_FILE_NAME;
			if (home == null) {
				paths = new String[] {
					System.getProperty("user.dir") + filename,
					ETC_FOLDER+filename };
			} else {	
				paths = new String[] {
					home+System.getProperty("file.separator")+ "."  + CONF_FILE_NAME, //home
					System.getProperty("user.dir") + filename,                        //where invoked
					ETC_FOLDER+filename,                                              //in etc
					System.getProperty("user.dir") +TEST_DATA_FOLDER + testFilename   //in test-data
				};
			}
			openPropertiesFile(paths);
		} else {
			openPropertiesFile(new String[] { configFile });
		}
		
		if (getBoolean("Logging", "debug")) {
			LogLog.setInternalDebugging(true);
		}
		
		String logconfig = getString("Logging","logconfig");
		if (logconfig == null) {
			// debug!
			if(verbose)
				System.out.println("Errors will be logged to console...");
			BasicConfigurator.configure();
			Logger.getRootLogger().setLevel(Level.INFO);
		} else {
			PropertyConfigurator.configure(logconfig);
		}
		
		// open the global configuration
		GlobalConfiguration global = GlobalConfiguration.getInstance();
		String globalurl = globalConfigUrl!=null? globalConfigUrl : getString("MWConfig","global");
		String indexpath = getString("Indexes","path");
		if(globalurl==null){
			System.out.println("FATAL: Need to define global configuration url in local config file.");
			System.exit(1);
		} else if(indexpath==null){
			System.out.println("FATAL: Need to define Indexes.path variable in local configuration.");
			System.exit(1);
		}
		try {
			global.readFromURL(new URL(globalurl),indexpath);
		} catch (MalformedURLException e) {
			System.out.println("Malformed URL "+globalurl+" cannot read global configuration (check MWConfig.global in "+CONF_FILE_NAME+"), exiting...");
			System.exit(1);
		} catch (IOException e) {
			System.out.println("Will not proceed without global configuration (make sure MWConfig.global in "+CONF_FILE_NAME+" points to right URL), exiting...");
			System.exit(1);
		}
	}
	private Properties props;
	
	/*
	 * trys to locate a properties file and open it.
	 */
	private void openPropertiesFile(String[] paths) 
	{	
		props = new Properties();
		String path="";
		
		for(int i=0;i<paths.length;i++){
			try {
				path = paths[i];
				if(verbose)
					System.out.println("Trying config file at path "+path);
				props.load(new FileInputStream(new File(path)));
				configFile = path;
				return;
			} catch (FileNotFoundException e3) {
				System.err.println("file not found at "+path);
				// try the next one
			} catch (IOException e3) {
				System.err.println("Error: IO error reading config: " + e3.getMessage());
				return;
			}
		} 
		System.out.println("FATAL: Couldn't find a config file");
		System.exit(1);
	}

	/** Returns the string property, or null if property is not set */ 
	public String getString(String section, String name) {
		return props.getProperty(section+"."+name);
	}
	/** Get string property, if not set will return default value */
	public String getString(String section, String name, String defaultValue) {
		String ret = getString(section,name);
		if(ret == null)
			return defaultValue;
		else
			return ret;
	}
	
	public String[] getArray(String section, String name) {		
		String s = props.getProperty(section+"."+name);
		if (s != null)
			return s.split(" ");
		return null;
	}

	public boolean getBoolean(String section, String name) {
		String s = getString(section, name);
		return s != null && s.equals("true");		
	}
	
	public boolean getBoolean(String section, String name, boolean defaultValue) {
		String s = getString(section, name);
		if( s != null)
			return s.equals("true");
		else
			return defaultValue;
	}
	
	public int getInt(String section, String name, int defaultValue) {
		String s = getString(section, name);
		if (s == null)
			return defaultValue;
		try {
			return Integer.parseInt(s);
		} catch (Exception e) {
			return defaultValue;
		}
	}
		
	public String getLanguage(String dbname) {
		String[] suffixes = getArray("Database", "suffix");
		if (suffixes == null)
			return "en";
		for (int i = 0; i < suffixes.length; i++) {
			if (dbname.endsWith(suffixes[i]))
				return dbname.substring(0, dbname.length() - suffixes[i].length());
		}		
		return "en";
	}

	public double getDouble(String section, String name, double defaultValue) {
		String s = getString(section, name);
		if(s==null) return defaultValue;
		try{
			double d = new Double(s).doubleValue();
			return d;
		} catch(Exception e){
			return defaultValue;
		}
	}

	public static void setVerbose(boolean v) {
		verbose = v;		
	}

	public static void setGlobalConfigUrl(String globalConfigUrl) {
		Configuration.globalConfigUrl = globalConfigUrl;
	}
}
