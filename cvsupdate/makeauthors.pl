#!/usr/bin/perl -w

=head1 NAME

makeauthors.pl - ������ ��� �������� ������ �������

=head1 SYNOPSIS

makeauthors.pl

=head1 DESCRIPTION

������ ������� � ��������� ������� authors � A2Q, ��������� 
���������� �� ������ authors,nicks,ssnicks

=head1 AUTHOR

����� ���������


=cut

use ChgkAuthors;
$inst = new ChgkAuthors();

$inst->process();
exit;
