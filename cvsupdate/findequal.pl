#!/usr/bin/perl -w

=head1 NAME

findequal.pl - a script for filling the equalto tablee. 

=head1 SYNOPSIS

findequal.pl


=head1 DESCRIPTION

This script will create a table B<equalto>
in the B<chgk> database and fill it with pairs of 
equal questions. If the tables exist, it will ask user whether
new table should be created. 

=head1 AUTHOR

Roman Semizarov

=cut


use DBI;
use locale;
use dbchgk;
use POSIX qw (locale_h);

do "common.pl";

my ($thislocale);
if ($^O =~ /win/i) {
	$thislocale = "Russian_Russia.20866";
} else {
	$thislocale = "ru_RU.KOI8-R";
}
POSIX::setlocale( &POSIX::LC_ALL, $thislocale );
if ((uc 'Á') ne 'á') {die "!Koi8-r locale not installed!\n"};



if ((uc 'Á') ne 'á') {die "!Koi8-r locale not installed!\n"};



#if (checktable('equalto')) {die "The table equalto exists. You must delete it first!\n"};

if ((uc 'Á') ne 'á') {die "!Koi8-r locale not installed!\n"};



print "Creating equalto table...\n";
	mydo("DROP TABLE IF EXISTS equalto");

	mydo("CREATE TABLE equalto (
		First   INT UNSIGNED NOT NULL PRIMARY KEY, KEY FirstKey (First),
		Second  INT UNSIGNED NOT NULL, KEY SecondKey (Second)
	)")

	or die "Can't create equalto table: $!\n";


if ((uc 'Á') ne 'á') {die "!Koi8-r locale not installed!\n"};
print "before getbase";

getbase(QuestionId,Question,Authors,Comments);


print "after getbase";

print "Loading questions...\n";

if ((uc 'Á') ne 'á') {die "!Koi8-r locale not installed!\n"};

while ((($id, $a,$author,$comment) = getrow), $id) 
{
        if (!($id%1000)) {print "$id questions loaded...\n"}

        $a=~s/³£pPHXxAaBEe3KMoOT/åÅÒòîèÈáÁ÷åÅúëíÏïô/;
        $a=uc $a;

 	$a=~s/[^êãõëåîçûıúèÿüöäìïòğá÷ùæñşóíéôøâàÊÃÕËÅÎÇÛİÚÈßÆÙ×ÁĞÒÏÌÄÖÜÑŞÓÍÉÔØÂÀ]//g;
 	$ar[$id]=$a;
 	$dop[$id]= ($author ? 2 : 0) + ($comment ? 1 : 0);
 	$last=$id;
}



print "Checking...\n";

$cur=0;
$ar[0]="\0";
foreach $q (sort {($ar[$a] cmp $ar[$b]) || ($dop[$b]<=>$dop[$a])} 1..$last)
{
  if ($ar[$q] eq $ar[$cur]) {$equal{$q}=$cur} else {$cur=$q} 
}

print scalar keys %equal, " pairs found\n";

print("Updating the DB...\n");

foreach $a (keys %equal)
{
  mydo("INSERT INTO equalto (First,Second) VALUES ($a,$equal{$a})");
}

