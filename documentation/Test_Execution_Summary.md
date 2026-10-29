# CampusCare — Test Execution Summary

## 1. Execution Details

| Item | Details |
|---|---|
| Project | CampusCare — Student Complaint and Grievance Management System |
| Test type | Automated functional testing |
| Automation tool | Selenium WebDriver |
| Test framework | pytest |
| Browser | Google Chrome |
| Execution environment | Windows, local XAMPP server |
| Test execution command | `.\run_tests.ps1` |
| HTML report | `reports/selenium_report.html` |
| Latest recorded execution | 29 October 2026 |
| Execution duration | Approximately 21 seconds |

## 2. Test Results

| Test ID | Test Case | Expected Result | Recorded Result |
|---|---|---|---|
| AT-01 | Student login with valid credentials | Student reaches dashboard | PASS |
| AT-02 | Student login with invalid password | Login is rejected | PASS |
| AT-03 | Student submits a complaint | Complaint is saved and appears in the complaint list | PASS |
| AT-04 | Student accesses another student's complaint | Access is denied | PASS |
| AT-05 | Admin updates complaint status | Updated status and note are visible to the complaint owner | PASS |

## 3. Execution Statistics

| Metric | Result |
|---|---:|
| Total automated tests | 5 |
| Passed | 5 |
| Failed | 0 |
| Skipped | 0 |
| Errors | 0 |
| Pass rate | 100% |

**Pass rate calculation:** (Passed tests / Total tests) × 100 = (5 / 5) × 100 = 100%.

## 4. Test Observations

1. Valid student credentials successfully authenticated the student.
2. An incorrect password was rejected.
3. A complaint was submitted successfully and displayed in the student's complaint list.
4. A student was prevented from viewing another student's complaint details through a modified complaint ID.
5. The administrator updated a complaint status and added a note; the complaint owner could see the update.

## 5. Test Evidence

The detailed execution evidence is available in:

`reports/selenium_report.html`

The report records the five automated test cases and their results.

## 6. Conclusion

The five automated functional test cases passed in the latest recorded execution. The results provide evidence that the tested authentication, complaint submission, complaint ownership protection, and administrator status update workflows behaved as expected.

These results apply only to the tested scenarios and do not establish that all CampusCare functionality is defect-free.
