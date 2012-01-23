package org.apache.lucene.search;

import java.io.IOException;
import java.util.Vector;

import org.apache.lucene.index.IndexReader;
import org.apache.lucene.index.Term;
import org.apache.lucene.index.TermPositions;
import org.apache.lucene.util.ToStringUtils;

/**
 * Phrase query with 
 * 1) extra boost for different position ranges within the document 
 * 2) ability to use aggregate positional information if available 
 * 
 * @author rainman
 *
 */
public class PositionalQuery extends Query {

	  private String field;
	  private Vector<Term> terms = new Vector<Term>();
	  private Vector<Integer> positions = new Vector<Integer>();
	  private int slop = 0;
	  
	  /** Sets the number of other words permitted between words in query phrase.
	    If zero, then this is an exact phrase search.  For larger values this works
	    like a <code>WITHIN</code> or <code>NEAR</code> operator.

	    <p>The slop is in fact an edit-distance, where the units correspond to
	    moves of terms in the query phrase out of position.  For example, to switch
	    the order of two words requires two moves (the first move places the words
	    atop one another), so to permit re-orderings of phrases, the slop must be
	    at least two.

	    <p>More exact matches are scored higher than sloppier matches, thus search
	    results are sorted by exactness.

	    <p>The slop is zero by default, requiring exact matches.*/
	  public void setSlop(int s) { slop = s; }
	  /** Returns the slop.  See setSlop(). */
	  public int getSlop() { return slop; }
	  
	/**
	 * 
	 */
	private static final long serialVersionUID = 3882888014364425710L;
	protected PositionalOptions options; 
	protected int stopWordCount = 0;
	
	public PositionalQuery(PositionalOptions options){
		this.options = options;
	}

	  /**
	   * Adds a term to the end of the query phrase.
	   * The relative position of the term is the one immediately after the last term added.
	   */
	  public void add(Term term) {
	    int position = 0;
	    if(positions.size() > 0)
	        position = positions.lastElement().intValue() + 1;

	    add(term, position);
	  }
	  
	  /**
	   * Adds a term to the end of the query phrase.
	   * The relative position of the term within the phrase is specified explicitly.
	   * This allows e.g. phrases with more than one term at the same position
	   * or phrases with gaps (e.g. in connection with stopwords).
	   * 
	   * @param term
	   * @param position
	   */
	  public void add(Term term, int position) {
	      if (terms.size() == 0)
	          field = term.field();
	      else if (term.field() != field)
	          throw new IllegalArgumentException("All phrase terms must be in the same field: " + term);

	      terms.addElement(term);
	      positions.addElement(new Integer(position));
	  }
	  
	/** Add to end of phrase */
	public void add(Term term, boolean isStopWord) {
		if(isStopWord)
			stopWordCount++;
		add(term);
	}

	
	
	/** Add to position in phrase */
	public void add(Term term, int position, boolean isStopWord) {
		if(isStopWord)
			stopWordCount++;
		add(term,position);
	}
	

	  /** Prints a user-readable version of this query. */
	  public String toString(String f) {
	    StringBuffer buffer = new StringBuffer();
	    if (!field.equals(f)) {
	      buffer.append(field);
	      buffer.append(":");
	    }

	    buffer.append("\"");
	    for (int i = 0; i < terms.size(); i++) {
	      buffer.append(terms.elementAt(i).text());
	      if (i != terms.size()-1)
	  buffer.append(" ");
	    }
	    buffer.append("\"");

	    if (slop != 0) {
	      buffer.append("~");
	      buffer.append(slop);
	    }

	    buffer.append(ToStringUtils.boost(getBoost()));

	    return "(P "+buffer.toString()+")";
	  }
	

	
	protected Weight createWeight(Searcher searcher) throws IOException {
		return new PositionalWeight(searcher);
	}
	

	  /**
	   * Returns the relative positions of terms in this phrase.
	   */
	  public int[] getPositions() {
	      int[] result = new int[positions.size()];
	      for(int i = 0; i < positions.size(); i++)
	          result[i] = positions.elementAt(i).intValue();
	      return result;
	  }
	
	/**
	 * Weight
	 * 
	 * @author rainman
	 *
	 */
	protected class PositionalWeight implements Weight//PhraseWeight
	{		
		
	    private Similarity similarity;
	    private float value;
	    private float idf;
	    private float queryNorm;
	    private float queryWeight;

		/**
		 * 
		 */
		private static final long serialVersionUID = 8588472089439448361L;

		
		public PositionalWeight(Searcher searcher) throws IOException{
		      this.similarity = getSimilarity(searcher);

		      idf = similarity.idf(terms, searcher);
		}
		
	    public void normalize(float queryNorm) {
	        this.queryNorm = queryNorm;
	        queryWeight *= queryNorm;                   // normalize query weight
	        value = queryWeight * idf;                  // idf for document 
	      }
		
		public Scorer scorer(IndexReader reader) throws IOException {
			if (terms.size() == 0)			  // optimize zero-term case
				return null;
						
			TermPositions[] tps = new TermPositions[terms.size()];
			for (int i = 0; i < terms.size(); i++) {
				TermPositions p = reader.termPositions(terms.elementAt(i));
				if (p == null)
					return null;
				tps[i] = p;
			}			
			
			// init aggregate meta field if any
			if(options.aggregateMeta != null)
				options.aggregateMeta.init(reader,field);
			
			if(options.rankMeta != null)
				options.rankMeta.init(reader,field);

			if( terms.size() == 1)
				return new PositionalScorer.TermScorer(this, tps, getPositions(), stopWordCount, 
						similarity,reader.norms(field), options);
			else if( slop == 0 )				 
				return new PositionalScorer.ExactScorer(this, tps, getPositions(), stopWordCount,
						similarity,	reader.norms(field), options);
			else
				return new PositionalScorer.SloppyScorer(this, tps, getPositions(), stopWordCount, 
						similarity, slop,	reader.norms(field), options);
		}
		
	    public Explanation explain(IndexReader reader, int doc)
	      throws IOException {

	      Explanation result = new Explanation();
	      result.setDescription("weight(custom["+getQuery()+"] in "+doc+"), product of:");

	      StringBuffer docFreqs = new StringBuffer();
	      StringBuffer query = new StringBuffer();
	      query.append('\"');
	      for (int i = 0; i < terms.size(); i++) {
	        if (i != 0) {
	          docFreqs.append(" ");
	          query.append(" ");
	        }

	        Term term = terms.elementAt(i);

	        docFreqs.append(term.text());
	        docFreqs.append("=");
	        docFreqs.append(reader.docFreq(term));

	        query.append(term.text());
	      }
	      query.append('\"');

	      Explanation idfExpl =
	        new Explanation(idf, "idf(" + field + ": " + docFreqs + ")");

	      // explain query weight
	      Explanation queryExpl = new Explanation();
	      queryExpl.setDescription("queryWeight(" + getQuery() + "), product of:");

	      Explanation boostExpl = new Explanation(getBoost(), "boost");
	      if (getBoost() != 1.0f)
	        queryExpl.addDetail(boostExpl);
	      queryExpl.addDetail(idfExpl);

	      Explanation queryNormExpl = new Explanation(queryNorm,"queryNorm");
	      queryExpl.addDetail(queryNormExpl);

	      queryExpl.setValue(boostExpl.getValue() *
	                         idfExpl.getValue() *
	                         queryNormExpl.getValue());

	      result.addDetail(queryExpl);

	      // explain field weight
	      Explanation fieldExpl = new Explanation();
	      fieldExpl.setDescription("fieldWeight("+field+":"+query+" in "+doc+
	                               "), product of:");

	      Explanation tfExpl = scorer(reader).explain(doc);
	      fieldExpl.addDetail(tfExpl);
	      
	      fieldExpl.addDetail(idfExpl);

	      Explanation fieldNormExpl = new Explanation();
	      //byte[] fieldNorms = reader.norms(field);
	      //fieldNorms!=null ? Similarity.decodeNorm(fieldNorms[doc]) : 0.0f;
	      float fieldNorm = 1;
	      fieldNormExpl.setValue(fieldNorm);
	      fieldNormExpl.setDescription("fieldNorm(field="+field+", doc="+doc+")");
	      fieldExpl.addDetail(fieldNormExpl);

	      fieldExpl.setValue(tfExpl.getValue() *
	                         idfExpl.getValue() *
	                         fieldNormExpl.getValue());

	      result.addDetail(fieldExpl);

	      // combine them
	      result.setValue(queryExpl.getValue() * fieldExpl.getValue());

	      if (queryExpl.getValue() == 1.0f)
	        return fieldExpl;

	      return result;
	    }
	    public Query getQuery() { return PositionalQuery.this; }
	    public float getValue() { return value; }

	    public float sumOfSquaredWeights() {
	      queryWeight = idf * getBoost();             // compute query weight
	      return queryWeight * queryWeight;           // square it
	    }
	}

	@Override
	public int hashCode() {
		final int PRIME = 31;
		int result = super.hashCode();
		result = PRIME * result + ((options == null) ? 0 : options.hashCode());
		result = PRIME * result + stopWordCount;
		return result;
	}

	@Override
	public boolean equals(Object obj) {
		if (this == obj)
			return true;
		if (!super.equals(obj))
			return false;
		if (getClass() != obj.getClass())
			return false;
		final PositionalQuery other = (PositionalQuery) obj;
		if (options == null) {
			if (other.options != null)
				return false;
		} else if (!options.equals(other.options))
			return false;
		if (stopWordCount != other.stopWordCount)
			return false;
		return true;
	}
	
	
}
