grammar wikiTable;

@header {
package p;

}

@header {
package org.wikimedia.antlrSpec;
import org.antlr.test;} // not auto-copied to lexer
@lexer::header{
package org.wikimedia.antlrSpec;

//
}

@lexer::members {
//state check are deeply nested in a table are we?
int inTable=0;
List tokens = new ArrayList();
public void emit(Token token) {
        state.token = token;
    	tokens.add(token);
}
public Token nextToken() {
    	super.nextToken();
        if ( tokens.size()==0 ) {
            return Token.EOF_TOKEN;
        }
        return (Token)tokens.remove(0);
}
}

@members{
//int inTable=0;
//public void foo(){};
//int rows=0;
}

//Parser Rules

wikiTable 
scope{boolean triedHeader;}
@init{$wikiTable::triedHeader=false;}
	: TBL_START  xml_attributes?  caption? head?  rows TBL_END
	;
caption		
	: CAPTION_START HS xml_attributes? captionText=TEXT+
	;
fragment
head		
	: {!$wikiTable::triedHeader}?=>(hCell hCellInLine*)+{$wikiTable::triedHeader=true;}
	;	
rows 	
	: (firstRow|row) row*
	;
	
firstRow 	: cells ;
row		: ROW_START xml_attributes? cells;
cells		:((cell|hCell) (cellInline|hCellInLine)*)+;
cell		: CELL_START xml_attributes? text=TEXT*;
cellInline	: CELL_INLINE_STRT xml_attributes? text=TEXT*;
hCell		: HEAD_START xml_attributes? text=TEXT*;
hCellInLine	: HEAD_INLINE_STRT xml_attributes? text=TEXT*;


//this is the recursive definition allowing table nesting
//cells		:( {input.LT(0)==CELL_START||input.LT(0)==HEAD_START}?=>(HEAD_START | CELL_START) XHTML_ATTRIBUTES? (TEXT|wikiTable)+ (CELL_INLINE_STRT XHTML_ATTRIBUTES? (TEXT|wikiTable)+)* )+  ;

//this needs to be in the parser for LT(2) to mean the second parser token
xml_attributes: {input.LT(2).getText().equals("=")}? xml_attribute+ PIPE? ;
xml_attribute: name=TEXT EQ DQUOTE value=TEXT* DQUOTE ;
//Lexer Rules
TBL_START	: {getCharPositionInLine()==0}?=> '{|'{inTable++; }	;
TBL_END		: {getCharPositionInLine()==0&&inTable>0}?=> '|}'{inTable--;}	;
HEAD_START      : {getCharPositionInLine()==0&&inTable>0}?=> '!';
HEAD_INLINE_STRT: {inTable>0}?=> '!!';

CELL_START  	: {getCharPositionInLine()==0&&inTable>0}?=> '|';	//this should only be recognized within a table
PIPE		: {getCharPositionInLine()>0||inTable==0}?=> '|';	//outside table or not at tart of line

CELL_INLINE_STRT: {inTable>0}?=> '||'; 					//this should only be recognized within a table
ROW_START 	: {getCharPositionInLine()==0&&inTable>0}?=> '|-' ;
CAPTION_START	: {getCharPositionInLine()==0&&inTable>0}?=> '|+' 	;


TEXT		: ('a'..'z'|'A'..'Z'|'0'..'9'|'.'|'-'|';'|':'|',')+;					//simplified

DQUOTE		: '"';
//WS 		:  (HS | VS)  ; //{ $channel = HIDDEN; } ;
HS		: ( ' ' | '\t'  )+ { $channel = HIDDEN; } ;
VS		: ( '\r' | '\n' )+ { $channel = HIDDEN; } ;
EQ		: '=';
