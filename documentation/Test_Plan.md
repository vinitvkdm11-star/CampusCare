# CampusCare — Software Test Plan

## 1. Project Overview

**Project Name:** CampusCare — Student Complaint and Grievance Management System

**Testing Type:** Manual Testing and Automated Functional Testing

**Technology Stack:** PHP, HTML, CSS, JavaScript, MySQL/MariaDB, XAMPP, Selenium WebDriver, Python, and pytest.

## 2. Testing Objectives

- Verify that students can register and log in.
- Verify that invalid login credentials are rejected.
- Verify that students can submit complaints and view their complaints.
- Verify that students cannot access complaints belonging to other students.
- Verify that administrators can update complaint statuses and add notes.
- Verify that status updates are visible to the student who owns the complaint.

## 3. Scope of Testing

### In Scope
- Student authentication.
- Complaint submission.
- Complaint ownership and access control.
- Administrator complaint status updates.
- Student visibility of complaint status and notes.
- Automated functional regression testing.

### Out of Scope
- Production deployment and load testing.
- Formal penetration testing.
- Email/SMS notification testing, unless implemented.
- Third-party integration testing, unless implemented.

## 4. Test Environment

| Component | Configuration |
|---|---|
| Operating System | Windows |
| Local Server | XAMPP Apache |
| Database | XAMPP MariaDB |
| Backend | PHP |
| Browser | Google Chrome |
| Automation | Selenium WebDriver |
| Test Framework | pytest |
| Test Report | pytest-html |

## 5. Test Strategy

Automated browser tests are executed using Selenium WebDriver and pytest. Tests cover valid login, invalid login, complaint submission, complaint ownership protection, and administrator status updates.

The test suite is executed using `run_tests.ps1`. An HTML report is generated at `reports/selenium_report.html`.

Manual testing should be documented separately using the manual test-case workbook.

## 6. Entry Criteria

- Apache and MariaDB are running.
- CampusCare is accessible at `http://localhost/CampusCare`.
- Required test accounts exist.
- Python virtual environment and test dependencies are installed.

## 7. Exit Criteria

- All planned test cases have been executed.
- Test results are recorded accurately.
- Failed tests, if any, are investigated and documented.
- The final test report is saved with the project.

## 8. Current Automation Results

The latest recorded automated execution completed with **5 passed tests and 0 failures** in approximately 21 seconds.

This result applies to the five existing automated tests only. It does not establish that every application feature has been tested or that all manual test cases have passed.
