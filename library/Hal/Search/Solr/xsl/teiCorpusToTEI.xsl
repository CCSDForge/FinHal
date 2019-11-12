<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:tei="http://www.tei-c.org/ns/1.0" xmlns="http://www.tei-c.org/ns/1.0" version="1.0">
	<xsl:output method="xml"/>
	
	<xsl:key name="org" match="/tei:teiCorpus/tei:TEI/tei:text/tei:back/tei:listOrg/tei:org" use="@xml:id"/>
	
	<xsl:template match="/tei:teiCorpus">
		<TEI>
			<xsl:apply-templates select="tei:teiHeader"/>
			<text>
				<body>
					<listBibl>
						<xsl:for-each select="//tei:biblFull">
							<xsl:apply-templates select="."/>
						</xsl:for-each>
					</listBibl>
				</body>
				<back>
					<!-- Ajout des structures -->
					<xsl:call-template name="traite_list_org">
						<xsl:with-param name="type_org">structures</xsl:with-param>
					</xsl:call-template>
					<!-- Ajout des projets -->
					<xsl:call-template name="traite_list_org">
						<xsl:with-param name="type_org">projects</xsl:with-param>
					</xsl:call-template>
					
				</back>
			</text>
		</TEI>
	</xsl:template>
	
	
	<!-- Traitement des listOrg avec suppression des doublons -->
	<xsl:template name="traite_list_org">
		<xsl:param name="type_org"/>
		<xsl:if test="string-length(//tei:listOrg[@type=$type_org])!=0">
			<listOrg>
				<xsl:attribute name="type">
					<xsl:value-of select="$type_org"/>
				</xsl:attribute>
				<xsl:for-each select="//tei:listOrg[@type=$type_org]/tei:org[generate-id() = generate-id(key('org',@xml:id)[1])]">
					<xsl:apply-templates select="."/>
				</xsl:for-each>
			</listOrg>
		</xsl:if>
	</xsl:template>
	
	<!-- par défaut, on recopie l'élément avec tous les attributs et tous les noeuds-->
	<xsl:template match="* | @*">
		<xsl:copy>
			<xsl:copy-of select="@*"/>
			<xsl:apply-templates/>
		</xsl:copy>
	</xsl:template>
</xsl:stylesheet>
