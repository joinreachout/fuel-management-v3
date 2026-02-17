<template>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-bold text-gray-800">
            <i class="fas fa-calendar-alt text-teal-500 mr-2"></i>
            Orders Calendar
          </h3>
          <p class="text-xs text-gray-500 mt-1">Scheduled and planned fuel orders</p>
        </div>

        <!-- Filters -->
        <div class="flex items-center gap-3">
          <select
            v-model="filters.station"
            class="text-xs px-3 py-1.5 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-teal-500">
            <option value="">All Stations</option>
            <option v-for="station in stations" :key="station" :value="station">{{ station }}</option>
          </select>
          <select
            v-model="filters.fuel"
            class="text-xs px-3 py-1.5 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-teal-500">
            <option value="">All Fuel Types</option>
            <option v-for="fuel in fuelTypes" :key="fuel" :value="fuel">{{ fuel }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Calendar Navigation -->
    <div class="bg-gray-50 border-b border-gray-200 px-6 py-3 flex items-center justify-between">
      <button
        type="button"
        class="px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-200 rounded-lg transition-colors"
        @click="previousMonth">
        <i class="fas fa-chevron-left mr-1"></i>
        Previous
      </button>
      <div class="text-sm font-bold text-gray-800">{{ currentMonthLabel }}</div>
      <button
        type="button"
        class="px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-200 rounded-lg transition-colors"
        @click="nextMonth">
        Next
        <i class="fas fa-chevron-right ml-1"></i>
      </button>
    </div>

    <!-- Content -->
    <div class="p-6">
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-500"></div>
      </div>

      <div v-else>
        <!-- Calendar Grid -->
        <div class="grid grid-cols-7 gap-2 mb-4">
          <!-- Day Headers -->
          <div
            v-for="day in ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']"
            :key="day"
            class="text-center text-xs font-semibold text-gray-600 py-2">
            {{ day }}
          </div>

          <!-- Calendar Days -->
          <div
            v-for="(day, index) in calendarDays"
            :key="index"
            class="aspect-square border rounded-lg p-1 transition-all"
            :class="getDayClass(day)">
            <div class="text-xs font-medium" :class="day.isCurrentMonth ? 'text-gray-900' : 'text-gray-400'">
              {{ day.date }}
            </div>
            <div v-if="day.orders && day.orders.length > 0" class="mt-1 space-y-0.5">
              <div
                v-for="(order, idx) in day.orders.slice(0, 2)"
                :key="idx"
                class="text-[9px] px-1 py-0.5 rounded truncate"
                :class="getOrderClass(order.type)"
                :title="order.description">
                {{ order.station }}
              </div>
              <div v-if="day.orders.length > 2" class="text-[8px] text-gray-500 px-1">
                +{{ day.orders.length - 2 }} more
              </div>
            </div>
          </div>
        </div>

        <!-- Legend -->
        <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded bg-blue-100 border border-blue-300"></div>
            <span class="text-xs text-gray-600">Scheduled</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded bg-amber-100 border border-amber-300"></div>
            <span class="text-xs text-gray-600">Pending Approval</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded bg-green-100 border border-green-300"></div>
            <span class="text-xs text-gray-600">Confirmed</span>
          </div>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-4 gap-4 mt-6 pt-4 border-t border-gray-200">
          <div class="text-center">
            <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
            <div class="text-xs text-gray-500">Total Orders</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-blue-600">{{ stats.scheduled }}</div>
            <div class="text-xs text-gray-500">Scheduled</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-amber-600">{{ stats.pending }}</div>
            <div class="text-xs text-gray-500">Pending</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-green-600">{{ stats.confirmed }}</div>
            <div class="text-xs text-gray-500">Confirmed</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const loading = ref(true);
const currentMonth = ref(new Date());

const filters = ref({
  station: '',
  fuel: ''
});

const stations = ref(['Bishkek Central', 'Osh Terminal', 'Jalal-Abad', 'Karakol', 'Naryn']);
const fuelTypes = ref(['Diesel', 'Petrol 95', 'Petrol 98']);

const stats = ref({
  total: 18,
  scheduled: 8,
  pending: 5,
  confirmed: 5
});

// Mock orders data
const orders = ref([
  { date: 5, station: 'Bishkek', type: 'confirmed', description: 'Diesel - 15,000L' },
  { date: 8, station: 'Osh', type: 'scheduled', description: 'Petrol 95 - 10,000L' },
  { date: 12, station: 'Jalal-Abad', type: 'pending', description: 'Diesel - 12,000L' },
  { date: 15, station: 'Karakol', type: 'confirmed', description: 'Petrol 98 - 5,000L' },
  { date: 18, station: 'Naryn', type: 'scheduled', description: 'Diesel - 8,000L' },
  { date: 22, station: 'Bishkek', type: 'pending', description: 'Petrol 95 - 18,000L' },
  { date: 25, station: 'Osh', type: 'confirmed', description: 'Diesel - 20,000L' },
  { date: 28, station: 'Jalal-Abad', type: 'scheduled', description: 'Petrol 98 - 6,000L' }
]);

const currentMonthLabel = computed(() => {
  const months = ['January', 'February', 'March', 'April', 'May', 'June',
                  'July', 'August', 'September', 'October', 'November', 'December'];
  return `${months[currentMonth.value.getMonth()]} ${currentMonth.value.getFullYear()}`;
});

const calendarDays = computed(() => {
  const year = currentMonth.value.getFullYear();
  const month = currentMonth.value.getMonth();

  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);

  // Start from Monday
  const startDay = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
  const days = [];

  // Previous month days
  const prevMonthLastDay = new Date(year, month, 0).getDate();
  for (let i = startDay - 1; i >= 0; i--) {
    days.push({
      date: prevMonthLastDay - i,
      isCurrentMonth: false,
      orders: []
    });
  }

  // Current month days
  for (let i = 1; i <= lastDay.getDate(); i++) {
    const dayOrders = orders.value.filter(o => o.date === i);
    days.push({
      date: i,
      isCurrentMonth: true,
      orders: dayOrders,
      isToday: i === new Date().getDate() &&
               month === new Date().getMonth() &&
               year === new Date().getFullYear()
    });
  }

  // Next month days
  const remaining = 35 - days.length;
  for (let i = 1; i <= remaining; i++) {
    days.push({
      date: i,
      isCurrentMonth: false,
      orders: []
    });
  }

  return days;
});

const getDayClass = (day) => {
  if (!day.isCurrentMonth) return 'bg-gray-50 border-gray-100';
  if (day.isToday) return 'bg-teal-50 border-teal-300 border-2';
  if (day.orders && day.orders.length > 0) return 'bg-white border-gray-300 hover:border-teal-400 cursor-pointer';
  return 'bg-white border-gray-200 hover:bg-gray-50';
};

const getOrderClass = (type) => {
  const classes = {
    'scheduled': 'bg-blue-100 text-blue-800',
    'pending': 'bg-amber-100 text-amber-800',
    'confirmed': 'bg-green-100 text-green-800'
  };
  return classes[type] || 'bg-gray-100 text-gray-800';
};

const previousMonth = () => {
  currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() - 1);
};

const nextMonth = () => {
  currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + 1);
};

const loadData = () => {
  loading.value = true;

  setTimeout(() => {
    loading.value = false;
  }, 500);
};

onMounted(() => {
  loadData();
});
</script>

<style scoped>
/* Component specific styles */
</style>
