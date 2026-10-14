# Site configuration diff security model

The default mode is **read-only**. A snapshot is a sanitized inventory, not a backup or a complete record of all Moodle configuration values. Unknown settings and **all plugin settings** are redacted unless a future reviewed policy explicitly adds them. Credentials, DB configuration, file storage details, authentication protections, mail secrets, tokens, sessions, cookies and salts must never be exported or applied. Avoid adding new keys to `policy::READABLE` or `policy::WRITABLE` without an individual data-classification and regression-test review.

We intentionally do not hash redacted values: many secrets/settings are low entropy, so exporting a digest would enable offline guessing. Because only presence is preserved for unknown settings, the comparison **cannot truthfully state that two present redacted values differ**. It displays `hidden`. Missing keys remain detectable.

The SHA-256 integrity digest is unauthenticated. An attacker able to alter a snapshot can recompute it. Import from trusted channels only. Imported JSON is bounded to 1 MiB, schema-checked and rejected when unreviewed clear-text fields appear. Imported snapshots and audit data reside in the Moodle database; database access permissions, backups, encryption policies and retention procedures remain the site operator's responsibility.

Apply is a distinct and separately guarded action requiring all of: authenticated user, CSRF sesskey, system-context capability, site administrator role, and an explicit numeric ID in `allowedappliers`. For fresh installations the last condition is empty and nobody can apply. Plans use an allowlist that currently includes only core language, country and timezone. Theme, calendar, authentication, plugin settings and anything requiring a specialized API or restart are **manual**.

A plan is tied to the destination installation and uses optimistic per-setting checks. The controller holds an application lock for this plugin's plans, but third-party writes to the same Moodle setting may still race between recheck and `set_config()`. Only target files/DB configuration modifications managed by this plugin are serialized. After applying, inspect the audit and the live site. Effects of external services, already-sent messages, running jobs, caches and code changes are not automatically reversible. Reversal is merely a new gated preview checked again against the current value.

The Privacy API exports attributable records and clears user IDs upon GDPR erasure, while retaining de-identified configuration audit history. Set an appropriate retention schedule for operational records and review plugin access roles periodically.

Report vulnerabilities privately to the repository maintainers. Do not paste sensitive snapshots or production credentials into public issue threads.
