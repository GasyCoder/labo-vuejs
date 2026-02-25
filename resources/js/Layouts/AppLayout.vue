<template>
    <div class="nk-app-root overflow-hidden">
        <div class="nk-main">
            <Sidebar />
            <div
                class="nk-wrap xl:ps-72 [&>.nk-header]:xl:start-72 [&>.nk-header]:xl:w-[calc(100%-theme(spacing.72))] peer-[&.is-compact:not(.has-hover)]:xl:ps-[74px] peer-[&.is-compact:not(.has-hover)]:[&>.nk-header]:xl:start-[74px] peer-[&.is-compact:not(.has-hover)]:[&>.nk-header]:xl:w-[calc(100%-74px)] flex flex-col min-h-screen transition-all duration-300">

                <Header />

                <div id="pagecontent" class="nk-content mt-8 px-1.5 py-6 sm:px-5 sm:py-8 bg-slate-50 dark:bg-transparent">
                    <div :class="['container', container ? '' : 'max-w-none']">
                        <slot />
                    </div>
                </div><!-- content -->

                <Footer />

            </div>
            <div
                class="sidebar-backdrop sidebar-toggle fixed inset-0 bg-slate-950/20 z-[1030] opacity-0 invisible pointer-events-none transition-opacity duration-300 lg:hidden">
            </div>
        </div>
    </div><!-- root -->
</template>

<script setup>
import Sidebar from './Partials/Sidebar.vue';
import Header from './Partials/Header.vue';
import Footer from './Partials/Footer.vue';

const props = defineProps({
    container: {
        type: Boolean,
        default: false,
    }
});
</script>
