<xsl:stylesheet xmlns:tei="http://www.tei-c.org/ns/1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" exclude-result-prefixes="tei" version="1.0"
        >

    <xsl:output method="html" encoding="utf-8" indent="yes" />

    <xsl:template match="/TEI">
        <div class="row" id="document">
            <div class="span9">
                <xsl:apply-templates select="truc"/>
            </div>

            <div class="span3">

            </div>
        </div>
    </xsl:template>

    <xsl:template match="truc">
        <xsl:value-of select="title" />
        Titre de l'article
    </xsl:template>

</xsl:stylesheet>
