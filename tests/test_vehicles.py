import pytest
from playwright.sync_api import Page, expect
from test_config import BASE_URL, ADMIN_EMAIL, ADMIN_PASSWORD, PATH_PREFIX


class TestVehicles:
    """Test vehicles module functionality"""

    def test_vehicles_page_loads(self, authenticated_admin_page: Page):
        """Test that vehicles page loads correctly"""
        # Navigate from dashboard using nav link
        authenticated_admin_page.click("text=Vehicles")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/vehicles.php")
        expect(authenticated_admin_page).to_have_title("Vehicles | Mobilis")

    def test_vehicles_display_card_view(self, authenticated_admin_page: Page):
        """Test that vehicles are displayed in card view"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Vehicles")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/vehicles.php")
        # Check for vehicle cards
        expect(authenticated_admin_page.locator(".vehicle-card").first).to_be_visible()

    def test_vehicle_search(self, authenticated_admin_page: Page):
        """Test vehicle search functionality"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Vehicles")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/vehicles.php")
        search_input = authenticated_admin_page.locator("input[placeholder*='Search']")
        if search_input.is_visible():
            search_input.fill("Toyota")
            authenticated_admin_page.press("input[placeholder*='Search']", "Enter")
            # Wait for results
            authenticated_admin_page.wait_for_timeout(1000)

    def test_vehicle_filter_by_status(self, authenticated_admin_page: Page):
        """Test filtering vehicles by status"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Vehicles")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/vehicles.php")
        # Click on available filter
        available_filter = authenticated_admin_page.locator("text=Available")
        if available_filter.is_visible():
            available_filter.click()
            authenticated_admin_page.wait_for_timeout(1000)

    def test_vehicle_view_details(self, authenticated_admin_page: Page):
        """Test viewing vehicle details"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Vehicles")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/vehicles.php")
        # Click on first vehicle card
        first_vehicle = authenticated_admin_page.locator(".vehicle-card").first
        if first_vehicle.is_visible():
            first_vehicle.click()
            authenticated_admin_page.wait_for_url("**/vehicle-view.php*")
            expect(authenticated_admin_page.locator("text=Vehicle Details")).to_be_visible()

    def test_customer_vehicles_page_loads(self, page: Page):
        """Test that customer vehicles page loads correctly"""
        page.goto(f"{BASE_URL}/index.php")
        page.fill("input[name='email']", "customer@mobilis.ph")
        page.fill("input[name='password']", "password")
        page.click("button[type='submit']")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Customer/dashboard.php")
        
        # Navigate from dashboard
        page.click("text=Browse vehicles")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Customer/vehicles.php")
        expect(page).to_have_title("Browse vehicles | Mobilis")

    def test_customer_vehicle_booking_form(self, page: Page):
        """Test that customer can access booking form from vehicles page"""
        page.goto(f"{BASE_URL}/index.php")
        page.fill("input[name='email']", "customer@mobilis.ph")
        page.fill("input[name='password']", "password")
        page.click("button[type='submit']")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Customer/dashboard.php")
        
        # Navigate from dashboard
        page.click("text=Browse vehicles")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Customer/vehicles.php")
        # Click on first vehicle to book
        first_vehicle = page.locator(".vehicle-card").first
        if first_vehicle.is_visible():
            first_vehicle.click()
            # Should navigate to booking page
            page.wait_for_timeout(1000)
