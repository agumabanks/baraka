# Carriers & Services

Permissions: admin.hq, branch_ops (BranchScopedPolicy)

Endpoints (Admin):
- GET /admin/carriers
- POST /admin/carriers
- GET /admin/carrier-services
- POST /admin/carrier-services

Data Model:
- carriers: name, code, mode
- carrier_services: carrier_id, code, name, requires_eawb

Seeders include common EU↔Africa carriers and services.

