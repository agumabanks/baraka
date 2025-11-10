<?php

namespace Database\Seeders;

use App\Models\Translation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing translations
        DB::table('translations')->delete();

        $translations = [
            // Auth translations
            'auth.failed' => [
                'en' => 'You are not an active person, please contact Admin!',
                'fr' => 'Vous n\'êtes pas une personne active, veuillez contacter l\'administrateur !',
                'sw' => 'Wewe sio mtu active, tafadhali wasiliana na Admin!',
            ],
            'auth.password' => [
                'en' => 'The provided password is incorrect.',
                'fr' => 'Le mot de passe fourni est incorrect.',
                'sw' => 'Nenosiri lililotolewa si sahihi.',
            ],
            'auth.throttle' => [
                'en' => 'Too many login attempts. Please try again in :seconds seconds.',
                'fr' => 'Trop de tentatives de connexion. Veuillez réessayer dans :seconds secondes.',
                'sw' => 'Jaribu nyingi za kuingia. Tafadhali jaribu tena baada ya :seconds sekunde.',
            ],
            'auth.signin_msg' => [
                'en' => 'Signin successfully!',
                'fr' => 'Connexion réussie !',
                'sw' => 'Umeingia kwa mafanikio!',
            ],
            'auth.profile_msg' => [
                'en' => 'Profile successfully!',
                'fr' => 'Profil réussi !',
                'sw' => 'Profil imefanikiwa!',
            ],

            // Dashboard translations
            'dashboard.title' => [
                'en' => 'Dashboard',
                'fr' => 'Tableau de bord',
                'sw' => 'Bodi ya dashibodi',
            ],
            'dashboard.courier' => [
                'en' => 'Courier',
                'fr' => 'Coursier',
                'sw' => 'Abiria',
            ],
            'dashboard.merchant' => [
                'en' => 'Merchant',
                'fr' => 'Marchand',
                'sw' => 'Mwuzaji',
            ],
            'dashboard.delivery_man' => [
                'en' => 'Delivery Man',
                'fr' => 'Livreur',
                'sw' => 'Mtu wa kutoa',
            ],
            'dashboard.hub' => [
                'en' => 'Branch',
                'fr' => 'Agence',
                'sw' => 'Tawi',
            ],
            'dashboard.total_parcel' => [
                'en' => 'Total Parcel',
                'fr' => 'Total Colis',
                'sw' => 'Jumla ya kifurushi',
            ],
            'dashboard.total_user' => [
                'en' => 'Total User',
                'fr' => 'Total Utilisateurs',
                'sw' => 'Jumla ya Watumiaji',
            ],
            'dashboard.total_merchant' => [
                'en' => 'Total Merchant',
                'fr' => 'Total Marchands',
                'sw' => 'Jumla ya Wauzaji',
            ],
            'dashboard.total_delivery_man' => [
                'en' => 'Total Delivery Man',
                'fr' => 'Total Livreurs',
                'sw' => 'Jumla ya Watu wa Kutoa',
            ],
            'dashboard.total_hubs' => [
                'en' => 'Total Branch',
                'fr' => 'Total Agences',
                'sw' => 'Jumla ya Matawi',
            ],
            'dashboard.total_accounts' => [
                'en' => 'Total Accounts',
                'fr' => 'Total Comptes',
                'sw' => 'Jumla ya Akaunti',
            ],
            'dashboard.balance' => [
                'en' => 'Balance',
                'fr' => 'Solde',
                'sw' => 'Salio',
            ],
            'dashboard.total_balance' => [
                'en' => 'Total Balance',
                'fr' => 'Solde total',
                'sw' => 'Jumla ya Salio',
            ],
            'dashboard.revenue' => [
                'en' => 'Revenue',
                'fr' => 'Revenu',
                'sw' => 'Mapato',
            ],
            'dashboard.recent' => [
                'en' => 'Recent',
                'fr' => 'Récent',
                'sw' => 'Hivi karibuni',
            ],
            'dashboard.view_details' => [
                'en' => 'View Details',
                'fr' => 'Voir les détails',
                'sw' => 'Ona Maelezo',
            ],
            'dashboard.total' => [
                'en' => 'Total',
                'fr' => 'Total',
                'sw' => 'Jumla',
            ],
            'dashboard.pending' => [
                'en' => 'Pending',
                'fr' => 'En attente',
                'sw' => 'Inasubiri',
            ],
            'dashboard.deliver' => [
                'en' => 'Delivered',
                'fr' => 'Livré',
                'sw' => 'Imetolewa',
            ],
            'dashboard.return' => [
                'en' => 'Return',
                'fr' => 'Retour',
                'sw' => 'Rudi',
            ],
            'dashboard.today' => [
                'en' => 'Today',
                'fr' => 'Aujourd\'hui',
                'sw' => 'Leo',
            ],
            'dashboard.yesterday' => [
                'en' => 'Yesterday',
                'fr' => 'Hier',
                'sw' => 'Jana',
            ],
            'dashboard.last_week' => [
                'en' => 'Last Week',
                'fr' => 'La semaine dernière',
                'sw' => 'Wiki iliyopita',
            ],
            'dashboard.custom' => [
                'en' => 'Custom',
                'fr' => 'Personnalisé',
                'sw' => 'Desturi',
            ],

            // Common translations
            'common.save' => [
                'en' => 'Save',
                'fr' => 'Enregistrer',
                'sw' => 'Hifadhi',
            ],
            'common.cancel' => [
                'en' => 'Cancel',
                'fr' => 'Annuler',
                'sw' => 'Ghairi',
            ],
            'common.edit' => [
                'en' => 'Edit',
                'fr' => 'Modifier',
                'sw' => 'Hariri',
            ],
            'common.delete' => [
                'en' => 'Delete',
                'fr' => 'Supprimer',
                'sw' => 'Futa',
            ],
            'common.create' => [
                'en' => 'Create',
                'fr' => 'Créer',
                'sw' => 'Unda',
            ],
            'common.update' => [
                'en' => 'Update',
                'fr' => 'Mettre à jour',
                'sw' => 'Sasisha',
            ],
            'common.view' => [
                'en' => 'View',
                'fr' => 'Voir',
                'sw' => 'Ona',
            ],
            'common.back' => [
                'en' => 'Back',
                'fr' => 'Retour',
                'sw' => 'Rudi',
            ],
            'common.next' => [
                'en' => 'Next',
                'fr' => 'Suivant',
                'sw' => 'Nexti',
            ],
            'common.previous' => [
                'en' => 'Previous',
                'fr' => 'Précédent',
                'sw' => 'Iliyopita',
            ],
            'common.search' => [
                'en' => 'Search',
                'fr' => 'Rechercher',
                'sw' => 'Tafuta',
            ],
            'common.filter' => [
                'en' => 'Filter',
                'fr' => 'Filtrer',
                'sw' => 'Chuja',
            ],
            'common.export' => [
                'en' => 'Export',
                'fr' => 'Exporter',
                'sw' => 'Toa',
            ],
            'common.print' => [
                'en' => 'Print',
                'fr' => 'Imprimer',
                'sw' => 'Chapisha',
            ],
            'common.close' => [
                'en' => 'Close',
                'fr' => 'Fermer',
                'sw' => 'Funga',
            ],
            'common.confirm' => [
                'en' => 'Confirm',
                'fr' => 'Confirmer',
                'sw' => 'Thibitisha',
            ],
            'common.yes' => [
                'en' => 'Yes',
                'fr' => 'Oui',
                'sw' => 'Ndiyo',
            ],
            'common.no' => [
                'en' => 'No',
                'fr' => 'Non',
                'sw' => 'Hapana',
            ],
            'common.loading' => [
                'en' => 'Loading...',
                'fr' => 'Chargement...',
                'sw' => 'Inapakia...',
            ],
            'common.no_data' => [
                'en' => 'No data available',
                'fr' => 'Aucune donnée disponible',
                'sw' => 'Hakuna data inapatikana',
            ],

            // Settings translations
            'settings.title' => [
                'en' => 'Settings',
                'fr' => 'Paramètres',
                'sw' => 'Mipangilio',
            ],
            'settings.general' => [
                'en' => 'General',
                'fr' => 'Général',
                'sw' => 'Jumla',
            ],
            'settings.language' => [
                'en' => 'Language',
                'fr' => 'Langue',
                'sw' => 'Lugha',
            ],
            'settings.english' => [
                'en' => 'English',
                'fr' => 'Anglais',
                'sw' => 'Kiingereza',
            ],
            'settings.french' => [
                'en' => 'French',
                'fr' => 'Français',
                'sw' => 'Kifaransa',
            ],
            'settings.swahili' => [
                'en' => 'Swahili',
                'fr' => 'Swahili',
                'sw' => 'Kiswahili',
            ],
            'settings.translations' => [
                'en' => 'Translations',
                'fr' => 'Traductions',
                'sw' => 'Tafsiri',
            ],
            'settings.manage_translations' => [
                'en' => 'Manage Translations',
                'fr' => 'Gérer les traductions',
                'sw' => 'Simamia Tafsiri',
            ],
            'settings.translations_description' => [
                'en' => 'Manage and edit all application translations',
                'fr' => 'Gérer et modifier toutes les traductions d\'application',
                'sw' => 'Simamia na hariri tafsiri zote za programu',
            ],
            'settings.select_language' => [
                'en' => 'Select Default Language',
                'fr' => 'Sélectionner la langue par défaut',
                'sw' => 'Chagua Lugha ya Chaguo-msingi',
            ],
            'settings.current_language' => [
                'en' => 'Current Language',
                'fr' => 'Langue actuelle',
                'sw' => 'Lugha ya Sasa',
            ],
            'settings.save_changes' => [
                'en' => 'Save Changes',
                'fr' => 'Sauvegarder les modifications',
                'sw' => 'Hifadhi Mabadiliko',
            ],
            'settings.add_translation' => [
                'en' => 'Add Translation',
                'fr' => 'Ajouter une traduction',
                'sw' => 'Ongeza Tafsiri',
            ],
            'settings.translation_key' => [
                'en' => 'Translation Key',
                'fr' => 'Clé de traduction',
                'sw' => 'Ufunguo wa Tafsiri',
            ],
            'settings.translation_value' => [
                'en' => 'Translation Value',
                'fr' => 'Valeur de traduction',
                'sw' => 'Thamani ya Tafsiri',
            ],
            'settings.language_code' => [
                'en' => 'Language Code',
                'fr' => 'Code de langue',
                'sw' => 'Msimbo wa Lugha',
            ],
            'settings.description' => [
                'en' => 'Description',
                'fr' => 'Description',
                'sw' => 'Maelezo',
            ],

            // Message translations
            'messages.success' => [
                'en' => 'Success',
                'fr' => 'Succès',
                'sw' => 'Mafanikio',
            ],
            'messages.error' => [
                'en' => 'Error',
                'fr' => 'Erreur',
                'sw' => 'Kosa',
            ],
            'messages.warning' => [
                'en' => 'Warning',
                'fr' => 'Avertissement',
                'sw' => 'Onyo',
            ],
            'messages.info' => [
                'en' => 'Information',
                'fr' => 'Information',
                'sw' => 'Maelezo',
            ],
            'messages.saved_successfully' => [
                'en' => 'Saved successfully',
                'fr' => 'Enregistré avec succès',
                'sw' => 'Imehifadhiwa kwa mafanikio',
            ],
            'messages.updated_successfully' => [
                'en' => 'Updated successfully',
                'fr' => 'Mis à jour avec succès',
                'sw' => 'Imesasishwa kwa mafanikio',
            ],
            'messages.deleted_successfully' => [
                'en' => 'Deleted successfully',
                'fr' => 'Supprimé avec succès',
                'sw' => 'Imefutwa kwa mafanikio',
            ],
            'messages.operation_completed' => [
                'en' => 'Operation completed successfully',
                'fr' => 'Opération terminée avec succès',
                'sw' => 'Opereshioni imekamilika kwa mafanikio',
            ],
        ];

        foreach ($translations as $key => $values) {
            foreach ($values as $languageCode => $value) {
                Translation::create([
                    'key' => $key,
                    'language_code' => $languageCode,
                    'value' => $value,
                    'description' => $this->getDescriptionForKey($key),
                ]);
            }
        }

        $this->command->info('Initial translations seeded successfully.');
    }

    /**
     * Get description for a translation key.
     */
    private function getDescriptionForKey(string $key): string
    {
        $descriptions = [
            'auth.failed' => 'Authentication failed message',
            'auth.password' => 'Wrong password error message',
            'auth.throttle' => 'Too many login attempts message',
            'dashboard.title' => 'Dashboard page title',
            'common.save' => 'Save button text',
            'common.cancel' => 'Cancel button text',
            'settings.title' => 'Settings section title',
            'settings.language' => 'Language settings label',
        ];

        return $descriptions[$key] ?? 'Translation for ' . $key;
    }
}
