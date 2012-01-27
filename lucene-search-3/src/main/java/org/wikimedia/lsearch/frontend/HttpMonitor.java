package org.wikimedia.lsearch.frontend;

import java.util.ArrayList;
import java.util.Collections;
import java.util.Comparator;
import java.util.Map.Entry;
import java.util.concurrent.ConcurrentHashMap;

import org.apache.log4j.Level;
import org.apache.log4j.Logger;

final public class HttpMonitor extends Thread { // NOPMD by oren on 1/27/12 6:58 PM
	private static final Logger LOGGER = Logger.getLogger(HttpMonitor.class);
	private static HttpMonitor instance;
	/** times when HTTP request have been started */
	private final transient ConcurrentHashMap<HttpHandler,Long> startTimes = new ConcurrentHashMap<HttpHandler,Long>();
	
	/** threshold in milliseconds for reporting */
	private final static long THREASHOLD = 10000;
	
	private HttpMonitor(){ super();}
	
	/** Get a running HttpMonitor instance */
	public synchronized static HttpMonitor getInstance(){ // NOPMD by oren on 1/27/12 7:09 PM
		if(instance == null){
			instance = new HttpMonitor();
			instance.start();
		}
			
		return instance;
	}
	
	@Override
	public void run() {		
		LOGGER.info("HttpMonitor thread started");		
		
		for(;;){
			try {				
				// sleep until next check
				Thread.sleep(THREASHOLD);
				final long cur = System.currentTimeMillis();
				
				// check for long-running HTTP request				
				final ConcurrentHashMap<HttpHandler,Long> times = (ConcurrentHashMap<HttpHandler, Long>) startTimes; //
				for(Entry<HttpHandler,Long> e : times.entrySet()){
					final long timeWait = cur - e.getValue();
					if(timeWait > THREASHOLD && LOGGER.isEnabledFor(Level.WARN) ){					
						LOGGER.warn(e.getKey()+" is waiting for "+timeWait+" ms on "+e.getKey().rawUri);
					}
				}
			} catch (InterruptedException e) {
				LOGGER.error("HttpMonitor thread interrupted",e);
			}
		}
	}
	
	/** Mark HTTP request start */
	public void requestStart(final HttpHandler thread){
		startTimes.put(thread,System.currentTimeMillis());
	}
	
	/** Mark HTTP request end */
	public void requestEnd(final HttpHandler thread){
		startTimes.remove(thread);
	}
	
	public String printReport(){
		final StringBuilder stringBuilder = new StringBuilder();	
		final ConcurrentHashMap<HttpHandler,Long> times = (ConcurrentHashMap<HttpHandler, Long>) startTimes;
		final ArrayList<Entry<HttpHandler, Long>> sorted = new ArrayList<Entry<HttpHandler,Long>>(times.entrySet()); 
		Collections.sort(sorted, new Comparator<Entry<HttpHandler,Long>>() {
			public int compare(final Entry<HttpHandler, Long> originalEntry,
					final Entry<HttpHandler, Long> otherEntry) {
				return (int) (otherEntry.getValue() - originalEntry.getValue());
			}
		});
		
		final long cur = System.currentTimeMillis();
		
		for(Entry<HttpHandler,Long> e : sorted){
			final long timeWait = cur - e.getValue();
			stringBuilder.append("[ "+timeWait+" ms ] "+ e.getKey().rawUri +"\n");
		}
		
		return stringBuilder.toString();
	}
	
}
