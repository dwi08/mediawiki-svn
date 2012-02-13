package org.wikimedia.nlp.mdl;

import static org.junit.Assert.*;

import org.junit.Before;
import org.junit.Test;

public class MdlMorphologyTest {

	int maxLength=0;
	
	public long genFrequency(String input)
	{
		
		return (1+maxLength-input.length())*20;
		
	}
	
	Signature sig ;
	Atom govern;
	Atom government;
	Atom governnor;
	@Before
	public void setUp(){
		
		String[] words = new String[]{ "govern", "government", "governnor"};
		
		 govern = new Atom(words[1],genFrequency(words[1]),"en");
		 government = new Atom(words[2],genFrequency(words[2]),"en");
		 governnor = new Atom(words[3],genFrequency(words[3]),"en");		
		 sig = new Signature(); 
		
	}
	@Test
	public void test() {
		
		
		
		fail("Not yet implemented");
	}

}
