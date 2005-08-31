#!/usr/bin/perl -w

# Remove any mixed content within an XML file
# Also put the white spaces within tags
# B. Piwowarski / aug 2005


# use strict;
use XML::Parser;
use File::Basename;
use FileHandle;
use IO::Handle;
use bytes;

my @stack;
my $cdata = "";
my $xrais = "xrai:s";
my @endtag;

#
# --- SAX handlers ---
#

sub check_end {
   if ($#endtag >= 0) {
#       print "$cdata";
      while ($#endtag >= 0) { my $t = shift @endtag; print "</$t>"; }
#       $cdata = "";
   }
}

sub handle_start {
   check_end();
#    print STDERR "$_[1] ($cdata)\n";
   if (!($cdata =~ /^[\n\r\s]*$/s)) {
      print "<${xrais}>" . $cdata . "</${xrais}>";
      $cdata = "";
   }
   $stack[$#stack] = 1 if ($#stack >= 0);

   print "<$_[1]";
   for(my $i = 2; $i < $#_; $i+=2) {
      print " $_[$i]=\"" . $_[$i+1] . "\"";
   }
   print ">";
   push @stack, 0;
}

sub handle_text {
   $_[1] =~ s/</&lt;/g;
   $_[1] =~ s/>/&gt;/g;
   $_[1] =~ s/&/&amp;/g;
   if (!($_[1] =~ /^[\n\r\s]*$/s)) {
      print $cdata; $cdata = "";
      check_end();
   }
   $cdata .= "$_[1]";
}

sub handle_end {
   if (!($cdata =~ /^[\n\r\s]*$/s)) {
      check_end();
      if ($stack[$#stack] > 0) { print "<${xrais}>" . $cdata . "</${xrais}>"; }
      else { print $cdata; }
      $cdata = "";
   }
   push @endtag, $_[1];
   pop @stack;
}

my $parser = new XML::Parser(ParseParamEnt => 1, Handlers => {Start => \&handle_start, End   => \&handle_end, Char => \&handle_text});

$parser->parsefile("-");
check_end();