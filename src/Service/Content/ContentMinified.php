<?php

namespace App\Service\Content;

class ContentMinified
{
    /**
     * Provides a minified version of  specific piece of content
     */
    public static function mini($content)
    {
        if (!$content) {
            return $content;
        }
        
        $content = json_decode(json_encode($content), true);

        unset($content['GameContentLinks']);
        foreach ($content as $field => $value) {
            if (is_array($value)) {
                $value = $value['ID'] ?? $value;
            }

            if (is_array($value)) {
                foreach ($value as $i => $val) {
                    $value[$i] = $val['ID'] ?? null;
                }
            }

            $content[$field] = $value;
        }
    
        $content = json_decode(json_encode($content));

        return $content;
    }
}
