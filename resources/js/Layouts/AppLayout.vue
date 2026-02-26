<template>
    <div class="nk-app-root overflow-hidden">
        <div class="nk-main">
            <Sidebar />
            <div
                class="nk-wrap xl:ps-72 [&>.nk-header]:xl:start-72 [&>.nk-header]:xl:w-[calc(100%-theme(spacing.72))] peer-[&.is-compact:not(.has-hover)]:xl:ps-[74px] peer-[&.is-compact:not(.has-hover)]:[&>.nk-header]:xl:start-[74px] peer-[&.is-compact:not(.has-hover)]:[&>.nk-header]:xl:w-[calc(100%-74px)] flex flex-col min-h-screen transition-all duration-300">

                <Header />

                <!-- pt-24 (96px) : l'équilibre parfait pour une navbar de 64px -->
                <div id="pagecontent" class="nk-content pt-24 px-4 sm:px-6 lg:px-8 bg-white dark:bg-slate-900">
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
import { onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import Sidebar from './Partials/Sidebar.vue';
import Header from './Partials/Header.vue';
import Footer from './Partials/Footer.vue';

const props = defineProps({
    container: {
        type: Boolean,
        default: false,
    }
});

const page = usePage();

let removeListener = null;

onMounted(() => {
    removeListener = router.on('success', () => {
        const flash = page.props.flash;
        if (flash?.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: flash.success,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        }
        if (flash?.error) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: flash.error,
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
            });
        }
    });
});

onUnmounted(() => {
    if (removeListener) removeListener();
});
</script>
