<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div v-for="i in 4" :key="i" class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col items-center gap-3">
            <SkeletonBase className="h-10 w-10 rounded-full" />
            <SkeletonBase className="h-6 w-16" />
            <SkeletonBase className="h-3 w-24" />
        </div>
    </div>
</template>

<script setup>
import SkeletonBase from './SkeletonBase.vue';
</script>
