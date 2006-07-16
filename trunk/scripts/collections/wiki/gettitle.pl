#!/usr/bin/perl

use strict;
use bytes;

my $intitle=0;
my $title = "";

while (<>) {
  $intitle = 1 if (s/^.*<name[^>]*>//);
  last if (s/<\/name>//);
  $title .= $_ if ($intitle == 1);
}

$title .= "$_";
$title =~ s/[\n\r\s]+/ /gm;
$title =~ s/(^\s*)|(\s*$)//g;
print "$title";
