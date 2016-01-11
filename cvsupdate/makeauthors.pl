#!/usr/bin/perl -w

=head1 NAME

makeauthors.pl - скрипт для создания таблиц авторов

=head1 SYNOPSIS

makeauthors.pl

=head1 DESCRIPTION

Скрипт создаёт и заполянет таблицы authors и A2Q, используя 
информацию из файлов authors,nicks,ssnicks

=head1 AUTHOR

Роман Семизаров


=cut

use ChgkAuthors;
$inst = new ChgkAuthors();

$inst->process();
exit;
