import pytest
from playwright.sync_api import Page

# Import configuration
from test_config import (
    BASE_URL,
    ADMIN_EMAIL,
    ADMIN_PASSWORD,
    STAFF_EMAIL,
    STAFF_PASSWORD,
    CUSTOMER_EMAIL,
    CUSTOMER_PASSWORD,
    PATH_PREFIX
)

@pytest.fixture(scope="session")
def browser_context_args(browser_context_args):
    return {
        **browser_context_args,
        "viewport": {"width": 1280, "height": 720},
        "ignore_https_errors": True,
    }

@pytest.fixture(scope="function")
def page(browser):
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
