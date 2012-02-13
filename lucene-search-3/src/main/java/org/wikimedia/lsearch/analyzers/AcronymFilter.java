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

	protected transient Token buffered = null; // TODO: document buffer behavior.

	public AcronymFilter(final TokenStream input) {
		super(input);
	}

	@Override
	public Token next(Token reusableToken) throws IOException { // NOPMD by oren on 2/13/12 12:41 AM

		if (buffered == null) {
			reusableToken = input.next(reusableToken);
			if (reusableToken != null && isAcronym(reusableToken.termBuffer())) {
				buffered = new Token(filteredBuffer.toString(),
						reusableToken.startOffset(), reusableToken.endOffset(),
						reusableToken.type());
				buffered.setPositionIncrement(0);
			}
		}else{
			reusableToken = buffered;
			buffered = null; // NOPMD by oren on 2/13/12 1:00 AM
			
		}
		return reusableToken;
	}

	protected transient StringBuffer filteredBuffer = new StringBuffer(); // NOPMD by oren on 2/13/12 1:00 AM

	/**
	 * check is a token is an acronym and gen filtered version
	 * 
	 * @param buffer
	 * @param start
	 * @param end
	 * @return
	 */
	protected boolean isAcronym(final char[] buffer) {

		boolean isAlpha = false;
		boolean hasDot = false; // NOPMD by oren on 2/13/12 12:53 AM
		// boolean isNumeric=false;

		filteredBuffer.setLength(0);

		for (int offset = 0; offset < buffer.length; offset++) {
			final char character = buffer[offset];

			if (character == '.') {
				hasDot = true; // NOPMD by oren on 2/13/12 12:53 AM
			} else {

				// side effect - filter the dot
				filteredBuffer.append(character);

				if (isAlpha || character < '0' || character > '9') {
					isAlpha = true;
				}

				// process full string
			}
		}

		return hasDot && isAlpha;
	}


}
