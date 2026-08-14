# Migrating `lib/OA` to Composer autoloading and PSR-4

This note describes the pattern used for the first increment of the `lib/OA`
modernisation, so that the remaining subtrees can be converted the same way.
`lib/OA/Algorithm/` is the reference conversion.

## Where we are

`composer.json` now contains three autoload mechanisms for `lib/OA`:

```jsonc
"autoload": {
    "psr-4": {
        "OA\\Algorithm\\": "lib/OA/Algorithm/"   // converted subtrees
    },
    "classmap": [ "lib/OA/" ],                   // everything not converted yet
    "exclude-from-classmap": [ "/tests/", "/lib/OA/Algorithm/" ],
    "files": [ "lib/OA/Algorithm/legacy_aliases.php" ]  // compatibility bridge
}
```

* The **classmap** makes every remaining PEAR-style class (`OA_DB` in
  `lib/OA/DB.php`, `OA_Dll` in `lib/OA/Dll.php`, ...) autoloadable, so the manual
  `require_once MAX_PATH . '/lib/OA/...'` lines can be deleted incrementally,
  file by file, without a big-bang rename.
* The **PSR-4 prefix** covers the subtrees that have already been converted.
* The **files** entry registers the legacy class name bridge for those subtrees.

Run `composer dump-autoload` after changing any of these, and after adding,
renaming, or removing a class in a classmapped directory.

## Converting a subtree

Pick a subtree with few external dependencies and do the following.

### 1. Namespace mapping

The PEAR name maps to the namespace one-to-one, dropping the `OA_` prefix and
turning each `_` into `\`:

| Legacy class                         | File                                       | New class                                |
| ------------------------------------ | ------------------------------------------ | ---------------------------------------- |
| `OA_Algorithm_Dependency`            | `lib/OA/Algorithm/Dependency.php`          | `OA\Algorithm\Dependency`                |
| `OA_Algorithm_Dependency_Item`       | `lib/OA/Algorithm/Dependency/Item.php`     | `OA\Algorithm\Dependency\Item`           |
| `OA_Algorithm_Dependency_Source_HoA` | `lib/OA/Algorithm/Dependency/Source/HoA.php` | `OA\Algorithm\Dependency\Source\HoA`   |

No file moves are needed: the directory layout is already PSR-4 compatible.

Then, per file:

1. Add the `namespace` declaration below the licence header.
2. Rename the class to its last segment and add `use` statements for the classes
   it references (including global ones such as `Exception`, `PEAR` or
   `MDB2_Driver_Common`, which are no longer resolved implicitly).
3. Delete the now redundant `require_once MAX_PATH . '/lib/OA/...'` lines for
   classes inside the subtree.
4. Add a PSR-4 prefix for the subtree in `composer.json` and, because the
   subtree is no longer PEAR-style, add it to `exclude-from-classmap`.

### 2. The `class_alias` bridge

Every converted subtree gets a `legacy_aliases.php` file, registered under
`autoload.files`, mapping each legacy name to its replacement:

```php
$aAliases = [
    'OA_Algorithm_Dependency' => OA\Algorithm\Dependency::class,
    // ...
];

spl_autoload_register(static function (string $class) use ($aAliases): void {
    if (isset($aAliases[$class])) {
        class_alias($aAliases[$class], $class);
    }
});
```

The alias is created from an autoloader rather than eagerly, so the namespaced
classes are only loaded when a legacy name is actually used — this matters for
the delivery engine, which is included on every ad request.

Because `class_alias()` creates a real alias, `instanceof`, type declarations
and `assertIsA()` in the SimpleTest suites keep working with either name. Call
sites can therefore be updated at leisure; update the ones you can see (as was
done in `lib/OX/Extension/deliveryLog/Setup.php`) and leave the rest to the
bridge.

Remove a subtree's aliases only once no call site — including plugins,
`etc/changes/` upgrade scripts and tests — uses the legacy names.

### 3. Typed property rules

While converting, add types where they are provably safe. The suite still runs
on real data, so err on the side of caution:

* Add `declare`d property types when every assignment is visible inside the
  class (`protected bool $ignoreOrphans;`, `protected array $selected = [];`).
* Use a nullable type with a `null` default rather than an uninitialised typed
  property, so that reading an unset property keeps returning `null` instead of
  raising `Error: must not be accessed before initialization`. `OA_DB_Charset::$oDbh`
  is the example.
* Do not add a parameter type to a method that legacy code may call with a
  `PEAR_Error` (a common "return value" in this codebase). Document the union in
  the docblock instead.
* Return types can be widened honestly: several methods return `false` on error,
  which is expressed as `array|false` rather than being silently "fixed".
* Assigning by reference (`$this->oDbh = &$oDbh;`) is incompatible with typed
  properties; objects are handles already, so drop the `&`.

### 4. Validate

```bash
composer dump-autoload
lib/vendor/bin/phpstan analyse
```

plus the SimpleTest suites (`ant simpletest`, or `tests/run.php` for a single
directory).

## Database access

`lib/OA/DB.php` keeps returning the MDB2 handle from `OA_DB::singleton()`, but
`OA_DB::connection()` now returns the same connection wrapped in
`RV\Database\ConnectionInterface` (`RV\Database\Mdb2Connection`). New and
migrated code should depend on that interface — it exposes only the
query/exec/fetch operations the codebase actually uses and throws
`RV\Database\DatabaseException` instead of returning `PEAR_Error` — so that MDB2
can eventually be replaced by another implementation without touching call
sites. `Mdb2Connection::getMdb2Connection()` is the escape hatch for code that
still needs the raw handle.
