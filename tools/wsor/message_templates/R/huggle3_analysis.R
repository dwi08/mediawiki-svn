# source('/home/rfaulkner/trunk/projects/WSOR/message_templates/R/huggle3_analysis.R')
#
# Ryan Faulkner, January 23rd 2012
#
# Comparison of edit counts for Huggle 3 test among templates z64 (http://en.wikipedia.org/wiki/Template:Uw-error1-default) / z65 (http://en.wikipedia.org/wiki/Template:Uw-error1-short)
#  

# Import helper methods - GLOBAL

home_dir <- "/home/rfaulkner/trunk/projects/WSOR/message_templates/"
# home_dir <- "/home/rfaulk/trunk/projects/WSOR/message_templates/"

helper_import <- paste(home_dir,"R/R_helper_functions.R",sep="")
source(helper_import)


# FUNCTION :: import.experimental.metrics.data
#
# Import the template data and build data frames from it
#

import.experimental.metrics.data <- function(template_indices_test, template_indices_control, fname_first_part) {
		
	# Read aggregated results for the template
		
	fname_last_part_edits <- "_editcounts.tsv"
	fname_last_part_blocks <- "_blocks.tsv"
	fname_last_part_warn <- "_warnings.tsv"
		
	warn_test <<- build.data.frames(template_indices_test, fname_first_part, fname_last_part_warn, string_frames=c(1))
	warn_control <<- build.data.frames(template_indices_control, fname_first_part, fname_last_part_warn, string_frames=c(1))
	
	blocks_test <<- build.data.frames(template_indices_test, fname_first_part, fname_last_part_blocks, string_frames=c(1))
	blocks_control <<- build.data.frames(template_indices_control, fname_first_part, fname_last_part_blocks, string_frames=c(1))
	
	edits_test <<- build.data.frames(template_indices_test, fname_first_part, fname_last_part_edits, string_frames=c(1))
	edits_control <<- build.data.frames(template_indices_control, fname_first_part, fname_last_part_edits, string_frames=c(1))
	
}



# FUNCTION :: process.data.frames
#
# Given a set of data frames containing template test metrics per user posting combine and generate summary metric frames
#
# GLOBALS assumed to exist:  warn_test, warn_control, blocks_test, blocks_control, edits_test, edits_control
#

process.data.frames <- function() {
	
	# MERGE THE METRICS AND ADD TEMPLATE COLS

	print("Merge Data..")
	
	merged_test <<- merge(edits_test, blocks_test, by=intersect(names(edits_test),names(blocks_test)), all=TRUE)
	merged_control <<- merge(edits_control, blocks_control, by=intersect(names(edits_control),names(blocks_control)), all=TRUE)
	
	merged_test <<- merge(merged_test, warn_test, by=intersect(names(merged_test),names(warn_test)), all=TRUE)
	merged_control <<- merge(merged_control, warn_control, by=intersect(names(merged_control),names(warn_control)), all=TRUE)
	
	merged_test$template <<- 1
	merged_control$template <<- 0
	
	
	# FILTER DATA

	print("Filter Data..")
	min_edits_before <- 5
	min_deleted_edits_before <- 0
	
	max_edits_before <- Inf
	max_deleted_edits_before <- Inf
	
	maximum_warns_before <- 0
	
	IP_regex <- "^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$"
	IP_regex_not <- '.*[a-zA-z].*'
	
	condition_1 <- TRUE # merged_test$blocks_before > 0
	condition_2 <- merged_test$blocks_after == 0
	condition_3 <- merged_test$ns_0_revisions_before >= min_edits_before & merged_test$ns_0_revisions_before <= max_edits_before 
	condition_4 <- merged_test$ns_0_revisions_deleted_before >= min_deleted_edits_before & merged_test$ns_0_revisions_deleted_before <= max_deleted_edits_before
	condition_5 <- merged_test$warns_before <= maximum_warns_before
	condition_6 <- filter.list.by.regex(IP_regex_not, merged_test$recipient_name)
	condition_7 <- merged_test$ns_0_revisions_after_0_3 > 0
	
	indices <- condition_1 & condition_2 & condition_3 & condition_4 & condition_5 & condition_6 & condition_7
	merged_test <<- merged_test[indices,]
	
	condition_1 <- TRUE # merged_control$blocks_before > 0
	condition_2 <- merged_control$blocks_after == 0
	condition_3 <- merged_control$ns_0_revisions_before >= min_edits_before & merged_control$ns_0_revisions_before <= max_edits_before
	condition_4 <- merged_control$ns_0_revisions_deleted_before >= min_deleted_edits_before & merged_control$ns_0_revisions_deleted_before <= max_deleted_edits_before
	condition_5 <- merged_control$warns_before <= maximum_warns_before
	condition_6 <- filter.list.by.regex(IP_regex_not, merged_control$recipient_name)
	condition_7 <- merged_control$ns_0_revisions_after_0_3 > 0
	
	indices <- condition_1 & condition_2 & condition_3 & condition_4 & condition_5 & condition_6 & condition_7 
	merged_control <<- merged_control[indices,]
		
	
	# ADD DERIVED COLS 
	
	print("Add derived columns..")
	
	merged_test$edits_decrease <<- (merged_test$ns_0_revisions_before - merged_test$ns_0_revisions_after_0_3) / (merged_test$ns_0_revisions_before)
	merged_control$edits_decrease <<- (merged_control$ns_0_revisions_before - merged_control$ns_0_revisions_after_0_3) / (merged_control$ns_0_revisions_before)
	
	merged_test$edits_del_decrease <<- (merged_test$ns_0_revisions_deleted_before - (merged_test$ns_0_revisions_deleted_after_0_3)) / (merged_test$ns_0_revisions_deleted_before + 1)
	merged_control$edits_del_decrease <<- (merged_control$ns_0_revisions_deleted_before - (merged_control$ns_0_revisions_deleted_after_0_3)) / (merged_control$ns_0_revisions_deleted_before + 1)
	
}



# IMPORT DATA

template_indices_control <- c(84, 0) # c(107,109,111,113,115) # c(1,4) # c(84,99,101,103,105) # c(60,62,64,66,68,70,72,74,76) 
template_indices_test <- c(86, 0) # c(108,110,114,116) # c(2,3) # c(85,86,100,102,104,106) # c(61,63,65,67,69,71,73,75,77) 
fname_first_part <- paste(home_dir,"output/metrics_1108_1202_z",sep="") # paste(home_dir,"output/metrics_1122_1222_z",sep="") # paste(home_dir,"output/metrics_pt_z",sep="") #  paste(home_dir,"output/metrics_1018_1119_z",sep="") # "/home/rfaulk/WSOR/message_templates/output/metrics_pt_z"

# import.experimental.metrics.data(template_indices_test, template_indices_control, fname_first_part)



# PROCESS DATA

# print("")
# print("Processing data frames.")
process.data.frames()



# HYPOTHESIS TESTING

#test_edits <- get.decrease.in.edits.after.template(edits_test$ns_0_revisions_before, edits_test$ns_0_revisions_after_3_30,lower_bound_rev_before=200,lower_bound_rev_after=0)
#control_edits <- get.decrease.in.edits.after.template(edits_control$ns_0_revisions_before, edits_control$ns_0_revisions_after_3_30,lower_bound_rev_before=200, lower_bound_rev_after=0)

#test_blocks <- get.change.in.blocks(blocks_test$blocks_before, blocks_test$blocks_after)
#control_blocks <- get.change.in.blocks(blocks_control$blocks_before, blocks_control$blocks_after)

#t_result_edits = t.test(x=test_edits, y=control_edits, alternative = "two.sided", paired = FALSE, var.equal = FALSE, conf.level = 0.95)
#t_result_blocks = t.test(x=test_blocks, y=control_blocks, alternative = "two.sided", paired = FALSE, var.equal = FALSE, conf.level = 0.95)



# LOGISTIC REGRESSION MODELLING:

all_data <- append.data.frames(merged_test, merged_control)
summary(glm(template ~ edits_decrease, data=all_data, family=binomial(link="logit")))
# summary(glm(template ~ edits_del_decrease, data=all_data, family=binomial(link="logit")))


