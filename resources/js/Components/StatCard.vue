<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: String, required: true },
    sub: { type: String, default: '' },
    accent: { type: String, default: 'blue' }, // blue / green / purple / amber
});

const accentClass = {
    blue: 'bg-blue-500',
    green: 'bg-green-500',
    purple: 'bg-purple-500',
    amber: 'bg-amber-500',
};
</script>

<template>
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 w-2 h-10 rounded" :class="accentClass[accent] || accentClass.blue"></div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">{{ label }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ value }}</p>
                    <p v-if="sub" class="text-xs text-gray-400 mt-1">{{ sub }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
