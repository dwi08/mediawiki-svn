import pymongo
import codecs
from pymongo.master_slave_connection import MasterSlaveConnection

master = pymongo.Connection('alpha')
slaves = [pymongo.Connection('beta')]
conn = MasterSlaveConnection(master, slaves)
db = conn['wikilytics']

articles = set()
namespaces = ['4','5']
editors_found=[]

cursor = db.enwiki_articles_dataset.find({'category': 'Deletion'})
for article in cursor:
    articles.add(article['id'])

cursor = db.enwiki_editors_dataset.find()

fh = codecs.open('taxonomy_deletion', 'w', 'utf-8')

for editor in cursor:
    articles_edited = editor.get('articles_edited', {})
    years = articles_edited.keys()
    username = editor['username']
    for year in years:
        months = articles_edited[year].keys()
        for month in months:
            for ns in namespaces:
                articles_list = articles_edited[year][month].get(ns, [])
                arts = set()
                for article in articles_list:
                    arts.add(article)
                deletion_articles = articles.intersection(arts)
                if len(deletion_articles) > 10 and editor['editor'] not in editors_found:
                    time = '%s-%s' % (year, month)
                    #if type(username) == type(str()):
                    #    usename = username.decode('utf-8')
                    #elif type(username) == type(unicode()):
                    #    usename = username.encode('utf-8')
                    #print '%s\t%s\t%s\t%s' % (username, editor['editor'],time, len(deletion_articles))
                    fh.write('%s\t%s\t%s\t%s\n' % (username, editor['editor'],time, len(deletion_articles)))
            editors_found.append(editor['editor'])
fh.close()

