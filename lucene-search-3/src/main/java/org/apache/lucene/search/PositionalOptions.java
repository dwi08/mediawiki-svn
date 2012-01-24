package org.apache.lucene.search;

import java.io.Serializable;

import org.wikimedia.lsearch.search.AggregateInfoImpl;
import org.wikimedia.lsearch.search.AggregateInfoImpl.RankInfo;

public class PositionalOptions implements Serializable {
	/**
	 * 
	 */
	private static final long serialVersionUID = 8133507278162258948L;
	protected AggregateInfo aggregateMeta = null;
	protected RankInfo rankMeta = null;

	/** use additional boosts when phrase if at the beginning of document */
	protected boolean useBeginBoost = false;	
	/** boost when whole phrase matches */
	protected float wholeBoost = 1;
	/** boost when phrases match when stop words are excluded */
	protected float wholeNoStopWordsBoost = 1;
	/** boost if the phrase matches with slop 0 */
	protected float exactBoost = 8;
	/** take max score from multiple phrase in the document instead of summing them up */
	protected boolean takeMaxScore = false;
	/** wether to use the stop words proportion */
	protected boolean useNoStopWordLen = false;
	/** nonzero score only on whole match (either all words, or all words without stopwords */
	protected boolean onlyWholeMatch = false;
	/** table of boost values for first 25, 50, 200, 500 tokens */
	protected float beginTable[] = { 16, 4, 4, 2 };
	/** useful for redirects - use main rank as whole-match boost */
	protected boolean useRankForWholeMatch = false;
	/** for sloppy phrases if we find a sloppy phrase at the very beginning */
	protected float beginExactBoost = 8;
	/** namespace-specific boost values */
	protected NamespaceBoost nsWholeBoost = null;
	/** when all tokens *and* aliases match (happens only when title and query are identical - same accents, etc..) */ 
	protected float completeBoost = 1;
	/** use complete number of tokens (with completeBoost) only for scoring */
	protected boolean useCompleteOnly = false;
	/** act exactly as a phrase query without any positional or such optimizations */
	protected boolean phraseQueryFallback = false;
	/** if single words can be whole matches */
	protected boolean allowSingleWordWholeMatch = false;
	/** extra boost if whole alttitle (and not anchor) is matched */
	protected float alttileWholeExtraBoost = 1;

	
	/** Options specific for phrases in contents */
	public static class Sloppy extends PositionalOptions {
		/**
		 * 
		 */
		private static final long serialVersionUID = -6778997372025237713L;

		public Sloppy(){
			rankMeta = new RankInfo();
			useBeginBoost = true;
			exactBoost = 4;
			beginExactBoost = 8;
		}
	}
	
	/** Options specific for phrases that match exactly in contents */
	/* public static class Exact extends PositionalOptions {
		public Exact(){
			rankMeta = new RankInfo();
			useBeginBoost = true;
			exactBoost = 4;
			//beginTable = new float[] { 256, 64, 4, 2 }; 
		}
	} */
	
	/** Options for alttitle field */
	public static class Alttitle extends PositionalOptions {
		/**
		 * 
		 */
		private static final long serialVersionUID = -8151439171086838369L;

		public Alttitle(){
			aggregateMeta = new AggregateInfoImpl();
			takeMaxScore = true;
			//exactBoost = 2;
			//wholeBoost = 10;
		}
	}
	/** Match only whole entries on an aggregate field */
	public static class AlttitleWhole extends PositionalOptions {
		/**
		 * 
		 */
		private static final long serialVersionUID = -44219584478834032L;

		public AlttitleWhole(){
			aggregateMeta = new AggregateInfoImpl();
			takeMaxScore = true;
			wholeBoost = 1000;
			alttileWholeExtraBoost = 100;
			//wholeNoStopWordsBoost = 1000;
			//onlyWholeMatch = true;
		}
	}
	
	public static class AlttitleWholeSloppy extends PositionalOptions {
		/**
		 * 
		 */
		private static final long serialVersionUID = -543978831961407868L;

		public AlttitleWholeSloppy(){
			aggregateMeta = new AggregateInfoImpl();
			takeMaxScore = true;
			wholeBoost = 100;
			alttileWholeExtraBoost = 10;
			//exactBoost = 10;
			allowSingleWordWholeMatch = true;
			//wholeNoStopWordsBoost = 1000;
			//onlyWholeMatch = true;
		}
	}
	
	/** Options specific to related fields */
	public static class Related extends PositionalOptions {
		/**
		 * 
		 */
		private static final long serialVersionUID = -2602121388756335605L;

		public Related(){
			aggregateMeta = new AggregateInfoImpl();
			takeMaxScore = true;
			//exactBoost = 2;
		}
	}
	
	public static class RelatedWhole extends PositionalOptions {
		/**
		 * 
		 */
		private static final long serialVersionUID = 6098185842620225695L;

		public RelatedWhole(){
			aggregateMeta = new AggregateInfoImpl();
			takeMaxScore = true;
			// exactBoost = 4;
			allowSingleWordWholeMatch = true;
		}
	}
	
	/** Options for additional alttitle query */
	public static class AlttitleSloppy extends PositionalOptions {
		/**
		 * 
		 */
		private static final long serialVersionUID = -1855749973847919484L;

		public AlttitleSloppy(){
			aggregateMeta = new AggregateInfoImpl();
			takeMaxScore = true;
			wholeNoStopWordsBoost = 300;
			useNoStopWordLen = true;
		}
	}
	
	/** Options for additional alttitle query */
	public static class AlttitleExact extends PositionalOptions {
		/**
		 * 
		 */
		private static final long serialVersionUID = -1774598698488092771L;

		public AlttitleExact(){
			aggregateMeta = new AggregateInfoImpl();
			takeMaxScore = true;
			wholeBoost = 10000;
		}
	}
	
	/** alttitle query to match redirects */
	public static class RedirectMatch extends PositionalOptions {
		/**
		 * 
		 */
		private static final long serialVersionUID = 1371571685071626823L;

		public RedirectMatch(){
			aggregateMeta = new AggregateInfoImpl();
			takeMaxScore = true;
			wholeNoStopWordsBoost = 10000;
			wholeBoost = 50000;
			useRankForWholeMatch = true;
			nsWholeBoost = new NamespaceBoost.DefaultBoost();
			allowSingleWordWholeMatch = true;
			exactBoost = 100;
			alttileWholeExtraBoost = 100;
		}
	}
	
	/** alttitle query to match complete titles */
	public static class RedirectComplete extends PositionalOptions {
		/**
		 * 
		 */
		private static final long serialVersionUID = -8968024335275647874L;

		public RedirectComplete(){
			aggregateMeta = new AggregateInfoImpl();
			takeMaxScore = true;
			completeBoost = 500000000;
			useCompleteOnly = true;
			useRankForWholeMatch = true;
			nsWholeBoost = new NamespaceBoost.DefaultBoost();
		}
	}
	
	/** Options for sections field */
	public static class Sections extends PositionalOptions {
		/**
		 * 
		 */
		private static final long serialVersionUID = -8692410614019818270L;

		public Sections(){
			aggregateMeta = new AggregateInfoImpl();
			takeMaxScore = true;
			//wholeBoost = 8;
		}
	}
	
	/** Options for sections field */
	public static class SectionsWhole extends PositionalOptions {
		/**
		 * 
		 */
		private static final long serialVersionUID = 1350993858005999931L;

		public SectionsWhole(){
			aggregateMeta = new AggregateInfoImpl();
			takeMaxScore = true;
			// wholeBoost = 8;
			allowSingleWordWholeMatch = true;
		}
	}
	/** Fallback to phasequery-type behaviour, no positional info */
	public static class PhraseQueryFallback extends PositionalOptions {
		/**
		 * 
		 */
		private static final long serialVersionUID = 6589847326296430092L;

		public PhraseQueryFallback(){
			phraseQueryFallback = true;
		}
	}
	
	/** Near match phrases, when more than 50% of nonstopwords are matched */
	public static class AlttitleNearMatch extends PositionalOptions {
		/**
		 * 
		 */
		private static final long serialVersionUID = 4735381044510834341L;

		public AlttitleNearMatch(){
			aggregateMeta = new AggregateInfoImpl();
			takeMaxScore = true;
		}
	}
	
	public abstract static class NamespaceBoost implements Serializable {
		/**
		 * 
		 */
		private static final long serialVersionUID = 1355534237962528582L;

		public abstract float getBoost(int namespace);
		
		public static class DefaultBoost extends NamespaceBoost {
			/**
			 * 
			 */
			private static final long serialVersionUID = -3306348859577417462L;

			public float getBoost(int namespace){
				if(namespace % 2 == 1) // talk pages
					return 0.75f;  
				if(namespace == 10) // templates
					return 0.5f;
				return 1f;
			}
		}
	}

	

	@Override
	public boolean equals(Object obj) {
		if (this == obj)
			return true;
		if (obj == null)
			return false;
		if (getClass() != obj.getClass())
			return false;
		return true;
	}
	
	
	
}
