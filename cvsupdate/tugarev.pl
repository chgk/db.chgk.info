#!/usr/bin/perl -w

=head1 NAME

makeeditors.pl - скрипт для создания таблиц авторов

=head1 SYNOPSIS

makeeditors.pl

=head1 DESCRIPTION

Скрипт создаёт и заполняет таблицу E2T и апдейтит таблицу Authors, используя 
информацию из файлов authors,nicks,ssnicks

=head1 AUTHOR

Роман Семизаров


=cut

use ChgkPeople;

$inst = new ChgkPeople();

$patched = $inst->patch('dump/tugarev4.csv');
print $patched;
exit;
