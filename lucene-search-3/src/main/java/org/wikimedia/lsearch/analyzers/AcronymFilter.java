package org.wikimedia.lsearch.analyzers;

import java.io.IOException;

import org.apache.lucene.analysis.Token;
import org.apache.lucene.analysis.TokenFilter;
import org.apache.lucene.analysis.TokenStream;

/**
 * Filters acronyms tokens to tokens without internal dots.
 * 
 */
public class AcronymFilter extends TokenFilter {

	Token buffered = null;						//TODO: document buffer behavior.
	
	public AcronymFilter(TokenStream input) {
		super(input);
	}
	
	
	@Override 
	public Token next(Token reusableToken) throws IOException {
		
		if(buffered != null){
			reusableToken = buffered;
			buffered = null;
			return reusableToken;
		}
		reusableToken = input.next(reusableToken);
		if(reusableToken == null)
			return null;
		
		if(isAcronym(reusableToken.termBuffer())){
			buffered = new Token(filteredBuffer.toString(),reusableToken.startOffset(),reusableToken.endOffset(),reusableToken.type());
			buffered.setPositionIncrement(0);
		}
		return reusableToken;
	}
	
	StringBuffer filteredBuffer = new StringBuffer();

	/**
	 * check is a token is an acronym and gen filtered version 
	 * 
	 * @param buffer
	 * @param start
	 * @param end
	 * @return
	 */
	protected boolean isAcronym(char[] buffer){		
		
		boolean isAlpha=false;
		boolean hasDot=false;
		//boolean isNumeric=false;
		
		filteredBuffer.setLength(0);
		
		char c=' ';
		
		for (int offset = 0; offset < buffer.length; offset++) {
			c = buffer[offset];

			if (c == '.') {
				hasDot = true;
			} else {

				// side effect - filter the dot
				filteredBuffer.append(c);				

				if (!isAlpha && c >= '0' && c <= '9') { 
					//isNumeric = true;
				} else {
					isAlpha = true;
				}
				
				//process full string 				 
			}
		}

		return 	hasDot && isAlpha  ;
	}
	
	protected boolean hasDot(char[] buffer){
		for(char c: buffer){
			if (c=='.') return true;
		}
		return false;
	}
	
	protected boolean isNumber(char[] buffer){
		for(char c: buffer){
			if(! ((c >= '0' && c <='9') || (c=='.') ))
				return false;
		}
		return true;
	}
	
	
	
}
