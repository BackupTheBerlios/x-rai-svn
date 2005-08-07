<?xml version="1.0" encoding="iso-8859-1"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">  
  <xsl:output encoding="iso-8859-15" indent="no" method="xhtml" omit-xml-declaration="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"/>
  <xsl:strip-space elements="*"/>
  <xsl:param name="baseurl"/>
  
  <xsl:template match="/">
    <html><head><title>Tree view</title>
      <link rel="stylesheet" href="{$baseurl}css/tree.css" />
      <script language="javascript">
        baseurl = "<xsl:value-of select="$baseurl"/>";
      </script>
      <script src="{$baseurl}js/tree.js" language="javascript"/>
    </head>
    <body>
     <div id='root'><xsl:apply-templates/></div>
    </body>
    </html>
  </xsl:template>
  
  <xsl:template match="text()">
  </xsl:template>
            
            
  <xsl:template match="*">
    <xsl:variable name="path"><xsl:apply-templates select="." mode="localize"/></xsl:variable>
    <xsl:variable name="name" select="name(.)"/>
    <xsl:variable name="rank" select="count(preceding-sibling::*[name()=$name])+1"/>
      <div class="in" name="{name(.)}[{$rank}]">
   <xsl:choose>
     <xsl:when test="*">
      <span><img src="{$baseurl}img/tree_plus.png" onclick="toggle_node(this)"/><a onclick="goto_path(this)" href="#"><xsl:value-of select="name(.)"/></a></span>
     <div class="hidden"><xsl:apply-templates/></div>
    </xsl:when>
    <xsl:otherwise>
      <span><img src="{$baseurl}img/leaf.png"/><a onclick="goto_path(this)" href="#"><xsl:value-of select="name(.)"/></a></span>
    </xsl:otherwise>
    </xsl:choose>
    </div>
  </xsl:template>
  

</xsl:stylesheet>