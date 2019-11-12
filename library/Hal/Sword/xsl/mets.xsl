<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns="http://www.tei-c.org/ns/1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:output method="xml" encoding="utf-8" indent="yes" />
<xsl:param name="files"/>

<xsl:template match="/art">
<TEI xmlns="http://www.tei-c.org/ns/1.0" xmlns:hal="http://hal.archives-ouvertes.fr">
	<text>
		<body>
            <listBibl>
				<biblFull>
					<titleStmt>
						<xsl:for-each select="fm/bibl/title">
							<title><xsl:attribute name="xml:lang"><xsl:value-of select='@xml:lang' /></xsl:attribute><xsl:value-of select='.'/></title>
						</xsl:for-each>
                        <!-- auteur -->
						<xsl:for-each select="fm/bibl/aug/au">
							<author><xsl:attribute name="role"><xsl:choose><xsl:when test="@ca='yes'"><xsl:text>rsp</xsl:text></xsl:when><xsl:otherwise><xsl:text>aut</xsl:text></xsl:otherwise></xsl:choose></xsl:attribute>
								<xsl:if test="snm">
									<persName><xsl:if test="fnm"><forename type="first"><xsl:value-of select='fnm'/></forename></xsl:if><xsl:if test="mnm"><forename type="middle"><xsl:value-of select='mnm'/></forename></xsl:if><surname><xsl:value-of select='snm'/></surname></persName>
								</xsl:if>
                                <xsl:if test="email"><email><xsl:value-of select='email'/></email></xsl:if>
							</author>
						</xsl:for-each>
					</titleStmt>
					<editionStmt>
						<edition>
							<xsl:call-template name="tokenize">
								<xsl:with-param name="text" select="$files"/>
                                <xsl:with-param name="main" select="1"/>
							</xsl:call-template>
						</edition>
					</editionStmt>
                    <notesStmt>
                        <note type="audience" n="2"/>
                        <note type="popular" n="0"/>
                        <note type="peer" n="1"/>
                    </notesStmt>
					<sourceDesc>
						<biblStruct>
							<analytic>
								<xsl:for-each select="fm/bibl/title">
									<title><xsl:attribute name="xml:lang"><xsl:value-of select='@xml:lang' /></xsl:attribute><xsl:value-of select='.'/></title>
								</xsl:for-each>
                                <!-- auteur -->
                                <xsl:for-each select="fm/bibl/aug/au">
                                    <author><xsl:attribute name="role"><xsl:choose><xsl:when test="@ca='yes'"><xsl:text>rsp</xsl:text></xsl:when><xsl:otherwise><xsl:text>aut</xsl:text></xsl:otherwise></xsl:choose></xsl:attribute>
                                        <xsl:if test="snm">
                                            <persName><xsl:if test="fnm"><forename type="first"><xsl:value-of select='fnm'/></forename></xsl:if><xsl:if test="mnm"><forename type="middle"><xsl:value-of select='mnm'/></forename></xsl:if><surname><xsl:value-of select='snm'/></surname></persName>
                                        </xsl:if>
                                        <xsl:if test="email"><email><xsl:value-of select='email'/></email></xsl:if>
                                    </author>
                                </xsl:for-each>
							</analytic>
							<monogr>
                                <title level="j"><xsl:value-of select="fm/bibl/source"/></title>
								<imprint>
                                    <xsl:if test="fm/bibl/volume">
                                    	<biblScope unit="volume"><xsl:value-of select="fm/bibl/volume"/></biblScope>
                                    </xsl:if>
                                    <xsl:if test="fm/bibl/issue">
                                    	<biblScope unit="issue"><xsl:value-of select="fm/bibl/issue"/></biblScope>
                                    </xsl:if>
                                    <xsl:if test="fm/bibl/fpage">
                                    	<biblScope unit="pp"><xsl:value-of select='fm/bibl/fpage' /></biblScope>
                                    </xsl:if>
                                    <xsl:if test="fm/bibl/pubdate">
                                    	<date type="datePub"><xsl:value-of select="substring(fm/bibl/pubdate, 0, 4)"/></date>
                                    </xsl:if>
								</imprint>
							</monogr>
                            <xsl:if test="fm/bibl/xrefbib/pubidlist/pubid[@idtype='doi']">
                                <idno type="doi"><xsl:value-of select="fm/bibl/xrefbib/pubidlist/pubid[@idtype='doi']" /></idno>
                            </xsl:if>
                            <xsl:if test="fm/bibl/xrefbib/pubidlist/pubid[@idtype='pmpid']">
                                <idno type="pubmed"><xsl:value-of select="fm/bibl/xrefbib/pubidlist/pubid[@idtype='pmpid']" /></idno>
                            </xsl:if>
						</biblStruct>
					</sourceDesc>
					<profileDesc>
                        <langUsage>
                            <language><xsl:attribute name="ident"><xsl:choose><xsl:when test="@xml:lang"><xsl:value-of select='@xml:lang' /></xsl:when><xsl:otherwise>en</xsl:otherwise></xsl:choose></xsl:attribute></language>
                        </langUsage>
                        <textClass>
                       		<keywords scheme="author">
                       		<xsl:for-each select="front/article-meta/kwd-group/kwd">
                       			<term><xsl:attribute name="xml:lang"><xsl:value-of select='../@xml:lang' /></xsl:attribute><xsl:value-of select='.' /></term>
                       		</xsl:for-each>
                       		</keywords>
                            <xsl:if test="front/journal-meta/journal-title-group/journal-title='Astronomy &amp; Astrophysics'">
                                <classCode scheme="halDomain" n="phys.astr"/>
                            </xsl:if>
                       		<classCode scheme="halTypology" n="ART"/>
                       	</textClass>
                       	<xsl:for-each select="fm/abs/sec">
                       		<abstract><xsl:attribute name="xml:lang"><xsl:value-of select='@xml:lang' /></xsl:attribute><xsl:value-of select='.'/></abstract>
                       	</xsl:for-each>
                    </profileDesc>
                 </biblFull>
			</listBibl>
		</body>
	</text>
</TEI>
</xsl:template>

<xsl:template name="tokenize">
	<xsl:param name="text"/>
	<xsl:param name="main"/>
	<xsl:if test="string-length($text)">
		<ref type="file" subtype="author"><xsl:attribute name="n"><xsl:value-of select="$main"/></xsl:attribute><xsl:attribute name="target"><xsl:value-of select="substring-before(concat($text,','),',')" /></xsl:attribute></ref>
		<xsl:call-template name="tokenize"><xsl:with-param name="text" select="substring-after($text, ',')"/><xsl:with-param name="main" select="0"/></xsl:call-template>
	</xsl:if>
</xsl:template>

</xsl:stylesheet>