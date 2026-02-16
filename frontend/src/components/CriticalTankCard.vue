<template>
  <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
    <div class="flex justify-between items-start mb-2">
      <div>
        <h4 class="font-semibold text-gray-900">{{ tank.depot_name }}</h4>
        <p class="text-sm text-gray-600">{{ tank.station_name }}</p>
      </div>
      <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded">
        {{ Math.round(tank.days_until_empty * 10) / 10 }} days
      </span>
    </div>

    <div class="mt-3 space-y-2">
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Fuel Type:</span>
        <span class="font-medium">{{ tank.fuel_type_name }}</span>
      </div>

      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Current Stock:</span>
        <span class="font-medium">{{ formatLiters(tank.current_stock_liters) }}</span>
      </div>

      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Daily Consumption:</span>
        <span class="font-medium">{{ formatLiters(tank.daily_consumption_liters) }}/day</span>
      </div>

      <!-- Progress Bar -->
      <div class="mt-3">
        <div class="flex justify-between text-xs text-gray-600 mb-1">
          <span>Fill Level</span>
          <span>{{ tank.fill_percentage }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div
            :class="[
              'h-2 rounded-full transition-all',
              tank.fill_percentage > 50 ? 'bg-green-500' :
              tank.fill_percentage > 25 ? 'bg-yellow-500' : 'bg-red-500'
            ]"
            :style="{ width: tank.fill_percentage + '%' }"
          ></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  tank: {
    type: Object,
    required: true,
  },
});

const formatLiters = (liters) => {
  if (!liters) return '0 L';
  const num = parseFloat(liters);
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M L';
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K L';
  }
  return num.toFixed(0) + ' L';
};
</script>
