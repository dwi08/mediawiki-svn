package org.wikimedia.lsearch.util;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.IOException;
import java.util.ArrayList;
import org.wikimedia.lsearch.util.MathFunc;


/**
 * This class is test MathFunc which is not used in the project
 * The test is visual i.e. putput not a success failure 
 * also neither MathFunc nor MathFuncTest are referenced in the project
 * 
 * @deprecated
 * 
 * if the class isn't deprecated then the following should be addressed  
 * configure test file location from command line 
 * convert to unit test
 * convert to output to log4j for non console execution

 * 
 */
public class MathFuncTest
{
	
	
	/**
	 * prints the entries of an integer and a double arrays side by side
	 * 
	 *   
	 * @param p the integers
	 * @param val the doubles
	 */
	public static void print(int[] p, double[] val){
		
		//for each integer in p
		for(int i=0;i<p.length-1;i++){
			
			//print the value of val[i]
			System.out.print(MathFunc.avg(val,p[i],p[i+1])+" -> ");

			//prints all a sequence of values from val
			for(int j=p[i];j<p[i+1];j++){
				System.out.print(val[j]+" ");
			}			
			System.out.println();
		}
		System.out.println();
	}
	
	/**
	 * 1. loads data from "./test-data/mathfunc.test"
	 * 2.
	 * 
	 * @param args
	 * @throws FileNotFoundException 
	 */
	public static void main(String[] args) throws FileNotFoundException {
		double[] val = {39.2,13.45,12.67,10.25,8.84,8.66,8.31,8.19,8.06,7.99,6.39,6.19,6,5.92,5.85};
		String testfile = "./test-data/mathfunc.test";
		int[] p = MathFunc.partitionList(val,3);
		print(p,val);		
		
		
		//load data from file
		System.out.println("From "+testfile);
		BufferedReader r = new BufferedReader(new FileReader(new File(testfile)));
		String line;
		ArrayList<Double> val2a = new ArrayList<Double>();
		try {
			while((line = r.readLine()) != null){
				val2a.add(new Double(line));
			}
		} catch (NumberFormatException e) {
			// TODO badly formated file
			e.printStackTrace();
		} catch (IOException e) {
			// TODO could not read file
			e.printStackTrace();
			
		}
		
		double[] val2 = new double[val2a.size()];
		for(int i=0;i<val2.length;i++)
			val2[i] = val2a.get(i);
		print(MathFunc.partitionList(val2,5),val2);
		
		double[] val3 = {1.5192982456140351, 1.222988282514404, 1.053690036900369, 1.053690036900369, 1.003690036900369, 0.5229882825144041};
		print(MathFunc.partitionList(val3,5),val3);
		
	}
}
