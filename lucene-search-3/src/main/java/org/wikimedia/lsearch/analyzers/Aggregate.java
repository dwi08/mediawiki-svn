package org.wikimedia.lsearch.analyzers;

import java.io.IOException;
import java.io.StringReader;
import java.util.ArrayList;
import java.util.HashSet;

import org.apache.lucene.analysis.Analyzer;
import org.apache.lucene.analysis.Token;
import org.apache.lucene.analysis.TokenStream;
import org.apache.lucene.analysis.tokenattributes.OffsetAttribute;
import org.apache.lucene.analysis.tokenattributes.TermAttribute;
import org.wikimedia.lsearch.config.IndexId;

/**
 * Aggregate bean that captures information about one
 * item going into the some index aggregate field. 
 * 
 * @author rainman
 *
 */
public class Aggregate {
	protected ArrayList<Token> tokens;
	protected float boost;
	protected int noStopWordsLength;
	protected Flags flags;
	
	public enum Flags { NONE, ALTTITLE, ANCHOR, RELATED, SECTION };
	
	/** Construct from arbitrary text that will be tokenized 
	 * @throws IOException */
	public Aggregate(String text, float boost, IndexId iid, Analyzer analyzer, 
			String field, HashSet<String> stopWords, Flags flags) throws IOException{
		setTokens(toTokenArray(analyzer.tokenStream(field,new StringReader(text))),stopWords);
		this.boost = boost;
		this.flags = flags;
		
	}
	/** Set new token array, calc length, etc.. */
	public void setTokens(ArrayList<Token> tokens, HashSet<String> stopWords){
		this.tokens = tokens;
		if(stopWords != null){
			noStopWordsLength = 0;		
			for(Token t : tokens){
				if(!stopWords.contains(t.termText()) && t.getPositionIncrement()!=0)
					noStopWordsLength++;
			}
		} else{
			noStopWordsLength = noAliasLength();
		}
	}
	/** Number of tokens without aliases */
	public int noAliasLength(){
		int len = 0;
		for(Token t : tokens){
			if(t.getPositionIncrement() != 0)
				len++;
		}
		return len;
	}
	
	/** Construct with specific analyzer  
	 * @throws IOException */
	public Aggregate(String text, float boost, IndexId iid, Analyzer analyzer, 
			String field, Flags flags) throws IOException{		
		this.tokens = toTokenArray(analyzer.tokenStream(field,new StringReader(text)));
		this.boost = boost;
		this.noStopWordsLength = noAliasLength();
		this.flags = flags;
	}
	
	private ArrayList<Token> toTokenArray(TokenStream tokenStream) throws IOException {
		ArrayList<Token> tt = new ArrayList<Token>();
		
		//	TODO: remove 2.9.x api
		
		/**	
			Token reusableToken = new Token();
			while ((reusableToken = tokenStream.next(reusableToken)) != null
					&& tt.size() < 0xff - 1) {
				tt.add(reusableToken);
			}
		
		*/
			OffsetAttribute offsetAttribute = (OffsetAttribute) tokenStream.getAttribute(OffsetAttribute.class);
 
			TermAttribute termAttribute = (TermAttribute) tokenStream.getAttribute(TermAttribute.class);
			//TODO: update above to 3.5 api by replacing with
			//CharTermAttribute charTermAttribute = (CharTermAttribute) tokenStream.getAttribute(CharTermAttribute.class);


			while (tokenStream.incrementToken() && tt.size() < 0xff - 1) {
			   
				tt.add(new Token(termAttribute.term(),offsetAttribute.startOffset(),offsetAttribute.endOffset()));
				//TODO: update above to 3.5 api replacing with
				//tt.add(new Token(charTermAttribute.toString(),offsetAttribute.startOffset(),offsetAttribute.endOffset()));
				

			}
		//}
		return tt;
	}
	
	
	

	/** Number of tokens */
	public int length(){
		if(tokens != null)
			return tokens.size();
		else
			return 0;
	}
	
	/** Number of tokens when stop words are excluded */
	public int getNoStopWordsLength(){
		return noStopWordsLength;
	}
	
	/** boost factor */
	public float boost(){
		return boost;
	}

	public Token getToken(int index){
		return tokens.get(index);
	}
	
	public ArrayList<Token> getTokens() {
		return tokens;
	}
	
	public Flags getFlags() {
		return flags;
	}
	/** 
	 * Generate the meta field stored contents 
	 * format: [length] [length without stop words] [boost] [complete length] [flags] (1+1+4+1+1 bytes) 
	 */
	public static byte[] serializeAggregate(ArrayList<Aggregate> items){
		byte[] buf = new byte[items.size() * 8];
		
		for(int i=0;i<items.size();i++){
			Aggregate ag = items.get(i);
			assert ag.length() < 0xff;
			assert ag.noAliasLength() < 0xff;
			assert ag.getNoStopWordsLength() < 0xff;
			buf[i*8] = (byte)(ag.noAliasLength() & 0xff);
			buf[i*8+1] = (byte)(ag.getNoStopWordsLength() & 0xff);
			int boost = Float.floatToIntBits(ag.boost()); 
	      buf[i*8+2] = (byte)((boost >>> 24) & 0xff);
	      buf[i*8+3] = (byte)((boost >>> 16) & 0xff);
	      buf[i*8+4] = (byte)((boost >>> 8) & 0xff);
	      buf[i*8+5] = (byte)((boost >>> 0) & 0xff);
	      buf[i*8+6] = (byte)(ag.length() & 0xff);
	      buf[i*8+7] = (byte)(ag.getFlags().ordinal() & 0xff);
		}
		
		return buf;		
	}
	
	
}
