source("loader/load_hugglings.R")
source("loader/load_huggling_codings.R")
library(doBy)
hugglings = load_hugglings()

hugglingCounts = summaryBy(
	recipient ~ recipient,
	data = hugglings,
	FUN=length
)
hugglingCounts$count = hugglingCounts$recipient.length
hugglingCounts$recipient.length = NULL

hugglings = merge(hugglings, hugglingCounts, by=c("recipient"))

huggling_codings = load_huggling_codings(reload=T)
messaged_codings = huggling_codings[!is.na(huggling_codings$before_rating),]

messaged_codings$retailates   = messaged_codings$retaliates > 0
messaged_codings$contact      = !is.na(messaged_codings$contacts_huggler) & (messaged_codings$contacts_huggler > 0 | messaged_codings$retaliates > 0)
messaged_codings$quality_work = messaged_codings$after_rating >= 3.0
messaged_codings$stay         = !is.na(messaged_codings$after_rating)
messaged_codings$improves     = messaged_codings$after_rating > messaged_codings$before_rating
messaged_codings$anon         = messaged_codings$is_anon > 0
messaged_codings$talk_edits_before_msg = with(
	messaged_codings,
	user_talk_edits_after_msg + article_talk_edits_before_msg
)
messaged_codings$ntalk_edits_before_msg = with(
	messaged_codings,
	edits_before_msg - talk_edits_before_msg
)
messaged_codings$good_contact = mapply(
	function(contact, retaliates){
		if(!is.na(contact) & contact){
			retaliates <= 0 
		}else{
			NA
		}
	},
	messaged_codings$contact,
	messaged_codings$retaliates
)
messaged_codings$good_outcome = with(
	messaged_codings,
	(
		before_rating <= 2 &
		(
			is.na(after_rating) |
			after_rating > 2
		)
	) |
	(
		!is.na(good_contact) & 
		good_contact
	) | 
	(
		!is.na(quality_work) &
		quality_work
	)
)

##
# Groups
#
# - < 2          at least one of us thought "no hope"
# - >= 2 & <= 3  possibles
# - > 3          at least one of us thought "golden"
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

messaged_codings$group = as.factor(sapply(
	messaged_codings$before_rating,
	function(rating){
		if(is.na(rating)){
			NA
		}else if(rating < 2){
			"unlikely"
		}else if(rating <= 3){
			"possible"
		}else{
			"golden"
		}
	}
))

formatNum = function(num){
	if(!is.numeric(num) | is.nan(num)){
		" ---  "
	}
	else if(num >= 0){
		paste(" ", format(round(num, 3), nsmall=3), sep="")
	}else{
		format(round(num, 3), nsmall=3)
	}
}

for(group in c("unlikely", "possible", "golden")){
	group_codings = messaged_codings[messaged_codings$group == group,]
	
	
	cat("Result's for ", length(group_codings$group), " '", group, "' editors:\n", sep='')
	cat("============================================================\n")
	
	control      = group_codings[!group_codings$personal & !group_codings$teaching,]
	personal     = group_codings[group_codings$personal & !group_codings$teaching,]
	teaching     = group_codings[group_codings$teaching & !group_codings$personal,]
	both         = group_codings[group_codings$teaching & group_codings$personal,]
	
	experiments = list(
		list(name="Personal           ", data=personal),
		list(name="Teaching           ", data=teaching),
		list(name="Personal & Teaching", data=teaching)
	)
	
	outcomes = list(
		list(name="Good outcome", field="good_outcome"),
		list(name="Improves", field="improves"),
		list(name="Contact", field="contact"),
		list(name="Stays", field="stay"),
		list(name="Good contact", field="good_contact")
	)
		
	for(outcome in outcomes){
		cat(outcome$name, ": \n", sep="")
		
		controlLen = length(na.omit(control[[outcome$field]]))
		controlSuccess = sum(control[[outcome$field]], na.rm=T)
		cat(
			"\tControl            ",
			": prop=", formatNum(controlSuccess/controlLen),
			", n=", controlLen, "\n", 
			sep=""
		)
		for(experiment in experiments){
			expSuccess = sum(experiment$data[[outcome$field]], na.rm=T)
			expLen  = length(na.omit(experiment$data[[outcome$field]]))
			if(controlLen > 0 & expLen > 0){
				t = prop.test(
					c(
						expSuccess, 
						controlSuccess
					),
					c(
						expLen,
						controlLen
					)
				)
			}else{
				t = list(
					p.value=NA,
					conf.int=c(NA, NA)
				)
			}
			
			propDiff = mean(experiment$data[[outcome$field]], na.rm=T)-mean(control[[outcome$field]], na.rm=T)
			cat(
				"\t", experiment$name, 
				": prop=", formatNum(expSuccess/expLen),
				", diff=", formatNum(propDiff),
				", p-value=", formatNum(t$p.value),
				", conf.int=(", formatNum(t$conf.int[1]), ", ", formatNum(t$conf.int[2]), ")", 
				", n=", expLen, "\n",
				sep=""
			)
		}
		cat("\n")
	}
	
	
	cat("\n\n\n")
}

