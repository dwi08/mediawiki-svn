
"""
    Script to execute a series of commands to perform data retrieval for template tests and  

"""


""" Script meta """
__author__ = "Ryan Faulkner"
__date__ = "January 12th, 2012"


""" Import python base modules """
import sys, logging, datetime, settings, os


""" CONFIGURE THE LOGGER """
LOGGING_STREAM = sys.stderr
logging.basicConfig(level=logging.DEBUG, stream=LOGGING_STREAM, format='%(asctime)s %(levelname)-8s %(message)s', datefmt='%b-%d %H:%M:%S')

"""
    Define script usage
"""
class Usage(Exception):
    
    def __init__(self, msg):
        self.msg = msg

"""
    Execution body of main
"""
def main(args):

    # Set flags for template indices to process 
    template_indices = {1 : False, 2 : False, 3 : False,
                        60 : False, 61 : False, 62 : False, 63 : False, 64 : False, 65 : False, 66 : False, 67 : False, 68 : False, 69 : False,
                        70 : False, 71 : False, 72 : False, 73 : False, 74 : False, 75 : False, 76 : False, 77 : False,
                        84 : False, 85 : False, 86 : False, 99 : False, 100 : False, 101 : False, 102 : False, 103 : False, 104 : False, 
                        105 : False, 106 : False, 107 : False, 108 : False, 109 : False, 110 : False, 111 : False, 112 : False,  113 : False, 
                        114 : False, 115 : False, 116 : False, 78 : False, 79 : False, 81 : False, 82 : False, 87 : True, 88 : True, 
                        89 : True, 90 : True, 91 : True, 92 : True, 93 : True, 94 : True, 95 : True, 96 : True, 97 : True, 98 : True
                        }    
#    template_indices = {78 : True}
    # Run postings and metrics
    
    generator = 'editcount'
        
    postings_cmd = './postings -h db1047 --start=%(start_time)s --end=%(end_time)s --comment="%(rev_comment)s" --message="{{%(template)s}}" --outfilename postings_%(file_name)s.tsv'
    metrics_cmd = 'cat ./output/postings_%(file_name)s.tsv | ./metrics -h db1047 --header --outfilename metrics_%(file_name)s_%(fname_generator)s.tsv %(generator)s'
    
    for key in template_indices:   
        
        name, start_ts, end_ts, comment = get_experiment(key)
        template_name = 'z' + str(key)
        
        if template_indices[key]:
            
            logging.info('Generating postings for %s' % template_name)
            # filename_part = start_ts[4:8] + '_' + end_ts[4:8] + '_' + template_name
            filename_part = 'test_' + start_ts[4:8] + '_' + end_ts[4:8] + '_' + template_name

            os.system(postings_cmd % {'start_time' : start_ts, 'end_time' : end_ts, 'template' : template_name, 'file_name' : filename_part, 'rev_comment' : comment})
            # os.system(metrics_cmd % {'file_name' : filename_part, 'file_name' : filename_part, 'generator' : generator, 'fname_generator' : generator})
        else:
            logging.info('Skipping postings for %s' % template_name)
            
    return 0

"""
    Returns the experiment name and start and end timestamps corresponding to the key 
"""
def get_experiment(index):
    
    if index >= 60 and index <= 77:
        test_handle = 'Huggle_3'
        start_ts = '20111018000000'
        end_ts = '20111119000000'
        comment = '\(\[\[WP:HG\|HG\]\]\)'

    elif index >= 1 and index <= 3:
        test_handle = 'Huggle_1_Portuguese'
        start_ts = '20111027000000'
        end_ts = '20111128000000'
        comment = '\(\[\[WP:HG\|HG\]\]\)'

    elif (index >= 84 and index <= 86) or (index >= 99 and index <= 106):
        test_handle = 'Huggle_Short_1_and_2'
        start_ts = '20111108000000'
        end_ts = '20111202000000'
        comment = '\(\[\[WP:HG\|HG\]\]\)'
        
    elif index >= 107 and index <= 116:
        test_handle = 'Huggle_Short_2'
        start_ts = '20111122000000'
        end_ts = '20111222000000'
        comment = '\(\[\[WP:HG\|HG\]\]\)'
        
    elif (index >= 78 and index <= 79) or (index >= 81 and index <= 82):
        test_handle = 'Twinkle_1'
        start_ts = '20111109000000'
        end_ts = '20111209000000'
        comment = '\(\[\[WP:TW\|TW\]\]\)'

    elif index >= 87 and index <= 98:
        test_handle = 'XLinkBot'
        start_ts = '20111117000000'
        end_ts = '20111217000000'
        comment = 'BOT'

    logging.info('Processing %(test_handle)s from %(start_ts)s to %(end_ts)s on comment "%(comment)s" ...' % {'test_handle' : test_handle, 'start_ts' : start_ts, 'end_ts' : end_ts, 'comment' : comment})
   
    return test_handle, start_ts, end_ts, comment
    
"""
    Call main, exit when execution is complete

""" 
if __name__ == "__main__":
    main([])