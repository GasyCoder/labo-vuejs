<template>
<div class="w-full min-w-[320px] mt-auto border-t bg-white dark:bg-gray-950 border-gray-200 dark:border-gray-900 px-1.5 sm:px-5 py-5">
    <div class="container max-w-none">
        <div class="flex items-center justify-between flex-wrap">
            <div class="text-sm text-slate-500 pb-1 sm:pb-0"> &copy; {{ new Date().getFullYear() }} {{ $page.props.settings?.site_name || $page.props.settings?.nom_entreprise }}. Tous droits réservés. Conçu par <a class="hover:text-primary-500" href="https://gasycoder.com" target="_blank">GasyCoder</a>
            </div>
        </div>
    </div>
    <!-- container -->
</div>
<!-- footer -->
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
</script>
