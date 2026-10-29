# CampusCare — Requirements Traceability Matrix (RTM)

## 1. Purpose

The Requirements Traceability Matrix maps CampusCare functional requirements to the automated test cases designed to verify them.

## 2. Traceability Matrix

| Requirement ID | Requirement | Test Case ID | Test File | Recorded Result |
|---|---|---|---|---|
| REQ-01 | Students can log in with valid credentials | AT-01 | `test_login.py::test_student_login_success` | PASS |
| REQ-02 | Invalid student credentials are rejected | AT-02 | `test_login.py::test_student_login_invalid_password` | PASS |
| REQ-03 | Students can submit complaints | AT-03 | `test_complaint_submission.py::test_student_can_submit_complaint` | PASS |
| REQ-04 | Students cannot view another student's complaint | AT-04 | `test_complaint_security.py::test_student_cannot_view_another_students_complaint` | PASS |
| REQ-05 | Administrators can update complaint status and notes | AT-05 | `test_admin_status_update.py::test_admin_updates_complaint_status` | PASS |

## 3. Coverage Summary

- Functional requirements listed: 5
- Requirements linked to automated tests: 5
- Requirements without an automated test in this matrix: 0
- Recorded result for the mapped automated tests: PASS

**Traceability coverage:** 5 / 5 × 100 = 100% for the five requirements listed in this matrix.

This percentage represents coverage of the requirements listed here only. It does not represent coverage of every feature or requirement in the complete CampusCare application.

## 4. Limitations

The current automated suite does not independently verify every application feature. Additional test cases may be required for registration validation, logout, user activation/deactivation, report exports, complaint filtering, invalid form submissions, and other implemented functionality.

## 5. Conclusion

Each of the five listed functional requirements is linked to an automated Selenium test. All five tests passed in the latest recorded execution.
