# Zones & Lanes

Permissions: admin.hq, branch_ops with policy BranchScopedPolicy on Zone/Lane.

Endpoints (Admin):
- GET /admin/zones — list zones
- POST /admin/zones — create zone
- GET /admin/lanes — list lanes
- POST /admin/lanes — create lane

Data Model:
- zones: code, name, countries[]
- lanes: origin_zone_id, dest_zone_id, mode (air/road), std_transit_days, dim_divisor, eawb_required

Usage: Lanes feed rating, transit time and e-AWB readiness flags.  




