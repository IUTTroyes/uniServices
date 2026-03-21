<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'

type Item = {
  id: string | number
  label: string
}

const props = defineProps<{ items: Item[] }>()

const selected = ref<Array<string | number>>([])

const ids = ref<Record<string | number, string>>({})

const makeId = (id: string | number) => `chckbox-${id}-${Math.random().toString(36).slice(2, 9)}`

const ensureIds = () => {
  props.items?.forEach((item) => {
    if (!ids.value[item.id]) ids.value[item.id] = makeId(item.id)
  })
}

onMounted(ensureIds)
watch(() => props.items, ensureIds, { immediate: true })
</script>

<template>
  <div class="card flex flex-wrap justify-center gap-4">
    <div
        class="flex items-center gap-2"
        v-for="item in props.items"
        :key="item.id"
    >
      <Checkbox
          v-model="selected"
          :inputId="ids[item.id]"
          :value="item.id"
      />
      <label :for="ids[item.id]">{{ item.label }}</label>
    </div>
  </div>
</template>
