#!/usr/bin/perl -w

=head1 NAME

makeeditors.pl - ������ ��� �������� ������ �������

=head1 SYNOPSIS

makeeditors.pl

=head1 DESCRIPTION

������ ������� � ��������� ������� E2T � �������� ������� Authors, ��������� 
���������� �� ������ authors,nicks,ssnicks

=head1 AUTHOR

����� ���������


=cut

use ChgkEditors;

$inst = new ChgkEditors();

$inst->process();
exit;
