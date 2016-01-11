#!/usr/bin/perl -w

=head1 SYNOPSIS

get_rating_tour.pl textId


=cut

use dbchgk;

use Data::Dumper;
use Encode;
use LWP::Simple;
use Text::CSV;
use strict;

our $dictdir;
do "chgk.cnf";

my $id = shift;

my $csv = Text::CSV->new( { binary => 1, eol=>"\r\n", sep_char => ';' } );

die "Usage: get_rating_tour.pl Tournament\n" unless $id;

my $RatingId = gettour($id, 'RatingId');

die "Can not find Rating Id for $id" unless $RatingId;

my %teams = get_teams( $RatingId );

my %results = get_results( $RatingId );
my $n = 0;
$csv->eol("\n");
foreach my $tour(keys %results) {
    my $fileName = "$dictdir/results/$id.$tour";
    if ( -e $fileName ) {
	print "$fileName already exists, skipping\n";
	next;
    }
    open F, ">:encoding(utf8)","$fileName";
    foreach (keys %{$results{$tour}}) {
	$csv->combine($_, $teams{$_}->{name}, $teams{$_}->{rating}, @{$results{$tour}->{$_}});
	my $line = $csv->string();
	print F "$line";
    }
    close F;
}

#print Dumper (\%results);


sub get_results {
    my $RatingId = shift;
    my $lines = get_csv("http://ratingnew.chgk.info/tournaments.php?displaytournament=$RatingId&export_tournament_tour");
    shift (@$lines);
    my %res;
    foreach ( @$lines ) {
	my $tourNumber = $_->[3];
	my $teamId = $_->[0];
	my @results = @$_[4..$#$_];
	$res{$tourNumber}->{$teamId} = \@results;
    }
    return %res;
#    my %res;
#    foreach (@$lines) {
#        $rating{$_->[1]} = $_->[4];
#    }
#    return %rating;
    
}

sub get_teams {
    my $RatingId = shift;
    my $lines = get_csv("http://ratingnew.chgk.info/tournaments.php?displaytournament=$RatingId&export_tournament");
    shift (@$lines);
    my %res;
    foreach (@$lines) {
        $res{$_->[1]} = {'name'=>$_->[2], 'rating'=>$_->[4]};
    }
    return %res;
}

sub get_csv{
    my $url = shift;
    my $content = get( $url );
    my @lines = split(/[\n\r]+/, $content);

    my @rows;
    foreach (@lines) {
	$_ = decode("cp-1251", $_);
        $csv->parse( $_ );
        my @row = $csv->fields();
        push @rows, \@row;
    }
    return \@rows;
}

