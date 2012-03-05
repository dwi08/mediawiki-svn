
# source('/home/rfaulkner/trunk/projects/WSOR/message_templates/R/visualize_edits_decrease.R')
#
# Ryan Faulkner, February 28th 2012
#
# Prepare data and visualize
#  

source('/home/rfaulkner/trunk/projects/WSOR/message_templates/R/template_analysis.R')
# library(ggplot2)

# FUNCTION :: plot.control.vs.test
#
# Basic plotting for te	st vs. control
#
# e.g. call :: plot.control.vs.test("Huggle Short 2 Experiment (reduced) - Decrease in Editor Activity", "Minimum Edits before Template Posting", "Mean % Decrease in Edit Activity", edit_decrease_means_test, edit_decrease_means_control)	
#

plot.control.vs.test <- function(title, x_label, y_label, test_samples, control_samples) {

	# Define axis range
	
	scaling_factor = 10
	shift_factor = 0.1	
	y_axis_range = ((scaling_factor + shift_factor) * min(c(test_samples, control_samples, 0))) : ((scaling_factor + shift_factor) * max(c(test_samples, control_samples)))
	y_axis_range = round(y_axis_range / scaling_factor, digits=2)
	g_range <- range(y_axis_range)
	
	# Graph cars using a y axis that ranges from 0 to 12
	plot(edit_decrease_means_test, type="o", col="blue", ylim=c(g_range[1],g_range[2]), axes=FALSE, ann=FALSE)
	
	# Make x axis using Mon-Fri labels
		
	axis(1, at=1:length(test_samples))
	axis(2, las=1, at=y_axis_range)
	box()

	# Create a title with a red, bold/italic font
	title(main=title, col.main="red", font.main=4)

	# Make x axis using Mon-Fri labels
	# Label the x and y axes with dark green text
	title(xlab=x_label, col.lab=rgb(0,0.5,0))
	title(ylab=y_label, col.lab=rgb(0,0.5,0))
	
	# Graph trucks with red dashed line and square points
	lines(edit_decrease_means_control, type="o", pch=22, lty=2, col="red")
	
	# Create a legend at (1, g_range[2]) that is slightly smaller 
	# (cex) and uses the same line colors and points used by 
	# the actual plots 
	legend(1, 1, c("test","control"), cex=0.8, 
	   col=c("blue","red"), pch=21:22, lty=1:2);	
}


# FUNCTION :: line.plot.results
#
# Generates metrics for the test and control template sets and visualizes the 
#
# edit_count_min_lower - lower bound of plotting range for minumum number of editors
# edit_count_min_upper - upper bound of plotting range for minumum number of editors
# import_metrics - imports metrics files if TRUE
# save_plot - saves plot if TRUE
# registered - look at registered editors if TRUE (non-registered otherwise)
# error_bars - display error bars if TRUE
#

line.plot.results <- function(edit_count_min_lower = 1, edit_count_min_upper = 10, import_metrics = FALSE, save_plot = TRUE, filename = 'ggplot_out_', registered = TRUE, error_bars = FALSE)
{
	# IMPORT DATA 
	
	#  c(78,81) c(1,4)  c(60,62,64,66,68,70,72,74,76) c(60,62,66,76) c(107,109,111,113,115) c(84,99,101,103,105)
	#  c(79,82) c(2,3)  c(61,63,65,67,69,71,73,75,77) c(61,63,67,77)  c(108,110,114,116) c(85,86,100,102,104,106)
	#   paste(home_dir,"output/metrics_1109_1209_z",sep="") paste(home_dir,"output/metrics_pt_z",sep="")  paste(home_dir,"output/metrics_1018_1119_z",sep="") paste(home_dir,"output/metrics_1122_1222_z",sep="")

	template_indices_control <- c(84, 0)	
	template_indices_test <- c(85, 0)	
	fname_first_part <- paste(home_dir,"output/metrics_1108_1202_z",sep="")
	
	if (import_metrics)
		import.experimental.metrics.data(template_indices_test, template_indices_control, fname_first_part)
	
	
	
	# PROCESS DATA
	
	edit_count_before_filter <- edit_count_min_lower : edit_count_min_upper
	
	data_counts_test <<- c()
	data_counts_control <<- c()
	
	edit_decrease_means_test <<- c()
	edit_decrease_means_control <<- c()
	
	edit_decrease_sd_test <<- c()
	edit_decrease_sd_control <<- c()	
	
	
	if (registered)
		reg_str = 'registered'
	else
		reg_str = 'non_registered'
	
	for (i in edit_count_before_filter)
	{
		process.data.frames(i,0,Inf,Inf,registered=registered,min_revisions_after=0)
		
		edit_decrease_means_test <<- c(edit_decrease_means_test, mean(merged_test$edits_decrease) * 100)
		edit_decrease_means_control <<- c(edit_decrease_means_control, mean(merged_control$edits_decrease) * 100)
		
		edit_decrease_sd_test <<- c(edit_decrease_sd_test, sd(merged_test$edits_decrease * 100))
		edit_decrease_sd_control <<- c(edit_decrease_sd_control, sd(merged_control$edits_decrease * 100))
		
		data_counts_test <<- c(data_counts_test, length(merged_test$edits_decrease))	
		data_counts_control <<- c(data_counts_control, length(merged_control$edits_decrease))
	}
	
	# PLOT DATA		
	
	plot_title = paste("Huggle Short 1 & 2 Experiment (", reg_str, ") - Decrease in Editor Activity", sep="")
	
	df <- data.frame(x=1:length(edit_decrease_means_test), y_test=edit_decrease_means_test, y_ctrl=edit_decrease_means_control, y_test_sd=edit_decrease_sd_test, y_ctrl_sd=edit_decrease_sd_control)	
	p <- ggplot(df,aes(x)) + geom_line(aes(y=y_test,colour="Test")) + geom_line(aes(y=y_ctrl,colour="Control")) 
	
	if (error_bars)
 		p <- p + geom_errorbar(aes(ymin = y_test - y_test_sd, ymax = y_test + y_test_sd, colour="Test"), width=0.2) + geom_errorbar(aes(ymin = y_ctrl - y_ctrl_sd, ymax = y_ctrl + y_ctrl_sd, colour="Control"), width=0.2)
	
	p <- p + scale_x_continuous('Minimum Edits before Template Posting') + scale_y_continuous('Mean % Decrease in Edit Activity') + opts(title = plot_title, legend.title = theme_blank())
	
	if (save_plot)
		ggsave(paste('/home/rfaulkner/trunk/projects/WSOR/message_templates/R/plots/',filename,reg_str,'.png',sep=""),width=8)
}

