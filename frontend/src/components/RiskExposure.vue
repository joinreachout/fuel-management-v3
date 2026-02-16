<template>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 py-4">
      <h3 class="text-lg font-bold text-gray-800">
        <i class="fas fa-exclamation-triangle text-amber-500 mr-2"></i>
        Risk Exposure Overview
      </h3>
      <p class="text-xs text-gray-500 mt-1">Aggregated risk summary by scenario</p>

      <!-- Scenario Tabs -->
      <div class="flex gap-2 mt-3">
        <button
          v-for="scenario in scenarios"
          :key="scenario.id"
          type="button"
          class="px-4 py-2 text-sm font-medium rounded-lg transition-all"
          :class="activeScenario === scenario.id
            ? 'bg-white text-gray-900 border-2 border-gray-300 shadow-sm'
            : 'bg-gray-100 text-gray-600 border-2 border-transparent hover:bg-gray-200'"
          @click="activeScenario = scenario.id">
          {{ scenario.name }}
        </button>
      </div>
    </div>

    <!-- Content -->
    <div class="px-6 py-6">
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-500"></div>
      </div>

      <div v-else>
        <!-- Hero Section -->
        <div class="mb-6 p-6 rounded-xl"
             :class="currentData.safe ? 'bg-gradient-to-br from-emerald-50 to-green-50 border-2 border-emerald-200' : 'bg-gradient-to-br from-amber-50 to-orange-50 border-2 border-amber-200'">
          <div class="flex items-center gap-3 mb-2">
            <div class="text-5xl font-bold" :class="currentData.safe ? 'text-emerald-600' : 'text-amber-600'">
              {{ currentData.safetyBuffer }}
            </div>
            <div class="text-sm text-gray-600">
              <div class="font-semibold">days safety buffer</div>
              <div class="text-xs">worst-case coverage at {{ currentData.fillTarget }}% fill target</div>
            </div>
          </div>
          <div class="flex items-center gap-2 mt-2">
            <span v-if="currentData.safe"
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
              <i class="fas fa-check-circle mr-1"></i>
              Meets Policy Minimum
            </span>
            <span v-else
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
              <i class="fas fa-exclamation-triangle mr-1"></i>
              Below Policy Minimum
            </span>
          </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <!-- Safety Buffer -->
          <div class="p-4 rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200">
            <div class="text-xs font-semibold text-blue-700 mb-1">Safety Buffer</div>
            <div class="text-2xl font-bold text-blue-900">{{ currentData.safetyBuffer }}</div>
            <div class="text-xs text-blue-600">days minimum coverage</div>
          </div>

          <!-- Demand Stress -->
          <div class="p-4 rounded-lg bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200">
            <div class="text-xs font-semibold text-purple-700 mb-1">Demand Stress</div>
            <div class="text-2xl font-bold text-purple-900">{{ currentData.demandStress }}</div>
            <div class="text-xs text-purple-600">percentile assumption</div>
          </div>

          <!-- Locations Below -->
          <div class="p-4 rounded-lg bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200">
            <div class="text-xs font-semibold text-amber-700 mb-1">Below Threshold</div>
            <div class="text-2xl font-bold text-amber-900">{{ currentData.locationsBelow }}</div>
            <div class="text-xs text-amber-600">locations at risk</div>
          </div>

          <!-- Recovery Time -->
          <div class="p-4 rounded-lg bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200">
            <div class="text-xs font-semibold text-emerald-700 mb-1">Recovery Time</div>
            <div class="text-2xl font-bold text-emerald-900">{{ currentData.recoveryTime }}</div>
            <div class="text-xs text-emerald-600">days max lead time</div>
          </div>
        </div>

        <!-- Risk Assessment Summary -->
        <div class="p-5 rounded-lg bg-gray-50 border border-gray-200 mb-4">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-sm font-semibold text-gray-700 mb-1">Overall Risk Score</div>
              <div class="flex items-center gap-2">
                <div class="text-3xl font-bold text-gray-900">{{ currentData.riskScore }}<span class="text-lg text-gray-500">/5</span></div>
                <span class="px-3 py-1 rounded-full text-xs font-medium"
                      :class="currentData.riskScore <= 2 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                  {{ currentData.riskScore <= 2 ? 'Within Risk Tolerance' : 'Outside Risk Tolerance' }}
                </span>
              </div>
            </div>
            <div class="text-5xl" :class="currentData.riskScore <= 2 ? 'text-emerald-500' : 'text-amber-500'">
              <i :class="currentData.riskScore <= 2 ? 'fas fa-shield-check' : 'fas fa-exclamation-circle'"></i>
            </div>
          </div>
        </div>

        <!-- Critical Locations (if any) -->
        <div v-if="currentData.locationsBelow > 0" class="mt-4">
          <button
            type="button"
            class="flex items-center justify-between w-full p-4 rounded-lg bg-red-50 border border-red-200 hover:bg-red-100 transition-colors"
            @click="showCriticalLocations = !showCriticalLocations">
            <div class="flex items-center gap-2">
              <i class="fas fa-exclamation-triangle text-red-600"></i>
              <span class="text-sm font-semibold text-red-900">{{ currentData.locationsBelow }} locations below conservative threshold</span>
            </div>
            <i class="fas fa-chevron-down text-red-600 transition-transform"
               :class="{ 'rotate-180': showCriticalLocations }"></i>
          </button>

          <div v-if="showCriticalLocations" class="mt-2 p-4 bg-white border border-red-200 rounded-lg">
            <div v-for="(location, idx) in criticalLocations" :key="idx"
                 class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
              <div class="text-sm font-medium text-gray-800">{{ location.name }}</div>
              <div class="flex items-center gap-3">
                <div class="text-xs text-gray-500">{{ location.bufferDays }} days remaining</div>
                <div class="text-xs font-semibold text-red-600">-{{ location.deficit }} days</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const loading = ref(false);
const activeScenario = ref('recommended');
const showCriticalLocations = ref(false);

const scenarios = [
  { id: 'conservative', name: 'Conservative' },
  { id: 'recommended', name: 'Recommended' },
  { id: 'aggressive', name: 'Aggressive' }
];

// Mock data for each scenario
const scenarioData = {
  conservative: {
    safetyBuffer: 12,
    fillTarget: 70,
    safe: true,
    demandStress: 'P95',
    locationsBelow: 0,
    recoveryTime: 5,
    riskScore: 1
  },
  recommended: {
    safetyBuffer: 8,
    fillTarget: 65,
    safe: true,
    demandStress: 'P85',
    locationsBelow: 2,
    recoveryTime: 4,
    riskScore: 2
  },
  aggressive: {
    safetyBuffer: 5,
    fillTarget: 60,
    safe: false,
    demandStress: 'P75',
    locationsBelow: 5,
    recoveryTime: 3,
    riskScore: 4
  }
};

const criticalLocations = [
  { name: 'Station Alpha', bufferDays: 4, deficit: 4 },
  { name: 'Station Beta', bufferDays: 3, deficit: 5 },
  { name: 'Station Gamma', bufferDays: 5, deficit: 3 },
  { name: 'Station Delta', bufferDays: 2, deficit: 6 },
  { name: 'Station Epsilon', bufferDays: 6, deficit: 2 }
];

const currentData = computed(() => {
  return scenarioData[activeScenario.value] || scenarioData.recommended;
});
</script>

<style scoped>
.rotate-180 {
  transform: rotate(180deg);
}
</style>
