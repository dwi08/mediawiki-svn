
# Ryan Faulkner, January 25th 2012
#
# Comparison of metrics for Huggle 3 using a chi-square goodness of fit test
#  


source('/home/rfaulk/WSOR/message_templates/R/R_helper_functions.R')


# MAIN EXECUTION
# ==============

# Read aggregated results

metrics_test = read.table("/home/rfaulk/WSOR/message_templates/output/metrics_1018_1119_z70_editcounts.tsv", na.strings="\\N", sep="\t", comment.char="", quote="", header=T)
metrics_control = read.table("/home/rfaulk/WSOR/message_templates/output/metrics_1018_1119_z71_editcounts.tsv", na.strings="\\N", sep="\t", comment.char="", quote="", header=T)


# Compute the change in edits after the template
# ===============================================


test_samples <- c()
control_samples <- c()

for (i in 1:length(metrics_test$ns_0_revisions_before)) 
	if (metrics_test$ns_0_revisions_before[i] != 0)
		test_samples <- c(test_samples, 
		(metrics_test$ns_0_revisions_before[i] - metrics_test$ns_0_revisions_after[i]) / metrics_test$ns_0_revisions_before[i])

for (i in 1:length(metrics_control$ns_0_revisions_before)) 
	if (metrics_control$ns_0_revisions_before[i] != 0)
		control_samples <- c(control_samples, 
		(metrics_control$ns_0_revisions_before[i] - metrics_control$ns_0_revisions_after[i]) / metrics_control$ns_0_revisions_before[i])



# Construct a distribution (Normal) using parameters computed from metric count data 
# This will be used as the model distribution - do symetrically (ie. fot both ways)
# ====================================================================================


# Number of samples for each template

n_test <- length(test_samples)
n_control <- length(control_samples)


# Produce probabilities for normal to be fit
# build data frames

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
chisq_res_1 = chisq.test(counts_test$counts, p=probs_control$counts)
chisq_res_2 = chisq.test(counts_control$counts, p=probs_test$counts)




