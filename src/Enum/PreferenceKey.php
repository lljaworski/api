<?php

declare(strict_types=1);

namespace App\Enum;

enum PreferenceKey: string
{
    case SITE_NAME = 'site_name';
    case SITE_DESCRIPTION = 'site_description';
    case SITE_URL = 'site_url';
    case MAINTENANCE_MODE = 'maintenance_mode';
    case DEFAULT_LANGUAGE = 'default_language';
    case DEFAULT_TIMEZONE = 'default_timezone';
    case MAX_UPLOAD_SIZE = 'max_upload_size';
    case ITEMS_PER_PAGE = 'items_per_page';
    case ENABLE_REGISTRATION = 'enable_registration';
    case ENABLE_API = 'enable_api';
    case INVOICE_NUMBER_FORMAT = 'invoice_number_format';

    public function getLabel(): string
    {
        return match ($this) {
            self::SITE_NAME => 'Site Name',
            self::SITE_DESCRIPTION => 'Site Description',
            self::SITE_URL => 'Site URL',
            self::MAINTENANCE_MODE => 'Maintenance Mode',
            self::DEFAULT_LANGUAGE => 'Default Language',
            self::DEFAULT_TIMEZONE => 'Default Timezone',
            self::MAX_UPLOAD_SIZE => 'Maximum Upload Size',
            self::ITEMS_PER_PAGE => 'Items Per Page',
            self::ENABLE_REGISTRATION => 'Enable Registration',
            self::ENABLE_API => 'Enable API',
            self::INVOICE_NUMBER_FORMAT => 'Invoice Number Format',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SITE_NAME => 'The name of the website or application',
            self::SITE_DESCRIPTION => 'A brief description of the website',
            self::SITE_URL => 'The base URL of the website',
            self::MAINTENANCE_MODE => 'Enable/disable maintenance mode',
            self::DEFAULT_LANGUAGE => 'Default language for the application',
            self::DEFAULT_TIMEZONE => 'Default timezone for the application',
            self::MAX_UPLOAD_SIZE => 'Maximum file upload size in MB',
            self::ITEMS_PER_PAGE => 'Default number of items per page in lists',
            self::ENABLE_REGISTRATION => 'Allow new user registrations',
            self::ENABLE_API => 'Enable API endpoints',
            self::INVOICE_NUMBER_FORMAT => 'Format string for invoice numbering. Must contain {year}, {month}, and {number} placeholders. Example: {year}/{month}/{number}',
        };
    }
}
