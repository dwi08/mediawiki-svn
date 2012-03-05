# source('/home/rfaulkner/trunk/projects/WSOR/message_templates/R/template_analysis.R')
#
# Ryan Faulkner, January 23rd 2012
#
# Process template posting aggregate data for analysis
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

process.data.frames <- function(min_edits_before=0, min_deleted_edits_before=0, max_edits_before=Inf, max_deleted_edits_before=Inf, min_revisions_after = 0, registered=TRUE) {
	
	# MERGE THE METRICS AND ADD TEMPLATE COLS

	# print("Merge Data..")
	
	merged_test <<- merge(edits_test, blocks_test, by=intersect(names(edits_test),names(blocks_test)), all=TRUE)
	merged_control <<- merge(edits_control, blocks_control, by=intersect(names(edits_control),names(blocks_control)), all=TRUE)
	
	merged_test <<- merge(merged_test, warn_test, by=intersect(names(merged_test),names(warn_test)), all=TRUE)
	merged_control <<- merge(merged_control, warn_control, by=intersect(names(merged_control),names(warn_control)), all=TRUE)
	
	merged_test$template <<- 1
	merged_control$template <<- 0
	
	
	# FILTER DATA

	# print("Filter Data..")
	
	maximum_warns_before <- 0
	
	if (!registered)
		IP_regex <- "^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$"
	else
		IP_regex <- '.*[a-zA-z].*'
	
	condition_1 <- TRUE # merged_test$blocks_before > 0
	condition_2 <- merged_test$blocks_after == 0
	condition_3 <- merged_test$ns_0_revisions_before >= min_edits_before & merged_test$ns_0_revisions_before <= max_edits_before 
	condition_4 <- merged_test$ns_0_revisions_deleted_before >= min_deleted_edits_before & merged_test$ns_0_revisions_deleted_before <= max_deleted_edits_before
	condition_5 <- merged_test$warns_before <= maximum_warns_before
	condition_6 <- filter.list.by.regex(IP_regex, merged_test$recipient_name)
	condition_7 <- merged_test$ns_0_revisions_after_0_3 >= min_revisions_after
	
	indices <- condition_1 & condition_2 & condition_3 & condition_4 & condition_5 & condition_6 & condition_7
	merged_test <<- merged_test[indices,]
	
	condition_1 <- TRUE # merged_control$blocks_before > 0
	condition_2 <- merged_control$blocks_after == 0
	condition_3 <- merged_control$ns_0_revisions_before >= min_edits_before & merged_control$ns_0_revisions_before <= max_edits_before
	condition_4 <- merged_control$ns_0_revisions_deleted_before >= min_deleted_edits_before & merged_control$ns_0_revisions_deleted_before <= max_deleted_edits_before
	condition_5 <- merged_control$warns_before <= maximum_warns_before
	condition_6 <- filter.list.by.regex(IP_regex, merged_control$recipient_name)
	condition_7 <- merged_control$ns_0_revisions_after_0_3 >= min_revisions_after
	
	indices <- condition_1 & condition_2 & condition_3 & condition_4 & condition_5 & condition_6 & condition_7 
	merged_control <<- merged_control[indices,]
		
	
	# ADD DERIVED COLS 
	
	# print("Add derived columns..")
	
	merged_test$edits_decrease <<- (merged_test$ns_0_revisions_before - merged_test$ns_0_revisions_after_0_3) / (merged_test$ns_0_revisions_before)
	merged_control$edits_decrease <<- (merged_control$ns_0_revisions_before - merged_control$ns_0_revisions_after_0_3) / (merged_control$ns_0_revisions_before)
	
	# merged_test$edits_del_decrease <<- (merged_test$ns_0_revisions_deleted_before - (merged_test$ns_0_revisions_deleted_after_0_3)) / (merged_test$ns_0_revisions_deleted_before)
	# merged_control$edits_del_decrease <<- (merged_control$ns_0_revisions_deleted_before - (merged_control$ns_0_revisions_deleted_after_0_3)) / (merged_control$ns_0_revisions_deleted_before)
	
}

# FUNCTION :: execute.chi.square.test
#
# Construct a distribution (Normal) using parameters computed from metric count data 
# This will be used as the model distribution - do symetrically (ie. fot both ways)	
#

execute.chi.square.test <- function(test_samples, control_samples) {
	
	# Number of samples for each template	
	n_test <- length(test_samples)
	n_control <- length(control_samples)
	
	
	# Produce probabilities for normal to be fit
	
	lower_bound_range <- trunc(min(min(c(control_samples, test_samples))) - 1)
	upper_bound_range <- trunc(max(max(c(control_samples, test_samples))) + 1)
	bins <- sort(lower_bound_range : upper_bound_range)
	
	probs_control <- get_normal_bins(bins, control_samples)
	probs_test <- get_normal_bins(bins, test_samples)
	
	probs_control <- data.frame(values=bins, counts=probs_control)
	probs_test <- data.frame(values=bins, counts=probs_test)
	
	counts_test <- get_bin_counts(bins, test_samples)
	counts_control <- get_bin_counts(bins, control_samples)
	
	counts_test <- pad_counts(bins, counts_test)
	counts_control <- pad_counts(bins, counts_control)
		
	# Get chi-squared test results
	chisq_res_test <<- chisq.test(counts_test$counts, p=probs_control$counts)
	chisq_res_control <<- chisq.test(counts_control$counts, p=probs_test$counts)
}


# FUNCTION :: execute.main
#
# A pseudo main method to allow the script to be executed as a batch 
#

execute.main <- function() {
	
	# IMPORT DATA
	
	template_indices_control <- c(60,62,66,76) # c(107,109,111,113,115) # c(78,81) # c(84, 0) #  c(1,4) # c(84,99,101,103,105) # c(60,62,64,66,68,70,72,74,76) 
	template_indices_test <- c(61,63,67,77) # c(108,110,114,116) # c(79,82) # c(86, 0) # c(2,3) # c(85,86,100,102,104,106) # c(61,63,65,67,69,71,73,75,77) 
	fname_first_part <- paste(home_dir,"output/metrics_1018_1119_z",sep="") # paste(home_dir,"output/metrics_1122_1222_z",sep="") # paste(home_dir,"output/metrics_1109_1209_z",sep="") # paste(home_dir,"output/metrics_1108_1202_z",sep="") # paste(home_dir,"output/metrics_pt_z",sep="") #  paste(home_dir,"output/metrics_1018_1119_z",sep="") 
	
	# import.experimental.metrics.data(template_indices_test, template_indices_control, fname_first_part)
	
	
	
	# PROCESS DATA
	
	# print("")
	# print("Processing data frames.")
	registered = TRUE
	process.data.frames(3,0,Inf,Inf,registered)
	
	
	
	# HYPOTHESIS TESTING
	
	# t_result <- t.test(x=merged_test$edits_decrease, y=merged_control$edits_decrease, alternative = "two.sided", paired = FALSE, var.equal = FALSE, conf.level = 0.95)
	# chi_result <- execute.chi.square.test(merged_test$edits_decrease, merged_control$edits_decrease)
	
	
	# LOGISTIC REGRESSION MODELLING:
	
	all_data <<- append.data.frames(merged_test, merged_control)
	# summary(glm(template ~ edits_decrease, data=all_data, family=binomial(link="logit")))
	# summary(glm(template ~ edits_del_decrease, data=all_data, family=binomial(link="logit")))

}
