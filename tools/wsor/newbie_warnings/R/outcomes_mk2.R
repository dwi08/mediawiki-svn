source("loader/load_huggling_codings_mk2.R")
library(doBy)
hugglings = load_huggling_codings_mk2()

#hugglingCounts = summaryBy(
#	recipient ~ recipient,
#	data = hugglings,
#	FUN=length
#)
#hugglingCounts$count = hugglingCounts$recipient.length
#hugglingCounts$recipient.length = NULL
#
#hugglings = merge(hugglings, hugglingCounts, by=c("recipient"))

#huggling_codings = load_huggling_codings(reload=T)
#messaged_codings = huggling_codings[!is.na(huggling_codings$before_rating),]
ifNA = function(val, naThen){
	if(is.na(val)){
		naThen
	}else{
		val
	}
}

hugglings$contact = with(
	hugglings,
	mapply(
		ifNA,
		responds_hugglers_talk | 
		responds_own_talk | 
		responds_elsewhere | 
		retaliates,
		F
	)
)
hugglings$good_contact = hugglings$contact & !hugglings$retaliates
hugglings$stay         = !is.na(hugglings$after_rating)
hugglings$improves     = hugglings$after_rating > hugglings$before_rating
hugglings$talk_edits_before_msg = with(
	hugglings,
	user_talk_edits_after_msg + article_talk_edits_before_msg
)
# Can't do it
#messaged_codings$ntalk_edits_before_msg = with(
#	messaged_codings,
#	edits_before_msg - talk_edits_before_msg
#)

hugglings$good_outcome = with(
	hugglings,
	( #Suckas leave or get better
		before_rating <= 2 &
		(
			is.na(after_rating) |
			after_rating > 2
		)
	) |
	( #Good contact was made 
		!is.na(good_contact) & 
		good_contact
	) | 
	( #Edits are good after receiving message
		!is.na(after_rating) &
		after_rating > 3
	)
)

##
# Groups
#
# - <= 1        "vandal": We all agreed that the editor was a blatant vandal
# - > 1 & <= 2  "bad faith": We all agreed that the editor was bad faith
# - > 2 & < 4   "test": A test edit, but not good faith
# - >= 4        "good faith": Good faith to excellent
#
# For each group:
#  - contact
#    - contact huggler + retaliate
#    - talk? (wait for staeiou)
#  - continue editing
#    - did they actually
#    - quality
#      - improve
#      - was it good
#      - degrade
#
#
# Predictors:
#  - number of edilts before message
#    - number deleted
#  - makes edits to talk (before/after)

hugglings$group = as.factor(sapply(
	hugglings$before_rating,
	function(rating){
		if(is.na(rating)){
			NA
		}else if(rating <= 1){
			"vandal"
		}else if(rating > 1 & rating <= 2){
			"bad faith"
		}else if(rating > 2 & rating < 4){
			"test"
		}else if(rating >= 4){
			"good faith"
		}else{
			NA
		}
	}
))


for(group in c("vandal", "bad faith", "test", "good faith")){
	group_codings = hugglings[hugglings$group == group,]
	
	
	cat("Result's for ", length(group_codings$group), " '", group, "' editors:\n", sep='')
	cat("============================================================\n")
	
	print(summary(glm(
		good_outcome ~ is_anon + personal + nodirectives,
		data = group_codings
	)))
	
	print(summary(glm(
		improves ~ is_anon + personal + nodirectives,
		data = group_codings[group_codings$before_rating <= 4,]
	)))
	
	print(summary(glm(
		contact ~ is_anon + personal + nodirectives,
		data = group_codings
	)))
	
	print(summary(glm(
		good_contact ~ is_anon + personal + nodirectives,
		data = group_codings[group_codings$contact,]
	)))
	
	print(summary(glm(
		stay ~ is_anon + personal + nodirectives,
		data = group_codings
	)))
	
	cat("\n\n\n")
}

meanNoNA = function(x){
	mean(x, na.rm=T)
}
lengthNoNA = function(x){
	length(na.omit(x))
}

library(lattice)
outcomeNames = list(
	good_outcome = "with a \"good outcome\"",
	improves     = "who show improvement",
	contact      = "who contact the reverting editor",
	good_contact = "who contact the reverting editor nicely",
	stay         = "who make at least one edit after reading the message"
)
for(outcomeName in c("good_outcome", "improves", "contact", "good_contact", "stay")){
	f = with(
		summaryBy(
			outcome ~ group + teaching + personal,
			data = data.frame(
				outcome  = messaged_codings[[outcomeName]],
				teaching = messaged_codings$teaching,
				personal = messaged_codings$personal,
				group    = messaged_codings$group
			),
			FUN=c(meanNoNA, lengthNoNA)
		),
		data.frame(
			group    = group,
			message  = mapply(
				function(personal, teaching){
					if(personal & teaching){
						"personal & teaching"
					}else if(personal){
						"personal"
					}else if(teaching){
						"teaching"
					}else{
						"control"
					}
				},
				personal,
				teaching
			),
			#teaching = teaching,
			#personal = personal,
			prop     = outcome.meanNoNA,
			n        = outcome.lengthNoNA
		)
	)
	cat(outcomeName, "\n")
	cat(f$prop, "\n\n")
	svg(paste("plots/outcome", outcomeName, "all_groups.svg", sep="."), height=4, width=8)
	print(barchart(
		prop ~ group | message,
		data = f,
		layout=c(4,1),
		xlab="Pre-message rating",
		lab="Proportion of editors",
		main=paste("Proportion of editors", outcomeNames[[outcomeName]])
	))
	dev.off()
}

messaged_codings$default = !messaged_codings$personal & !messaged_codings$teaching
messaged_codings$teaching_only = messaged_codings$teaching & !messaged_codings$personal
messaged_codings$personal_only = !messaged_codings$teaching & messaged_codings$personal
messaged_codings$teaching_and_personal = messaged_codings$teaching & messaged_codings$personal

s = scale

for(condition in c("teaching_only", "personal_only", "teaching_and_personal")){
	cat("-----------------------------------------------------------\n")
	cat("-----------", condition, "\n")
	cat("-----------------------------------------------------------\n")
	exp_codings = messaged_codings[
		messaged_codings[[condition]] | 
		messaged_codings$default,
	]
	
	exp_codings$condition = exp_codings[[condition]]
	
	print(summary(glm(
		good_outcome ~ 
		anon + 
		s(ntalk_edits_before_msg) + 
		s(talk_edits_before_msg) + 
		s(before_rating) * 
		condition,
		data = exp_codings[exp_codings$image,]
	)))
	print(summary(glm(
		good_outcome ~ 
		anon + 
		s(ntalk_edits_before_msg) + 
		s(talk_edits_before_msg) + 
		s(before_rating) * 
		condition,
		data = exp_codings[!exp_codings$image,]
	)))
	
	
	print(summary(glm(
		improves ~ 
		anon + 
		s(ntalk_edits_before_msg) + 
		s(talk_edits_before_msg) + 
		s(before_rating) * 
		condition,
		data = exp_codings[exp_codings$image,]
	)))
	print(summary(glm(
		improves ~ 
		anon + 
		s(ntalk_edits_before_msg) + 
		s(talk_edits_before_msg) + 
		s(before_rating) * 
		condition,
		data = exp_codings[!exp_codings$image,]
	)))
	
	
	print(summary(glm(
		contact ~ 
		anon + 
		s(ntalk_edits_before_msg) + 
		s(talk_edits_before_msg) + 
		s(before_rating) * 
		condition,
		data = exp_codings[exp_codings$image,]
	)))
	print(summary(glm(
		contact ~ 
		anon + 
		s(ntalk_edits_before_msg) + 
		s(talk_edits_before_msg) + 
		s(before_rating) * 
		condition,
		data = exp_codings[!exp_codings$image,]
	)))
	
	
	print(summary(glm(
		good_contact ~ 
		anon + 
		s(ntalk_edits_before_msg) + 
		s(talk_edits_before_msg) + 
		s(before_rating) * 
		condition,
		data = exp_codings[exp_codings$image,]
	)))
	print(summary(glm(
		good_contact ~ 
		anon + 
		s(ntalk_edits_before_msg) + 
		s(talk_edits_before_msg) + 
		s(before_rating) * 
		condition,
		data = exp_codings[!exp_codings$image,]
	)))
	
	
	print(summary(glm(
		stay ~ 
		anon + 
		s(ntalk_edits_before_msg) + 
		s(talk_edits_before_msg) + 
		s(before_rating) * 
		condition,
		data = exp_codings[exp_codings$image,]
	)))
	print(summary(glm(
		stay ~ 
		anon + 
		s(ntalk_edits_before_msg) + 
		s(talk_edits_before_msg) + 
		s(before_rating) * 
		condition,
		data = exp_codings[!exp_codings$image,]
	)))
}


