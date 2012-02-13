package org.wikimedia.nlp.mdl;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class MdlMorphology {

	/**new words*/
	protected List<Atom> lexemesLst = new ArrayList<Atom>(); 
	
	/** stems and roots*/	
	protected List<Atom> stemLst = new ArrayList<Atom>();
	
	/** suffixes */	
	protected List<Atom> affixLst = new ArrayList<Atom>();
	
	//stem to signature map
	Map<Atom,Signature> morphology= new HashMap<Atom,Signature>();
	
	public void addLexeme(String text,Long frequency,String langId){
		new Atom(text, frequency,langId);
		
	}
	
	public void addLexeme(String text){
		addLexeme(text,1l,"unknown");
		
	}
	
}
