#!/bin/bash

SETUP_FOLDER=`readlink -f $(dirname $0)`

if [ ! -n "$CNF" ] 
then 
    if [ -n "$1" ]
    then 
	CNF=$1
    else
	
	if [ -f ./chgk.cnf ] 
	    then
		CNF=./chgk.cnf
	fi
    fi
    if [ ! -n "$CNF" ] 
    then 
	echo I don not know the config file name
	exit;
    fi	
    if [ -f $CNF  ] 
    then 
	source $CNF 
    else 
	echo Can not open $CNF
	exit;
    fi
fi

SUCCESS=1

MYSQL_EXEC_PASS_PART=""

WWW_SOURCE_DIR=$SVN_DIR/db/www

DISTRIB_DIR=$SVN_DIR/db/distrib

if [ -n "$DB_USERPASS" ]
  then 
      MYSQL_EXEC_PASS_PART="-p"$DB_USERPASS
fi

if [ ! -n "$OWNER" ]
  then 
    OWNER=`whoami`:`whoami`
fi

if [ ! -n "$DRUSH_EXECUTABLE" ]
  then 
    DRUSH_EXECUTABLE=drush
fi

DRUSH="$DRUSH_EXECUTABLE --root=$DOCUMENT_ROOT"

if [ -n "$SVN_USERNAME" ]
  then 
      SVN_USERNAME_PART="--username="$SVN_USERNAME
fi

if [ -n "$SVN_USERPASS" ]
  then 
      SVN_USERNAME_PART="--password="$SVN_USERPASS
fi

if [ 'FreeBSD' = `uname` ]
    then
	SED_EXECUTABLE='sed -i ""'
    else 
	SED_EXECUTABLE='sed -i '
fi


if [ ! -d $TEMP_DIR ]
    then
	echo -n Temp folder  does not exist. Creating...
	mkdir $TEMP_DIR
	source $SETUP_FOLDER/check_success "Can not create $TEMP_DIR";
	
	chown $OWNER $TEMP_DIR
fi

if [ ! -d $LOG_DIR ]
    then
	echo -n Log folder  does not exist. Creating...
	mkdir $LOG_DIR
	source $SETUP_FOLDER/check_success "Can not create $DUMP_DIR/drupal";
	chown $OWNER $LOG_DIR
fi

if [ ! -d $DUMP_DIR ]
    then
	echo -n Dump folder  does not exist. Creating...
	mkdir $DUMP_DIR
	source $SETUP_FOLDER/check_success "Can not create $DUMP_DIR";
	chown $OWNER $DUMP_DIR
fi

if [ ! -n "$SPHINX_DIR" ]
then
	SPHINX_DIR=$WORK_DIR/sphinx
fi 


if [ ! -n "$SPHINX_HOST" ]
then
	SPHINX_HOST=localhost
fi 

if [ ! -n "$SPHINX_PORT" ]
then
	SPHINX_PORT=9312
fi 

if [ ! -n "$SPHINX_CONFIG" ]
then
	SPHINX_CONFIG=$SPHINX_DIR/$SPHINX_NAME/sphinx.conf
fi 

if [ ! -n "$SEARCHD_EXECUTABLE" ]
then
    SEARCHD_EXECUTABLE=searchd
fi

if [ ! -n "$SEARCHD_EXECUTABLE_FULL" ]
then
    SEARCHD_EXECUTABLE_FULL="$SEARCHD_EXECUTABLE --config $SPHINX_CONFIG"
fi

if [ ! -n "$INDEXER_EXECUTABLE" ]
then
    INDEXER_EXECUTABLE=indexer
fi

if [ ! -n "$INDEXER_EXECUTABLE_FULL" ]
then
    INDEXER_EXECUTABLE_FULL="$INDEXER_EXECUTABLE --config $SPHINX_CONFIG --all "
fi



MYSQL_EXECUTABLE_FULL="mysql -u$DB_USERNAME $MYSQL_EXEC_PASS_PART -h$DB_HOSTNAME"
MYSQLADMIN_EXECUTABLE_FULL="mysqladmin -u$DB_USERNAME $MYSQL_EXEC_PASS_PART -h$DB_HOSTNAME"
MYSQLDUMP_EXECUTABLE_FULL="mysqldump -u$DB_USERNAME $MYSQL_EXEC_PASS_PART -h$DB_HOSTNAME"
SVN_EXECUTABLE_FULL="svn $SVN_USERNAME_PART $SVN_PASSWORD_PART export --force"

pushd `dirname $0` >/dev/null


popd >/dev/null

