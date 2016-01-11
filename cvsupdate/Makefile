export DBNAME=chgk
export HOME=/home/znatoki/chgk-db
DOCUMENTROOT=$(HOME)/public_html
SRCDIR=$(CURDIR)/baza
DICTDIR=$(CURDIR)/dict
IMAGEDIR=$(SRCDIR)/images
SOUNDDIR=$(SRCDIR)/sounds
ATTDIR=$(SRCDIR)/attachments
DESTIMAGEDIR=$(DOCUMENTROOT)/images/db
DESTSOUNDDIR=$(DOCUMENTROOT)/sounds/db
DESTATTDIR=$(DOCUMENTROOT)/attachments
DUMPDIR=$(CURDIR)/dump
INDEXTIMESTAMP= $(DUMPDIR)/index_timestamp_$(DBNAME)
TEXTTIMESTAMP= $(DUMPDIR)/text_timestamp_$(DBNAME)
IMAGETIMESTAMP= $(DUMPDIR)/image_timestamp_$(DBNAME)
SOUNDTIMESTAMP= $(DUMPDIR)/sound_timestamp_$(DBNAME)
ATTTIMESTAMP= $(DUMPDIR)/att_timestamp_$(DBNAME)
SEARCHTIMESTAMP= $(DUMPDIR)/search_timestamp_$(DBNAME)
AUTHORSTIMESTAMP=$(DUMPDIR)/authors_timestamp_$(DBNAME)
ALLTIMESTAMP=$(DUMPDIR)/all_timestamp_$(DBNAME)
RATINGTIMESTAMP=$(DUMPDIR)/rating_timestamp_$(DBNAME)

TEXTS=$(wildcard $(SRCDIR)/*.txt)
IMAGES=$(wildcard $(IMAGEDIR)/*.gif) $(wildcard $(IMAGEDIR)/*.jpg)
SOUNDS=$(wildcard $(SOUNDDIR)/*.mp3) $(wildcard $(SOUNDDIR)/*.mid)
ATTACHMENTS=$(wildcard $(ATTDIR)/*)
CVS=cvs -d :pserver:roma7@bilbo.dynip.com:/home/cvsroot

INSTALLSCRIPTS=$(CURDIR)/../install
QUESTIONSINDEXEDFILE=$(INSTALLSCRIPTS)/.$(DBNAME)_questions_indexed
TOURSINDEXEDFILE=$(INSTALLSCRIPTS)/.$(DBNAME)_tournaments_indexed

all: update_index fill_questions copy_images copy_sounds copy_attachments updateauthors updatedate $(QUESTIONSINDEXEDFILE)

rebuild:
	perl $(CURDIR)/mkdb.pl 
	$(CVS) update baza/index
	rm -f $(INDEXTIMESTAMP)
	$(MAKE) $(INDEXTIMESTAMP)
	$(CVS) checkout baza
	$(CURDIR)/updatedb.pl baza/*.txt
	touch $(TEXTTIMESTAMP)
	$(MAKE) find_created
	rm -f $(RATINGTIMESTAMP)
	$(MAKE) $(RATINGTIMESTAMP)
	$(MAKE) reindexquestions
	$(CVS) checkout baza/images
	cp $(IMAGEDIR)/*.* $(DESTIMAGEDIR)
	$(CVS) checkout baza/sounds
	cp $(SOUNDDIR)/*.* $(DESTSOUNDDIR)
	rm -f $(AUTHORSTIMESTAMP)
	$(MAKE) $(AUTHORSTIMESTAMP)
	rm -f $(ALLTIMESTAMP)
	$(MAKE) updatedate

	
update_index: 
	$(CVS) update baza/index
	$(MAKE) $(INDEXTIMESTAMP)

$(INDEXTIMESTAMP): $(SRCDIR)/index 
	perl $(CURDIR)/updateindex.pl -i$?
	touch $(INDEXTIMESTAMP)
	$(MAKE) reindextours
	
fill_questions: 
	$(CVS) checkout baza
	$(MAKE) $(TEXTTIMESTAMP)


copy_images:
	$(CVS) checkout baza/images
	$(MAKE) $(IMAGETIMESTAMP)

copy_sounds: $(SOUNDS)
	$(CVS) checkout baza/sounds
	$(MAKE) $(SOUNDTIMESTAMP)

copy_attachments: $(ATTACHMENTS)
	$(CVS) checkout baza/attachments
	$(MAKE) $(ATTTIMESTAMP)

$(TEXTTIMESTAMP): $(TEXTS) 
	$(CURDIR)/updatedb.pl $?
	$(MAKE) find_created
	$(MAKE) reindexquestions
	touch $(TEXTTIMESTAMP)


$(IMAGETIMESTAMP): $(IMAGES)
	for image in $? ; do cp $$image $(DESTIMAGEDIR)/ ; done
	touch $(IMAGETIMESTAMP)

$(SOUNDTIMESTAMP): $(SOUNDS)
	for sound in $? ; do cp $$sound $(DESTSOUNDDIR)/ ; done
	touch $(SOUNDTIMESTAMP)

$(ATTTIMESTAMP): $(ATTACHMENTS)
	for att in $? ; do cp $$att $(DESTATTDIR)/ ; done
	touch $(ATTTIMESTAMP)

$(ALLTIMESTAMP): $(IMAGETIMESTAMP) $(SOUNDTIMESTAMP) $(ATTTIMESTAMP) $(TEXTTIMESTAMP) $(INDEXTIMESTAMP)
	$(INSTALLSCRIPTS)/updatemission $(INSTALLSCRIPTS)/$(DBNAME).cnf
	$(INSTALLSCRIPTS)/updatedate $(INSTALLSCRIPTS)/$(DBNAME).cnf
	$(INSTALLSCRIPTS)/clear_cache $(INSTALLSCRIPTS)/$(DBNAME).cnf
	touch $(ALLTIMESTAMP)
	
find_created: 
	cd $(SRCDIR) && cvs log -l -r1.1 *.txt >$(DUMPDIR)/cvs_log.txt 
	$(CURDIR)/find_created.pl $(DUMPDIR)/cvs_log.txt >x

$(QUESTIONSINDEXEDFILE): $(TEXTTIMESTAMP) $(RATINGTIMESTAMP)
	$(MAKE) reindexquestions

reindexquestions:
	$(INSTALLSCRIPTS)/reindex $(INSTALLSCRIPTS)/$(DBNAME).cnf
#	rm -f $(QUESTIONSINDEXEDFILE) 

reindextours:
	rm -f $(TOURSINDEXEDFILE)

updateauthors:
	$(CVS) checkout dict	
	$(MAKE) $(AUTHORSTIMESTAMP)


updatedate:
	$(MAKE) $(ALLTIMESTAMP)
	
$(AUTHORSTIMESTAMP): dict/nicks dict/authors $(TEXTTIMESTAMP) $(INDEXTIMESTAMP)
	$(MAKE) people
	
people:
	perl makepeople.pl
	perl makeeditors.pl
	perl makeauthors.pl
	touch $(AUTHORSTIMESTAMP)

makerating:
	$(MAKE) $(RATINGTIMESTAMP)
	
$(RATINGTIMESTAMP): dict/rating_map.txt dict/rating_questions.txt $(TEXTTIMESTAMP)
	perl makerating.pl
