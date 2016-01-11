#!/usr/bin/perl -w

=head1 SYNOPSIS

mrating.pl <map_file>

=cut

use dbchgk;
use Data::Dumper;
use Statistics::Basic qw(:all);
use Text::CSV;
use List::MoreUtils qw/ uniq /;
use strict;

sub addRatingsToDb($$$$$);
sub addTourToRatings($$$);
sub getGoodTours( $$$ );

our $dictdir;
do "chgk.cnf";

my $id = shift;

my $date = gettour($id, 'PlayedAt');


my $fileName = "$dictdir/results/$id";
while (glob "$fileName*") {
    my $n = $_;
    $n=~s/.*\///;
    make_tour( $n, $_ );
}



sub get_csv {
    my $filename = shift;
    open my $fh, "<:encoding(utf8)", $filename  or die "$filename: $!";
    my $csv = Text::CSV->new ( { sep_char => ';' } )  # should set binary attribute.
                     or die "Cannot use CSV: ".Text::CSV->error_diag ();
    my @rows = ();
    while ( my $row = $csv->getline( $fh ) ) {
	my @row = @$row;
	my %row = ();
	my @results = @row[3..$#row];
	push @rows, {
	    'team_id' => $row[0],
	    'rating' => $row[2],
	    'results' => \@results
	}
    }
    return \@rows;
}

sub make_tour {
    my $id = shift;
    my $filename = shift;
    my $results = get_csv( $filename );
    my @fields = ('Id', 'PlayedAt');
    my %tour;
    print "A: $id\n";
    ($tour{id}, $tour{date}) = gettour($id, @fields);
    print "B:\n";
    my $tour_rating = get_tour_rating($id, $results, $tour{date});
    my $sql;
    mydo($sql = "UPDATE Tournaments  SET Complexity=$tour_rating WHERE Id='$tour{id}'");
    my %plus;
    my %minus;
    my $team;
    foreach $team( @$results ) {
	my $n = 0;
	foreach my $q( @{$team->{results}} ) {
	    ++$n;
	    if ($q) {
		$plus{$n}++;
	    } else {
		$minus{$n}++;
	    }
	}
    }
    my @keys = sort {$a<=>$b} uniq (keys %plus, keys %minus);
    my %vsego;
    foreach (@keys) {
	$plus{$_}||=0;
	$minus{$_}||=0;
	$vsego{$_} = $plus{$_}+$minus{$_};
        my $nrating{$_} = ($minus{$_}+1)/($vsego{$_}+1)*$tour_rating;
	mydo("UPDATE Questions SET Rating = '".$plus{$_}."/".$vsego{$_}."' WHERE ParentId = $tour{id} && Number = $_");
    }
    

##########################!!!!!!!!!!
}

sub get_tour_rating {
    my $id = shift;
    my $results = shift;
    my $date = shift;
    my $sum = 0;
    my $number = 0;
    
    foreach (@$results ) {
#    print "!".Dumper($_)."?";
        if ( $_->{rating} > 100) {
    	    $sum+=$_->{rating};
    	    $number++;
    	}
    }
    
    my $tourRating = $sum/$number;
    $tourRating=5000 if $date lt "2004";  
    $tourRating=3000 if $tourRating <3000;
    $tourRating+=5000;

}

exit;
__END__

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