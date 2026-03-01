<template>
  <div class="bg-white rounded-2xl shadow-lg overflow-visible">
    <!-- Header with Tabs -->
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 pt-4 pb-0">
      <h3 class="text-lg font-bold text-gray-800">
        <i class="fas fa-gas-pump text-orange-500 mr-2"></i>
        Station Fill Levels
      </h3>
      <p class="text-xs text-gray-500 mt-1">Current fuel levels by station</p>

      <!-- Station Tabs -->
      <div class="station-tabs-bar" v-if="stations.length > 0">
        <button
          v-for="station in stations"
          :key="station.station_id"
          type="button"
          class="station-tab"
          :class="{ 'active': activeStationId === station.station_id }"
          :style="getTabStyle(station)"
          @click="selectStation(station.station_id)">
          {{ station.station_name }}
        </button>
      </div>
    </div>

    <!-- Vertical Bars Display -->
    <div class="px-8 pt-8 pb-10 min-w-0">
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500"></div>
      </div>

      <div v-else-if="currentStationTanks.length === 0" class="text-center py-12">
        <i class="fas fa-gas-pump text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-500">No tank data available</p>
      </div>

      <div v-else ref="scrollContainer" @scroll="handleScroll" class="overflow-x-auto overflow-y-visible scrollbar-thin w-full min-w-0">
        <div class="flex items-end justify-around gap-6 min-h-[300px] min-w-max pb-1">
          <!-- Vertical Bar for each tank -->
          <div
            v-for="tank in currentStationTanks"
            :key="tank.tank_id"
            class="flex flex-col items-center gap-2"
            style="min-width: 80px;"
            @mouseenter="showTooltip($event, tank)"
            @mousemove="moveTooltip($event)"
            @mouseleave="hideTooltip()">

            <!-- Bar Container -->
            <div class="relative flex flex-col justify-end" style="height: 250px; width: 60px;">
              <!-- Bar Background -->
              <div class="absolute bottom-0 left-0 right-0 bg-gray-100 rounded-t-lg border border-gray-200"
                   style="height: 100%;">
              </div>

              <!-- Fill Bar with Gradient -->
              <div
                class="fill-bar animate relative rounded-t-lg transition-all duration-1000"
                :style="getBarStyle(tank)">

                <!-- Percentage Label on Bar -->
                <div v-if="tank.fill_percentage > 15"
                     class="absolute top-2 left-0 right-0 text-center text-white text-xs font-bold drop-shadow">
                  {{ tank.fill_percentage.toFixed(0) }}%
                </div>
              </div>

            </div>

            <!-- Label Below Bar (no procurement text — it's in the tooltip) -->
            <div class="text-center">
              <div class="text-xs font-semibold text-gray-700">{{ tank.product_name }}</div>
              <div class="text-xs font-bold" :style="{ color: getBarColor(tank.fuel_type_id) }">
                {{ formatTons(tank.current_stock_tons) }}
              </div>
              <div class="text-xs text-gray-500">{{ formatTons(tank.capacity_tons) }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Scroll Page Indicators -->
      <div class="flex justify-center gap-2 mt-3 min-h-[22px] py-0.5" v-if="scrollPages > 1">
        <button
          v-for="page in scrollPages"
          :key="page"
          type="button"
          class="w-2 h-2 rounded-full transition-all cursor-pointer hover:opacity-70"
          :class="currentScrollPage === page - 1 ? 'bg-orange-500 w-6' : 'bg-gray-300'"
          @click="scrollToPage(page - 1)">
        </button>
      </div>
    </div>
  </div>

  <!-- ── Teleport Tooltip — rendered in <body> to bypass overflow clipping ── -->
  <Teleport to="body">
    <div v-if="hoverTank"
      class="fixed z-[9999] pointer-events-none transition-opacity duration-100"
      :style="tooltipStyle">
      <div class="bg-white text-gray-800 rounded-xl px-3 py-2.5 shadow-xl border border-gray-200 whitespace-nowrap"
        style="min-width: 170px;">

        <!-- Fuel + fill -->
        <div class="font-bold text-sm text-gray-900 mb-1">{{ hoverTank.product_name }}</div>
        <div class="text-gray-500 text-xs mb-1">
          {{ formatTons(hoverTank.current_stock_tons) }} / {{ formatTons(hoverTank.capacity_tons) }}
          <span class="font-bold text-gray-800 ml-1">({{ hoverTank.fill_percentage.toFixed(1) }}%)</span>
        </div>

        <!-- Procurement data -->
        <div class="border-t border-gray-100 mt-2 pt-2 space-y-1.5 text-xs">
          <template v-if="getShortage(hoverTank)">
            <!-- Daily consumption -->
            <div class="flex justify-between gap-6">
              <span class="text-gray-400">Daily cons.</span>
              <span class="font-semibold text-gray-700">
                {{ getDailyCons(getShortage(hoverTank), hoverTank) != null
                    ? getDailyCons(getShortage(hoverTank), hoverTank) + ' t/day'
                    : '—' }}
              </span>
            </div>
            <!-- Days until empty -->
            <div class="flex justify-between gap-6">
              <span class="text-gray-400">Until empty</span>
              <span class="font-semibold text-gray-700">
                {{ getShortage(hoverTank).days_left != null
                    ? Math.round(getShortage(hoverTank).days_left) + 'd'
                    : '∞' }}
              </span>
            </div>
            <!-- Days to critical -->
            <div class="flex justify-between gap-6 border-t border-gray-100 pt-1.5">
              <span class="text-gray-400">Days to crit.</span>
              <span class="font-bold" :class="getDaysToCritClass(getShortage(hoverTank))">
                {{ getShortage(hoverTank).days_until_critical_level != null
                    ? Math.round(getShortage(hoverTank).days_until_critical_level) + 'd'
                    : '∞' }}
              </span>
            </div>
            <!-- Last order date -->
            <div v-if="getShortage(hoverTank).last_order_date" class="flex justify-between gap-6">
              <span class="text-gray-400">Order by</span>
              <span class="font-bold"
                :class="isOrderUrgent(getShortage(hoverTank).last_order_date) ? 'text-red-500' : 'text-gray-700'">
                {{ fmtShortDate(getShortage(hoverTank).last_order_date) }}
              </span>
            </div>
            <!-- Urgency -->
            <div class="flex justify-between gap-6">
              <span class="text-gray-400">Urgency</span>
              <span class="font-bold" :class="getDaysToCritClass(getShortage(hoverTank))">
                {{ getShortage(hoverTank).urgency }}
              </span>
            </div>
          </template>
          <!-- No entry at all → truly zero consumption -->
          <template v-else>
            <div class="flex items-center gap-1.5 text-green-600 font-semibold">
              <i class="fas fa-check-circle text-green-500"></i>
              No consumption data
            </div>
          </template>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { stationsApi, procurementApi } from '../services/api';

const loading = ref(true);
const stations = ref([]);
const allTanks = ref([]); // Store all tanks from API
const activeStationId = ref(null);
const scrollContainer = ref(null);
const currentScrollPage = ref(0);
const scrollPages = ref(1);

// Procurement data for days-to-critical + last_order_date + days_left
const shortages = ref([]);
// Map keyed by "stationId_fuelTypeId" → most urgent shortage item for that combo
const urgencyOrder = { CATASTROPHE: 0, CRITICAL: 1, MUST_ORDER: 2, WARNING: 3, PLANNED: 4 }
const shortageMap = computed(() => {
  const map = {}
  for (const s of shortages.value) {
    const key = `${s.station_id}_${s.fuel_type_id}`
    // Keep the most urgent entry (lowest urgencyOrder value) per station+fuel
    if (!map[key] || (urgencyOrder[s.urgency] ?? 5) < (urgencyOrder[map[key].urgency] ?? 5)) {
      map[key] = s
    }
  }
  return map
})
// Look up shortage for a grouped tank
const getShortage = (tank) =>
  shortageMap.value[`${tank.station_id}_${tank.fuel_type_id}`] || null

// Daily consumption in tons — derived from current stock and days_left
// days_left = current_stock / daily_cons → daily_cons = current_stock / days_left
const getDailyCons = (shortage, tank) => {
  const dl = shortage?.days_left
  if (!dl || dl <= 0 || !tank?.current_stock_tons) return null
  return (tank.current_stock_tons / dl).toFixed(1)
}

// Color class for "days to critical" text (light background — use dark readable shades)
const getDaysToCritClass = (shortage) => {
  const u = shortage?.urgency
  return {
    CATASTROPHE: 'text-red-600',
    CRITICAL:    'text-red-500',
    MUST_ORDER:  'text-orange-500',
    WARNING:     'text-amber-600',
    PLANNED:     'text-blue-500',
  }[u] || 'text-gray-500'
}

// Red if last_order_date is today or past
const isOrderUrgent = (dateStr) => {
  if (!dateStr) return false
  return new Date(dateStr) <= new Date()
}

// Format "2026-02-08" → "Feb 8"
const fmtShortDate = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

// ── Teleport Tooltip — mouse-tracked, bypasses overflow clipping ──────────────
const hoverTank   = ref(null)
const tooltipX    = ref(0)
const tooltipY    = ref(0)

const tooltipStyle = computed(() => ({
  left:      (tooltipX.value + 14) + 'px',
  top:       (tooltipY.value - 12) + 'px',
  transform: 'translateY(-100%)',
}))

function showTooltip(event, tank) {
  hoverTank.value = tank
  tooltipX.value  = event.clientX
  tooltipY.value  = event.clientY
}
function moveTooltip(event) {
  tooltipX.value = event.clientX
  tooltipY.value = event.clientY
}
function hideTooltip() {
  hoverTank.value = null
}

// Get tanks for currently selected station from real data
// Group by fuel type and sum capacities/stocks
const currentStationTanks = computed(() => {
  if (!activeStationId.value || allTanks.value.length === 0) return [];

  const stationTanks = allTanks.value.filter(tank => tank.station_id === activeStationId.value);

  // Group by fuel type ID
  const groupedByFuel = {};

  stationTanks.forEach(tank => {
    const fuelId = tank.fuel_type_id;

    if (!groupedByFuel[fuelId]) {
      groupedByFuel[fuelId] = {
        tank_id: `grouped_${fuelId}`,
        station_id: tank.station_id,
        fuel_type_id: fuelId,
        product_name: tank.product_name,
        fuel_type_code: tank.fuel_type_code,
        tank_capacity_liters: 0,
        current_stock_liters: 0,
        capacity_tons: 0,
        current_stock_tons: 0,
        tank_count: 0
      };
    }

    groupedByFuel[fuelId].tank_capacity_liters += tank.tank_capacity_liters;
    groupedByFuel[fuelId].current_stock_liters += tank.current_stock_liters;
    groupedByFuel[fuelId].capacity_tons += tank.capacity_tons;
    groupedByFuel[fuelId].current_stock_tons += tank.current_stock_tons;
    groupedByFuel[fuelId].tank_count++;
  });

  // Convert to array and calculate fill percentage
  const grouped = Object.values(groupedByFuel).map(fuel => ({
    ...fuel,
    fill_percentage: fuel.tank_capacity_liters > 0
      ? (fuel.current_stock_liters / fuel.tank_capacity_liters) * 100
      : 0
  }));

  // Sort by fuel type name
  return grouped.sort((a, b) => a.product_name.localeCompare(b.product_name));
});

const getTabStyle = (station) => {
  if (activeStationId.value === station.station_id) {
    return {}; // Active tab uses CSS class styling
  }

  // Get all tanks for this station
  const stationTanks = allTanks.value.filter(t => t.station_id === station.station_id);
  if (stationTanks.length === 0) return { background: '#e5e7eb' };

  // Find minimum fill percentage (most critical tank)
  const minFill = Math.min(...stationTanks.map(t => parseFloat(t.fill_percentage || 100)));

  // Color based on most critical tank level
  if (minFill < 30) {
    return { background: '#fecaca', color: '#991b1b' }; // Red - critical
  } else if (minFill < 50) {
    return { background: '#fed7aa', color: '#9a3412' }; // Orange - low
  } else {
    return { background: '#d1fae5', color: '#065f46' }; // Green - normal
  }
};

const getBarStyle = (tank) => {
  const height = `${tank.fill_percentage}%`;
  let gradient = '';

  // Gradient based on fill level
  if (tank.fill_percentage < 30) {
    // Red gradient for critical
    gradient = 'linear-gradient(to top, #dc2626, #ef4444)';
  } else if (tank.fill_percentage < 50) {
    // Orange gradient for low
    gradient = 'linear-gradient(to top, #ea580c, #f97316)';
  } else if (tank.fill_percentage < 80) {
    // Yellow-green gradient for medium
    gradient = 'linear-gradient(to top, #84cc16, #a3e635)';
  } else {
    // Green gradient for good
    gradient = 'linear-gradient(to top, #16a34a, #22c55e)';
  }

  return {
    height,
    background: gradient,
    boxShadow: 'inset 0 2px 4px rgba(255,255,255,0.2), inset 0 -2px 4px rgba(0,0,0,0.1)',
  };
};

const getBarColor = (fuelTypeId) => {
  // Find a tank with this fuel type to get its fill percentage
  const tank = currentStationTanks.value.find(t => t.fuel_type_id === fuelTypeId);
  if (!tank) return '#6b7280'; // Gray fallback

  const fillPct = tank.fill_percentage;

  // Return color matching the gradient (use darker color from gradient)
  if (fillPct < 30) {
    return '#dc2626'; // Red
  } else if (fillPct < 50) {
    return '#ea580c'; // Orange
  } else if (fillPct < 80) {
    return '#84cc16'; // Yellow-green
  } else {
    return '#16a34a'; // Green
  }
};

const formatTons = (tons) => {
  if (!tons) return '0 t';
  const num = parseFloat(tons);
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K t';
  }
  return num.toFixed(1) + ' t';
};

const formatLiters = (liters) => {
  if (!liters) return '0 L';
  const num = parseFloat(liters);
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K L';
  }
  return num.toFixed(0) + ' L';
};

const selectStation = async (stationId) => {
  activeStationId.value = stationId;

  // Load tanks for this station if not already loaded
  const hasStationTanks = allTanks.value.some(t => t.station_id === stationId);
  if (!hasStationTanks) {
    await loadStationTanks(stationId);
  }

  // Update scroll pages after station change
  setTimeout(() => {
    updateScrollPages();
    if (scrollContainer.value) {
      scrollContainer.value.scrollLeft = 0; // Reset scroll position
    }
  }, 100);
};

const loadStationTanks = async (stationId) => {
  try {
    const response = await stationsApi.getTanks(stationId);

    if (response.data.success && response.data.data) {
      const tanks = response.data.data;

      // Transform tank data to match template expectations
      const transformedTanks = tanks.map(tank => ({
        tank_id: tank.tank_id,
        station_id: stationId,
        depot_id: tank.depot_id,
        depot_name: tank.depot_name,
        tank_number: tank.tank_number,
        fuel_type_id: tank.fuel_type_id,
        product_name: tank.fuel_type_name, // Map fuel_type_name to product_name for template
        fuel_type_code: tank.fuel_type_code,
        density: parseFloat(tank.density || 0),
        tank_capacity_liters: parseFloat(tank.capacity_liters || 0),
        current_stock_liters: parseFloat(tank.current_stock_liters || 0),
        capacity_tons: parseFloat(tank.capacity_tons || 0),
        current_stock_tons: parseFloat(tank.current_stock_tons || 0),
        fill_percentage: parseFloat(tank.fill_percentage || 0),
      }));

      // Add to allTanks (remove existing tanks for this station first)
      allTanks.value = allTanks.value.filter(t => t.station_id !== stationId);
      allTanks.value.push(...transformedTanks);
    }
  } catch (error) {
    console.error(`Error loading tanks for station ${stationId}:`, error);
  }
};

const loadData = async () => {
  try {
    loading.value = true;

    // Load stations + procurement shortages in parallel
    // Use 9999-day horizon to capture ALL depots regardless of urgency timeline
    const [stationsRes, shortagesRes] = await Promise.all([
      stationsApi.getAll(),
      procurementApi.getUpcomingShortages(9999).catch(() => null), // non-fatal
    ]);

    // Store shortages for days-to-critical / last_order_date overlays
    if (shortagesRes?.data?.success) {
      shortages.value = shortagesRes.data.data || [];
    }

    if (stationsRes.data.success) {
      const stationsData = stationsRes.data.data || [];

      // Transform stations data
      stations.value = stationsData.map(s => ({
        station_id: s.id,
        station_name: s.name,
        station_code: s.code
      }));

      // Load tanks for ALL stations to enable color indicators on tabs
      if (stations.value.length > 0) {
        activeStationId.value = stations.value[0].station_id;

        // Load all station tanks in parallel
        await Promise.all(
          stations.value.map(station => loadStationTanks(station.station_id))
        );
      }
    }
  } catch (error) {
    console.error('Error loading station fill levels:', error);
  } finally {
    loading.value = false;
  }
};

const handleScroll = () => {
  if (!scrollContainer.value) return;

  const container = scrollContainer.value;
  const scrollLeft = container.scrollLeft;
  const containerWidth = container.clientWidth;
  const scrollWidth = container.scrollWidth;

  // Calculate number of pages based on scroll width
  scrollPages.value = Math.ceil(scrollWidth / containerWidth);

  // Calculate current page
  currentScrollPage.value = Math.floor(scrollLeft / containerWidth);
};

const updateScrollPages = () => {
  if (!scrollContainer.value) return;

  const container = scrollContainer.value;
  const containerWidth = container.clientWidth;
  const scrollWidth = container.scrollWidth;

  scrollPages.value = Math.ceil(scrollWidth / containerWidth);
};

const scrollToPage = (pageIndex) => {
  if (!scrollContainer.value) return;

  const container = scrollContainer.value;
  const containerWidth = container.clientWidth;
  const scrollLeft = pageIndex * containerWidth;

  container.scrollTo({
    left: scrollLeft,
    behavior: 'smooth'
  });
};

onMounted(() => {
  loadData();

  // Update scroll pages after data loads and DOM updates
  setTimeout(() => {
    updateScrollPages();
  }, 500);
});
</script>

<style scoped>
/* Station Tabs Bar */
.station-tabs-bar {
  display: flex;
  gap: 0;
  padding: 0 24px;
  margin: 0 -24px;
  margin-top: 12px;
  margin-bottom: 0;
  overflow-x: auto;
  scrollbar-width: thin;
}

.station-tab {
  border: 1px solid transparent;
  padding: 10px 20px;
  font-size: 14px;
  color: #0f172a;
  cursor: pointer;
  margin-bottom: -1px;
  border-radius: 6px 6px 0 0;
  white-space: nowrap;
  transition: background 0.2s, color 0.2s, border-color 0.2s;
}

.station-tab.active {
  background: #fff !important;
  color: #0f172a !important;
  font-weight: 600;
  border: 1px solid #e5e7eb;
  border-bottom: 1px solid #fff;
}

.station-tab:hover:not(.active) {
  opacity: 0.8;
}

/* Fill Bar Animation */
.fill-bar {
  opacity: 0;
  min-height: 2px;
}

.fill-bar.animate {
  opacity: 1;
}

.fill-bar:hover {
  filter: brightness(1.1);
  cursor: pointer;
}
</style>
