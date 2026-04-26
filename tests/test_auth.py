import pytest
from playwright.sync_api import Page, expect
from test_config import BASE_URL, ADMIN_EMAIL, ADMIN_PASSWORD, STAFF_EMAIL, STAFF_PASSWORD, CUSTOMER_EMAIL, CUSTOMER_PASSWORD, PATH_PREFIX


class TestAuthentication:
    """Test authentication functionality including login, logout, and role-based access"""

    def test_login_page_loads(self, page: Page):
        """Test that the login page loads correctly"""
        page.goto(f"{BASE_URL}{PATH_PREFIX}/login.php")
        expect(page).to_have_title("Sign In | Mobilis")
        expect(page.locator("input[name='email']")).to_be_visible()
        expect(page.locator("input[name='password']")).to_be_visible()
        expect(page.locator("button[type='submit']")).to_be_visible()

    def test_admin_login_success(self, page: Page):
        """Test successful admin login"""
        page.goto(f"{BASE_URL}{PATH_PREFIX}/login.php")
        page.fill("input[name='email']", ADMIN_EMAIL)
        page.fill("input[name='password']", ADMIN_PASSWORD)
        page.click("button[type='submit']")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/dashboard.php")
        expect(page.locator("text=Dashboard")).to_be_visible()

    def test_staff_login_success(self, page: Page):
        """Test successful staff login"""
        page.goto(f"{BASE_URL}{PATH_PREFIX}/login.php")
        page.fill("input[name='email']", STAFF_EMAIL)
        page.fill("input[name='password']", STAFF_PASSWORD)
        page.click("button[type='submit']")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/dashboard.php")
        expect(page.locator("text=Dashboard")).to_be_visible()

    def test_customer_login_success(self, page: Page):
        """Test successful customer login"""
        page.goto(f"{BASE_URL}{PATH_PREFIX}/login.php")
        page.fill("input[name='email']", CUSTOMER_EMAIL)
        page.fill("input[name='password']", CUSTOMER_PASSWORD)
        page.click("button[type='submit']")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Customer/dashboard.php")
        expect(page.locator("text=Dashboard")).to_be_visible()

    def test_login_invalid_credentials(self, page: Page):
        """Test login with invalid credentials"""
        page.goto(f"{BASE_URL}{PATH_PREFIX}/login.php")
        page.fill("input[name='email']", "invalid@test.com")
        page.fill("input[name='password']", "wrongpassword")
        page.click("button[type='submit']")
        expect(page).to_have_url(f"{BASE_URL}{PATH_PREFIX}/login.php")
        # Check for error message
        expect(page.locator("text=Invalid credentials")).to_be_visible()

    def test_login_empty_fields(self, page: Page):
        """Test login with empty fields"""
        page.goto(f"{BASE_URL}{PATH_PREFIX}/login.php")
        page.click("button[type='submit']")
        # Should stay on login page
        expect(page).to_have_url(f"{BASE_URL}{PATH_PREFIX}/login.php")

    def test_admin_logout(self, page: Page):
        """Test admin logout functionality"""
        page.goto(f"{BASE_URL}{PATH_PREFIX}/login.php")
        page.fill("input[name='email']", ADMIN_EMAIL)
        page.fill("input[name='password']", ADMIN_PASSWORD)
        page.click("button[type='submit']")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/dashboard.php")
        
        page.click("a[href*='logout']")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/index.php")

    def test_customer_logout(self, page: Page):
        """Test customer logout functionality"""
        page.goto(f"{BASE_URL}{PATH_PREFIX}/login.php")
        page.fill("input[name='email']", CUSTOMER_EMAIL)
        page.fill("input[name='password']", CUSTOMER_PASSWORD)
        page.click("button[type='submit']")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Customer/dashboard.php")
        
        page.click("a[href*='logout']")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/index.php")

    def test_role_based_access_admin_to_customer_pages(self, page: Page):
        """Test that admin cannot access customer-specific pages"""
        page.goto(f"{BASE_URL}{PATH_PREFIX}/login.php")
        page.fill("input[name='email']", ADMIN_EMAIL)
        page.fill("input[name='password']", ADMIN_PASSWORD)
        page.click("button[type='submit']")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/dashboard.php")
        
        # Try to access customer booking page
        page.goto(f"{BASE_URL}{PATH_PREFIX}/Customer/bookings.php")
        # Should be redirected or show error
        expect(page).to_have_url(f"{BASE_URL}{PATH_PREFIX}/Staff/dashboard.php")

    def test_role_based_access_customer_to_staff_pages(self, page: Page):
        """Test that customer cannot access staff pages"""
        page.goto(f"{BASE_URL}{PATH_PREFIX}/login.php")
        page.fill("input[name='email']", CUSTOMER_EMAIL)
        page.fill("input[name='password']", CUSTOMER_PASSWORD)
        page.click("button[type='submit']")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Customer/dashboard.php")
        
        # Try to access staff vehicles page
        page.goto(f"{BASE_URL}{PATH_PREFIX}/Staff/vehicles.php")
        # Should be redirected to customer dashboard or login
        expect(page).to_have_url(f"{BASE_URL}{PATH_PREFIX}/Customer/dashboard.php")
