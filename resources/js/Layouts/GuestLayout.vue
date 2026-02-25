<template>
    <div class="relative flex min-h-screen">
        <div class="relative flex flex-col flex-shrink-0 w-full min-h-full bg-white dark:bg-gray-950">
            <div class="m-auto w-full max-w-[420px] xs:max-w-[520px] p-5">
                <!-- Logo -->
                <div class="relative flex justify-center flex-shrink-0 pb-6">
                    <a :href="route('root')" class="relative inline-block h-16 w-auto transition-opacity duration-300">
                        <img
                            class="h-16 w-auto object-contain opacity-0 dark:opacity-100"
                            :src="$page.props.settings?.logo"
                            :alt="$page.props.settings?.nom_entreprise"
                        >
                        <img
                            class="absolute top-0 left-0 h-16 w-auto object-contain opacity-100 dark:opacity-0"
                            :src="$page.props.settings?.logo"
                            :alt="$page.props.settings?.nom_entreprise"
                        >
                    </a>
                </div>

                <!-- Content Card -->
                <div class="p-5 border border-gray-300 rounded dark:border-gray-900 sm:p-6 md:p-10">
                    <slot />
                </div>
            </div>

            <!-- Footer -->
            <div class="py-6 border-t border-gray-200 dark:border-gray-800 px-5.5">
                <div class="container max-w-7xl">
                    <div class="flex flex-wrap justify-center -m-2">
                        <div class="w-full p-2">
                            <p class="text-center text-slate-400 text-sm/4">
                                &copy; {{ new Date().getFullYear() }} La Reference.
                                Tous droits réservés. Développé par
                                <a href="https://gasycoder.com" target="_blank" class="hover:text-primary-500 transition-colors">GasyCoder</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
</script>
