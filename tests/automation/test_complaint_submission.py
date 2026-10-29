
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
    browser.implicitly_wait(2)

    yield browser
    browser.quit()


def test_student_can_submit_complaint(driver):
    email = os.getenv("CAMPUSCARE_STUDENT_EMAIL")
    password = os.getenv("CAMPUSCARE_STUDENT_PASSWORD")

    if not email or not password:
        pytest.skip(
            "Set CAMPUSCARE_STUDENT_EMAIL and "
            "CAMPUSCARE_STUDENT_PASSWORD before running this test."
        )

    # Step 1: Log in as a student.
    driver.get(f"{BASE_URL}/login.php")

    WebDriverWait(driver, 10).until(
        EC.visibility_of_element_located((By.NAME, "email"))
    ).send_keys(email)

    driver.find_element(By.NAME, "password").send_keys(password)
    driver.find_element(By.NAME, "password").submit()

    WebDriverWait(driver, 10).until(
        EC.url_contains("/student/dashboard.php")
    )

    # Step 2: Open the complaint form.
    driver.get(f"{BASE_URL}/student/submit_complaint.php")

    # Use a unique subject so this test is easy to identify.
    subject = "Selenium Test Complaint - Internet Connectivity"
    description = (
        "Internet connectivity in the college computer laboratory "
        "is frequently unavailable during practical sessions."
    )

    # Step 3: Fill in the form using its actual field names.
    Select(driver.find_element(By.NAME, "category")).select_by_value(
        "Internet"
    )

    driver.find_element(By.NAME, "subject").send_keys(subject)

    driver.find_element(By.NAME, "description").send_keys(
        description
    )

    Select(driver.find_element(By.NAME, "priority")).select_by_value(
        "High"
    )

    # Step 4: Submit the complaint.
    driver.find_element(
        By.CSS_SELECTOR, "button[type='submit']"
    ).click()

    # Step 5: Confirm that the application redirects to My Complaints.
    WebDriverWait(driver, 10).until(
        EC.url_contains("/student/my_complaints.php")
    )

    assert "submitted=1" in driver.current_url

    # Step 6: Confirm that the new complaint appears in the list.
    WebDriverWait(driver, 10).until(
        EC.text_to_be_present_in_element(
            (By.TAG_NAME, "body"), subject
        )
    )

    page_text = driver.find_element(By.TAG_NAME, "body").text

    assert subject in page_text
    assert "Internet" in page_text
    assert "High" in page_text
