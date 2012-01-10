postings = read.table("/home/halfak/Sandbox/wsor/message_templates/data/twinkle/postings.20120104.tsv", na.strings="\\N", sep="\t", comment.char="", quote="", header=T)
metrics = read.table("/home/halfak/Sandbox/wsor/message_templates/data/twinkle/metrics.20120105.tsv", na.strings="\\N", sep="\t", comment.char="", quote="", header=T)
combined = merge(postings, metrics, by=c("recipient_name", "timestamp"))


summary(postings)

#Number of message recipients grouped by previous main namespace edits and order of magnitude
table(10^round(log(combined$ns_0_revisions_before, base=10)))


