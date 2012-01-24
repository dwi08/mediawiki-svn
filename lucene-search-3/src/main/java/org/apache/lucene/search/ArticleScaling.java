package org.apache.lucene.search;

import java.io.Serializable;

/**
 * Scoring method to apply in ArticleQueryWrap using
 * ArticleInfo
 * 
 * @author rainman
 *
 */
abstract public class ArticleScaling implements Serializable {
	
	/**
	 * 
	 */
	private static final long serialVersionUID = 2170644350907751230L;

	abstract public float score(float score, float diff);
	public String explain(float score, float diff){
		return "score="+score+", diff="+diff;
	}
	
	/** add to score ~ log(1/diff) between [0,max] */
	public static class Bonus extends ArticleScaling {
		/**
		 * 
		 */
		private static final long serialVersionUID = 1100709052264451254L;
		float max;
		public Bonus(float max){
			this.max = max;
		}
		
		public float score(float score, float diff){
			return (float)(score + max * Math.log(1+(Math.E-1)/(1+diff))); 
		}
	}
	
	/** Scale score by ~ log(1/diff) between [min,max] */
	public static class Scaling extends ArticleScaling {
		/**
		 * 
		 */
		private static final long serialVersionUID = 5974506142878361951L;
		float max, min;
		public Scaling(float min, float max){
			this.max = max;
			this.min = min;
		}
		
		public float score(float score, float diff){
			double sc = Math.log(1+(Math.E-1)/(1+diff));
			return (float)(score * ((max-min) * sc + min) ); 
		}
	}
	/** Scale score by ~ sqrt(1/diff) between [min,max] */
	public static class SqrtScale extends ArticleScaling {
		/**
		 * 
		 */
		private static final long serialVersionUID = 2046408434470271501L;
		float max, min;
		public SqrtScale(float min, float max){
			this.max = max;
			this.min = min;
		}
		
		public float score(float score, float diff){
			double sc = Math.sqrt(1.0/(1+diff));
			return (float)(score * ((max-min) * sc + min) );
		}
		
		public String explain(float score, float diff){
			double sc = Math.sqrt(1.0/(1+diff));
			return score+" * "+((float)((max-min) * sc + min));
		}
		
	}
	
	/** Return scores based on a step function of time */
	public static class StepScale extends ArticleScaling {
		/**
		 * 
		 */
		private static final long serialVersionUID = 1291538363159806255L;
		float[] chunks = new float[5];
		public StepScale(float min, float max){
			float inc = (max-min)/chunks.length;
			for(int i=0;i<chunks.length;i++)
				chunks[i] = max-i*inc;
		}
		
		public float getChunk(float diff){		
			if(diff < 30)
				return chunks[0];
			else if(diff < 90)
				return chunks[1];
			else if(diff < 180)
				return chunks[2];
			else if(diff < 365)
				return chunks[3];
			
			return chunks[4];
		}
		
		public float score(float score, float diff){
			return score * getChunk(diff);
		}
		
		public String explain(float score, float diff){
			return score+" * "+getChunk(diff);
		}
	}
	
	/** No scaling */
	public static class None extends ArticleScaling {
		/**
		 * 
		 */
		private static final long serialVersionUID = -65887438293294573L;

		@Override
		public final float score(float score, float diff) {
			return score;
		}		
	}

}
