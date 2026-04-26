from playwright.sync_api import Page, BrowserContext, Browser
import pytest

# Test configuration
BASE_URL = "http://localhost:8000"
BASE_URL_XAMPP = "http://localhost/Mobilis-System"

# Determine which base URL to use based on environment
import os
USE_XAMPP = True  # Hardcoded for XAMPP setup
BASE_URL = BASE_URL_XAMPP if USE_XAMPP else BASE_URL

# XAMPP router handles /public internally, so paths don't include it
# PHP dev server serves from public directly, so paths include /public
PATH_PREFIX = "" if USE_XAMPP else "/public"

# Test credentials
ADMIN_EMAIL = "admin@mobilis.ph"
ADMIN_PASSWORD = "password"
STAFF_EMAIL = "staff@mobilis.ph"
STAFF_PASSWORD = "password"
CUSTOMER_EMAIL = "customer@mobilis.ph"
CUSTOMER_PASSWORD = "password"

@pytest.fixture(scope="session")
def browser_context_args(browser_context_args):
    return {
        **browser_context_args,
        "viewport": {"width": 1280, "height": 720},
        "ignore_https_errors": True,
    }

@pytest.fixture(scope="function")
def page(browser: Browser):
    context = browser.new_context()
    page = context.new_page()
    yield page
    context.close()

@pytest.fixture(scope="function")
def authenticated_admin_page(page: Page):
    """Fixture that provides a page authenticated as admin"""
    page.goto(f"{BASE_URL}{PATH_PREFIX}/login.php")
    page.fill("input[name='email']", ADMIN_EMAIL)
    page.fill("input[name='password']", ADMIN_PASSWORD)
    page.click("button[type='submit']")
    page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/dashboard.php")
    yield page
    # Logout after test
    page.click("a[href*='logout']")
    page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/index.php")

@pytest.fixture(scope="function")
def authenticated_staff_page(page: Page):
    """Fixture that provides a page authenticated as staff"""
    page.goto(f"{BASE_URL}{PATH_PREFIX}/login.php")
    page.fill("input[name='email']", STAFF_EMAIL)
    page.fill("input[name='password']", STAFF_PASSWORD)
    page.click("button[type='submit']")
    page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/dashboard.php")
    yield page
    # Logout after test
    page.click("a[href*='logout']")
    page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/index.php")

@pytest.fixture(scope="function")
def authenticated_customer_page(page: Page):
    """Fixture that provides a page authenticated as customer"""
    page.goto(f"{BASE_URL}{PATH_PREFIX}/login.php")
    page.fill("input[name='email']", CUSTOMER_EMAIL)
    page.fill("input[name='password']", CUSTOMER_PASSWORD)
    page.click("button[type='submit']")
    page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Customer/dashboard.php")
    yield page
    # Logout after test
    page.click("a[href*='logout']")
    page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/index.php")
