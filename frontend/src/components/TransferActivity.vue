<template>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 py-4">
      <h3 class="text-lg font-bold text-gray-800">
        <i class="fas fa-truck text-blue-500 mr-2"></i>
        Transfer Activity Monitor
      </h3>
      <p class="text-xs text-gray-500 mt-1">Active fuel transfers in progress</p>
    </div>

    <!-- Content -->
    <div class="p-6">
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
      </div>

      <div v-else-if="activeTransfers.length === 0" class="py-12 text-center">
        <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
        <div class="text-lg font-semibold text-gray-700 mb-1">No Active Transfers</div>
        <div class="text-sm text-gray-500">All fuel transfers completed successfully</div>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="transfer in activeTransfers"
          :key="transfer.id"
          class="bg-gradient-to-r from-blue-50 to-white border border-blue-200 rounded-lg p-4 hover:shadow-md transition-shadow">
          <div class="flex items-start justify-between mb-3">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span class="text-sm font-bold text-gray-900">Transfer #{{ transfer.id }}</span>
                <span
                  class="px-2 py-0.5 rounded-full text-xs font-medium"
                  :class="getStatusClass(transfer.status)">
                  {{ formatStatus(transfer.status) }}
                </span>
              </div>
              <div class="text-xs text-gray-600">
                <i class="fas fa-map-marker-alt text-gray-400 mr-1"></i>
                {{ transfer.from }} → {{ transfer.to }}
              </div>
            </div>
            <div class="text-right">
              <div class="text-lg font-bold text-blue-900">{{ transfer.amount }}</div>
              <div class="text-xs text-gray-500">{{ transfer.fuelType }}</div>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="mb-2">
            <div class="flex justify-between text-xs text-gray-600 mb-1">
              <span>Progress</span>
              <span>{{ transfer.progress }}%</span>
            </div>
            <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
              <div
                class="h-full bg-gradient-to-r from-blue-500 to-blue-600 rounded-full transition-all duration-300"
                :style="{ width: transfer.progress + '%' }"></div>
            </div>
          </div>

          <!-- Details -->
          <div class="flex items-center justify-between text-xs text-gray-500">
            <div class="flex items-center gap-4">
              <span>
                <i class="far fa-clock mr-1"></i>
                {{ transfer.eta }}
              </span>
              <span>
                <i class="fas fa-user mr-1"></i>
                {{ transfer.driver }}
              </span>
            </div>
            <button
              type="button"
              class="text-blue-600 hover:text-blue-700 font-medium"
              @click="viewDetails(transfer)">
              View Details →
            </button>
          </div>
        </div>
      </div>

      <!-- Summary Stats -->
      <div class="mt-6 pt-4 border-t border-gray-200 grid grid-cols-3 gap-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
          <div class="text-xs text-gray-500">Total Today</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-600">{{ stats.active }}</div>
          <div class="text-xs text-gray-500">In Transit</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-600">{{ stats.completed }}</div>
          <div class="text-xs text-gray-500">Completed</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { transfersApi } from '../services/api';

const loading = ref(true);
const activeTransfers = ref([]);

const stats = ref({
  total: 0,
  active: 0,
  completed: 0
});

const getStatusClass = (status) => {
  const classes = {
    'in_progress': 'bg-blue-100 text-blue-800',
    'pending': 'bg-amber-100 text-amber-800',
    'completed': 'bg-green-100 text-green-800',
    'cancelled': 'bg-red-100 text-red-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatStatus = (status) => {
  const labels = {
    'in_progress': 'In Transit',
    'pending': 'Pending',
    'completed': 'Completed',
    'cancelled': 'Cancelled'
  };
  return labels[status] || status;
};

const formatAmount = (amount) => {
  const num = parseFloat(amount);
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K L';
  }
  return num.toFixed(0) + ' L';
};

const viewDetails = (transfer) => {
  console.log('View transfer details:', transfer);
  // TODO: Implement transfer details modal
};

const loadData = async () => {
  try {
    loading.value = true;

    // Get in_progress and pending transfers
    const response = await transfersApi.getAll();

    if (response.data.success) {
      const transfers = response.data.data || [];
      const statsData = response.data.stats;

      // Show only active transfers (pending or in_progress)
      activeTransfers.value = transfers
        .filter(t => t.status === 'in_progress' || t.status === 'pending')
        .map(t => ({
          id: t.id,
          from: t.from_station_name,
          to: t.to_station_name,
          amount: formatAmount(t.transfer_amount_liters),
          fuelType: t.fuel_type_name,
          status: t.status,
          progress: t.status === 'in_progress' ? 50 : 0, // in_progress = en route (~50%), pending = not started
          eta: t.estimated_delivery_date || 'N/A',
          driver: t.driver_name || 'N/A'
        }));

      // Set stats
      stats.value = {
        total: parseInt(statsData?.total_transfers) || 0,
        active: (parseInt(statsData?.in_progress_transfers) || 0) + (parseInt(statsData?.pending_transfers) || 0),
        completed: parseInt(statsData?.completed_transfers) || 0
      };
    }
  } catch (error) {
    console.error('Error loading transfer activity:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadData();
});
</script>

<style scoped>
/* Component specific styles */
</style>
