#!/usr/bin/perl -w

=head1 NAME

add_rating.pl - ������ ��� ������������ ��������������� �������� � ����� nicks

=head1 SYNOPSIS

makeauthors.pl

=head1 DESCRIPTION

������ ����� "1" � ����� ������� nicks �� ������������� ������ � ��������. � ������, ���� ������� � ����� ������ � �������� ������ ������, 
�������� � ���� dump/rating_mult

=head1 AUTHOR

����� ���������

=cut

use dbchgk;

my $DUMPDIR = $ENV{DUMPDIR} || "dump";
do "chgk.cnf";
use locale;
use POSIX qw (locale_h);
use Data::Dumper;
open RPLAYERS, "<$rating_players_file" or die "Can not open $rating_players_file";
open NICKS, "<$nicksfile" or die "Can not open nicks";
open MULT, ">$DUMPDIR/rating_mult" or die "Can not open $DUMPDIR/rating_mult";
my %rating_player;
my %mult;
my %realname;
my %count;
my $unknown = 1111111111;
while (<RPLAYERS>) {
  chomp;
  my ($id, $name, $surname, $count, $ed, $ref) = split /\t/;
  $ed||='';
  $ref||='';
  my $uc = uc "$name $surname";
  my $realname = $uc;
  $uc =~s/�/�/i;
  if ($rating_player{ $uc }) {
    if ( $count{$uc}<$count ) {
      $rating_player{ $uc } = $id;
      $realname{$uc} = $realname;
      $count{$uc} = $count;
      $add{$uc}="$ed$ref";
    } 
    $mult{ $uc } = 1;
  } else {
    $rating_player{$uc} = $id;
    $realname{$uc} = $realname;
    $count{$uc} = $count;
    $add{$uc}="$ed$ref";
  }
}
print MULT join "\n", keys %mult;

while (<NICKS>) {
#  s/^\s*\d+/$unknown/;
  if ( /^\s*$unknown\s/ ) {
    print change($_);
  } else {
    print;
  }
}

sub change {
  my $n = shift;
  my $name = <NICKS>;
  $name=~s/^\s+//;
  $name=~s/\s+$//;
  $name = uc $name;
  $n=~s/\s+$//;
  $n=~s/^\s+//;
  my($number, $nick, $ed, $ref) = split ' ', $n;
  my $name1=$name;
  $name1=~  s/�/�/i;
  if ( $rating_player{$name1} && ( $rating_player{$name1} ne 'mult' ) ) {
    my $r = $rating_player{$name1};
    $n = $r.' '.$nick.($add{$name1}?' '.$add{$name1}:'');
#    $n =~ s/^\s*$unknown /$r /;
    
  }
  return $n."\n ".($realname{$name}||$name)."\n";
}