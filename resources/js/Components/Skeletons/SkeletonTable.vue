<template>
    <div class="space-y-4">
        <!-- Table Header Mockup -->
        <div class="flex items-center justify-between py-4">
            <SkeletonBase className="h-10 w-64" />
            <SkeletonBase className="h-10 w-32" />
        </div>
        
        <!-- Table Body Mockup -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
            <div class="border-b border-slate-100 dark:border-slate-700 p-4 flex gap-4">
                <SkeletonBase v-for="i in 5" :key="i" className="h-4 flex-1" />
            </div>
            <div v-for="row in 5" :key="row" class="p-4 flex gap-4 border-b border-slate-50 dark:border-slate-700/50 last:border-0">
                <SkeletonBase v-for="col in 5" :key="col" className="h-8 flex-1" />
            </div>
        </div>
        
        <!-- Pagination Mockup -->
        <div class="flex justify-center pt-4">
            <SkeletonBase className="h-10 w-48" />
        </div>
    </div>
</template>

<script setup>
import SkeletonBase from './SkeletonBase.vue';
</script>
