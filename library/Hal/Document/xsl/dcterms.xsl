<?xml version="1.0" encoding="UTF-8"?>
<stylesheet
        xmlns:oai_dcterms="http://www.openarchives.org/OAI/2.0/oai_dcterms/"
        xmlns="http://www.w3.org/1999/XSL/Transform"
        xmlns:dcterms="http://purl.org/dc/terms/"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xmlns:tei="http://www.tei-c.org/ns/1.0"
        xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dcterms/ http://dublincore.org/schemas/xmls/qdc/dcterms.xsd"
        version="1.0">

    <output method="xml" indent="yes" encoding="UTF-8" omit-xml-declaration="yes"/>
    <param name="currentDate" />
    <variable name="currentYear">
        <value-of select="substring-before($currentDate,'-')" />
    </variable>
    <variable name="currentmonthday">
        <value-of select="substring-after($currentDate,'-')" />
    </variable>
    <variable name="currentMonth">
        <value-of select="substring-before($currentmonthday,'-')" />
    </variable>
    <variable name="currentDay">
        <value-of select="substring-after($currentmonthday,'-')" />
    </variable>
    <template match="tei:TEI">
        <oai_dcterms:dcterms>
            <apply-templates select="tei:text/tei:body/tei:listBibl/tei:biblFull"/>
        </oai_dcterms:dcterms>
    </template>
    <template match="tei:biblFull">
        <!-- IDENTIFIANT DU DOCUMENT HAL -->
        <apply-templates select="tei:publicationStmt/tei:idno[@type='halId']"/>
        <!-- URL DU DOCUMENT HAL -->
        <apply-templates select="tei:publicationStmt/tei:idno[@type='halUri']"/>
        <!-- LIEN VERS LE FICHIER PRINCIPAL -->
        <apply-templates select="tei:editionStmt/tei:edition[@type='current']/tei:ref[@type='file']"/>
        <!-- IDENTIFIANTS EXTERNE (DOI, ARXIVID, PUBMED, ENSAM, SCIENCESPO) -->
        <apply-templates select="tei:sourceDesc/tei:biblStruct/tei:idno"/>
        <!-- COLLECTIONS HAL -->
        <apply-templates select="tei:seriesStmt/tei:idno" mode="biblFull"/>
        <!-- TITRE PRINCIPAL DU DOCUMENT -->
        <apply-templates select="tei:titleStmt/tei:title" mode="biblFull"/>
        <!-- AUTEUR -->
        <apply-templates select="tei:titleStmt/tei:author"/>
        <!-- DOMAINES SCIENTIFIQUES ET TYPE DE DOCUMENT -->
        <apply-templates select="tei:profileDesc/tei:textClass/tei:classCode"/>
        <!-- MOT-CLES -->
        <apply-templates select="tei:profileDesc/tei:textClass/tei:keywords/tei:term"/>
        <!-- RESUME -->
        <apply-templates select="tei:profileDesc/tei:abstract"/>
        <!-- COORDONNES GPS -->
        <apply-templates select="tei:sourceDesc/tei:listPlace/tei:place/tei:location/tei:geo"/>
        <!-- DATE DE CREATION/PRODUCIOTN ET DATE DE VISIBILITE (EN CAS EMBARGO) -->
        <apply-templates select="tei:editionStmt/tei:edition[@type='current']/tei:date"/>
        <!-- LANGUE DU DOCUMENT -->
        <apply-templates select="tei:profileDesc/tei:langUsage/tei:language"/>
        <!-- LISENCE SUR LE DOCUMENT -->
        <apply-templates select="tei:publicationStmt/tei:availability/tei:licence"/>
        <apply-templates select="tei:editionStmt/tei:edition[@type='current']/tei:ref[@type='file'][@n=1]" mode="rights" />
        <!-- ELEMENT monogr = ADDRESSE, JOURNAL, TITRE OUVRAGE, CONFERENCE -->
        <apply-templates select="tei:sourceDesc/tei:biblStruct/tei:monogr"/>

        <!-- TODO 
            <dcterms:isPartOf>COLLECTIONS HAL</dcterms:isPartOf>
            <dcterms:rights>RIGHTHOLDER MEDIHAL</dcterms:rights>
            <dcterms:source>SOURCE MEDIHAL</dcterms:source>
            <dcterms:relation>Si URL LIEN</dcterms:relation>
        -->
    </template>

    <!-- element idno -->
    <template match="tei:idno|tei:ref">
        <choose>
            <!-- IDENTIFIANT DU DOCUMENT HAL -->
            <when test="@type='halId'">
                <dcterms:identifier>
                    <value-of select="."/>
                </dcterms:identifier>
            </when>
            <!-- URL DU DOCUMENT HAL -->
            <when test="@type='halUri'">
                <dcterms:identifier>
                    <value-of select="."/>
                </dcterms:identifier>
            </when>
            <!-- LIEN VERS LE FICHIER PRINCIPAL -->
            <when test="@type='file'">
                <dcterms:identifier>
                    <value-of select="@target"/>
                </dcterms:identifier>
            </when>
            <!-- IDENTIFIANTS EXTERNE (DOI, ARXIVID, PUBMED, ENSAM, SCIENCESPO) -->
            <when test="@type='doi'">
                <dcterms:identifier>
                    <text>doi:</text>
                    <value-of select="."/>
                </dcterms:identifier>
            </when>
            <when test="@type='arxiv'">
                <dcterms:identifier>
                    <text>arxiv:</text>
                    <value-of select="."/>
                </dcterms:identifier>
            </when>
            <when test="@type='pubmed'">
                <dcterms:identifier>
                    <text>pubmed:</text>
                    <value-of select="."/>
                </dcterms:identifier>
            </when>
            <when test="@type='ensam' or @type='sciencespo'">
                <dcterms:identifier>
                    <value-of select="."/>
                </dcterms:identifier>
            </when>
            <otherwise/>
        </choose>
    </template>

    <!-- COLLECTIONS HAL -->
    <template match="tei:idno" mode="biblFull">
        <if test="@type='stamp'">
            <dcterms:isPartOf>
                <text>[</text>
                <value-of select="@n"/>
                <text>] </text>
                <value-of select="."/>
            </dcterms:isPartOf>
        </if>
    </template>

    <!-- TITRE PRINCIPAL DU DOCUMENT -->
    <template match="tei:title" mode="biblFull">
        <choose>
            <!-- TITRE -->
            <when test="not(@type='sub')">
                <dcterms:title>
                    <attribute name="xml:lang">
                        <value-of select="@xml:lang"/>
                    </attribute>
                    <value-of select="."/>
                </dcterms:title>
            </when>
            <!-- SOUS TITRE -->
            <when test="@type='sub'">
                <variable name="lang">
                    <value-of select="@xml:lang"/>
                </variable>
                <dcterms:title>
                    <attribute name="xml:lang">
                        <value-of select="$lang"/>
                    </attribute>
                    <value-of select="../tei:title[@xml:lang=$lang][not(@type)]"/>
                    <text> : </text>
                    <value-of select="."/>
                </dcterms:title>
            </when>
        </choose>
    </template>

    <!-- AUTEUR (FORME : "NOM, PRENOM") -->
    <template match="tei:author">
        <dcterms:creator>
            <value-of select="tei:persName/tei:surname"/>
            <text>, </text>
            <value-of select="tei:persName/tei:forename[@type='first']"/>
            <if test="string-length(tei:persName/tei:forename[@type='middle'])!=0">
                <text>, </text>
                <value-of select="./tei:forename[@type='middle']"/>
            </if>
        </dcterms:creator>
    </template>

    <!-- element classCode -->
    <template match="tei:classCode">
        <choose>
            <!-- Mots-clés issus des thesaurus ACM, JEL, MESH -->
            <when test="@scheme='jel' or @scheme='acm' or @scheme='mesh'">
                <dcterms:subject>
                    <value-of select="translate(@scheme,
                                'abcdefghijklmnopqrstuvwxyz',
                                'ABCDEFGHIJKLMNOPQRSTUVWXYZ')" />
                    <text> : </text>
                    <value-of select="." />
                </dcterms:subject>
            </when>
            <!-- DOMAINES SCIENTIFIQUES (FORME : "[code] libellé en") -->
            <when test="@scheme='halDomain'">
                <dcterms:subject>
                    <text>[</text>
                    <value-of select="translate(./@n,
                        'abcdefghijklmnopqrstuvwxyz',
                        'ABCDEFGHIJKLMNOPQRSTUVWXYZ')"/>
                    <text>] </text>
                    <value-of select="."/>
                </dcterms:subject>
            </when>
            <!-- TYPE DE DOCUMENT (ART, VIDEO, ...) -->
            <when test="@scheme='halTypology'">
                <dcterms:type>
                    <value-of select="@n"/>
                </dcterms:type>
            </when>
            <otherwise/>
        </choose>
    </template>

    <!-- MOT-CLES -->
    <template match="tei:term">
        <dcterms:subject>
            <attribute name="xml:lang">
                <value-of select="@xml:lang"/>
            </attribute>
            <value-of select="."/>
        </dcterms:subject>
    </template>

    <!-- RESUME -->
    <template match="tei:abstract">
        <dcterms:abstract>
            <attribute name="xml:lang">
                <value-of select="@xml:lang"/>
            </attribute>
            <value-of select="."/>
        </dcterms:abstract>
    </template>

    <!-- COORDONNES GPS (FORME : "east=LONG; north=LAT") -->
    <template match="tei:geo">
        <dcterms:spatial xsi:type="dcterms:Point">
            <text>Latitude=</text>
            <value-of select="normalize-space(substring-before(.,' '))"/>
            <text>; Longitude=</text>
            <value-of select="normalize-space(substring-after(.,' '))"/>
        </dcterms:spatial>
    </template>

    <!-- DATE DE CREATION/PRODUCIOTN (FORME : "YYYY-MM-DD") -->
    <template match="tei:date">
        <choose>
            <when test="@type='whenProduced'">
                <dcterms:created>
                    <value-of select="."/>
                </dcterms:created>
            </when>
            <when test="@type='whenEndEmbargoed'">
                <dcterms:available>
                    <value-of select="."/>
                </dcterms:available>
            </when>
            <otherwise/>
        </choose>
    </template>

    <!-- LANGUE DU DOCUMENT -->
    <template match="tei:language">
        <choose>
            <when test="string-length(@ident)!=0">
                <dcterms:language>
                    <value-of select="@ident"/>
                </dcterms:language>
            </when>
            <otherwise/>
        </choose>
    </template>

    <!-- LISENCE SUR LE DOCUMENT -->
    <template match="tei:licence">
        <if test="string-length(@target)!=0">
            <dcterms:licence xsi:type="dcterms:URI">
                <value-of select="@target"/>
            </dcterms:licence>
        </if>
    </template>

    <template match="tei:idno|tei:ref" mode="rights">
        <variable name="year">
            <value-of select="substring-before(./tei:date/@notBefore,'-')" />
        </variable>
        <variable name="monthday">
            <value-of select="substring-after(./tei:date/@notBefore,'-')" />
        </variable>
        <variable name="month">
            <value-of select="substring-before($monthday,'-')" />
        </variable>
        <variable name="day">
            <value-of select="substring-after($monthday,'-')" />
        </variable>
        <choose>
            <when test="$currentYear &gt; $year">
        <dcterms:rights>info:eu-repo/semantics/OpenAccess</dcterms:rights>
            </when>
            <when test="$currentYear = $year">
                <choose>
                    <when test="$currentMonth &gt; $month">
                        <dcterms:rights>info:eu-repo/semantics/OpenAccess</dcterms:rights>
                    </when>
                    <when test="$currentMonth = $month">
                        <if test="$currentDay &gt;= $day">
                            <dcterms:rights>info:eu-repo/semantics/OpenAccess</dcterms:rights>
                        </if>
                    </when>
                </choose>
            </when>
        </choose>
    </template>

    <!-- eLement monogr -->
    <template match="tei:monogr">
        <!-- PAYS - VILLE (FORME: "ville, pays") -->
        <call-template name="affiche_address">
            <with-param name="country">
                <value-of select="./tei:country"/>
            </with-param>
            <with-param name="town">
                <value-of select="./tei:settlement"/>
            </with-param>
        </call-template>
        <!-- TITRE OUVRAGE-->
        <apply-templates select="tei:title"/>
        <!-- JOURNAL -->
        <apply-templates select="tei:imprint/tei:publisher"/>
        <!-- CONFERENCE -->
        <apply-templates select="tei:meeting"/>
    </template>

    <!-- TITRE OUVRAGE-->
    <template match="tei:title">
        <dcterms:source>
            <value-of select="."/>
        </dcterms:source>
    </template>

    <!-- JOURNAL -->
    <template match="tei:publisher">
        <dcterms:source>
            <value-of select="."/>
        </dcterms:source>
    </template>

    <!-- CONFERENCE -->
    <template match="tei:meeting">
        <!-- TITRE DE LA CONFERENCE -->
        <apply-templates select="tei:title"/>
        <!-- PAYS - VILLE (FORME: "ville, pays") -->
        <call-template name="affiche_address">
            <with-param name="country">
                <value-of select="./tei:country"/>
            </with-param>
            <with-param name="town">
                <value-of select="./tei:settlement"/>
            </with-param>
        </call-template>
    </template>

    <!-- affiche une adresse -->
    <template name="affiche_address">
        <param name="country"/>
        <param name="town"/>
        <if test="string-length($country)!=0 or string-length($town)!=0">
            <dcterms:coverage>
                <choose>
                    <when test="string-length($country)!=0 and string-length($town)!=0">
                        <value-of select="$town"/><text>, </text><value-of select="$country"/>
                    </when>
                    <when test="string-length($country)!=0">
                        <value-of select="$country"/>
                    </when>
                    <when test="string-length($town)!=0">
                        <value-of select="$town"/>
                    </when>
                    <otherwise/>
                </choose>
            </dcterms:coverage>
        </if>
    </template>
</stylesheet>
