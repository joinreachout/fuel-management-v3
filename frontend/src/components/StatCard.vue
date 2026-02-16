<template>
  <div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-600 mb-1">{{ title }}</p>
        <p class="text-3xl font-bold text-gray-900">{{ value }}</p>
        <p v-if="subtitle" class="text-sm text-gray-500 mt-1">{{ subtitle }}</p>
      </div>
      <div :class="[
        'h-12 w-12 rounded-full flex items-center justify-center',
        iconBgColor
      ]">
        <span class="text-2xl">{{ icon }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  subtitle: {
    type: String,
    default: '',
  },
  icon: {
    type: String,
    default: '📊',
  },
  iconBgColor: {
    type: String,
    default: 'bg-blue-100',
  },
});
</script>
