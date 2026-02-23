<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <div v-if="links && links.length > 3">
        <div class="flex flex-wrap -mb-1">
            <template v-for="(link, p) in links" :key="p">
                <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded dark:border-gray-700" v-html="link.label" />
                <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white dark:border-gray-600 focus:border-indigo-500 focus:text-indigo-500 transition-colors" :class="{ 'bg-blue-50 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 font-bold border-blue-500': link.active, 'dark:text-gray-300': !link.active }" :href="link.url" v-html="link.label" preserve-state preserve-scroll />
            </template>
        </div>
    </div>
</template>
