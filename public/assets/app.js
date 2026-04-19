const insightButtons = document.querySelectorAll('[data-refresh-insights]');
const insightsOutput = document.querySelector('#insights-output');

async function refreshInsights() {
  if (!insightsOutput) return;

  insightsOutput.textContent = 'Running PHP + Python analysis...';

  try {
    const response = await fetch('/api/dashboard.php', {
      method: 'GET',
      headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
      throw new Error(`Failed with status ${response.status}`);
    }

    const data = await response.json();
    insightsOutput.textContent = JSON.stringify(data.insights, null, 2);
  } catch (error) {
    insightsOutput.textContent = `Could not load analytics: ${error.message}`;
  }
}

insightButtons.forEach((button) => {
  button.addEventListener('click', refreshInsights);
});

if (insightsOutput && insightButtons.length > 0 && insightsOutput.textContent.includes('Loading')) {
  refreshInsights();
}

function formatCurrency(value) {
  const number = Number(value || 0);
  return 'P' + number.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDateLabel(dateString) {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) return dateString;
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function buildRecentBookingItem(booking) {
  const li = document.createElement('li');

  const label = document.createElement('span');
  label.textContent = booking.label || 'N/A';

  const status = document.createElement('span');
  const statusValue = String(booking.status || 'pending').toLowerCase();
  status.className = 'pill ' + statusValue;
  status.textContent = statusValue.charAt(0).toUpperCase() + statusValue.slice(1);

  li.appendChild(label);
  li.appendChild(status);
  return li;
}

function setupCustomerProfilePanel() {
  const dataNode = document.getElementById('customer-profile-data');
  if (!dataNode) return;

  let customers = {};
  try {
    customers = JSON.parse(dataNode.dataset.customers || '{}');
  } catch (error) {
    return;
  }

  const rows = document.querySelectorAll('.customer-row');
  const viewButtons = document.querySelectorAll('.customer-view-btn');

  const profileAvatar = document.getElementById('profile-avatar');
  const profileName = document.getElementById('profile-name');
  const profileTier = document.getElementById('profile-tier');
  const profileEmail = document.getElementById('profile-email');
  const profilePhone = document.getElementById('profile-phone');
  const profileLicense = document.getElementById('profile-license');
  const profileLicenseExp = document.getElementById('profile-license-exp');
  const profileAddress = document.getElementById('profile-address');
  const profileBookings = document.getElementById('profile-bookings');
  const profileSpent = document.getElementById('profile-spent');
  const profileAvgRental = document.getElementById('profile-avg-rental');
  const profileNoShows = document.getElementById('profile-no-shows');
  const recentBookingsList = document.getElementById('profile-recent-bookings');
  const profileMessageBtn = document.getElementById('profile-message-btn');
  const profileEditBtn = document.getElementById('profile-edit-btn');
  const profileBookingBtn = document.getElementById('profile-booking-btn');
  const customerSearchInput = document.querySelector('[data-customer-search]');

  const requiredNodes = [
    profileAvatar,
    profileName,
    profileTier,
    profileEmail,
    profilePhone,
    profileLicense,
    profileLicenseExp,
    profileAddress,
    profileBookings,
    profileSpent,
    profileAvgRental,
    profileNoShows,
    recentBookingsList,
    profileMessageBtn,
    profileEditBtn,
    profileBookingBtn,
  ];

  if (requiredNodes.some((node) => !node)) return;

  function updateActiveRow(customerId) {
    rows.forEach((row) => {
      row.classList.toggle('is-active', row.dataset.customerId === String(customerId));
    });
  }

  function updateProfile(customerId) {
    const customer = customers[String(customerId)];
    if (!customer) return;

    const initials = String(customer.name || '')
      .split(/\s+/)
      .filter(Boolean)
      .map((part) => part[0])
      .join('')
      .slice(0, 2)
      .toUpperCase();

    profileAvatar.textContent = initials || 'CU';
    profileName.textContent = customer.name || 'Unknown customer';

    const tierText = (customer.tier || 'Regular') + ' Customer';
    const joinedDate = customer.created_at ? new Date(customer.created_at) : null;
    const joinedText = joinedDate && !Number.isNaN(joinedDate.getTime())
      ? joinedDate.toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
      : 'N/A';
    profileTier.textContent = tierText + ' · since ' + joinedText;

    profileEmail.textContent = customer.email || 'N/A';
    profilePhone.textContent = customer.phone || 'N/A';
    profileLicense.textContent = customer.license_number || 'N/A';
    profileLicenseExp.textContent = formatDateLabel(customer.license_expiry || '');
    profileAddress.textContent = customer.address || 'N/A';

    profileBookings.textContent = Number(customer.bookings || 0).toLocaleString('en-PH');
    profileSpent.textContent = formatCurrency(customer.spent || 0);
    profileAvgRental.textContent = Number(customer.avg_rental_days || 0).toFixed(1) + ' days';
    profileNoShows.textContent = Number(customer.no_shows || 0).toLocaleString('en-PH');

    const email = String(customer.email || '').trim();
    const name = String(customer.name || '').trim();
    profileMessageBtn.href = 'mailto:' + email + '?subject=' + encodeURIComponent('Mobilis customer support: ' + name);
    profileEditBtn.href = '/Staff/customer-edit.php?id=' + encodeURIComponent(String(customerId));
    profileBookingBtn.href = '/Staff/booking-create.php?customer_id=' + encodeURIComponent(String(customerId));

    recentBookingsList.innerHTML = '';
    const recent = Array.isArray(customer.recent_bookings) ? customer.recent_bookings : [];

    if (recent.length === 0) {
      const empty = document.createElement('li');
      empty.innerHTML = '<span>No recent bookings</span><span class="pill pending">Pending</span>';
      recentBookingsList.appendChild(empty);
    } else {
      recent.forEach((booking) => {
        recentBookingsList.appendChild(buildRecentBookingItem(booking));
      });
    }

    updateActiveRow(customerId);
  }

  viewButtons.forEach((button) => {
    button.addEventListener('click', () => {
      updateProfile(button.dataset.customerId);
    });
  });

  rows.forEach((row) => {
    row.addEventListener('click', (event) => {
      if (event.target.closest('.customer-view-btn')) return;
      updateProfile(row.dataset.customerId);
    });
  });

  if (customerSearchInput) {
    customerSearchInput.addEventListener('input', () => {
      const term = customerSearchInput.value.trim().toLowerCase();
      rows.forEach((row) => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
      });
    });
  }
}

setupCustomerProfilePanel();
