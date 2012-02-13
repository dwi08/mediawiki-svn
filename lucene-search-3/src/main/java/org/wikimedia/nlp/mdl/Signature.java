package org.wikimedia.nlp.mdl;

import java.util.HashMap;


public class Signature {

	private HashMap<Atom,String> signatures = new HashMap<Atom,String>();
	
	public void addKey(Atom keySource, Atom keyTarget ){	
		signatures.put(keyTarget, signatures.get(keySource));
	}
	
	public void addKeyValue(Atom lexeme, String suffixses){	
		signatures.put(lexeme, suffixses);
	}

	public void addKey(Atom lexeme){	
		signatures.put(lexeme,null);
	}

	public boolean hasKey(Atom key) {		
		
		return signatures.containsKey(key);
	}

	
	public HashMap<Atom,String> getSignatures() {
		
		return  new HashMap<Atom,String>(signatures);
	}

	public String getVal(Atom lexeme) {
		
		return signatures.get(lexeme);
	}
	
}
