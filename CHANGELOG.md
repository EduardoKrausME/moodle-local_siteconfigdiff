# Changelog

This changelog documents only the two most recent **published** releases of `local_siteconfigdiff`, verified on October 24, 2026. Each entry reflects the published Git tag compared with its immediately preceding published release tag. Commits made on October 20–21, 2026 (`Mustache`, `Boilerplate Headers`, and `-core_text`) occurred after release `1.0.3` and are intentionally excluded.

## [1.0.3] - 2026-10-15

### Added
- Added the plugin icon asset at `pix/icon.png`.

### Release metadata
- Updated `version.php` from internal version `2026101402`, release `1.0.2`, to internal version `2026101500`, release `1.0.3`.
- No plugin application logic files changed between the published tags.

**Full comparison:** [1.0.2...1.0.3](https://github.com/EduardoKrausME/moodle-local_siteconfigdiff/compare/1.0.2...1.0.3)

## [1.0.2] - 2026-10-15

### Fixed
- Changed the configuration snapshot collector to read Moodle core configuration explicitly with `get_config('core')`, rather than calling `get_config()` without a component.
- Corrected privacy metadata registration by mapping the four plugin-owned history tables to their corresponding language-string keys, while retaining user-specific export and audit-ownership anonymization.
- Adjusted the generic configuration audit event to store the affected record ID in its `other` payload instead of assigning an `objectid` belonging to different record tables.
- Required authentication in the plugin's `index.php` entry point.
- Updated Mustache comparison and application-plan templates to preserve the intended action URL in link and form attributes.

### Tests and code quality
- Adjusted Behat navigation and assertions for snapshot capture and reviewed application plans, including the displayed applied status.
- Updated PHPUnit regression annotations, callback typing, PHPDoc blocks and formatting to address Moodle Code Checker / PHPCS findings.
- Standardized licensing comments and method documentation across controllers, domain services, repositories, events, privacy code and plugin files.

### Maintenance
- Removed the former `.github/workflows/moodle-ci.yml` workflow in the historical release comparison. This changelog does not modify workflows.
- Updated `version.php` from internal version `2026101401`, release `1.0.1`, to internal version `2026101402`, release `1.0.2`.

**Full comparison:** [1.0.1...1.0.2](https://github.com/EduardoKrausME/moodle-local_siteconfigdiff/compare/1.0.1...1.0.2)
