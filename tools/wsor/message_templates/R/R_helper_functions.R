# Ryan Faulkner, January 26th 2012
#
# Comparison of metrics for Huggle 3 using a chi-square goodness of fit test
#  



# FUNCTION
#
# Given a set of data compute a normal distribution and the probabilities of falling on each bin
#
# 	bins --
# 	data --
#

get_normal_bins <- function(bins, data) {

	sample_sd <- sd(data)
	sample_mean <- mean(data)

	# vector to store bucket probabilities	
	probs <- c()
	num_bins <- length(bins)
	
	# Compute the probabilities
	
	for (i in 1:num_bins) 
	{
		if (i == 1) {
			upper <- bins[1] + ((bins[2] - bins[1]) / 2)
			lower <- bins[1] - ((bins[2] - bins[1]) / 2)
		} else if (i == num_bins) {
			upper <- bins[num_bins] + ((bins[num_bins] - bins[num_bins-1]) / 2)
			lower <- bins[num_bins] - ((bins[num_bins] - bins[num_bins-1]) / 2)
		} else {
			ip1 <- i + 1
			im1 <- i - 1
			upper <- bins[i] + ((bins[ip1] - bins[i]) / 2)
			lower <- bins[i] - ((bins[i] - bins[im1]) / 2)
		}

		p = pnorm(upper, mean = sample_mean, sd = sample_sd, log = FALSE) - pnorm(lower, mean = sample_mean, sd = sample_sd, log = FALSE)
		probs <- c(probs, p)
	}
	
	probs <- probs / sum(probs) 	# normalize the probabilities
	probs
}



# FUNCTION
#
#
# Given a set of data compute a normal distribution and the probabilities of falling on each bin
#
# 	bins --
# 	value --
#

find_bin <- function(bins, value) {
	distances <- abs(bins - value) 
	index <- order(sapply(distances, min))[1]
	bins[index]
}


# FUNCTION :: get_bin_counts
#
# Given a set of data break it into bins and return the counts with the bin index
#

get_bin_counts <- function(bins, data) {
	
	new_data <- c()
	for (i in 1:length(data))		
	{
		bin <- find_bin(bins, data[i])
		new_data <- c(new_data, bin)
	}
		
	tab <- table(new_data)
	xu <- as.numeric(names(tab))
	xn <- as.vector(tab)
	data.frame(values=xu, counts=xn)
}


# FUNCTION :: construct_probs
#
# Extract the probabilities corresponding to the samples
#

construct_probs <- function(values, full_probs) {
	
	sample_probs <- c()
	
	for (i in 1:length(values))
	{
		val <- values[i]
		bin <- find_bin(full_probs$values, val)
		index <- which(full_probs$values == bin)[1]
		sample_probs <- c(sample_probs, full_probs$counts[index])
	}
	
	sample_probs
}


# FUNCTION :: convert_to_bins
#
# Maps counts from a data frame (values, counts) to a pre-defined set of bins
#

convert_to_bins <- function(bins, samples) {
	
	for (i in 1:length(samples$values))
		samples$values[i] <- find_bin(bins, samples$values[i])
	
	samples
}


# FUNCTION :: pad_counts
#
# Pad counts from a data frame (values, counts) in a given range to contain 0 values where a bin is missing
#

pad_counts <- function(bin_range, samples) {

	new_values <- c()
	new_counts <- c()
		
	for (i in bin_range)
	{
		if (i %in% samples$values)
		{
			index <- which(samples$values == i)[1]
			new_values <- c(new_values, i)
			new_counts <- c(new_counts, samples$counts[index])

		} else {
			new_values <- c(new_values, i)
			new_counts <- c(new_counts, 0)
		}
	}
	
	data.frame(values=new_values, counts=new_counts)
}