#!/usr/bin/perl

=head1 NAME

dbchgk.pm - модуль для работы с базой

=head1 SYNOPSIS

  use chgkfiles.pm  

=head1 DESCRIPTION

  Работа с базой


=head1 AUTHOR

Роман Семизаров
=cut

package dbchgk;
use DBI;
use Exporter;
use Data::Dumper;
use vars qw(@ISA @EXPORT);
@ISA=qw(Exporter);

@EXPORT = qw(&getbase &getquestions &closebase &getrow $z &in2out &getall &addnf &out2in &mydo
             &getequalto &forbidden &getquestion &checktable &addword2task &addnest &getwordkeys &getflag &addword2task 
             &updateword2question &updatew2q &knownword &incnf &searchmark &knownnf &getnests 
             &packword &getnfnumbers &getword2question &addauthors &addquestions2author &addtours2author &getalltours &tableexists 
             &createquestionstable &createtournamentstable &db &gettour) ;

my $z;
my $qbase;
BEGIN {do "chgk.cnf"; 	
          $qbase = DBI -> connect ("DBI:mysql:$base",
		$ENV{'DB_USERNAME'}||'chgk',
		exists($ENV{'DB_USERPASS'})?$ENV{'DB_USERPASS'}:'ChgK');
	  $qbase->do("SET NAMES koi8r");
      };



sub searchmark 
{
   my $a=$_[0];
   $qbase->do ("UPDATE Questions SET ProcessedBySearch=1 WHERE QuestionId=$a")
}

sub knownword
{
        my $a=$qbase ->quote (uc $_[0]);
        my $select = "select distinct w2 from nests where w1=$a";
        print "$select\n" if $debug;
	my $z=  $qbase -> prepare($select);
	$z -> execute;
	my @res;
	while ( my @ar=$z -> fetchrow)
        {
          push (@res,$ar[0])
        }
        return @res;

}

sub knownnf
{
        my $a=$qbase ->quote (uc $_[0]);
        my $select = "select id from nf where word=$a";
        print "$select\n" if $debug;
	my $z=  $qbase -> prepare($select);
	$z -> execute;
	my @ar=$z -> fetchrow;
        return $ar[0];
}

sub incnf
{
   my $a=$_[0];
   my $b=$_[1]||1;
   $qbase -> do ("UPDATE nf SET number=number+$b WHERE id=$a")
}

sub getbase
{    
        my $a=join(", ",@_);
        my $select="select $a FROM Questions WHERE QuestionId<=$qnumber ORDER BY QuestionId";
        print "$select\n" if $debug;
	$z=  $qbase -> prepare($select);
        print "prepared\n" if $debug;
	$result = $z -> execute;
        print "executed\n" if $debug;	
	return $result;
}

sub getalltours
{    
        my $a=join(", ",@_);
        my $select="select $a FROM Tournaments -- WHERE Type='Ч'";
        print "$select\n" if $debug;
	$z=  $qbase -> prepare($select);
	$z -> execute;
}

sub gettour
{
	my $id = shift;
        my $a=join(", ",@_);

	if ($id=~/^\d+$/) {
	    $select = "SELECT $a FROM Tournaments WHERE Id=$id"
	} else {
		    $select = "SELECT $a FROM Tournaments WHERE TextId='$id'"
	}
	$z=  $qbase -> prepare($select);
	$z -> execute;
	return $z->fetchrow;
}

sub getquestions
{    
        my $cond=pop @_;
        my $a=join(", ",@_);
        my $select="select $a FROM Questions WHERE QuestionId<=$qnumber AND ($cond)";
        print "$select\n" if $debug;
	$z=  $qbase -> prepare($select);
	$z -> execute;
}


sub getword2question
{    
        my $select='select word, questions FROM word2question';
print "$select\n";
	$z=  $qbase -> prepare($select);
	$z -> execute;
}


sub addword2task
{
  ($w1,$w2)=@_;
  $w2=$qbase -> quote ($w2);
  $qbase -> do("insert into word2question (word,questions) values ($w1,$w2)");
}

sub authorexists
{
    $textid = shift;
    $sql = "select 1 from People Where CharId = ".$qbase->quote($textid);
    $z = $qbase ->prepare($sql);
    $z->execute;
    return $z->rows;
}

sub addauthor  
{
    my $p = shift;
    my ($charid,$name,$surname,$nicks, $ratingId)=@_;
    if ( authorexists($p->{nick}) ) {
      return;
    } else {
      my @v = ();
      push @v, $qbase ->quote($_)  foreach ($p->{nick},$p->{name},$p->{surname},$p->{nicks},$p->{ratingId}, $p->{certified_editor}, $p->{certified_referee}, $p->{city});  
      my $query= "insert into People (CharId,name,surname,Nicks, RatingId, IsCertifiedEditor, IsCertifiedReferee, City  ) 
                values (".join(', ', @v).")";
      print STDERR Dumper($p).$query."\n" unless $p->{nick};
      mydo($query);
    }
}
sub addquestions2author
{

  my ($questions, $p,$forbidden)=@_;  
  $forbidden||={};
  print STDERR Dumper($questions)."\n" unless $p->{nick};
  my $kvo=scalar grep {!$forbidden->{$_}} @$questions;
  addauthor($p);
  $qbase->do( $sql = "UPDATE People SET QNumber=$kvo WHERE CharId=".$qbase->quote( $p->{nick} ) );
  my @pairs = ();
  foreach my $q (@{$questions})
  {
    push @pairs, "(".$qbase->quote($p->{nick}).",$q)";
  }
  my $s = join ",", @pairs;
  $query="insert into P2Q (Author,Question) values $s"; 
  $qbase -> do($query) ;
  
}

sub addtours2author
{
  my $tours = shift;
  my $p = shift;
  my $kvo = shift;
#  my ($charid,$name,$surname,$tours,$nicks,$ratingId)=@_;  
#  $kvo||= @$tours;
#  addauthor($p->{nick}, $p->{name}, $p->{surname}, $p->{nicks}, $p->{ratingId});
  addauthor($p);
  $qbase->do("UPDATE People SET TNumber=$kvo WHERE CharId=".$qbase->quote($p->{nick}));  
  foreach my $t (@{$tours})
  {
    $query="insert into P2T (Author,Tour) 
                values (".$qbase->quote($p->{nick}).",$t)";
    $qbase -> do($query) ;
  }
}

sub packword
{
  my ($fieldnumber,$id,$wordnumber)=@_;
die "packword: fieldnumber is $fieldnumber! -- id=$id, word=$wordnumber\n" if $fieldnumber>6;
  $r=pack("CSC",$fieldnumber|(($id >> 16) << 4),$id%65536,$wordnumber%256);
}


sub updatew2q {
  my ($n,$fieldnumber, $id,$wordnumber)=@_;
  my ($z,@a); 
  $query="replace into w2q (wordId,questionId,fieldNumber,wordNumber) values ($n,$id,$fieldnumber,$wordnumber)";
  print "$query\n" if $debug;
  $qbase->do($query);
}

sub updateword2question
{
  my ($n,$addstring,$was)=@_;
  $addstring=$qbase->quote($addstring);
  my ($z,@a); 

  if (!(defined $was))
  {
    $query="select word from word2question where word=$n";
print "$query\n" if $debug;
    $z=$qbase->prepare($query);
    $z->execute;
    @a=$z->fetchrow;
    $was=$a[0];
  }
  my $select=$was ? "UPDATE word2question set questions = CONCAT(questions,$addstring)
                              where word=$n"
                 :
                    "insert into word2question (word,questions) values 
                    ($n,$addstring)";
print "$select\n" if $debug;
  $qbase->do ($select);      

}



sub addnest
{
  my ($w1,$w2)=@_;
  $w1=$qbase -> quote($w1);
  my $query="insert into nests (w1,w2) values ($w1,$w2)";
  print $query if $debug;
  $qbase -> do($query);
}

sub addnf
{
  my ($w0,$w1,$w2,$w3)=@_;
  $w1=$qbase -> quote($w1);
  $w2=$qbase -> quote($w2);
  my $query;
  my $z=  $qbase -> prepare("select flag,id FROM nf WHERE word=$w1");
  $z -> execute;
  my @a=$z->fetchrow;
  my $id;
  if ($a[0]) 
  { 
    $query="update nf set flag=$w2, number=$w3 WHERE word=$w1";
    print "$query\n" if $debug;
    $qbase -> do($query); 
    return $a[1];
  }
  else
  { 
    if ($w0)
    {
       $query="insert into nf (id,word,flag,number) values ($w0,$w1,$w2,$w3)";
       $qbase -> do($query);
       return $w0;
    }
    else
    {
       $query="insert into nf (word,flag,number) values ($w1,$w2,$w3)";
       $qbase -> do($query);
       $query="select id from nf where word=$w1";
print "$query\n" if $debug;
       $z=$qbase->prepare($query);
       $z->execute;
       ($id)=$z->fetchrow;
       return $id;
    }
  } 
}

sub getwordkeys
{
	$z=  $qbase -> prepare("select word, flag FROM nf");
	$z -> execute;
	my %h;
	while ( my  ($first, $second)=$z -> fetchrow)
        {
            $h{$first}=$second;
        }
        $z -> finish;
        %h;
}


sub getequalto
{    
	$z=  $qbase -> prepare("select first, second FROM equalto");
	$z -> execute;
	my %h;
	while ( my  ($first, $second)=$z -> fetchrow)
        {
            $h{$first}=$second;
        }
        $z -> finish;
        %h;
}

sub getnfnumbers
{    
	$z=  $qbase -> prepare("select word, id FROM nf");
	$z -> execute;
	my %h;
	while ( my  ($first, $second)=$z -> fetchrow)
        {
            $h{$first}=$second;
        }
        $z -> finish;
        %h;
}


sub getnests
{    
	$z=  $qbase -> prepare("select w1, w2 FROM nests");
	$z -> execute;
	my %h;
	while ( my  ($first, $second)=$z -> fetchrow)
        {
            $h{$first}.=" $second";
        }
        $z -> finish;
        %h;
}


sub getflag
{
        $w=$qbase->quote($_[0]);
	$z=  $qbase -> prepare("select flag, id from nf where word=$w");
	$z -> execute;
	@res=$z->fetchrow();

	@res;
}


sub closebase
{
    $z -> finish;
    $qbase -> disconnect;
}

sub getrow
{
  $z -> fetchrow
}

sub mydo
{
  $sql = shift;
  $qbase -> do ($sql);
}

sub getall
{
  $z -> fetchall_arrayref;
}

sub forbidden
{
   keys %getequalto
}

sub tableexists {
    $TabName = shift;
    return grep(/^$TabName$/i, &tablelist);
}

sub checktable # если $param='delete' удаляет существующую таблицу,
               # если $param='ask' спрашивает, не удалить ли
               # если $param не определено -- просто удаляет.
               # если $param='deletedata' -- удаляет из таблицы данные
{
	my ($TabName,$param) = @_;
	my ($ans);
	my @x = &tablelist;
	if ( tableexists($TabName) ) {
	        return 1 unless $param;
		if ($param =~ /delete/) {$ans='y';}
                   else {
                           print "Table $TabName exists. Do you want to delete it? ";
                           $ans = <STDIN>
                        }
		if ($ans =~ /[yY]/) {
		    if ($param eq 'delete') {
			$qbase->do("DROP TABLE $TabName");
			print "deleted table $TabName\n";
		    } else {
			$qbase->do("DELETE FROM $TabName");
			print "Deleted everything from $TabName\n";
		    }
		    return 0;
		} else {
			return 1
		}
	}
 0	
}

sub tablelist
{
    my $q = $qbase->prepare('SHOW TABLES');
    $q->execute();
    my @tables = ();
    while (my ($t) = $q->fetchrow()) {
	push  @tables, $t;
    }
    @tables;
}

sub in2out
{
   $qid=shift;

   my $z=  $qbase -> prepare ( "select t2.Id, t2.Number, t3.FileName 
                from Questions AS t1, Tournaments AS t2 ,  Tournaments AS t3
                where (t1.QuestionId = $qid)  && (t1.ParentId = t2.Id) && (t2.ParentId = t3.Id) ");

   $z -> execute;
  ($tourid, $tourname, $filename)= $z -> fetchrow;


   $z=  $qbase -> prepare("select QuestionId  from Questions  WHERE ParentId = $tourid");

    $z -> execute;
    my $i;
    for ($i=1;  ($q= $z->fetchrow) && $q!=$qid; $i++){};

   $_=lc $_;
   $filename=~s/\.txt$//i;
   "$filename\.$tourname\.$i";
}



sub out2in
{
   @q= split(/\./, lc shift);

   $q[0].='.txt';

# 


   $z=  $qbase -> prepare ( "select q.QuestionId  from Questions as q, 
                Tournaments as t1, Tournaments as t2
                where (t2.FileName= \"$q[0]\")  && 
                      (t1.ParentId = t2.Id) && 
                      (q.ParentId = t1.Id)  && 
                      (t1.Number=\"$q[1]\")
            ");

   $z -> execute;
#   ($tourid)=$z -> fetchrow or die "Bad identifier". join (".", @q);

#   print "--$tourid--";

#   $z=  $qbase -> prepare("select QuestionId  from questions  WHERE ParentId = $tourid");

    my $i;
    $z -> execute;
    for ($i=1;  $i <= $q[2]; $i++){@qq= $z->fetchrow};

    $z -> finish;
    $qq[0];
}

sub createquestionstable
{
	mydo("SET NAMES utf8");
	mydo("CREATE TABLE Questions (
		QuestionId 	INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			KEY QuestionIdKey (QuestionId),
		ParentId 	SMALLINT UNSIGNED NOT NULL,
			KEY ParentIdKey (ParentId),
		Number 	       SMALLINT UNSIGNED NOT NULL,
			KEY NumberKey (Number),
		`Type` 		CHAR(5) NOT NULL DEFAULT 'п╖',
		        KEY TypeKey (Type),
		`TypeNum` 		TINYINT NOT NULL DEFAULT 0,
		        KEY TypeNumKey (TypeNum),
		TextId		CHAR(16) UNIQUE KEY,
		Question 	TEXT,
		Answer 		TEXT,
		PassCriteria	TEXT,
		Authors 	TEXT,
		Sources 	TEXT,
		Comments 	TEXT,
                Rating          TEXT,
                RatingNumber    FLOAT,
			KEY RatingKey (RatingNumber),
                Complexity    TINYINT,
			KEY ComplexityKey (Complexity),
                Topic           TEXT,
                ProcessedBySearch  INT
	)  DEFAULT CHARSET=utf8 ENGINE=MyISAM" );
	print "Questions table is created\n";
}


sub createtournamentstable
{
	mydo("SET NAMES utf8");
	mydo("CREATE TABLE Tournaments (
		Id 			INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			KEY IdKey (Id),
		ParentId 	INT UNSIGNED NOT NULL,
			KEY ParentIdKey (ParentId),
		Title 		TINYTEXT NOT NULL,
                Number    SMALLINT UNSIGNED,
                TextId CHAR(16)  CHARACTER SET latin1 COLLATE latin1_bin UNIQUE KEY,
		QuestionsNum INT UNSIGNED DEFAULT 0,
		Complexity FLOAT,
		Type 		ENUM('п╖','п╒','п⌠'),
		Copyright 	TEXT,
		Info 			TEXT,
		URL 			TINYTEXT,
		FileName 	CHAR(25) CHARACTER SET latin1 COLLATE latin1_bin,
		KEY FileNameKey (FileName),
		RatingId	INT,

                Editors         TEXT,
                EnteredBy       TEXT,
                LastUpdated     DATETIME,
		PlayedAt 	DATE,
		PlayedAt2       DATE,		
		KandId          INT,		
		CreatedAt 	DATE NOT NULL
	)   DEFAULT CHARSET=utf8 ENGINE=MyISAM");
	print "Tournaments table is created\n";

}

sub db() {
    return $qbase;
}

1;
