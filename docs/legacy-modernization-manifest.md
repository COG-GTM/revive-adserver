# Legacy PHP Modernization — Migration Manifest

Program: migrate legacy PEAR-era modules (`lib/OA/`, `lib/max/`) to modern namespaced PSR-4 code under `lib/RV/`, with back-compat shims so chunks are independent and parallel-safe.

Baseline: full test suite green on `master` (`ant test-all`, php 8.3 / mysqli / myisam: 291 test cases, 0 failures, 0 errors) before any change.

## Conversion recipe (applied uniformly in every chunk)

1. Move each `Prefix_Class_Name` class to a namespaced `RV\Legacy\...` PSR-4 class under `lib/RV/Legacy/`, registered via the existing `"RV\\": "lib/RV/"` composer PSR-4 autoload (run `composer dump-autoload` after adding files).
2. Leave a thin back-compat shim at the old path (exact same file path — many callers use explicit `require MAX_PATH . '/lib/...'`, not the autoloader): the shim declares `class_alias(RV\Legacy\...::class, 'Old_Class_Name');` so all untouched callers keep working unchanged. The shim must be include-safe (idempotent, no new side effects) and must not assume the composer autoloader beyond what the original file already assumed.
3. Old class names must also stay resolvable via autoload (some callers rely on `class_exists('OA_Foo')` or lazy loading without an explicit require): register the shim files in a composer `classmap` (or `files`) autoload entry so legacy underscore names resolve without an explicit include. PSR-4 alone cannot resolve legacy names.
   - Merge-conflict avoidance: since every wave-1 chunk edits the same `composer.json` autoload section, each child adds exactly ONE directory-level classmap entry for its own module directory (e.g. `"classmap": ["lib/OA/DB/"]`), each on its own line, appended to the classmap array in a fixed module order (Dal, Maintenance, Dll, DB, Delivery). One-line-per-module keeps conflicts trivial (adjacent-line) and mechanically resolvable at merge time.
4. Add parameter/return types and typed properties only where provably safe; no behavior changes, no refactors.
5. All existing tests stay green; update namespace references in tests only where strictly needed.
6. Procedural files (no classes — e.g. `lib/max/Delivery/*.php`, `lib/OA/Dal/Delivery*.php`, `lib/OA/DB/CustomDatatypes/*.php`): the `class_alias` recipe does not apply. These are loaded via explicit `require MAX_PATH . '/lib/...'` on the delivery hot path, which must keep working in delivery-only deployments that never run `composer dump-autoload`. Recipe variant: group functions into namespaced classes with static methods and keep thin global-function shims at the exact old paths delegating to them (the shim file `require_once`s the new class file directly, no autoloader dependency), OR leave the file untouched if the risk/benefit is poor. No behavior or performance regressions allowed on the delivery path.
7. Audit name-string-driven usages before converting each class: `class_alias` is NOT transparent to `get_class()`, `__CLASS__`, `serialize()`d class names, or string manipulation of class names (e.g. `str_replace('OA_Dal_', ...)`, `strtolower(get_class(...))` used to build table/DAL names). After migration these return/see the new namespaced name. Grep each class's dependents for `get_class`, `__CLASS__`, dynamic `new $name`, and hard-coded legacy-name string comparisons; if behavior would change and cannot be preserved trivially, leave that class unconverted and note it in the PR.
   7a. (sub-item of 7) Known name-string-driven call sites to audit (non-exhaustive): `OA_Maintenance_Priority_DeliveryLimitation_Factory` builds `OA_Maintenance_Priority_DeliveryLimitation_<Type>` names from plugin strings; `OA_DB_AdvisoryLock`/`OA_DB_Charset` instantiate `<Class>_<dbtype>` driver subclasses from strings; DataObject/MDB2 code derives table names and file paths from class names. Reserved-word note: `OA_Maintenance_Priority_DeliveryLimitation_Empty` cannot map to a namespace segment/class named `Empty` (reserved); name the new class `EmptyLimitation` (file `EmptyLimitation.php`) and keep the `class_alias` to the old name.
8. Touch nothing outside the assigned directory except: `composer.json` autoload (if needed), new files under `lib/RV/Legacy/`, and the alias shims.

## Test command (local)

```
composer install
# MySQL 5.7 on 127.0.0.1:3306, root/secret, db test_revive (docker run mysql:5.7 as in .github/workflows/test.yml)
# ant 1.7 required (see .github/workflows/test.yml for install steps)
cat > devel.xml   # see test.yml "Generate configuration file" step
ant test-all
```

CI: GitHub Actions matrix PHP 8.1–8.5 × db-type (mysqli/pgsql) × table-type (myisam/innodb/pgsql) × audit (no-audit/audit), with exclusions per `.github/workflows/test.yml`. All cells must be green before merge.

## Wave 1 modules (one child session / PR each)

## Child A: `lib/OA/Dal` → `RV\Legacy\OA\Dal`

Non-test files: 17 | Test files: 35

| File | Classes | External dependents (files) |
|---|---|---|
| `lib/OA/Dal/ApplicationVariables.php` | `OA_Dal_ApplicationVariables` | 25 |
| `lib/OA/Dal/DataGenerator.php` | `DataGenerator` | 80 |
| `lib/OA/Dal/Delivery.php` | (no class) | 0 |
| `lib/OA/Dal/Delivery/mysqli.php` | (no class) | 0 |
| `lib/OA/Dal/Delivery/pgsql.php` | (no class) | 0 |
| `lib/OA/Dal/Links.php` | `Openads_Links` | 3 |
| `lib/OA/Dal/Maintenance/Common.php` | `OA_Dal_Maintenance_Common` | 0 |
| `lib/OA/Dal/Maintenance/Priority.php` | `OA_Dal_Maintenance_Priority` | 18 |
| `lib/OA/Dal/Maintenance/UI.php` | `OA_Dal_Maintenance_UI` | 1 |
| `lib/OA/Dal/PasswordRecovery.php` | `OA_Dal_PasswordRecovery` | 1 |
| `lib/OA/Dal/Statistics.php` | `OA_Dal_Statistics` | 0 |
| `lib/OA/Dal/Statistics/Advertiser.php` | `OA_Dal_Statistics_Advertiser` | 1 |
| `lib/OA/Dal/Statistics/Agency.php` | `OA_Dal_Statistics_Agency` | 1 |
| `lib/OA/Dal/Statistics/Banner.php` | `OA_Dal_Statistics_Banner` | 1 |
| `lib/OA/Dal/Statistics/Campaign.php` | `OA_Dal_Statistics_Campaign` | 1 |
| `lib/OA/Dal/Statistics/Publisher.php` | `OA_Dal_Statistics_Publisher` | 1 |
| `lib/OA/Dal/Statistics/Zone.php` | `OA_Dal_Statistics_Zone` | 2 |

## Child B: `lib/OA/Maintenance` → `RV\Legacy\OA\Maintenance`

Non-test files: 21 | Test files: 22

| File | Classes | External dependents (files) |
|---|---|---|
| `lib/OA/Maintenance/Auto.php` | `OA_Maintenance_Auto` | 4 |
| `lib/OA/Maintenance/Priority.php` | `OA_Maintenance_Priority` | 14 |
| `lib/OA/Maintenance/Priority/Ad.php` | `OA_Maintenance_Priority_Ad` | 3 |
| `lib/OA/Maintenance/Priority/AdServer.php` | `OA_Maintenance_Priority_AdServer` | 0 |
| `lib/OA/Maintenance/Priority/AdServer/Task.php` | `OA_Maintenance_Priority_AdServer_Task` | 0 |
| `lib/OA/Maintenance/Priority/AdServer/Task/AllocateZoneImpressions.php` | `OA_Maintenance_Priority_AdServer_Task_AllocateZoneImpressions` | 0 |
| `lib/OA/Maintenance/Priority/AdServer/Task/ECPMCommon.php` | `OA_Maintenance_Priority_AdServer_Task_ECPMCommon` | 1 |
| `lib/OA/Maintenance/Priority/AdServer/Task/ECPMforContract.php` | `OA_Maintenance_Priority_AdServer_Task_ECPMforContract` | 0 |
| `lib/OA/Maintenance/Priority/AdServer/Task/ECPMforRemnant.php` | `OA_Maintenance_Priority_AdServer_Task_ECPMforRemnant` | 1 |
| `lib/OA/Maintenance/Priority/AdServer/Task/GetRequiredAdImpressions.php` | `OA_Maintenance_Priority_AdServer_Task_GetRequiredAdImpressions` | 0 |
| `lib/OA/Maintenance/Priority/AdServer/Task/GetRequiredAdImpressionsDaily.php` | `OA_Maintenance_Priority_AdServer_Task_GetRequiredAdImpressionsDaily` | 0 |
| `lib/OA/Maintenance/Priority/AdServer/Task/GetRequiredAdImpressionsLifetime.php` | `OA_Maintenance_Priority_AdServer_Task_GetRequiredAdImpressionsLifetime` | 0 |
| `lib/OA/Maintenance/Priority/AdServer/Task/PriorityCompensation.php` | `OA_Maintenance_Priority_AdServer_Task_PriorityCompensation` | 0 |
| `lib/OA/Maintenance/Priority/DeliveryLimitation.php` | `OA_Maintenance_Priority_DeliveryLimitation` | 0 |
| `lib/OA/Maintenance/Priority/DeliveryLimitation/Common.php` | `OA_Maintenance_Priority_DeliveryLimitation_Common` | 3 |
| `lib/OA/Maintenance/Priority/DeliveryLimitation/Empty.php` | `OA_Maintenance_Priority_DeliveryLimitation_Empty` | 1 |
| `lib/OA/Maintenance/Priority/DeliveryLimitation/Factory.php` | `OA_Maintenance_Priority_DeliveryLimitation_Factory` | 3 |
| `lib/OA/Maintenance/Pruning.php` | `OA_Maintenance_Pruning` | 1 |
| `lib/OA/Maintenance/Regenerate.php` | `OA_Maintenance_Regenerate` | 0 |
| `lib/OA/Maintenance/RollupStats.php` | `OA_Maintenance_RollupStats` | 1 |
| `lib/OA/Maintenance/Status.php` | `OA_Maintenance_Status` | 1 |

## Child C: `lib/OA/Dll` → `RV\Legacy\OA\Dll`

Non-test files: 22 | Test files: 11

| File | Classes | External dependents (files) |
|---|---|---|
| `lib/OA/Dll/Advertiser.php` | `OA_Dll_Advertiser` | 2 |
| `lib/OA/Dll/AdvertiserInfo.php` | `OA_Dll_AdvertiserInfo` | 8 |
| `lib/OA/Dll/Agency.php` | `OA_Dll_Agency` | 2 |
| `lib/OA/Dll/AgencyInfo.php` | `OA_Dll_AgencyInfo` | 8 |
| `lib/OA/Dll/Audit.php` | `OA_Dll_Audit` | 3 |
| `lib/OA/Dll/Banner.php` | `OA_Dll_Banner` | 2 |
| `lib/OA/Dll/BannerInfo.php` | `OA_Dll_BannerInfo` | 8 |
| `lib/OA/Dll/Campaign.php` | `OA_Dll_Campaign` | 2 |
| `lib/OA/Dll/CampaignInfo.php` | `OA_Dll_CampaignInfo` | 8 |
| `lib/OA/Dll/Channel.php` | `OA_Dll_Channel` | 1 |
| `lib/OA/Dll/ChannelInfo.php` | `OA_Dll_ChannelInfo` | 3 |
| `lib/OA/Dll/Publisher.php` | `OA_Dll_Publisher` | 3 |
| `lib/OA/Dll/PublisherInfo.php` | `OA_Dll_PublisherInfo` | 10 |
| `lib/OA/Dll/TargetingInfo.php` | `OA_Dll_TargetingInfo` | 6 |
| `lib/OA/Dll/Tracker.php` | `OA_Dll_Tracker` | 1 |
| `lib/OA/Dll/TrackerInfo.php` | `OA_Dll_TrackerInfo` | 2 |
| `lib/OA/Dll/User.php` | `OA_Dll_User` | 4 |
| `lib/OA/Dll/UserInfo.php` | `OA_Dll_UserInfo` | 10 |
| `lib/OA/Dll/Variable.php` | `OA_Dll_Variable` | 1 |
| `lib/OA/Dll/VariableInfo.php` | `OA_Dll_VariableInfo` | 2 |
| `lib/OA/Dll/Zone.php` | `OA_Dll_Zone` | 3 |
| `lib/OA/Dll/ZoneInfo.php` | `OA_Dll_ZoneInfo` | 8 |

## Child D: `lib/OA/DB` → `RV\Legacy\OA\DB`

Non-test files: 19 | Test files: 9

| File | Classes | External dependents (files) |
|---|---|---|
| `lib/OA/DB/AdvisoryLock.php` | `OA_DB_AdvisoryLock` | 6 |
| `lib/OA/DB/AdvisoryLock/file.php` | `OA_DB_AdvisoryLock_file` | 0 |
| `lib/OA/DB/AdvisoryLock/mysqli.php` | `OA_DB_AdvisoryLock_mysqli` | 0 |
| `lib/OA/DB/AdvisoryLock/pgsql.php` | `OA_DB_AdvisoryLock_pgsql` | 0 |
| `lib/OA/DB/Charset.php` | `OA_DB_Charset` | 1 |
| `lib/OA/DB/Charset/mysqli.php` | `OA_DB_Charset_mysqli` | 0 |
| `lib/OA/DB/Charset/pgsql.php` | `OA_DB_Charset_pgsql` | 0 |
| `lib/OA/DB/CustomDatatypes/mysqli.php` | (no class) | 0 |
| `lib/OA/DB/CustomDatatypes/mysqli_info.php` | (no class) | 0 |
| `lib/OA/DB/CustomDatatypes/pgsql.php` | (no class) | 0 |
| `lib/OA/DB/CustomDatatypes/pgsql_info.php` | (no class) | 0 |
| `lib/OA/DB/DataObject/Generator.php` | `OA_DB_DataObject_Generator` | 1 |
| `lib/OA/DB/Distributed.php` | `OA_DB_Distributed` | 4 |
| `lib/OA/DB/Sql.php` | `OA_DB_Sql` | 0 |
| `lib/OA/DB/Table.php` | `OA_DB_Table` | 30 |
| `lib/OA/DB/Table/Core.php` | `OA_DB_Table_Core` | 6 |
| `lib/OA/DB/Table/Priority.php` | `OA_DB_Table_Priority` | 12 |
| `lib/OA/DB/Table/Statistics.php` | `OA_DB_Table_Statistics` | 1 |
| `lib/OA/DB/XmlCache.php` | `OA_DB_XmlCache` | 2 |

## Child E: `lib/max/Delivery` → `RV\Legacy\Max\Delivery`

Non-test files: 16 | Test files: 20

| File | Classes | External dependents (files) |
|---|---|---|
| `lib/max/Delivery/XML-RPC.php` | (no class) | 0 |
| `lib/max/Delivery/adRender.php` | (no class) | 0 |
| `lib/max/Delivery/adSelect.php` | (no class) | 0 |
| `lib/max/Delivery/base64.php` | (no class) | 0 |
| `lib/max/Delivery/cache.php` | (no class) | 0 |
| `lib/max/Delivery/common.php` | (no class) | 0 |
| `lib/max/Delivery/cookie.php` | (no class) | 0 |
| `lib/max/Delivery/flash.php` | (no class) | 0 |
| `lib/max/Delivery/image.php` | (no class) | 0 |
| `lib/max/Delivery/javascript.php` | (no class) | 0 |
| `lib/max/Delivery/limitations.delivery.php` | (no class) | 0 |
| `lib/max/Delivery/limitations.php` | (no class) | 0 |
| `lib/max/Delivery/log.php` | (no class) | 0 |
| `lib/max/Delivery/querystring.php` | (no class) | 0 |
| `lib/max/Delivery/remotehost.php` | (no class) | 0 |
| `lib/max/Delivery/tracker.php` | (no class) | 0 |

## Next wave (do NOT start now)

| Module | Files (total) | Notes |
|---|---|---|
| `lib/OA/Admin` | 129 | Admin UI layer; largest module, many cross-dependencies |
| `lib/max/Dal` | 90 | DataObjects/DAL; heavy MDB2 coupling, migrate after wave 1 patterns are proven |

## Guardrails

- No behavior changes, no refactors beyond the recipe, no test deletions/edits to force green.
- Each child touches only its module scope + autoload + shims.
- "External dependents" above counts files outside the module referencing the class — these rely on the `class_alias` shim staying in place.
