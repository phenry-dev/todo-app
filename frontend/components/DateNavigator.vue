<script setup lang="ts">
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps<{ date: string }>()
const emits = defineEmits<{ 'update:date': (date: string) => void }>()

function changeDate(days: number) {
  const d = new Date(props.date)
  d.setDate(d.getDate() + days)
  const iso = d.toISOString().split('T')[0]
  emits('update:date', iso)
}

function setToday() {
  const iso = new Date().toISOString().split('T')[0]
  emits('update:date', iso)
}

function formatDate(dateStr: string) {
  const d = new Date(dateStr + 'T00:00:00')
  return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })
}
</script>

<template>
  <div class="flex items-center gap-4 mb-6">
    <button @click="changeDate(-1)" class="text-slate-600 hover:text-slate-900 transition">
      <ChevronLeft :size="20" />
    </button>

    <div class="text-center">
      <p class="text-sm text-slate-500">Selected date</p>
      <p class="text-lg font-semibold text-slate-900">{{ formatDate(props.date) }}</p>
    </div>

    <button @click="changeDate(1)" class="text-slate-600 hover:text-slate-900 transition">
      <ChevronRight :size="20" />
    </button>

    <button @click="setToday" class="ml-auto px-3 py-1 text-xs bg-slate-900 text-white rounded hover:bg-slate-800 transition">
      Today
    </button>
  </div>
</template>
