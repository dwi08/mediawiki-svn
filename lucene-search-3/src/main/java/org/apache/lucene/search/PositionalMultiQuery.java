package org.apache.lucene.search;

import java.io.IOException;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Collections;
import java.util.Iterator;
import java.util.List;
import java.util.Set;
import java.util.Vector;

import org.apache.lucene.index.IndexReader;
import org.apache.lucene.index.Term;
import org.apache.lucene.index.TermPositions;
import org.apache.lucene.util.ToStringUtils;

/**
 * MultiPhraseQuery with positional info
 * 
 * @author rainman
 *
 */
public class PositionalMultiQuery extends Query {
	/**
	 * 
	 */
	private static final long serialVersionUID = -7768563477711927881L;
	
	protected PositionalOptions options; 
	protected int stopWordCount = 0;
	protected ArrayList<ArrayList<Float>> boosts = new ArrayList<ArrayList<Float>>();
	protected boolean scaledBoosts = false;

	public PositionalMultiQuery(PositionalOptions options){
		this.options = options;
	}

	/** Add to pos with custom boost */
	public void addWithBoost(Term[] terms, int pos, ArrayList<Float> boost) {
		if(terms.length != boost.size())
			throw new RuntimeException("Mismached boost values for positional multi query");
		add(terms,pos);
		boosts.add(boost);
	}
	/** Add with custom boost */
	public void addWithBoost(Term[] terms, ArrayList<Float> boost){
		if(terms.length != boost.size())
			throw new RuntimeException("Mismached boost values for positional multi query");
		add(terms);
		boosts.add(boost);
	}
	
	
	  /** Prints a user-readable version of this query. */
	  public final String toString(String f) {
	    StringBuffer buffer = new StringBuffer();
	    if (!field.equals(f)) {
	      buffer.append(field);
	      buffer.append(":");
	    }

	    buffer.append("\"");
	    Iterator<Term[]> i = termArrays.iterator();
	    while (i.hasNext()) {
	      Term[] terms = i.next();
	      if (terms.length > 1) {
	        buffer.append("(");
	        for (int j = 0; j < terms.length; j++) {
	          buffer.append(terms[j].text());
	          if (j < terms.length-1)
	            buffer.append(" ");
	        }
	        buffer.append(")");
	      } else {
	        buffer.append(terms[0].text());
	      }
	      if (i.hasNext())
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
	  
	public Weight createWeight(Searcher searcher) throws IOException {
		return new PositionalMultiWeight(searcher);
	}

	
	
	
	/** 
	 * Weight 
	 * 
	 * @author rainman
	 *
	 */
	protected class PositionalMultiWeight  extends Weight {
		
		/**
		 * 
		 */
		private static final long serialVersionUID = 2888766082285532430L;



		public PositionalMultiWeight(Searcher searcher) throws IOException {
			
			
		  this.similarity = getSimilarity(searcher);


	      // compute idf - take average when multiple terms

		  this.idf = 0;
	      Iterator<Term[]> i = termArrays.iterator();
	      int count = 0;
	      while (i.hasNext()) {
	        Term[] terms = i.next();
	        float av = 0;
	        float[] idfs = new float[terms.length];
	        for (int j=0; j<terms.length; j++) {
	      	  idfs[j] = getSimilarity(searcher).idf(terms[j], searcher); 
	      	  av += idfs[j];
	        }
	        av /= terms.length;
	        idf += av;
	        
	        if(!scaledBoosts){
	      	  // rescale boosts to reinstall right idfs per term
	      	  ArrayList<Float> fb = boosts.get(count);
	      	  for(int j=0; j<idfs.length; j++){
	      		  fb.set(j,fb.get(j)*(idfs[j]/av));
	      	  } 	        
	        }
	        count++;
	      }
	      scaledBoosts = true;
		}

		
		@Override
		public Scorer scorer(IndexReader reader, boolean scoreDocsInOrder,
				boolean topScorer) throws IOException {

			return scorer(reader) ;	
		}
		
		public Scorer scorer(IndexReader reader) throws IOException {
			if (termArrays.size() == 0)                  // optimize zero-term case
				return null;

			TermPositions[] tps = new TermPositions[termArrays.size()];
			for (int i=0; i<tps.length; i++) {
				Term[] terms = termArrays.get(i);
				float[] boost = new float[terms.length];
				if(terms.length != boosts.get(i).size())
					throw new RuntimeException("Inconsistent term/boost data: terms="+Arrays.toString(terms)+", boosts="+boosts.get(i)+", in query="+PositionalMultiQuery.this);
				int j=0;
				for(Float f : boosts.get(i))
					boost[j++] = f;

				TermPositions p;
				if (terms.length > 1)
					p = new MultiBoostTermPositions(reader, terms, boost);
				else
					p = reader.termPositions(terms[0]);

				if (p == null)
					return null;

				tps[i] = p;
			}

			// init aggregate meta field if any
			if(options.aggregateMeta != null)
				options.aggregateMeta.init(reader,field);

			if(options.rankMeta != null)
				options.rankMeta.init(reader,field);

			if( tps.length == 1)
				return new PositionalScorer.TermScorer(this, tps, getPositions(), stopWordCount, 
						similarity,reader.norms(field), options);
			else if( slop == 0 )				 
				return new PositionalScorer.ExactScorer(this, tps, getPositions(), stopWordCount,
						similarity,	reader.norms(field), options);
			else
				return new PositionalScorer.SloppyScorer(this, tps, getPositions(), stopWordCount, 
						similarity, slop,	reader.norms(field), options);
		}

		
		public Explanation explain(IndexReader reader, int doc) throws IOException {
			ComplexExplanation result = new ComplexExplanation();
			result.setDescription("weight("+getQuery()+" in "+doc+"), product of:");

			Explanation idfExpl = new Explanation(idf, "idf("+getQuery()+")");

			// explain query weight
			Explanation queryExpl = new Explanation();
			queryExpl.setDescription("queryWeight(" + getQuery() + "), product of:");

			Explanation boostExpl = new Explanation(getBoost(), "boost (per term="+boosts+")");
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
			ComplexExplanation fieldExpl = new ComplexExplanation();
			fieldExpl.setDescription("fieldWeight("+getQuery()+" in "+doc+
			"), product of:");

			Explanation tfExpl = scorer(reader).explain(doc);
			fieldExpl.addDetail(tfExpl);
			fieldExpl.addDetail(idfExpl);

			Explanation fieldNormExpl = new Explanation();
			float fieldNorm = 1; // NO NORMS
			fieldNormExpl.setValue(fieldNorm);
			fieldNormExpl.setDescription("fieldNorm(field="+field+", doc="+doc+")");
			fieldExpl.addDetail(fieldNormExpl);

			fieldExpl.setMatch(Boolean.valueOf(tfExpl.isMatch()));
			fieldExpl.setValue(tfExpl.getValue() *
					idfExpl.getValue() *
					fieldNormExpl.getValue());

			result.addDetail(fieldExpl);
			result.setMatch(fieldExpl.getMatch());

			// combine them
			result.setValue(queryExpl.getValue() * fieldExpl.getValue());

			if (queryExpl.getValue() == 1.0f)
				return fieldExpl;

			return result;
		}

	
		    private Similarity similarity;
		    private float value;
		    private float idf;
		    private float queryNorm;
		    private float queryWeight;



		    public Query getQuery() { return PositionalMultiQuery.this; }
		    public float getValue() { return value; }

		    public float sumOfSquaredWeights() {
		      queryWeight = idf * getBoost();             // compute query weight
		      return queryWeight * queryWeight;           // square it
		    }

		    public void normalize(float queryNorm) {
		      this.queryNorm = queryNorm;
		      queryWeight *= queryNorm;                   // normalize query weight
		      value = queryWeight * idf;                  // idf for document 
		    }



		  
		  }

	
	
	public Query rewrite(IndexReader reader) {
		// optimize one-term case
	    if (termArrays.size() == 1 && (options==null || !options.takeMaxScore)) {                 
	      Term[] terms = termArrays.get(0);
	      ArrayList<Float> boost = boosts.get(0);
	      if(terms.length == 1){
	      	PositionalQuery pq = new PositionalQuery(options);
	      	pq.add(terms[0]);
	      	pq.setBoost(getBoost()*boost.get(0));
	      	return pq;
	      } else{
	      	BooleanQuery boq = new BooleanQuery(true);
	      	for (int i=0; i<terms.length; i++) {
	      		PositionalQuery pq = new PositionalQuery(options);
	      		pq.add(terms[i]);	
	      		pq.setBoost(boost.get(i));
	      		boq.add(pq, BooleanClause.Occur.SHOULD);
	      	}
	      	boq.setBoost(getBoost());
	      	return boq;
	      }
	    } else {
	      return this;
	    }
	  }
	
	@Override
	 /** Returns a hash code value for this object.*/
	public int hashCode() {
		final int PRIME = 31;
		int result = Float.floatToIntBits(getBoost())
			      ^ slop
			      ^ termArrays.hashCode()
			      ^ positions.hashCode()
			      ^ 0x4AC65113;
		result = PRIME * result + ((options == null) ? 0 : options.hashCode());
		result = PRIME * result + stopWordCount;
		return result;
	}
	
	/** Returns true if <code>o</code> is equal to this. */
	@Override	
	public boolean equals(Object obj) {
		
		if (this == obj)
			return true;
		
		if (!(obj instanceof MultiPhraseQuery)) {
			return false;		
		}
		
		//safe to cast
		final PositionalMultiQuery other = (PositionalMultiQuery)obj;

		if ( this.getBoost() != other.getBoost()
	      || this.slop != other.slop
	      || !this.termArrays.equals(other.termArrays)
	      || !this.positions.equals(other.positions)
	      || stopWordCount != other.stopWordCount
		  || this.getClass() != obj.getClass()
  		   )
		{
		      return false;
		}
		
		//compare options
		if (options == null) {
			if (other.options != null)
				return false;
		} else if (other.options == null 
			   || !options.equals(other.options)){
			return false;
		}
	
		return true;
	}


	
	/** copy of origian code */
	
	  private String field;
	  private ArrayList<Term[]> termArrays = new ArrayList<Term[]>();
	  private Vector<Integer> positions = new Vector<Integer>();

	  private int slop = 0;

	  /** Sets the phrase slop for this query.
	   * @see PhraseQuery#setSlop(int)
	   */
	  public void setSlop(int s) { slop = s; }

	  /** Sets the phrase slop for this query.
	   * @see PhraseQuery#getSlop()
	   */
	  public int getSlop() { return slop; }

	  /** Add a single term at the next position in the phrase.
	   * @see PhraseQuery#add(Term)
	   */
	  public void add(Term term) { add(new Term[]{term}); }

	  /** Add multiple terms at the next position in the phrase.  Any of the terms
	   * may match.
	   *
	   * @see PhraseQuery#add(Term)
	   */
	  public void add(Term[] terms) {
	    int position = 0;
	    if (positions.size() > 0)
	      position = positions.lastElement().intValue() + 1;

	    add(terms, position);
	  }

	  /**
	   * Allows to specify the relative position of terms within the phrase.
	   * 
	   * @see PhraseQuery#add(Term, int)
	   * @param terms
	   * @param position
	   */
	  public void add(Term[] terms, int position) {
	    if (termArrays.size() == 0)
	      field = terms[0].field();

	    for (int i = 0; i < terms.length; i++) {
	      if (terms[i].field() != field) {
	        throw new IllegalArgumentException(
	            "All phrase terms must be in the same field (" + field + "): "
	                + terms[i]);
	      }
	    }

	    termArrays.add(terms);
	    positions.addElement(new Integer(position));
	  }

	  /**
	   * Returns a List<Term[]> of the terms in the multiphrase.
	   * Do not modify the List or its contents.
	   */
	  public List<Term[]> getTermArrays() {
		  return Collections.unmodifiableList(termArrays);
	  }

	  /**
	   * Returns the relative positions of terms in this phrase.
	   */
	  public int[] getPositions() {
	    int[] result = new int[positions.size()];
	    for (int i = 0; i < positions.size(); i++)
	      result[i] = positions.elementAt(i).intValue();
	    return result;
	  }

	  // inherit javadoc
	  @SuppressWarnings("unchecked")
	@Override
	  public void extractTerms(@SuppressWarnings("rawtypes") Set terms) {
	    for (Iterator<Term[]> iter = termArrays.iterator(); iter.hasNext();) {
	      Term[] arr = iter.next();
	      for (int i=0; i<arr.length; i++) {
	        terms.add(arr[i]);
	      }
	    }
	  }
	  
}
