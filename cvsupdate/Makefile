export DB_CHGK_NAME=chgk
export DB_DRUPAL_NAME=chgk_drupal
export DB_USERNAME=root
export DB_USERPASS=
export WORKDIR=$(CURDIR)/dump
export CNF=empty
export SPHINX_DIR=$(WORKDIR)/sphinx
export SPHINX_NAME=$(DB_CHGK_NAME)
export SPHINX_PORT=9312
export DOCUMENT_ROOT=$(HOME)/public_html
CVS_USER=roma7
SPHINX_HOST=localhost
SRCDIR=$(CURDIR)/baza
DICTDIR=$(CURDIR)/dict
IMAGEDIR=$(SRCDIR)/images
SOUNDDIR=$(SRCDIR)/sounds
ATTDIR=$(SRCDIR)/attachments
DESTIMAGEDIR=$(DOCUMENT_ROOT)/images/db
DESTSOUNDDIR=$(DOCUMENT_ROOT)/sounds/db
DESTATTDIR=$(DOCUMENT_ROOT)/attachments
INDEXTIMESTAMP= $(WORKDIR)/index_timestamp_$(DB_CHGK_NAME)
TEXTTIMESTAMP= $(WORKDIR)/text_timestamp_$(DB_CHGK_NAME)
IMAGETIMESTAMP= $(WORKDIR)/image_timestamp_$(DB_CHGK_NAME)
SOUNDTIMESTAMP= $(WORKDIR)/sound_timestamp_$(DB_CHGK_NAME)
ATTTIMESTAMP= $(WORKDIR)/att_timestamp_$(DB_CHGK_NAME)
SEARCHTIMESTAMP= $(WORKDIR)/search_timestamp_$(DB_CHGK_NAME)
AUTHORSTIMESTAMP=$(WORKDIR)/authors_timestamp_$(DB_CHGK_NAME)
ALLTIMESTAMP=$(WORKDIR)/all_timestamp_$(DB_CHGK_NAME)
RATINGTIMESTAMP=$(WORKDIR)/rating_timestamp_$(DB_CHGK_NAME)
INITED=$(WORKDIR)/inited

TEXTS=$(wildcard $(SRCDIR)/*.txt)
IMAGES=$(wildcard $(IMAGEDIR)/*.gif) $(wildcard $(IMAGEDIR)/*.jpg)
SOUNDS=$(wildcard $(SOUNDDIR)/*.mp3) $(wildcard $(SOUNDDIR)/*.mid)
ATTACHMENTS=$(wildcard $(ATTDIR)/*)
CVS=cvs -d :pserver:$(CVS_USER)@bilbo.dynip.com:/home/cvsroot

INSTALLSCRIPTS=$(CURDIR)/../install
QUESTIONSINDEXEDFILE=$(INSTALLSCRIPTS)/.$(DB_CHGK_NAME)_questions_indexed
TOURSINDEXEDFILE=$(INSTALLSCRIPTS)/.$(DB_CHGK_NAME)_tournaments_indexed


all: update_index fill_questions copy_images copy_sounds copy_attachments updateauthors updatedate $(QUESTIONSINDEXEDFILE)

rebuild: $(INITED)
	perl $(CURDIR)/mkdb.pl 
	$(CVS) checkout baza
	rm -f $(INDEXTIMESTAMP)
	$(MAKE) $(INDEXTIMESTAMP)
	$(CURDIR)/updatedb.pl baza/*.txt
	touch $(TEXTTIMESTAMP)
	$(MAKE) find_created
	$(CVS) checkout dict
	rm -f $(RATINGTIMESTAMP)
	$(MAKE) $(RATINGTIMESTAMP)
	rm -f $(AUTHORSTIMESTAMP)
	$(MAKE) $(AUTHORSTIMESTAMP)
	$(MAKE) reindexquestions
	rm -f $(ALLTIMESTAMP)
	$(MAKE) updatedate
	$(MAKE) deploy_static

deploy_static: | $(DESTIMAGEDIR) $(DESTSOUNDDIR)
	find $(IMAGEDIR) -type f -exec cp {} $(DESTIMAGEDIR)/ \; 
	find $(SOUNDDIR) -type f -exec cp {} $(DESTSOUNDDIR)/ \; 

$(WORKDIR):
	mkdir $(WORKDIR)

$(DESTIMAGEDIR):
	mkdir -p $(DESTIMAGEDIR)	

$(DESTSOUNDDIR):
	mkdir -p $(DESTSOUNDDIR)	

$(INITED): | $(WORKDIR)
	$(CVS) login -p
	touch $(INITED)

$(SPHINX_DIR): | $(WORKDIR)
	mkdir $(SPHINX_DIR)

init_sphinx:
	$(CURDIR)/make_sphinx_conf

start_sphinx:
	$(INSTALLSCRIPTS)/start_sphinx
stop_sphinx:
	$(INSTALLSCRIPTS)/stop_sphinx
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
	$(INSTALLSCRIPTS)/updatemission $(INSTALLSCRIPTS)/$(DB_CHGK_NAME).cnf
	$(INSTALLSCRIPTS)/updatedate $(INSTALLSCRIPTS)/$(DB_CHGK_NAME).cnf
	$(INSTALLSCRIPTS)/clear_cache $(INSTALLSCRIPTS)/$(DB_CHGK_NAME).cnf
	touch $(ALLTIMESTAMP)
	
find_created: 
	cd $(SRCDIR) && cvs log -l -r1.1 *.txt >$(WORKDIR)/cvs_log.txt 
	$(CURDIR)/find_created.pl $(WORKDIR)/cvs_log.txt >x

$(QUESTIONSINDEXEDFILE): $(TEXTTIMESTAMP) $(RATINGTIMESTAMP)
	$(MAKE) reindexquestions

reindexquestions:
	$(INSTALLSCRIPTS)/reindex $(INSTALLSCRIPTS)/$(DB_CHGK_NAME).cnf
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
#	perl makepeople.pl
#	perl makeeditors.pl
	perl makeauthors.pl
	touch $(AUTHORSTIMESTAMP)

makerating:
	$(MAKE) $(RATINGTIMESTAMP)
	
$(RATINGTIMESTAMP): dict/rating_map.txt dict/rating_questions.txt $(TEXTTIMESTAMP)
	perl makerating.pl
