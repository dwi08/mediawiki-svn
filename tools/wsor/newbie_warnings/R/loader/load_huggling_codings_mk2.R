source("util/env.R")

load_huggling_codings_mk2 = function(verbose=T, reload=F){
	filename = paste(DATA_DIR, "mk2/huggling_codings.tsv", sep="/")
	if(!exists("HUGGLING_CODINGS_MK2")){
		HUGGLING_CODINGS_MK2 <<- NULL
	}
	if(is.null(HUGGLING_CODINGS_MK2) | reload){
		HUGGLING_CODINGS_MK2 <<- NULL
	}
	if(is.null(HUGGLING_CODINGS_MK2)){
		if(verbose){cat("Loading ", filename, "...")} 
		HUGGLING_CODINGS_MK2 <<- read.table(
			filename, 
			header=T, sep="\t", 
			quote="", comment.char="", 
			na.strings="NULL"
		)
		HUGGLING_CODINGS_MK2$def          = HUGGLING_CODINGS_MK2$def == 1
		HUGGLING_CODINGS_MK2$personal     = HUGGLING_CODINGS_MK2$personal == 1
		HUGGLING_CODINGS_MK2$nodirectives = HUGGLING_CODINGS_MK2$nodirectives == 1
		HUGGLING_CODINGS_MK2$experimental = HUGGLING_CODINGS_MK2$experimental == 1
		
		
		HUGGLING_CODINGS_MK2$edits_own_talkpage      = HUGGLING_CODINGS_MK2$edits_own_talkpage > 0
		HUGGLING_CODINGS_MK2$edits_hugglers_talkpage = HUGGLING_CODINGS_MK2$edits_hugglers_talkpage > 0
		HUGGLING_CODINGS_MK2$responds_own_talk       = HUGGLING_CODINGS_MK2$responds_own_talk > 0
		HUGGLING_CODINGS_MK2$responds_elsewhere      = HUGGLING_CODINGS_MK2$responds_elsewhere > 0
		HUGGLING_CODINGS_MK2$is_anon                 = HUGGLING_CODINGS_MK2$is_anon  > 0
		HUGGLING_CODINGS_MK2$retaliates              = HUGGLING_CODINGS_MK2$retaliates  > 0
		HUGGLING_CODINGS_MK2$good_outcome            = HUGGLING_CODINGS_MK2$good_outcome  > 0
		HUGGLING_CODINGS_MK2$warning_first_msg       = HUGGLING_CODINGS_MK2$warning_first_msg > 0
		HUGGLING_CODINGS_MK2$is_shared_ip            = HUGGLING_CODINGS_MK2$is_shared_ip > 0
		
		if(verbose){cat("DONE!\n")}
	}
	HUGGLING_CODINGS_MK2
}
################################################################################
# rev_id - Revision identifier of the message posting
# def - Boolean.  True if the message posted was the default message.
# personal - Boolean.  True if the message posted was a personal message.
# nodirectives - Boolean.  True if the message posted was nice.
# experimental - Boolean.  True if either personal or no directives.
# reverted_id - Revision id that was reverted. 
# rev_page - User talk page ID of reverted editor
# rev_user - Reverting user identifier
# rev_user_text - Reverting user name
# rev_timestamp - Timestamp at which the warning was posted.
# page_id - User talk page ID of reverted editor
# warned_user - Reverted editor's user_text
# message_consumed - Timestamp at which the message was consumed. 
# deleted_revs - ???
# revs_after - The number of revisions performed by the editor after consuming the message
# user_talk_edits_before_msg - Edits to namespace 3 before consuming the message
# user_talk_edits_after_msg - Edits to namespace 3 after consuming the message
# article_talk_edits_before_msg - Edits to namespace 1 before consuming the message
# article_talk_edits_after_msg - Edits to namespace 1 after consuming the message
# ns0_edits_before_msg - Edits to articles before consuming the message
# ns0_edits_after_msg - Edits to articles after consuming the message
# wp_edits_before_msg - Edits to project namespace before consuming the message
# wp_edits_after_msg - Edits to project namespace after consuming the message
# exp_case - "def", "personal" or "nodirectives"
# others_edit_talkpage_after - Boolean.  True if other editors edit the warned user's talk page. 
# others_edit_talkpage_after_tools - Boolean.  True if other editors edit the warned user's talk page with tools.
# warnings_after_72hrs - Additional warnings posted 72 hours after warned user consumed the message
# warnings_after_24hrs - Additional warnings posted 24 hours after warned user consumed the message
# edits_hugglers_talkpage - Boolean.  True if warned user edits huggling editor's talk page after consuming message
# edits_own_talkpage - Boolean.  True if warned user edits own talk page after consuming message
# edits_after_msg_3days - Number of edits performed by the warned user within 3 days after consuming the message.
# edits_before_msg_3_30_days - Number of edits performed by the warned user 3-30 days before consuming the message.
# edits_after_msg_3_30_days - Number of edits performed by the warned user 3-30 days after consuming the message
# edits_before_msg_30_60_days - Number of edits performed by the warned user 30-60 days before consuming the message
# edits_after_msg_30_60_days - Number of edits performed by the warned user 30-60 days after consuming the message
# before_rating - Aggregate rating of activity before the warned user consumed the message
# after_rating - Aggregate rating of activity after the warned user consumed the message
# responds_hugglers_talk - Boolean.  True if the warned user responds on huggling editor's talk page
# responds_own_talk - Boolean.  True if the warned user responds to huggler on own talk page.
# responds_elsewhere - Boolean.  True if the warned user responds somewhere that isn't their talk page or the huggling editor's talk page.
# edits_talk_noresponse - Boolean.  True if the warned user edits their own talk page but does not response to huggling editor
# retaliates - Boolean.  True if the warned user retaliates against the huggling editor.
# before_after - -1 if rating decayed, 0 if consistent and 1 if rating improved.
# good_outcome - ???
# is_anon - Boolean.  True if warned user is anonymous
# warning_first_msg - Boolean.  True if the warning in question was the first message on a user's talk page.
# is_shared_ip - Boolean.  True if the warned user is connecting from a shared IP.
# blocked_after_msg_seconds - The number of seconds between consuming the message and being blocked from editing.
################################################################################

