<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:xrai="http://inex.lip6.fr/X-Rai"
   version="1.0">
  

       
  <xsl:strip-space elements="*"/>
  <xsl:param name="num" select="'no'"/>

	<xsl:output method="xhtml" encoding="utf-8" indent="yes"/> 

  <!-- Localization -->

	<xsl:template match="@*" mode="localize">
		<xsl:value-of select="name(.)"/>="<xsl:value-of select="."/>"
	</xsl:template>


        <xsl:template match="*" mode="number">
          <xsl:variable name="n" select="name(.)"/>
          <xsl:value-of select="count(preceding-sibling::*[name()=$n])+1"/>
        </xsl:template>


        <xsl:template match="*" mode="localize"><xsl:variable name="n"><xsl:apply-templates mode="number" select="."/></xsl:variable><xsl:value-of select="concat(name(.),'[',$n,']')"/></xsl:template>



  <!-- Highlight -->

  <xsl:template match="fig/no|figw/no" mode="high">
    <xsl:variable name="l"><xsl:apply-templates mode="localize" select="."/></xsl:variable>
    <xmltag type="span" name="{name(.)}" path="{$l}"  count="{count(.//*)}" ccount="{count(*)}" tcount="y">
    <strong>Figure <xsl:value-of select="."/>: </strong>
    </xmltag>
  </xsl:template>


	<xsl:template match="art" mode="high">
	   <xsl:variable name="path"><xsl:apply-templates mode="localize" select="."/></xsl:variable>
	   <xmltag type="div" name="{name(.)}" path="{$path}"  count="{count(.//*)}" ccount="{count(*)}" tcount="y">
        <showart file="{@file}" width="{@w}" height="{@h}"/>
    <xsl:apply-templates/>
	 </xmltag>
	</xsl:template>

  <!-- Nodes with math tex code inside -->    
   <xsl:template match="tf" mode="high">
      <xsl:variable name="path"><xsl:apply-templates mode="localize" select="."/></xsl:variable>
      <xmltag type="div" name="{name(.)}" path="{$path}"  count="{count(.//*)}" ccount="{count(*)}" tcount="y">
      <xrai-maths><xsl:apply-templates/></xrai-maths>
    </xmltag>
   </xsl:template>
   <xsl:template match="tmath" mode="high">
      <xsl:variable name="path"><xsl:apply-templates mode="localize" select="."/></xsl:variable>
      <xmltag type="span" name="{name(.)}" path="{$path}"  count="{count(.//*)}" ccount="{count(*)}" tcount="y">
      <xrai-maths><xsl:apply-templates/></xrai-maths>
    </xmltag>
   </xsl:template>
   
  <xsl:template match="hdr|hdr1|hdr2|item|li|f|fn|bq|figw|fig|index|index-entry|vt|entry|question|doi|answer|article|bib|tbl|brief|tig|ti|l1|kwd|fno|edintro|bdy|fm|sec|ss1|ss2|ip1|ip2|ip2|ip3|ip4|ip5|p|p1|p2|p3|lc|bm|apt|abs|app|bb|ack|list|bibl|hdr|hdr1|hdr2|footnote|edinfo|fgc|bullet-list|list|la|lb|lc|ld|ul|l1|l2|l3|l4|l5|l6|l7|l8|l9|numeric-list" mode="high">
    <xsl:variable name="path"><xsl:apply-templates mode="localize" select="."/></xsl:variable>
	 <xmltag type="div" name="{name(.)}" path="{$path}" count="{count(.//*)}" ccount="{count(*)}" tcount="y">
    <xsl:apply-templates/>
    <endtag type="div" name="{name(.)}" path="${path}"/>
	 </xmltag>
 </xsl:template>

 <xsl:template match="url" mode="high">
     <xsl:variable name="path"><xsl:apply-templates mode="localize" select="."/></xsl:variable><xmltag  count="{count(.//*)}" ccount="{count(*)}" tcount="y" type="span" name="{name(.)}" path="{$path}"><a target="_other" href="{.}"><xsl:apply-templates/></a></xmltag>
  </xsl:template>

  
  
  <!-- Table -->
   <xsl:template match="tgroup" mode="high">
	 <xsl:variable name="l"><xsl:apply-templates mode="localize" select="."/></xsl:variable>
	   <xmltag  count="{count(.//*)}" ccount="{count(*)}" tcount="y" type="div" name="{name(.)}" path="{$l}">
      <table>
      <thead>
        <tr><td colspan="{count(tbody/row[1]/entry)+1}"><xsl:apply-templates select="colspec|spanspec"/></td></tr>
        <xsl:apply-templates select="thead"/>
      </thead>
      <xsl:apply-templates select="tbody"/>
      <tfoot>
        <xsl:apply-templates select="tfoot"/>
      </tfoot>
      </table>
      </xmltag>
  </xsl:template>
  
  <xsl:template match="tbody" mode="high">
	 <xsl:variable name="l"><xsl:apply-templates mode="localize" select="."/></xsl:variable>
	 <xmltag  count="{count(.//*)}" ccount="{count(*)}" tcount="y" type="tbody" name="{name(.)}" path="{$l}">
    <xsl:apply-templates/>
    </xmltag>
  </xsl:template>


  <xsl:template match="row" mode="high">
	 <xsl:variable name="l"><xsl:apply-templates mode="localize" select="."/></xsl:variable>
	 <xmltag  count="{count(.//*)}" ccount="{count(*)}" tcount="y" type="tr" name="{name(.)}" path="{$l}">
    <xsl:apply-templates/>
    </xmltag>
  </xsl:template>

  <xsl:template match="entry" mode="high">
    <td>
	 <xsl:variable name="l"><xsl:apply-templates mode="localize" select="."/></xsl:variable>
	 <xmltag  count="{count(.//*)}" ccount="{count(*)}" tcount="y" type="td" name="{name(.)}" path="{$l}">
    <xsl:apply-templates/></xmltag></td>
  </xsl:template>

<!--  <xsl:template match="crt|doi|colspec|spanspec|tbl/no" mode="high">
  </xsl:template>

  <xsl:template match="tf|littmath|tmath|tgroup|tbody" mode="high">
    <xsl:apply-templates/>
  </xsl:template>//-->

<!--  <xsl:template match="tf|littmath|tmath" mode="high">
    <i><xsl:apply-templates/></i>
  </xsl:template>//-->

<!--  <xsl:template match="sub|scp|reviewer|doi|fgb|ariel|bi|bu|bui|cen|rm|rom|scp|ss|tt|u|ub|cny|cty|ead|pc|san|sbd|str|deg|appel|a|ref|role" mode="high">
    <xsl:apply-templates/>
  </xsl:template>//-->
  <xsl:template match="ref" mode="high">
    <xsl:variable name="path"><xsl:apply-templates mode="localize" select="."/></xsl:variable>
    <xsl:choose>
      <xsl:when test="@type='BIB' or @type='bib'">
    	 <xmltag  count="{count(.//*)}" ccount="{count(*)}" tcount="y" type="span" name="{name(.)}" path="{$path}">
        <a href="#{@rid}">
          <xsl:apply-templates/>
        </a>
        </xmltag>
      </xsl:when>
      <xsl:otherwise>
    	 <xmltag  count="{count(.//*)}" ccount="{count(*)}" tcount="y" type="span" name="{name(.)}" path="{$path}">
        <xsl:apply-templates/>
    	 </xmltag>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="*" mode="high">
    <xsl:variable name="path"><xsl:apply-templates mode="localize" select="."/></xsl:variable>
	 <xmltag  count="{count(.//*)}" ccount="{count(*)}" tcount="y" type="span" name="{name(.)}" path="{$path}">
    <xsl:apply-templates/>
	 </xmltag>
  </xsl:template>

  <!-- HTML code -->

  <xsl:template match="/">
    <div style="background: #eeeeee; padding: 5px; boder: 1px solid #000000;">
    <xsl:call-template name="toc"/>
    </div>

    <xsl:apply-templates mode="high"  select="/article"/>
  </xsl:template>

  <!-- general document structure: title, front matter, body matter -->

  <xsl:template match="article">
    <xsl:apply-templates mode="high" select="*"/>

<!--     <xsl:apply-templates  select="bdy" mode="high"/> -->

<!--     <xsl:apply-templates  select="bm" mode="high"/> -->

  </xsl:template>

  <!-- Header -->
  <xsl:template match="ti">
    <em><xsl:apply-templates mode="high" select="."/></em>
  </xsl:template>
  <xsl:template match="atl">
    <h1 class="inex"><xsl:apply-templates mode="high" select="."/></h1>
  </xsl:template>

  <!-- table of contents -->



  <xsl:template mode="metadata" match="/article">
    <table>
      <tr>
        <th align="left">
           <xsl:choose>
             <xsl:when test="fm/au[2]">
               <xsl:text>Authors:</xsl:text>
             </xsl:when>
             <xsl:otherwise>
               <xsl:text>Author:</xsl:text>
             </xsl:otherwise>
           </xsl:choose>
         </th>
        <td>
          <xsl:apply-templates  select="fm/au"/>
        </td>
      </tr>
      <tr>
        <th align="left"><xsl:text>Journal:</xsl:text></th>
        <td>
          <xsl:apply-templates  select="fm/hdr/hdr1/ti"/>
        </td>
      </tr>
      <tr>
        <th align="left"><xsl:text>Issue:</xsl:text></th>
        <td>
          <xsl:apply-templates  select="fm/hdr/hdr2/obi/volno"/>
          <xsl:text>, </xsl:text>
          <xsl:apply-templates  select="fm/hdr/hdr2/obi/issno"/>
        </td>
      </tr>
      <tr>
        <th align="left"><xsl:text>Publication Date:</xsl:text></th>
        <td>
          <xsl:apply-templates   select="fm/hdr/hdr2/pdt/mo"/>
	  <xsl:text> </xsl:text>
          <xsl:apply-templates    select="fm/hdr/hdr2/pdt/yr"/>
        </td>
      </tr>
      <tr>
        <th align="left"><xsl:text>Pages:</xsl:text></th>
        <td>
          <xsl:apply-templates  select="fm/hdr/hdr2/pp"/>
          <xsl:text> [</xsl:text>
          <a>
            <xsl:attribute name="href">
              <xsl:text>http://www.computer.org/</xsl:text>
              <xsl:apply-templates select="fm/hdr/hdr1/ti" mode="shortcut2"/>
              <xsl:text>/</xsl:text>
              <xsl:apply-templates select="fm/hdr/hdr1/ti" mode="shortcut"/>
              <xsl:value-of select="fm/hdr/hdr2/pdt/yr"/>
              <xsl:text>/</xsl:text>
              <xsl:value-of select="fno"/>
              <xsl:text>abs.htm</xsl:text>
            </xsl:attribute>
            <xsl:text>Article at IEEE</xsl:text>
          </a>
          <xsl:text> ]</xsl:text>
        </td>
      </tr>
    </table>
  </xsl:template>

  <xsl:template mode="toc" match="sec|ss1|ss2">
    <xsl:value-of select="position()"/><xsl:text>   </xsl:text>
    <a href="#{generate-id(.)}">
      <xsl:value-of select="substring(st,1,1)"/>
      <xsl:value-of select="translate(substring(st,2),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')"/>
    </a>
    <br/>
    <xsl:variable name="subs" select="ss1|ss2"/>
    <xsl:if test="$subs">
      <blockquote><xsl:apply-templates select="$subs" mode="toc"/></blockquote>
    </xsl:if>
  </xsl:template>

  <xsl:template name="toc">
    
    <!-- table of contents -->
    <h2><xsl:text>Table of Contents</xsl:text></h2>
    <xsl:apply-templates select="//sec" mode="toc"/>
  </xsl:template>





  <!-- front matter: authors -->

  <xsl:template match="au[1]">
    <xsl:apply-templates mode="high" select="."/>
  </xsl:template>

  <xsl:template match="au">
    <xsl:text>; </xsl:text>
    <xsl:apply-templates mode="high" select="."/>
  </xsl:template>

 <!-- Author Affiliation -->
  <xsl:template match="aff">
     (<xsl:apply-templates mode="high" select="." />)
  </xsl:template>

  <xsl:template match="aff/onm">
  	<xsl:apply-templates mode="high" select="." />
  </xsl:template>

  <!-- first name -->
  <xsl:template match="fnm">
  	<xsl:apply-templates mode="high" select="." />
  </xsl:template>

  <!-- last name -->
  <xsl:template match="snm">
  	<xsl:apply-templates mode="high" select="." />
  </xsl:template>

  <!-- role -->
  <xsl:template match="role">
    <xsl:text>, </xsl:text>
  	<xsl:apply-templates mode="high" select="." />
  </xsl:template>


  <!-- front matter: publication date -->

  <!-- month -->
  <xsl:template match="mo">
    <xsl:apply-templates mode="high" select="." />
  </xsl:template>

  <!-- year -->
  <xsl:template match="yr">
    <xsl:apply-templates mode="high" select="." />
  </xsl:template>


  <!-- front matter: pages -->

  <xsl:template match="pp">
    <xsl:apply-templates mode="high" select="." />
  </xsl:template>


  <!-- front matter: journal title -->

 

  <xsl:template match="ti" mode="shortcut">
    <xsl:choose>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee annals of the history of computing'">
        <xsl:text>an</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee computer graphics and applications'">
        <xsl:text>cg</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='computer'">
        <xsl:text>co</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee computational science &amp; engineering'">
        <xsl:text>cs</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee design &amp; test of computers'">
        <xsl:text>dt</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee expert'">
        <xsl:text>ex</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee internet computing'">
        <xsl:text>ic</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='it professional'">
        <xsl:text>it</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee micro'">
        <xsl:text>mi</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee multimedia'">
        <xsl:text>mu</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee parallel &#38; distributed technology'">
        <xsl:text>pd</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee software'">
        <xsl:text>so</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee transactions on computers'">
        <xsl:text>tc</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee transactions on parallel and distributed systems'">
        <xsl:text>tp</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee transactions on visualization and computer graphics'">
        <xsl:text>tg</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee transactions on knowledge and data engineering'">
        <xsl:text>tk</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee transactions on pattern analysis and machine intelligence'">
        <xsl:text>tp</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee transactions on software engineering'">
        <xsl:text>ts</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:text>#</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="ti" mode="shortcut2">
    <xsl:choose>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee annals of the history of computing'">
        <xsl:text>annals</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee computer graphics and applications'">
        <xsl:text>cga</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='computer'">
        <xsl:text>computer</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee computational science &amp; engineering'">
        <xsl:text>cise</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee design &amp; test of computers'">
        <xsl:text>dt</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee expert'">
        <xsl:text>intelligent</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee internet computing'">
        <xsl:text>internet</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='it professional'">
        <xsl:text>itpro</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee micro'">
        <xsl:text>micro</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee multimedia'">
        <xsl:text>multimedia</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee parallel &#38; distributed technology'">
        <xsl:text>concurrency</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee software'">
        <xsl:text>software</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee transactions on computers'">
        <xsl:text>tc</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee transactions on parallel and distributed systems'">
        <xsl:text>tdps</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee transactions on visualization and computer graphics'">
        <xsl:text>tvcg</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee transactions on knowledge and data engineering'">
        <xsl:text>tkde</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee transactions on pattern analysis and machine intelligence'">
        <xsl:text>tpami</xsl:text>
      </xsl:when>
      <xsl:when test="translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='ieee transactions on software engineering'">
        <xsl:text>tse</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:text>#</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <!-- front matter: volume and number -->

  <xsl:template match="obi/issno">
    <xsl:apply-templates mode="high" select="." />
  </xsl:template>

<xsl:template match="obi/volno">
    <xsl:apply-templates mode="high" select="." />
  </xsl:template>


  <!-- abstract -->

  <xsl:template match="abs">
     <hr/>
       <h2>Abstract</h2>
       <xsl:apply-templates mode="high" select="." />
  </xsl:template>

  <xsl:template match="abs//p">
    <p>
      <i>
        <xsl:apply-templates mode="high" select="." />
      </i>
    </p>
  </xsl:template>      


  <!-- section title -->
  <xsl:template match="sec/st">
    <h2>
      <xsl:if test="../@TYPE='intro'">Introduction: <br/></xsl:if>
      <xsl:apply-templates mode="high" select="." />
    </h2>
  </xsl:template>

  <!-- Title of Subsection -->
  <xsl:template match="ss1/st">
    <h3><xsl:apply-templates mode="high" select="." /></h3>
  </xsl:template>

  <!-- Title of Sub-subsection -->
  <xsl:template match="ss2/st">
    <h4><xsl:apply-templates mode="high" select="." /></h4>
  </xsl:template>

  <!-- Title of Appendix -->
  <xsl:template match="apt">
    <h3><xsl:apply-templates mode="high" select="." /></h3>
  </xsl:template>

  <xsl:template match="scp">
    <font size="2"><xsl:apply-templates mode="high" select="." /></font>
  </xsl:template>

  <!-- figure (caption) -->
  <xsl:template match="fig|figw">
    <xsl:apply-templates mode="high" select="."/>
  </xsl:template>


  <xsl:template match="fgc|fgb">
    <i> <xsl:apply-templates mode="high" select="."/></i>
  </xsl:template>

  <xsl:template match="art">
      <xsl:apply-templates mode="high" select="." />
  </xsl:template>



  <!-- internal references are linked to the reference section -->
  <xsl:template match="ref">
      <xsl:apply-templates mode="high" select="." />
  </xsl:template>


  <!-- formatting the bibliographic entries -->
  <xsl:template match="bibl">
    <hr/>
    <xsl:apply-templates mode="high" select="." />
  </xsl:template>

  <xsl:template match="bb">
    <p>
      <a>
        <xsl:attribute name="name">
          <xsl:value-of select="@id"/>
        </xsl:attribute>
        [<xsl:number count="bb"/>]
      </a>
      <xsl:apply-templates mode="high" select="." />
    </p>
  </xsl:template>
  
  <xsl:template match="bb/atl">
    <i><xsl:apply-templates mode="high" select="." /></i>
  </xsl:template>

  <xsl:template match="bb/ti">
    <i><xsl:apply-templates mode="high" select="." /></i>
  </xsl:template>

  <xsl:template match="bb/au">
    <b><xsl:apply-templates mode="high" select="." /></b>
  </xsl:template>

  <xsl:template match="bb/*">
    <xsl:apply-templates mode="high" select="." />
  </xsl:template>

  <xsl:template match="bb/*/*">
    <xsl:apply-templates mode="high" select="." />
  </xsl:template>
  
  <xsl:template match="h">
    <h2><xsl:apply-templates mode="high" select="." /></h2>
  </xsl:template>


  <!-- ignore these tags -->

<!--  <xsl:template match="sdata">
  </xsl:template>

  <xsl:template match="//tig/ref">
  </xsl:template>

  <xsl:template match="pn">
  </xsl:template>

  <xsl:template match="fno">
  </xsl:template>//-->


  <!-- these are the direct html tags -->

  <xsl:template match="sec|sec1|ss1|ss2">
    <div>
      <a name="{generate-id(.)}"/>
      <xsl:apply-templates mode="high" select="."/>
    </div>
  </xsl:template>

  <xsl:template match="url">
  	<xsl:apply-templates mode="high" select="."/>
  </xsl:template>

  <xsl:template match="p|ip1|ip2">
    <xsl:apply-templates mode="high" select="."/>
  </xsl:template>

  <xsl:template match="bdy">
    <xsl:apply-templates mode="high" select="."/>
  </xsl:template>

  <xsl:template match="bm">
    <hr/>
    <xsl:apply-templates mode="high" select="."/>
  </xsl:template>

  <xsl:template match="it">
    <i><xsl:apply-templates mode="high" select="."/></i>
  </xsl:template>
 
  <xsl:template match="super">
    <sup><xsl:apply-templates mode="high" select="." /></sup>
  </xsl:template>

  <xsl:template match="sub">
    <sub><xsl:apply-templates mode="high" select="." /></sub>
  </xsl:template>

  <xsl:template match="b">
    <b><xsl:apply-templates mode="high" select="." /></b>
  </xsl:template>

  <xsl:template match="tt|lit">
    <tt><xsl:apply-templates mode="high" select="." /></tt>
  </xsl:template>

  <xsl:template match="h1">
    <h2><xsl:apply-templates mode="high" select="." /></h2>
  </xsl:template>

  <xsl:template match="h2">
    <h3><xsl:apply-templates mode="high" select="." /></h3>
  </xsl:template>

  <xsl:template match="h3">
    <h4><xsl:apply-templates mode="high" select="." /></h4>
  </xsl:template>

  <xsl:template match="h4">
    <h4><xsl:apply-templates mode="high" select="." /></h4>
  </xsl:template>

  <xsl:template match="bullet-list|list|la|lb|lc|ld|ul|l1|l2|l3|l4|l5|l6|l7|l8|l9">
    <ul><xsl:apply-templates mode="high" select="."/></ul>
  </xsl:template>
  
  <xsl:template match="numeric-list">
    <ol><xsl:apply-templates mode="high" select="."/></ol>
  </xsl:template>

  <xsl:template match="li|item|item-bullet|item-diamond|item-mdash">
    <li><xsl:apply-templates mode="high" select="."/></li>
  </xsl:template>

  <xsl:template match="dl">
    <dl><xsl:apply-templates mode="high" select="."/></dl>
  </xsl:template>

  <xsl:template match="dt">
    <dt><xsl:apply-templates mode="high" select="."/></dt>
  </xsl:template>

  <xsl:template match="dd">
    <dd><xsl:apply-templates mode="high" select="."/></dd>
  </xsl:template>


  <!-- Table -->
  

        
   <xsl:template match="*/text()">
  	<script language="php">ob_start("color");</script>
    <xsl:value-of select="translate(.,'&#xE4F8;','-')"/>
  	<script language="php">ob_end_flush();</script>
  </xsl:template>
        
  <xsl:template match="tmath/text()|tf/text()|th/text()">
     <xsl:value-of select="."/>
  </xsl:template>

  <xsl:template match="*">
    <xsl:apply-templates mode="high" select="."/>
  </xsl:template>


</xsl:stylesheet>
