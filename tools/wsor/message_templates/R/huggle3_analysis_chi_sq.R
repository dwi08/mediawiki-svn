
# Ryan Faulkner, January 25th 2012
#
# Comparison of metrics for Huggle 3 using a chi-square goodness of fit test
#  


source('/home/rfaulk/WSOR/message_templates/R/R_helper_functions.R')

# Read aggregated results for the template

template_indices_control <- c(60,62,64,66,68,70,72,74,76)
template_indices_test <- c(61,63,65,67,69,71,73,75,77)

fname_first_part <- "/home/rfaulk/WSOR/message_templates/output/metrics_1018_1119_z"
fname_last_part <- "_editcounts.tsv"


# MAIN EXECUTION
# ==============

# BUILD THE DATA FRAMES

metrics_test <- build.data.frames(template_indices_test, fname_first_part, fname_last_part)
metrics_control <- build.data.frames(template_indices_control, fname_first_part, fname_last_part)


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




