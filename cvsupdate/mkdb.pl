#!/usr/bin/perl -w

=head1 NAME

mkdb.pl - a script for creation of new database. 

=head1 SYNOPSIS

mkdb.pl


=head1 DESCRIPTION

This script will create tables Questions and Tournaments
in the B<chgk> databse. If the tables exist, it will ask user whether
new tables should be created.

=head1 BUGS

The database, user and password are hardcoded. 

=head1 AUTHOR

Dmitry Rubinstein

=head1 $Id: mkdb.pl,v 1.24 2009-12-12 16:55:21 roma7 Exp $

=cut


use DBI;
use strict;
use dbchgk;
use Data::Dumper;
my (@tbl_list, $dbh);
checktable('Questions', 'delete');
createquestionstable();
checktable('Tournaments', 'delete');
createtournamentstable();

