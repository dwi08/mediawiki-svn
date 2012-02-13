package org.wikimedia.nlp.mdl;

import static org.junit.Assert.*;

import org.hamcrest.CoreMatchers;
import org.hamcrest.core.IsEqual;
import org.junit.Before;
import org.junit.Test;
import org.junit.matchers.JUnitMatchers;
import org.wikimedia.nlp.mdl.Atom;
import org.wikimedia.nlp.mdl.Signature;

import static org.hamcrest.CoreMatchers.*;
//import static org.junit.matchers.JUnitMatchers.*; 

public class SignatureTest {

	Signature sig ;
	Atom govern = new Atom("govern",6l);
	Atom government = new Atom("government",5l);
	Atom governnor = new Atom("governnor",3l);
	@Before
	public void setUp(){
		
		 sig = new Signature(); 
		
	}
	
	@Test
	public void testAddKeyValue() {
		
		sig.addKeyValue(govern, "ment,nor");
				
		assertTrue("added",sig.hasKey(govern));
		assertFalse("not added",sig.hasKey(governnor));
	}
	
	@Test
	public void testKeyAvailable() {
		
		sig.addKey(govern);
		sig.addKey(government);		
		
		assertTrue(sig.hasKey(govern));		
		assertFalse(sig.hasKey(governnor));
	}	
	
	@Test
	public void testAddKeytoKey() {
		String suffixList="ment,nor";
		
		sig.addKeyValue(govern,suffixList );
		sig.addKey(govern, government);
				
		assertThat(sig.getVal(govern), equalTo(suffixList));
		assertThat(sig.getVal(government), equalTo(suffixList));
		assertThat(sig.getVal(governnor), not(equalTo(suffixList)));
		
	}
	

}
