<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const page = usePage();

const props = defineProps({
    prescriptions: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, default: () => ({}) },
    permissions: { type: Object, default: () => ({}) },
});

const form = reactive({
    search: props.filters.search || '',
    tab: props.filters.tab || 'actives',
    perPage: props.filters.perPage || 10,
    payment: props.filters.payment || '',
});

const perm = computed(() => ({
    canCreate: props.permissions.canCreate ?? false,
    canEdit: props.permissions.canEdit ?? false,
    canDelete: props.permissions.canDelete ?? false,
    canAccessTrash: props.permissions.canAccessTrash ?? false,
    canRestore: props.permissions.canRestore ?? false,
    canForceDelete: props.permissions.canForceDelete ?? false,
    canAccessArchive: props.permissions.canAccessArchive ?? false,
    canViewPrescription: props.permissions.canViewPrescription ?? false,
}));

// Selection logic
const selectedIds = ref([]);
const isAllSelected = computed(() => {
    return props.prescriptions.data.length > 0 && selectedIds.value.length === props.prescriptions.data.length;
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.prescriptions.data.map(p => p.id);
    }
};

const toggleSelect = (id) => {
    const index = selectedIds.value.indexOf(id);
    if (index === -1) {
        selectedIds.value.push(id);
    } else {
        selectedIds.value.splice(index, 1);
    }
};

// Reset selection when tab changes or search changes
watch(() => [form.tab, form.search, form.payment], () => {
    selectedIds.value = [];
});

// Modal state
const modal = reactive({
    show: false,
    type: '', // delete, restore, permanentDelete, archive, unarchive, pay, unpay, bulkDelete, bulkRestore, bulkForceDelete, bulkArchive, bulkPay, bulkUnpay
    prescriptionId: null, // Used for single actions
    processing: false,
});

const openModal = (type, id = null) => {
    modal.type = type;
    modal.prescriptionId = id;
    modal.show = true;
    modal.processing = false;
};

const closeModal = () => {
    modal.show = false;
    modal.type = '';
    modal.prescriptionId = null;
    modal.processing = false;
};

const modalConfig = computed(() => {
    const configs = {
        // Single actions
        delete: { 
            title: 'Mettre en corbeille ?', 
            desc: 'Cette action peut être annulée depuis la corbeille.', 
            variant: 'danger', 
            btn: 'Supprimer', 
            routeName: 'secretaire.prescription.destroy', 
            method: 'delete',
            isBulk: false
        },
        restore: { 
            title: 'Restaurer cette prescription ?', 
            desc: 'Elle sera remise dans la liste active.', 
            variant: 'warning', 
            btn: 'Restaurer', 
            routeName: 'secretaire.prescription.restore', 
            method: 'post',
            isBulk: false
        },
        permanentDelete: { 
            title: 'Supprimer définitivement ?', 
            desc: 'Cette action est irréversible.', 
            variant: 'danger', 
            btn: 'Supprimer', 
            routeName: 'secretaire.prescription.forceDelete', 
            method: 'delete',
            isBulk: false
        },
        archive: { 
            title: 'Archiver cette prescription ?', 
            desc: 'Elle sera déplacée vers les archives.', 
            variant: 'slate', 
            btn: 'Archiver', 
            routeName: 'secretaire.prescription.archive', 
            method: 'post',
            isBulk: false
        },
        pay: { 
            title: 'Confirmer le paiement', 
            desc: 'Marquer comme payé ? La date sera enregistrée automatiquement.', 
            variant: 'success', 
            btn: 'Confirmer', 
            routeName: 'secretaire.prescription.togglePayment', 
            method: 'post',
            isBulk: false
        },
        unpay: { 
            title: 'Annuler le paiement', 
            desc: 'Marquer comme non payé ? La date de paiement sera supprimée.', 
            variant: 'danger', 
            btn: 'Confirmer', 
            routeName: 'secretaire.prescription.togglePayment', 
            method: 'post',
            isBulk: false
        },
        
        // Bulk actions
        bulkDelete: { 
            title: 'Suppression groupée', 
            desc: `Êtes-vous sûr de vouloir mettre ${selectedIds.value.length} prescriptions en corbeille ?`, 
            variant: 'danger', 
            btn: 'Oui, supprimer tout', 
            routeName: 'secretaire.prescription.bulkDestroy', 
            method: 'post',
            isBulk: true
        },
        bulkRestore: { 
            title: 'Restauration groupée', 
            desc: `Voulez-vous restaurer les ${selectedIds.value.length} prescriptions sélectionnées ?`, 
            variant: 'warning', 
            btn: 'Oui, restaurer', 
            routeName: 'secretaire.prescription.bulkRestore', 
            method: 'post',
            isBulk: true
        },
        bulkForceDelete: { 
            title: 'Suppression définitive groupée', 
            desc: `ATTENTION : Vous allez supprimer définitivement ${selectedIds.value.length} prescriptions. Cette action est irréversible !`, 
            variant: 'danger', 
            btn: 'Oui, supprimer définitivement', 
            routeName: 'secretaire.prescription.bulkForceDelete', 
            method: 'post',
            isBulk: true
        },
        bulkArchive: { 
            title: 'Archivage groupée', 
            desc: `Voulez-vous archiver les ${selectedIds.value.length} prescriptions sélectionnées ? Seules les prescriptions validées seront archivées.`, 
            variant: 'slate', 
            btn: 'Oui, archiver', 
            routeName: 'secretaire.prescription.bulkArchive', 
            method: 'post',
            isBulk: true
        },
        bulkPay: { 
            title: 'Paiement groupé', 
            desc: `Voulez-vous marquer comme payés les ${selectedIds.value.length} prescriptions sélectionnées ?`, 
            variant: 'success', 
            btn: 'Oui, confirmer', 
            routeName: 'secretaire.prescription.bulkTogglePayment', 
            method: 'post',
            params: { status: true },
            isBulk: true
        },
        bulkUnpay: { 
            title: 'Paiement groupé', 
            desc: `Voulez-vous marquer comme non payés les ${selectedIds.value.length} prescriptions sélectionnées ?`, 
            variant: 'danger', 
            btn: 'Oui, confirmer', 
            routeName: 'secretaire.prescription.bulkTogglePayment', 
            method: 'post',
            params: { status: false },
            isBulk: true
        }
    };
    return configs[modal.type] || {};
});

const executeModalAction = () => {
    const cfg = modalConfig.value;
    if (modal.processing) return;
    
    modal.processing = true;
    
    let url;
    let data = {};
    
    if (cfg.isBulk) {
        url = route(cfg.routeName);
        data = { ids: selectedIds.value, ...(cfg.params || {}) };
    } else {
        if (!modal.prescriptionId) return;
        url = route(cfg.routeName, modal.prescriptionId);
    }

    const opts = {
        preserveScroll: true,
        onSuccess: () => {
            if (cfg.isBulk) selectedIds.value = [];
            closeModal();
        },
        onError: () => { modal.processing = false; },
        onFinish: () => { modal.processing = false; },
    };

    if (cfg.method === 'delete') {
        router.delete(url, opts);
    } else {
        router.post(url, data, opts);
    }
};

// Notification modal state
const patientName = (p) => {
    if (!p?.patient) return '';
    return (p.patient.nom_complet || `${p.patient.nom || ''} ${p.patient.prenom || ''}`).trim().split(/\s+/)[0] || 'Patient';
};

const DEFAULT_SMS = (prescription) => {
    const nom = patientName(prescription);
    return `Bonjour ${nom}, vos résultats d'analyses (${prescription.reference}) sont disponibles au laboratoire. Merci de passer les récupérer. - Lareference`;
};

const DEFAULT_EMAIL = (prescription) => {
    const nom = patientName(prescription);
    const pdfUrl = route('laboratoire.prescription.pdf', prescription.id);
    return `Bonjour ${nom},\n\nNous avons le plaisir de vous informer que les résultats de vos analyses (${prescription.reference}) sont désormais disponibles.\n\nVous pouvez les consulter et télécharger via le lien suivant :\n${pdfUrl}\n\nOu passer les récupérer directement au laboratoire.\n\nCordialement,\nLaboratoire Lareference`;
};

const notify = reactive({
    show: false,
    prescription: null,
    channel: '',
    message: '',
    processing: false,
});

const hasPhone = computed(() => !!notify.prescription?.patient?.telephone);
const hasEmail = computed(() => !!notify.prescription?.patient?.email);
const hasAnyContact = computed(() => hasPhone.value || hasEmail.value);

const openNotifyModal = (prescription) => {
    notify.prescription = prescription;
    const phone = !!prescription.patient?.telephone;
    const email = !!prescription.patient?.email;
    notify.channel = phone ? 'sms' : (email ? 'email' : '');
    notify.message = phone ? DEFAULT_SMS(prescription) : (email ? DEFAULT_EMAIL(prescription) : '');
    notify.processing = false;
    notify.show = true;
};

const closeNotifyModal = () => {
    notify.show = false;
    notify.prescription = null;
    notify.processing = false;
};

const setDefaultMessage = () => {
    if (!notify.prescription) return;
    notify.message = notify.channel === 'sms' ? DEFAULT_SMS(notify.prescription) : DEFAULT_EMAIL(notify.prescription);
};

const sendNotification = () => {
    if (!notify.prescription || notify.processing || !notify.message.trim() || !notify.channel) return;
    notify.processing = true;

    const routeName = notify.channel === 'sms' ? 'secretaire.prescription.send-sms' : 'secretaire.prescription.send-email';
    const data = { message: notify.message };

    if (notify.channel === 'email') {
        data.lien_pdf = route('laboratoire.prescription.pdf', notify.prescription.id);
    }

    router.post(route(routeName, notify.prescription.id), data, {
        preserveScroll: true,
        onSuccess: () => closeNotifyModal(),
        onError: () => { notify.processing = false; },
        onFinish: () => { notify.processing = false; },
    });
};

const handlePaymentToggle = (prescription) => {
    if (!prescription.paiement) return;
    openModal(prescription.paiement.status ? 'unpay' : 'pay', prescription.id);
};

const totalCount = computed(() => {
    return Number(props.counts.countActives || props.counts.actives || 0)
        + Number(props.counts.countValide || props.counts.valide || 0)
        + Number(props.counts.countAutreLab || props.counts.autre_lab || 0)
        + Number(props.counts.countDeleted || props.counts.deleted || 0);
});

const getTabCount = (tab) => {
    // Si c'est l'onglet actif, la source de vérité absolue est le total de la pagination
    if (form.tab === tab) {
        return props.prescriptions.total;
    }
    
    // Sinon on utilise les counts envoyés par le serveur
    const map = {
        actives: props.counts.countActives || props.counts.actives || 0,
        valide: props.counts.countValide || props.counts.valide || 0,
        autre_lab: props.counts.countAutreLab || props.counts.autre_lab || 0,
        deleted: props.counts.countDeleted || props.counts.deleted || 0,
    };
    return map[tab] || 0;
};

// Debug logic
watch(() => props.prescriptions.data, (newData) => {
    console.log(`[Debug] Tab: ${form.tab} | Items: ${newData.length} | Total Pagination: ${props.prescriptions.total} | Server Count Autre: ${props.counts.countAutreLab}`);
    newData.forEach(p => {
        if (form.tab === 'autre_lab') {
            console.log(` -> Prescription ${p.reference}: labo=${p.labo_traitement}, nom=${p.labo_autre_nom}`);
        }
    });
}, { immediate: true });

const paymentRate = computed(() => {
    const paid = Number(props.counts.countPaye || 0);
    const unpaid = Number(props.counts.countNonPaye || 0);
    const total = paid + unpaid;
    return total === 0 ? 0 : Math.round((paid / total) * 100);
});

const progressRate = computed(() => {
    const enCours = Number(props.counts.countEnCours || 0);
    const termine = Number(props.counts.countTermine || 0);
    const valide = Number(props.counts.countValide || props.counts.valide || 0);
    const total = totalCount.value;
    return total === 0 ? 0 : Math.round(((enCours + termine + valide) / total) * 100);
});

const efficiencyRate = computed(() => {
    const termine = Number(props.counts.countTermine || 0);
    const valide = Number(props.counts.countValide || props.counts.valide || 0);
    const total = totalCount.value;
    return total === 0 ? 0 : Math.round(((termine + valide) / total) * 100);
});

const applyFilters = () => {
    router.get(route('secretaire.prescription.index'), form, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const debouncedApplyFilters = debounce(() => applyFilters(), 300);

const changeTab = (tab) => { form.tab = tab; applyFilters(); };
const filterByPaymentStatus = (s) => { form.payment = s; applyFilters(); };
const clearSearch = () => { form.search = ''; applyFilters(); };
const clearAllFilters = () => { form.search = ''; form.payment = ''; applyFilters(); };

const tabClass = (tab) => form.tab === tab
    ? 'text-primary-600 dark:text-primary-400'
    : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300';

const statusClass = (status) => {
    const map = {
        EN_ATTENTE: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        EN_COURS: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        TERMINE: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        VALIDE: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        ARCHIVE: 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
    };
    return map[status] || 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
};

const initials = (p) => {
    const parts = String(p.patient?.nom_complet || 'NA').trim().split(/\s+/).filter(Boolean);
    return `${parts[0]?.charAt(0) || 'N'}${parts[1]?.charAt(0) || 'A'}`.toUpperCase();
};

const truncate = (v, max) => { const t = String(v || ''); return t.length <= max ? t : `${t.slice(0, max)}...`; };
const number = (v) => Number(v || 0).toLocaleString('fr-FR');

const canCreate = computed(() => perm.value.canCreate);
const canAccessTrash = computed(() => perm.value.canAccessTrash);
const canRestore = computed(() => perm.value.canRestore);
const canForceDelete = computed(() => perm.value.canForceDelete);

// Detail modal state
const detail = reactive({
    show: false,
    prescription: null,
});

const openDetailModal = (prescription) => {
    detail.prescription = prescription;
    detail.show = true;
};

const closeDetailModal = () => {
    detail.show = false;
    setTimeout(() => {
        detail.prescription = null;
    }, 200);
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MGA', maximumFractionDigits: 0 })
        .format(value || 0)
        .replace('MGA', 'Ar');
};

const printDetail = () => {
    if (!detail.prescription) return;
    window.open(route('secretaire.prescription.pdf-externe', detail.prescription.id), '_blank');
};
</script>

<template>
    <AppLayout>
        <div class="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded bg-primary-900/40 text-primary-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                    </div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">Liste des prescriptions</h1>
                </div>

                <div v-if="selectedIds.length > 0" class="flex flex-wrap items-center gap-2 animate-in fade-in slide-in-from-top-2 duration-300">
                    <span class="text-sm font-medium text-slate-600 dark:text-slate-400 mr-2">
                        {{ selectedIds.length }} sélectionné(s)
                    </span>
                    
                    <!-- Actions for Actives/Valide -->
                    <template v-if="form.tab === 'actives' || form.tab === 'valide'">
                        <button 
                            v-if="perm.canDelete"
                            @click="openModal('bulkDelete')"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-sm font-bold text-red-600 transition-all hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            Mettre en corbeille
                        </button>
                        <button 
                            v-if="form.tab === 'valide' && perm.canAccessArchive"
                            @click="openModal('bulkArchive')"
                            class="inline-flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm font-bold text-slate-600 transition-all hover:bg-slate-100 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                            Archiver
                        </button>

                        <!-- Payment Actions -->
                        <button 
                            v-if="perm.canEdit"
                            @click="openModal('bulkPay')"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-600 transition-all hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:hover:bg-emerald-900/30"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Marquer payé
                        </button>
                        <button 
                            v-if="perm.canEdit"
                            @click="openModal('bulkUnpay')"
                            class="inline-flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-sm font-bold text-amber-600 transition-all hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/30"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2.25m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Marquer non payé
                        </button>
                    </template>

                    <!-- Actions for Trash -->
                    <template v-else-if="form.tab === 'deleted'">
                        <button 
                            v-if="perm.canRestore"
                            @click="openModal('bulkRestore')"
                            class="inline-flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-sm font-bold text-amber-600 transition-all hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/30"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                            Restaurer la sélection
                        </button>
                        <button 
                            v-if="perm.canForceDelete"
                            @click="openModal('bulkForceDelete')"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-2 text-sm font-bold text-white transition-all hover:bg-red-700 shadow-sm"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            Supprimer définitivement
                        </button>
                    </template>
                </div>
            </div>

            <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-3 transition-all hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex flex-col items-center gap-1 text-center">
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center text-primary-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4.5m2.5 0h8.5M8 12l2-4 2.5 8 2-4" />
                            </svg>
                        </div>
                        <p class="mt-1 text-xl font-bold leading-none text-slate-900 dark:text-white">{{ number(counts.countActives || counts.actives) }}</p>
                        <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">En Traitement</p>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-3 transition-all hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex flex-col items-center gap-1 text-center">
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center text-emerald-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="mt-1 text-xl font-bold leading-none text-slate-900 dark:text-white">{{ number(counts.countValide || counts.valide) }}</p>
                        <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Validees</p>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-xl border border-orange-200 bg-orange-50/30 p-3 transition-all hover:shadow-md dark:border-orange-900/40 dark:bg-orange-900/10">
                    <div class="flex flex-col items-center gap-1 text-center">
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center text-orange-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18" />
                            </svg>
                        </div>
                        <p class="mt-1 text-xl font-bold leading-none text-orange-700 dark:text-orange-400">{{ number(counts.countAutreLab || counts.autre_lab) }}</p>
                        <p class="text-[11px] font-medium text-orange-600 dark:text-orange-300">Autre Lab</p>
                    </div>
                    <button @click="changeTab('autre_lab')" type="button" class="absolute inset-0 cursor-pointer focus:outline-none" title="Voir les dossiers Autre Lab"></button>
                </div>

                <div class="relative overflow-hidden rounded-xl bg-white p-3 transition-all hover:shadow-md dark:bg-slate-800" :class="form.payment === 'paye' ? 'border-emerald-400 ring-1 ring-emerald-400/50 dark:border-emerald-500' : 'border border-slate-200 dark:border-slate-700'">
                    <div class="flex flex-col items-center gap-1 text-center">
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center text-emerald-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="mt-1 text-xl font-bold leading-none text-slate-900 dark:text-white">{{ number(counts.countPaye) }}</p>
                        <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Payees</p>
                    </div>
                    <button @click="filterByPaymentStatus('paye')" type="button" class="absolute inset-0 cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500/50" title="Filtrer les payees"></button>
                </div>

                <div class="relative overflow-hidden rounded-xl bg-white p-3 transition-all hover:shadow-md dark:bg-slate-800" :class="form.payment === 'non_paye' ? 'border-red-400 ring-1 ring-red-400/50 dark:border-red-500' : 'border border-slate-200 dark:border-slate-700'">
                    <div class="flex flex-col items-center gap-1 text-center">
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center text-red-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2.25m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="mt-1 text-xl font-bold leading-none text-slate-900 dark:text-white">{{ number(counts.countNonPaye) }}</p>
                        <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Non Payees</p>
                    </div>
                    <button @click="filterByPaymentStatus('non_paye')" type="button" class="absolute inset-0 cursor-pointer focus:outline-none focus:ring-2 focus:ring-red-500/50" title="Filtrer les non payees"></button>
                </div>

                <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-slate-900 p-3 shadow-md">
                    <div class="flex flex-col items-center gap-1 text-center">
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center text-primary-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                        </div>
                        <p class="text-xl font-bold leading-none text-white">{{ number(totalCount) }}</p>
                        <p class="text-[11px] font-medium text-slate-400 uppercase tracking-tight">Total Labo</p>
                    </div>
                </div>
            </div>

            <!-- Progress Bars -->
            <div class="mb-6 grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between mb-2">
                        <span class="flex items-center gap-1.5 text-xs font-semibold text-emerald-500">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Taux de paiement
                        </span>
                        <span class="text-xs font-bold text-slate-900 dark:text-white">{{ paymentRate }}%</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                        <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" :style="{ width: `${paymentRate}%` }"></div>
                    </div>
                </div>
                
                <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between mb-2">
                        <span class="flex items-center gap-1.5 text-xs font-semibold text-blue-500">
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span> Progression
                        </span>
                        <span class="text-xs font-bold text-slate-900 dark:text-white">{{ progressRate }}%</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                        <div class="h-full rounded-full bg-blue-500 transition-all duration-500" :style="{ width: `${progressRate}%` }"></div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between mb-2">
                        <span class="flex items-center gap-1.5 text-xs font-semibold text-primary-500">
                            <span class="h-2 w-2 rounded-full bg-primary-500"></span> Efficacite
                        </span>
                        <span class="text-xs font-bold text-slate-900 dark:text-white">{{ efficiencyRate }}%</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                        <div class="h-full rounded-full bg-primary-500 transition-all duration-500" :style="{ width: `${efficiencyRate}%` }"></div>
                    </div>
                    <div class="mt-1.5 text-[10px] text-slate-400">Analyses completees</div>
                </div>
            </div>

            <div class="mb-6 rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex flex-col items-center gap-3 p-4 md:flex-row">
                    <!-- Search Bar taking flex-1 -->
                    <div class="relative w-full flex-1">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input
                            v-model="form.search"
                            type="text"
                            placeholder="Rechercher..."
                            class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-9 text-sm text-slate-900 placeholder-slate-400 transition-colors focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-700/50 dark:text-slate-100"
                            @input="debouncedApplyFilters"
                        >
                        <button
                            v-if="form.search"
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition-colors hover:text-slate-600 dark:hover:text-slate-300"
                            @click="clearSearch"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Filter Options -->
                    <div class="flex w-full items-center gap-1.5 rounded-lg bg-slate-100 p-1 md:w-auto dark:bg-slate-700/50">
                        <button type="button" class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-all" :class="!form.payment ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-600 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'" @click="filterByPaymentStatus('')">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" /></svg>
                            Toutes
                        </button>
                        <button type="button" class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-all" :class="form.payment === 'paye' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-600 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'" @click="filterByPaymentStatus('paye')">
                            <svg class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Payees ({{ number(counts.countPaye) }})
                        </button>
                        <button type="button" class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-all" :class="form.payment === 'non_paye' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-600 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'" @click="filterByPaymentStatus('non_paye')">
                            <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2.25m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Non payees ({{ number(counts.countNonPaye) }})
                        </button>
                    </div>

                    <!-- New Prescription Button -->
                    <Link
                        v-if="canCreate"
                        :href="route('secretaire.prescription.create')"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-600 md:w-auto"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Nouvelle prescription
                    </Link>
                </div>

                <div class="border-t border-slate-100 dark:border-slate-700/50">
                    <nav class="flex px-4" aria-label="Tabs">
                        <button type="button" class="relative px-4 py-3 text-sm font-medium transition-colors" :class="tabClass('actives')" @click="changeTab('actives')">
                            <span class="flex items-center gap-2">
                                Actives
                                <span class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full px-1.5 text-xs font-semibold" :class="form.tab === 'actives' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400'">{{ number(getTabCount('actives')) }}</span>
                            </span>
                            <span v-if="form.tab === 'actives'" class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full bg-primary-600 dark:bg-primary-400"></span>
                        </button>

                        <button type="button" class="relative px-4 py-3 text-sm font-medium transition-colors" :class="tabClass('valide')" @click="changeTab('valide')">
                            <span class="flex items-center gap-2">
                                Validees
                                <span class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full px-1.5 text-xs font-semibold" :class="form.tab === 'valide' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400'">{{ number(getTabCount('valide')) }}</span>
                            </span>
                            <span v-if="form.tab === 'valide'" class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full bg-primary-600 dark:bg-primary-400"></span>
                        </button>

                        <button type="button" class="relative px-4 py-3 text-sm font-medium transition-colors" :class="tabClass('autre_lab')" @click="changeTab('autre_lab')">
                            <span class="flex items-center gap-2">
                                Autre Lab
                                <span class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full px-1.5 text-xs font-semibold" :class="form.tab === 'autre_lab' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400'">{{ number(getTabCount('autre_lab')) }}</span>
                            </span>
                            <span v-if="form.tab === 'autre_lab'" class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full bg-orange-600 dark:bg-orange-400"></span>
                        </button>

                        <button v-if="canAccessTrash" type="button" class="relative px-4 py-3 text-sm font-medium transition-colors" :class="tabClass('deleted')" @click="changeTab('deleted')">
                            <span class="flex items-center gap-2">
                                Corbeille
                                <span v-if="getTabCount('deleted') > 0" class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-red-100 px-1.5 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-300">{{ number(getTabCount('deleted')) }}</span>
                            </span>
                            <span v-if="form.tab === 'deleted'" class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full bg-primary-600 dark:bg-primary-400"></span>
                        </button>
                    </nav>
                </div>
            </div>

            <div v-if="form.search || form.payment" class="mb-4 flex flex-wrap items-center gap-2">
                <span class="text-xs text-slate-500 dark:text-slate-400">Filtres actifs :</span>
                <span v-if="form.search" class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                    "{{ form.search }}"
                    <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" @click="clearSearch">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>
                <span v-if="form.payment" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs" :class="form.payment === 'paye' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'">
                    {{ form.payment === 'paye' ? 'Payees' : 'Non payees' }}
                    <button type="button" class="opacity-60 hover:opacity-100" @click="filterByPaymentStatus('')">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>
                <button type="button" class="text-xs text-slate-400 underline hover:text-slate-600 dark:hover:text-slate-300" @click="clearAllFilters">Tout effacer</button>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="hidden lg:block">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
                                <th class="w-10 px-4 py-4">
                                    <input 
                                        type="checkbox" 
                                        :checked="isAllSelected"
                                        @change="toggleSelectAll"
                                        class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700"
                                    >
                                </th>
                                <th class="px-4 py-4 text-left text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Dossier & Date</th>
                                <th class="px-4 py-4 text-left text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Patient & Prescripteur</th>
                                <th class="px-4 py-4 text-left text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Analyses</th>
                                <th class="px-4 py-4 text-left text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Personnel</th>
                                <th class="px-4 py-4 text-center text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Paiement</th>
                                <th class="px-4 py-4 text-center text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Statut</th>
                                <th class="px-4 py-4 text-right text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                            <tr v-for="(prescription, idx) in prescriptions.data" :key="prescription.id" class="group transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-700/20" :class="{'bg-primary-50/30 dark:bg-primary-900/10': selectedIds.includes(prescription.id)}">
                                <!-- 1. Checkbox -->
                                <td class="px-4 py-4 text-center">
                                    <input 
                                        type="checkbox" 
                                        :checked="selectedIds.includes(prescription.id)"
                                        @change="toggleSelect(prescription.id)"
                                        class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700"
                                    >
                                </td>

                                <!-- 2. Dossier / Date -->
                                <td class="px-4 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-black text-slate-900 dark:text-white leading-tight">{{ prescription.reference }}</span>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase mt-0.5 tracking-tighter">{{ prescription.created_at }}</span>
                                        <div v-if="prescription.labo_traitement === 'AUTRE'" class="mt-1">
                                            <span class="inline-flex text-[9px] font-black px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 uppercase">
                                                {{ prescription.labo_autre_nom || 'Externe' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- 3. Patient & Prescripteur -->
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3 overflow-hidden">
                                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 font-black text-[11px] shadow-sm">
                                            {{ initials(prescription) }}
                                        </div>
                                        <div class="min-w-0 flex flex-col">
                                            <span class="truncate text-sm font-bold text-slate-900 dark:text-white leading-none mb-1.5">{{ prescription.patient?.nom_complet || 'N/A' }}</span>
                                            <span class="text-[11px] text-slate-500 font-medium flex items-center gap-1">
                                                <em class="ni ni-user text-[10px] text-slate-400"></em>
                                                <span class="truncate">{{ prescription.prescripteur?.nom || 'Sans prescripteur' }}</span>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- 4. Analyses -->
                                <td class="px-4 py-4 text-left">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-primary-50 dark:bg-primary-900/30 border border-primary-100 dark:border-primary-800" title="Total analyses">
                                            <span class="text-[11px] font-black text-primary-600 dark:text-primary-400">{{ prescription.analyses_count }}</span>
                                        </div>
                                        <div class="flex flex-wrap gap-1">
                                            <span v-for="(analyse, aIdx) in prescription.analyses.slice(0, 2)" :key="aIdx" 
                                                class="text-[9px] font-black px-1.5 py-0.5 rounded bg-slate-50 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 uppercase">
                                                {{ analyse.code }}
                                            </span>
                                            <span v-if="prescription.analyses.length > 2" class="text-[10px] font-bold text-slate-400 self-center">+{{ prescription.analyses.length - 2 }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- 5. Traçabilité (Personnel) -->
                                <td class="px-4 py-4 text-left">
                                    <div class="flex flex-col gap-1.5">
                                        <!-- Saisie -->
                                        <div class="flex items-center gap-2" :title="'Saisi par : ' + (prescription.secretaire?.name || 'Système')">
                                            <div class="h-5 w-5 flex-shrink-0 rounded bg-blue-50 text-blue-600 dark:bg-blue-900/30 flex items-center justify-center">
                                                <em class="icon ni ni-user-alt text-[9px]"></em>
                                            </div>
                                            <span class="text-[10px] font-bold text-slate-600 dark:text-slate-400 truncate max-w-[120px]">{{ prescription.secretaire?.name || 'Système' }}</span>
                                        </div>
                                        <!-- Traitement (Technicien) -->
                                        <div v-if="prescription.technicien && ['EN_COURS', 'TERMINE', 'A_REFAIRE', 'VALIDE'].includes(prescription.status)" class="flex items-center gap-2" :title="'Traité par : ' + prescription.technicien.name">
                                            <div class="h-5 w-5 flex-shrink-0 rounded bg-teal-50 text-teal-600 dark:bg-teal-900/30 flex items-center justify-center">
                                                <em class="icon ni ni-account-setting-fill text-[9px]"></em>
                                            </div>
                                            <span class="text-[10px] font-bold text-slate-600 dark:text-slate-400 truncate max-w-[120px]">{{ prescription.technicien.name }}</span>
                                        </div>
                                        <!-- Validation (Biologiste) -->
                                        <div v-if="prescription.validator && prescription.status === 'VALIDE'" class="flex items-center gap-2" :title="'Validé par : ' + prescription.validator.name">
                                            <div class="h-5 w-5 flex-shrink-0 rounded bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 flex items-center justify-center">
                                                <em class="icon ni ni-user-check-fill text-[9px]"></em>
                                            </div>
                                            <span class="text-[10px] font-bold text-slate-600 dark:text-slate-400 truncate max-w-[120px]">{{ prescription.validator.name }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- 6. Paiement -->
                                <td class="px-4 py-4 text-center">
                                    <div v-if="prescription.paiement" class="flex flex-col items-center gap-1.5">
                                        <!-- Toggle Switch -->
                                        <button v-if="perm.canEdit" type="button" class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800" :class="prescription.paiement.status ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'" @click="handlePaymentToggle(prescription)">
                                            <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200" :class="prescription.paiement.status ? 'translate-x-4' : 'translate-x-0'"></span>
                                        </button>
                                        <div class="flex flex-col items-center">
                                            <span class="text-[10px] font-black uppercase tracking-tight" :class="prescription.paiement.status ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                                                {{ prescription.paiement.status ? 'Payé' : 'Impayé' }}
                                            </span>
                                            <span v-if="prescription.paiement.status && prescription.paiement.date_paiement" class="text-[9px] text-slate-400 font-bold mt-0.5 leading-none">{{ prescription.paiement.date_paiement }}</span>
                                        </div>
                                    </div>
                                    <span v-else class="text-[10px] font-bold text-slate-300 italic">—</span>
                                </td>

                                <!-- 7. Statut -->
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest shadow-sm border border-transparent whitespace-nowrap" :class="statusClass(prescription.status)">
                                        {{ prescription.status_label }}
                                    </span>
                                </td>

                                <!-- 8. Actions (Placeholder for the next step or keep the old ones if they fit) -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center justify-end gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <!-- Edit + Delete: Only on Active tab -->
                                        <template v-if="form.tab === 'actives'">
                                            <Link v-if="perm.canEdit" :href="route('secretaire.prescription.edit', prescription.id)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:hover:bg-emerald-900/40 transition-colors" title="Modifier">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                            </Link>
                                            <button v-if="perm.canDelete" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 transition-colors" title="Supprimer" @click="openModal('delete', prescription.id)">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            </button>
                                        </template>
                                        <!-- Valide tab: PDF + Notify only -->
                                        <template v-if="form.tab === 'valide'">
                                            <a v-if="perm.canViewPrescription" :href="route('laboratoire.prescription.pdf', prescription.id)" target="_blank" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 transition-colors" title="Résultats PDF">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                            </a>

                                            <button v-if="perm.canEdit && $page.props.enabledFeatures?.notifications_sms_email_validated !== false" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg transition-colors" :class="prescription.notified_at ? 'bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-slate-50 text-slate-400 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-500'" :title="prescription.notified_at ? 'Notifié le ' + prescription.notified_at : 'Notifier le patient'" @click="openNotifyModal(prescription)">
                                                <svg class="h-4 w-4" :fill="prescription.notified_at ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                                            </button>

                                            <button v-if="perm.canAccessArchive" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-500 hover:bg-slate-100 dark:bg-slate-700 dark:text-slate-400 dark:hover:bg-slate-600 transition-colors" title="Archiver" @click="openModal('archive', prescription.id)">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                                            </button>
                                        </template>

                                        <!-- Actions for Autre Lab -->
                                        <template v-if="form.tab === 'autre_lab'">
                                            <button type="button" @click="openDetailModal(prescription)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-orange-50 text-orange-600 hover:bg-orange-100 dark:bg-orange-900/20 dark:text-orange-400 dark:hover:bg-orange-900/40 transition-colors" title="Voir détail">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.323 8.19 7.225 5 12 5c4.775 0 8.677 3.19 9.964 6.678.045.129.045.259 0 .388C20.677 15.81 16.775 19 12 19c-4.775 0-8.677-3.19-9.964-6.678z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            </button>
                                            <Link v-if="perm.canEdit" :href="route('secretaire.prescription.edit', prescription.id)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:hover:bg-emerald-900/40 transition-colors" title="Modifier">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                            </Link>
                                            <button v-if="perm.canDelete" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 transition-colors" title="Supprimer" @click="openModal('delete', prescription.id)">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            </button>
                                        </template>

                                        <!-- Deleted: restore + permanent delete -->
                                        <template v-else-if="form.tab === 'deleted'">
                                            <button v-if="perm.canRestore" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/40 transition-colors" title="Restaurer" @click="openModal('restore', prescription.id)">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                                            </button>
                                            <button v-if="perm.canForceDelete" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 transition-colors" title="Supprimer définitivement" @click="openModal('permanentDelete', prescription.id)">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="prescriptions.data.length === 0">
                                <td colspan="9" class="py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700">
                                            <svg class="h-5 w-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375H8.25" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Aucune prescription trouvée</p>
                                        <p v-if="form.search" class="mt-1 text-xs text-slate-400 dark:text-slate-500">Essayez de modifier vos critères de recherche</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-700/50 lg:hidden">
                    <div v-for="prescription in prescriptions.data" :key="`m-${prescription.id}`" class="space-y-3 p-4" :class="{'bg-primary-50/30 dark:bg-primary-900/10': selectedIds.includes(prescription.id)}">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <input 
                                    type="checkbox" 
                                    :checked="selectedIds.includes(prescription.id)"
                                    @change="toggleSelect(prescription.id)"
                                    class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700"
                                >
                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ prescription.reference }}</span>
                            </div>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(prescription.status)">{{ prescription.status_label }}</span>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary-400 to-primary-600">
                                <span class="text-[11px] font-semibold text-white">{{ initials(prescription) }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ prescription.patient?.nom_complet || 'N/A' }}</p>
                                <p class="text-xs text-slate-400">{{ prescription.prescripteur?.nom || 'N/A' }} · {{ prescription.patient?.telephone || '-' }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <div class="flex flex-col gap-2 flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <!-- Total Count Badge Mobile -->
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300 text-[10px] font-black border border-primary-200 dark:border-primary-800 shadow-sm mr-0.5">
                                        {{ prescription.analyses_count }}
                                    </span>

                                    <span v-for="(analyse, idx) in prescription.analyses.slice(0, 4)" :key="idx" 
                                        class="inline-flex items-center rounded bg-blue-50 px-1.5 py-0.5 text-[9px] font-bold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-100/50">
                                        {{ analyse.code }}
                                    </span>
                                    <span v-if="prescription.analyses.length > 4" class="text-[9px] font-bold text-slate-400 self-center">
                                        +{{ prescription.analyses.length - 4 }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                    <span v-if="prescription.paiement" :class="prescription.paiement.status ? 'text-emerald-500' : 'text-red-500'">
                                        {{ prescription.paiement.status ? 'Paye' : 'Non paye' }}
                                    </span>
                                    <span>{{ prescription.created_at }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <!-- Edit + Delete: Only on Active tab -->
                                <template v-if="form.tab === 'actives'">
                                    <Link v-if="perm.canEdit" :href="route('secretaire.prescription.edit', prescription.id)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:hover:bg-emerald-900/40 transition-colors" title="Modifier">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </Link>
                                    <button v-if="perm.canDelete" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 transition-colors" title="Supprimer" @click="openModal('delete', prescription.id)">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </button>
                                </template>
                                <!-- Valide tab: PDF + Notify only -->
                                <template v-if="form.tab === 'valide'">
                                    <a v-if="perm.canViewPrescription" :href="route('laboratoire.prescription.pdf', prescription.id)" target="_blank" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 transition-colors" title="Résultats PDF">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                    </a>

                                    <button v-if="perm.canEdit && $page.props.enabledFeatures?.notifications_sms_email_validated !== false" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg transition-colors" :class="prescription.notified_at ? 'bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-slate-50 text-slate-400 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-500'" :title="prescription.notified_at ? 'Notifié le ' + prescription.notified_at : 'Notifier le patient'" @click="openNotifyModal(prescription)">
                                        <svg class="h-4 w-4" :fill="prescription.notified_at ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                                    </button>

                                    <button v-if="perm.canAccessArchive" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-500 hover:bg-slate-100 dark:bg-slate-700 dark:text-slate-400 dark:hover:bg-slate-600 transition-colors" title="Archiver" @click="openModal('archive', prescription.id)">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                                    </button>
                                </template>

                                <!-- Deleted: restore + permanent delete -->
                                <template v-else-if="form.tab === 'deleted'">
                                    <button v-if="perm.canRestore" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/40 transition-colors" title="Restaurer" @click="openModal('restore', prescription.id)">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                                    </button>
                                    <button v-if="perm.canForceDelete" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 transition-colors" title="Supprimer définitivement" @click="openModal('permanentDelete', prescription.id)">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div v-if="prescriptions.data.length === 0" class="py-12 text-center">
                        <p class="text-sm text-slate-500">Aucune prescription trouvée</p>
                    </div>
                </div>

                <div v-if="prescriptions.links && prescriptions.links.length > 3" class="border-t border-slate-100 bg-slate-50/50 px-4 py-3 dark:border-slate-700/50 dark:bg-slate-800/50">
                    <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <span class="font-medium">{{ prescriptions.from || 0 }}</span> à
                            <span class="font-medium">{{ prescriptions.to || 0 }}</span> sur
                            <span class="font-medium">{{ prescriptions.total || 0 }}</span>
                        </p>
                        <Pagination :links="prescriptions.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Reusable Confirmation Modal -->
        <ConfirmModal
            :show="modal.show"
            :title="modalConfig.title"
            :description="modalConfig.description || modalConfig.desc"
            :confirm-text="modalConfig.btn"
            :variant="modalConfig.variant"
            :processing="modal.processing"
            @close="closeModal"
            @confirm="executeModalAction"
        />

        <!-- Notification Modal (SMS / Email) -->
        <Teleport to="body">
            <div v-if="notify.show" class="fixed inset-0 z-[60] overflow-y-auto">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeNotifyModal"></div>
                <div class="flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4">
                    <div class="relative w-full sm:max-w-lg rounded-t-2xl sm:rounded-2xl bg-white shadow-xl dark:bg-slate-800" @click.stop>
                        <div class="sm:hidden flex justify-center pt-3">
                            <div class="w-10 h-1 bg-slate-300 dark:bg-slate-600 rounded-full"></div>
                        </div>
                        <div class="p-5 sm:p-6">
                            <div class="flex items-start gap-3 mb-5">
                                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Notifier le patient</h3>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ notify.prescription?.reference }} — {{ notify.prescription?.patient?.nom_complet || 'Patient' }}</p>
                                </div>
                                <button type="button" class="ml-auto flex-shrink-0 p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:text-slate-300 dark:hover:bg-slate-700 transition-colors" @click="closeNotifyModal">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div v-if="!hasAnyContact" class="text-center py-6">
                                <div class="mx-auto w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                </div>
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Aucun moyen de contact</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Ce patient n'a ni téléphone ni email enregistré.</p>
                            </div>
                            <template v-else>
                                <div class="mb-4">
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Canal d'envoi</label>
                                    <div class="flex gap-2" :class="hasPhone && hasEmail ? 'grid grid-cols-2' : ''">
                                        <label v-if="hasPhone" class="relative flex items-center gap-3 rounded-xl border-2 p-3 cursor-pointer transition-all" :class="notify.channel === 'sms' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-400' : 'border-slate-200 dark:border-slate-600 hover:border-slate-300 dark:hover:border-slate-500'">
                                            <input type="radio" v-model="notify.channel" value="sms" class="sr-only" @change="setDefaultMessage">
                                            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center" :class="notify.channel === 'sms' ? 'bg-blue-500 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400'">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                            </div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">SMS</p>
                                        </label>
                                        <label v-if="hasEmail" class="relative flex items-center gap-3 rounded-xl border-2 p-3 cursor-pointer transition-all" :class="notify.channel === 'email' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-400' : 'border-slate-200 dark:border-slate-600 hover:border-slate-300 dark:hover:border-slate-500'">
                                            <input type="radio" v-model="notify.channel" value="email" class="sr-only" @change="setDefaultMessage">
                                            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center" :class="notify.channel === 'email' ? 'bg-blue-500 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400'">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                                            </div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Email</p>
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Message</label>
                                        <span v-if="notify.channel === 'sms'" class="text-[11px] font-medium" :class="notify.message.length > 160 ? 'text-red-500' : 'text-slate-400'">{{ notify.message.length }}/160</span>
                                    </div>
                                    <textarea v-model="notify.message" :rows="notify.channel === 'sms' ? 3 : 6" :maxlength="notify.channel === 'sms' ? 160 : undefined" class="w-full px-3 py-2.5 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-700/50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none transition-colors" placeholder="Saisissez votre message..."></textarea>
                                </div>
                            </template>
                            <div class="flex items-center justify-end gap-3" :class="{ 'mt-4': !hasAnyContact }">
                                <button type="button" class="h-10 px-5 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors" @click="closeNotifyModal">{{ hasAnyContact ? 'Annuler' : 'Fermer' }}</button>
                                <button v-if="hasAnyContact" type="button" class="h-10 px-5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-lg shadow-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2" :disabled="!notify.message.trim() || notify.processing || !notify.channel || (notify.channel === 'sms' && notify.message.length > 160)" @click="sendNotification">
                                    <svg v-if="notify.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <span>{{ notify.processing ? 'Envoi...' : (notify.channel === 'sms' ? 'Envoyer' : 'Envoyer') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
        <!-- Detail Modal (Autre Lab) -->
        <Teleport to="body">
            <div v-if="detail.show" class="fixed inset-0 z-[60] overflow-y-auto">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeDetailModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-800" @click.stop>
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-4 dark:border-slate-700 dark:bg-slate-900/50">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-900/30">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Détail Prescription Externe</h3>
                                    <p class="text-xs font-medium text-slate-500">{{ detail.prescription?.reference }} — {{ detail.prescription?.labo_autre_nom || 'Laboratoire Externe' }}</p>
                                </div>
                            </div>
                            <button type="button" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700" @click="closeDetailModal">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <!-- Modal Body (Printable area) -->
                        <div id="printable-detail" class="p-6">
                            <!-- Header for Print only -->
                            <div class="hidden print:block mb-8 border-b-2 border-slate-900 pb-4">
                                <h1 class="text-2xl font-black uppercase tracking-tighter">Fiche de Prescription Externe</h1>
                                <div class="mt-2 flex justify-between text-sm font-bold">
                                    <span>Réf: {{ detail.prescription?.reference }}</span>
                                    <span>Date: {{ detail.prescription?.created_at }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Patient Info -->
                                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/30">
                                    <h4 class="mb-3 text-[10px] font-black uppercase tracking-widest text-slate-400">Informations Patient</h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-xs text-slate-500">Nom Complet:</span>
                                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ detail.prescription?.patient?.nom_complet }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-xs text-slate-500">Âge / Sexe:</span>
                                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ detail.prescription?.age }} {{ detail.prescription?.unite_age }} • {{ detail.prescription?.patient?.civilite }}</span>
                                        </div>
                                        <div v-if="detail.prescription?.patient?.telephone" class="flex justify-between">
                                            <span class="text-xs text-slate-500">Téléphone:</span>
                                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ detail.prescription?.patient?.telephone }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Medical Context -->
                                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/30">
                                    <h4 class="mb-3 text-[10px] font-black uppercase tracking-widest text-slate-400">Contexte Médical</h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-xs text-slate-500">Prescripteur:</span>
                                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ detail.prescription?.prescripteur?.nom }}</span>
                                        </div>
                                        <div v-if="detail.prescription?.poids" class="flex justify-between">
                                            <span class="text-xs text-slate-500">Poids:</span>
                                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ detail.prescription?.poids }} Kg</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-xs text-slate-500">Laboratoire:</span>
                                            <span class="text-sm font-black text-orange-600 uppercase">{{ detail.prescription?.labo_autre_nom || 'Externe' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Clinical Info -->
                            <div v-if="detail.prescription?.renseignement_clinique" class="mt-6 rounded-xl border border-slate-100 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                <h4 class="mb-2 text-[10px] font-black uppercase tracking-widest text-slate-400">Renseignements Cliniques</h4>
                                <p class="text-sm text-slate-700 dark:text-slate-300 italic">"{{ detail.prescription.renseignement_clinique }}"</p>
                            </div>

                            <!-- Analysis Table -->
                            <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                                        <tr>
                                            <th class="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-500">Code</th>
                                            <th class="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-500">Désignation</th>
                                            <th class="px-4 py-2 text-right text-[10px] font-black uppercase tracking-widest text-slate-500">Prix</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                        <tr v-for="analyse in detail.prescription?.analyses" :key="analyse.code">
                                            <td class="px-4 py-3 font-mono text-xs font-bold text-primary-600">{{ analyse.code }}</td>
                                            <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ analyse.designation }}</td>
                                            <td class="px-4 py-3 text-right font-black tabular-nums">{{ formatCurrency(analyse.prix) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-slate-900 text-white dark:bg-black">
                                        <tr>
                                            <td colspan="2" class="px-4 py-3 text-right text-xs font-bold uppercase tracking-widest opacity-60">Total Prescription</td>
                                            <td class="px-4 py-3 text-right text-base font-black tabular-nums text-primary-400">
                                                {{ formatCurrency(detail.prescription?.analyses.reduce((t, a) => t + Number(a.prix), 0)) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/50 px-6 py-4 dark:border-slate-700 dark:bg-slate-900/50">
                            <button type="button" class="text-sm font-bold text-slate-500 hover:text-slate-700" @click="closeDetailModal">Fermer</button>
                            <button type="button" @click="printDetail" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg transition-all hover:bg-primary-700 active:scale-95">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                                Générer PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
@media print {
    body * {
        visibility: hidden;
    }
    #printable-detail, #printable-detail * {
        visibility: visible;
    }
    #printable-detail {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 0;
        margin: 0;
    }
    /* Hide scrollbars and other UI elements during print */
    .scrollbar-thin {
        overflow: visible !important;
    }
}
.animate-in {
    animation-duration: 0.3s;
    animation-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-in-from-top-2 {
    animation-name: slide-in-from-top-2;
}
@keyframes slide-in-from-top-2 {
    from {
        transform: translateY(-0.5rem);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>
