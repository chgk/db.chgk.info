#!/usr/bin/perl
#
# Get statistics for the database in the form
# Date total_questions  distinct_questions
#
use DBI;
use strict;

my $dbname;
my $username;
my $password;
my $host;

$dbname="chgk";
$username="root";
$password="Bycnfkk7";
$host="localhost";



my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday) =
    gmtime(time);
$year += 1900;
$mon ++;
my @names=('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
$wday=$names[$wday];

printf "$wday %04d-%02d-%02d %02d:%02d:%02d GMT ", $year,$mon,$mday,$hour,$min,$sec;

my($dsn) = "DBI:mysql:database=$dbname;host=$host";
my $dbh = DBI->connect($dsn, $username, $password) || die "Cannot connect\n";

my ($sth) = $dbh->prepare("SELECT COUNT(*) FROM Questions");
$sth->execute;
my $total=($sth->fetchrow)[0];
$sth->finish;
$sth= $dbh -> prepare("select distinct count(first) FROM equalto");
$sth -> execute;
my ($equal)=$sth->fetchrow;
$sth -> finish;

print " $total ",$total-$equal, "\n";

$dbh->disconnect;

exit 0;
