<?php

/**
 * Hal_Document Dates Settings
 */

Class Hal_Document_Settings_Dates extends Hal_Document_Settings
{

    const TYPE_DATE = 'date';
    const TYPE_WRITING_DATE = 'writingDate';
    const TYPE_SUBMITTED_DATE = 'submittedDate';
    const TYPE_CONFERENCE_START_DATE = 'conferenceStartDate';

    /**
     * Configures an order of type of dates to use selecting a publication Date
     * Use specific type or fallback to GENERIC_DOC_TYPE
     * GENERIC_DOC_TYPE is common type for all DOC TYPES
     * @var array
     */
    protected static $_publicationDateConf =
        [
            'GENERIC_DOC_TYPE' => [
                self::TYPE_DATE,
                self::TYPE_WRITING_DATE,
                self::TYPE_SUBMITTED_DATE],
            'COMM' => [
                self::TYPE_DATE,
                self::TYPE_CONFERENCE_START_DATE,
                self::TYPE_WRITING_DATE,
                self::TYPE_SUBMITTED_DATE],
            'POSTER' => [
                self::TYPE_CONFERENCE_START_DATE,
                self::TYPE_DATE,
                self::TYPE_WRITING_DATE,
                self::TYPE_SUBMITTED_DATE],
            'PRESCONF' => [
                self::TYPE_CONFERENCE_START_DATE,
                self::TYPE_DATE,
                self::TYPE_WRITING_DATE,
                self::TYPE_SUBMITTED_DATE],
            'UNDEFINED' => [
                self::TYPE_DATE,
                self::TYPE_WRITING_DATE,
                self::TYPE_SUBMITTED_DATE],
        ];





    /**
     * @param string $documentType
     * @return array
     */
    static public function getPublicationDateMethods(string $documentType): array
    {
        $publicationDateConfig = self::getPublicationDateConf();

        if (!in_array($documentType, $publicationDateConfig)) {
            $documentType = 'GENERIC_DOC_TYPE';
        }
        return $publicationDateConfig[$documentType];

    }

    /**
     * @return array
     */
    static public function getPublicationDateConf()
    {
        return self::$_publicationDateConf;
    }

}