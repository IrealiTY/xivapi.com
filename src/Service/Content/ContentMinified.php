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
        
        foreach ($content as $field => $value) {
            if (is_object($value)) {
                $content->{$field} = isset($value->ID) ? $value->ID : $value;
            }

            if (is_array($value)) {
                foreach ($value as $i => $val) {
                    if (is_object($val)) {
                        $content->{$field}[$i] = $val->ID ?? $val;
                    }
                }
    
                // remove any duplicates
                $content->{$field} = array_unique($content->{$field});
            }
        }
    
        unset($content->GameContentLinks);
        return $content;
    }
}
