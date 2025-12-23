<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $translations = [
            // Branch layout
            'branch.layout.title' => [
                'en' => 'Branch Control Center',
                'fr' => "Centre de contrôle d'agence",
                'sw' => 'Kituo cha Udhibiti wa Tawi',
            ],
            'branch.layout.menu' => [
                'en' => 'Menu',
                'fr' => 'Menu',
                'sw' => 'Menyu',
            ],
            'branch.layout.active_branch' => [
                'en' => 'Active branch',
                'fr' => 'Agence active',
                'sw' => 'Tawi linalotumika',
            ],
            'branch.layout.branch_fallback' => [
                'en' => 'Branch',
                'fr' => 'Agence',
                'sw' => 'Tawi',
            ],
            'branch.layout.parent_line' => [
                'en' => 'Parent: :code • :name',
                'fr' => 'Parent : :code • :name',
                'sw' => 'Mzazi: :code • :name',
            ],
            'branch.layout.signed_in_as' => [
                'en' => 'Signed in as',
                'fr' => 'Connecté en tant que',
                'sw' => 'Umeingia kama',
            ],
            'branch.layout.errors.fix_following' => [
                'en' => 'Please fix the following:',
                'fr' => 'Veuillez corriger les éléments suivants :',
                'sw' => 'Tafadhali rekebisha yafuatayo:',
            ],

            // Branch sidebar
            'branch.badge.new' => [
                'en' => 'NEW',
                'fr' => 'NOUVEAU',
                'sw' => 'MPYA',
            ],
            'branch.sidebar.branch' => [
                'en' => 'Branch',
                'fr' => 'Agence',
                'sw' => 'Tawi',
            ],
            'branch.sidebar.overview' => [
                'en' => 'Overview',
                'fr' => 'Aperçu',
                'sw' => 'Muhtasari',
            ],
            'branch.sidebar.section.shipments' => [
                'en' => 'Shipments',
                'fr' => 'Expéditions',
                'sw' => 'Usafirishaji',
            ],
            'branch.sidebar.shipments_pos' => [
                'en' => 'Shipments POS',
                'fr' => 'Expéditions POS',
                'sw' => 'POS ya Usafirishaji',
            ],
            'branch.sidebar.shipments_management' => [
                'en' => 'Shipments Management',
                'fr' => 'Gestion des expéditions',
                'sw' => 'Usimamizi wa Usafirishaji',
            ],
            'branch.sidebar.section.operations' => [
                'en' => 'Operations',
                'fr' => 'Opérations',
                'sw' => 'Uendeshaji',
            ],
            'branch.sidebar.operations_board' => [
                'en' => 'Operations Board',
                'fr' => "Tableau d'opérations",
                'sw' => 'Bodi ya Uendeshaji',
            ],
            'branch.sidebar.workforce' => [
                'en' => 'Workforce',
                'fr' => 'Main-d’œuvre',
                'sw' => 'Nguvu Kazi',
            ],
            'branch.sidebar.clients_crm' => [
                'en' => 'Clients & CRM',
                'fr' => 'Clients et CRM',
                'sw' => 'Wateja na CRM',
            ],
            'branch.sidebar.finance' => [
                'en' => 'Finance',
                'fr' => 'Finance',
                'sw' => 'Fedha',
            ],
            'branch.sidebar.pl_settlements' => [
                'en' => 'P&L / Settlements',
                'fr' => 'P&L / Règlements',
                'sw' => 'Faida/Hasara / Malipo',
            ],
            'branch.sidebar.warehouse' => [
                'en' => 'Warehouse',
                'fr' => 'Entrepôt',
                'sw' => 'Ghala',
            ],
            'branch.sidebar.fleet' => [
                'en' => 'Fleet',
                'fr' => 'Flotte',
                'sw' => 'Meli ya Magari',
            ],
            'branch.sidebar.section.security' => [
                'en' => 'Security',
                'fr' => 'Sécurité',
                'sw' => 'Usalama',
            ],
            'branch.sidebar.sessions' => [
                'en' => 'Sessions',
                'fr' => 'Sessions',
                'sw' => 'Vipindi',
            ],
            'branch.sidebar.audit_logs' => [
                'en' => 'Audit Logs',
                'fr' => "Journaux d'audit",
                'sw' => 'Kumbukumbu za Ukaguzi',
            ],
            'branch.sidebar.section.settings' => [
                'en' => 'Settings',
                'fr' => 'Paramètres',
                'sw' => 'Mipangilio',
            ],
            'branch.sidebar.branch_settings' => [
                'en' => 'Branch Settings',
                'fr' => "Paramètres d'agence",
                'sw' => 'Mipangilio ya Tawi',
            ],

            // Branch user dropdown
            'branch.user.section.account' => [
                'en' => 'Account',
                'fr' => 'Compte',
                'sw' => 'Akaunti',
            ],
            'branch.user.profile' => [
                'en' => 'Profile',
                'fr' => 'Profil',
                'sw' => 'Wasifu',
            ],
            'branch.user.security_settings' => [
                'en' => 'Security Settings',
                'fr' => 'Paramètres de sécurité',
                'sw' => 'Mipangilio ya Usalama',
            ],
            'branch.user.two_factor_auth' => [
                'en' => 'Two-Factor Auth',
                'fr' => 'Authentification à deux facteurs',
                'sw' => 'Uthibitishaji wa Hatua Mbili',
            ],
            'branch.user.devices_sessions' => [
                'en' => 'Devices & Sessions',
                'fr' => 'Appareils et sessions',
                'sw' => 'Vifaa na Vipindi',
            ],
            'branch.user.notifications' => [
                'en' => 'Notifications',
                'fr' => 'Notifications',
                'sw' => 'Arifa',
            ],
            'branch.user.preferences' => [
                'en' => 'Preferences',
                'fr' => 'Préférences',
                'sw' => 'Mapendeleo',
            ],
            'branch.user.section.support' => [
                'en' => 'Support',
                'fr' => 'Support',
                'sw' => 'Msaada',
            ],
            'branch.user.help_status' => [
                'en' => 'Help & status',
                'fr' => 'Aide et statut',
                'sw' => 'Msaada na hali',
            ],
            'branch.user.billing' => [
                'en' => 'Billing',
                'fr' => 'Facturation',
                'sw' => 'Malipo',
            ],
            'branch.user.section.admin' => [
                'en' => 'Admin',
                'fr' => 'Admin',
                'sw' => 'Msimamizi',
            ],
            'branch.user.user_management' => [
                'en' => 'User management',
                'fr' => 'Gestion des utilisateurs',
                'sw' => 'Usimamizi wa watumiaji',
            ],
            'branch.user.logout' => [
                'en' => 'Logout',
                'fr' => 'Déconnexion',
                'sw' => 'Toka',
            ],

            // Branch finance nav
            'branch.finance.nav.overview' => [
                'en' => 'Overview',
                'fr' => 'Aperçu',
                'sw' => 'Muhtasari',
            ],
            'branch.finance.nav.receivables' => [
                'en' => 'Receivables',
                'fr' => 'Créances',
                'sw' => 'Madeni ya Kupokea',
            ],
            'branch.finance.nav.invoices' => [
                'en' => 'Invoices',
                'fr' => 'Factures',
                'sw' => 'Ankara',
            ],
            'branch.finance.nav.cod_management' => [
                'en' => 'COD Management',
                'fr' => 'Gestion COD',
                'sw' => 'Usimamizi wa COD',
            ],
            'branch.finance.nav.expenses' => [
                'en' => 'Expenses',
                'fr' => 'Dépenses',
                'sw' => 'Gharama',
            ],
            'branch.finance.nav.cash_position' => [
                'en' => 'Cash Position',
                'fr' => 'Position de trésorerie',
                'sw' => 'Nafasi ya Fedha Taslimu',
            ],
            'branch.finance.nav.daily_report' => [
                'en' => 'Daily Report',
                'fr' => 'Rapport quotidien',
                'sw' => 'Ripoti ya Kila Siku',
            ],
        ];

        $now = now();
        $rows = [];
        foreach ($translations as $key => $locales) {
            foreach ($locales as $languageCode => $value) {
                $rows[] = [
                    'key' => $key,
                    'language_code' => $languageCode,
                    'value' => $value,
                    'description' => 'seed:branch_ui',
                    'metadata' => json_encode(['source' => 'seed', 'domain' => 'branch_ui']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('translations')->insertOrIgnore($chunk);
        }

        foreach (['en', 'fr', 'sw'] as $locale) {
            Cache::forget("translations_array_{$locale}");
            Cache::forget("api_translations_{$locale}");
        }
    }

    public function down(): void
    {
        DB::table('translations')
            ->where('description', 'seed:branch_ui')
            ->delete();

        foreach (['en', 'fr', 'sw'] as $locale) {
            Cache::forget("translations_array_{$locale}");
            Cache::forget("api_translations_{$locale}");
        }
    }
};

