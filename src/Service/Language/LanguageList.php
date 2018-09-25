<?php

namespace App\Service\Language;

class LanguageList
{
    const DEFAULT = 'en';

    const LANGUAGES = [
        'en', 'de', 'fr', 'ja', 'kr', 'cn'
    ];
    
    public static function get($language)
    {
        if (in_array($language, self::LANGUAGES)) {
            return $language;
        }

        return self::DEFAULT;
    }
}
