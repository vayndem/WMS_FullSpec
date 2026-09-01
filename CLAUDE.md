# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

WMS FullSpec is a Laravel 12 / PHP 8.2+ / MySQL modular monolith connecting warehouse, procurement, finance, and accounting into one controlled flow: request → PO → LPB (receipt) → multi-warehouse stock → NPK (usage) → supplier invoice → payment → GL journal. Stock movements and financial postings are not administrative side notes — they drive inventory value, FIFO layers, payables, and the general ledger together.

Business-level documentation is bilingual Indonesian/English in [README.md](README.md) (product overview) and [feed.MD](feed.MD) (technical reference — role matrix, domain flows, database tables, WMS control framework, technical debt). Read `feed.MD` section 14 ("Source of truth") when docs and code disagree: migration > policy > controller+form request > service > model > tests.

## Commands

```bash
composer install
npm install
php artisan key:generate
php artisan migrate --seed     # dev/test only — full seeder mixes master + demo data
npm run build
php artisan serve
npm run dev                    # Vite dev server for frontend
```

Testing:

```bash
php artisan test                              # full suite (unit + feature)
php artisan test --filter=TestClassName       # single test class
php artisan test --filter=test_method_name    # single test method
composer test:case-sensitive                  # strict PSR-4 autoload + ArchitectureConventionTest (case-sensitivity check, matches Linux CI runner on Windows)
```

CI (`.github/workflows/ci.yml`) runs against real MySQL: `composer test:case-sensitive` → `php artisan migrate:fresh --seed --force` → `php artisan test`. Case-sensitivity bugs only surface on Linux/CI, so always run `composer test:case-sensitive` locally before pushing on Windows.

## Architecture rules (enforced by `tests/Unit/ArchitectureConventionTest.php`)

These are hard constraints, not style suggestions — the architecture test fails the build if violated:

- **Class/file casing**: class name must exactly match its filename (case-sensitive), checked against both PHP reflection and actual Git-tracked paths — not just the local filesystem (Windows is case-insensitive, so a local mismatch won't show until CI/Linux).
- **No inline validation in controllers**: controllers must not call `->validate(...)` directly; all validation goes in `app/Http/Requests` Form Request classes.
- **Routes are split by domain** and required from `routes/web.php`: `auth.php`, `warehouse.php`, `procurement.php`, `finance.php`, `accounting.php`, `assets-and-services.php`. Routes must stay `route:cache`-able.
- **Status columns are uppercase machine codes** (e.g. `PENDING`, `APPROVED`, `POSTED`, `VOID`) — never lowercase or mixed case in the database. Indonesian-language labels belong only in the presentation layer.
- **No floating point for business data** in migrations (`$table->double(...)` is banned) — use fixed-point: quantities `DECIMAL(18,6)`, unit cost `DECIMAL(18,4)`, money `DECIMAL(18,2)`, rates `DECIMAL(8,4)`.
- **No legacy table names** (`admin_namagudang`, `invoicelpbs`, `invoicelpbdetails`, `lpbdetails`, `kategoribahan`) may reappear in migrations.
- Table names are plural `snake_case`; new foreign keys follow `<model>_id`.

## Layered structure

| Layer | Location | Responsibility |
|---|---|---|
| Routes | `routes/*.php` | endpoints + middleware, split per domain |
| Controllers | `app/Http/Controllers` | HTTP orchestration only |
| Form Requests | `app/Http/Requests` | validation, and some authorization |
| Policies | `app/Policies` | role + document-state guards, auto-discovered by Laravel convention |
| Services | `app/Services` | FIFO/inventory cost, accounting postings, document numbering, opname, asset, reconciliation, transfer, three-way match, landed cost, reversal logic |
| Models | `app/Models` | relations, casts, domain helpers |
| Migrations | `database/migrations` | schema + constraints |
| Seeders | `database/seeders` | roles, users, master data, demo data |
| Views | `resources/views` | Blade + Tailwind CSS + daisyUI + Alpine.js |

## Frontend conventions (Tailwind + daisyUI + Alpine, since 2026-09-01)

The UI was fully migrated off Bootstrap 5 / jQuery / DataTables.js — there is zero jQuery or Bootstrap JS in the codebase now. Keep it that way for any new view or edit:

- **Stack**: Tailwind CSS + daisyUI components, compiled via Vite (`resources/css/app.css`, `resources/js/app.js`). Interactivity is Alpine.js, not jQuery.
- **Server-side tables**: list pages talk to the existing Yajra (`datatables()->of(...)`) JSON endpoints through the reusable `wmsDataTable()` Alpine factory (`resources/js/data-table.js`) — do not reintroduce DataTables.js. Row markup is a Blade `<template x-for>`, not server-built HTML strings.
- **Modals**: use a native `<dialog class="modal">` + `.modal-box`, opened via `openAjaxModal()` / closed via `closeAjaxModal()` (`resources/js/ajax-modal.js`), not Bootstrap's `data-bs-toggle="modal"`.
- **Simple CRUD forms**: use `submitAjaxForm($event)` / `confirmAjaxDelete($event, message)` instead of writing a new `$.ajax` handler per view.
- **Searchable `<select>`**: add `data-app-picker` — `resources/js/smart-picker.js` auto-enhances it (no jQuery involved).
- **`.modal-box` must scroll, never clip**: it has a global `max-height` + `overflow-y: auto` in `app.css` specifically so a tall form's footer buttons stay reachable instead of being cut off — don't override that with a fixed height or `overflow-hidden` on a per-view basis.
- **Responsiveness is a hard requirement, not an afterthought.** Every new or edited view must be checked at mobile width, not just desktop: use Tailwind's responsive prefixes (`sm:`/`md:`/`lg:`) on grids and flex layouts instead of a single fixed multi-column layout, wrap any wide table in `overflow-x-auto`, and actually resize the browser (or check devtools' responsive mode) before calling a view done — do not assume a desktop-only render is good enough.

Cross-model processes (posting a document, reversing it, computing FIFO cost) belong in `app/Services`, not controllers. Gates (`AuthServiceProvider`) are used only for capabilities that span multiple models (e.g. `viewWmsControl`, `operateWarehouse`); everything else uses per-model Policies.

Stock and accounting mutations are wrapped in `DB::transaction()`; operations prone to races use `lockForUpdate()`.

## Authorization model

- `users.type` is a foreign key into `user_roles` (roles are no longer bare integers). Six roles: `super_admin`(0), `purchasing`(1), `finance`(2), `warehouse`(3), `accounting`(4), `production`(5).
- `SuperAdmin` bypasses all policies via `Gate::before` in `app/Providers/AuthServiceProvider.php` — do not add redundant SuperAdmin checks inside individual policies.
- `User` model exposes role helpers: `isSuperAdmin()`, `isPurchasing()`, `isFinance()`, `isWarehouse()`, `isAccounting()`, `isProduction()`, `isWarehouseOperator()`, plus warehouse-scoping helpers `accessibleGudangIds($ability)` and `canAccessGudang($gudangId, $ability)`.
- `Produksi` (production) role is scoped to specific warehouses via the `pembagian_gudangs` table (fields: `boleh_menerima`, `boleh_npk`, `boleh_transfer`, `boleh_opname`). `Warehouse` role has access to all active warehouses; `Produksi` only to assigned ones. Controllers filter warehouse-scoped lists through `accessibleGudangIds()`.
- Newer policies (`NpkPolicy`, `TransferGudangPolicy`, `StockOpnamePolicy`, and others) follow a pattern of private "who is allowed" / "warehouse access" helper methods, with public `view/create/update/...` methods below — follow this pattern for new policies.

## Domain concepts to know before editing warehouse/inventory code

- **Warehouse types** (`Gudang` model constants): `NORMAL`, `CONSIDER` (quarantine), `RUSAK` (damaged, terminal state).
- **Document lifecycles**: Material Request `PENDING → APPROVED/REJECTED`; Purchase Order `OPEN → CLOSED`; LPB/BAP and NPK `DRAFT → POSTED → REVERSED` (or `CANCELLED`); Invoice `UNPAID → PARTIALLY_PAID → PAID` (or `VOID`); Payment `POSTED → VOID`; Transfer `DRAFT → DIAJUKAN → DIKIRIM → DITERIMA` (or `DIBATALKAN`); Stock Opname `DRAFT → SUBMITTED → APPROVED → POSTED` (or `REJECTED`). **Posted documents are immutable** — corrections always go through a controlled reversal/void with audit metadata and a reversing journal entry, never edit/delete.
- **FIFO inventory layers** (`InventoryLayer`): LPB posting creates layers; NPK consumes them FIFO (FEFO-then-FIFO for picking). NPK only consumes layers that are `AVAILABLE`, not blocked, and not expired. Layer statuses: `AVAILABLE`, `QC_HOLD`, `IN_TRANSIT`, `REVERSED`, `TRANSFER_SHORTAGE`.
- **Units**: `Bahan` (material) may have a small/transaction unit distinct from its main stock unit. NPK stores `jumlah` (transaction qty), `jumlah_stok` (converted to stock unit), and `satuan_transaksi` (snapshot of the input unit) — conversion always happens before FIFO consumption.
- **Transfers** preserve layer value across warehouses and use an idempotency key to reject duplicate requests. In-transit stock sits in an `IN_TRANSIT` layer; global company balance is unaffected until received. Internal transfers currently post no journal entry.
- **Journals**: LPB posts debit persediaan (inventory) / credit GRNI. NPK posts debit beban (expense) / credit persediaan. Journal creation is service-layer, not controller-layer.
- **Three-way match**: supplier invoices reconcile PO–LPB–Invoice before an AP journal posts (`ThreeWayMatchService`).
- **Landed cost**: capitalized into active layers and posted balanced to GL (`LandedCostService`).
- **Reconciliation invariants**: master quantity = warehouse + in-transit; warehouse balance = sum of layers; layer value = GL inventory balance.
- The **WMS Control Center** (menu: Multi Gudang → WMS Control Center) is the operational home for traceability (bin/lot/serial/expiry, block/release), warehouse execution (QC, putaway, reservation, FEFO/FIFO picking), financial control (three-way match, landed cost, controlled reversal), and planning (reorder point, safety stock, replenishment).
- **Gudang Produksi** (production warehouse) is a stage-1 feature: `Gudang Utama → Gudang Produksi` moves via ordinary Transfer Gudang (no journal for the internal move); NPK from Gudang Produksi is where material consumption begins. There is no dedicated WIP/manufacturing model yet — production still uses the same direct-consumption-on-NPK pattern as other warehouses.

## Known repo quirks

- The historical `accouting` (not `accounting`) misspelling in `resources/views/accouting` and `AuthController` was fixed 2026-09-01 — the view directory is now `resources/views/accounting`. If you spot the old name anywhere else (a stray blade include, a bookmark, an old branch), fix it the same way.
- `.env.example` exists; production secrets must still be managed outside the repo.
- `DatabaseSeeder` mixes master data and demo data — avoid running the full seeder against production.

## Known gaps (analyzed 2026-09-01)

Found during a full read-through of the payment/COA flow (`InvoicePaymentController`, `WmsAccountingService`, `PaymentAllocationService`, `ChartOfAccount`, policies). Items marked **Done (2026-09-01)** were fixed the same day, on the current Laravel 10 stack — not part of the deferred Laravel 12/rename refactor below. The rest are still open and listed here so a future session doesn't have to re-derive them.

### Accounting completeness vs. Indonesian standards (PSAK / pajak)
- **Done (2026-09-01).** `invoice_lpbs.no_faktur_pajak` (NSFP) added — migration `2026_09_01_000001_add_faktur_pajak_to_invoice_lpbs`, required in `Store/UpdateInvoiceLpbRequest` when `is_ppn` is true (regex `\d{3}\.\d{3}-\d{2}\.\d{8}`), wired through `InvoiceLpbController`, and surfaced in the create/edit/show views.
- No statutory financial statement reports — no Neraca Saldo (trial balance), Buku Besar per account, Laporan Laba Rugi, or Neraca (balance sheet). Only a raw journal list (`JurnalController::reportPdf`) and the reconciliation dashboard exist. The underlying `jurnal_details` data supports building these; the report layer just doesn't exist yet. **Still open** — biggest remaining accounting-completeness gap.
- Only PPh 23 is modeled (`TaxRate`, `AccountingSetting::HUTANG_PPH23`). PPh 22 and PPh 4(2) final (common in Indonesian procurement — government purchases, rent, construction services) are not supported. **Still open.**
- Asset depreciation amount is manually entered per period (`StoreAssetDepreciationRequest` — `amount` is free input), not auto-computed from useful life / method (straight-line, declining balance) per PSAK 16. **Still open.**
- No multi-currency / kurs handling — no PPN Impor, Bea Masuk, or selisih kurs (PSAK 10) for import suppliers. **Still open.**
- No AR/piutang or sales side at all — this system is procure-to-pay only. **Still open** (see WMS outbound gap below — same root cause).

### WMS completeness vs. a mature/enterprise WMS
- No outbound-to-customer side: no Sales Order, Surat Jalan/Delivery Order, or sales return. The system only models goods coming in and being consumed internally via NPK — it is not a distribution/3PL warehouse. **Still open** — needs its own design/scoping session before building (new domain, not a bug fix).
- **Done (2026-09-01), scoped.** Formal purchase return / return-to-vendor (RTV) document added: `ReturPembelian` + `ReturPembelianDetail` (migration `2026_09_01_000004_create_retur_pembelians`), `ReturPembelianController` (`routes/warehouse.php` — `retur-pembelian.*`, Purchasing/Warehouse can create, viewable by Accounting too), `ReturPembelianPolicy`, `StoreReturPembelianRequest`, `WmsAccountingService::postReturPembelian()` (debits GRNI/`coa_clearing_lpb_id`, credits Persediaan, grouped by `kategori_bahan` — mirrors `postLpb()` in reverse), and `InventoryReversalService::reverseReturPembelian()` (controlled reversal via the existing `document_reversals` audit trail, gated by the `controlInventoryFinance` ability — Accounting only, same as `reverseLpb`/`reverseNpk`). It reduces the specific `InventoryLayer` created by the original LPB detail (not a FIFO blend) and decrements `lpb_details.jumlah_tersisa` via a new `jumlah_retur` column (migration `2026_09_01_000003`). **Scope boundary:** only works while the source LPB is still unbilled (`no_invoice IS NULL`) — a return after the invoice (or payment) has already posted still has no workflow; that residual case (reducing AP or issuing a debit note against an already-invoiced/paid LPB) is **still open**. UI: `resources/views/retur_pembelian/{index,create}.blade.php`, linked from the sidebar under "Penerimaan". Covered by `tests/Feature/WmsControlFrameworkTest.php::test_purchase_return_reduces_stock_and_grni_then_can_be_reversed` and `::test_purchase_return_index_and_create_views_render`.
- No real barcode/RF scanning — "barcode" appears only as UI copy in `resources/views/wms_control/index.blade.php`; all putaway/picking input is manual web forms. **Still open.**
- No wave/batch picking, cross-docking, or slotting/ABC analysis. Planning is limited to reorder point + safety stock (`ReplenishmentService`). **Still open.**
- No BOM / manufacturing order / WIP model, consistent with `feed.MD`'s own note. `Gudang Produksi` exists but NPK is still direct material consumption, not staged production. **Still open.**
- No external integration layer (API/EDI to suppliers, marketplaces, other ERPs) beyond a bare Sanctum auth base. **Still open.**
- No kitting/bundling. **Still open.**

### Smaller code-level findings (payment/COA area)
- **Done (2026-09-01).** `InvoicePaymentPolicy::create()` now requires `isFinance()` (was `isPurchasing()`, contradicting the real Finance-only gate on `POST invoice-payments`). `InvoicePaymentController::store()` also now calls `$this->authorize('create', InvoicePayment::class)` explicitly, so the policy is live rather than dead code.
- **Done (2026-09-01).** `UANG_MUKA_SUPPLIER` (overpayment recorded as a supplier advance) now has a consumption workflow: `invoice_payments.uang_muka_sumber_payment_id` + `uang_muka_dipakai` (migration `2026_09_01_000002_add_advance_consumption_to_invoice_payments`), `InvoicePayment::sisaUangMuka()` computes remaining balance, `GET invoice-payments/available-advances/{supplier}` lists usable advances, `InvoicePaymentController::store()` lets a later payment (same supplier only, cross-supplier blocked) apply an existing advance to reduce AP without new cash, and `WmsAccountingService::postPayment()` posts the matching credit line to the advance's COA account. Voiding a payment that is itself a consumed advance source is blocked until the consuming payment is voided first. Covered by `tests/Feature/WmsControlFrameworkTest.php::test_supplier_advance_can_be_generated_then_consumed_by_a_later_payment` and `::test_supplier_advance_cannot_be_used_across_different_suppliers`.
- **Done (2026-09-01).** `InvoicePaymentController::destroy()` now aborts with a clear 422 (`'Pembayaran ini sudah dibatalkan sebelumnya.'`) if the payment is already `VOID`, instead of falling through to a generic 404 from the journal-reversal lookup.
- `Accounting` role has very broad power by design (can create/edit/delete supplier invoices *and* fully control COA/journals/period locks/reversals) — no maker-checker separation. Likely intentional (single back-office role) but worth flagging if this ever handles real money at scale. **Not changed** — this is a design/policy decision for the user to make, not a bug.

## Planned future work

The user's 4-phase modernization plan, approved 2026-09-01:

1. **Framework upgrade** — Laravel 10 → **12**, plus **Tailwind CSS + daisyUI + Alpine.js** replacing Bootstrap 5/jQuery/DataTables. ✅ **Done (2026-09-01).** Verified via `composer test:case-sensitive` + full test suite (56/56) staying green throughout, and manual browser check. See "Frontend conventions" above for the resulting pattern.
2. **Database naming**: rename tables/columns to Indonesian, using consistent `snake_case_seperti_ini` — no more mixed/odd naming. This touches migrations through controllers. **Deferred** — explicitly postponed by the user (2026-09-01); do not start without a fresh go-ahead.
3. **File/class naming**: must match what `php artisan make:model -a` generates (model + migration + factory + seeder + controller + policy + form requests all named consistently off the same model name) — this is a hard readability requirement for the next phase. **Not started.**
4. **Structural convention to standardize and carry into the user's future projects, not just this one**: Controllers stay thin; validation lives in Form Requests; authorization lives in Policies; any logic reused more than once belongs in a Service class. This is the target pattern going forward — keep it in mind for any new code even before the rest of the refactor lands.
