#!/usr/bin/perl

# Takes one argument (or standard input): a file
# with two columns (id title)
use strict;

our $c = 1;
our @files;
push @files, [];

our $div = 181; #100;

while (chop($_=<>)) {
  if (/^(\d+)\s+(.*)$/) {
    my $word = $2;
    my $id = $1;

    push @{$files[$#files]}, ["$id",$word,$word];


    my $i = $#files;

    while (($i >= 0) && (@{$files[$i]} >= $div)) {
      $i--;
      flush($i, \@files, 0);
    }

  };

}

# Flush everything
print STDERR "Flushing everything\n";
for(my $i = $#files; $i >= 0; $i--) {
  flush($i-1, \@files, 1);
}


# -----------------------------------------------------------------------------------------

sub flush {
  my $i = $_[0];
  my $files = $_[1];
  my $itIsTheEnd = ($_[2] > 0) && ($i < 0);

  if ($i < 0) {
    unshift @{$files}, [];
#    print STDERR "Adding a level (" .  (@{$files}) . ")\n";
    $i++;
  }

 # print STDERR "Number of files is " . @{$files[$#files]} . " with " . @files . " levels: (";
#  for(my $i = 0; $i < @files; $i++) {
#    print STDERR "," if ($i > 0);
#    print STDERR $#{@{$files[$i]}}+1;
#  }
#  print STDERR ")\n";




  # Construct the filename for the container
  my $s = "";

  for(my $j = 0; $j <= $i ; $j++)  {
    $s .= "-" if ($j > 0);
    $s .= @{$files->[$j]} + ($i == $j ? 1 : 0);
  }

  for(my $j = $i+1; $j < @{$files}; $j++) {
    $s .= "-x";
  }

  $s = "index" if ($itIsTheEnd);

#  print STDERR "Pushing: $s, " . $files->[$i+1]->[0]->[1] . ", " . $files->[$i+1]->[@{$files->[$i+1]}-1]->[2] . "\n";

  push @{$files->[$i]}, [$s, $files->[$i+1]->[0]->[1], $files->[$i+1]->[@{$files->[$i+1]}-1]->[2]];

  open OUT, "> $s.xrai";
  print OUT "<?xml version=\"1.0\"?>\n";
  print OUT "<collection path=\"$s.xrai\">\n";
  foreach my $f (@{$files->[$i+1]}) {
#    print STDERR "\t$f->[0] ($f->[1], $f->[2])\n";

    if (($i + 1) != $#{$files}) {
      print OUT "<subcollection path=\"$f->[0]\">\"$f->[1]\" to \"$f->[2]\"</subcollection>\n";
    } else {
      print OUT "<document path=\"$f->[0]\">$f->[1]</document>\n";
    }

  }
  print OUT "</collection>\n";
  $files[$i+1] = [];
}
