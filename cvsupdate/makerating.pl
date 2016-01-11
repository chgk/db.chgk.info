#!/usr/bin/perl -w

=head1 SYNOPSIS

makerating.pl <map_file> <results_file>

=head1 Description

<map_file>  is file, with mapping between tournament text id and rating id. Each line is space separated pair

<results_file> --  is result of the following request to rating database.

SELECT 
    chgk_tournament.idtournament,chgk_tournament_tours.tour, chgk_tournament.tour_questions,
    chgk_team.idteam, IF(`team_tech_rating`, `team_tech_rating_value`, rd.rating ) as rating, chgk_tournament_tours.`mask` 
FROM chgk_tournament_tours
    INNER JOIN chgk_tournament ON chgk_tournament.idtournament = chgk_tournament_tours.idtournament 
    INNER JOIN chgk_team ON chgk_team.idteam = chgk_tournament_tours.idteam
    INNER JOIN chgk_tournament_positions p ON (p.idtournament = chgk_tournament.idtournament AND chgk_team.idteam =p.idteam)

    INNER JOIN chgk_release c ON c.idrelease = (SELECT c1.idrelease FROM chgk_release c1 WHERE c1.`date` < chgk_tournament.date_end ORDER BY c1.`date` DESC LIMIT 1) 
    LEFT JOIN chgk_release_data rd ON rd.idrelease=c.idrelease AND rd.idteam=chgk_team.idteam
=cut

use dbchgk;
use Data::Dumper;
use Statistics::Basic qw(:all);
use strict;

sub addRatingsToDb($$$$$);
sub addTourToRatings($$$);
sub getGoodTours( $$$ );

my $sql;

mydo($sql = "UPDATE LOW_PRIORITY Questions SET Rating = NULL, RatingNumber= NULL, Complexity=NULL");
print $sql."\n";
open (F, shift||'dict/rating_map.txt');
my $g = shift||'dict/rating_questions.txt';
open G, $g or die "CAn not open $g";
my %badId;
#1885

$badId{$_} = 1 foreach qw/1735
1646
2056
1743
1737
1764
1725
1709
1745
1744
1715
1885
1774
1710
1647/;

my %forced = (
  'ovsch11'=>1,
  'vmog11'=>1,
  'blin11'=>1,
  'ruscup10'=>1
);

my %cutzero = (
  'vmog11'=>1,
  'ruscup10'=>1
);

my %qnumber = (
#  2025=>15
);


my %ids;
while (<F>) {
    my ($textId, $ratingId) = split;
#    next unless $textId eq 'zamkad12';
#    next  if $textId ne "rubik02";
    mydo($sql = "UPDATE Tournaments SET RatingId='$ratingId' WHERE textId='$textId'");
    if ( $ids{ $ratingId } ) {
      if ( ! (ref $ids{ $ratingId }) ) {
        $ids{$ratingId}=[$ids{$ratingId}];
      } 
      push @{$ids{ $ratingId }}, $textId;
    } else {
      $ids{$ratingId}=$textId;
    }
        
}
my %tours;
my %ratingSum = ();
my %ratingNumber = ();
while (<G>) {
    next unless /^\d/;

    my ($tournamentId, $tour, $qnum, $teamId, $rating, $mask)=split;
#    next unless $tournamentId==2069;
    my @q = split '', sprintf("%0${qnum}s", (decbin($mask)));
    @q=@q[0..$qnumber{$tournamentId}-1] if $qnumber{$tournamentId};

    if ( $rating ne 'NULL' && $rating>100) {
      $ratingSum{$tournamentId}+=$rating;
      $ratingNumber{$tournamentId}++;
    }
    $tours{$tournamentId}->{$teamId}->[$tour]= \@q;
}

my @allratings = ();
my @rvalues = ();

TOUR: foreach my $tournament (keys %tours){
  next if ref $ids{$tournament};
  if (!$ids{$tournament} || $badId{$tournament}) {
    next;
  }
  my %rating = ();
  print STDERR $tournament."\n";
  print STDERR $ids{$tournament}."\n";
  my ($tournamentId, $textId, $title, $qnum, $date) = gettour($ids{$tournament}, 'Id', 'TextId', 'Title', 'QuestionsNum', 'PlayedAt');
  if (!$tournamentId) {
    print STDERR "ERROR: no tournament ".$ids{$tournament}."\n";
  }
  
  $cutzero{$tournamentId} = $cutzero{$textId} if ($cutzero{$textId});
  my $tourRating = int ($ratingSum{$tournament}/$ratingNumber{$tournament});
  $tourRating=5000 if $date lt "2004";  
#  $tourRating=3000 if $tourRating <3000;
  $tourRating+=5000;
  mydo($sql = "UPDATE Tournaments  SET Complexity=$tourRating WHERE Id=$tournamentId");
  
  foreach my $teamId(keys %{$tours{$tournament}}) {
    my @tours = @{$tours{$tournament}->{$teamId}}[1..$#{$tours{$tournament}->{$teamId}}];
    if ( $forced{$textId} ) {
      my $kvo;
      foreach my $t(@tours) {
        $kvo = @$t if ref $t;
      }
      foreach my $t(@tours) {
        if ( ! ref $t ) {
          $t=[];
          for (1..$kvo) {
            push @$t, undef;
          }
        }
      }
    }
    my @goodTours = getGoodTours( $tournamentId, \@tours, $forced{$textId});
    if ( !@goodTours ) {
      print STDERR "Problems with $tournament -- $textId\n";
      next TOUR;
    }
    my @q = ();
    my $i = 0;
    foreach (@tours) {
      if ( !$forced{$textId} ) {
     
        my $kvo = $goodTours[$i]->[2]-($cutzero{$textId}||0);
        push @q, @$_[0..$kvo-1]; 
        $i++
      } else {
      
        push @q, @$_;
      }
    }
    my $n = 0;
    my $first = 0;
    foreach (@goodTours) {
      $cutzero{$_->[3]} = $cutzero{$textId} if ($cutzero{$textId});
      my $kvo = $_->[2]-($cutzero{$textId}||0);
      addTourToRatings( $_->[3],  [@q[$first..$first+$kvo-1]],  \%rating);
      $first += $kvo;
    }
  }
  print STDERR "Error2 with $textId\n" if (!addRatingsToDb( $tourRating, \%rating, \@allratings, \@rvalues, $cutzero{$textId} ) );
}
#print join "\n", sort {$a<=>$b} @rvalues;
my $median = median(@rvalues);
my $stddev   = stddev(@rvalues );

foreach (@allratings) {
    $_->{'norm_rating'} = ($_->{'rating'}-$median)/$stddev;
}

@allratings = sort {$a->{'norm_rating'} <=> $b->{'norm_rating'} } @allratings;

my $min = $allratings[0]->{'norm_rating'};
my $max = $allratings[$#allratings]->{'norm_rating'};

$_->{'norm_rating'} = ($_->{'norm_rating'}-$min)*10/($max-$min) foreach (@allratings);

my $comp_cats = 5;

my $count = @allratings;
my $i=0;
my $divider = int($count/$comp_cats)+1;


my $divider2 = 10/$comp_cats+0.01;
mydo($sql = "UPDATE Questions  SET Rating='". $_->{'text_rating'}. "', RatingNumber = ". $_->{'norm_rating'} .",  "
#."Complexity = ".$_->{'norm_rating'} ." div $divider2 "
."Complexity = ". ($i++) ." div $divider + 1 "
." WHERE ParentId = ".$_->{'tour_id'}." AND Number = "
.($_->{'number'}+($cutzero{$_->{tour_id}}||0))
), print "$sql\n" foreach @allratings;


  
sub decbin {
    my $str = unpack("B32", pack("N", shift));
    $str =~ s/^0+//; # В противном случае появятся начальные нули
    return $str;
}


# tourId - Id of Tour in Tournaments table
# questions --> array ref to results, e.g [0,1,0,0,1,1...]
# rating hash reference: $rating->{$tourId}->{QuestionNumber} = {'plusov'=>..., 'minusov'=>...}
sub addTourToRatings($$$) {
  my ($tourId,  $questions, $rating) = @_;
  for my $i( 1..@$questions ) {
    next if !defined($questions->[$i-1]);
    if ($questions->[$i-1] == 1) {
      $rating->{$tourId}->{$i}{'plus'}++;
    } elsif ($questions->[$i-1] == 0) {
      $rating->{$tourId}->{$i}{'minus'}++;
    }
  }
}

# $tourRating -- average rating of participantes
# $rating -- hash reference: $rating->{$tourId}->{QuestionNumber} = {'plusov'=>..., 'minusov'=>...}
sub addRatingsToDb($$$$$) {
  my $tourRating = shift;
  my $rating = shift;
  my $allratings = shift;
  my $rvalues = shift;
  my $cutzero = shift||0;
  my $good = 0;

  foreach my $t(keys %$rating) {
    my $p = -1;
    foreach ( sort {$a<=>$b} keys %{$rating->{$t}} ) {
      $rating->{$t}->{$_}->{'minus'}||=0;
      $rating->{$t}->{$_}->{'plus'}||=0;
      my $minusov = ($rating->{$t}->{$_}->{'minus'});
      my $plusov = ($rating->{$t}->{$_}->{'plus'});
      $good = 1 if ($plusov<$p);
#      print STDERR $plusov."!".$good."?\n";
      $p = $plusov;

      my $vsego = $minusov + $plusov;
      if ($plusov ==0 ) {
          mydo("UPDATE Questions SET Rating = '$plusov/$vsego' WHERE ParentId = $t && Number = $_+$cutzero");
          next;
      }

      my $questionRating = ($minusov+1)/($vsego+1);
      my $r = $rating->{$t}->{$_}->{'rating'} = $tourRating * $questionRating;
#      print "!$tournament!$t!$_!$r $tourRating * $questionRating; $minusov $plusov $vsego\n";
      push @$allratings, {'tour_id'=>$t, 'number'=>$_, 'rating'=>$r, 'text_rating'=>$plusov.'/'.$vsego, 'grob'=>($plusov==0) };
      push @$rvalues, $r;
#      mydo("UPDATE Questions SET Rating = $r WHERE ParentId = $t && Number = $_+$cutzero");
    }
  }
  return $good;
}

sub getGoodTours( $$$ ) {
    my ($tournamentId, $tours, $force) = @_;
    $z = db()->prepare("SELECT TextId, Number, QuestionsNum, Id FROM Tournaments WHERE ParentId=$tournamentId");
    $z -> execute();
    my $tid = 0;
    my @goodTours=();
    while (my @a = $z->fetchrow) {
      
      next unless $tours->[$tid];

      if ( (@{$tours->[$tid]} >= $a[2]-($cutzero{$tournamentId}||0)) && ($a[2]>3) || $force) {
        $tid++;
        push  @goodTours, \@a;
      }
    }
    return () if ($tid!=@$tours && !$force);
    return @goodTours;
}