<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import axios from 'axios';
import dayjs from 'dayjs';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';

const toast = useToast();
const page = usePage();
const dtSC = ref();
const loading = ref(false);
const year = ref();
const month = ref();
let opexDef = ref(6);
let proginDef = ref(5);
const tempOpex = ref<number | null>(null);
const tempProgin = ref<number | null>(null);
const monthRange = ref(null);
const selectStandardPeriod = ref<StandardPeriod | null>(null);
const updateReportDialog = ref(false);
const updateConstDialog = ref(false);
type UpdateStatus = 'idle' | 'updating' | 'done';
const updateStatus = ref<UpdateStatus>('idle');
const updateType = ref<'standardCost' | 'opgin' | null>(null);
const selectionModeType = ref('single');
const activeTabValue = ref('0');
const userName = computed(() => page.props.auth?.user?.name ?? '');

const standardCostUrl = ref('/finacc/api/standard/get-std-cost');
const perPage = ref(10);
const currentStdCostPage = ref(1);
const sortFieldStdCost = ref(null);
const sortOrderStdCost = ref(null);
const paginatedStdCostData = ref<any>(null);
const searchTimeout = ref<number | null>(null);
const listStandardPeriod = ref<List[]>([]);
const listStandardType = ref<List[]>([]);

const isReady = reactive({
    standardCost: false,
});
const standardCostTimeout = ref<ReturnType<typeof setTimeout> | null>(null);

const isInitialLoading = ref(true);

interface List {
    name: string;
    code: string;
}
function tbStyle(section: 'main' | 'rm' | 'pr' | 'wip' | 'fg') {
    const styles = {
        main: { header: '#758596', body: '#c8cccc' },
        rm: { header: '#2c7a7b', body: '#e6fffa' },
        pr: { header: '#6b46c1', body: '#faf5ff' },
        wip: { header: '#d69e2e', body: '#fffaf0' },
        fg: { header: '#2b6cb0', body: '#ebf8ff' },
    };
    const color = styles[section] || styles.main;
    return {
        headerStyle: { backgroundColor: color.header, color: 'white' },
        bodyStyle: { backgroundColor: color.body, color: 'black' },
    };
}

function getSeverity(type: string) {
    switch (type) {
        case 'Disc':
            return 'success';
        case 'Sidering':
            return 'info';
        case 'Wheel':
            return 'warning';
        case 'Assy Tire':
            return 'primary';
        case 'Rim':
            return 'secondary';
        case 'Material':
            return 'contrast';
        default:
            return 'danger';
    }
}

const props = defineProps({
    auth: Object,
});

const currentSelectionMode = computed(() => {
    return selectionModeType.value === 'range' ? 'range' : 'single';
});

const filtersStandard = ref({
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    type_name: { value: null, matchMode: FilterMatchMode.EQUALS },
    'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
    period: { value: null as string | null, matchMode: FilterMatchMode.EQUALS },
});

interface StandardPeriod {
    name: string;
    code: string;
}

watch(selectStandardPeriod, (newValue) => {
    if (newValue) {
        const year = newValue.code;

        filtersStandard.value.period.value = year;
    } else {
        // Reset filter jika Select dikosongkan
        filtersStandard.value.period.value = null;
    }
});

async function exportCSV(type: string) {
    if (type === 'standardCost') {
        const currentPeriod = filtersStdCost.value.period.value;

        try {
            // 1. Ambil data lengkap dari endpoint JSON Anda
            const response = await axios.get(route('report.standard.export'), {
                params: { period_filter: currentPeriod },
            });

            const data = response.data;

            if (!data || data.length === 0) {
                toast.add({ severity: 'warn', summary: 'Info', detail: 'Tidak ada data untuk diexport', life: 3000 });
                return;
            }

            // 2. Konversi JSON ke CSV
            const csvContent = convertToCSV(data);

            // 3. Trigger Download
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);

            link.setAttribute('href', url);
            link.setAttribute('download', `StandardCost_${currentPeriod || 'All'}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } catch (error) {
            console.error('Export failed', error);
            toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal mengambil data export', life: 3000 });
        }
    }
}

function convertToCSV(objArray) {
    const array = typeof objArray !== 'object' ? JSON.parse(objArray) : objArray;
    let str = '';

    // Ambil Header (Keys) otomatis
    const headers = Object.keys(array[0]);
    str += headers.join(',') + '\r\n';

    // Ambil Data (Values)
    for (let i = 0; i < array.length; i++) {
        let line = '';
        for (let index in headers) {
            if (line !== '') line += ',';

            // Handle jika data mengandung koma agar tidak merusak format CSV
            const value = array[i][headers[index]] + '';
            line += `"${value.replace(/"/g, '""')}"`;
        }
        str += line + '\r\n';
    }
    return str;
}

const lastMaster = computed(() => page.props.lastMaster as any);

function formatlastUpdate(date: Date | string) {
    return dayjs(date).format('DD MMM YYYY HH:mm:ss');
}

const saveOpexProgin = () => {
    if (tempOpex.value !== null && tempProgin.value !== null) {
        opexDef.value = tempOpex.value;
        proginDef.value = tempProgin.value;
    }
    updateConstDialog.value = false;
};

const cancelOpexProgin = () => {
    updateConstDialog.value = false; // Tutup dialog
};

function showUpdateDialog(type: 'standardCost' | 'opgin') {
    updateType.value = type;
    updateStatus.value = 'idle';

    if (updateType.value === 'opgin') {
        nextTick(() => {
            tempOpex.value = opexDef.value;
            tempProgin.value = proginDef.value;
            updateConstDialog.value = true;
        });
    } else {
        nextTick(() => {
            updateReportDialog.value = true;
        });
    }
}

const validationErrors = ref({
    sc: '',
});

function confirmUpdate() {
    if (!updateType.value) return;
    let payload = {};
    let SCupdatePeriod: string | null = null;

    if (updateType.value === 'standardCost') {
        if (!year.value) {
            validationErrors.value.sc = 'Year cannot be empty.';
            return;
        }
        const yearValue = year.value.getFullYear();
        SCupdatePeriod = yearValue.toString();

        payload = {
            year: year.value.getFullYear(),
        };
    } else if (updateType.value === 'opgin') {
    }

    if (Object.keys(payload).length === 0 && updateType.value === 'opgin') {
    }

    updateStatus.value = 'updating';
    const type = updateType.value;

    const routes = {
        standardCost: 'report.standard.update',
        opgin: 'report.standard.update-opgin',
    };

    const messages = {
        standardCost: 'Standard Cost',
        opgin: 'OPEX / Profit Margin',
    };

    router.post(
        route(routes[type]),
        payload, // payload kini selalu terdefinisi
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                updateStatus.value = 'done';
                if (type === 'standardCost' && SCupdatePeriod) {
                }
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    group: 'br',
                    detail: `${messages[type]} updated successfully`,
                    life: 3000,
                });
                loadLazyData(standardCostUrl.value, 'standardCost');
                if (type === 'standardCost' && SCupdatePeriod) {
                    // Set nilai filter period agar sesuai dengan tahun yang baru diupdate
                    filtersStdCost.value.period.value = SCupdatePeriod;
                }
            },
            onError: () => {
                updateStatus.value = 'idle';

                toast.add({
                    severity: 'warn',
                    summary: 'Error',
                    group: 'br',
                    detail: `Failed to update ${messages[type]}`,
                    life: 3000,
                });
            },
        },
    );
}

function closeDialog() {
    updateReportDialog.value = false;
    updateStatus.value = 'idle';
    updateType.value = null;
    validationErrors.value = {
        sc: '',
    };

    year.value = null;
    month.value = null;
    monthRange.value = null;
}

function openPreviewTab(item_code: string, opex: number, progin: number, previewType: string) {
    let previewUrl;

    previewUrl = route('report.standard.preview', {
        item_code: item_code,
        opex: opex,
        progin: progin,
    });

    window.open(previewUrl, '_blank');
}

const fetchDatas = async (url: string) => {
    try {
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();
        return data;
    } catch (e: any) {
        console.error(`Fetch Period Error from ${url}:`, e);
        throw new Error(`Fail to retrieve data from ${url}: ${e.message}`);
    }
};

const fetchStandardPeriod = async () => {
    try {
        const standardPeriod = await fetchDatas('/finacc/api/standard/list-period');

        if (Array.isArray(standardPeriod)) {
            listStandardPeriod.value = standardPeriod.map((p: string) => ({ name: p, code: p }));
        } else {
            console.error('API /api/standard/list-period did not return an array:', standardPeriod);
            listStandardPeriod.value = [];
        }
    } catch (error) {
        console.error('Failed to fetch material group list:', error);
        listStandardPeriod.value = [];
    }
};

const fetchLatestPeriod = async () => {
    try {
        const latest = await fetchDatas('/finacc/api/standard/latest-period');
        if (latest) {
            filtersStdCost.value.period.value = latest;
        }
    } catch (error) {
        console.error('Failed to fetch latest period:', error);
    }
};

const fetchStandardType = async () => {
    try {
        const standardType = await fetchDatas('/finacc/api/standard/list-type');

        if (Array.isArray(standardType)) {
            listStandardType.value = standardType.map((p: string) => ({ name: p, code: p }));
        } else {
            console.error('API /api/standard/list-type did not return an array:', standardType);
            listStandardType.value = [];
        }
    } catch (error) {
        console.error('Failed to fetch material group list:', error);
        listStandardType.value = [];
    }
};

const filtersStdCost = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    period: { value: null, matchMode: FilterMatchMode.EQUALS },
    'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
    type: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const initFilters = () => {
    // Reset filters Stamat (karena ini yang digunakan oleh Tab 0)
    filtersStdCost.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
        period: { value: null, matchMode: FilterMatchMode.EQUALS },
        'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
        type: { value: null, matchMode: FilterMatchMode.EQUALS },
    };
};

const controllers = {
    standardCost: null as AbortController | null,
};

const loadingStates = reactive({
    standardCost: false,
});

const loadLazyData = async (url: string, type: 'standardCost') => {
    if (controllers[type]) controllers[type].abort();
    controllers[type] = new AbortController();

    loadingStates[type] = true;

    const configMap = {
        standardCost: {
            filters: filtersStdCost.value,
            page: currentStdCostPage.value,
            descKey: 'bom.description',
            sortField: sortFieldStdCost.value,
            sortOrder: sortOrderStdCost.value,
            dataRef: paginatedStdCostData,
        },
    };

    const config = configMap[type];
    const currentFilters = config.filters;
    const params = new URLSearchParams({
        page: config.page.toString(),
        per_page: perPage.value.toString(),
        search: currentFilters.global.value || '',
        item_code_filter: currentFilters.item_code.value || '',
        description_filter: (currentFilters as any)[config.descKey]?.value || '',
    });

    if (config.sortField && config.sortOrder) {
        params.append('sort_field', config.sortField);
        params.append('sort_order', config.sortOrder === 1 ? 'asc' : 'desc');
    }

    if (type === 'standardCost') {
        const itemPeriod = (currentFilters as any).period?.value;
        const itemType = (currentFilters as any).type?.value;

        if (itemPeriod) params.append('period_filter', itemPeriod);

        if (itemType) {
            if (Array.isArray(itemType)) {
                // Mengirim sebagai type_filter[] agar Laravel membacanya sebagai array
                itemType.forEach((val) => params.append('type_filter[]', val));
            } else {
                params.append('type_filter', itemType);
            }
        }
    }

    try {
        const response = await fetch(`${url}?${params.toString()}`, {
            signal: controllers[type].signal,
        });
        // console.log(`${url}?${params.toString()}`);

        if (!response.ok) throw new Error('Fetch failed');
        const data = await response.json();
        config.dataRef.value = data;
    } catch (error: any) {
        if (error.name === 'AbortError') return;
        console.error(`Failed to fetch ${type} data:`, error);
    } finally {
        loadingStates[type] = false;
    }
};

const onLazyLoadStdCost = (event: any) => {
    const { first, rows, sortField, sortOrder } = event;

    const newPage = Math.floor(first / rows) + 1;
    perPage.value = rows;

    if (sortFieldStdCost.value !== sortField) {
        currentStdCostPage.value = 1;
    } else {
        currentStdCostPage.value = newPage;
    }

    sortFieldStdCost.value = sortField || null;
    sortOrderStdCost.value = sortOrder || null;

    loadLazyData(standardCostUrl.value, 'standardCost');
};

watch(
    filtersStdCost,
    () => {
        if (!isReady.standardCost) {
            isReady.standardCost = true;
            return;
        }

        if (standardCostTimeout.value) clearTimeout(standardCostTimeout.value);
        standardCostTimeout.value = setTimeout(() => {
            loadLazyData(standardCostUrl.value, 'standardCost');
        }, 500);
    },
    { deep: true },
);

onMounted(async () => {
    initFilters();
    try {
        await fetchStandardType();
        await fetchStandardPeriod();
        await fetchLatestPeriod();
        await loadLazyData(standardCostUrl.value, 'standardCost');
    } finally {
        isInitialLoading.value = false;
    }
});

const clearFilter = (type: 'standardCost') => {
    if (type === 'standardCost') {
        filtersStdCost.value = {
            global: { value: null, matchMode: FilterMatchMode.CONTAINS },
            item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
            period: { value: null, matchMode: FilterMatchMode.EQUALS },
            'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
            type: { value: null, matchMode: FilterMatchMode.EQUALS },
        };
        currentStdCostPage.value = 1;
    }
};

const columnConfigs = [
    // Raw Material Group
    { field: 'disc_qty', header: 'Qty', type: 'qty', style: 'rm' },
    { field: 'disc_code', header: 'Disc', type: 'text', style: 'rm' },
    { field: 'disc_price', header: 'Price', type: 'price', style: 'rm' },
    { field: 'rim_qty', header: 'Qty', type: 'qty', style: 'rm' },
    { field: 'rim_code', header: 'Rim', type: 'text', style: 'rm' },
    { field: 'rim_price', header: 'Price', type: 'price', style: 'rm' },
    { field: 'sidering_qty', header: 'Qty', type: 'qty', style: 'rm' },
    { field: 'sidering_code', header: 'Sidering', type: 'text', style: 'rm' },
    { field: 'sidering_price', header: 'Price', type: 'price', style: 'rm' },

    // Process Group
    { field: 'pr_disc', header: 'Pr Disc', type: 'text', style: 'pr' },
    { field: 'pr_disc_price', header: 'Price', type: 'price', style: 'pr' },
    { field: 'pr_rim', header: 'Pr Rim', type: 'text', style: 'pr' },
    { field: 'pr_rim_price', header: 'Price', type: 'price', style: 'pr' },
    { field: 'pr_sidering', header: 'Pr Sidering', type: 'text', style: 'pr' },
    { field: 'pr_sidering_price', header: 'Price', type: 'price', style: 'pr' },
    { field: 'pr_assy', header: 'Pr Assy', type: 'text', style: 'pr' },
    { field: 'pr_assy_price', header: 'Price', type: 'price', style: 'pr' },
    { field: 'pr_cedW', header: 'Pr CED W', type: 'text', style: 'pr' },
    { field: 'pr_cedW_price', header: 'Price', type: 'price', style: 'pr' },
    { field: 'pr_cedSR', header: 'Pr CED SR', type: 'text', style: 'pr' },
    { field: 'pr_cedSR_price', header: 'Price', type: 'price', style: 'pr' },
    { field: 'pr_tcW', header: 'Pr Topcoat W', type: 'text', style: 'pr' },
    { field: 'pr_tcW_price', header: 'Price', type: 'price', style: 'pr' },
    { field: 'pr_tcSR', header: 'Pr tcSR', type: 'text', style: 'pr' },
    { field: 'pr_tcSR_price', header: 'Price', type: 'price', style: 'pr' },
    { field: 'pack_price', header: 'Packing Price', type: 'price', style: 'pr' },

    // WIP Group
    { field: 'wip_disc', header: 'WiP Disc', type: 'text', style: 'wip' },
    { field: 'wip_disc_price', header: 'Cost', type: 'price', style: 'wip' },
    { field: 'wip_rim', header: 'WiP Rim', type: 'text', style: 'wip' },
    { field: 'wip_rim_price', header: 'Cost', type: 'price', style: 'wip' },
    { field: 'wip_sidering', header: 'WiP Sidering', type: 'text', style: 'wip' },
    { field: 'wip_sidering_price', header: 'Cost', type: 'price', style: 'wip' },
    { field: 'wip_assy', header: 'WiP Assy', type: 'text', style: 'wip' },
    { field: 'wip_assy_price', header: 'Cost', type: 'price', style: 'wip' },
    { field: 'wip_cedW', header: 'WiP CED W', type: 'text', style: 'wip' },
    { field: 'wip_cedW_price', header: 'Cost', type: 'price', style: 'wip' },
    { field: 'wip_cedSR', header: 'WiP CED SR', type: 'text', style: 'wip' },
    { field: 'wip_cedSR_price', header: 'Cost', type: 'price', style: 'wip' },
    { field: 'wip_tcW', header: 'WiP Topcoat W', type: 'text', style: 'wip' },
    { field: 'wip_tcW_price', header: 'Cost', type: 'price', style: 'wip' },
    { field: 'wip_tcSR', header: 'WiP Topcoat SR', type: 'text', style: 'wip' },
    { field: 'wip_tcSR_price', header: 'Cost', type: 'price', style: 'wip' },
    { field: 'wip_valve', header: 'WiP Valve', type: 'text', style: 'wip' },
    { field: 'wip_valve_price', header: 'Cost', type: 'price', style: 'wip' },

    // Final Totals
    { field: 'total_raw_material', header: 'Total Raw Material', type: 'price', style: 'fg' },
    { field: 'total_process', header: 'Total Process', type: 'price', style: 'fg' },
    { field: 'total', header: 'Total', type: 'price', style: 'fg' },
];

const formatCurrency = (value: any) => {
    if (value === null || value === undefined) return '0';
    return Number(value).toLocaleString('id-ID');
};
</script>

<template>
    <Head title="Standard Cost" />
    <AppLayout>
        <div class="p-6">
            <div class="flex flex-col gap-1">
                <h2 class="mb-2 text-start text-3xl font-bold text-gray-900 dark:text-white">Standard Cost</h2>
                <p class="text-start text-gray-600 dark:text-gray-400">Generate Standard Cost Calculation</p>
            </div>

            <div class="mb-8">
                <div class="relative mb-6 text-center">
                    <h1 class="relative z-1 inline-block bg-white px-4 text-2xl font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        Report Section
                    </h1>
                    <hr class="absolute top-1/2 left-0 z-0 w-full -translate-y-1/2 border-gray-300 dark:border-gray-600" />
                </div>
            </div>

            <Dialog
                v-model:visible="updateReportDialog"
                header="Update Confirmation"
                modal
                class="w-11/12 md:w-1/2 lg:w-1/3"
                :closable="false"
                @hide="closeDialog"
            >
                <template v-if="updateStatus === 'idle'">
                    <div class="space-y-4">
                        <p>
                            Hi <span class="text-red-400">{{ userName }}</span
                            >,
                        </p>
                        <p>Are you sure you want to update the report?</p>
                        <div>
                            <div class="mt-6 mb-2 font-semibold">Select Standard Price Period:</div>
                            <div class="flex space-x-4">
                                <div class="flex-1">
                                    <DatePicker
                                        v-model="year"
                                        view="year"
                                        dateFormat="yy"
                                        :showClear="true"
                                        :selectionMode="currentSelectionMode"
                                        placeholder="Select year"
                                    />
                                </div>
                            </div>
                            <p v-if="validationErrors.sc" class="mt-2 inline-block rounded bg-red-500 px-2 py-1 text-sm font-medium text-white">
                                {{ validationErrors.sc }}
                            </p>
                        </div>

                        <div v-if="updateType == 'standardCost'">
                            <p class="mt-6 mb-2 font-semibold">Make sure this data is up to date:</p>
                            <div class="overflow-x-auto">
                                <table v-if="updateType === 'standardCost' || updateType === 'actualCost'" class="w-full border-collapse text-left">
                                    <thead>
                                        <tr>
                                            <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Data</th>
                                            <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Last Update</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="border-b border-gray-800 px-4 py-2">Standard Material Price</td>
                                            <td class="border-b border-gray-800 px-4 py-2">
                                                <span class="text-red-300">{{ lastMaster[0] ? formatlastUpdate(lastMaster[0]) : '-' }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="border-b border-gray-800 px-4 py-2">Valve Price</td>
                                            <td class="border-b border-gray-800 px-4 py-2">
                                                <span class="text-red-300">{{ lastMaster[1] ? formatlastUpdate(lastMaster[1]) : '-' }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="border-b border-gray-800 px-4 py-2">Bill of Material</td>
                                            <td class="border-b border-gray-800 px-4 py-2">
                                                <span class="text-red-300">{{ lastMaster[2] ? formatlastUpdate(lastMaster[2]) : '-' }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="border-b border-gray-800 px-4 py-2">Process Cost</td>
                                            <td class="border-b border-gray-800 px-4 py-2">
                                                <span class="text-red-300">{{ lastMaster[3] ? formatlastUpdate(lastMaster[3]) : '-' }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <Button
                                label=" Cancel"
                                icon="pi pi-times"
                                unstyled
                                class="w-28 cursor-pointer rounded-xl bg-red-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-red-700"
                                @click="closeDialog"
                            />
                            <Button
                                label=" Yes, Update"
                                icon="pi pi-check"
                                unstyled
                                class="w-36 cursor-pointer rounded-xl bg-emerald-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-emerald-700"
                                :loading="updateStatus.value === 'updating'"
                                @click="confirmUpdate"
                            />
                        </div>
                    </div>
                </template>

                <!-- Done state -->
                <template v-else-if="updateStatus === 'done'">
                    <div class="space-y-4">
                        <p>
                            Hi <span class="text-red-400">{{ userName }}</span
                            >,
                        </p>
                        <p>
                            <strong class="text-green-500">Finished</strong> updating the report.<br />
                            It’s now safe to close this window.
                        </p>

                        <div class="flex justify-end pt-4">
                            <Button label="Close" icon="pi pi-times" @click="closeDialog" />
                        </div>
                    </div>
                </template>

                <!-- Updating state (optional animation) -->
                <template v-else>
                    <div class="flex flex-col items-center space-y-4 text-center">
                        <i class="pi pi-spin pi-spinner text-4xl text-primary" />
                        <p class="font-medium">Updating report...</p>
                    </div>
                </template>
            </Dialog>

            <Dialog v-model:visible="updateConstDialog" header="Edit OPEX & Profit Margin" modal class="w-[25rem]" :closable="false">
                <div class="space-y-4">
                    <div class="flex flex-col gap-2">
                        <label for="tempOpex">OPEX (%):</label>
                        <InputNumber v-model="tempOpex" inputId="tempOpex" suffix="%" fluid />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="tempProgin">Profit Margin (%):</label>
                        <InputNumber v-model="tempProgin" inputId="tempProgin" suffix="%" fluid />
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-6">
                    <Button
                        label=" Cancel"
                        icon="pi pi-times"
                        unstyled
                        class="w-28 cursor-pointer rounded-xl bg-red-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-red-700"
                        @click="cancelOpexProgin"
                    />
                    <Button
                        label=" Save"
                        icon="pi pi-check"
                        unstyled
                        class="w-28 cursor-pointer rounded-xl bg-emerald-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-emerald-700"
                        @click="saveOpexProgin"
                    />
                </div>
            </Dialog>

            <div class="mx-26 mb-26">
                <Tabs v-model="activeTabValue">
                    <TabList>
                        <Tab value="0">Standard Cost</Tab>
                    </TabList>

                    <TabPanels>
                        <TabPanel value="0">
                            <section class="p-2">
                                <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <!-- Title -->
                                    <h2 class="text-3xl font-semibold text-gray-900 dark:text-white">Standard Cost</h2>
                                </div>

                                <!-- Last Update Info -->
                                <div class="mb-4 text-right text-gray-700 dark:text-gray-300">
                                    <div>
                                        Last Update :
                                        <span class="text-red-300">{{ lastMaster[4] ? formatlastUpdate(lastMaster[4]) : '-' }}</span>
                                    </div>
                                </div>

                                <DataTable
                                    :value="paginatedStdCostData?.data || []"
                                    :lazy="true"
                                    :totalRecords="paginatedStdCostData?.total || 0"
                                    :rows="perPage"
                                    @page="onLazyLoadStdCost"
                                    @sort="onLazyLoadStdCost"
                                    :first="(currentStdCostPage - 1) * perPage"
                                    :paginator="true"
                                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                                    currentPageReportTemplate="Showing {first} to {last} from {totalRecords} data"
                                    responsiveLayout="scroll"
                                    :globalFilterFields="['item_code', 'type', 'description']"
                                    showGridlines
                                    :removableSort="true"
                                    v-model:filters="filtersStdCost"
                                    filterDisplay="row"
                                    :loading="loadingStates.standardCost || isInitialLoading"
                                    ref="dtStdCost"
                                >
                                    <template #header>
                                        <div class="flex justify-between">
                                            <div class="flex justify-start space-x-2">
                                                <div class="flex flex-col gap-2 sm:flex-row sm:gap-4">
                                                    <Button
                                                        icon="pi pi-download"
                                                        label=" Export Report"
                                                        unstyled
                                                        class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-orange-700 sm:w-auto"
                                                        @click="exportCSV('standardCost')"
                                                    />
                                                    <Button
                                                        v-if="auth?.user?.permissions?.includes('Update_Report')"
                                                        icon="pi pi-sync"
                                                        label=" Update Report?"
                                                        unstyled
                                                        class="w-full cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-cyan-700 sm:w-auto"
                                                        @click="showUpdateDialog('standardCost')"
                                                    />
                                                </div>

                                                <!-- OPEX and Profit Margin Section -->
                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                                                    <div class="flex flex-col gap-2">
                                                        <label for="opex" class="block text-sm font-medium text-gray-400">OPEX :</label>
                                                        <InputNumber v-model="opexDef" inputId="percent" suffix="%" fluid disabled class="w-20" />
                                                    </div>
                                                    <div class="flex flex-col gap-2">
                                                        <label for="progin" class="block text-sm font-medium text-gray-400">Profit Margin :</label>
                                                        <InputNumber v-model="proginDef" inputId="percent" suffix="%" fluid disabled class="w-20" />
                                                    </div>
                                                    <Button
                                                        icon="pi pi-sync"
                                                        label=" Update Value?"
                                                        unstyled
                                                        class="w-full cursor-pointer rounded-xl bg-emerald-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-emerald-700 sm:w-auto"
                                                        @click="showUpdateDialog('opgin')"
                                                    />
                                                </div>
                                            </div>
                                            <div class="justify-end">
                                                <div class="flex justify-between space-x-2">
                                                    <Button
                                                        type="button"
                                                        icon="pi pi-filter-slash"
                                                        label=" Clear"
                                                        unstyled
                                                        class="w-full cursor-pointer rounded-xl bg-slate-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-slate-700 sm:w-auto"
                                                        @click="clearFilter('standardCost')"
                                                    />

                                                    <IconField>
                                                        <InputIcon>
                                                            <i class="pi pi-search" />
                                                        </InputIcon>
                                                        <InputText v-model="filtersStdCost['global'].value" placeholder="Keyword Search" />
                                                    </IconField>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <template #empty>
                                        <div v-if="!loading && !isInitialLoading" class="flex justify-center p-4">No data found.</div>
                                        <div v-else class="flex justify-center p-4">Loading data, please wait...</div>
                                    </template>

                                    <Column
                                        field="item_code"
                                        header="Item Code"
                                        headerClass="justify-content-center"
                                        class="text-center"
                                        :showFilterMenu="false"
                                        sortable
                                        v-bind="tbStyle('main')"
                                    >
                                        <template #body="{ data }">
                                            <div class="flex w-full justify-center">
                                                {{ data.item_code }}
                                            </div>
                                        </template>
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search item code"
                                                class="w-full"
                                            />
                                        </template>
                                    </Column>

                                    <Column field="period" sortable v-bind="tbStyle('main')" :showFilterMenu="false">
                                        <template #header>
                                            <div class="flex w-full justify-center font-bold">
                                                <span>Period</span>
                                            </div>
                                        </template>
                                        <template #body="{ data }">
                                            <div class="flex w-full justify-center">
                                                {{ data.period }}
                                            </div>
                                        </template>
                                        <template #filter="{ filterModel, filterCallback }">
                                            <div class="flex flex-col justify-center">
                                                <Select
                                                    v-model="filterModel.value"
                                                    :options="listStandardPeriod"
                                                    optionLabel="name"
                                                    optionValue="name"
                                                    placeholder="Standard Period"
                                                    class="w-full md:w-20"
                                                    :showClear="true"
                                                    @change="filterCallback()"
                                                />
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="type" sortable v-bind="tbStyle('main')" :showFilterMenu="false">
                                        <template #header>
                                            <div class="flex w-full justify-center font-bold">
                                                <span>Type</span>
                                            </div>
                                        </template>
                                        <template #body="{ data }">
                                            <div class="flex w-full justify-center">
                                                <Tag :value="data.type" :severity="getSeverity(data.type)" />
                                            </div>
                                        </template>

                                        <template #filter="{ filterModel, filterCallback }">
                                            <div class="flex flex-col">
                                                <MultiSelect
                                                    v-model="filterModel.value"
                                                    display="chip"
                                                    :options="listStandardType"
                                                    optionLabel="name"
                                                    optionValue="name"
                                                    filter
                                                    placeholder="Standard Type"
                                                    class="w-full md:w-40"
                                                    :showClear="true"
                                                    @change="filterCallback()"
                                                />
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="bom.description" :showFilterMenu="false" sortable v-bind="tbStyle('main')">
                                        <template #header>
                                            <div class="flex w-full justify-center font-bold">
                                                <span>Description</span>
                                            </div>
                                        </template>
                                        <template #body="{ data }">
                                            <div class="flex w-full">
                                                {{ data.bom ? data.bom.description : 'N/A' }}
                                            </div>
                                        </template>
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search description"
                                                class="w-full md:w-60"
                                            />
                                        </template>
                                    </Column>

                                    <Column v-for="col in columnConfigs" :key="col.field" :field="col.field" sortable v-bind="tbStyle(col.style)">
                                        <template #header>
                                            <div class="flex w-full justify-center text-center font-bold">
                                                <span>{{ col.header }}</span>
                                            </div>
                                        </template>

                                        <template #body="{ data }">
                                            <div class="flex w-full justify-center md:w-30">
                                                <template v-if="col.type === 'price'">
                                                    {{ formatCurrency(data[col.field]) }}
                                                </template>

                                                <template v-else-if="col.type === 'qty'">
                                                    {{ data[col.field] }}
                                                </template>

                                                <template v-else>
                                                    {{ data[col.field] || '-' }}
                                                </template>
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="action" :exportable="false" v-bind="tbStyle('fg')">
                                        <template #header>
                                            <div class="flex w-full justify-center font-bold">Action</div>
                                        </template>
                                        <template #body="{ data }">
                                            <div class="flex justify-center gap-2">
                                                <Button
                                                    v-tooltip="'Preview Product'"
                                                    icon="pi pi-eye"
                                                    severity="info"
                                                    rounded
                                                    text
                                                    @click="openPreviewTab(data.item_code, opexDef, proginDef, 'standardCost')"
                                                />
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </div>
    </AppLayout>
</template>
