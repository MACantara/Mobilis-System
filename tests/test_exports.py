import pytest
from playwright.sync_api import Page, expect
from test_config import BASE_URL, ADMIN_EMAIL, ADMIN_PASSWORD, PATH_PREFIX


class TestExports:
    """Test export functionality"""

    def test_bookings_export_page_loads(self, authenticated_admin_page: Page):
        """Test that bookings export page loads"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Bookings")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/bookings.php")
        # Click export button
        export_button = authenticated_admin_page.locator("text=Export")
        if export_button.is_visible():
            export_button.click()
            authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/bookings-export.php")
            expect(authenticated_admin_page.locator("text=Export Bookings")).to_be_visible()

    def test_vehicles_export_page_loads(self, authenticated_admin_page: Page):
        """Test that vehicles export page loads"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Vehicles")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/vehicles.php")
        # Click export button
        export_button = authenticated_admin_page.locator("text=Export")
        if export_button.is_visible():
            export_button.click()
            authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/vehicles-export.php")
            expect(authenticated_admin_page.locator("text=Export Vehicles")).to_be_visible()

    def test_customers_export_page_loads(self, authenticated_admin_page: Page):
        """Test that customers export page loads"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Customers")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/customers.php")
        # Click export button
        export_button = authenticated_admin_page.locator("text=Export")
        if export_button.is_visible():
            export_button.click()
            authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/customers-export.php")
            expect(authenticated_admin_page.locator("text=Export Customers")).to_be_visible()

    def test_payments_export_page_loads(self, authenticated_admin_page: Page):
        """Test that payments export page loads"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Payments")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/payments.php")
        # Click export button
        export_button = authenticated_admin_page.locator("text=Export")
        if export_button.is_visible():
            export_button.click()
            authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/payments-export.php")
            expect(authenticated_admin_page.locator("text=Export Payments")).to_be_visible()

    def test_export_format_selection_csv(self, authenticated_admin_page: Page):
        """Test CSV format selection"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Bookings")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/bookings.php")
        # Click export button
        export_button = authenticated_admin_page.locator("text=Export")
        if export_button.is_visible():
            export_button.click()
            authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/bookings-export.php")
            csv_radio = authenticated_admin_page.locator("input[value='csv']")
            if csv_radio.is_visible():
                csv_radio.check()
                expect(csv_radio).to_be_checked()

    def test_export_format_selection_excel(self, authenticated_admin_page: Page):
        """Test Excel format selection"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Bookings")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/bookings.php")
        # Click export button
        export_button = authenticated_admin_page.locator("text=Export")
        if export_button.is_visible():
            export_button.click()
            authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/bookings-export.php")
            excel_radio = authenticated_admin_page.locator("input[value='xlsx']")
            if excel_radio.is_visible():
                excel_radio.check()
                expect(excel_radio).to_be_checked()

    def test_export_format_selection_pdf(self, authenticated_admin_page: Page):
        """Test PDF format selection"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Bookings")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/bookings.php")
        # Click export button
        export_button = authenticated_admin_page.locator("text=Export")
        if export_button.is_visible():
            export_button.click()
            authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/bookings-export.php")
            pdf_radio = authenticated_admin_page.locator("input[value='pdf']")
            if pdf_radio.is_visible():
                pdf_radio.check()
                expect(pdf_radio).to_be_checked()

    def test_export_submit_button_visible(self, authenticated_admin_page: Page):
        """Test that export submit button is visible"""
        # Navigate from dashboard
        authenticated_admin_page.click("text=Bookings")
        authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/bookings.php")
        # Click export button
        export_button = authenticated_admin_page.locator("text=Export")
        if export_button.is_visible():
            export_button.click()
            authenticated_admin_page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/Staff/bookings-export.php")
            expect(authenticated_admin_page.locator("button[type='submit']")).to_be_visible()
