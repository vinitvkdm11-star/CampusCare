
# CampusCare — Student Complaint & Grievance Management System

CampusCare is a web-based college grievance management system that allows students to submit complaints, track their status, and view resolution updates. Administrators can manage complaints, update statuses, manage users, and view reports.

## Features

### Student Module
- Student registration and login
- Submit complaints with category, subject, description, and priority
- View submitted complaints
- Track complaint status
- View complaint details and resolution updates

### Admin Module
- Admin login and dashboard
- View and manage student complaints
- Update complaint status
- Add resolution notes
- Manage users
- View complaint reports

### Testing and Quality Assurance
- Automated browser testing using Selenium and Python
- Test execution using pytest
- Login validation testing
- Complaint submission testing
- Complaint status update testing
- Authorization testing to prevent students from viewing other students' complaints
- Test execution report in HTML format

## Technology Stack

| Component | Technology |
|---|---|
| Frontend | HTML, CSS |
| Backend | PHP |
| Database | MySQL / MariaDB |
| Local Server | XAMPP |
| Automation Testing | Python, Selenium, pytest |
| Version Control | Git and GitHub |

## Project Structure

```text
CampusCare/
├── admin/
│   ├── complaints.php
│   ├── dashboard.php
│   ├── manage_users.php
│   └── reports.php
├── config/
│   ├── database.example.php
│   └── database.php
├── database/
│   └── campuscare.sql
├── documentation/
│   ├── Defect_and_Observations_Log.md
│   ├── Requirements_Traceability_Matrix.md
│   ├── Test_Execution_Summary.md
│   └── Test_Plan.md
├── reports/
│   └── selenium_report.html
├── student/
│   ├── complaint_details.php
│   ├── dashboard.php
│   ├── my_complaints.php
│   └── submit_complaint.php
├── tests/
│   └── automation/
├── .gitignore
├── login.php
├── register.php
├── logout.php
└── run_tests.ps1
```

## Database Design

CampusCare uses three main database tables:

- **users** — stores user accounts and roles.
- **complaints** — stores complaint information, priority, status, and resolution notes.
- **complaint_updates** — stores complaint status changes and update comments.

The database schema is available in `database/campuscare.sql`.

## Installation and Setup

### Prerequisites

- XAMPP with Apache and MySQL/MariaDB
- PHP compatible with the project
- Python 3.11 or a compatible version
- Google Chrome
- Git (optional)

### 1. Clone the Repository

```bash
git clone https://github.com/vinitvkdm11-star/CampusCare.git
```

Alternatively, download the repository as a ZIP file and extract it into your XAMPP `htdocs` directory.

### 2. Configure the Database

1. Start Apache and MySQL/MariaDB from XAMPP.
2. Open phpMyAdmin.
3. Create a database named `campuscare`.
4. Import `database/campuscare.sql`.
5. Copy `config/database.example.php` and rename the copy to `database.php`.
6. Update the database host, port, database name, username, and password according to your local environment.

**Important:** Keep `config/database.php` private. Never upload actual database passwords or credentials to GitHub.

### 3. Run the Application

Place the project inside the XAMPP `htdocs` directory.

Open the application in your browser:

```text
http://localhost/CampusCare/
```

Open the login page if the application does not redirect automatically:

```text
http://localhost/CampusCare/login.php
```

Register a student account to test the student module. Configure an administrator account securely before testing admin features.

### 4. Run the Automated Tests

Open a terminal in the project directory.

Create and activate a Python virtual environment:

```powershell
python -m venv .venv
.\.venv\Scripts\Activate.ps1
```

Install the test dependencies:

```powershell
pip install -r tests/automation/requirements.txt
```

Ensure Apache and the database are running, the application is configured, and the test prerequisites and test accounts are available.

Run the tests:

```powershell
python -m pytest tests/automation -v
```

Alternatively, use the included PowerShell test runner:

```powershell
.\run_tests.ps1
```

Check the `reports/` directory for the generated HTML report, if produced by the test runner.

## Automated Testing Results

The initial local Selenium test run completed successfully with **5 passed tests**.

The tested scenarios included:

- Successful student login
- Login with an invalid password
- Student complaint submission
- Admin complaint status update
- Prevention of unauthorized access to another student's complaint

These results represent the recorded local test run. Results may differ in other environments.

## Live Demo

**Website:** https://campuscare-demo.infy.click/

The live demo may depend on hosting availability. Use demonstration accounts and avoid entering real or sensitive personal information.

## Future Enhancements

- Email notifications for complaint updates
- Complaint filtering and advanced analytics
- Improved mobile-responsive interface
- More automated test cases
- Enhanced reporting and dashboard visualizations

## Author

**CampusCare — Academic Project**

Developed as a web technology and software testing project to demonstrate PHP web development, database integration, role-based access, complaint management, and automated testing.

## License

This project is intended for educational and portfolio purposes.
