<?header("Content-type: application/xml");
header("Content-encoding: UTF-8");
print "<?";?>xml version="1.0" encoding="UTF-8"<?print "?>\n";?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 plus MathML 2.0//EN"  "http://www.w3.org/TR/MathML2/dtd/xhtml-math11-f.dtd" [ <!ENTITY mathml "http://www.w3.org/1998/Math/MathML">]>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head><title>Informations</title></head>
<link href="../css/style.css" rel="stylesheet"/>
<style>
iframe {
  width: 90%; margin-left: auto; margin-right: auto; border: 1pt solid #bbbbbb;
  max-height: 4cm;
}
</style>
<body>
<h1>Informations</h1>
<div>X-Rai version 2004-07-10 by <a href="mailto:Benjamin.Piwowarski@lip6.fr">B. Piwowarski</a>.
LaTeX to MathML transformations are made with <a href="http://hutchinson.belmont.ma.us/tth/mml/"><img src='../img/ttm_icon.gif' alt='ttm'/></a>, a tool written by <a href="http://www.psfc.mit.edu/people/hutch/">Ian Hutchinson</a></div>

<h1>Bugs</h1>

<p>If you have found a bug with the assessment tool, please send me an email (<a href="mailto:Benjamin.Piwowarski@lip6.fr?subject=[INEX-Asssessments bug]">Benjamin.Piwowarski@lip6.fr</a>). Please do include in your mail: your login, the URL of the bug and (if relevant) the element you are trying to assess together with the assessment. Don't forget to mention your OS name and you navigator name and version</p>

<h1>Wishlist, in progress</h1>

<iframe src="info-iframe?file=todo"/>


<h1>Changelog</h1>

<iframe src="info-iframe?file=changelog"/>

</body>
</html>