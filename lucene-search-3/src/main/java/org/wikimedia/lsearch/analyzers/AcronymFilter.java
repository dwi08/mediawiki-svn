package org.wikimedia.lsearch.analyzers;

import java.io.IOException;

import org.apache.lucene.analysis.Token;
import org.apache.lucene.analysis.TokenFilter;
import org.apache.lucene.analysis.TokenStream;

/**
 * Removes dots from acronyms?
 */
public class AcronymFilter extends TokenFilter {
	Token buffered = null;
	
	public AcronymFilter(TokenStream input) {
		super(input);
	}

	@Override 
	public Token next(Token nextToken) throws IOException {
		if(buffered != null){
			nextToken = buffered;
			buffered = null;
			return nextToken;
		}
		nextToken = input.next();
		if(nextToken == null)
			return null;
		if(nextToken.termText().contains(".") && !isNumber(nextToken.termText())){
			buffered = new Token(nextToken.termText().replace(".",""),nextToken.startOffset(),nextToken.endOffset(),nextToken.type());
			buffered.setPositionIncrement(0);
		}
		return nextToken;
	}
	
	protected boolean isNumber(String str){
		for(int i=0;i<str.length();i++){
			char c = str.charAt(i);
			if(! ((c >= '0' && c <='9') || (c=='.') ))
				return false;
		}
		return true;
	}
	
	
	
}
