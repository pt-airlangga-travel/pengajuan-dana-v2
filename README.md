# Pengajuan Dana AGT — Filament Rebuild

> Rebuild dari `pengajuan_dana_agt_v2_20260913` (Laravel 10 + Blade + Yajra) ke `pengajuan_dana_baru` (Laravel 13 + Filament 5.8). **DB & flow tidak diubah**, 1 prefix `/dashboard` dengan 5 folder per role di dalam.

---

## 1. Tech Stack & Prefix

| Item | v2 | baru |
|---|---|---|
| Laravel | 10.10, PHP 8.1 | 13.x, PHP 8.3 |
| UI | Blade + DataTables + DomPDF + QRCode + Excel | Filament 5.8 + `filament-captcha`, `filament-auth-designer` |
| DB | `pengajuan_dana_agt_20260913` MySQL | sama (`/.env:27` `DB_DATABASE=pengajuan_dana_agt_20260913`) — 19 migrasi dicopy |
| Auth | `LoginController:authenticate` filter `id_role` + `active_status` | `app/Filament/Pages/Auth/Login.php:60` Filament + Captcha, compat legacy `id_role` |
| Prefix | 5: `s-admin`, `c-member`, `o-admin`, `i-manager`, `e-treasurer` | **1**: `app/Providers/Filament/DashboardPanelProvider.php:38` `->path('dashboard')` + `navigationGroups` |

> `discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')` scan rekursif, folder = URL: `dashboard/creative-member/proposal-drafts`, `dashboard/system-admin/users` dll.

---

## 2. DB Schema (tidak diubah)

Copy dari `v2/database/migrations/` ke `baru/database/migrations/` + `database/seeders/`:

```
0001_..._create_cache_table.php (baru)
2014_10_12_000000_create_users_table.php (+ FK id_role/id_division char3 di 9999...)
2024_01_30_134748_create_roles_table.php (role_defined_id char3 PK, role_name, role_active_status)
2024_01_30_144111_create_divisions_table.php (division_defined_id char3)
2024_01_31_062731_create_institutions_table.php (institution_defined_id char3)
2024_01_31_043543_create_events_table.php (event_defined_id char12, short_name/slug/availability, id_institution)
2024_02_01_035616_create_proposal_drafts_table.php (proposal_draft_defined_id char17 = 12 event +5 index, status tinyInt)
2024_02_01_132225_create_banks_table.php (bank_defined_id char3)
2024_07_18_165216_create_bank_asals_table.php (bank_name, no_rekening, color)
2024_02_01_143914_create_proposal_submissions_table.php (proposal_submission_defined_id char17, status tinyInt, checked_date)
2024_02_02_163546_create_bank_accounts_table.php (revised, sub_total, owner, number, status_revised, alasan_revised)
2024_02_02_172340_create_vendors_table.php (vendor_sub_total string)
2024_02_02_175135_create_needs_table.php (need_defined_id char3)
2024_02_02_192640_need_submissions.php (pivot need_submissions)
2024_02_03_100623_create_bank_transfers_table.php (bank_transfer_defined_id char17, transfer_attached_name, id_bank_asal, eagle_treasurer)
9999_01_30_152337_foreign_key.php (FK terpusat)
```

Status workflow (tetap):
- `proposal_drafts.proposal_draft_status`: `2 Menunggu` → `3 Diajukan ke Manager` → `0 Ditolak` / `1 Selesai`
- `proposal_submissions.proposal_submission_status`: `2 Menunggu` → `3 Proses TF` → `0 Tolak` / `1 Selesai` / `4 Dikembalikan`

---

## 3. Roles & Auth

Seed `v2/database/seeders/RoleSeeder.php`:
`001 System Admin`, `002 Creative Member`, `003 Organizer Admin`, `004 Inspiring Manager`, `005 Eagle Treasurer`

**Enum terpusat** `app/Enums/Role.php:6` + trait `app/Filament/Resources/Concerns/HasRoleNavigation.php:10`
```php
hasRole(Role::OrganizerAdmin) // id_role === '003'
hasAnyRole([Role::CreativeMember, Role::OrganizerAdmin])
```

**User compat** `app/Models/User.php:52`:
- `role()` belongsTo `Role` via `id_role → role_defined_id`, `roles()` hasMany `Role where role_defined_id = id_role` (agar `load('roles')` Filament jalan, return collection 0/1)
- `getIsActiveAttribute()` alias `active_status`, `getActiveRoleIdAttribute()` fallback `role->id` (kolom `active_role_id` tidak ada di legacy)
- `canAccessPanel()` cek `active_status`

**Role compat** `app/Models/Role.php:12`: `getIsActiveAttribute()` alias `role_active_status`

**Login** `app/Filament/Pages/Auth/Login.php:60`: `loadMissing('roles')` + fallback `roles=[role]` jika kosong, cek `is_active`, skip `update active_role_id` jika `Schema::hasColumn` false. Tetap support `email`/`username` + Captcha.

---

## 4. Models (sync v2)

`app/Models/*.php` PK char dipertahankan, `incrementing=false`, `keyType=string` untuk `*_defined_id`:

```
User (id, id_role char3, id_division char3) → role, division, ManagerSubmission, roles()
Role (role_defined_id char3 PK)
Division (division_defined_id char3)
Institution (institution_defined_id char3) → events
Event (event_defined_id char12 PK) → institution, ProposalDrafts
ProposalDraft (proposal_draft_defined_id char17 PK, index 5=3seq+2rev) → Event, Vendors, ProposalSubmission, creativeMember
Vendor (id_proposal_draft char17)
ProposalSubmission (proposal_submission_defined_id char17 PK) → ProposalDraft, Needs M2M, BankAccounts, Manager
Need (need_defined_id char3)
Bank (bank_defined_id char3) → BankAccounts
BankAccount (id_bank, id_proposal_submission) → Bank, ProposalSubmission, BankTransfer
BankTransfer (bank_transfer_defined_id char17, id_bank_account, id_bank_asal, eagle_treasurer)
BankAsal (bank_name, color, no_rekening)
```

---

## 5. Helpers (port v2 Controller)

`v2/app/Http/Controllers/Controller.php:21` → `baru/app/Services/ProposalHelper.php:16` + `app/Http/Controllers/Controller.php` + `app/Helpers.php` (autoload `composer.json: files`):

- `ProposalHelper::defined_id($value,$digits)` — padding nol
- `formatDefinedId($data)` — `2024.01.001...`
- `createSlug($string)`, `getFileAsBase64($path)`, `terbilang($angka)` — Terbilang rupiah rekursif
- `proposalformvalidation(Request id)`, `printFormulir(Request id)` — DomPDF view `formulir-fee/non-fee` + QR `route('proposalformvalidation')`, `exportExcel()` — guard `class_exists` jika deps belum install

Dipakai di Resources untuk generate ID & PDF.

---

## 6. Struktur Filament (Opsi B: 1 prefix, 5 folder)

```
app/Filament/
  Pages/Auth/Login.php
  Resources/
    Concerns/HasRoleNavigation.php
    Shared/EventResource.php (+ Pages/ListEvents,CreateEvent,EditEvent)
    SystemAdmin/
      UserResource.php, RoleResource.php, DivisionResource.php
      InstitutionResource.php, BankResource.php, NeedResource.php
      BankAsalResource.php, EventResource.php
      Pages/ListUsers,CreateUser,EditUser etc (8 resources × 3 pages)
    CreativeMember/
      ProposalDraftResource.php (+ handleStore, ReCreate rev+1 max3)
      ProposalSubmissionResource.php (needs M2M auto-create, bank repeater, event_identity)
    OrganizerAdmin/
      ProposalDraftResource.php (action Setujui 2→3 / Tolak 2→0 modal note_admin)
      ProposalSubmissionResource.php (create + reCreate submission)
    InspiringManager/
      ProposalDraftResource.php (confirm draft, read-only)
      ProposalSubmissionResource.php (filter status/event, action Setujui 2→3 / Tolak 0 update inspiring_manager+checked_date)
    EagleTreasurer/
      ProposalSubmissionResource.php (where status 3 Proses TF, action Lihat Bank Detail / Tolak / Confirm Selesai)
      BankDetailResource.php (per bank_account: Reject status_revised+alasan_revised, Upload bukti_tf id_bank_asal+allTransferred→submission+draft 1)
      BankAccountResource.php (alias non-navigasi), BankTransferResource.php (legacy, shouldRegisterNavigation false)
```

`DashboardPanelProvider.php:44` navigationGroups: `System Admin`, `Creative Member`, `Organizer Admin`, `Inspiring Manager`, `Eagle Treasurer`, `Master`.

Tiap Resource:
```php
use HasRoleNavigation;
protected static ?string $model = X::class;
protected static string|\UnitEnum|null $navigationGroup = '...';
public static function shouldRegisterNavigation(): bool { return static::hasRole(Role::SystemAdmin); }
public static function canAccess(): bool { return ...; }
public static function getPages(): array { return ['index'=>\App\...\Pages\ListX::route('/'), ...]; }
```

Routes: `dashboard/creative-member/proposal-drafts`, `dashboard/system-admin/users` dll (76 routes, `php artisan route:list`).

---

## 7. Flow End-to-End + File Terlibat

### Draft: Create → Verifikasi Org → Submission
1. **CreativeMember create draft** (`CreativeMember/ProposalDraftResource.php:49 form` Select Event available, FileUpload `proposal` 5MB, DatePicker deadline, Repeater vendors)
   - Store: `handleStore()` → `latestIndex = SUBSTRING(1,12)=id_event ? "00100" : inc substr0,3+"00"`, `definedId=id_event+index`, `storeAs('proposal', "{id}.ext")`, `Vendor::create` — sama `v2/creative_member_controllers/ProposalDraftController.php:store`
   - ReCreate: action `reCreate` → `prefix15=substr(id,0,15)`, cek `count>=3` & `where status 2 exists` block, `rev=intval(substr(15,2))+1`, `newId=prefix15+defined_id(rev,2)` — copy vendors/file
2. **OrganizerAdmin verifikasi** (`OrganizerAdmin/ProposalDraftResource.php:table` recordActions Setujui/Tolak)
   - `proposalDraftConfirm` → update `proposal_draft_status 3/0`, `proposal_draft_note_admin`, `organizer_admin=Auth::id()` — sama `v2/organizer_admin_controllers/ProposalDraftController.php:proposalDraftConfirm`
   - Jika `3` → Creative/Organizer buat submission

### Submission: Create → Manager Approval
3. **Creative/Organizer create submission** (`CreativeMember/ProposalSubmissionResource.php` & `OrganizerAdmin/ProposalSubmissionResource.php` Select draft `where status 3` tanpa submission aktif, Select Needs multiple `need_submissions` auto-create `Need` defined_id incremental, Repeater BankAccounts `id_bank` Select Bank aktif)
   - Store: `latestIndex = SUBSTRING(id_proposal_draft,1,15)` → `definedId=substr(draft,0,12)+latestIndex`, `event_identity=year/institution/short_name/month/date/substr(index,3)` — sama `v2/.../ProposalSubmissionController.php:store`
   - ReCreate submission jika `0` Tolak (increment rev, max3, pending2)
4. **InspiringManager approval** (`InspiringManager/ProposalSubmissionResource.php:table` filter status `SelectFilter` + event, columns status badge, actions)
   - `proposalSubmissionConfirm` → `status 3` Setujui atau `0` Tolak, `proposal_submission_note_manager`, `inspiring_manager=Auth::id()`, `checked_date=now()` — sama `v2/inspiring_manager_controllers/ProposalSubmissionController.php`

### Transfer: Treasurer eksekusi
5. **EagleTreasurer proses** (`EagleTreasurer/ProposalSubmissionResource.php:where status 3` + `BankDetailResource.php:table`)
   - Per rekening: `Reject` → `status_revised=true, alasan_revised`, Creative `saveBankAccount` reset `revised++`.
   - `Upload Bukti` → FileUpload `bukti_tf/{time}.ext`, `bank_transfer_defined_id=defined_id(latest+1,3)`, `id_bank_asal` Select `BankAsal`, `eagle_treasurer=Auth::id()`, `dijalankan_tanggal=now()`, cek `allTransferred foreach BankAccounts has transfer?` → `DB::transaction` update `proposal_submission_status=1`, `proposal_draft_status=1` Selesai — sama `v2/eagle_treasurer_controllers/BankController.php:uploadBuktiTransfer`

### Master & PDF
6. **SystemAdmin CRUD** (`SystemAdmin/*Resource.php` form/table Toggle active_status, TextInput char3 unique) — port `v2/system_admin_controllers/*`
7. **PDF Formulir** (`ProposalHelper::printFormulir()` view `formulir-fee.blade.php`/`formulir-non-fee.blade.php` jika `needs=fee`, terbilang nominal sum vendor, QR SVG base64, color `bank_asals.color`) + `proposalformvalidation` view.

---

## 8. Cara Tambah Fitur

- Tambah Resource baru di folder role yang sesuai, `use HasRoleNavigation`, set `navigationGroup`, `shouldRegisterNavigation` → auto-discover (tidak perlu register manual).
- Action approve/reject pakai `Filament\Actions\Action` dengan `->requiresConfirmation()->schema([Textarea note])->action(fn($record) => update status)`.
- Generate PK selalu via `ProposalHelper::defined_id()` agar konsisten `char17`.
- File storage: `proposal/` untuk draft, `bukti_tf/` untuk transfer (disk `local`).

---

## 9. Verifikasi

```bash
php artisan migrate:status # 19 Ran
php artisan filament:upgrade # Successfully upgraded
php artisan route:list | grep dashboard # 50+ filament routes
php -l app/Filament/Resources/**/*.php # No syntax errors
```

Login test: `wsobirin2@gmail.com / 001 System Admin`, `member@gmail.com /002`, `manager@gmail.com /004`, `bendahara@gmail.com /005` (password `password`, `active_status=true`) — menu filter per role via `HasRoleNavigation`.

