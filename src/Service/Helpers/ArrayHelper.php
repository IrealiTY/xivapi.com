<?php

namespace App\Service\Helpers;

trait ArrayHelper
{
    /**
     * Allows you to access a multi-dimensional array using dot notation, eg:
     * [x][y][z] = x.y.z
     */
    public function getArrayValue($array, $key, $default = null)
    {
        $value = $default;
        if (is_array($array) && array_key_exists($key, $array)) {
            $value = $array[$key];
        } else if (is_object($array) && property_exists($array, $key)) {
            $value = $array->$key;
        } else {
            $segments = explode('.', $key);

            foreach ($segments as $segment) {
                if (is_array($array) && array_key_exists($segment, $array)) {
                    $value = $array = $array[$segment];
                } else if (is_object($array) && property_exists($array, $segment)) {
                    $value = $array = $array->$segment;
                } else {
                    $value = $default;
                    break;
                }
            }
        }

        return $value;
    }

    /**
     * Flattens an array into dot notation
     */
    public function flattenArray($array, $prepend = '')
    {
        $results = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && ! empty($value)) {
                $results = array_merge($results, $this->flattenArray($value, $prepend.$key.'.'));
            } else {
                $results[$prepend.$key] = $value;
            }
        }
        return $results;
    }

    /**
     * Describes an array
     * - values that are an array will become []
     * - Values not array will be "best guess"
     */
    public function describeArray($array)
    {
        foreach ($array as $i => $a) {
            if (is_array($a) && !is_numeric($i)) {
                if ($this->isAssoc($a)) {
                    $array[$i] = $this->describeArray($a);
                } else {
                    $array[$i] = "array";
                }
            } else {
                if ($a === true || $a === false) {
                    $array[$i] = "boolean";
                } else if (is_numeric($a)) {
                    $array[$i] = "int";
                } else if (is_float($a)) {
                    $array[$i] = "int";
                } else if (is_string($a)) {
                    $array[$i] = "string";
                } else if (is_object($a)) {
                    $array[$i] = "object";
                } else {
                    $array[$i] = "[?] {$a}";
                }
            }
        }
        
        return $array;
    }
    
    /**
     * Returns true OR false if the array is Associate or not (Numeric)
     */
    public function isAssoc(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
