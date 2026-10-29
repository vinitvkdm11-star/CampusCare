
import os

import pytest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC


BASE_URL = "http://localhost/CampusCare"


@pytest.fixture
def driver():
    """Start Chrome and close it after the test."""
    options = webdriver.ChromeOptions()
    options.add_argument("--start-maximized")

    browser = webdriver.Chrome(options=options)
    browser.implicitly_wait(2)

    yield browser

    browser.quit()


def test_student_login_success(driver):
    """Verify that a student can log in with valid credentials."""

    email = os.getenv("CAMPUSCARE_STUDENT_EMAIL")
    password = os.getenv("CAMPUSCARE_STUDENT_PASSWORD")

    if not email or not password:
        pytest.skip(
            "Set CAMPUSCARE_STUDENT_EMAIL and "
            "CAMPUSCARE_STUDENT_PASSWORD before running this test."
        )

    driver.get(f"{BASE_URL}/login.php")

    # Locate the login form fields.
    email_field = WebDriverWait(driver, 10).until(
        EC.visibility_of_element_located((By.NAME, "email"))
    )
    password_field = driver.find_element(By.NAME, "password")

    email_field.clear()
    email_field.send_keys(email)

    password_field.clear()
    password_field.send_keys(password)

    # Submit the login form.
    password_field.submit()

    # Confirm that the student dashboard opens.
    WebDriverWait(driver, 10).until(
        EC.url_contains("/student/dashboard.php")
    )

    assert "/student/dashboard.php" in driver.current_url



def test_student_login_invalid_password(driver):
    email = os.getenv("CAMPUSCARE_STUDENT_EMAIL")

    if not email:
        pytest.skip("Set CAMPUSCARE_STUDENT_EMAIL before running this test.")

    driver.get(f"{BASE_URL}/login.php")

    email_field = WebDriverWait(driver, 10).until(
        EC.visibility_of_element_located((By.NAME, "email"))
    )
    password_field = driver.find_element(By.NAME, "password")

    email_field.clear()
    email_field.send_keys(email)

    password_field.clear()
    password_field.send_keys("DefinitelyWrongPassword123!")

    password_field.submit()

    # The user should remain on the login page.
    WebDriverWait(driver, 10).until(
        EC.url_contains("/login.php")
    )

    assert "/login.php" in driver.current_url
    assert "/student/dashboard.php" not in driver.current_url
