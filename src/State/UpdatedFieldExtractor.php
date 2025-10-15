<?php

declare(strict_types=1);

namespace App\State;

/**
 * Utility for extracting updated fields from API Platform request context.
 * Reduces code duplication in State Processors for PATCH operations.
 */
final class UpdatedFieldExtractor
{
    /**
     * Extract updated field value from request context.
     * Returns the entity's value if the field was provided in the request,
     * or null if the field was not included (for PATCH operations).
     */
    public static function extract(object $entity, array $context, string $fieldName, string $getterMethod): mixed
    {
        $isPatchRequest = isset($context['input_class']) && 
                          isset($context['operation']) &&
                          $context['operation']->getMethod() === 'PATCH';
        
        if (!$isPatchRequest) {
            // For non-PATCH operations, always include all fields
            return $entity->$getterMethod();
        }
        
        // For PATCH operations, check if the field was included in the request
        $requestData = $context['request']?->getContent();
        
        if ($requestData === null || $requestData === '') {
            return null;
        }
        
        try {
            $decodedData = json_decode($requestData, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
        
        // Convert camelCase to snake_case for JSON field names
        $jsonFieldName = self::camelCaseToSnakeCase($fieldName);
        
        // If the field exists in the request data, return the entity's value
        // If not, return null to indicate it shouldn't be updated
        return array_key_exists($jsonFieldName, $decodedData) ? $entity->$getterMethod() : null;
    }
    
    /**
     * Batch extract multiple fields at once.
     * Returns an array with field names as keys and extracted values.
     */
    public static function extractMultiple(object $entity, array $context, array $fieldMappings): array
    {
        $extracted = [];
        
        foreach ($fieldMappings as $fieldName => $getterMethod) {
            $extracted[$fieldName] = self::extract($entity, $context, $fieldName, $getterMethod);
        }
        
        return $extracted;
    }
    
    /**
     * Convert camelCase to snake_case for JSON field matching.
     */
    private static function camelCaseToSnakeCase(string $input): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $input));
    }
}