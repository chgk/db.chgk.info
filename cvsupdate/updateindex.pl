#!/usr/bin/perl -w

=head1 NAME

updateindex.pl - a script for creation of new database. 

=head1 SYNOPSIS

updateind.pl [B<-i> I<indexfile>] [B<-y>|B<-n>] [B<-r>]


=head1 DESCRIPTION

Upadets metainformation in the B<chgk> databse. 

An example of the index file follows:

		 Авторские вопросы
			 Виктор Байрак
 bayrak.txt  			Вопросы В.Байрака
			 Борис Бурда
 burda.txt   			Вопросы Бориса Бурды
 burda1.txt  			Тренировки Бориса Бурды 1
 burda10.txt 			Тренировки Бориса Бурды 10
 burda11.txt 			Тренировки Бориса Бурды 11
 burda12.txt 			Тренировки Бориса Бурды 12


=head1 OPTIONS

=over 4

=item B<-i> I<indexfile>

The index file to read (Standard input by default)

=item B<-y>

Answer 'yes' to all questions

=item B<-n>

Answer 'no' to all questions

=item B<-r>

Remove all entries with zero QuestionNum

=head1 BUGS

The database, user and password are hardcoded. 

=head1 SEE ALSO

createindex.pl(1)

=head1 AUTHOR

Boris Veytsman

=head1 $Id: updateindex.pl,v 1.12 2008-11-14 11:23:03 roma7 Exp $

=cut

    use strict;
use vars qw($opt_i $opt_h $opt_y $opt_n $opt_r);

use Getopt::Std;
use DBI;
use dbchgk;

MAIN: 
{
    my $USAGE="Usage: updateindex.pl [-i indexfile] [-y|-n][-r]\n";
    my $REMOVE=0;
    getopts('hi:ynr') or die $USAGE;
    if ($opt_h) {
	print $USAGE;
	exit 0;
    }
    my $decision='askuser';
    if ($opt_y) {
	$decision = 'yes';
    } 
    if ($opt_n ) {
	$decision = 'no';
    }
    if ($opt_r) {
	$REMOVE=1;
    }
    my($source) = $opt_i;
    my $champ;
    my($depth, @depthId, @depthTextId);
    my $filename;
    if ($source) {
	open INFO, $source or die "Can't open input file: $!\n";
    } else {
	*INFO=*STDIN;
    }

    while (<INFO>) {
	chomp;
	s///;
	next if (/^\s*$/);
	if (s/^(\S+\.txt) *//) { # File found
	    $filename = $1;
	    $depth = -1;
	    $champ=1;
	} else {  # Group found
	    if (s/^(\S+)//)
	    	{ $filename = $1;}	
	    else 
		{$filename = ''}
	    $depth = -2;
	    $champ=0;
	}
	s/^(\t*)//;
	$depth += length($1);
	if ($depth < 0) {
	    die "Wrong line $_\n";
	}
	s/^\s*//;
	s/\s$//;
	my $title = $_;
	my $ParentId = ($depth) ? $depthId[$depth - 1] : 0;
	my $parentTextId = ($depth) ? $depthTextId[$depth - 1] : 0;
	my ($Id, $TextId) = CheckId( $title,$ParentId,$decision,$filename, $parentTextId );
	if (!$Id  || $champ) {
	    next;
	}
	$depthId[$depth] = $Id;
	$depthTextId[$depth] = $parentTextId;

    }
    print STDERR "Всего вопросов: ",
    UpdateGroup(0),"\n";
    if ($REMOVE) {
	print STDERR "Removing empty tours.";
	db()->do("DELETE FROM Tournaments WHERE QuestionsNum=0");
    }
}

sub makeTextId {
    my ($fileName, $number) = @_;
    $fileName=~s/\.txt//;
    if ($number) {
	return "$fileName.$number";
    } else {
	return $fileName;
    }
     
}

sub CheckId {
    my ( $title,$ParentId,$answer,$filename, $parentTextId ) = @_;
    my $type;
    my $key;
    my $value;
    my $Id = 0;
    my $textId = makeTextId($filename);
    print "$filename $textId\n";
    if ($filename && $filename=~/\.txt/) {
	$type=db()->quote('Ч');
    }	else {$type=db()->quote('Г');}
    if ($filename)
    {
    	$key = "FileName";
    	$value = db()->quote($filename);
    } else {
    	$key = "Title";
    	$value = db()->quote($title);
    }

    $title=db()->quote($title);    
    my $sth = db()->prepare("SELECT Id  FROM Tournaments 
                             WHERE $key=$value");
    $sth->execute or die db()->errstr;
    my @arr = $sth->fetchrow;

    ($Id)  = @arr;
    my $quotedTextId = db()->quote($textId);
    my $quotedFileName = db()->quote($filename);
    if ($Id) {
	$sth = db()->prepare("UPDATE Tournaments
                             SET Title=$title, ParentId=$ParentId, 
                             Type=$type, TextId=$quotedTextId, FileName = $quotedFileName
                             WHERE Id=$Id");

    } else {
	$sth = db()->prepare("INSERT INTO Tournaments
                             (Title, ParentId, Type,CreatedAt, LastUpdated, TextId, FileName) 
                             VALUES
                             ($title, $ParentId,$type,NOW(), NOW(), $quotedTextId, $quotedFileName)");
    }
    $sth->execute or die db()->errstr;
    if (!$Id) {
	$Id = $sth->{'mysql_insertid'};
    }
    return $Id;
}

sub UpdateGroup {
    my ( $Id ) = @_;
    my $sth = db()->prepare("SELECT COUNT(*) FROM Questions
			     WHERE ParentId=$Id");
    $sth->execute;
    my @arr=$sth->fetchrow;
    my $result=$arr[0];
    my @Tours = GetTours($Id);
    foreach my $TourId (@Tours) {
	$result += UpdateGroup($TourId);
    }
    $sth=db()->prepare("UPDATE Tournaments SET
                   QuestionsNum=$result 
                   WHERE Id=$Id");
    $sth->execute;
    return $result;
}

sub GetTours {
	my ( $ParentId) = @_;
	my (@arr, @Tours);

	my ($sth) = db()->prepare("SELECT Id FROM Tournaments
		WHERE ParentId=$ParentId ORDER BY Id");

	$sth->execute;

	while (@arr = $sth->fetchrow) {
		push @Tours, $arr[0];
	}

	return @Tours;
}

