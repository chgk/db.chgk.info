package ChgkPeople;

use dbchgk;
use Data::Dumper;
do "chgk.cnf";
use locale;
use POSIX qw (locale_h);

my $DUMPDIR = $ENV{DUMPDIR} || "dump";


sub new {
  my $class = shift;
  my $self = {};
  bless $self, $class;
  $self->initLocale();
  return $self; 
}

sub initLocale() {
  my $self = shift;
  my ($thislocale);
  if ($^O =~ /win/i) {
    $thislocale = "Russian_Russia.20866";
  } else {
    $thislocale = "ru_RU.KOI8-R"; 
  }

  POSIX::setlocale( &POSIX::LC_ALL, $thislocale );
  if ((uc 'а') ne 'А') {die "!Koi8-r locale not installed!\n"};
}

sub openFiles() {
  my $self = shift;
  open UNKNOWN, ">".$self->getUnknownStringsFileName();
#  open UNICKS, ">$DUMPDIR/uenicks";
  open STDERR, ">$DUMPDIR/errors";
}



sub readNicks() {
  $self = shift;

  open NICKS, "<$nicksfile" or die "Can not open nicks";
  while (<NICKS>)
  {
    my ($number, $nick, $add)=split;
    $add||='';
    next unless $number;   
    next unless $number=~/^\d+$/;
    my $s = <NICKS>;
    $s=~s/\((.*)\)//;
    my $city = lc $1;
    $city =~ s/\b(\S)/\u$1/gi;
    my @parts = split ' ',$s;
    $_ = ucfirst lc $_ foreach  @parts;
    my $surname = pop @parts;
    $surname=~s/\-(.)/"-". uc $1/ge;
    $surname=~s/\'(.)/"'". uc $1/ge;
    $self->{people}->{$nick} =   { 
      'nick'=>$nick, surname=>$surname, name=> (join ' ', @parts), ratingId=>$number, certified_editor=> ($add=~/E/?1:0),
      certified_referee => ($add=~/R/?1:0), city=>$city
    };
    if ( $number != 1111111111 ) {
      if ( $self->{rat2nick}->{$number} ) {
        print STDERR "Duplicated id: $number\n";
      } else {
        $self->{rat2nick}->{$number} = $nick
      }
    }
  }
  close NICKS;
  $self->{people}->{'error'} = {'surname'=>'Глюков', 'name'=>'Очепят', 'nick'=>'error'};
  $self->{people}->{'unknown'} = {'surname'=>'Неизвестный', 'name'=>'Псевдоним', 'nick'=>'unknown'};
  $self->{people}->{'team'} = {'surname'=>'Авторов', 'name'=>'Коллектив', 'nick'=>'team'};

}

sub readSSNicks() {
  $self = shift;
  open SSNICKS, "<$ssnicksfile" or die "Can not open ssnicks";
  while (<SSNICKS>)
  {
    $str=$_;
    ($number,$n)=split ' ',$str;
    if ($number=~/\d+/) {$nick=$n;next}
    $str=~s/^\s+//;
    $str=~s/\s+$//;   
    $str=~s/\s+/ /;
    $self->{people}->{$nick}->{ssnick}.="|$str";
  }
  close SSNICKS;
}

sub getStringsFileName() {
  return $editorsfile;
}

sub readStrings() {
  my $self = shift;
  my $strings = shift;
  open STRINGS,"<$strings" or die "Can not open editors";
  
  while (<STRINGS>)
  {
    my ($nick,$number,$descr)=m/^([a-zA-Z][a-zA-Z\s]+)(\d+)\s+(.*)$/g;
    if (!$nick) 
    {
      ($number,$descr)=m/^(\d+)\s+(.*)$/g;
      $nick='unknown';
    }
    $descr=~s/([\.\,\:\!\?])/$1 /g;
    $descr=~s/\\n/ /g;
    $descr=~s/^\s+//g;
    $descr=~s/\s+$//g;
    $descr=~s/\s+/ /g;
    $descr=uc $descr;
    $self->{string2nicks}->{$descr} = $nick;
    foreach (split ' ', $nick)
    {
      $unknick{$_}=1  unless $self->{people}->{$_}
    }
  }
  open UNICKS, ">".$self->getUnknownNicksFileName() or die "Can not open ".$self->getUnknownNicksFileName();
  foreach my $as(keys %unknick)
  {
    print UNICKS "$as \n ", (join "\n ", (grep {$self->{string2nicks}->{$_}=~/$as/} keys %{$self->{string2nicks}}) );
    print UNICKS "\n";
  }
  close UNICKS;
}

sub getUnknownNicksFileName() {
  return "$DUMPDIR/uenicks";
}


sub readEntities() {
  $self = shift;
  getalltours('Id','Editors', 'ParentId', 'Type', 'TextId');

  while (($TournamentId, $editor, $parent, $type, $tid )=getrow,$TournamentId) {
    $editor||='';
    next if  !$type || $type eq 'Г';
    $self->{entities}->{$TournamentId} = {
      string => $editor, 
      tid => $tid,
      parent => $parent,
      type => $type,
    };
    push @{$self->{entities}->{$parent}->{children}}, $TournamentId;
  }

  foreach $t( keys %{$self->{entities}} ) {
    my %tour = %{$self->{entities}->{$t}};
    next if  !$tour{type} || $tour{type} eq 'Г';
    if (
      (exists $tour{'children'}) && 
      ($tour{'type'} eq 'Ч')
    ) {
      $childrenSameAuthor = 1;
      foreach (@{$tour{children}}) {        
        if ($self->{entities}->{$_} -> {string} ne $tour{string} ) {
          $childrenSameAuthor = 0;
        } else {
          $self->{entities}->{$_} -> {string} = '';
        }
      }
    }
  }
}


sub adjustString() {
  my $self = shift;
  my $string = shift;
  $string=~s/([\.\,\:\!\?])/$1 /gm;
  $string=~s/^\s+//mg;
  $string=~s/\\n/ /g;
  $string=~s/\s+$//mg;
  $string=~s/\s+/ /mg;
  $string=uc $string;
}

sub getStringFromId() {
  my $self = shift;
  my $id = shift;
  $self->{entities}->{$id}->{string};
}


sub updateDatabase {
  print scalar keys %{$self->{string2nicks} } , " editors found\n";
  addtours2author( $self->{ent}->{$_}, $self->{people}->{$_}, scalar keys %{$self->{tournaments}->{$_} } ) foreach keys %{$self->{ent}};

}

sub getUnknownStringsFileName() {
  return "$DUMPDIR/ueditors";
}
sub process {
  my $self = shift;  

#  $self->openFiles();
  open STDERR, ">$DUMPDIR/errors";

  $self->readNicks();

  $self->readSSNicks();

  $self->readStrings( $self->getStringsFileName() );

  $self->readEntities();

  $self->{ent} = {};
  
  foreach  my $id(keys %{$self->{entities}} )
  {
    my $string;
    next unless $string = $self->getStringFromId( $id ) ;
    
    $string = $self->adjustString( $string );
    if ( ($nick = $self->{string2nicks}->{$string}) ) {
      @nicks = split ' ',$nick;
      @nicks = grep { $self->{people}->{$_} } @nicks;
      foreach ( @nicks) {
        next if $_ eq 'error' || $_ eq 'unknown' || $_ eq 'team';
        push @{$self->{ent}->{$_}}, $id;
        if ( $self->{entities}->{$id}->{type} eq 'Ч' ) {
          $self->{tournaments}->{$_}->{$id} = 1;
        } else {
          $self->{tournaments}->{$_}->{ $self->{entities}->{$id}->{parent}} = 1;
        }
      }
      

    } else {
      $unknown{$string}=1;
   }
  }
  $self -> updateDatabase();
  open UNKNOWN, ">".$self->getUnknownStringsFileName();
  print UNKNOWN "$_\n" foreach sort keys %unknown;

}

sub readPatch {
  my $self = shift;
  my $file = shift;
  open P, $file or die "Can not oprn file";
  while (<P>) {
    my ($old, $nick, $new) = split /;/;
    if ( !$old || !$new || $old == $new || $self->{rat2nick}->{$new} ) {
      print STDERR "?".$_;
    } else { 
      $self->{patchedRating}->{$nick} = $new;
    }
    
  }
}

sub patch {
  my $self = shift;
  my $file = shift;
  
  $self->readNicks( );
  $self->readPatch( $file );
  open NICKS, "<$nicksfile" or die "Can not open nicks";
  while (<NICKS>)
  {
    my ($number, $nick, $add)=split;
    my $r;
    if ( $number && ($r = $self->{patchedRating}->{$nick}) ) {
      s/$number/$r/;
    }
    $output.=$_;
    next unless $number;   
    next unless $number=~/^\d+$/;
    my $s = <NICKS>;
    $output.=$s;
  }
  close NICKS;
  return $output;

}
1;
