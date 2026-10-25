# Security Policy


## Reporting a Vulnerability

For responsible disclosure of security concerns, see our program page at https://hackerone.com/revive_adserver

## PHP runtime requirements (PHP2Shell: CVE-2026-109618 / CVE-2026-109619)

Two critical, unauthenticated remote code execution bugs in the PHP engine itself
("PHP2Shell", fixed upstream on 2026-10-21) affect every web SAPI (mod_php, PHP-FPM,
php-cgi, FrankenPHP, LiteSpeed). This repository does not ship or select a PHP
runtime (no Dockerfile, container image or deploy manifest): the runtime is chosen
by the operator who installs Revive Adserver. Operators must run one of:

| PHP branch | Minimum fixed release |
|------------|-----------------------|
| 8.5        | 8.5.12                |
| 8.4        | 8.4.27                |
| 8.3        | 8.3.36                |
| 8.2        | 8.2.35                |
| 8.1 and older | **No fixed release (end of life).** Upgrade to 8.2+, or use a distro package whose changelog explicitly lists both CVEs. |

Verify with `php -v` (and `php-fpm -v`) on the host that actually serves traffic,
then restart every PHP worker (FPM pool, Apache, etc.) after upgrading.
The `pre-check.php` minimum (PHP 8.1.0) and the Composer platform setting are the
application's language-level minimums; they do not make a vulnerable runtime safe.

The CI test matrix in `.github/workflows/test.yml` runs on PHP 8.2–8.5 only and
fails if the installed PHP build predates the fix. The PHP 8.1 lane was removed
because no fixed 8.1 build exists.

**Mitigation (not a fix):** while a host is still on a vulnerable build, set
`session.upload_progress.enabled = 0` in `php.ini` or the PHP-FPM pool
configuration (not via `ini_set()` or `.user.ini`) and restart the PHP workers.
This reduces exposure to one of the two bugs; upgrading the runtime is the only
complete fix.
