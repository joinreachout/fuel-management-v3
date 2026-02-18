<template>
  <div class="inline-flex items-center group">
    <template v-if="!editing">
      <span
        class="text-sm font-medium text-gray-900 cursor-pointer hover:text-blue-600 hover:underline"
        :class="{ 'text-gray-400 italic': value === null || value === undefined || value === '' }"
        @click="startEdit"
      >{{ display }}</span>
      <button
        @click="startEdit"
        class="ml-1 text-gray-300 hover:text-blue-500 opacity-0 group-hover:opacity-100 transition-opacity"
      >
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
        </svg>
      </button>
    </template>
    <template v-else>
      <input
        v-model="draft"
        :type="type"
        :step="step"
        @blur="save"
        @keydown="onKey"
        class="w-28 px-2 py-1 text-sm border border-blue-400 rounded focus:outline-none focus:ring-2 focus:ring-blue-300"
        autofocus
      />
      <span v-if="suffix" class="ml-1 text-xs text-gray-500">{{ suffix }}</span>
      <button @click="save" class="ml-1 text-green-600 hover:text-green-800">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
      </button>
      <button @click="cancel" class="ml-1 text-gray-400 hover:text-gray-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </template>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  value:       { default: null },
  type:        { default: 'text' },
  step:        { default: '1' },
  suffix:      { default: '' },
  placeholder: { default: '—' },
});

const emit = defineEmits(['save']);

const editing = ref(false);
const draft   = ref('');

const display = computed(() => {
  if (props.value === null || props.value === undefined || props.value === '') {
    return props.placeholder;
  }
  return String(props.value) + (props.suffix || '');
});

function startEdit() {
  draft.value   = props.value ?? '';
  editing.value = true;
}
function cancel() {
  editing.value = false;
}
function save() {
  editing.value = false;
  const val = props.type === 'number' ? parseFloat(draft.value) : draft.value;
  if (String(val) !== String(props.value)) emit('save', val);
}
function onKey(e) {
  if (e.key === 'Enter')  save();
  if (e.key === 'Escape') cancel();
}
</script>
