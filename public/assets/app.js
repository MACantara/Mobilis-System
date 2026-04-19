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
    if (profileEditBtn) {
      profileEditBtn.href = '/Staff/customer-edit.php?id=' + encodeURIComponent(String(customerId));
    }
    if (profileBookingBtn) {
      profileBookingBtn.href = '/Staff/booking-create.php?user_id=' + encodeURIComponent(String(customerId));
    }

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

function hasSeriesData(values) {
  if (!Array.isArray(values) || values.length === 0) return false;
  return values.some((value) => Number(value || 0) > 0);
}

function showChartFallback(canvas, message) {
  if (!canvas) return;

  const card = canvas.closest('.reports-chart-card');
  canvas.remove();

  if (!card) return;

  const fallback = document.createElement('p');
  fallback.className = 'muted reports-chart-empty';
  fallback.textContent = message;
  card.appendChild(fallback);
}

function initializeReportsCharts() {
  const dataNode = document.getElementById('reports-chart-data');
  if (!dataNode) return;

  if (typeof window.Chart !== 'function') {
    document.querySelectorAll('.reports-chart-card canvas').forEach((canvas) => {
      showChartFallback(canvas, 'Charts are unavailable right now.');
    });
    return;
  }

  let payload = {};
  try {
    payload = JSON.parse(dataNode.textContent || '{}');
  } catch (error) {
    payload = {};
  }

  const palette = {
    green: '#16986d',
    blue: '#2878d8',
    amber: '#d78a24',
    violet: '#7b5acc',
    red: '#cc5a5a',
    slate: '#4d5d63',
    teal: '#1f9f95',
  };

  const numberFormat = new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  });

  const moneyFormat = new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

  const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        labels: {
          boxWidth: 10,
          boxHeight: 10,
          usePointStyle: true,
          font: { family: 'Manrope, Segoe UI, sans-serif' },
        },
      },
    },
    scales: {
      x: {
        ticks: { color: '#5f6f71', maxRotation: 0, autoSkip: true },
        grid: { color: 'rgba(209, 222, 216, 0.55)' },
      },
      y: {
        ticks: { color: '#5f6f71' },
        grid: { color: 'rgba(209, 222, 216, 0.55)' },
      },
    },
  };

  const revenueCanvas = document.getElementById('reports-revenue-chart');
  const revenueLabels = payload?.revenueTrend?.labels || [];
  const revenueValues = payload?.revenueTrend?.values || [];

  if (revenueCanvas && hasSeriesData(revenueValues)) {
    new window.Chart(revenueCanvas.getContext('2d'), {
      type: 'line',
      data: {
        labels: revenueLabels,
        datasets: [{
          label: 'Revenue',
          data: revenueValues,
          borderColor: palette.green,
          backgroundColor: 'rgba(22, 152, 109, 0.16)',
          fill: true,
          pointRadius: 2.4,
          pointHoverRadius: 4,
          tension: 0.34,
        }],
      },
      options: {
        ...chartDefaults,
        plugins: {
          ...chartDefaults.plugins,
          tooltip: {
            callbacks: {
              label(context) {
                return ' P' + moneyFormat.format(context.parsed.y || 0);
              },
            },
          },
        },
        scales: {
          ...chartDefaults.scales,
          y: {
            ...chartDefaults.scales.y,
            ticks: {
              color: '#5f6f71',
              callback(value) {
                return 'P' + numberFormat.format(Number(value || 0));
              },
            },
          },
        },
      },
    });
  } else {
    showChartFallback(revenueCanvas, 'No revenue trend data available.');
  }

  const bookingsCanvas = document.getElementById('reports-bookings-chart');
  const bookingLabels = payload?.bookingTrend?.labels || [];
  const bookingCounts = payload?.bookingTrend?.counts || [];

  if (bookingsCanvas && hasSeriesData(bookingCounts)) {
    new window.Chart(bookingsCanvas.getContext('2d'), {
      type: 'bar',
      data: {
        labels: bookingLabels,
        datasets: [{
          label: 'Bookings',
          data: bookingCounts,
          borderRadius: 8,
          borderSkipped: false,
          backgroundColor: 'rgba(40, 120, 216, 0.8)',
        }],
      },
      options: {
        ...chartDefaults,
        scales: {
          ...chartDefaults.scales,
          y: {
            ...chartDefaults.scales.y,
            ticks: {
              stepSize: 1,
              color: '#5f6f71',
              callback(value) {
                return numberFormat.format(Number(value || 0));
              },
            },
          },
        },
      },
    });
  } else {
    showChartFallback(bookingsCanvas, 'No booking trend data available.');
  }

  const paymentCanvas = document.getElementById('reports-payment-chart');
  const paymentLabels = payload?.paymentStatus?.labels || [];
  const paymentCounts = payload?.paymentStatus?.counts || [];

  if (paymentCanvas && hasSeriesData(paymentCounts)) {
    new window.Chart(paymentCanvas.getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: paymentLabels,
        datasets: [{
          data: paymentCounts,
          backgroundColor: [palette.green, palette.amber, palette.blue, palette.slate],
          borderWidth: 0,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              boxWidth: 10,
              boxHeight: 10,
              usePointStyle: true,
              font: { family: 'Manrope, Segoe UI, sans-serif' },
            },
          },
        },
      },
    });
  } else {
    showChartFallback(paymentCanvas, 'No payment status data available.');
  }

  const bookingStatusCanvas = document.getElementById('reports-booking-status-chart');
  const bookingStatusLabels = payload?.bookingStatus?.labels || [];
  const bookingStatusCounts = payload?.bookingStatus?.counts || [];

  if (bookingStatusCanvas && hasSeriesData(bookingStatusCounts)) {
    new window.Chart(bookingStatusCanvas.getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: bookingStatusLabels,
        datasets: [{
          data: bookingStatusCounts,
          backgroundColor: [palette.blue, palette.green, palette.violet, palette.red, palette.slate],
          borderWidth: 0,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              boxWidth: 10,
              boxHeight: 10,
              usePointStyle: true,
              font: { family: 'Manrope, Segoe UI, sans-serif' },
            },
          },
        },
      },
    });
  } else {
    showChartFallback(bookingStatusCanvas, 'No booking status data available.');
  }

  const customersCanvas = document.getElementById('reports-customers-chart');
  const customerLabels = payload?.topCustomers?.labels || [];
  const customerRevenue = payload?.topCustomers?.values || [];

  if (customersCanvas && hasSeriesData(customerRevenue)) {
    new window.Chart(customersCanvas.getContext('2d'), {
      type: 'bar',
      data: {
        labels: customerLabels,
        datasets: [{
          label: 'Revenue',
          data: customerRevenue,
          borderRadius: 8,
          borderSkipped: false,
          backgroundColor: 'rgba(22, 152, 109, 0.74)',
        }],
      },
      options: {
        ...chartDefaults,
        plugins: {
          ...chartDefaults.plugins,
          legend: {
            display: false,
          },
        },
        scales: {
          ...chartDefaults.scales,
          y: {
            ...chartDefaults.scales.y,
            ticks: {
              color: '#5f6f71',
              callback(value) {
                return 'P' + numberFormat.format(Number(value || 0));
              },
            },
          },
        },
      },
    });
  } else {
    showChartFallback(customersCanvas, 'No customer revenue data available.');
  }

  const vehicleTypeCanvas = document.getElementById('reports-vehicle-type-chart');
  const vehicleTypeLabels = payload?.vehicleTypeRevenue?.labels || [];
  const vehicleTypeValues = payload?.vehicleTypeRevenue?.values || [];

  if (vehicleTypeCanvas && hasSeriesData(vehicleTypeValues)) {
    new window.Chart(vehicleTypeCanvas.getContext('2d'), {
      type: 'bar',
      data: {
        labels: vehicleTypeLabels,
        datasets: [{
          label: 'Revenue',
          data: vehicleTypeValues,
          borderRadius: 8,
          borderSkipped: false,
          backgroundColor: 'rgba(31, 159, 149, 0.78)',
        }],
      },
      options: {
        ...chartDefaults,
        indexAxis: 'y',
        plugins: {
          ...chartDefaults.plugins,
          legend: {
            display: false,
          },
        },
        scales: {
          x: {
            ...chartDefaults.scales.x,
            ticks: {
              color: '#5f6f71',
              callback(value) {
                return 'P' + numberFormat.format(Number(value || 0));
              },
            },
          },
          y: {
            ...chartDefaults.scales.y,
          },
        },
      },
    });
  } else {
    showChartFallback(vehicleTypeCanvas, 'No vehicle type revenue data available.');
  }
}

initializeReportsCharts();

const leafletAssets = {
  ready: false,
  pending: null,
};

function loadStyleOnce(href, id) {
  if (document.getElementById(id)) return;
  const link = document.createElement('link');
  link.id = id;
  link.rel = 'stylesheet';
  link.href = href;
  document.head.appendChild(link);
}

function loadScriptOnce(src, id) {
  const existing = document.getElementById(id);
  if (existing) {
    if (window.L) return Promise.resolve();
    return new Promise((resolve, reject) => {
      existing.addEventListener('load', resolve, { once: true });
      existing.addEventListener('error', () => reject(new Error('Failed to load map script')), { once: true });
    });
  }

  return new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.id = id;
    script.src = src;
    script.async = true;
    script.onload = resolve;
    script.onerror = () => reject(new Error('Failed to load map script'));
    document.body.appendChild(script);
  });
}

async function ensureLeaflet() {
  if (window.L) {
    leafletAssets.ready = true;
    return;
  }

  if (leafletAssets.pending) {
    await leafletAssets.pending;
    return;
  }

  leafletAssets.pending = (async () => {
    loadStyleOnce('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', 'leaflet-css');
    await loadScriptOnce('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', 'leaflet-js');
    leafletAssets.ready = true;
  })();

  await leafletAssets.pending;
}

function trackingStatusClass(status) {
  const value = String(status || 'available').trim().toLowerCase();
  return value.replace(/\s+/g, '.');
}

function trackingStatusLabel(status) {
  const value = String(status || 'available').trim().toLowerCase();
  if (!value) return 'Unknown';
  return value.charAt(0).toUpperCase() + value.slice(1);
}

function trackingMarkerColor(status) {
  const value = String(status || 'available').toLowerCase();
  if (value === 'rented' || value === 'confirmed' || value === 'active') return '#1e73be';
  if (value === 'maintenance') return '#d48723';
  if (value === 'cancelled') return '#bf3d3d';
  return '#17966e';
}

function renderTrackingList(listEl, vehicles, listLimit) {
  if (!listEl) return;

  listEl.innerHTML = '';

  if (!Array.isArray(vehicles) || vehicles.length === 0) {
    const empty = document.createElement('li');
    empty.innerHTML = '<div><strong>No tracked vehicles</strong><p class="muted">No active GPS locations are available.</p></div><span class="pill pending">Idle</span>';
    listEl.appendChild(empty);
    return;
  }

  const limit = Number.isFinite(Number(listLimit)) ? Math.max(1, Number(listLimit)) : 10;
  const visibleVehicles = vehicles.slice(0, limit);

  visibleVehicles.forEach((vehicle) => {
    const li = document.createElement('li');

    const details = document.createElement('div');

    const name = document.createElement('strong');
    name.textContent = vehicle.name || 'Unknown vehicle';

    const plate = document.createElement('p');
    plate.textContent = vehicle.plate || 'No plate';

    const coords = document.createElement('p');
    coords.className = 'muted tracking-coordinate';
    const lat = Number(vehicle.lat || 0);
    const lng = Number(vehicle.lng || 0);
    coords.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;

    details.appendChild(name);
    details.appendChild(plate);
    details.appendChild(coords);

    const badge = document.createElement('span');
    const statusClass = trackingStatusClass(vehicle.status);
    badge.className = 'pill ' + statusClass;
    badge.textContent = trackingStatusLabel(vehicle.status);

    li.appendChild(details);
    li.appendChild(badge);
    listEl.appendChild(li);
  });
}

function updateTrackingMarkers(map, markerStore, vehicles) {
  const seen = new Set();

  vehicles.forEach((vehicle) => {
    const id = String(vehicle.vehicle_id || vehicle.plate || vehicle.name || 'unknown');
    seen.add(id);

    const lat = Number(vehicle.lat);
    const lng = Number(vehicle.lng);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

    const color = trackingMarkerColor(vehicle.status);
    const popup = `<strong>${vehicle.name || 'Unknown vehicle'}</strong><br>${vehicle.plate || ''}<br>${trackingStatusLabel(vehicle.status)}<br>${lat.toFixed(6)}, ${lng.toFixed(6)}`;

    if (markerStore.has(id)) {
      const marker = markerStore.get(id);
      marker.setLatLng([lat, lng]);
      marker.setStyle({ color, fillColor: color });
      marker.bindPopup(popup);
      return;
    }

    const marker = window.L.circleMarker([lat, lng], {
      radius: 8,
      color,
      fillColor: color,
      fillOpacity: 0.86,
      weight: 2,
    }).addTo(map);

    marker.bindPopup(popup);
    markerStore.set(id, marker);
  });

  markerStore.forEach((marker, id) => {
    if (seen.has(id)) return;
    map.removeLayer(marker);
    markerStore.delete(id);
  });
}

async function setupLiveTrackingMap(mapEl) {
  await ensureLeaflet();

  const endpoint = mapEl.dataset.trackingEndpoint || '/api/tracking.php';
  const listTargetId = mapEl.dataset.trackingListTarget || '';
  const statusTargetId = mapEl.dataset.trackingStatusTarget || '';
  const listLimit = Number(mapEl.dataset.trackingListLimit || 10);
  const vehicleIdsRaw = mapEl.dataset.trackingVehicleIds || '';
  const allowedVehicleIds = new Set(
    vehicleIdsRaw
      .split(',')
      .map((value) => Number(value.trim()))
      .filter((value) => Number.isFinite(value) && value > 0)
  );
  const listEl = listTargetId ? document.getElementById(listTargetId) : null;
  const statusEl = statusTargetId ? document.getElementById(statusTargetId) : null;

  const map = window.L.map(mapEl, { zoomControl: true });
  window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map);

  map.setView([14.5995, 121.0223], 11);

  const markerStore = new Map();
  let hasAutoFitted = false;
  let pollMs = 5000;
  let timerId = 0;

  async function refreshTracking() {
    try {
      const response = await fetch(endpoint, {
        method: 'GET',
        headers: { Accept: 'application/json' },
        cache: 'no-store',
      });

      if (!response.ok) {
        throw new Error(`Tracking API failed (${response.status})`);
      }

      const payload = await response.json();
      const allVehicles = Array.isArray(payload.vehicles) ? payload.vehicles : [];
      const vehicles = allowedVehicleIds.size > 0
        ? allVehicles.filter((vehicle) => allowedVehicleIds.has(Number(vehicle.vehicle_id || 0)))
        : allVehicles;
      const stepSeconds = Number(payload.step_seconds || 5);

      if (Number.isFinite(stepSeconds) && stepSeconds > 0) {
        pollMs = Math.max(2000, Math.floor(stepSeconds * 1000));
      }

      updateTrackingMarkers(map, markerStore, vehicles);
      renderTrackingList(listEl, vehicles, listLimit);

      if (!hasAutoFitted && vehicles.length > 0) {
        const bounds = window.L.latLngBounds(vehicles.map((vehicle) => [Number(vehicle.lat), Number(vehicle.lng)]));
        map.fitBounds(bounds.pad(0.25), { maxZoom: 13 });
        hasAutoFitted = true;
      }

      if (statusEl) {
        const count = vehicles.length;
        statusEl.textContent = count > 0
          ? `${count} vehicle${count === 1 ? '' : 's'} updated at ${new Date().toLocaleTimeString()}`
          : 'No tracked vehicles are currently available.';
      }
    } catch (error) {
      if (statusEl) {
        statusEl.textContent = `Tracking temporarily unavailable: ${error.message}`;
      }
    }

    timerId = window.setTimeout(refreshTracking, pollMs);
  }

  const refreshButtons = document.querySelectorAll(`[data-tracking-refresh="${mapEl.id}"]`);
  refreshButtons.forEach((button) => {
    button.addEventListener('click', () => {
      if (timerId) {
        window.clearTimeout(timerId);
      }
      refreshTracking();
    });
  });

  window.setTimeout(() => map.invalidateSize(), 120);
  refreshTracking();
}

async function initializeLiveTracking() {
  const maps = document.querySelectorAll('[data-tracking-map]');
  if (maps.length === 0) return;

  for (const mapEl of maps) {
    setupLiveTrackingMap(mapEl);
  }
}

initializeLiveTracking();
