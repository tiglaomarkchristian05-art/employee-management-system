# Core 3 panel-readiness release

This release covers training-needs analysis, configurable certificate completion rules, automated certificate PDFs and registry synchronization, document resubmission/version tracking, contract expiration monitoring, benefit and loan workflow controls, named exit-clearance approvers, notification/audit improvements, and statutory-calculation safeguards.

## Deployment prerequisites

1. Back up the target database and generated upload storage.
2. Use the PHP dependencies locked in `composer.lock` (`composer install --no-dev --prefer-dist`).
3. Keep environment credentials in the deployment environment. Do not copy a developer `.env`, local database dump, generated certificate, or uploaded employee document.
4. Validate the migration sequence on staging before production.

## Database upgrade order

For an existing installation, apply the existing baseline migrations first if that environment does not already have them. The changes in this release then require this order:

1. `database/security_hardening.sql`
2. `database/preoral_defense_recommendations.sql`
3. `database/employee_certification_registry.sql`
4. `database/automated_training_certificates.sql`
5. `database/panel_readiness_rules.sql`

Document versioning depends on `database/document_contract_workflow_patch.sql`. Notification ownership fields depend on `database/phase11_notifications.sql`. Do not import `database/seed.sql` or a full local database export as an upgrade mechanism.

New installations can use `php scripts/init-database.php`; production bootstrap passwords must be supplied through private environment variables. The migration SQL uses MariaDB/MySQL `IF NOT EXISTS` support, so confirm compatibility with the target database version.

## Verification

Run the following against an isolated development or staging database:

```text
php tests/panel_readiness_http_test.php
php -d session.save_path=tests/artifacts tests/panel_readiness_rules_test.php
php -d session.save_path=tests/artifacts tests/automated_training_certificate_test.php
php -d session.save_path=tests/artifacts tests/exit_clearance_accountability_test.php
php -d session.save_path=tests/artifacts tests/contract_expiration_monitoring_test.php
php -d session.save_path=tests/artifacts tests/core3_demo_dataset_test.php
php -d session.save_path=tests/artifacts tests/rbac_authorization_test.php
```

When Apache uses XAMPP PHP, run the HTTP test with the matching XAMPP PHP binary so the CLI and web server share the configured session directory.

## Operational limitations

- Statutory rates remain unconfirmed legacy demonstration values. Monetary posting stays blocked until the client, Payroll, and legal owners provide approved rates, sources, and effective dates.
- Core 3 marks an employee **Ready for Payroll**; it does not calculate or transmit final pay to an external Payroll API.
- Completed final-clearance records are read-only. Any future reopening requirement needs a separately authorized and audited business workflow.
- Local demo datasets and browser rehearsal artifacts are not deployment assets.
