# QA & Testing Guide

This guide explains how to perform unit testing, static analysis, and database auditing for this Symfony project.

## 1. Unit Testing (PHPUnit)
Unit tests ensure that individual components of the application work as expected.

### How to Run Tests
To run all tests in the project:
```bash
php bin/phpunit
```

To run a specific test file:
```bash
php bin/phpunit tests/Entity/PostTest.php
```

### Creating New Tests
New tests should be placed in the `tests/` directory and follow the naming convention `*Test.php`.
- Use `TestCase` for pure unit tests.
- Use `KernelTestCase` or `WebTestCase` for tests that require Symfony services or HTTP simulation.

---

## 2. Static Analysis (PHPStan)
PHPStan finds bugs in your code without actually running it. It helps catch type mismatches and potential null pointer exceptions.

### How to Run PHPStan
To analyze the entire `src/` directory:
```bash
vendor/bin/phpstan analyse -c phpstan.neon
```

### Configuration
The configuration is stored in `phpstan.neon`. You can adjust the `level` (0-9) to increase the strictness of the analysis.

---

## 3. Database Auditing
Ensuring the database schema is synchronized with your PHP entities is crucial for stability.

### Schema Validation
To check if the mapping is correct and the database is in sync:
```bash
php bin/console doctrine:schema:validate
```

### Database Migration
If you make changes to your entities, always create and run a migration:
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

---

## 4. QA Reports
Reports are automatically generated or manually updated in:
`tests/TestResult/QA_Report.md`

Always refer to this file for the latest health status of the project.
