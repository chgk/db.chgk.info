package ChgkAuthors;
use ChgkPeople;
use dbchgk;
use Data::Dumper;

@ISA=(ChgkPeople);
my $DUMPDIR = $ENV{DUMPDIR} || "dump";
sub updateDatabase {
  $self=shift;
  print scalar keys %{$self->{string2nicks} } , " editors found\n";
#print STDERR Dumper($self->{ent});
#print STDERR "\n----------------\n";  
#print STDERR Dumper($self->{people});  
#  (print STDERR "\nXXX".$_.Dumper($self->{ent}->{$_}).Dumper($self->{people}->{$_})."YYY\n"), 
  addquestions2author($self->{ent}->{$_}, $self->{people}->{$_} ) foreach keys %{$self->{ent}};
}

sub getUnknownNicksFileName() {
  return "$DUMPDIR/unicks";
}

sub getUnknownStringsFileName() {
  return "$DUMPDIR/uauthors";
}


sub readEntities() {
  $self = shift;
  my $i;
  my $j;
  
  getbase('QuestionId','Authors');
  while ( ($QuestionId, $author)=getrow, $QuestionId )
  {
    print STDERR "$QuestionId\n";
    $j++;
    next unless $author;
    print "." unless $i++ % 100;
    $self->{ entities }->{ $QuestionId } = {
      'string'=> $author
    }
  }
  print "\n$i\n$j\n";
}



1;