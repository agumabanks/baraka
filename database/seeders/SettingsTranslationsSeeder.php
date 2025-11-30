<?php

namespace Database\Seeders;

use App\Models\Translation;
use Illuminate\Database\Seeder;

class SettingsTranslationsSeeder extends Seeder
{
    /**
     * Seed translations for all settings pages.
     * Run: php artisan db:seed --class=SettingsTranslationsSeeder
     */
    public function run(): void
    {
        $translations = [
            // General Settings
            'settings.general.title' => 'General Settings',
            'settings.general.description' => 'Core application identity, runtime controls, and language defaults',
            'settings.general.identity.title' => 'Application Identity',
            'settings.general.identity.subtitle' => 'How Baraka appears across the platform',
            'settings.general.app_name.label' => 'Application Name',
            'settings.general.app_url.label' => 'Application URL',
            'settings.general.timezone.label' => 'Timezone',
            'settings.general.locale.label' => 'Interface Language',
            'settings.general.maintenance.label' => 'Maintenance Mode',
            'settings.general.maintenance.help' => 'Take the platform offline for deployments',
            'settings.general.region.title' => 'Language & Region',
            'settings.general.region.subtitle' => 'Language defaults and currency settings',
            'settings.general.currency.label' => 'Default Currency',
            'settings.general.currency.help' => 'Used across finance panels, invoices, and branch dashboards',
            'settings.general.runtime.title' => 'Runtime Environment',
            'settings.general.runtime.subtitle' => 'Current environment diagnostics',
            'settings.general.environment.label' => 'Environment',
            'settings.general.debug.label' => 'Debug Mode',
            'settings.general.save_button' => 'Save Settings',

            // Branding Settings
            'settings.branding.title' => 'Branding',
            'settings.branding.description' => 'Logos, colors, and visual identity',
            'settings.branding.visual.title' => 'Visual Identity',
            'settings.branding.visual.subtitle' => 'Primary and secondary color palette',
            'settings.branding.primary_color.label' => 'Primary Color',
            'settings.branding.primary_color.help' => 'Used for primary buttons and highlights',
            'settings.branding.secondary_color.label' => 'Secondary Color',
            'settings.branding.secondary_color.help' => 'Used for subtle accents',
            'settings.branding.company_name.label' => 'Company Name',
            'settings.branding.tagline.label' => 'Tagline',

            // Language Settings
            'settings.language.title' => 'Language & Translations',
            'settings.language.description' => 'Manage translations for English, French, and Kiswahili',
            'settings.language.search.placeholder' => 'Search by key or value',
            'settings.language.filter.all' => 'All Statuses',
            'settings.language.filter.complete' => 'Complete',
            'settings.language.filter.incomplete' => 'Incomplete',
            'settings.language.filter.empty' => 'Empty',
            'settings.language.stats.title' => 'Translation Progress',
            'settings.language.table.key' => 'Translation Key',
            'settings.language.table.actions' => 'Actions',
            'settings.language.add_new' => 'Add New Translation',
            'settings.language.save' => 'Save All Changes',
            'settings.language.delete_confirm' => 'Are you sure you want to delete this translation?',

            // Operations Settings
            'settings.operations.title' => 'Operations',
            'settings.operations.description' => 'Workflow automation and file handling',
            'settings.operations.file.title' => 'File Handling',
            'settings.operations.file.subtitle' => 'Control limits and supported file types',
            'settings.operations.max_file_size.label' => 'Max File Size (KB)',
            'settings.operations.backup_frequency.label' => 'Backup Frequency',

            // Notifications Settings
            'settings.notifications.title' => 'Notifications',
            'settings.notifications.description' => 'Channels and delivery configuration',
            'settings.notifications.channels.title' => 'Communication Channels',
            'settings.notifications.channels.subtitle' => 'Configure how Baraka sends notifications',
            'settings.notifications.email.label' => 'Email Notifications',
            'settings.notifications.sms.label' => 'SMS Notifications',
            'settings.notifications.push.label' => 'Push Notifications',
            'settings.notifications.slack.label' => 'Slack Notifications',

            // System Settings
            'settings.system.title' => 'System Information',
            'settings.system.description' => 'Runtime environment and infrastructure diagnostics',
            'settings.system.overview.title' => 'Environment Overview',
            'settings.system.overview.subtitle' => 'Runtime diagnostics and PHP configuration',
            'settings.system.php_version.label' => 'PHP Version',
            'settings.system.laravel_version.label' => 'Laravel Version',
            'settings.system.timezone.label' => 'Timezone',
            'settings.system.memory_limit.label' => 'Memory Limit',
            'settings.system.database.label' => 'Database',
            'settings.system.cache.label' => 'Cache',

            // Finance Settings
            'settings.finance.title' => 'Finance',
            'settings.finance.description' => 'Tax, currency, and invoicing configuration',
            'settings.finance.tax.title' => 'Tax & Currency',
            'settings.finance.tax.subtitle' => 'Configure tax rates and currency preferences',

            // Integrations Settings
            'settings.integrations.title' => 'Third-Party Integrations',
            'settings.integrations.description' => 'Connect external services and APIs',
            'settings.integrations.api.title' => 'API & Webhooks',
            'settings.integrations.api.subtitle' => 'Connect external services and APIs',

            // Website Settings
            'settings.website.title' => 'Website Settings',
            'settings.website.description' => 'Configure the public website and marketing content',
            'settings.website.landing.title' => 'Landing Page',
            'settings.website.landing.subtitle' => 'Configure the public website and marketing content',

            // Common Translations
            'settings.save_button' => 'Save Changes',
            'settings.cancel_button' => 'Cancel',
            'settings.delete_button' => 'Delete',
            'settings.edit_button' => 'Edit',
            'settings.view_button' => 'View',
            'settings.required_field' => 'This field is required',
            'settings.success_message' => 'Settings saved successfully',
            'settings.error_message' => 'Failed to save settings',
        ];

        foreach ($translations as $key => $value) {
            foreach (['en', 'fr', 'sw'] as $lang) {
                Translation::updateOrCreate(
                    ['key' => $key, 'language_code' => $lang],
                    ['value' => $lang === 'en' ? $value : ''] // Only populate English initially
                );
            }
        }

        $this->command->info('Settings translations seeded successfully!');
        $this->command->info('Total keys created: ' . count($translations));
        $this->command->info('French and Kiswahili translations are empty - admin can fill them via /settings/language');
    }
}
