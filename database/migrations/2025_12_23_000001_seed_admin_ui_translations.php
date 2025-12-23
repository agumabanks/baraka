<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $translations = [
            // Admin layout
            'admin.layout.title' => [
                'en' => 'Admin Panel',
                'fr' => "Panneau d'administration",
                'sw' => 'Admin Panel',
            ],
            'admin.layout.menu' => [
                'en' => 'Menu',
                'fr' => 'Menu',
                'sw' => 'Menyu',
            ],
            'admin.layout.system_administration' => [
                'en' => 'System Administration',
                'fr' => 'Administration du système',
                'sw' => 'Usimamizi wa Mfumo',
            ],
            'admin.layout.header.default' => [
                'en' => 'Dashboard',
                'fr' => 'Tableau de bord',
                'sw' => 'Dashibodi',
            ],
            'admin.layout.signed_in_as' => [
                'en' => 'Signed in as',
                'fr' => 'Connecté en tant que',
                'sw' => 'Umeingia kama',
            ],
            'admin.layout.errors.fix_following' => [
                'en' => 'Please fix the following:',
                'fr' => 'Veuillez corriger les éléments suivants :',
                'sw' => 'Tafadhali rekebisha yafuatayo:',
            ],

            // Admin sidebar
            'admin.badge.new' => [
                'en' => 'NEW',
                'fr' => 'NOUVEAU',
                'sw' => 'MPYA',
            ],
            'admin.sidebar.section.overview' => [
                'en' => 'Overview',
                'fr' => 'Aperçu',
                'sw' => 'Muhtasari',
            ],
            'admin.sidebar.section.operations' => [
                'en' => 'Operations',
                'fr' => 'Opérations',
                'sw' => 'Uendeshaji',
            ],
            'admin.sidebar.section.clients_personnel' => [
                'en' => 'Clients & Personnel',
                'fr' => 'Clients et personnel',
                'sw' => 'Wateja na Wafanyakazi',
            ],
            'admin.sidebar.dashboard' => [
                'en' => 'Dashboard',
                'fr' => 'Tableau de bord',
                'sw' => 'Dashibodi',
            ],
            'admin.sidebar.shipments_pos' => [
                'en' => 'Shipments POS',
                'fr' => 'Expéditions POS',
                'sw' => 'POS ya Usafirishaji',
            ],
            'admin.sidebar.shipments_management' => [
                'en' => 'Shipments Management',
                'fr' => 'Gestion des expéditions',
                'sw' => 'Usimamizi wa Usafirishaji',
            ],
            'admin.sidebar.branches' => [
                'en' => 'Branches',
                'fr' => 'Agences',
                'sw' => 'Matawi',
            ],
            'admin.sidebar.hubs' => [
                'en' => 'Hubs',
                'fr' => 'Hubs',
                'sw' => 'Vituo',
            ],
            'admin.sidebar.clients' => [
                'en' => 'Clients',
                'fr' => 'Clients',
                'sw' => 'Wateja',
            ],
            'admin.sidebar.merchants' => [
                'en' => 'Merchants',
                'fr' => 'Commerçants',
                'sw' => 'Wafanyabiashara',
            ],
            'admin.sidebar.delivery_personnel' => [
                'en' => 'Delivery Personnel',
                'fr' => 'Personnel de livraison',
                'sw' => 'Wahudumu wa Uwasilishaji',
            ],
            'admin.sidebar.section.tracking_dispatch' => [
                'en' => 'Tracking & Dispatch',
                'fr' => 'Suivi et dispatch',
                'sw' => 'Ufuatiliaji na Usambazaji',
            ],
            'admin.sidebar.live_tracking' => [
                'en' => 'Live Tracking',
                'fr' => 'Suivi en direct',
                'sw' => 'Ufuatiliaji wa Moja kwa Moja',
            ],
            'admin.sidebar.dispatch_center' => [
                'en' => 'Dispatch Center',
                'fr' => 'Centre de dispatch',
                'sw' => 'Kituo cha Usambazaji',
            ],
            'admin.sidebar.section.finance' => [
                'en' => 'Finance',
                'fr' => 'Finance',
                'sw' => 'Fedha',
            ],
            'admin.sidebar.network_finance' => [
                'en' => 'Network Finance',
                'fr' => 'Finance réseau',
                'sw' => 'Fedha za Mtandao',
            ],
            'admin.sidebar.cod_collections' => [
                'en' => 'COD & Collections',
                'fr' => 'COD et recouvrements',
                'sw' => 'COD na Makusanyo',
            ],
            'admin.sidebar.cod_management' => [
                'en' => 'COD Management',
                'fr' => 'Gestion COD',
                'sw' => 'Usimamizi wa COD',
            ],
            'admin.sidebar.settlements' => [
                'en' => 'Settlements',
                'fr' => 'Règlements',
                'sw' => 'Malipo',
            ],
            'admin.sidebar.section.analytics' => [
                'en' => 'Analytics',
                'fr' => 'Analytique',
                'sw' => 'Uchanganuzi',
            ],
            'admin.sidebar.executive_dashboard' => [
                'en' => 'Executive Dashboard',
                'fr' => 'Tableau de bord exécutif',
                'sw' => 'Dashibodi ya Utendaji',
            ],
            'admin.sidebar.analytics_reports' => [
                'en' => 'Reports',
                'fr' => 'Rapports',
                'sw' => 'Ripoti',
            ],
            'admin.sidebar.section.security' => [
                'en' => 'Security',
                'fr' => 'Sécurité',
                'sw' => 'Usalama',
            ],
            'admin.sidebar.security_center' => [
                'en' => 'Security Center',
                'fr' => 'Centre de sécurité',
                'sw' => 'Kituo cha Usalama',
            ],
            'admin.sidebar.two_factor_auth' => [
                'en' => 'Two-Factor Auth',
                'fr' => 'Authentification à deux facteurs',
                'sw' => 'Uthibitishaji wa Hatua Mbili',
            ],
            'admin.sidebar.mfa_policy_settings' => [
                'en' => 'MFA Policy Settings',
                'fr' => 'Paramètres de politique MFA',
                'sw' => 'Mipangilio ya Sera ya MFA',
            ],
            'admin.sidebar.section.management' => [
                'en' => 'Management',
                'fr' => 'Gestion',
                'sw' => 'Usimamizi',
            ],
            'admin.sidebar.users' => [
                'en' => 'Users',
                'fr' => 'Utilisateurs',
                'sw' => 'Watumiaji',
            ],
            'admin.sidebar.reports' => [
                'en' => 'Reports',
                'fr' => 'Rapports',
                'sw' => 'Ripoti',
            ],
            'admin.sidebar.section.settings' => [
                'en' => 'Settings',
                'fr' => 'Paramètres',
                'sw' => 'Mipangilio',
            ],
            'admin.sidebar.settings' => [
                'en' => 'Settings',
                'fr' => 'Paramètres',
                'sw' => 'Mipangilio',
            ],
            'admin.sidebar.general_settings' => [
                'en' => 'General Settings',
                'fr' => 'Paramètres généraux',
                'sw' => 'Mipangilio ya Jumla',
            ],
            'admin.sidebar.sign_out' => [
                'en' => 'Sign Out',
                'fr' => 'Déconnexion',
                'sw' => 'Toka',
            ],

            // Admin dashboard
            'admin.dashboard.title' => [
                'en' => 'Dashboard',
                'fr' => 'Tableau de bord',
                'sw' => 'Dashibodi',
            ],
            'admin.dashboard.header' => [
                'en' => 'Dashboard Overview',
                'fr' => "Aperçu du tableau de bord",
                'sw' => 'Muhtasari wa Dashibodi',
            ],
            'admin.dashboard.welcome' => [
                'en' => 'Welcome back, :name',
                'fr' => 'Bon retour, :name',
                'sw' => 'Karibu tena, :name',
            ],
            'admin.dashboard.subtitle' => [
                'en' => "Here's what's happening across :brand today.",
                'fr' => "Voici ce qui se passe chez :brand aujourd'hui.",
                'sw' => 'Haya ndiyo yanayoendelea katika :brand leo.',
            ],
            'admin.dashboard.current_time' => [
                'en' => 'Current Time',
                'fr' => 'Heure actuelle',
                'sw' => 'Saa ya Sasa',
            ],
            'admin.dashboard.date' => [
                'en' => 'Date',
                'fr' => 'Date',
                'sw' => 'Tarehe',
            ],
            'admin.dashboard.range.today' => [
                'en' => 'Today',
                'fr' => "Aujourd'hui",
                'sw' => 'Leo',
            ],
            'admin.dashboard.range.7d' => [
                'en' => '7 Days',
                'fr' => '7 jours',
                'sw' => 'Siku 7',
            ],
            'admin.dashboard.range.30d' => [
                'en' => '30 Days',
                'fr' => '30 jours',
                'sw' => 'Siku 30',
            ],
            'admin.dashboard.range.this_month' => [
                'en' => 'This Month',
                'fr' => 'Ce mois-ci',
                'sw' => 'Mwezi Huu',
            ],
            'admin.dashboard.range.last_7_days' => [
                'en' => 'Last 7 Days',
                'fr' => '7 derniers jours',
                'sw' => 'Siku 7 zilizopita',
            ],
            'admin.dashboard.kpi.total_shipments.title' => [
                'en' => 'Total Shipments',
                'fr' => 'Total des expéditions',
                'sw' => 'Jumla ya Mizigo',
            ],
            'admin.dashboard.kpi.this_period' => [
                'en' => 'This period',
                'fr' => 'Cette période',
                'sw' => 'Kipindi hiki',
            ],
            'admin.dashboard.kpi.delivered.title' => [
                'en' => 'Delivered',
                'fr' => 'Livré',
                'sw' => 'Imefikishwa',
            ],
            'admin.dashboard.kpi.delivered.subtitle' => [
                'en' => ':rate% success rate',
                'fr' => ':rate% de réussite',
                'sw' => 'Kiwango cha mafanikio :rate%',
            ],
            'admin.dashboard.kpi.in_transit.title' => [
                'en' => 'In Transit',
                'fr' => 'En transit',
                'sw' => 'Njiani',
            ],
            'admin.dashboard.kpi.in_transit.subtitle' => [
                'en' => 'Active shipments',
                'fr' => 'Expéditions actives',
                'sw' => 'Mizigo inayoendelea',
            ],
            'admin.dashboard.kpi.revenue.title' => [
                'en' => 'Revenue',
                'fr' => 'Revenus',
                'sw' => 'Mapato',
            ],
            'admin.dashboard.sla.on_time_delivery' => [
                'en' => 'On-Time Delivery',
                'fr' => 'Livraison à temps',
                'sw' => 'Uwasilishaji kwa Wakati',
            ],
            'admin.dashboard.common.of' => [
                'en' => ':count of :total',
                'fr' => ':count sur :total',
                'sw' => ':count kati ya :total',
            ],
            'admin.dashboard.sla.avg_delivery_time' => [
                'en' => 'Avg Delivery Time',
                'fr' => 'Temps moyen de livraison',
                'sw' => 'Wastani wa Muda wa Uwasilishaji',
            ],
            'admin.dashboard.sla.hours_from_pickup' => [
                'en' => 'Hours from pickup',
                'fr' => "Heures depuis l'enlèvement",
                'sw' => 'Saa tangu kuchukuliwa',
            ],
            'admin.dashboard.sla.first_attempt_rate' => [
                'en' => 'First Attempt Rate',
                'fr' => 'Taux de première tentative',
                'sw' => 'Kiwango cha Jaribio la Kwanza',
            ],
            'admin.dashboard.sla.delivered_first_try' => [
                'en' => 'Delivered on 1st try',
                'fr' => 'Livré au 1er essai',
                'sw' => 'Imefikishwa kwa jaribio la kwanza',
            ],
            'admin.dashboard.sla.exceptions' => [
                'en' => 'Exceptions',
                'fr' => 'Exceptions',
                'sw' => 'Matukio Maalum',
            ],
            'admin.dashboard.sla.rate' => [
                'en' => ':rate% rate',
                'fr' => 'Taux :rate%',
                'sw' => 'Kiwango :rate%',
            ],
            'admin.dashboard.sla.returns' => [
                'en' => 'Returns',
                'fr' => 'Retours',
                'sw' => 'Marejesho',
            ],
            'admin.dashboard.sla.cancelled' => [
                'en' => '+ :count cancelled',
                'fr' => '+ :count annulé(s)',
                'sw' => '+ :count imeghairiwa',
            ],
            'admin.dashboard.finance.cod_collections' => [
                'en' => 'COD Collections',
                'fr' => 'Encaissements COD',
                'sw' => 'Makusanyo ya COD',
            ],
            'admin.dashboard.finance.settlements' => [
                'en' => 'Settlements',
                'fr' => 'Règlements',
                'sw' => 'Malipo',
            ],
            'admin.dashboard.finance.invoices' => [
                'en' => 'Invoices',
                'fr' => 'Factures',
                'sw' => 'Ankara',
            ],
            'admin.dashboard.stats.branches' => [
                'en' => 'Branches',
                'fr' => 'Agences',
                'sw' => 'Matawi',
            ],
            'admin.dashboard.stats.of_total' => [
                'en' => 'of :total total',
                'fr' => 'sur :total au total',
                'sw' => 'kati ya :total jumla',
            ],
            'admin.dashboard.stats.drivers' => [
                'en' => 'Drivers',
                'fr' => 'Chauffeurs',
                'sw' => 'Madereva',
            ],
            'admin.dashboard.stats.avg_per_driver' => [
                'en' => ':avg avg/driver',
                'fr' => ':avg moy./chauffeur',
                'sw' => ':avg wastani/dereva',
            ],
            'admin.dashboard.stats.merchants' => [
                'en' => 'Merchants',
                'fr' => 'Commerçants',
                'sw' => 'Wafanyabiashara',
            ],
            'admin.dashboard.stats.customers' => [
                'en' => 'Customers',
                'fr' => 'Clients',
                'sw' => 'Wateja',
            ],
            'admin.dashboard.stats.scans_today' => [
                'en' => 'Scans Today',
                'fr' => "Scans aujourd'hui",
                'sw' => 'Skani Leo',
            ],
            'admin.dashboard.ops.pending_pickups' => [
                'en' => 'Pending Pickups',
                'fr' => 'Collectes en attente',
                'sw' => 'Makusanyo Yanayosubiri',
            ],
            'admin.dashboard.ops.out_for_delivery' => [
                'en' => 'Out for Delivery',
                'fr' => 'En cours de livraison',
                'sw' => 'Imetoka kwa Uwasilishaji',
            ],
            'admin.dashboard.ops.active_vehicles' => [
                'en' => 'Active Vehicles',
                'fr' => 'Véhicules actifs',
                'sw' => 'Magari Yanayotumika',
            ],
            'admin.dashboard.ops.in_maintenance' => [
                'en' => 'In Maintenance',
                'fr' => 'En maintenance',
                'sw' => 'Kwenye Matengenezo',
            ],
            'admin.dashboard.charts.shipment_trends' => [
                'en' => 'Shipment Trends',
                'fr' => "Tendances des expéditions",
                'sw' => 'Mwenendo wa Mizigo',
            ],
            'admin.dashboard.charts.created' => [
                'en' => 'Created',
                'fr' => 'Créé',
                'sw' => 'Imeundwa',
            ],
            'admin.dashboard.charts.delivered' => [
                'en' => 'Delivered',
                'fr' => 'Livré',
                'sw' => 'Imefikishwa',
            ],
            'admin.dashboard.charts.status_distribution' => [
                'en' => 'Status Distribution',
                'fr' => 'Répartition des statuts',
                'sw' => 'Mgawanyo wa Hali',
            ],
            'admin.dashboard.quick_actions.title' => [
                'en' => 'Quick Actions',
                'fr' => 'Actions rapides',
                'sw' => 'Vitendo vya Haraka',
            ],
            'admin.dashboard.quick_actions.new_shipment' => [
                'en' => 'New Shipment',
                'fr' => 'Nouvelle expédition',
                'sw' => 'Mzigo Mpya',
            ],
            'admin.dashboard.quick_actions.shipment_pos' => [
                'en' => 'Shipment POS',
                'fr' => "POS d'expédition",
                'sw' => 'POS ya Usafirishaji',
            ],
            'admin.dashboard.quick_actions.live_tracking' => [
                'en' => 'Live Tracking',
                'fr' => 'Suivi en direct',
                'sw' => 'Ufuatiliaji wa Moja kwa Moja',
            ],
            'admin.dashboard.quick_actions.monitor_fleet' => [
                'en' => 'Monitor fleet',
                'fr' => 'Surveiller la flotte',
                'sw' => 'Fuatilia magari',
            ],
            'admin.dashboard.quick_actions.analytics' => [
                'en' => 'Analytics',
                'fr' => 'Analytique',
                'sw' => 'Uchanganuzi',
            ],
            'admin.dashboard.quick_actions.view_reports' => [
                'en' => 'View reports',
                'fr' => 'Voir les rapports',
                'sw' => 'Tazama ripoti',
            ],
            'admin.dashboard.quick_actions.dispatch' => [
                'en' => 'Dispatch',
                'fr' => 'Dispatch',
                'sw' => 'Usambazaji',
            ],
            'admin.dashboard.quick_actions.route_optimization' => [
                'en' => 'Route optimization',
                'fr' => 'Optimisation des itinéraires',
                'sw' => 'Uboreshaji wa Njia',
            ],
            'admin.dashboard.recent_activity.title' => [
                'en' => 'Recent Activity',
                'fr' => 'Activité récente',
                'sw' => 'Shughuli za Hivi Karibuni',
            ],
            'admin.dashboard.top_branches.title' => [
                'en' => 'Top Branches',
                'fr' => 'Meilleures agences',
                'sw' => 'Matawi Bora',
            ],
            'admin.dashboard.top_customers.title' => [
                'en' => 'Top Customers',
                'fr' => 'Meilleurs clients',
                'sw' => 'Wateja Bora',
            ],
            'admin.dashboard.top_customers.shipments' => [
                'en' => ':count shipments',
                'fr' => ':count expéditions',
                'sw' => 'mizigo :count',
            ],
            'admin.dashboard.shipments_by_city.title' => [
                'en' => 'Shipments by City',
                'fr' => 'Expéditions par ville',
                'sw' => 'Mizigo kwa Jiji',
            ],
            'admin.dashboard.top_routes.title' => [
                'en' => 'Top Routes',
                'fr' => 'Meilleurs itinéraires',
                'sw' => 'Njia Bora',
            ],
            'admin.dashboard.top_routes.shipments' => [
                'en' => 'shipments',
                'fr' => 'expéditions',
                'sw' => 'mizigo',
            ],
            'admin.dashboard.recent_shipments.title' => [
                'en' => 'Recent Shipments',
                'fr' => 'Expéditions récentes',
                'sw' => 'Mizigo ya Hivi Karibuni',
            ],
            'admin.dashboard.recent_shipments.tracking' => [
                'en' => 'Tracking #',
                'fr' => 'Suivi #',
                'sw' => 'Ufuatiliaji #',
            ],
            'admin.dashboard.recent_shipments.origin' => [
                'en' => 'Origin',
                'fr' => 'Origine',
                'sw' => 'Asili',
            ],
            'admin.dashboard.recent_shipments.destination' => [
                'en' => 'Destination',
                'fr' => 'Destination',
                'sw' => 'Mahali',
            ],
            'admin.dashboard.recent_shipments.status' => [
                'en' => 'Status',
                'fr' => 'Statut',
                'sw' => 'Hali',
            ],
            'admin.dashboard.recent_shipments.created' => [
                'en' => 'Created',
                'fr' => 'Créé',
                'sw' => 'Imeundwa',
            ],
            'admin.dashboard.recent_shipments.empty' => [
                'en' => 'No shipments found',
                'fr' => 'Aucune expédition trouvée',
                'sw' => 'Hakuna mizigo iliyopatikana',
            ],

            // Shared admin/common
            'admin.common.pending' => [
                'en' => 'Pending',
                'fr' => 'En attente',
                'sw' => 'Inasubiri',
            ],
            'admin.common.completed' => [
                'en' => 'Completed',
                'fr' => 'Terminé',
                'sw' => 'Imekamilika',
            ],
            'admin.common.overdue' => [
                'en' => 'Overdue',
                'fr' => 'En retard',
                'sw' => 'Imechelewa',
            ],
            'admin.common.collected' => [
                'en' => 'Collected',
                'fr' => 'Collecté',
                'sw' => 'Imekusanywa',
            ],
            'admin.common.view_all' => [
                'en' => 'View All',
                'fr' => 'Voir tout',
                'sw' => 'Tazama Yote',
            ],
            'admin.common.view' => [
                'en' => 'View',
                'fr' => 'Voir',
                'sw' => 'Tazama',
            ],
            'admin.common.actions' => [
                'en' => 'Actions',
                'fr' => 'Actions',
                'sw' => 'Vitendo',
            ],
            'admin.common.na' => [
                'en' => 'N/A',
                'fr' => 'N/A',
                'sw' => 'N/A',
            ],
            'admin.common.unknown' => [
                'en' => 'Unknown',
                'fr' => 'Inconnu',
                'sw' => 'Haijulikani',
            ],
            'admin.common.no_data' => [
                'en' => 'No data available',
                'fr' => 'Aucune donnée disponible',
                'sw' => 'Hakuna data inayopatikana',
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
                    'description' => 'seed:admin_ui',
                    'metadata' => json_encode(['source' => 'seed', 'domain' => 'admin_ui']),
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
            ->where('description', 'seed:admin_ui')
            ->delete();

        foreach (['en', 'fr', 'sw'] as $locale) {
            Cache::forget("translations_array_{$locale}");
            Cache::forget("api_translations_{$locale}");
        }
    }
};

