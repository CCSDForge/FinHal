<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
    version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:tei="http://www.tei-c.org/ns/1.0"
    exclude-result-prefixes="tei">
    
    <xsl:output method="html" encoding="utf-8" indent="yes"/>
        
    <xsl:template match="tei:biblStruct">
        <span class="citation-reference">
             <span class="authors-reference">
                 <xsl:apply-templates select="tei:analytic/tei:author"/>  
                 <xsl:apply-templates select="tei:monogr/tei:author"/>
             </span>
             <xsl:apply-templates select="tei:analytic/tei:title" mode="analytic"/>
             <xsl:apply-templates select="tei:monogr/tei:title" mode="monogr"/>
             <xsl:apply-templates select="tei:monogr/tei:title" mode="monogrReportTitle"/> 
             <!--<xsl:apply-templates select="tei:text/tei:back/tei:div/tei:listBibl/tei:biblStruct/tei:monogr/tei:imprint/tei:publisher"/>-->
             <!--<xsl:apply-templates select="tei:text/tei:back/tei:div/tei:listBibl/tei:biblStruct/tei:monogr/tei:meeting/tei:address"/>-->
             <xsl:apply-templates select="tei:monogr/tei:imprint/tei:biblScope"/>
             <xsl:apply-templates select="tei:monogr/tei:imprint/tei:date"/>
        </span>
        <span class="identifiers-reference">
            <xsl:apply-templates select="tei:idno"/>
        </span>
    </xsl:template>
    
    <!-- AUTHOR -->
    <xsl:template match="tei:author">
         <xsl:choose>           
             <xsl:when test="position() = 6">
                 <xsl:text> et al.</xsl:text>
             </xsl:when>
             <xsl:when test="position() > 6">
             </xsl:when>
             <xsl:when test="position() = last() and position() = 2">
                 <xsl:text> and </xsl:text>
                     <xsl:call-template name="persName"/>
             </xsl:when>
             <xsl:when test="position() = last() and position() != 1">
                 <xsl:text>, and </xsl:text>
                 <xsl:call-template name="persName"/>
             </xsl:when>
             <xsl:when test="position() = 1">
                <xsl:call-template name="persName"/>  
             </xsl:when>
             <xsl:otherwise>
                 <xsl:text>, </xsl:text>
                 <xsl:call-template name="persName"/>
             </xsl:otherwise>
         </xsl:choose>
    </xsl:template>
    <xsl:template name="persName">
        <xsl:choose>
            <xsl:when test="string(tei:persName/tei:forename[@type='first']) = 1">
                <xsl:value-of select="tei:persName/tei:forename[@type='first']"/>
                <xsl:text>.</xsl:text>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="substring(tei:persName/tei:forename[@type='first'], 1, 1)"/>
                <xsl:text>.</xsl:text>
            </xsl:otherwise>
        </xsl:choose>
        <xsl:if test="string-length(tei:persName/tei:forename[@type='middle']) != 0">
            <xsl:text> </xsl:text>
            <xsl:choose>
                <xsl:when test="string-length(tei:persName/tei:forename[@type='middle']) = 1">
                    <xsl:value-of select="tei:persName/tei:forename[@type='middle']"/>
                    <xsl:text>.</xsl:text>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="substring(tei:persName/tei:forename[@type='middle'], 1, 1)"/>
                    <xsl:text>.</xsl:text>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:if>
        <xsl:text> </xsl:text>
        <xsl:value-of select="concat(substring(tei:persName/tei:surname,1,1), translate(translate(substring((tei:persName/tei:surname),2), ' ', '-'), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'))"/>         
    </xsl:template>
   
    <!-- REFERENCE TITLE --> 
    <xsl:template match="tei:title" mode="analytic">
        <xsl:if test="string-length(.)!=0">
            <xsl:text>, </xsl:text>
            <span class="title-reference">
                <xsl:value-of select="."/>
            </span>
        </xsl:if>
    </xsl:template>
    
    <!-- CONFERENCE/JOURNAL TITLE -->
    <xsl:template match="tei:title" mode="monogr">
        <xsl:if test="not(@type)">
            <span class="titleEvent-reference">
               <xsl:text>, </xsl:text>
               <span class="titleEvent-reference-value">
                   <xsl:value-of select="."/>
               </span>
            </span>
        </xsl:if>
    </xsl:template>
    
    <!-- REPORT TITLE -->
    <xsl:template match="tei:title" mode="monogrReportTitle">
        <xsl:if test="@type='main'">
            <xsl:text>, </xsl:text>
                <span class="title-reference">
                    <xsl:value-of select="."/>
                </span>
        </xsl:if>
    </xsl:template>
   
    <!-- Publisher -->
    <xsl:template match="tei:publisher">
        <xsl:if test="string-length(.)!=0">
            <xsl:value-of select="."/>
        </xsl:if>
    </xsl:template>
    
    <!-- CONFERENCE ADDRESSE -->
    <xsl:template match="tei:address">
        <xsl:if test="string-length(tei:addrLine)!=0">
            <xsl:value-of select="tei:addrLine"/>
        </xsl:if>
    </xsl:template> 
    
    <!-- VOLUME, ISSUE, PAGES -->
    <xsl:template match="tei:biblScope">
        <xsl:choose>
            <xsl:when test="@unit='volume'">
                <span class="vol-reference">
                    <xsl:text>, </xsl:text>
                    <xsl:text>vol.</xsl:text>
                    <span class="vol-reference-value">
                        <xsl:value-of select="."/>
                    </span>
                </span>
            </xsl:when>
            <xsl:when test="@unit='issue'">
                <span class="issue-reference">
                    <xsl:text>, </xsl:text>
                    <xsl:text>issue.</xsl:text>
                    <span class="issue-reference-value">
                        <xsl:value-of select="../tei:biblScope[@unit='issue']"/>
                    </span>
                </span>
            </xsl:when>
            <xsl:when test="@unit='page' and not(@from) and not(@to)">
                <span class="pages-refernence">
                    <xsl:text>, </xsl:text>
                    <xsl:text>p.</xsl:text>
                        <span class="pages-reference-value">
                            <xsl:value-of select="."/>
                        </span>
                </span>
            </xsl:when>
            <xsl:when test="@unit='page'">
                <span class="pages-reference">
                   <xsl:text>, </xsl:text>
                   <xsl:text>pp.</xsl:text>
                       <span class="pages-reference-value"> 
                           <xsl:value-of select="@from"/>
                           <xsl:text>-</xsl:text>
                           <xsl:value-of select="@to"/>
                       </span>
                </span>
            </xsl:when>
            <xsl:otherwise/>
        </xsl:choose>        
    </xsl:template>
    
    <!-- PUBLICATION DATE -->
    <xsl:template match="tei:date">
        <xsl:if test="@type='published'">
            <xsl:choose>
                <xsl:when test="substring(@when,5,1) = '-'">
                    <span class="date-reference">
                        <xsl:text>, </xsl:text>
                        <span class="date-reference-value">
                            <xsl:value-of select="normalize-space(substring-before(@when,'-'))"/>
                        </span>
                        <xsl:text>.</xsl:text>
                    </span>
                </xsl:when>
                <xsl:otherwise>
                    <span class="date-reference">
                        <xsl:text>, </xsl:text>
                        <span class="date-reference-value">
                            <xsl:value-of select="@when"/>
                        </span>
                        <xsl:text>.</xsl:text>
                    </span>
                </xsl:otherwise>
            </xsl:choose>
          
        </xsl:if>
    </xsl:template>
    
    <!-- IDENTIFIER [DOI, URI] -->
    <xsl:template match="tei:idno">
        <xsl:choose>
            <xsl:when test="@type='doi'">
                <xsl:variable name="doiValue"><xsl:value-of select="."/></xsl:variable>
                <span class="doi-reference">
                    <br/><xsl:text>DOI : </xsl:text>
                    <a class="doi-reference-value" target="_blank" href="{concat('http://dx.doi.org/', $doiValue)}">
                        <xsl:value-of select="$doiValue"/> 
                    </a>
                </span>
            </xsl:when>
            <xsl:when test="@type='uri'">
                <xsl:variable name="uriValue"><xsl:value-of select="."/></xsl:variable>
                <span class="url-reference">
                    <br/><xsl:text>URL : </xsl:text>
                    <a class="url-reference-value" target="_blank" href="{$uriValue}">
                        <xsl:value-of select="$uriValue"/> 
                    </a>
                </span>
            </xsl:when>
            <xsl:otherwise/>
        </xsl:choose>
    </xsl:template>
  
</xsl:stylesheet>