
import os
import pytest

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select, WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

BASE_URL = "http://localhost/CampusCare"


@pytest.fixture
def driver():
    options = webdriver.ChromeOptions()
    options.add_argument("--start-maximized")

    browser = webdriver.Chrome(options=options)
    yield browser
    browser.quit()


def login(driver, email, password, expected_path):
    driver.get(f"{BASE_URL}/login.php")

    WebDriverWait(driver, 10).until(
        EC.visibility_of_element_located((By.NAME, "email"))
    ).send_keys(email)

    driver.find_element(By.NAME, "password").send_keys(password)
    driver.find_element(By.NAME, "password").submit()

    WebDriverWait(driver, 10).until(
        EC.url_contains(expected_path)
    )


def test_admin_updates_complaint_status(driver):
    admin_email = os.getenv("CAMPUSCARE_ADMIN_EMAIL")
    admin_password = os.getenv("CAMPUSCARE_ADMIN_PASSWORD")
    student_email = os.getenv("CAMPUSCARE_STUDENT2_EMAIL")
    student_password = os.getenv("CAMPUSCARE_STUDENT2_PASSWORD")
    complaint_id = os.getenv("CAMPUSCARE_OTHER_COMPLAINT_ID")

    if not all([
        admin_email,
        admin_password,
        student_email,
        student_password,
        complaint_id,
    ]):
        pytest.skip(
            "Set the admin credentials, second-student credentials, "
            "and CAMPUSCARE_OTHER_COMPLAINT_ID."
        )

    note = "The IT department has been notified. Please check the laboratory computers."

    # 1. Log in as admin.
    login(driver, admin_email, admin_password, "/admin/dashboard.php")

    # 2. Open complaint management.
    driver.get(f"{BASE_URL}/admin/complaints.php")

    # 3. Locate the update form for the selected complaint ID.
    update_form = None

    for form in driver.find_elements(By.CSS_SELECTOR, "form[method='POST']"):
        hidden_id = form.find_element(
            By.NAME, "complaint_id"
        ).get_attribute("value")

        if hidden_id == complaint_id:
            update_form = form
            break

    assert update_form is not None, (
        f"Complaint ID {complaint_id} was not found in admin complaints."
    )

    # 4. Update status and note.
    Select(update_form.find_element(By.NAME, "status")).select_by_value(
        "In Progress"
    )

    note_field = update_form.find_element(By.NAME, "resolution_note")
    note_field.clear()
    note_field.send_keys(note)

    update_form.find_element(
        By.CSS_SELECTOR, "button[type='submit']"
    ).click()

    # 5. Verify admin update confirmation.
    WebDriverWait(driver, 10).until(
        EC.url_contains("updated=1")
    )

    assert "Complaint updated successfully." in (
        driver.find_element(By.TAG_NAME, "body").text
    )

    # 6. Log out and log in as the student who owns this complaint.
    driver.get(f"{BASE_URL}/logout.php")

    login(
        driver,
        student_email,
        student_password,
        "/student/dashboard.php",
    )

    # 7. Open the complaint details and verify the saved update.
    driver.get(
        f"{BASE_URL}/student/complaint_details.php?id={complaint_id}"
    )

    WebDriverWait(driver, 10).until(
        EC.text_to_be_present_in_element(
            (By.TAG_NAME, "body"), note
        )
    )

    page_text = driver.find_element(By.TAG_NAME, "body").text

    assert "In Progress" in page_text
    assert note in page_text
