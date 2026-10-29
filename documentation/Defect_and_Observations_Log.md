# CampusCare — Defect and Observations Log

## 1. Purpose

This document records defects identified during testing, observations from test execution, and areas that require further testing.

## 2. Defect Summary

| Metric | Result |
|---|---:|
| Defects confirmed by the five automated tests | 0 |
| Automated test failures in the latest recorded run | 0 |
| Unverified areas requiring further testing | Multiple |

**Note:** Zero automated test failures does not prove that the entire application is defect-free. No claim is made that exhaustive manual or security testing has been completed.

## 3. Test Observations

| Observation ID | Observation | Status |
|---|---|---|
| OBS-01 | Valid student login redirects to the student dashboard | Verified by automated test |
| OBS-02 | Invalid student password is rejected | Verified by automated test |
| OBS-03 | Complaint submission creates a complaint visible in the student's list | Verified by automated test |
| OBS-04 | A student cannot view another student's complaint using a modified complaint ID | Verified by automated test |
| OBS-05 | Admin status updates and notes are visible to the complaint owner | Verified by automated test |
| OBS-06 | Complaint submission adds records to the test database on each successful run | Expected test side effect; monitor test data |
| OBS-07 | Admin status-update testing modifies an existing complaint and creates status history | Expected test side effect; use designated test data |

## 4. Areas Requiring Further Testing

The following areas have not been established as fully tested by the five current automated tests:

- Student registration validation and duplicate email handling.
- Logout and session protection.
- Complaint form validation for missing or invalid inputs.
- Complaint filtering and administrative search.
- Student activation and deactivation.
- Reports and CSV export.
- Additional authorization and security scenarios.
- Browser compatibility and responsive layouts.
- Database error handling and recovery.

These are pending test activities, not confirmed defects.

## 5. Defect Recording Procedure

If a defect is discovered, record:

1. Defect ID.
2. Short description.
3. Steps to reproduce.
4. Expected result.
5. Actual result.
6. Severity and priority.
7. Screenshot or log evidence.
8. Current status.

## 6. Conclusion

The latest automated execution reported five passed tests and zero failures. The tested workflows behaved as expected. Further testing is necessary before concluding that all application requirements have been verified.
