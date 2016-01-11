#!/usr/bin/perl -w

use dbchgk;

=head1 NAME

makepeople.pl - скрипт для создания пустой таблицы People

=head1 SYNOPSIS

makepeople.pl


=head1 AUTHOR

Роман Семизаров
 
=cut




my $DUMPDIR = $ENV{DUMPDIR} || "../dump";

do "chgk.cnf";
use locale;
use POSIX qw (locale_h);

mydo("DROP TABLE IF EXISTS People");

mydo("CREATE TABLE People
  ( 
    Id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
    CharId CHAR(20),    
    Name CHAR(50),
    Surname CHAR(50),
    City CHAR(50),
    Nicks TEXT,
    QNumber INT DEFAULT 0,
    TNumber INT DEFAULT 0,
    RatingId INT DEFAULT 0,
    IsCertifiedEditor BOOL default 0, 
    IsCertifiedReferee BOOL default 0,
    UNIQUE KEY (CharId)
) COLLATE utf8_general_ci");

mydo ("DROP TABLE IF EXISTS P2Q");

mydo("CREATE TABLE P2Q
    (
        Author CHAR(20),
        Question INT UNSIGNED,
	KEY authorkey(Author),
	KEY questionkey(Question),
	PRIMARY KEY (Author, Question)
    )"
);

mydo("DROP TABLE IF EXISTS P2T");

mydo("CREATE TABLE P2T
    (
        Author CHAR(20),
        Tour INT UNSIGNED,
	KEY authorkey(Author),
	KEY tourkey(Tour),
	PRIMARY KEY (Author, Tour)
    )"
);
