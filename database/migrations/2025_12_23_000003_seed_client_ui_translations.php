<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $translations = [
            // Client layout / navigation
            'client.title.default' => [
                'en' => 'Dashboard',
                'fr' => 'Tableau de bord',
                'sw' => 'Dashibodi',
            ],
            'client.layout.portal_label' => [
                'en' => 'Customer Portal',
                'fr' => 'Portail client',
                'sw' => 'Tovuti ya Wateja',
            ],
            'client.nav.section.account' => [
                'en' => 'Account',
                'fr' => 'Compte',
                'sw' => 'Akaunti',
            ],
            'client.layout.header.default' => [
                'en' => 'Dashboard',
                'fr' => 'Tableau de bord',
                'sw' => 'Dashibodi',
            ],
            'client.layout.quick_track.placeholder' => [
                'en' => 'Track shipment...',
                'fr' => "Suivre l'expédition...",
                'sw' => 'Fuatilia mzigo...',
            ],
            'client.nav.dashboard' => [
                'en' => 'Dashboard',
                'fr' => 'Tableau de bord',
                'sw' => 'Dashibodi',
            ],
            'client.nav.new_shipment' => [
                'en' => 'New Shipment',
                'fr' => 'Nouvelle expédition',
                'sw' => 'Mzigo Mpya',
            ],
            'client.nav.my_shipments' => [
                'en' => 'My Shipments',
                'fr' => 'Mes expéditions',
                'sw' => 'Mizigo Yangu',
            ],
            'client.nav.track' => [
                'en' => 'Track Shipment',
                'fr' => "Suivre l'expédition",
                'sw' => 'Fuatilia Mzigo',
            ],
            'client.nav.quote' => [
                'en' => 'Get Quote',
                'fr' => 'Obtenir un devis',
                'sw' => 'Pata Nukuu',
            ],
            'client.nav.address_book' => [
                'en' => 'Address Book',
                'fr' => "Carnet d'adresses",
                'sw' => 'Kitabu cha Anwani',
            ],
            'client.nav.invoices' => [
                'en' => 'Invoices',
                'fr' => 'Factures',
                'sw' => 'Ankara',
            ],
            'client.nav.profile' => [
                'en' => 'Profile',
                'fr' => 'Profil',
                'sw' => 'Wasifu',
            ],
            'client.nav.support' => [
                'en' => 'Support',
                'fr' => 'Support',
                'sw' => 'Msaada',
            ],
            'client.nav.sign_out' => [
                'en' => 'Sign Out',
                'fr' => 'Déconnexion',
                'sw' => 'Toka',
            ],

            // Client common
            'client.common.view_all' => [
                'en' => 'View All',
                'fr' => 'Voir tout',
                'sw' => 'Tazama Yote',
            ],
            'client.common.search' => [
                'en' => 'Search',
                'fr' => 'Rechercher',
                'sw' => 'Tafuta',
            ],

            // Client dashboard
            'client.dashboard.title' => [
                'en' => 'Dashboard',
                'fr' => 'Tableau de bord',
                'sw' => 'Dashibodi',
            ],
            'client.dashboard.header' => [
                'en' => 'Dashboard',
                'fr' => 'Tableau de bord',
                'sw' => 'Dashibodi',
            ],
            'client.dashboard.welcome' => [
                'en' => 'Welcome back, :name!',
                'fr' => 'Bon retour, :name !',
                'sw' => 'Karibu tena, :name!',
            ],
            'client.dashboard.subtitle' => [
                'en' => "Here's what's happening with your shipments today.",
                'fr' => "Voici ce qui se passe avec vos expéditions aujourd'hui.",
                'sw' => 'Haya ndiyo yanayoendelea na mizigo yako leo.',
            ],
            'client.dashboard.actions.new_shipment' => [
                'en' => 'New Shipment',
                'fr' => 'Nouvelle expédition',
                'sw' => 'Mzigo Mpya',
            ],
            'client.dashboard.actions.get_quote' => [
                'en' => 'Get Quote',
                'fr' => 'Obtenir un devis',
                'sw' => 'Pata Nukuu',
            ],
            'client.dashboard.stats.all_time' => [
                'en' => 'All Time',
                'fr' => 'Tout le temps',
                'sw' => 'Muda wote',
            ],
            'client.dashboard.stats.total_shipments' => [
                'en' => 'Total Shipments',
                'fr' => 'Total des expéditions',
                'sw' => 'Jumla ya Mizigo',
            ],
            'client.dashboard.stats.active' => [
                'en' => 'Active',
                'fr' => 'Actif',
                'sw' => 'Inaendelea',
            ],
            'client.dashboard.stats.in_transit' => [
                'en' => 'In Transit',
                'fr' => 'En transit',
                'sw' => 'Njiani',
            ],
            'client.dashboard.stats.complete' => [
                'en' => 'Complete',
                'fr' => 'Terminé',
                'sw' => 'Imekamilika',
            ],
            'client.dashboard.stats.delivered' => [
                'en' => 'Delivered',
                'fr' => 'Livré',
                'sw' => 'Imefikishwa',
            ],
            'client.dashboard.stats.lifetime' => [
                'en' => 'Lifetime',
                'fr' => 'Depuis le début',
                'sw' => 'Tangu mwanzo',
            ],
            'client.dashboard.stats.total_spent' => [
                'en' => 'Total Spent',
                'fr' => 'Total dépensé',
                'sw' => 'Jumla iliyotumika',
            ],
            'client.dashboard.recent_shipments.title' => [
                'en' => 'Recent Shipments',
                'fr' => 'Expéditions récentes',
                'sw' => 'Mizigo ya Hivi Karibuni',
            ],
            'client.dashboard.recent_shipments.empty' => [
                'en' => 'No shipments yet',
                'fr' => "Aucune expédition pour l'instant",
                'sw' => 'Bado hakuna mizigo',
            ],
            'client.dashboard.recent_shipments.create_first' => [
                'en' => 'Create Your First Shipment',
                'fr' => 'Créez votre première expédition',
                'sw' => 'Unda Mzigo Wako wa Kwanza',
            ],
            'client.dashboard.quick_actions.title' => [
                'en' => 'Quick Actions',
                'fr' => 'Actions rapides',
                'sw' => 'Vitendo vya Haraka',
            ],
            'client.dashboard.quick_actions.track' => [
                'en' => 'Track a Shipment',
                'fr' => 'Suivre une expédition',
                'sw' => 'Fuatilia Mzigo',
            ],
            'client.dashboard.quick_actions.quote' => [
                'en' => 'Get a Quote',
                'fr' => 'Obtenir un devis',
                'sw' => 'Pata Nukuu',
            ],
            'client.dashboard.quick_actions.addresses' => [
                'en' => 'Manage Addresses',
                'fr' => 'Gérer les adresses',
                'sw' => 'Dhibiti Anwani',
            ],
            'client.dashboard.quick_actions.invoices' => [
                'en' => 'View Invoices',
                'fr' => 'Voir les factures',
                'sw' => 'Tazama Ankara',
            ],
            'client.dashboard.account_summary.title' => [
                'en' => 'Account Summary',
                'fr' => 'Résumé du compte',
                'sw' => 'Muhtasari wa Akaunti',
            ],
            'client.dashboard.account_summary.customer_id' => [
                'en' => 'Customer ID',
                'fr' => 'ID client',
                'sw' => 'Kitambulisho cha Mteja',
            ],
            'client.dashboard.account_summary.account_type' => [
                'en' => 'Account Type',
                'fr' => 'Type de compte',
                'sw' => 'Aina ya Akaunti',
            ],
            'client.dashboard.account_summary.account_type_regular' => [
                'en' => 'Regular',
                'fr' => 'Standard',
                'sw' => 'Kawaida',
            ],
            'client.dashboard.account_summary.discount_rate' => [
                'en' => 'Discount Rate',
                'fr' => 'Taux de remise',
                'sw' => 'Kiwango cha Punguzo',
            ],
            'client.dashboard.account_summary.credit_used' => [
                'en' => 'Credit Used',
                'fr' => 'Crédit utilisé',
                'sw' => 'Mkopo Uliotumika',
            ],

            // Client shipments
            'client.shipments.title' => [
                'en' => 'My Shipments',
                'fr' => 'Mes expéditions',
                'sw' => 'Mizigo Yangu',
            ],
            'client.shipments.header' => [
                'en' => 'My Shipments',
                'fr' => 'Mes expéditions',
                'sw' => 'Mizigo Yangu',
            ],
            'client.shipments.stats.total' => [
                'en' => 'Total',
                'fr' => 'Total',
                'sw' => 'Jumla',
            ],
            'client.shipments.stats.in_transit' => [
                'en' => 'In Transit',
                'fr' => 'En transit',
                'sw' => 'Njiani',
            ],
            'client.shipments.stats.delivered' => [
                'en' => 'Delivered',
                'fr' => 'Livré',
                'sw' => 'Imefikishwa',
            ],
            'client.shipments.actions.new' => [
                'en' => 'New Shipment',
                'fr' => 'Nouvelle expédition',
                'sw' => 'Mzigo Mpya',
            ],
            'client.shipments.search.placeholder' => [
                'en' => 'Search tracking number...',
                'fr' => 'Rechercher un numéro de suivi...',
                'sw' => 'Tafuta namba ya ufuatiliaji...',
            ],
            'client.shipments.search.all_statuses' => [
                'en' => 'All Statuses',
                'fr' => 'Tous les statuts',
                'sw' => 'Hali zote',
            ],
            'client.shipments.status.pending' => [
                'en' => 'Pending',
                'fr' => 'En attente',
                'sw' => 'Inasubiri',
            ],
            'client.shipments.status.booked' => [
                'en' => 'Booked',
                'fr' => 'Réservé',
                'sw' => 'Imehifadhiwa',
            ],
            'client.shipments.status.in_transit' => [
                'en' => 'In Transit',
                'fr' => 'En transit',
                'sw' => 'Njiani',
            ],
            'client.shipments.status.out_for_delivery' => [
                'en' => 'Out for delivery',
                'fr' => 'En cours de livraison',
                'sw' => 'Imetoka kwa uwasilishaji',
            ],
            'client.shipments.status.delivered' => [
                'en' => 'Delivered',
                'fr' => 'Livré',
                'sw' => 'Imefikishwa',
            ],
            'client.shipments.status.picked_up' => [
                'en' => 'Picked up',
                'fr' => 'Enlevé',
                'sw' => 'Imepokelewa',
            ],
            'client.shipments.empty' => [
                'en' => 'No shipments found',
                'fr' => 'Aucune expédition trouvée',
                'sw' => 'Hakuna mizigo iliyopatikana',
            ],
            'client.shipments.create_first' => [
                'en' => 'Create Your First Shipment',
                'fr' => 'Créez votre première expédition',
                'sw' => 'Unda Mzigo Wako wa Kwanza',
            ],
            'client.shipments.origin' => [
                'en' => 'Origin',
                'fr' => 'Origine',
                'sw' => 'Asili',
            ],
            'client.shipments.destination' => [
                'en' => 'Destination',
                'fr' => 'Destination',
                'sw' => 'Mahali',
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
                    'description' => 'seed:client_ui',
                    'metadata' => json_encode(['source' => 'seed', 'domain' => 'client_ui']),
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
            ->where('description', 'seed:client_ui')
            ->delete();

        foreach (['en', 'fr', 'sw'] as $locale) {
            Cache::forget("translations_array_{$locale}");
            Cache::forget("api_translations_{$locale}");
        }
    }
};

