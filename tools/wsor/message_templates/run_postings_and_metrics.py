
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
                        114 : False, 115 : False, 116 : False, 78 : False, 79 : False, 81 : False, 82 : False, 87 : False, 88 : False, 
                        89 : True, 90 : True, 91 : True, 92 : True, 93 : True, 94 : True, 95 : True, 96 : True, 97 : True, 98 : True
                        }    
    
    # Run postings and metrics
    
    generator = 'editcount'
    postings_cmd = './postings -h db1047 --start=%(start_time)s --end=%(end_time)s --comment="\(\[\[WP:HG\|HG\]\]\)" --message="{{%(template)s}}" --outfilename postings_%(file_name)s.tsv'
    metrics_cmd = 'cat ./output/postings_%(file_name)s.tsv | ./metrics -h db1047 --header --outfilename metrics_%(file_name)s_%(fname_generator)s.tsv %(generator)s'
    
    for key in template_indices:   
        
        name, start_ts, end_ts = get_experiment(key)
        template_name = 'z' + str(key)
        
        if template_indices[key]:
            
            logging.info('Generating postings for %s' % template_name)
            filename_part = start_ts[4:8] + '_' + end_ts[4:8] + '_' + template_name

            os.system(postings_cmd % {'start_time' : start_ts, 'end_time' : end_ts, 'template' : template_name, 'file_name' : filename_part})
            # os.system(metrics_cmd % {'file_name' : filename_part, 'file_name' : filename_part, 'generator' : generator, 'fname_generator' : generator})
        else:
            logging.info('Skipping postings for %s' % template_name)
            
    return 0

"""
    Returns the experiment name and start and end timestamps corresponding to the key 
"""
def get_experiment(index):
    
    if index >= 60 and index <= 77:
        return 'Huggle_3', '20111018000000', '20111119000000'
    elif index >= 1 and index <= 3:
        return 'Huggle_1_Portuguese', '20111027000000', '20111128000000'
    elif (index >= 84 and index <= 86) or (index >= 99 and index <= 106):
        return 'Huggle_Short_1_and_2', '20111108000000', '20111202000000'
    elif index >= 107 and index <= 116:
        return 'Huggle_Short_2', '20111122000000', '20111222000000'
    elif (index >= 77 and index <= 79) or (index >= 81 and index <= 82):
        return 'Twinkle_1', '20111109000000', '20111209000000'
    elif index >= 87 and index <= 98:
        return 'XLinkBot', '20111117000000', '20111217000000'

"""
    Call main, exit when execution is complete

""" 
if __name__ == "__main__":
    main([])