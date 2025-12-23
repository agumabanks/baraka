<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $translations = [
            // Shared client/common
            'client.common.na' => [
                'en' => 'N/A',
                'fr' => 'N/A',
                'sw' => 'N/A',
            ],
            'client.common.kg' => [
                'en' => 'kg',
                'fr' => 'kg',
                'sw' => 'kg',
            ],
            'client.common.standard' => [
                'en' => 'Standard',
                'fr' => 'Standard',
                'sw' => 'Kawaida',
            ],
            'client.common.cancel' => [
                'en' => 'Cancel',
                'fr' => 'Annuler',
                'sw' => 'Ghairi',
            ],
            'client.common.save_changes' => [
                'en' => 'Save Changes',
                'fr' => 'Enregistrer les modifications',
                'sw' => 'Hifadhi Mabadiliko',
            ],

            // Client tracking
            'client.tracking.title' => [
                'en' => 'Track Shipment',
                'fr' => "Suivre l'expédition",
                'sw' => 'Fuatilia Mzigo',
            ],
            'client.tracking.header' => [
                'en' => 'Track Shipment',
                'fr' => "Suivre l'expédition",
                'sw' => 'Fuatilia Mzigo',
            ],
            'client.tracking.hero.title' => [
                'en' => 'Track Your Shipment',
                'fr' => "Suivez votre expédition",
                'sw' => 'Fuatilia Mzigo Wako',
            ],
            'client.tracking.hero.subtitle' => [
                'en' => 'Enter your tracking number or AWB to get the latest status',
                'fr' => 'Entrez votre numéro de suivi ou AWB pour obtenir le dernier statut',
                'sw' => 'Weka namba ya ufuatiliaji au AWB kupata hali ya sasa',
            ],
            'client.tracking.search.placeholder' => [
                'en' => 'Enter tracking number...',
                'fr' => 'Entrez le numéro de suivi...',
                'sw' => 'Weka namba ya ufuatiliaji...',
            ],
            'client.tracking.search.submit' => [
                'en' => 'Track',
                'fr' => 'Suivre',
                'sw' => 'Fuatilia',
            ],
            'client.tracking.result.tracking_number' => [
                'en' => 'Tracking Number',
                'fr' => 'Numéro de suivi',
                'sw' => 'Namba ya Ufuatiliaji',
            ],
            'client.tracking.result.from' => [
                'en' => 'From',
                'fr' => 'De',
                'sw' => 'Kutoka',
            ],
            'client.tracking.result.to' => [
                'en' => 'To',
                'fr' => 'À',
                'sw' => 'Kwenda',
            ],
            'client.tracking.result.created' => [
                'en' => 'Created',
                'fr' => 'Créé',
                'sw' => 'Imeundwa',
            ],
            'client.tracking.result.weight' => [
                'en' => 'Weight',
                'fr' => 'Poids',
                'sw' => 'Uzito',
            ],
            'client.tracking.result.service' => [
                'en' => 'Service',
                'fr' => 'Service',
                'sw' => 'Huduma',
            ],
            'client.tracking.not_found.title' => [
                'en' => 'Shipment Not Found',
                'fr' => 'Expédition introuvable',
                'sw' => 'Mzigo Haujapatikana',
            ],
            'client.tracking.not_found.message' => [
                'en' => "We couldn't find a shipment with tracking number: :tracking",
                'fr' => "Nous n'avons pas trouvé d'expédition avec le numéro de suivi : :tracking",
                'sw' => 'Hatukuweza kupata mzigo wenye namba ya ufuatiliaji: :tracking',
            ],
            'client.tracking.not_found.hint' => [
                'en' => 'Please check the tracking number and try again.',
                'fr' => 'Veuillez vérifier le numéro de suivi et réessayer.',
                'sw' => 'Tafadhali hakiki namba ya ufuatiliaji kisha ujaribu tena.',
            ],

            // Client quotes
            'client.quotes.title' => [
                'en' => 'Get Quote',
                'fr' => 'Obtenir un devis',
                'sw' => 'Pata Nukuu',
            ],
            'client.quotes.header' => [
                'en' => 'Get a Quote',
                'fr' => 'Obtenir un devis',
                'sw' => 'Pata Nukuu',
            ],
            'client.quotes.calculator.title' => [
                'en' => 'Shipping Quote Calculator',
                'fr' => 'Calculateur de devis de livraison',
                'sw' => 'Kikokotoo cha Nukuu ya Usafirishaji',
            ],
            'client.quotes.form.from' => [
                'en' => 'From (Origin)',
                'fr' => "De (origine)",
                'sw' => 'Kutoka (Asili)',
            ],
            'client.quotes.form.to' => [
                'en' => 'To (Destination)',
                'fr' => 'À (destination)',
                'sw' => 'Kwenda (Mahali)',
            ],
            'client.quotes.form.select_origin' => [
                'en' => 'Select origin...',
                'fr' => "Sélectionnez l'origine...",
                'sw' => 'Chagua asili...',
            ],
            'client.quotes.form.select_destination' => [
                'en' => 'Select destination...',
                'fr' => 'Sélectionnez la destination...',
                'sw' => 'Chagua mahali...',
            ],
            'client.quotes.form.weight' => [
                'en' => 'Weight (kg)',
                'fr' => 'Poids (kg)',
                'sw' => 'Uzito (kg)',
            ],
            'client.quotes.form.length' => [
                'en' => 'Length (cm)',
                'fr' => 'Longueur (cm)',
                'sw' => 'Urefu (cm)',
            ],
            'client.quotes.form.width' => [
                'en' => 'Width (cm)',
                'fr' => 'Largeur (cm)',
                'sw' => 'Upana (cm)',
            ],
            'client.quotes.form.height' => [
                'en' => 'Height (cm)',
                'fr' => 'Hauteur (cm)',
                'sw' => 'Kimo (cm)',
            ],
            'client.quotes.actions.calculate' => [
                'en' => 'Calculate Quote',
                'fr' => 'Calculer le devis',
                'sw' => 'Hesabu Nukuu',
            ],
            'client.quotes.results.title' => [
                'en' => 'Available Services',
                'fr' => 'Services disponibles',
                'sw' => 'Huduma Zinazopatikana',
            ],
            'client.quotes.state.calculating' => [
                'en' => 'Calculating...',
                'fr' => 'Calcul en cours...',
                'sw' => 'Inahesabu...',
            ],
            'client.quotes.errors.calculating' => [
                'en' => 'Error calculating quote. Please try again.',
                'fr' => 'Erreur lors du calcul du devis. Veuillez réessayer.',
                'sw' => 'Hitilafu katika kuhesabu nukuu. Tafadhali jaribu tena.',
            ],
            'client.quotes.empty' => [
                'en' => 'No services available for this route.',
                'fr' => 'Aucun service disponible pour cet itinéraire.',
                'sw' => 'Hakuna huduma zinazopatikana kwa njia hii.',
            ],
            'client.quotes.breakdown.base_rate' => [
                'en' => 'Base Rate',
                'fr' => 'Tarif de base',
                'sw' => 'Kiwango cha Msingi',
            ],
            'client.quotes.breakdown.weight' => [
                'en' => 'Weight',
                'fr' => 'Poids',
                'sw' => 'Uzito',
            ],
            'client.quotes.breakdown.discount' => [
                'en' => 'Discount',
                'fr' => 'Remise',
                'sw' => 'Punguzo',
            ],
            'client.quotes.actions.select_service' => [
                'en' => 'Select :service',
                'fr' => 'Sélectionner :service',
                'sw' => 'Chagua :service',
            ],
            'client.quotes.service.economy' => [
                'en' => 'Economy',
                'fr' => 'Économie',
                'sw' => 'Kiuchumi',
            ],
            'client.quotes.service.standard' => [
                'en' => 'Standard',
                'fr' => 'Standard',
                'sw' => 'Kawaida',
            ],
            'client.quotes.service.express' => [
                'en' => 'Express',
                'fr' => 'Express',
                'sw' => 'Haraka',
            ],
            'client.quotes.service.priority' => [
                'en' => 'Priority',
                'fr' => 'Prioritaire',
                'sw' => 'Kipaumbele',
            ],
            'client.quotes.service_desc.economy' => [
                'en' => '7-10 business days',
                'fr' => '7 à 10 jours ouvrables',
                'sw' => 'Siku 7-10 za kazi',
            ],
            'client.quotes.service_desc.standard' => [
                'en' => '5-7 business days',
                'fr' => '5 à 7 jours ouvrables',
                'sw' => 'Siku 5-7 za kazi',
            ],
            'client.quotes.service_desc.express' => [
                'en' => '2-3 business days',
                'fr' => '2 à 3 jours ouvrables',
                'sw' => 'Siku 2-3 za kazi',
            ],
            'client.quotes.service_desc.priority' => [
                'en' => '1-2 business days',
                'fr' => '1 à 2 jours ouvrables',
                'sw' => 'Siku 1-2 za kazi',
            ],

            // Client addresses
            'client.addresses.title' => [
                'en' => 'Address Book',
                'fr' => "Carnet d'adresses",
                'sw' => 'Kitabu cha Anwani',
            ],
            'client.addresses.header' => [
                'en' => 'Address Book',
                'fr' => "Carnet d'adresses",
                'sw' => 'Kitabu cha Anwani',
            ],
            'client.addresses.subtitle' => [
                'en' => 'Manage your saved addresses for faster checkout',
                'fr' => 'Gérez vos adresses enregistrées pour un paiement plus rapide',
                'sw' => 'Dhibiti anwani ulizohifadhi kwa urahisi zaidi',
            ],
            'client.addresses.actions.add' => [
                'en' => 'Add Address',
                'fr' => 'Ajouter une adresse',
                'sw' => 'Ongeza Anwani',
            ],
            'client.addresses.default' => [
                'en' => 'Default',
                'fr' => 'Par défaut',
                'sw' => 'Chaguo-msingi',
            ],
            'client.addresses.delete_confirm' => [
                'en' => 'Delete this address?',
                'fr' => 'Supprimer cette adresse ?',
                'sw' => 'Futa anwani hii?',
            ],
            'client.addresses.empty.title' => [
                'en' => 'No Saved Addresses',
                'fr' => 'Aucune adresse enregistrée',
                'sw' => 'Hakuna anwani zilizohifadhiwa',
            ],
            'client.addresses.empty.subtitle' => [
                'en' => 'Add addresses for faster shipping',
                'fr' => 'Ajoutez des adresses pour une expédition plus rapide',
                'sw' => 'Ongeza anwani kwa usafirishaji wa haraka',
            ],
            'client.addresses.empty.cta' => [
                'en' => 'Add Your First Address',
                'fr' => 'Ajoutez votre première adresse',
                'sw' => 'Ongeza Anwani ya Kwanza',
            ],
            'client.addresses.modal.title' => [
                'en' => 'Add New Address',
                'fr' => 'Ajouter une nouvelle adresse',
                'sw' => 'Ongeza Anwani Mpya',
            ],
            'client.addresses.form.label' => [
                'en' => 'Label',
                'fr' => 'Libellé',
                'sw' => 'Lebo',
            ],
            'client.addresses.form.label_placeholder' => [
                'en' => 'e.g., Home, Office',
                'fr' => 'ex. : Maison, Bureau',
                'sw' => 'mf. Nyumbani, Ofisi',
            ],
            'client.addresses.form.contact_name' => [
                'en' => 'Contact Name',
                'fr' => 'Nom du contact',
                'sw' => 'Jina la Mawasiliano',
            ],
            'client.addresses.form.phone' => [
                'en' => 'Phone',
                'fr' => 'Téléphone',
                'sw' => 'Simu',
            ],
            'client.addresses.form.address_1' => [
                'en' => 'Address Line 1',
                'fr' => "Adresse ligne 1",
                'sw' => 'Anwani Mstari 1',
            ],
            'client.addresses.form.address_2' => [
                'en' => 'Address Line 2',
                'fr' => "Adresse ligne 2",
                'sw' => 'Anwani Mstari 2',
            ],
            'client.addresses.form.city' => [
                'en' => 'City',
                'fr' => 'Ville',
                'sw' => 'Jiji',
            ],
            'client.addresses.form.country' => [
                'en' => 'Country',
                'fr' => 'Pays',
                'sw' => 'Nchi',
            ],
            'client.addresses.form.postal_code' => [
                'en' => 'Postal Code',
                'fr' => 'Code postal',
                'sw' => 'Msimbo wa Posta',
            ],
            'client.addresses.form.set_default' => [
                'en' => 'Set as default',
                'fr' => 'Définir par défaut',
                'sw' => 'Weka kama chaguo-msingi',
            ],
            'client.addresses.actions.save' => [
                'en' => 'Save Address',
                'fr' => "Enregistrer l'adresse",
                'sw' => 'Hifadhi Anwani',
            ],

            // Client invoices
            'client.invoices.title' => [
                'en' => 'Invoices',
                'fr' => 'Factures',
                'sw' => 'Ankara',
            ],
            'client.invoices.header' => [
                'en' => 'Invoices',
                'fr' => 'Factures',
                'sw' => 'Ankara',
            ],
            'client.invoices.status.paid' => [
                'en' => 'Paid',
                'fr' => 'Payée',
                'sw' => 'Imelipwa',
            ],
            'client.invoices.status.unpaid' => [
                'en' => 'Unpaid',
                'fr' => 'Impayée',
                'sw' => 'Haijalipwa',
            ],
            'client.invoices.empty.title' => [
                'en' => 'No Invoices',
                'fr' => 'Aucune facture',
                'sw' => 'Hakuna ankara',
            ],
            'client.invoices.empty.subtitle' => [
                'en' => "You don't have any invoices yet.",
                'fr' => "Vous n'avez pas encore de factures.",
                'sw' => 'Bado huna ankara.',
            ],

            // Client profile
            'client.profile.title' => [
                'en' => 'Profile',
                'fr' => 'Profil',
                'sw' => 'Wasifu',
            ],
            'client.profile.header' => [
                'en' => 'My Profile',
                'fr' => 'Mon profil',
                'sw' => 'Wasifu Wangu',
            ],
            'client.profile.info.title' => [
                'en' => 'Profile Information',
                'fr' => 'Informations du profil',
                'sw' => 'Taarifa za Wasifu',
            ],
            'client.profile.fields.company_name' => [
                'en' => 'Company Name',
                'fr' => "Nom de l'entreprise",
                'sw' => 'Jina la Kampuni',
            ],
            'client.profile.fields.company_name_placeholder' => [
                'en' => 'Your company name',
                'fr' => "Nom de votre entreprise",
                'sw' => 'Jina la kampuni yako',
            ],
            'client.profile.fields.contact_person' => [
                'en' => 'Contact Person',
                'fr' => 'Personne de contact',
                'sw' => 'Mtu wa Mawasiliano',
            ],
            'client.profile.fields.phone' => [
                'en' => 'Phone',
                'fr' => 'Téléphone',
                'sw' => 'Simu',
            ],
            'client.profile.fields.mobile' => [
                'en' => 'Mobile',
                'fr' => 'Mobile',
                'sw' => 'Simu ya mkononi',
            ],
            'client.profile.fields.billing_address' => [
                'en' => 'Billing Address',
                'fr' => 'Adresse de facturation',
                'sw' => 'Anwani ya Malipo',
            ],
            'client.profile.fields.city' => [
                'en' => 'City',
                'fr' => 'Ville',
                'sw' => 'Jiji',
            ],
            'client.profile.fields.country' => [
                'en' => 'Country',
                'fr' => 'Pays',
                'sw' => 'Nchi',
            ],
            'client.profile.password.title' => [
                'en' => 'Change Password',
                'fr' => 'Changer le mot de passe',
                'sw' => 'Badilisha Nenosiri',
            ],
            'client.profile.password.current' => [
                'en' => 'Current Password',
                'fr' => 'Mot de passe actuel',
                'sw' => 'Nenosiri la Sasa',
            ],
            'client.profile.password.new' => [
                'en' => 'New Password',
                'fr' => 'Nouveau mot de passe',
                'sw' => 'Nenosiri Jipya',
            ],
            'client.profile.password.confirm' => [
                'en' => 'Confirm New Password',
                'fr' => 'Confirmer le nouveau mot de passe',
                'sw' => 'Thibitisha Nenosiri Jipya',
            ],
            'client.profile.password.update' => [
                'en' => 'Update Password',
                'fr' => 'Mettre à jour le mot de passe',
                'sw' => 'Sasisha Nenosiri',
            ],
            'client.profile.member_since' => [
                'en' => 'Member Since',
                'fr' => 'Membre depuis',
                'sw' => 'Mwanachama Tangu',
            ],
            'client.profile.stats.title' => [
                'en' => 'Your Statistics',
                'fr' => 'Vos statistiques',
                'sw' => 'Takwimu Zako',
            ],
            'client.profile.stats.last_shipment' => [
                'en' => 'Last Shipment',
                'fr' => 'Dernière expédition',
                'sw' => 'Mzigo wa Mwisho',
            ],

            // Client support
            'client.support.title' => [
                'en' => 'Support',
                'fr' => 'Support',
                'sw' => 'Msaada',
            ],
            'client.support.header' => [
                'en' => 'Support',
                'fr' => 'Support',
                'sw' => 'Msaada',
            ],
            'client.support.contact.title' => [
                'en' => 'Contact Us',
                'fr' => 'Contactez-nous',
                'sw' => 'Wasiliana Nasi',
            ],
            'client.support.contact.subject' => [
                'en' => 'Subject',
                'fr' => 'Sujet',
                'sw' => 'Mada',
            ],
            'client.support.contact.topic_select' => [
                'en' => 'Select a topic...',
                'fr' => 'Choisissez un sujet...',
                'sw' => 'Chagua mada...',
            ],
            'client.support.contact.topic.shipment' => [
                'en' => 'Shipment Issue',
                'fr' => "Problème d'expédition",
                'sw' => 'Tatizo la Mzigo',
            ],
            'client.support.contact.topic.billing' => [
                'en' => 'Billing Question',
                'fr' => 'Question de facturation',
                'sw' => 'Swali la Malipo',
            ],
            'client.support.contact.topic.account' => [
                'en' => 'Account Support',
                'fr' => 'Support du compte',
                'sw' => 'Msaada wa Akaunti',
            ],
            'client.support.contact.topic.other' => [
                'en' => 'Other',
                'fr' => 'Autre',
                'sw' => 'Nyingine',
            ],
            'client.support.contact.message' => [
                'en' => 'Message',
                'fr' => 'Message',
                'sw' => 'Ujumbe',
            ],
            'client.support.contact.message_placeholder' => [
                'en' => 'How can we help you?',
                'fr' => 'Comment pouvons-nous vous aider ?',
                'sw' => 'Tunawezaje kukusaidia?',
            ],
            'client.support.contact.send' => [
                'en' => 'Send Message',
                'fr' => 'Envoyer le message',
                'sw' => 'Tuma Ujumbe',
            ],
            'client.support.faq.title' => [
                'en' => 'Frequently Asked Questions',
                'fr' => 'Questions fréquemment posées',
                'sw' => 'Maswali Yanayoulizwa Mara kwa Mara',
            ],
            'client.support.faq.q1' => [
                'en' => 'How do I track my shipment?',
                'fr' => 'Comment suivre mon expédition ?',
                'sw' => 'Ninafuatiliaje mzigo wangu?',
            ],
            'client.support.faq.a1' => [
                'en' => 'You can track your shipment by entering your tracking number in the Track Shipment page or using the quick track bar in the header.',
                'fr' => "Vous pouvez suivre votre expédition en entrant votre numéro de suivi sur la page de suivi ou en utilisant la barre de suivi rapide dans l'en-tête.",
                'sw' => 'Unaweza kufuatilia mzigo kwa kuweka namba ya ufuatiliaji kwenye ukurasa wa ufuatiliaji au kutumia sehemu ya ufuatiliaji ya haraka kwenye kichwa.',
            ],
            'client.support.faq.q2' => [
                'en' => 'What are the delivery times?',
                'fr' => 'Quels sont les délais de livraison ?',
                'sw' => 'Muda wa uwasilishaji ni upi?',
            ],
            'client.support.faq.a2' => [
                'en' => 'Delivery times vary by service level: Economy (7-10 days), Standard (5-7 days), Express (2-3 days), Priority (1-2 days).',
                'fr' => 'Les délais varient selon le service : Économie (7-10 jours), Standard (5-7 jours), Express (2-3 jours), Prioritaire (1-2 jours).',
                'sw' => 'Muda hutegemea huduma: Kiuchumi (siku 7-10), Kawaida (siku 5-7), Haraka (siku 2-3), Kipaumbele (siku 1-2).',
            ],
            'client.support.faq.q3' => [
                'en' => 'How do I get a quote?',
                'fr' => 'Comment obtenir un devis ?',
                'sw' => 'Ninapataje nukuu?',
            ],
            'client.support.faq.a3' => [
                'en' => 'Use our Get Quote tool to enter your shipment details and receive instant pricing for all service levels.',
                'fr' => "Utilisez l'outil Obtenir un devis pour saisir les détails de votre expédition et obtenir des prix instantanés pour tous les services.",
                'sw' => 'Tumia zana ya Pata Nukuu kuweka maelezo ya mzigo na kupata bei papo hapo kwa huduma zote.',
            ],
            'client.support.info.title' => [
                'en' => 'Contact Information',
                'fr' => 'Informations de contact',
                'sw' => 'Taarifa za Mawasiliano',
            ],
            'client.support.info.email' => [
                'en' => 'Email',
                'fr' => 'Email',
                'sw' => 'Barua pepe',
            ],
            'client.support.info.phone' => [
                'en' => 'Phone',
                'fr' => 'Téléphone',
                'sw' => 'Simu',
            ],
            'client.support.info.hours' => [
                'en' => 'Hours',
                'fr' => 'Heures',
                'sw' => 'Saa',
            ],
            'client.support.info.hours_value' => [
                'en' => '24/7 Support',
                'fr' => 'Support 24/7',
                'sw' => 'Msaada 24/7',
            ],
            'client.support.manager.title' => [
                'en' => 'Your Account Manager',
                'fr' => 'Votre gestionnaire de compte',
                'sw' => 'Msimamizi wa Akaunti Yako',
            ],
            'client.support.manager.fallback' => [
                'en' => 'Account Manager',
                'fr' => 'Gestionnaire de compte',
                'sw' => 'Msimamizi wa Akaunti',
            ],
            'client.support.manager.none' => [
                'en' => 'No account manager assigned. Contact support for assistance.',
                'fr' => "Aucun gestionnaire attribué. Contactez le support pour obtenir de l'aide.",
                'sw' => 'Hakuna msimamizi wa akaunti. Wasiliana na msaada kwa usaidizi.',
            ],

            // Client shipment details
            'client.shipments.show.title' => [
                'en' => 'Shipment :tracking',
                'fr' => 'Expédition :tracking',
                'sw' => 'Mzigo :tracking',
            ],
            'client.shipments.show.header' => [
                'en' => 'Shipment Details',
                'fr' => "Détails de l'expédition",
                'sw' => 'Maelezo ya Mzigo',
            ],
            'client.shipments.show.back' => [
                'en' => 'Back to Shipments',
                'fr' => 'Retour aux expéditions',
                'sw' => 'Rudi kwenye Mizigo',
            ],
            'client.shipments.show.route.title' => [
                'en' => 'Route Information',
                'fr' => "Informations d'itinéraire",
                'sw' => 'Taarifa za Njia',
            ],
            'client.shipments.show.package.title' => [
                'en' => 'Package Details',
                'fr' => 'Détails du colis',
                'sw' => 'Maelezo ya Kifurushi',
            ],
            'client.shipments.show.package.weight' => [
                'en' => 'Weight',
                'fr' => 'Poids',
                'sw' => 'Uzito',
            ],
            'client.shipments.show.package.pieces' => [
                'en' => 'Pieces',
                'fr' => 'Pièces',
                'sw' => 'Vipande',
            ],
            'client.shipments.show.package.service' => [
                'en' => 'Service',
                'fr' => 'Service',
                'sw' => 'Huduma',
            ],
            'client.shipments.show.package.declared_value' => [
                'en' => 'Declared Value',
                'fr' => 'Valeur déclarée',
                'sw' => 'Thamani Iliyotangazwa',
            ],
            'client.shipments.show.package.description' => [
                'en' => 'Description',
                'fr' => 'Description',
                'sw' => 'Maelezo',
            ],
            'client.shipments.show.payment.title' => [
                'en' => 'Payment',
                'fr' => 'Paiement',
                'sw' => 'Malipo',
            ],
            'client.shipments.show.payment.total_amount' => [
                'en' => 'Total Amount',
                'fr' => 'Montant total',
                'sw' => 'Jumla',
            ],
            'client.shipments.show.payment.status' => [
                'en' => 'Payment Status',
                'fr' => 'Statut de paiement',
                'sw' => 'Hali ya Malipo',
            ],
            'client.shipments.payment_status.paid' => [
                'en' => 'Paid',
                'fr' => 'Payé',
                'sw' => 'Imelipwa',
            ],
            'client.shipments.payment_status.unpaid' => [
                'en' => 'Unpaid',
                'fr' => 'Impayé',
                'sw' => 'Haijalipwa',
            ],
            'client.shipments.show.receiver.title' => [
                'en' => 'Receiver',
                'fr' => 'Destinataire',
                'sw' => 'Mpokeaji',
            ],
            'client.shipments.show.receiver.name' => [
                'en' => 'Name',
                'fr' => 'Nom',
                'sw' => 'Jina',
            ],
            'client.shipments.show.receiver.phone' => [
                'en' => 'Phone',
                'fr' => 'Téléphone',
                'sw' => 'Simu',
            ],
            'client.shipments.show.receiver.address' => [
                'en' => 'Address',
                'fr' => 'Adresse',
                'sw' => 'Anwani',
            ],
            'client.shipments.show.timeline.title' => [
                'en' => 'Timeline',
                'fr' => 'Chronologie',
                'sw' => 'Ratiba',
            ],
            'client.shipments.show.timeline.created' => [
                'en' => 'Created',
                'fr' => 'Créé',
                'sw' => 'Imeundwa',
            ],
            'client.shipments.show.timeline.updated' => [
                'en' => 'Last Updated',
                'fr' => 'Dernière mise à jour',
                'sw' => 'Imesasishwa Mwisho',
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
                    'description' => 'seed:client_ui_more',
                    'metadata' => json_encode(['source' => 'seed', 'domain' => 'client_ui_more']),
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
            ->where('description', 'seed:client_ui_more')
            ->delete();

        foreach (['en', 'fr', 'sw'] as $locale) {
            Cache::forget("translations_array_{$locale}");
            Cache::forget("api_translations_{$locale}");
        }
    }
};

