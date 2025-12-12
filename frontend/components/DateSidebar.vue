<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ selectedDate: string }>()
const emit = defineEmits<{ 'update:date': (date: string) => void }>()

function formatDateLabel(date: Date): string {
  const today = new Date()
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)
  
  const isToday = date.toDateString() === today.toDateString()
  const isYesterday = date.toDateString() === yesterday.toDateString()
  
  if (isToday) return 'Today'
  if (isYesterday) return 'Yesterday'
  
  return date.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' })
}

function getWeekLabel(date: Date): string {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const weekStart = new Date(date)
  weekStart.setDate(date.getDate() - date.getDay())
  
  // Check if this is last week (the week before current week)
  const currentWeekStart = new Date(today)
  currentWeekStart.setDate(today.getDate() - today.getDay())
  const lastWeekStart = new Date(currentWeekStart)
  lastWeekStart.setDate(currentWeekStart.getDate() - 7)
  
  if (weekStart.getTime() === lastWeekStart.getTime()) {
    return 'Last week'
  }
  
  // Calculate week number in month
  const firstDayOfMonth = new Date(weekStart.getFullYear(), weekStart.getMonth(), 1)
  const firstWeekStart = new Date(firstDayOfMonth)
  firstWeekStart.setDate(firstDayOfMonth.getDate() - firstDayOfMonth.getDay())
  const weekNum = Math.floor((weekStart.getTime() - firstWeekStart.getTime()) / (7 * 24 * 60 * 60 * 1000)) + 1
  const monthName = weekStart.toLocaleDateString(undefined, { month: 'long' })
  
  return `${weekNum}${getOrdinalSuffix(weekNum)} Week of ${monthName}`
}

function getOrdinalSuffix(n: number): string {
  const j = n % 10
  const k = n % 100
  if (j === 1 && k !== 11) return 'st'
  if (j === 2 && k !== 12) return 'nd'
  if (j === 3 && k !== 13) return 'rd'
  return 'th'
}

function isSameWeek(date1: Date, date2: Date): boolean {
  const d1 = new Date(date1)
  const d2 = new Date(date2)
  d1.setHours(0, 0, 0, 0)
  d2.setHours(0, 0, 0, 0)
  const diff = d1.getTime() - d2.getTime()
  const daysDiff = Math.floor(diff / (1000 * 60 * 60 * 24))
  const day1 = d1.getDay()
  const day2 = d2.getDay()
  const week1 = Math.floor((d1.getDate() - day1) / 7)
  const week2 = Math.floor((d2.getDate() - day2) / 7)
  return week1 === week2 && d1.getFullYear() === d2.getFullYear() && d1.getMonth() === d2.getMonth()
}

const dateList = computed(() => {
  const items: Array<{ type: 'date' | 'week'; date?: Date; label: string; dateStr?: string }> = []
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  let lastWeek: number | null = null
  
  // Generate dates for the past 3 weeks
  for (let i = 0; i < 21; i++) {
    const date = new Date(today)
    date.setDate(date.getDate() - i)
    
    const weekStart = new Date(date)
    weekStart.setDate(date.getDate() - date.getDay())
    const weekNum = weekStart.getTime()
    
    // Add week separator if needed
    if (lastWeek !== null && weekNum !== lastWeek) {
      items.push({
        type: 'week',
        label: getWeekLabel(date)
      })
    }
    lastWeek = weekNum
    
    const label = formatDateLabel(date)
    const dateStr = date.toISOString().split('T')[0]
    
    items.push({
      type: 'date',
      date,
      label,
      dateStr
    })
  }
  
  return items
})

function selectDate(dateStr: string) {
  emit('update:date', dateStr)
}

function isSelected(dateStr: string): boolean {
  return dateStr === props.selectedDate
}
</script>

<template>
  <div class="w-64 bg-white border-r border-gray-200 overflow-y-auto">
    <div class="p-4 space-y-1">
      <template v-for="(item, index) in dateList" :key="index">
        <div
          v-if="item.type === 'week'"
          class="text-xs text-gray-400 px-3 py-2 mt-2"
        >
          {{ item.label }}
        </div>
        <button
          v-else
          @click="selectDate(item.dateStr!)"
          :class="[
            'w-full text-left px-3 py-2 rounded-lg text-sm transition',
            isSelected(item.dateStr!)
              ? 'bg-black text-white font-medium'
              : 'text-black hover:bg-gray-100'
          ]"
        >
          {{ item.label }}
        </button>
      </template>
    </div>
  </div>
</template>

