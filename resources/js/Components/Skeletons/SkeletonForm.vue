<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden">
        <div class="p-6 sm:p-8 space-y-8">
            <div v-for="section in 3" :key="section" class="grid grid-cols-1 md:grid-cols-3 gap-6 pb-8 border-b border-slate-100 dark:border-slate-700 last:border-0 last:pb-0">
                <div class="space-y-2">
                    <SkeletonBase className="h-6 w-32" />
                    <SkeletonBase className="h-4 w-48" />
                </div>
                <div class="md:col-span-2 space-y-4">
                    <div v-for="field in 2" :key="field" class="space-y-2">
                        <SkeletonBase className="h-4 w-24" />
                        <SkeletonBase className="h-10 w-full" />
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <SkeletonBase className="h-10 w-24" />
                <SkeletonBase className="h-10 w-32" />
            </div>
        </div>
    </div>
</template>

<script setup>
import SkeletonBase from './SkeletonBase.vue';
</script>
