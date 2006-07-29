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
my $wrap_cdata = 0; # 1 if cdata has to be wrapped

my $xrais = "xrai:s";
my @endtag;

my $debug = 0;

#
# --- SAX handlers ---
#

sub is_space {
   return $_[0] =~ /^[\n\r\s\t]*$/;
}

sub xmlentities {
   $_[0] =~ s/</&lt;/g;
   $_[0] =~ s/>/&gt;/g;
   $_[0] =~ s/'/&apos;/g;
   $_[0] =~ s/"/&quote;/g;
   $_[0] =~ s/&/&amp;/g;
   return $_[0];
}

sub check_end {
   if ($#endtag >= 0) {
      if (!is_space($cdata)) {
         if ($wrap_cdata) {
            print "<xrai:s>$cdata</xrai:s>";
         } else {
            print "$cdata";
         }
         $cdata = ""; $wrap_cdata = 0;
      }
      while ($#endtag >= 0) { my $t = shift @endtag; print "</$t>"; }
   }
}

sub handle_start {
   print "{2,$wrap_cdata,$_[1]<}"  if ($debug);
   check_end();
   print "{2>}" if ($debug);
   if (!is_space($cdata)) { print "<xrai:s>$cdata</xrai:s>"; $cdata = ""; }

   $stack[$#stack] = 1 if ($#stack >= 0);

   print "<$_[1]";
   for(my $i = 2; $i < $#_; $i+=2) {
      print " $_[$i]=\"" . xmlentities($_[$i+1]) . "\"";
   }
   print ">";
   push @stack, 0;
   $wrap_cdata = 0;
}


sub handle_end {
   print "{5}" if ($debug);
   push @endtag, $_[1];
   pop @stack;
}

sub handle_text {
   $_[1] =~ s/</&lt;/g;
   $_[1] =~ s/>/&gt;/g;
   $_[1] =~ s/&/&amp;/g;
   if (!is_space($_[1])) {
      check_end();
   }
   $wrap_cdata = 1 if ((!is_space($_[1])) && ($stack[$#stack] != 0));
   print "{4,$wrap_cdata|$cdata}" if ($debug);
   $cdata .= "$_[1]";
}


my $parser = new XML::Parser(ParseParamEnt => 1, Handlers => {Start => \&handle_start, End   => \&handle_end, Char => \&handle_text});

$parser->parsefile("-");
check_end();