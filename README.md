# Moodle Site Configuration Diff (`local_siteconfigdiff`)

A controlled, offline-first configuration snapshot and comparison plugin for Moodle 4.5 LTS and 5.0–5.2 (with PHP versions appropriate to each branch).

## Workflow

1. Use **Site administration → Plugins → Local plugins → Configuration comparisons**.
2. Configure permissions for `view`, `export` and `import`. Automatic `apply` is **off by default**.
3. An administrator with `export` can capture and download a sanitized JSON snapshot. Transport the file out-of-band to a second installation; **no remote admin endpoints** are required.
4. An administrator with `import` imports the JSON file, which is bounded, parsed, structurally checked, and checksum-verified.
5. Compare two imported snapshots, or a source snapshot against the **live** destination. Review redacted, missing, added, removed or modified values, plugin manifests, expected impact and risk.
7. To authorize automatic changes, explicitly grant the `local/siteconfigdiff:apply` capability and set the site administrator's numeric ID in `local_siteconfigdiff/allowedappliers` (comma-separated). A granted capability *alone* is not sufficient.
8. In a comparison against the live destination, select only available checkboxes and create a persistent preview plan. A second POST approval applies only the plan's allowlisted entries, rejecting values that have changed since preview.
9. Audit results are persisted. A reversal creates **a new preview plan** from successful modifications; nothing is rolled back automatically.

### Explicit v1.0.0 write allowlist

Only **core `lang`, `country`, and `timezone`** are eligible, subject to strict value validation. Theme and calendar can be inspected but must be applied manually. **No plugin configuration** can be automatically written in this version.

### Snapshot content

Exports include a non-secret installation fingerprint (`SHA-256` of `wwwroot`), Moodle release, timestamp, plugin identifiers and versions, sorted component settings, a format/schema version and a SHA-256 content checksum. Only reviewed keys reveal values: `core:lang`, `core:country`, `core:timezone`, `core:theme`, `core:calendar`. Every other key is represented solely by `{"kind":"redacted","present":true}`. Secrets are neither exported in plaintext nor in digest form.

The published schema lives at `docs/snapshot.schema.json`; import performs explicit local structural, type, key and allowlist checks, with a 1 MiB limit. Import never executes or deserializes PHP, fetches URLs or loads uploaded files as scripts. The checksum detects changes, **not identity or origin authenticity**. For origin assurance, use externally signed transport or a trusted channel.

### Execution and audit

Only current-site comparisons can yield plans. Other reports are inherently read-only. Every approved setting is checked again just before `set_config()`. The plan's installation identity must match the live site. Entries are independent, so one stale change does not imply rollback of earlier writes. Audit entries record the actor ID, timestamp, outcome, and only policy-approved values. Each plan can be applied at most once; further requests are rejected.

The code uses Moodle configuration APIs, XMLDB, Mustache templates, `render_from_template()`, component capabilities and Moodle events. The administration controller invokes explicit domain services and a repository; no renderer classes or `export_for_template()` exist.

## Tests

- PHPUnit: `vendor/bin/phpunit local/siteconfigdiff/tests/engine_test.php` from a prepared Moodle checkout, or `moodle-plugin-ci phpunit --fail-on-warning`.
- Behat: `moodle-plugin-ci behat` in an installed test Moodle.
- Lint/code style: `find . -name '*.php' -exec php -l {} \;`, `moodle-plugin-ci phpcs`, `moodle-plugin-ci validate`.
- Required finalization in the separate validator repository: `python3 .chatgpt/run.py /absolute/path/to/local/siteconfigdiff` then `python3 .chatgpt/run.py --check /absolute/path/to/local/siteconfigdiff`.

See [SECURITY.md](SECURITY.md) for operational and threat-model limitations.
