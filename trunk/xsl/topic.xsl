<?xml version="1.0" encoding="iso-8859-1"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">  
  <xsl:output encoding="iso-8859-15" method="xhtml" omit-xml-declaration="yes"/>

<xsl:template match="/inex_topic">
	<h1>Topic n°<xsl:value-of select="@topic_id"/> (<xsl:value-of select="@query_type"/>)</h1>
	<div>
		<div style='border: 1pt solid black;'>
   		<xsl:apply-templates select="title"/>
			<div style='padding: 3pt'>
		   	<xsl:apply-templates select="description|keywords|narrative"/>
			</div>
		</div>
	</div>
</xsl:template>

<xsl:template match="title">
<div style='padding: 3pt; border-bottom: 1pt solid black; background: lightgray'>
<b><xsl:value-of select="name()"/></b>: <code><xsl:value-of select="."/></code>
</div>
</xsl:template>

<xsl:template match="keywords">
<div>
<b><xsl:value-of select="name()"/></b>: <code><xsl:value-of select="."/></code>
</div>
</xsl:template>

<xsl:template match="narrative|description">
<div>
<b><xsl:value-of select="name()"/></b>: <xsl:value-of select="."/>
</div>
</xsl:template>

</xsl:stylesheet>
