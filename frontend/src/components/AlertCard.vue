<template>
  <div :class="[
    'rounded-lg p-4 border-l-4',
    alertClasses[severity]
  ]">
    <div class="flex items-start">
      <span class="text-2xl mr-3">{{ alertIcon[severity] }}</span>
      <div class="flex-1">
        <p :class="[
          'font-semibold mb-1',
          textClasses[severity]
        ]">
          {{ severityLabel[severity] }}
        </p>
        <p class="text-gray-700">{{ message }}</p>
        <p v-if="details" class="text-sm text-gray-600 mt-1">{{ details }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  severity: {
    type: String,
    required: true,
    validator: (value) => ['CATASTROPHE', 'CRITICAL', 'MUST_ORDER', 'WARNING', 'INFO'].includes(value),
  },
  message: {
    type: String,
    required: true,
  },
  details: {
    type: String,
    default: '',
  },
});

const alertClasses = {
  CATASTROPHE: 'bg-red-50 border-red-600',
  CRITICAL: 'bg-orange-50 border-orange-500',
  MUST_ORDER: 'bg-yellow-50 border-yellow-500',
  WARNING: 'bg-blue-50 border-blue-400',
  INFO: 'bg-gray-50 border-gray-400',
};

const textClasses = {
  CATASTROPHE: 'text-red-800',
  CRITICAL: 'text-orange-800',
  MUST_ORDER: 'text-yellow-800',
  WARNING: 'text-blue-800',
  INFO: 'text-gray-800',
};

const alertIcon = {
  CATASTROPHE: '🚨',
  CRITICAL: '⚠️',
  MUST_ORDER: '📦',
  WARNING: 'ℹ️',
  INFO: '💡',
};

const severityLabel = {
  CATASTROPHE: 'Catastrophe',
  CRITICAL: 'Critical',
  MUST_ORDER: 'Must Order',
  WARNING: 'Warning',
  INFO: 'Info',
};
</script>
