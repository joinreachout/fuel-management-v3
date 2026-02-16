<template>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <!-- Header with Tabs -->
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 pt-4 pb-0">
      <h3 class="text-lg font-bold text-gray-800">
        💰 Working Capital Optimization
      </h3>
      <p class="text-xs text-gray-500 mt-1">Optimize inventory levels to balance liquidity and supply safety</p>

      <!-- Tabs -->
      <div class="wc-tabs">
        <button
          type="button"
          class="wc-tab"
          :class="{ 'active': activeTab === 'snapshot' }"
          @click="activeTab = 'snapshot'">
          Snapshot
        </button>
        <button
          type="button"
          class="wc-tab"
          :class="{ 'active': activeTab === 'scenarios' }"
          @click="activeTab = 'scenarios'">
          Scenarios
        </button>
        <button
          type="button"
          class="wc-tab"
          :class="{ 'active': activeTab === 'opportunities' }"
          @click="activeTab = 'opportunities'">
          Opportunities
        </button>
      </div>
    </div>

    <!-- Tab Content -->
    <div class="p-6">
      <!-- Snapshot Tab -->
      <div v-if="activeTab === 'snapshot'" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-gradient-to-br from-blue-50 to-white p-4 rounded-lg border border-blue-100">
            <div class="text-xs text-blue-600 font-semibold mb-1">Current Stock Value</div>
            <div class="text-2xl font-bold text-blue-900">€12.4M</div>
            <div class="text-xs text-blue-500 mt-1">Across all locations</div>
          </div>

          <div class="bg-gradient-to-br from-emerald-50 to-white p-4 rounded-lg border border-emerald-100">
            <div class="text-xs text-emerald-600 font-semibold mb-1">Optimized Value</div>
            <div class="text-2xl font-bold text-emerald-900">€9.8M</div>
            <div class="text-xs text-emerald-500 mt-1">-21% reduction possible</div>
          </div>

          <div class="bg-gradient-to-br from-amber-50 to-white p-4 rounded-lg border border-amber-100">
            <div class="text-xs text-amber-600 font-semibold mb-1">Days of Cover</div>
            <div class="text-2xl font-bold text-amber-900">28 days</div>
            <div class="text-xs text-amber-500 mt-1">Current average</div>
          </div>

          <div class="bg-gradient-to-br from-purple-50 to-white p-4 rounded-lg border border-purple-100">
            <div class="text-xs text-purple-600 font-semibold mb-1">Capital Freed</div>
            <div class="text-2xl font-bold text-purple-900">€2.6M</div>
            <div class="text-xs text-purple-500 mt-1">Potential savings</div>
          </div>
        </div>
      </div>

      <!-- Scenarios Tab -->
      <div v-if="activeTab === 'scenarios'" class="space-y-4">
        <div class="flex gap-2 mb-4">
          <button
            v-for="scenario in scenarios"
            :key="scenario.id"
            type="button"
            class="scenario-btn"
            :class="{ 'active': activeScenario === scenario.id }"
            @click="activeScenario = scenario.id">
            {{ scenario.name }}
          </button>
        </div>

        <div v-if="activeScenario" class="bg-gray-50 p-4 rounded-lg">
          <h4 class="font-semibold text-gray-800 mb-3">
            {{ scenarios.find(s => s.id === activeScenario)?.name }} Scenario
          </h4>
          <div class="grid grid-cols-3 gap-3 text-sm">
            <div>
              <div class="text-gray-500 text-xs mb-1">Stock Value</div>
              <div class="font-bold text-gray-900">
                {{ scenarios.find(s => s.id === activeScenario)?.stockValue }}
              </div>
            </div>
            <div>
              <div class="text-gray-500 text-xs mb-1">Days of Cover</div>
              <div class="font-bold text-gray-900">
                {{ scenarios.find(s => s.id === activeScenario)?.daysOfCover }}
              </div>
            </div>
            <div>
              <div class="text-gray-500 text-xs mb-1">Risk Level</div>
              <div class="font-bold" :class="getRiskColor(scenarios.find(s => s.id === activeScenario)?.riskLevel)">
                {{ scenarios.find(s => s.id === activeScenario)?.riskLevel }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Opportunities Tab -->
      <div v-if="activeTab === 'opportunities'" class="space-y-3">
        <div
          v-for="opportunity in opportunities"
          :key="opportunity.id"
          class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
          <div class="text-2xl">{{ opportunity.icon }}</div>
          <div class="flex-1">
            <div class="font-semibold text-gray-800 text-sm">{{ opportunity.title }}</div>
            <div class="text-xs text-gray-600 mt-1">{{ opportunity.description }}</div>
            <div class="text-xs font-bold text-emerald-600 mt-1">{{ opportunity.savings }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer Note -->
    <div class="px-6 pb-4 text-xs text-gray-500 border-t border-gray-100 pt-3">
      Data basis: Calculated from trailing 90-day average demand, current inventory levels, confirmed inbound supply, and forecasted consumption.
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const activeTab = ref('snapshot');
const activeScenario = ref('recommended');

const scenarios = [
  {
    id: 'conservative',
    name: 'Conservative',
    stockValue: '€11.2M',
    daysOfCover: '25 days',
    riskLevel: 'Low'
  },
  {
    id: 'recommended',
    name: 'Recommended',
    stockValue: '€9.8M',
    daysOfCover: '21 days',
    riskLevel: 'Medium'
  },
  {
    id: 'aggressive',
    name: 'Aggressive',
    stockValue: '€8.4M',
    daysOfCover: '18 days',
    riskLevel: 'High'
  }
];

const opportunities = [
  {
    id: 1,
    icon: '📦',
    title: 'Reduce excess stock at Depot Alpha',
    description: 'Currently 32 days of cover, optimal is 21 days',
    savings: 'Save €280K'
  },
  {
    id: 2,
    icon: '🔄',
    title: 'Transfer surplus from Station Beta',
    description: 'Move 15K liters to high-demand Station Gamma',
    savings: 'Save €45K in ordering costs'
  },
  {
    id: 3,
    icon: '⚡',
    title: 'Just-in-time delivery for Diesel',
    description: 'Reduce safety stock by 15% with weekly deliveries',
    savings: 'Save €120K annually'
  }
];

const getRiskColor = (riskLevel) => {
  const colors = {
    'Low': 'text-emerald-600',
    'Medium': 'text-amber-600',
    'High': 'text-red-600'
  };
  return colors[riskLevel] || 'text-gray-600';
};
</script>

<style scoped>
/* Working Capital Tabs */
.wc-tabs {
  display: flex;
  gap: 0;
  padding: 0 24px;
  margin: 0 -24px;
  margin-top: 12px;
  margin-bottom: 0;
}

.wc-tab {
  background: #d1fae5;
  border: 1px solid transparent;
  padding: 10px 20px;
  font-size: 14px;
  color: #065f46;
  cursor: pointer;
  margin-bottom: -1px;
  border-radius: 6px 6px 0 0;
  transition: background 0.2s, color 0.2s;
}

.wc-tab:hover:not(.active) {
  background: #a7f3d0;
}

.wc-tab.active {
  background: #fff;
  color: #0f172a;
  font-weight: 600;
  border: 1px solid #e5e7eb;
  border-bottom: 1px solid #fff;
}

/* Scenario Buttons */
.scenario-btn {
  flex: 1;
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  border: 1px solid #e5e7eb;
  background: #f9fafb;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s ease;
}

.scenario-btn:hover {
  background: #f3f4f6;
}

.scenario-btn.active {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border-color: #10b981;
}
</style>
