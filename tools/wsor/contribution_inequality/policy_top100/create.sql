
-- Copyright (C) 2011 GIOVANNI LUCA CIAMPAGLIA, GCIAMPAGLIA@WIKIMEDIA.ORG
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
-- 
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
-- GNU General Public License for more details.
-- 
-- You should have received a copy of the GNU General Public License along
-- with this program; if not, write to the Free Software Foundation, Inc.,
-- 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
-- http://www.gnu.org/copyleft/gpl.html
 
drop table if exists giovanni.policy_contributors;

create table giovanni.policy_contributors select 
    page_title as title, 
    page_namespace  as namespace,
    rev_user as user_id, 
    rev_user_text user_name, 
    year(rev_timestamp) as year, 
    count(*) as editcount 
from revision join page 
on rev_page = page_id 
where page_title in (
    "Notability",
    "Verifiability",
    "No_original_research",
    "Neutral_point_of_view",
    "Article_titles",
    "What_Wikipedia_is_not",
    "Wikipedia_is_not_a_dictionary",
    "Biographies_of_living_persons",
    "Consensus",
    "Ignore_all_rules",
    "Deletion_policy",
    "Criteria_for_speedy_deletion",
    "Proposed_deletion",
    "Editing_policy",
    "Citing_sources",
    "Disambiguation",
    "Edit_warring",
    "Civility",
    "No_legal_threats",
    "No_personal_attacks",
    "Ownership_of_articles",
    "Sock_puppetry",
    "Conflict_of_interest",
    "Do_not_disrupt_Wikipedia_to_illustrate_a_point",
    "Etiquette",
    "Gaming_the_system",
    "Manual_of_Style",
    "Manual_of_Style_(abbreviations)",
    "Manual_of_Style_(accessibility)",
    "Manual_of_Style_(article_message_boxes)",
    "Manual_of_Style_(biographies)",
    "Manual_of_Style_(capital_letters)",
    "Manual_of_Style_(captions)",
    "Manual_of_Style_(dates_and_numbers)",
    "Manual_of_Style_(disambiguation_pages)",
    "Manual_of_Style_(embedded_lists)",
    "Manual_of_Style_(footnotes)",
    "Manual_of_Style_(icons)",
    "Manual_of_Style_(infoboxes)",
    "Manual_of_Style_(layout)",
    "Manual_of_Style_(lead_section)",
    "Manual_of_Style_(linking)",
    "Manual_of_Style_(lists)",
    "Manual_of_Style_(lists_of_works)",
    "Manual_of_Style_(pronunciation)",
    "Manual_of_Style_(proper_names)",
    "Manual_of_Style_(self-references_to_avoid)",
    "Manual_of_Style_(spelling)",
    "Manual_of_Style_(summary_style)",
    "Manual_of_Style_(tables)",
    "Manual_of_Style_(text_formatting)",
    "Manual_of_Style_(titles)",
    "Manual_of_Style_(trademarks)",
    "Manual_of_Style_(trivia_sections)",
    "Manual_of_Style_(words_to_watch)",
    "Help_desk",
    "Reference_desk/Computing",
    "Reference_desk/Entertainment",
    "Reference_desk/Humanities",
    "Reference_desk/Language",
    "Reference_desk/Mathematics",
    "Reference_desk/Science",
    "Reference_desk/Miscellaneous",
    "Editor%27s_index_to_Wikipedia",
    "Copyright_problems",
    "Contributor_copyright_investigations",
    "External_links/Noticeboard",
    "Fringe_theories/Noticeboard",
    "Neutral_point_of_view/Noticeboard",
    "Reliable_sources/Noticeboard",
    "Administrator_intervention_against_vandalism",
    "Arbitration_Committee/Noticeboard",
    "Arbitration/Requests/Enforcement",
    "Arbitration/Requests",
    "Editor_review",
    "Dispute_resolution_noticeboard",
    "Wikiquette_assistance",
    "Requests_for_mediation",
    "Notability_(academics)",
    "Notability_(books)",
    "Notability_(events)",
    "Notability_(films)",
    "Notability_(music)",
    "Notability_(numbers)",
    "Notability_(people)",
    "Notability_(sports)",
    "Notability_(web)",
    "Notability_(organizations_and_companies)",
    "No_original_research/Noticeboard",
    "Fiction/Noticeboard",
    "Content_forking",
    "Autobiography",
    "Non-free_content_review",
    "Bureaucrats%27_noticeboard",
    "External_links",
    "Fringe_theories",
    "No_disclaimers_in_articles",
    "Offensive_material",
    "Patent_nonsense",
    "Plagiarism",
    "Spam",
    "Wikipedia_is_not_for_things_made_up_one_day",
    "Sexual_content",
    "Vandalism",
    "Please_do_not_bite_the_newcomers",
    "Arbitration/Policy",
    "Banning_policy",
    "Blocking_policy",
    "Administrators",
    "Bot_policy",
    "Copyright_violations",
    "Image_use_policy",
    "Non-free_content_criteria",
    "Be_bold",
    "Categorization/Ethnicity,_gender,_religion_and_sexuality",
    "Categorization_of_people",
    "Categorization",
    "Citing_sources"
)
and page_namespace in (4,5)
group by rev_user_text, year(rev_timestamp), title, namespace
