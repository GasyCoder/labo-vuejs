<template>
    <div :class="['skeleton-item animate-shimmer bg-slate-200 dark:bg-slate-700 rounded', className]"></div>
</template>

<script setup>
const props = defineProps({
    className: {
        type: String,
        default: 'h-4 w-full'
    }
});
</script>

<style scoped>
.skeleton-item {
    background-image: linear-gradient(
        90deg, 
        rgba(255, 255, 255, 0) 0, 
        rgba(255, 255, 255, 0.2) 20%, 
        rgba(255, 255, 255, 0.5) 60%, 
        rgba(255, 255, 255, 0)
    );
    background-size: 200% 100%;
    animation: shimmer 2s infinite linear;
}

@keyframes shimmer {
    to {
        background-position-x: -200%;
    }
}
</style>
