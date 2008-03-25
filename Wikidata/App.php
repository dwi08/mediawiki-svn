<?php

# DO NOT EDIT THIS FILE DIRECTLY. INSTEAD, COPY RELEVANT
# CONFIGURATION VARIABLES TO LocalApp.php AND EDIT THEM
# THERE.

$wgDefaultGoPrefix='Expression:';
$wgHooks['BeforePageDisplay'][]='addWikidataHeader';
$wgHooks['GetEditLinkTrail'][]='addWikidataEditLinkTrail'; #TODO merge with modifyTabs
$wgHooks['GetHistoryLinkTrail'][]='addHistoryLinkTrail'; #TODO merge with modifyTabs
$wgHooks['SkinTemplateTabs'][]='modifyTabs';
$wgExtensionFunctions[]='initializeWikidata';

$wgCustomHandlerPath = array('*'=>"{$IP}/extensions/Wikidata/OmegaWiki/");
$wgDefaultClassMids = array(402295);

# Register the classes needed for the wikidata api with the autoloader.
$wgAutoloadClasses['ApiWikiData'] = "{$IP}/extensions/Wikidata/includes/api/ApiWikiData.php";
$wgAutoloadClasses['ApiWikiDataFormatBase'] = "{$IP}/extensions/Wikidata/includes/api/ApiWikiDataFormatBase.php";
$wgAutoloadClasses['ApiWikiDataFormatXml'] = "{$IP}/extensions/Wikidata/includes/api/ApiWikiDataFormatXml.php";

# Add the API module
$wgAPIModules['wikidata'] = 'ApiWikiData';


# The term dataset prefix identifies the Wikidata instance that will
# be used as a resource for obtaining language-independent strings
# in various places of the code. If the term db prefix is empty,
# these code segments will fall back to (usually English) strings.
# If you are setting up a new Wikidata instance, you may want to
# set this to ''.
$wdTermDBDataSet='uw';

# This is the dataset that should be shown to all users by default.
# It _must_ exist for the Wikidata application to be executed 
# successfully.
$wdDefaultViewDataSet='uw';

$wdShowCopyPanel=false;
$wdShowEditCopy=true;

$wdGroupDefaultView=array();
# Here you can set group defaults.

$wdGroupDefaultView['wikidata-omega']='uw';
#$wdGroupDefaultView['wikidata-umls']='umls';
#$wdGroupDefaultView['wikidata-sp']='sp';

# These are the user groups
$wgGroupPermissions['wikidata-omega']['editwikidata-uw']=true;
#$wgGroupPermissions['wikidata-test']['editwikidata-tt']=true;
$wgGroupPermissions['wikidata-copy']['wikidata-copy']=true;
$wgGroupPermissions['wikidata-omega']['wikidata-copy']=true;

# The permission needed to do ...
$wgCommunity_dc="uw";
$wgCommunityEditPermission="editwikidata-uw"; # only used for copy for now
global $wdTesting;
$wdTesting=false; #useful when testing, use as needed
$wdCopyAltDefinitions=false;
$wdCopyDryRunOnly=false;	# Copy.php:
				# If true: do everything needed to
				# make a copy, but do not actually
				# write to the database.


# The site prefix allows us to have multiple sets of customized
# messages (for different, typically site-specific UIs)
# in a single database.
if(!isset($wdSiteContext)) $wdSiteContext="uw";

$wgShowClassicPageTitles = false;
$wgDefinedMeaningPageTitlePrefix = "";
$wgExpressionPageTitlePrefix = "Multiple meanings";
require_once("$IP/extensions/Wikidata/OmegaWiki/GotoSourceTemplate.php");
			        			
$wgGotoSourceTemplates = array(5 => $swissProtGotoSourceTemplate);  

require_once("{$IP}/extensions/Wikidata/AddPrefs.php");
require_once("{$IP}/extensions/Wikidata/SpecialLanguages.php");
require_once("{$IP}/extensions/Wikidata/OmegaWiki/SpecialSuggest.php");
require_once("{$IP}/extensions/Wikidata/OmegaWiki/SpecialSelect.php");
require_once("{$IP}/extensions/Wikidata/OmegaWiki/SpecialDatasearch.php");
require_once("{$IP}/extensions/Wikidata/OmegaWiki/SpecialTransaction.php");
require_once("{$IP}/extensions/Wikidata/OmegaWiki/SpecialNeedsTranslation.php");
require_once("{$IP}/extensions/Wikidata/OmegaWiki/SpecialImportLangNames.php");
require_once("{$IP}/extensions/Wikidata/OmegaWiki/SpecialAddCollection.php");
require_once("{$IP}/extensions/Wikidata/OmegaWiki/SpecialConceptMapping.php");
require_once("{$IP}/extensions/Wikidata/OmegaWiki/SpecialCopy.php");
require_once("{$IP}/extensions/Wikidata/OmegaWiki/SpecialExportTSV.php");
require_once("{$IP}/extensions/Wikidata/OmegaWiki/SpecialImportTSV.php");
require_once("{$IP}/extensions/Wikidata/LocalApp.php");

function addWikidataHeader() {
	global $wgOut,$wgScriptPath;
	$dc=wdGetDataSetContext();
	$wgOut->addScript("<script type='text/javascript' src='{$wgScriptPath}/extensions/Wikidata/OmegaWiki/suggest.js'></script>");
	$wgOut->addLink(array('rel'=>'stylesheet','type'=>'text/css','media'=>'screen, projection','href'=>"{$wgScriptPath}/extensions/Wikidata/OmegaWiki/suggest.css"));
	$wgOut->addLink(array('rel'=>'stylesheet','type'=>'text/css','media'=>'screen, projection','href'=>"{$wgScriptPath}/extensions/Wikidata/OmegaWiki/tables.css"));                                                                                                                                                                    
	return true;
}

function wdIsWikidataNs() {
	global $wgTitle;
	$ns=Namespace::get($wgTitle->getNamespace());	
	return
	($ns->getHandlerClass()=='OmegaWiki' || $ns->getHandlerClass()=='DefinedMeaning' || $ns->getHandlerClass()=='ExpressionPage');

}

function addWikidataEditLinkTrail(&$trail) {
	if(wdIsWikidataNs()) {
		$dc=wdGetDatasetContext();
		$trail="&dataset=$dc";
	}
	return true;
}

function addHistoryLinkTrail(&$trail) {
	if(wdIsWikidataNs()) {
	    	$dc=wdGetDatasetContext();
	    	$trail="&dataset=$dc";
  	}
	return true;
}

/**
 * Purpose: Add custom tabs
 *
 * When editing in read-only data-set, if you have the copy permission, you can
 * make a copy into the designated community dataset and edit the data there.
 * This is accessible through an 'edit copy' tab which is added below.
 *
 * @param $skin Skin as passed by MW
 * @param $tabs as passed by MW
 */
function modifyTabs($skin, $content_actions) {
	global $wgUser, $wgTitle, $wdTesting, $wgCommunity_dc, $wdShowEditCopy;
	$dc=wdGetDataSetContext();
	$ns=Namespace::get($wgTitle->getNamespace());
	if($ns->getHandlerClass()=='DefinedMeaning') {
	
		# Hackishly determine which DMID we're on by looking at the page title component
		$tt=$wgTitle->getText();
		$rpos1=strrpos( $tt, '(');
		$rpos2=strrpos( $tt, ')');
		$dmid = ($rpos1 && $rpos2) ? substr($tt, $rpos1+1, $rpos2-$rpos1-1) : 0;
		if($dmid) {
			$copyTitle=SpecialPage::getTitleFor('Copy');
			#if(wdIsWikidataNs() && (!$wgUser->isAllowed('editwikidata-'.$dc) || $wdTesting)) {
			if(wdIsWikidataNs() && $dc!=$wgCommunity_dc && $wdShowEditCopy) {
				$content_actions['edit']=array(
				'class'=>false, 
				'text'=>'edit copy', 
				'href'=>$copyTitle->getLocalUrl("action=copy&dmid=$dmid&dc1=$dc&dc2=$wgCommunity_dc")
			);
			}
		 $content_actions['nstab-definedmeaning']=array(
				 'class'=>'selected',
				 'text'=>'defined meaning',
				 'href'=>$wgTitle->getLocalUrl("dataset=$dc"));

		}
	}
	return true;
}

function initializeWikidata() {
	global 
		$wgMessageCache, $wgExtensionPreferences, $wdSiteContext, $wgPropertyToColumnFilters;
		
	$dbr =& wfGetDB(DB_MASTER);
	$dbr->query("SET NAMES utf8");
	
	$datasets=wdGetDatasets();
	$datasetarray['']=wfMsgHtml('ow_none_selected');
	foreach($datasets as $datasetid=>$dataset) {
		$datasetarray[$datasetid]=$dataset->fetchName();
	}
	$wgExtensionPreferences[] = array(
		'name' => 'ow_uipref_datasets',
		'section' => 'ow_uiprefs',
		'type' => PREF_OPTIONS_T,
		'size' => 10,
		'options' => $datasetarray
	);
                            	
	global 
		$messageCacheOK;
		
	$messageCacheOK = true;
	
	global
		$wgRecordSetLanguage;
		
	$wgRecordSetLanguage = 0;
	
	return true;
}

