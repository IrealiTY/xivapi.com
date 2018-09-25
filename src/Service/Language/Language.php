<?php

namespace App\Service\Language;

use Symfony\Component\HttpFoundation\Request;

class Language
{
    private static $lang = LanguageList::DEFAULT;
    
    /**
     * Set the language for the API
     */
    public static function set(Request $request): void
    {
        self::$lang = $request->get('language') ?? LanguageList::DEFAULT;
        
        if (!in_array(self::$lang, LanguageList::LANGUAGES)) {
            self::$lang = LanguageList::DEFAULT;
        }
    }

    /**
     * Return the current language
     */
    public static function current(): string
    {
        return self::$lang;
    }
    
    /**
     * Convert an array of data into a specific language
     */
    public static function handle($data, string $language = null)
    {
        $language = $language ?: self::$lang;
        $language = substr(strtolower($language), 0, 2);

        if (!in_array($language, LanguageList::LANGUAGES)) {
            $language = LanguageList::LANGUAGES[0];
        }
    
        foreach ($data as $i => $value) {
            if (is_array($value)) {
                $data[$i] = self::handle($value);
            } else {
                $postfix = '_'. $language;
    
                if (strpos($i, $postfix) !== false) {
                    $data[str_ireplace($postfix, null, $i)] = $value;
                }
            }
        }
        
        return $data;
    }
}
