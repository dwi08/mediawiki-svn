
# Ryan Faulkner, January 23rd 2012
#
# Comparison of edit counts for Huggle 3 test among templates z64 (http://en.wikipedia.org/wiki/Template:Uw-error1-default) / z65 (http://en.wikipedia.org/wiki/Template:Uw-error1-short)
#  

# Read aggregated results for z64

metrics_ec_z64 = read.table("/home/rfaulk/WSOR/message_templates/output/metrics_1018_1119_z64_editcounts.tsv", na.strings="\\N", sep="\t", comment.char="", quote="", header=T)
metrics_blocks_z64 = read.table("/home/rfaulk/WSOR/message_templates/output/metrics_1018_1119_z64_blocks.tsv", na.strings="\\N", sep="\t", comment.char="", quote="", header=T)


# Read aggregated results for z65

metrics_ec_z65 = read.table("/home/rfaulk/WSOR/message_templates/output/metrics_1018_1119_z65_editcounts.tsv", na.strings="\\N", sep="\t", comment.char="", quote="", header=T)
metrics_blocks_z65 = read.table("/home/rfaulk/WSOR/message_templates/output/metrics_1018_1119_z65_blocks.tsv", na.strings="\\N", sep="\t", comment.char="", quote="", header=T)


# Compute max edit counts

max_ec_z64_ns_0 = max(append(metrics_ec_z64['ns_0_revisions_before'][[1]], metrics_ec_z64['ns_0_revisions_after'][[1]]))
max_ec_z64_ns_3 = max(append(metrics_ec_z64['ns_3_revisions_before'][[1]], metrics_ec_z64['ns_3_revisions_after'][[1]]))
max_ec_z65_ns_0 = max(append(metrics_ec_z65['ns_0_revisions_before'][[1]], metrics_ec_z65['ns_0_revisions_after'][[1]]))
max_ec_z65_ns_3 = max(append(metrics_ec_z65['ns_3_revisions_before'][[1]], metrics_ec_z65['ns_3_revisions_after'][[1]]))


# Compute edit count vectors -- normalize values by the maximum

z64_ns0 = (metrics_ec_z64['ns_0_revisions_before'][[1]] - metrics_ec_z64['ns_0_revisions_after'][[1]]) / metrics_ec_z64['ns_0_revisions_before'][[1]]
z64_ns3 = (metrics_ec_z64['ns_3_revisions_before'][[1]] - metrics_ec_z64['ns_3_revisions_after'][[1]]) / metrics_ec_z64['ns_3_revisions_before'][[1]]
z65_ns0 = (metrics_ec_z65['ns_0_revisions_before'][[1]] - metrics_ec_z65['ns_0_revisions_after'][[1]]) / metrics_ec_z65['ns_0_revisions_before'][[1]]
z65_ns3 = (metrics_ec_z65['ns_3_revisions_before'][[1]] - metrics_ec_z65['ns_3_revisions_after'][[1]]) / metrics_ec_z65['ns_3_revisions_before'][[1]]

## Generate results:

summary(z65_ns0)
summary(z64_ns0)
summary(z65_ns3)
summary(z64_ns3)

t_result_ns0 = t.test(x=z64_ns0, y=z65_ns0, alternative = "two.sided", paired = TRUE, var.equal = FALSE, conf.level = 0.95)
t_result_ns3 = t.test(x=z64_ns3, y=z65_ns3, alternative = "two.sided", paired = TRUE, var.equal = FALSE, conf.level = 0.95)

# combined = merge(postings, metrics_ec, by=c("recipient_name", "timestamp"))
# summary(postings)
# Number of message recipients grouped by previous main namespace edits and order of magnitude
# table(10^round(log(combined$ns_0_revisions_before, base=10)))


