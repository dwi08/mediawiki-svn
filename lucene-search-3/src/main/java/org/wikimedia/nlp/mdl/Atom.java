package org.wikimedia.nlp.mdl;

/**
 * atoms are POJO representing either unanalyzed Lexemes or Morphemes  
 * @author oren
 *
 */
public class Atom implements Comparable<Atom> {

	/** does this atom have a morphological analysis */
	private String langId="Unknown";	//iso code
	private boolean isAnalysed=false;
	private double  analysisConfidence;
	private boolean isStem=false;
	private boolean isAffix=false;	
	
	private String string;
	private Long frequency;
	
	
	private SortMode sortMode = SortMode.FRQ;

	public Atom(String string, Long frequency) {
		this.string=string;
		this.frequency=frequency;
	}

	public Atom(String string, Long frequency, String langId) {
		
		this.string=string;
		this.frequency=frequency;
		this.langId=langId;
	}

	public int compareTo(Atom o) {
		
		switch (sortMode){
			
			case FRQ:
				return (int) (frequency-o.frequency);
			
			default:
				return  string.compareTo(o.string);
		}
		

	}

}
