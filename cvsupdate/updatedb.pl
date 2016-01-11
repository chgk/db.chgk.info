#!/usr/bin/perl 

=head1 NAME

updatedb.pl - a script for creation of new database. 

=head1 SYNOPSIS

updatedb.pl B<[-i]> I<file1> I<file2>....


=head1 DESCRIPTION

Updates information in the B<chgk> databse. Uses file

=head1 OPTIONS

=item B<-i> 

Ask about ParentId. 


=head1 BUGS

The database, user and password are hardcoded. 

=head1 AUTHOR

Dmitry Rubinstein

=head1 $Id: updatedb.pl,v 1.48 2010-04-24 18:13:03 roma7 Exp $

=cut

use vars qw($opt_i, $opt_n);

use Getopt::Std;

use dbchgk;
getopts('in');
#open STDERR, ">errors";
my $Interactive=$opt_i || 0;
my $newOnly = $opt_n ||0;
my $DUMPDIR = $ENV{DUMPDIR} || "../dump";
my $unsortedname="$DUMPDIR/unsorted";
my %tourTextIds=();
my $UNSORTED_PARENT=4294967295;

my (%RevMonths) = 
    ('Jan', '1', 'Feb', '2', 'Mar', '3', 'Apr', '4', 'May', '5', 'Jun', '6',
     'Jul', '7', 'Aug', '8', 'Sep', '9', 'Oct', '10', 'Nov', '11',
     'Dec', '12', 
     'JAN', '1', 'FEB', '2', 'MAR', '3', 'APR', '4', 'MAY', '5', 'JUN', '6',
     'JUL', '7', 'AUG', '8', 'SEP', '9', 'OCT', '10', 'NOV', '11',
     'DEC', '12', 
     'Янв', '1', 'Фев', '2', 'Мар', '3', 'Апр', '4', 'Май', '5',
     'Июн', '6', 'Июл', '7', 'Авг', '8', 'Сен', '9', 
     'Окт', '10', 'Ноя', '11', 'Дек', '12');
my ($sth);





use DBI;
use strict;
use Data::Dumper;
my $isunsorted=0;

sub UpdateParents {
    my ($ParentId, $all_qnum,$CreatedAt) = @_;
    if ($ParentId) {
	my $sql;
	my ($sth1) = db()->prepare($sql="SELECT QuestionsNum, ParentId, CreatedAt
FROM Tournaments WHERE Id = $ParentId");
	$sth1->execute;
	my ($q, $p,$c) = ($sth1->fetchrow)[0, 1, 2];
	$c=$CreatedAt if $CreatedAt && ($CreatedAt gt $c);
	my $qc=db()->quote($c);
	db()->do("UPDATE Tournaments SET 
                  QuestionsNum=$q + $all_qnum, CreatedAt=$qc, LastUpdated=NOW()
                  WHERE Id = $ParentId");
	&UpdateParents($p, $all_qnum,$c);
    }
}

sub parseDate {
  my $value = shift;
  my ($from, $to) = split /\s+\-+\s+/, $value;
  $from =~ s/^(.*)-(.*)-(.*)$/$3-$2-$1/;
  my($month) = $RevMonths{$2} || '01';
  $from =~ s/-(.*)-/-$month-/;
  $from =~ s/-00*$/-01/;
  if ($to) {
    $to =~ s/^(.*)-(.*)-(.*)$/$3-$2-$1/;
    $month = $RevMonths{$2} || '01';    
    $to =~ s/-(.*)-/-$month-/;
    $to =~ s/-00*$/-01/;
  }
  return ($from, $to);
  
}

sub getField {
    my($desc) = @_;
    my($key);
    my($value) = ('');
    while (<$desc>) {
	s/[
]//g;
	if ($key && /^\s*$/) {
	    chomp $value;
            $value =~ s/\s+$//;
	    chomp $key;
	    if ($key eq 'Дата') {
	      my ($from, $to) = parseDate($value);
	      $value = {'PlayedAt'=>$from, 'PlayedAt2'=>$to};
	    }
	    if ($key eq 'Автор') {$value=~s/\.$//;}
	    return ($key, $value);
	}
	next if (/^\s*$/);
	
	if (!$key && /^(.*?)[:\.]\s*(.*)$/s) {
	    $key = $1;
	    $value=$2;
	    next;
	}
	if ($key) {
	    $value .= $_."\n";
	    next;
	}
    }
    if ($key && $value) {
        $value=~s/\s+$//sm;
	      return ($key, $value);
    }
    return (0, 0);
}

sub SelectGroup {
    my ( $source, $TourName) = @_;
    my ($sth, $ParentId, $i, @arr);

    $ParentId = $UNSORTED_PARENT;
    my $tempsource=$source;
    my $temptname=$TourName;
    $tempsource=~s/^\'(.*)\'$/$1/;
    $temptname=~s/^\'(.*)\'$/$1/;
    print UNSORTED "$tempsource".((12 -length($source))x' ')."\t$temptname\n";
    $isunsorted=1;
    my $textId = makeTourTextId( $source );
    $sth = db()->prepare("INSERT INTO Tournaments
		      (Title, Type, ParentId, FileName,CreatedAt, LastUpdated, TextId) 
			       VALUES (".db()->quote($TourName).", 'Ч', $ParentId, 
                                       ?,NOW(), NOW(), ? )");
    $sth->execute($source, $textId);
    my $TournamentId = $sth->{mysql_insertid};
    return ($TournamentId,$ParentId);
    
}

sub makeTourTextId {
    my ($fileName, $number) = @_;
    $fileName=~s/\.txt//;
    if ($number) {
	return "$fileName.$number";
    } else {
	return $fileName;
    }
     
}


sub UpdateTournament {
    my ( $TournamentId, $field, $value) = @_;
    if (ref $value eq 'HASH') {
	    # It means that $value contains information about several fields
	    foreach my $k(keys %$value) {
	      if ($value->{$k}) {
          &UpdateTournament( $TournamentId, $k, $value->{$k});
        }
      }
    } else {    
      my $v = db()->quote($value);
      db()->do("UPDATE Tournaments SET $field=$v, LastUpdated=NOW() WHERE Id=$TournamentId")
	    or die db()->errstr;
    }
}

sub UpdateQuestion {
    my ( $QuestionId, $field, $value) = @_;
    
    if (($field eq 'Type') && ($value eq "Д")) {
         $value = "ЧД";
    }
    my $add = '';
    if ($field eq 'Type' ) {
      my $numField=$value;
      $numField = 'Ч' if $numField eq 'ЧБ';
      $numField =~s/[^ЧБИЛЯЭ]//g;
      $numField=~tr/ЧБИЛЯЭ/123456/;
      $numField=sprintf("%d", $numField);
      $add = ", TypeNum = $numField";
    }
    my $v = db()->quote($value);
    db()->do("UPDATE Questions SET $field=$v $add
		WHERE QuestionId=$QuestionId")
	or die db()->errstr;
}

sub CheckFile {
    my ( $source, $title) = @_;
    my $sth = db()->prepare("SELECT Id,ParentId,QuestionsNum, TextId FROM Tournaments
                             WHERE FileName=? AND Type='Ч'");
    $sth->execute($source) or die db()->errstr;
    my @arr = $sth->fetchrow;
    if (! scalar @arr) {
	return SelectGroup( $source,$title);
    }
    my($Id,$ParentId,$QuestionsNum, $TextId)=@arr;
    $tourTextIds{$Id} = $TextId;
    if($QuestionsNum) {	
        if ($newOnly) {
	  return (0,0);
	}
	DeleteTournament( $Id,$ParentId,$QuestionsNum,0);
    } 
    return($Id,$ParentId);	
}


sub DeleteTournament {
    my ( $Id,$ParentId,$QuestionsNum,$DeleteMyself) = @_;
    if ($QuestionsNum) {
	UpdateParents($ParentId,-$QuestionsNum);
    }
    my (@Tours) = &GetTours( $Id);
    foreach my $Tour (@Tours) {
	DeleteTournament( $Tour,$Id,0,1);
    }
    my $sth = db()->prepare("DELETE FROM Questions
                             WHERE ParentId=$Id");
    $sth->execute or die db()->errstr;
    if($DeleteMyself) {
	$sth = db()->prepare("DELETE FROM Tournaments
                             WHERE Id=$Id");
	$sth->execute or die db()->errstr;
    }
}

sub GetTours {
	my ( $ParentId) = @_;
	my (@arr, @Tours);

	my ($sth) = db()->prepare("SELECT Id, Number FROM Tournaments
		WHERE ParentId=$ParentId ORDER BY Id");
	$sth->execute;

	while (@arr = $sth->fetchrow) {
		push @Tours, $arr[0];
	}

	return @Tours;
}

sub CreateTour {
    my ( $title,$ParentId,$TourNum,$rh_defaults, $source)=@_;   
    my $sql;
    my $textId = makeTourTextId( $source, $TourNum );
    my $sth = db()->prepare("INSERT INTO Tournaments  (Title, Type, ParentId, Number,CreatedAt, LastUpdated, TextId) 
			     VALUES (?, 'Т', $ParentId, $TourNum,NOW(), NOW(), ? )");
    $sth->execute($title, $textId );
    my $TourId = $sth->{mysql_insertid};
    while (my ($key,$value)=each %$rh_defaults) {
	    &UpdateTournament($TourId, $key, $value);
    }
    return ($TourId, $textId);
}
		

MAIN: 
{
    my($key, $value, $addition);
    #
    # Inherited fields for a Tour or Tournament
    #
    my %TourFields = ('Копирайт' => 'Copyright',
		      'Инфо' => 'Info', 'URL' => 'URL',
		      'Ссылка' => 'URL', 'Редактор' => 'Editors',
		      'Обработан'=> 'EnteredBy',
		      'Дата'=>'PlayedAt', 'PlayedAt2'=>'PlayedAt2');
    #
    # Inherited fields for a Question
    #
    my %QuestionFields = ('Тип'=> 'Type', 'Вид'=> 'Type', 
			  'Автор' => 'Authors', 'Рейтинг'=>'Rating', 
			  'Источник' => 'Sources',
			  'Тема' => 'Topic');
			  
		      
    my($source);
    my @sources;	
    open UNSORTED, ">$unsortedname";
    while ($source = shift) {
       push @sources,glob($source);
    }
    
    my $textId;
    
    foreach $source(@sources) {
	my $TourNum=0;
	my($PlayedAt) = '';
	my($QuestionId, $TourId, $TournamentId, $ParentId) = (0, 0, 0, 0);
	my($tournum, $qnum, $all_qnum) = (0, 0, 0);
	my (@d) = (localtime((stat($source))[9]))[5,4,3];
	$d[1]++;
	$d[1]=sprintf("%02d",$d[1]);
	$d[2]=sprintf("%02d",$d[2]);
	$d[0]+=1900;
	my $UnquotedCreated=join('-', @d);
	my ($CreatedAt) = $UnquotedCreated;
	my @a = stat($source);

	open INFD, $source 
	    or die "Can't open input file: $!\n";
	
	$source =~ s/^.*\/([^\/]*)$/$1/;

	my $unquotedsource=$source;
	$unquotedsource=~s/\.txt\s*$//;
	print STDERR "Файл: $source, дата: $CreatedAt ";
	my %TourDefaults=('CreatedAt'=>$CreatedAt);
	my %QuestionDefaults=();
	my %QuestionGlobalDefaults=('Type'=>'Ч');
	while (($key, $value) = getField(\*INFD)) {
	  
    last if (!$key);
	    
    if ($key =~ /Мета/) {
		  next;   # This is obsolete
    }
    if ($key =~ /Чемпионат/ || $key =~ /Пакет/) {		
		  ($TournamentId, $ParentId) = CheckFile($source,$value);
		  if (!$TournamentId)  {
		    last;
		  }	

		  $sth = db()->prepare("UPDATE Tournaments SET
			             Title=?, Type='Ч', 
                                     ParentId=?, 
                                     FileName=?, 
                                     CreatedAt=?, 
                                     LastUpdated = NOW()
                                     WHERE
                                     Id=?");
		  $sth->execute($value,$ParentId,$source,$CreatedAt,$TournamentId);
		  next;
    }
    if ($key =~ /Тур/) {      
		  if ($TourId) {
	    		db()->do("UPDATE Tournaments SET QuestionsNum=$qnum
			      WHERE Id=$TourId");
		  }
		  $qnum = 0;
		  $TourNum++;
		  $TourDefaults{'FileName'}= "$unquotedsource.$TourNum";
						

		  ($TourId, $textId)=CreateTour( $value,$TournamentId,$TourNum,
				   \%TourDefaults, $source);
		  %QuestionDefaults=%QuestionGlobalDefaults;
		  $QuestionId=0;
		  next;	
    }
    if ($key =~ /Вопрос/) {
		  if (!$TourId) {
		    $qnum = 0;
		    $TourNum++;	    
		    ($TourId, $textId)=CreateTour( '1',$TournamentId,$TourNum,
				       \%TourDefaults, $source);
    		    %QuestionDefaults=%QuestionGlobalDefaults;
  		}
  		my $QuestionTextId= $textId.'-'.($qnum+1);
  		my $query = "INSERT INTO Questions 
			     (ParentId, Number, TextId) 
			     VALUES ($TourId, $qnum+1, '$QuestionTextId')";
  		$sth = db()->prepare($query);
  		$sth->execute or print $query;;
  		$QuestionId = $sth->{mysql_insertid};
  		&UpdateQuestion(  $QuestionId, "Question", $value);
  		while (my ($key,$value)=each %QuestionDefaults) {
		    &UpdateQuestion(  $QuestionId, $key, $value);
  		}		
  		$qnum++;
  		$all_qnum++;
  		next;
    }
    if ($key =~ /Ответ/) {
      &UpdateQuestion( $QuestionId, "Answer", $value);
		  next;
    }

    if ($key =~ /Зач[её]т/) {
		  &UpdateQuestion( $QuestionId, "PassCriteria", $value);
		  next;
    }

    if ($key =~ /Комментари/) {
  		&UpdateQuestion( $QuestionId, "Comments", $value);
  		next;
    }
   
    my @Fields = grep { $key =~ /$_/ } keys %QuestionFields;

    if (scalar @Fields) {
		  my $word = shift @Fields;
		  my $field = $QuestionFields{$word};
		  if ($QuestionId) {
          &UpdateQuestion($QuestionId, $field, $value);
		  } elsif ($TourId) {
		    $QuestionDefaults{$field}=$value;
		  } else {
		    $QuestionGlobalDefaults{$field}=$value;
		  }
		  next;
    }

    @Fields = grep { $key =~ /$_/ } keys %TourFields;

    if (scalar @Fields) {
  		my $word = shift @Fields;
  		my $field = $TourFields{$word};
  		my $updateId;
  		if ($QuestionId) {
		    print STDERR "ОШИБКА: $key $value недопустимы после",
		    " начала вопросов\n";
  		} else {
  		  if ($TourId) {
  		    $updateId = $TourId;
        } else {
          $updateId = $TournamentId;
          $TourDefaults{$field}=$value;
        }
        &UpdateTournament( $updateId, $field, $value);
  		}
  		next;
    }

	    
	    #
	    # If we are here, something got wrong!
	    #
	    print STDERR "\nЯ НЕ ПОНИМАЮ: $key, $value!\n";
	    
	}
	db()->do("UPDATE Tournaments SET QuestionsNum=$qnum
			WHERE Id=$TourId");
	db()->do("UPDATE Tournaments SET QuestionsNum=$all_qnum
			WHERE Id=$TournamentId");
	&UpdateParents( $ParentId, $all_qnum,$UnquotedCreated);		
	print STDERR "Всего вопросов: $all_qnum \n";
    }
    close UNSORTED;
    unlink $unsortedname unless $isunsorted;
    db()->disconnect;
}
