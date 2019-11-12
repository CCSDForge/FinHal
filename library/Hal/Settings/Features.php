<?php

class Hal_Settings_Features
{
    /**
     * Is doc submission or modification activated
     * @return bool
     */
    public static function hasDocSubmit(): bool
    {
        return self::hasFeature('FEATURES_DOC_SUBMIT');
    }

    public static function hasFeature($featureName): bool
    {
        if (defined($featureName)) {
            $featureConstValue = constant($featureName);
            if (is_bool($featureConstValue)) {
                return $featureConstValue;
            }
        }
        return true; // Every feature is allowed by default
    }

    /**
     * Is document cache activated
     * @return bool
     */
    public static function hasDocCache(): bool
    {
        return self::hasFeature('FEATURES_DOC_CACHE');
    }


    /**
     * Is doc moderation allowed
     * @return bool
     */
    public static function hasDocModerate(): bool
    {
        return self::hasFeature('FEATURES_DOC_MODERATE');
    }

    /**
     * Get downtime URL for maintenance and downtime information
     * @return string
     */
    public static function getDowntimeUrl(): string
    {
        if ((defined('FEATURES_DOWNTIME_URL')) && (FEATURES_DOWNTIME_URL != '')) {
            if (filter_var(FEATURES_DOWNTIME_URL, FILTER_VALIDATE_URL) !== false) {
                return FEATURES_DOWNTIME_URL;
            }
        }
        return '';
    }

}
