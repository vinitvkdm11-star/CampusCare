
import os
import pytest

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

BASE_URL = "http://localhost/CampusCare"


@pytest.fixture
def driver():
    options = webdriver.ChromeOptions()
    options.add_argument("--start-maximized")

    browser = webdriver.Chrome(options=options)
    yield browser
    browser.quit()


def test_student_cannot_view_another_students_complaint(driver):
    email = os.getenv("CAMPUSCARE_STUDENT_EMAIL")
    password = os.getenv("CAMPUSCARE_STUDENT_PASSWORD")
    other_complaint_id = os.getenv("CAMPUSCARE_OTHER_COMPLAINT_ID")

    if not email or not password or not other_complaint_id:
        pytest.skip(
            "Set the student credentials and "
            "CAMPUSCARE_OTHER_COMPLAINT_ID before running."
        )

    # Log in as the first student.
    driver.get(f"{BASE_URL}/login.php")

    WebDriverWait(driver, 10).until(
        EC.visibility_of_element_located((By.NAME, "email"))
    ).send_keys(email)

    driver.find_element(By.NAME, "password").send_keys(password)
    driver.find_element(By.NAME, "password").submit()

    WebDriverWait(driver, 10).until(
        EC.url_contains("/student/dashboard.php")
    )

    # Try to access a complaint belonging to the second student.
    driver.get(
        f"{BASE_URL}/student/complaint_details.php"
        f"?id={other_complaint_id}"
    )

    # The application should reject access with HTTP 404.
    WebDriverWait(driver, 10).until(
        EC.text_to_be_present_in_element(
            (By.TAG_NAME, "body"),
            "Complaint not found or you do not have permission to view it."
        )
    )

    page_text = driver.find_element(By.TAG_NAME, "body").text

    assert (
        "Complaint not found or you do not have permission to view it."
        in page_text
    )
    assert "Complaint Details" not in page_text
