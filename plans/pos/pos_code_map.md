# POS Code Map

## Front-End (Blade/JS)
- Admin POS UI: `resources/views/admin/pos/index.blade.php`
- Branch POS UI: `resources/views/branch/pos/index.blade.php`
- Shared receipt template: `resources/views/shared/pos/receipt.blade.php`
- Shipping label (used by POS labels): `resources/views/branch/shipments/label.blade.php`

## Backend Controllers & Routes
- Shared POS controller (admin + branch): `app/Http/Controllers/Shared/ShipmentPosController.php`
- Admin routes (POS): `routes/web.php` → `Route::prefix('admin/pos')->...`
- Branch routes (POS): `routes/web.php` → `Route::prefix('pos')->...` inside branch group

## Services & Domain
- Rate calculation: `app/Services/Pricing/RateCalculationService.php`
- Shipment creation/lifecycle: `app/Services/ShipmentService.php`, `app/Services/Logistics/ShipmentLifecycleService.php`
- Label generation: `app/Services/LabelGeneratorService.php`

## Models & Data
- Shipment model/table: `app/Models/Shipment.php` (table `shipments`)
- Customer profile model/table: `app/Models/Customer.php` (table `customers`)
- Payment model/table: `app/Models/Payment.php` (table `payments`)
- Rate cards model/table: `app/Models/RateCard.php` (table `rate_cards`)

## Migrations (recent POS-related)
- Add customer profile FK: `database/migrations/2025_11_28_000001_add_customer_profile_to_shipments.php`
- Rate card fields for service_level etc.: `database/migrations/2025_11_28_000100_add_service_level_fields_to_rate_cards.php`
- Seed default rate cards: `database/migrations/2025_11_28_000200_seed_default_rate_cards.php`

## Other Integration Points
- Branch context helper: `app/Services/BranchContext.php`
- POS metadata persistence: stored in `shipments.metadata` during POS creation
