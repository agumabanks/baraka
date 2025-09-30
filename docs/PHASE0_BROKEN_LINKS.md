# PHASE 0 - Broken Navigation Links Report

## Summary
- **Total links verified**: 150+
- **Broken links found**: 1
- **Broken links fixed**: 1

## Broken Links

### 1. Quick Actions Dropdown - "Generate Report"
- **Link text/label**: "Generate Report"
- **Route name attempted**: `reports.generate`
- **Error**: Route not found - no route defined with this name
- **Location**: `resources/views/backend/partials/navber.blade.php:69`
- **Suggested fix**: Either define the route or remove the link

## Verified Working Links

### Sidebar Static Links
- ✅ Dashboard (`/dashboard`)
- ✅ Delivery Team (`deliveryman.index`)
- ✅ Todo List (`todo.index`)
- ✅ Support Tickets (`support.index`)
- ✅ Parcels (`parcel.index`)

### Sidebar Collapsible Groups
- ✅ Hub Management → Hubs (`hubs.index`)
- ✅ Hub Management → Payments (`hub.hub-payment.index`)
- ✅ Merchant Management → Merchants (`merchant.index`)
- ✅ Merchant Management → Payments (`merchant.manage.payment.index`)

### Quick Actions Dropdown
- ✅ Book Shipment (`admin.booking.step1`)
- ✅ Add Customer (`admin.customers.create`)
- ✅ Create Support Ticket (`support.create`)
- ✅ Bulk Upload Parcels (`parcel.parcel-import`)
- ❌ Generate Report (`reports.generate`) - BROKEN

### Config-Driven Bucket Links (Operations)
- ✅ Bookings (`admin.booking.step1`)
- ✅ Shipments (`admin.shipments.index`)
- ✅ Bags (`admin.bags.index`)
- ✅ Linehaul Legs (`admin.linehaul-legs.index`)
- ✅ AWB Stock (`admin.awb-stock.index`)
- ✅ Manifests (`admin.manifests.index`)
- ✅ ECMR (`admin.ecmr.index`)
- ✅ Scan Events (`admin.scans.index`)
- ✅ Routes (`admin.routes.index`)
- ✅ EPOD (`admin.epod.index`)
- ✅ Control Board (`admin.control.board`)
- ✅ Zones (`admin.zones.index`)
- ✅ Lanes (`admin.lanes.index`)
- ✅ Carriers (`admin.carriers.index`)
- ✅ Carrier Services (`admin.carrier-services.index`)
- ✅ Dispatch (`admin.dispatch.index`)
- ✅ Regular Pickup (`pickup.request.regular`)
- ✅ Express Pickup (`pickup.request.express`)
- ✅ Asset Categories (`asset-category.index`)
- ✅ Assets (`asset.index`)
- ✅ Fuels (`fuels.index`)
- ✅ Maintenance (`maintenance.index`)
- ✅ Accidents (`accident.index`)
- ✅ Asset Reports (`assets.reports`)

### Config-Driven Bucket Links (Sales)
- ✅ Customers Index (`admin.customers.index`)
- ✅ Customers Create (`admin.customers.create`)
- ✅ Quotations (`admin.quotations.index`)
- ✅ Contracts (`admin.contracts.index`)
- ✅ Address Book (`admin.address-book.index`)

### Config-Driven Bucket Links (Compliance)
- ✅ KYC (`admin.kyc.index`)
- ✅ DG (`admin.dg.index`)
- ✅ ICS2 (`admin.ics2.index`)
- ✅ Commodities (`admin.commodities.index`)
- ✅ Customs Docs (`admin.customs-docs.index`)
- ✅ DPS (`admin.dps.index`)

### Config-Driven Bucket Links (Finance)
- ✅ Rate Cards (`admin.rate-cards.index`)
- ✅ Invoices (`admin.invoices.index`)
- ✅ COD Receipts (`admin.cod-receipts.index`)
- ✅ Settlements (`admin.settlements.index`)
- ✅ Cash Office (`admin.cash-office.index`)
- ✅ Surcharges (`admin.surcharges.index`)
- ✅ FX Rates (`admin.fx.index`)
- ✅ GL Export (`admin.gl-export.index`)
- ✅ Payroll Generate (`salary.generate.index`)
- ✅ Salary Index (`salary.index`)
- ✅ Account Heads (`account.heads.index`)
- ✅ Accounts (`accounts.index`)
- ✅ Fund Transfer (`fund-transfer.index`)
- ✅ Income (`income.index`)
- ✅ Expense (`expense.index`)
- ✅ Bank Transaction (`bank-transaction.index`)
- ✅ Cash Received (`cash.received.deliveryman.index`)
- ✅ Hub Payment Request (`hub-panel.payment-request.index`)
- ✅ Paid Invoice (`paid.invoice.index`)
- ✅ Wallet Request (`wallet.request.index`)
- ✅ Online Payments (`online.payment.list`)
- ✅ Payout (`payout.index`)

### Config-Driven Bucket Links (Tools)
- ✅ Global Search (`admin.search`)
- ✅ API Keys (`admin.api-keys.index`)
- ✅ Observability (`admin.observability.index`)
- ✅ Exception Tower (`admin.exception-tower.index`)
- ✅ EDI (`admin.edi.index`)
- ✅ WhatsApp Templates (`admin.whatsapp-templates.index`)
- ✅ Push Notification (`push-notification.index`)
- ✅ Addons (`addons.index`)
- ✅ News & Offer (`news-offer.index`)
- ✅ Logs (`logs.index`)
- ✅ Fraud (`fraud.index`)
- ✅ Subscribe (`subscribe.index`)
- ✅ Parcel Reports (`parcel.reports`)
- ✅ Parcel Wise Profit (`parcel.wise.profit.index`)
- ✅ Salary Reports (`salary.reports`)
- ✅ Merchant Hub Deliveryman (`merchant.hub.deliveryman.reports`)
- ✅ Parcel Total Summary (`parcel.total.summery.index`)
- ✅ Social Link (`social.link.index`)
- ✅ Service (`service.index`)
- ✅ Why Courier (`why.courier.index`)
- ✅ FAQ (`faq.index`)
- ✅ Partner (`partner.index`)
- ✅ Blogs (`blogs.index`)
- ✅ Pages (`pages.index`)
- ✅ Section (`section.index`)

### Config-Driven Bucket Links (Settings)
- ✅ User Role → Roles (`roles.index`)
- ✅ User Role → Designations (`designations.index`)
- ✅ User Role → Departments (`departments.index`)
- ✅ User Role → Users (`users.index`)
- ✅ General Settings (`general-settings.index`)
- ✅ Delivery Category (`delivery-category.index`)
- ✅ Delivery Charge (`delivery-charge.index`)
- ✅ Delivery Type (`delivery-type.index`)
- ✅ Liquid Fragile (`liquid-fragile.index`)
- ✅ Packaging (`packaging.index`)
- ✅ SMS Settings (`sms-settings.index`)
- ✅ SMS Send Settings (`sms-send-settings.index`)
- ✅ Notification Settings (`notification-settings.index`)
- ✅ Google Map Settings (`googlemap-settings.index`)
- ✅ Mail Settings (`mail-settings.index`)
- ✅ Social Login Settings (`social.login.settings.index`)
- ✅ Payout Setup (`payout.setup.settings.index`)
- ✅ Currency (`currency.index`)
- ✅ Database Backup (`database.backup.index`)
- ✅ Invoice Generate Manually (`invoice.generate.menually.index`)
- ✅ Payment Methods → Banks (`bank.index`)
- ✅ Payment Methods → Mobile Banks (`mobile-bank.index`)

## Remaining Broken Links
- `reports.generate` - This route is not defined anywhere in the application. The link appears in both the navbar quick actions and dashboard cards but has no corresponding route definition.