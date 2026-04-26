import pytest
from playwright.sync_api import Page, expect
from test_config import BASE_URL, PATH_PREFIX


class TestLandingPage:
    """Test landing page functionality"""

    def test_landing_page_loads(self, page: Page):
        """Test that the landing page loads correctly"""
        page.goto(f"{BASE_URL}/index.php")
        expect(page).to_have_title("Mobilis | Smarter Vehicle Rental")
        expect(page.locator("text=Book faster.")).to_be_visible()
        expect(page.locator("text=Travel smarter.")).to_be_visible()

    def test_landing_page_navigation_links(self, page: Page):
        """Test that navigation links are present"""
        page.goto(f"{BASE_URL}/index.php")
        expect(page.locator("text=Sign in")).to_be_visible()
        expect(page.locator("text=Register")).to_be_visible()

    def test_landing_page_hero_section(self, page: Page):
        """Test that hero section displays correctly"""
        page.goto(f"{BASE_URL}/index.php")
        expect(page.locator("text=Redefining Mobility")).to_be_visible()
        expect(page.locator("text=Start your journey")).to_be_visible()
        expect(page.locator("text=I already have an account")).to_be_visible()

    def test_landing_page_stats_section(self, page: Page):
        """Test that stats section displays correctly"""
        page.goto(f"{BASE_URL}/index.php")
        expect(page.locator("text=Vehicles managed daily")).to_be_visible()
        expect(page.locator("text=Active rentals in progress")).to_be_visible()
        expect(page.locator("text=On-time booking updates")).to_be_visible()

    def test_landing_page_features_section(self, page: Page):
        """Test that features section displays correctly"""
        page.goto(f"{BASE_URL}/index.php")
        expect(page.locator("text=Instant booking visibility")).to_be_visible()
        expect(page.locator("text=Reliable vehicle options")).to_be_visible()
        expect(page.locator("text=Transparent billing flow")).to_be_visible()

    def test_landing_page_team_section(self, page: Page):
        """Test that team section displays correctly"""
        page.goto(f"{BASE_URL}/index.php")
        expect(page.locator("text=Meet Team Mobilis")).to_be_visible()
        expect(page.locator("text=The people building your smarter rental experience")).to_be_visible()

    def test_landing_page_cta_section(self, page: Page):
        """Test that CTA section displays correctly"""
        page.goto(f"{BASE_URL}/index.php")
        expect(page.locator("text=Ready to book your next vehicle?")).to_be_visible()
        expect(page.locator("text=Create account")).to_be_visible()
        expect(page.locator("text=Sign in")).to_be_visible()

    def test_landing_page_footer(self, page: Page):
        """Test that footer displays correctly"""
        page.goto(f"{BASE_URL}/index.php")
        expect(page.locator("text=© 2026 Mobilis")).to_be_visible()
        expect(page.locator("text=Built for reliable, transparent vehicle rentals")).to_be_visible()

    def test_navigate_to_login_from_landing(self, page: Page):
        """Test navigation to login page from landing page"""
        page.goto(f"{BASE_URL}/index.php")
        page.click("text=Sign in")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/login.php")
        expect(page).to_have_title("Sign In | Mobilis")

    def test_navigate_to_register_from_landing(self, page: Page):
        """Test navigation to register page from landing page"""
        page.goto(f"{BASE_URL}/index.php")
        page.click("text=Register")
        page.wait_for_url(f"{BASE_URL}{PATH_PREFIX}/register.php")
        expect(page).to_have_title("Register | Mobilis")
