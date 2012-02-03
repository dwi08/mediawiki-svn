
# Ryan Faulkner, January 23rd 2012
#
# Comparison of edit counts for Huggle 3 test among templates z64 (http://en.wikipedia.org/wiki/Template:Uw-error1-default) / z65 (http://en.wikipedia.org/wiki/Template:Uw-error1-short)
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


# Compute the change in edits after the template -- default to namespace 0
# User Talk namespace does not necessarily have edits before - in this case omit the result (it could be the case that templates stimulate user talk edits but that should be tested separately)
# Only append non-zero results - do this for just namespace 3 since it has zero entries for 'ns_3_revisions_before' 

z_test <- c()
z_control <- c()

for (i in 1:length(metrics_test$ns_0_revisions_before)) 
	if (metrics_test$ns_0_revisions_before[i] != 0)
		z_test <- c(z_test, 
		(metrics_test$ns_0_revisions_before[i] - metrics_test$ns_0_revisions_after[i]) / metrics_test$ns_0_revisions_before[i])

for (i in 1:length(metrics_control['ns_0_revisions_before'][[1]])) 
	if (metrics_control$ns_0_revisions_before[i] != 0)
		z_control <- c(z_control, 
		(metrics_control$ns_0_revisions_before[i] - metrics_control$ns_0_revisions_after[i]) / metrics_control$ns_0_revisions_before[i])


# Generate results:

t_result = t.test(x=z_test, y=z_control, alternative = "two.sided", paired = FALSE, var.equal = FALSE, conf.level = 0.95)
# t_result_ns3 = t.test(x=z64_ns3, y=z65_ns3, alternative = "two.sided", paired = FALSE, var.equal = FALSE, conf.level = 0.95)


