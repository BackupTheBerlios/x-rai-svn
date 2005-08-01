<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  version="1.0">

  <xsl:strip-space elements="*"/>

  <xsl:param name="basepath"/>
  
  <xsl:output method="xhtml" encoding="utf-8" indent="yes"/>

  <!-- Localization -->

	<xsl:template match="@*" mode="localize">
		<xsl:value-of select="name(.)"/>="<xsl:value-of select="."/>"
	</xsl:template>


        <xsl:template match="*" mode="number">
          <xsl:variable name="n" select="name(.)"/>
          <xsl:value-of select="count(preceding-sibling::*[name()=$n])+1"/>
        </xsl:template>

        <xsl:template match="/|/article" mode="localize"></xsl:template>

        <xsl:template match="*" mode="localize"><xsl:variable name="n"><xsl:apply-templates mode="number" select="."/></xsl:variable><xsl:apply-templates select=".." mode="localize"/>/<xsl:value-of select="concat(name(.),'[',$n,']')"/></xsl:template>

  <!-- HTML code -->

  <xsl:template match="journal">
	  <div style="margin: 0.2cm; border: 1pt solid black; background: #eeeeee; padding: 5pt; ">
		<div style="font-weight: bold; font-size: xx-large;"><xsl:value-of select="title"/></div>
		<xsl:apply-templates select="issue|publisher"/>
	  </div>
	  <div style="margin-left: 0.3cm">
	  	<xsl:apply-templates select="document|section|subcollection"/>
	  </div>
  </xsl:template>

  <xsl:template match="subcollection">
  <div style="border-bottom: 1px dashed #dddddd;">
  <script language="php">begin_subcollection("<xsl:value-of select="@path"/>");</script>
  <xsl:apply-templates/>
  <script language="php">end_subcollection();</script>
  </div>
  </xsl:template>
  
  <xsl:template match="sec1/title"><xsl:apply-templates/></xsl:template>
  <xsl:template match="issue"><b>Issue: </b> <xsl:apply-templates/><br/></xsl:template>
  <xsl:template match="publisher"><b>Publisher: </b> <xsl:apply-templates/><br/></xsl:template>
  <xsl:template match="section"><h2><xsl:apply-templates/></h2></xsl:template>
  <xsl:template match="document"><div><script language="php">begin_document("<xsl:value-of select="@path"/>");</script><xsl:value-of select="."/><script language="php">end_document();</script></div></xsl:template>
  



</xsl:stylesheet>
