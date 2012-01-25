
# Ryan Faulkner, January 23rd 2012
#
# Comparison of edit counts for Huggle 3 test among templates z64 (http://en.wikipedia.org/wiki/Template:Uw-error1-default) / z65 (http://en.wikipedia.org/wiki/Template:Uw-error1-short)
#  

# Read aggregated results for z64

metrics_ec_z64 = read.table("/home/rfaulk/WSOR/message_templates/output/metrics_1018_1119_z70_editcounts.tsv", na.strings="\\N", sep="\t", comment.char="", quote="", header=T)
metrics_blocks_z64 = read.table("/home/rfaulk/WSOR/message_templates/output/metrics_1018_1119_z70_blocks.tsv", na.strings="\\N", sep="\t", comment.char="", quote="", header=T)


# Read aggregated results for z65

metrics_ec_z65 = read.table("/home/rfaulk/WSOR/message_templates/output/metrics_1018_1119_z71_editcounts.tsv", na.strings="\\N", sep="\t", comment.char="", quote="", header=T)
metrics_blocks_z65 = read.table("/home/rfaulk/WSOR/message_templates/output/metrics_1018_1119_z71_blocks.tsv", na.strings="\\N", sep="\t", comment.char="", quote="", header=T)


# Compute max edit counts

max_ec_z64_ns_0 = max(append(metrics_ec_z64['ns_0_revisions_before'][[1]], metrics_ec_z64['ns_0_revisions_after'][[1]]))
max_ec_z64_ns_3 = max(append(metrics_ec_z64['ns_3_revisions_before'][[1]], metrics_ec_z64['ns_3_revisions_after'][[1]]))
max_ec_z65_ns_0 = max(append(metrics_ec_z65['ns_0_revisions_before'][[1]], metrics_ec_z65['ns_0_revisions_after'][[1]]))
max_ec_z65_ns_3 = max(append(metrics_ec_z65['ns_3_revisions_before'][[1]], metrics_ec_z65['ns_3_revisions_after'][[1]]))


# Compute the increase 

z64_ns0 = (metrics_ec_z64$ns_0_revisions_after - metrics_ec_z64$ns_0_revisions_before) / metrics_ec_z64$ns_0_revisions_before
z65_ns0 = (metrics_ec_z65$ns_0_revisions_after - metrics_ec_z65$ns_0_revisions_before / metrics_ec_z65$ns_0_revisions_before


# User Talk namespace does not necessarily have edits before - in this case omit the result (it could be the case that templates stimulate user talk edits but that should be tested separately)
# Only append non-zero results - do this for just namespace 3 since it has zero entries for 'ns_3_revisions_before' 

z64_ns3 <- c()
z65_ns3 <- c()

for (i in 1:length(metrics_ec_z64['ns_3_revisions_before'][[1]])) 
	if (metrics_ec_z64['ns_3_revisions_before'][[1]][i] != 0)
		z64_ns3 <- c(z64_ns3, 
		(metrics_ec_z64['ns_3_revisions_before'][[1]][i] - metrics_ec_z64['ns_3_revisions_after'][[1]][i]) / metrics_ec_z64['ns_3_revisions_before'][[1]][i])

for (i in 1:length(metrics_ec_z65['ns_3_revisions_before'][[1]])) 
	if (metrics_ec_z65['ns_3_revisions_before'][[1]][i] != 0)
		z65_ns3 <- c(z65_ns3, 
		(metrics_ec_z65['ns_3_revisions_before'][[1]][i] - metrics_ec_z65['ns_3_revisions_after'][[1]][i]) / metrics_ec_z65['ns_3_revisions_before'][[1]][i])


# Generate results:

summary(z65_ns0)
summary(z64_ns0)
summary(z65_ns3)
summary(z64_ns3)

t_result_ns0 = t.test(x=z64_ns0, y=z65_ns0, alternative = "two.sided", paired = FALSE, var.equal = FALSE, conf.level = 0.95)
t_result_ns3 = t.test(x=z64_ns3, y=z65_ns3, alternative = "two.sided", paired = FALSE, var.equal = FALSE, conf.level = 0.95)


