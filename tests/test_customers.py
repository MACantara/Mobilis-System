import pytest
from playwright.sync_api import Page, expect
from test_config import BASE_URL, ADMIN_EMAIL, ADMIN_PASSWORD, PATH_PREFIX


class TestCustomers:
    """Test customers module functionality"""

    def test_customers_page_loads(self, authenticated_admin_page: Page):
        """Test that customers page loads correctly"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Customers")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/customers.php")
        expect(authenticated_admin_page).to_have_title("Customers | Mobilis")

    def test_customers_display_list(self, authenticated_admin_page: Page):
        """Test that customers are displayed in list view"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Customers")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/customers.php")
        # Check for customer rows
        expect(authenticated_admin_page.locator("table")).to_be_visible()

    def test_customer_search(self, authenticated_admin_page: Page):
        """Test customer search functionality"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Customers")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/customers.php")
        search_input = authenticated_admin_page.locator("input[placeholder*='Search']")
        if search_input.is_visible():
            search_input.fill("Maria")
            authenticated_admin_page.press("input[placeholder*='Search']", "Enter")
            authenticated_admin_page.wait_for_timeout(1000)

    def test_customer_view_details(self, authenticated_admin_page: Page):
        """Test viewing customer details"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Customers")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/customers.php")
        # Click on first customer
        first_customer = authenticated_admin_page.locator("table tbody tr").first
        if first_customer.is_visible():
            first_customer.click()
            authenticated_admin_page.wait_for_timeout(1000)

    def test_customer_export_modal(self, authenticated_admin_page: Page):
        """Test customer export modal"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Customers")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/customers.php")
        export_button = authenticated_admin_page.locator("text=Export")
        if export_button.is_visible():
            export_button.click()
            expect(authenticated_admin_page.locator("text=Export Customers")).to_be_visible()
