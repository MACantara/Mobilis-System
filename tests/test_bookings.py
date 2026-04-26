import pytest
from playwright.sync_api import Page, expect
from test_config import BASE_URL, ADMIN_EMAIL, ADMIN_PASSWORD, CUSTOMER_EMAIL, CUSTOMER_PASSWORD, PATH_PREFIX


class TestBookings:
    """Test bookings module functionality"""

    def test_staff_bookings_page_loads(self, authenticated_admin_page: Page):
        """Test that staff bookings page loads correctly"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Bookings")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/bookings.php")
        expect(authenticated_admin_page).to_have_title("Bookings | Mobilis")

    def test_staff_bookings_display_list(self, authenticated_admin_page: Page):
        """Test that bookings are displayed in list view"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Bookings")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/bookings.php")
        # Check for booking rows
        expect(authenticated_admin_page.locator("table")).to_be_visible()

    def test_staff_booking_filter_by_status(self, authenticated_admin_page: Page):
        """Test filtering bookings by status"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Bookings")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/bookings.php")
        # Click on active filter
        active_filter = authenticated_admin_page.locator("text=Active")
        if active_filter.is_visible():
            active_filter.click()
            authenticated_admin_page.wait_for_timeout(1000)

    def test_staff_booking_view_details(self, authenticated_admin_page: Page):
        """Test viewing booking details"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Bookings")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/bookings.php")
        # Click on first booking
        first_booking = authenticated_admin_page.locator("table tbody tr").first
        if first_booking.is_visible():
            first_booking.click()
            authenticated_admin_page.wait_for_timeout(1000)

    def test_customer_bookings_page_loads(self, page: Page):
        """Test that customer bookings page loads correctly"""
        page.goto(f"{BASE_URL}/index.php")
        page.fill("input[name='email']", CUSTOMER_EMAIL)
        page.fill("input[name='password']", CUSTOMER_PASSWORD)
        page.click("button[type='submit']")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Customer/dashboard.php")
        
        # Navigate from dashboard
        page.click("text=My bookings")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Customer/bookings.php")
        expect(page).to_have_title("My bookings | Mobilis")

    def test_customer_booking_create_page_loads(self, page: Page):
        """Test that customer booking creation page loads"""
        page.goto(f"{BASE_URL}/index.php")
        page.fill("input[name='email']", CUSTOMER_EMAIL)
        page.fill("input[name='password']", CUSTOMER_PASSWORD)
        page.click("button[type='submit']")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Customer/dashboard.php")
        
        # Navigate from dashboard
        page.click("text=Browse vehicles")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Customer/vehicles.php")
        # Click on first vehicle to book
        first_vehicle = page.locator(".vehicle-card").first
        if first_vehicle.is_visible():
            first_vehicle.click()
            page.wait_for_timeout(1000)
            expect(page.locator("text=Create Booking")).to_be_visible()
            expect(page.locator("select[name='vehicle_id']")).to_be_visible()
            expect(page.locator("input[name='pickup_date']")).to_be_visible()
            expect(page.locator("input[name='return_date']")).to_be_visible()

    def test_customer_booking_submit(self, page: Page):
        """Test customer can submit a booking"""
        page.goto(f"{BASE_URL}/index.php")
        page.fill("input[name='email']", CUSTOMER_EMAIL)
        page.fill("input[name='password']", CUSTOMER_PASSWORD)
        page.click("button[type='submit']")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Customer/dashboard.php")
        
        # Navigate from dashboard
        page.click("text=Browse vehicles")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Customer/vehicles.php")
        # Click on first vehicle to book
        first_vehicle = page.locator(".vehicle-card").first
        if first_vehicle.is_visible():
            first_vehicle.click()
            page.wait_for_timeout(1000)
            
            # Select vehicle
            vehicle_select = page.locator("select[name='vehicle_id']")
            if vehicle_select.is_visible():
                vehicle_select.select_option(index=1)
                
                # Set dates
                page.fill("input[name='pickup_date']", "2026-05-01")
                page.fill("input[name='return_date']", "2026-05-03")
                
                # Submit
                page.click("button[type='submit']")
                page.wait_for_timeout(2000)
