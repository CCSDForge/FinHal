<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns="http://www.tei-c.org/ns/1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:output method="xml" encoding="utf-8" indent="yes" />
<xsl:param name="files"/>

<xsl:template match="/article">
<TEI xmlns="http://www.tei-c.org/ns/1.0" xmlns:hal="http://hal.archives-ouvertes.fr">
	<text>
		<body>
            <listBibl>
				<biblFull>
					<editionStmt>
						<edition>
							<xsl:call-template name="tokenize">
								<xsl:with-param name="text" select="$files"/>
								<xsl:with-param name="embargo" select="front/article-meta/pub-date[@date-type='open-access']"/>
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
								<xsl:for-each select="front/article-meta/title-group/article-title">
									<title><xsl:attribute name="xml:lang"><xsl:value-of select='@xml:lang' /></xsl:attribute><xsl:value-of select='.'/></title>
								</xsl:for-each>
								<xsl:for-each select="front/article-meta/title-group/subtitle">
									<title type="sub"><xsl:attribute name="xml:lang"><xsl:value-of select='@xml:lang' /></xsl:attribute><xsl:value-of select='.'/></title>
								</xsl:for-each>
                                <!-- auteur sans collaboration -->
                                <xsl:for-each select="front/article-meta/contrib-group[@content-type='authors' or not(@content-type)]/contrib[@contrib-type='author']/name">
                                    <xsl:if test="surname">
                                        <author>
                                            <xsl:attribute name="role"><xsl:choose><xsl:when test="../@corresp='yes'">crp</xsl:when><xsl:otherwise>aut</xsl:otherwise></xsl:choose></xsl:attribute>
                                            <persName><xsl:if test="given-names"><forename type="first"><xsl:value-of select='given-names'/></forename></xsl:if><surname><xsl:value-of select='surname'/></surname></persName>
                                            <xsl:variable name="mailId"><xsl:value-of select='../xref[@ref-type="corresp"]/@rid' /></xsl:variable>
                                            <xsl:if test="../../../author-notes/corresp/@id=$mailId">
                                                <email><xsl:value-of select='../../../author-notes/corresp[@id=$mailId]/email' /></email>
                                            </xsl:if>
                                        </author>
                                    </xsl:if>
                                </xsl:for-each>
                                <xsl:for-each select="front/article-meta/contrib-group[@content-type='authors' or not(@content-type)]/contrib[@contrib-type='author']/name-alternatives/name">
                                    <xsl:if test="surname">
                                        <author>
                                            <xsl:attribute name="role"><xsl:choose><xsl:when test="../@corresp='yes'">crp</xsl:when><xsl:otherwise>aut</xsl:otherwise></xsl:choose></xsl:attribute>
                                            <persName><xsl:if test="given-names"><forename type="first"><xsl:value-of select='given-names'/></forename></xsl:if><surname><xsl:value-of select='surname'/></surname></persName>
                                            <xsl:variable name="mailId"><xsl:value-of select='../xref[@ref-type="corresp"]/@rid' /></xsl:variable>
                                            <xsl:if test="../../../../author-notes/corresp/@id=$mailId">
                                                <email><xsl:value-of select='../../../../author-notes/corresp[@id=$mailId]/email' /></email>
                                            </xsl:if>
                                        </author>
                                    </xsl:if>
                                </xsl:for-each>
                                <!-- auteur sous une(des) collaboration(s) -->
                                <xsl:for-each select="front/article-meta/contrib-group[@content-type='authors' or not(@content-type)]/contrib[@contrib-type='collab']/collab/contrib-group[@content-type='authors']/contrib[@contrib-type='author']/name">
                                    <xsl:if test="surname">
                                        <author>
                                            <xsl:attribute name="role"><xsl:choose><xsl:when test="../@corresp='yes'">crp</xsl:when><xsl:otherwise>aut</xsl:otherwise></xsl:choose></xsl:attribute>
                                            <persName><xsl:if test="given-names"><forename type="first"><xsl:value-of select='given-names'/></forename></xsl:if><surname><xsl:value-of select='surname'/></surname></persName>
                                            <xsl:variable name="mailId"><xsl:value-of select='../xref[@ref-type="corresp"]/@rid' /></xsl:variable>
                                            <xsl:if test="../../../author-notes/corresp/@id=$mailId">
                                                <email><xsl:value-of select='../../../author-notes/corresp[@id=$mailId]/email' /></email>
                                            </xsl:if>
                                        </author>
                                    </xsl:if>
                                </xsl:for-each>
							</analytic>
							<monogr>
								<xsl:if test="front/journal-meta/issn[@pub-type='ppub']">
									<idno type="issn"><xsl:value-of select="front/journal-meta/issn[@pub-type='ppub']"/></idno>
								</xsl:if>
								<xsl:if test="front/journal-meta/issn[@pub-type='epub']">
									<idno type="eissn"><xsl:value-of select="front/journal-meta/issn[@pub-type='epub']"/></idno>
								</xsl:if>
                                <title level="j"><xsl:value-of select="front/journal-meta/journal-title-group/journal-title"/></title>
								<imprint>
                                    <xsl:if test="front/journal-meta/publisher/publisher-name">
                                        <publisher><xsl:value-of select="front/journal-meta/publisher/publisher-name"/></publisher>
                                    </xsl:if>
                                    <xsl:if test="front/article-meta/volume">
                                        <biblScope unit="volume"><xsl:value-of select="front/article-meta/volume"/></biblScope>
                                    </xsl:if>
                                    <xsl:if test="front/article-meta/volume-id">
                                    	<biblScope unit="volume"><xsl:value-of select="front/article-meta/volume-id"/></biblScope>
                                    </xsl:if>
                                    <xsl:if test="front/article-meta/issue">
                                        <biblScope unit="issue"><xsl:value-of select="front/article-meta/issue"/></biblScope>
                                    </xsl:if>
                                    <xsl:if test="front/article-meta/elocation-id">
                                    	<biblScope unit="pp"><xsl:value-of select='front/article-meta/elocation-id' /></biblScope>
                                    </xsl:if>
                                    <xsl:if test="front/article-meta/fpage">
                                    	<biblScope unit="pp">
                                            <xsl:if test="front/article-meta/fpage">
                                                <xsl:value-of select='front/article-meta/fpage' />
                                            </xsl:if>
                                            <xsl:if test="front/article-meta/lpage">
                                                <xsl:if test="front/article-meta/fpage">
                                                    <xsl:text>-</xsl:text>
                                                </xsl:if>
                                                <xsl:value-of select='front/article-meta/lpage' />
                                            </xsl:if>
                                    		<xsl:if test="front/article-meta/page-range">
                                                <xsl:value-of select='front/article-meta/page-range' />
                                            </xsl:if>
                                    	</biblScope>
                                    </xsl:if>
                                    <xsl:if test="front/article-meta/pub-date[@pub-type='ppub']">
                                        <date type="datePub"><xsl:value-of select="front/article-meta/pub-date[@pub-type='ppub']/year"/><xsl:if test="string-length(front/article-meta/pub-date[@pub-type='ppub']/month)=2">-<xsl:value-of select="front/article-meta/pub-date[@pub-type='ppub']/month"/><xsl:if test="string-length(front/article-meta/pub-date[@pub-type='ppub']/day)=2">-<xsl:value-of select="front/article-meta/pub-date[@pub-type='ppub']/day"/></xsl:if></xsl:if></date>
                                    </xsl:if>
                                    <xsl:if test="front/article-meta/pub-date[@pub-type='collection'] and not(front/article-meta/pub-date[@pub-type='ppub'])">
                                        <date type="datePub"><xsl:value-of select="front/article-meta/pub-date[@pub-type='collection']/year"/><xsl:if test="string-length(front/article-meta/pub-date[@pub-type='collection']/month)=2">-<xsl:value-of select="front/article-meta/pub-date[@pub-type='collection']/month"/><xsl:if test="string-length(front/article-meta/pub-date[@pub-type='collection']/day)=2">-<xsl:value-of select="front/article-meta/pub-date[@pub-type='collection']/day"/></xsl:if></xsl:if></date>
                                    </xsl:if>
                                    <xsl:if test="front/article-meta/pub-date[@pub-type='epub']">
                                    	<date type="dateEpub"><xsl:value-of select="front/article-meta/pub-date[@pub-type='epub']/year"/><xsl:if test="string-length(front/article-meta/pub-date[@pub-type='epub']/month)=2">-<xsl:value-of select="front/article-meta/pub-date[@pub-type='epub']/month"/><xsl:if test="string-length(front/article-meta/pub-date[@pub-type='epub']/day)=2">-<xsl:value-of select="front/article-meta/pub-date[@pub-type='epub']/day"/></xsl:if></xsl:if></date>
                                    </xsl:if>
								</imprint>
							</monogr>
                            <xsl:if test="front/article-meta/article-id[@pub-id-type='doi']">
                                <idno type="doi"><xsl:value-of select="front/article-meta/article-id[@pub-id-type='doi']" /></idno>
                            </xsl:if>
						</biblStruct>
					</sourceDesc>
					<profileDesc>
                        <langUsage>
                            <language><xsl:attribute name="ident"><xsl:choose><xsl:when test="@xml:lang"><xsl:value-of select='@xml:lang' /></xsl:when><xsl:otherwise>en</xsl:otherwise></xsl:choose></xsl:attribute></language>
                        </langUsage>
                        <textClass>
                            <xsl:if test="front/article-meta/kwd-group">
                                <keywords scheme="author">
                       		    <xsl:for-each select="front/article-meta/kwd-group/kwd">
                       			    <term><xsl:attribute name="xml:lang"><xsl:value-of select='../@xml:lang' /></xsl:attribute><xsl:value-of select='.' /></term>
                       		    </xsl:for-each>
                       		    </keywords>
                            </xsl:if>
                            <xsl:if test="front/journal-meta/journal-title-group/journal-title='Astronomy &amp; Astrophysics'">
                                <classCode scheme="halDomain" n="phys.astr"/>
                            </xsl:if>
                            <xsl:if test="front/journal-meta/publisher/publisher-name='BioMed Central'">
                                <classCode scheme="halDomain" n="sdv"/>
                            </xsl:if>
                            <xsl:if test="contains(front/journal-meta/journal-title-group/journal-subtitle, 'INRA')">
                                <classCode scheme="halDomain" n="sdv"/>
                            </xsl:if>
                            <xsl:if test="contains(front/journal-meta/journal-title-group/journal-subtitle, 'INRIA')">
                                <classCode scheme="halDomain" n="info"/>
                            </xsl:if>
                            <xsl:if test="contains(front/journal-meta/journal-title-group/abbrev-journal-title, 'IFP')">
                                <classCode scheme="halDomain" n="phys"/>
                            </xsl:if>
                       		<classCode scheme="halTypology" n="ART"/>
                       	</textClass>
                        <xsl:choose>
                            <xsl:when test="front/article-meta/abstract">
                                <xsl:for-each select="front/article-meta/abstract">
                                    <abstract><xsl:attribute name="xml:lang"><xsl:value-of select='@xml:lang' /></xsl:attribute><xsl:value-of select='.'/></abstract>
                                </xsl:for-each>
                            </xsl:when>
                            <xsl:otherwise>
                                <abstract xml:lang="en">No abstract available</abstract>
                            </xsl:otherwise>
                        </xsl:choose>
                        <xsl:if test="front/article-meta/contrib-group[@content-type='authors']/contrib[@contrib-type='collab']/collab">
                            <particDesc><org type="consortium"><xsl:value-of select="front/article-meta/contrib-group[@content-type='authors']/contrib[@contrib-type='collab']/collab/text()"/></org></particDesc>
                        </xsl:if>
                    </profileDesc>
                 </biblFull>
			</listBibl>
		</body>
	</text>
</TEI>
</xsl:template>

<xsl:template name="tokenize">
	<xsl:param name="text"/>
	<xsl:param name="embargo"/>
	<xsl:param name="main"/>
	<xsl:if test="string-length($text)">
        <xsl:variable name="filename" select="substring-before(concat($text,','),',')" />
        <xsl:variable name="listfiles" select="substring-after($text, ',')" />

        <xsl:variable name="length" select="string-length($filename)"/>
        <ref subtype="publisherPaid">
            <xsl:attribute name="type">
                <xsl:choose>
                    <xsl:when test="substring($filename,($length)-2) = 'pdf'">file</xsl:when>
                    <xsl:otherwise>src</xsl:otherwise>
                </xsl:choose>
            </xsl:attribute>
            <xsl:attribute name="n"><xsl:value-of select="$main"/></xsl:attribute>
            <xsl:attribute name="target"><xsl:value-of select="$filename" /></xsl:attribute>
        <xsl:if test="$embargo/year">
            <date><xsl:attribute name="notBefore"><xsl:value-of select="$embargo/year"/><xsl:if test="string-length($embargo/month)='2'">-<xsl:value-of select="$embargo/month"/><xsl:if test="string-length($embargo/day)='2'">-<xsl:value-of select="$embargo/day"/></xsl:if></xsl:if></xsl:attribute></date>
        </xsl:if>
        </ref>
		<xsl:call-template name="tokenize"><xsl:with-param name="text" select="$listfiles"/><xsl:with-param name="embargo" select="$embargo"/><xsl:with-param name="main" select="0"/></xsl:call-template>
	</xsl:if>
</xsl:template>

</xsl:stylesheet>