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
const dtAC = ref();
const loading = ref(false);
const year = ref();
const month = ref();
let opexDef = ref(6);
let proginDef = ref(5);
const tempOpex = ref<number | null>(null);
const tempProgin = ref<number | null>(null);
const checked = ref(false);
const monthRange = ref(null);
const selectActualPeriod = ref<ActualPeriod | null>(null);
const updateReportDialog = ref(false);
const updateConstDialog = ref(false);
type UpdateStatus = 'idle' | 'updating' | 'done';
const updateStatus = ref<UpdateStatus>('idle');
const updateType = ref<'actualCost' | 'opgin' | null>(null);
const maxDate = ref(new Date());
const currentDate = new Date();
const minDate = ref(new Date(new Date().getFullYear(), 0, 1));
const selectionModeType = ref('single');
const activeTabValue = ref('0');
const type = ['Disc', 'Sidering', 'Wheel'];
const userName = computed(() => page.props.auth?.user?.name ?? '');

const actualCostUrl = ref('/finacc/api/actual/get-act-cost');
const perPage = ref(10);
const currentActCostPage = ref(1);
const sortFieldActCost = ref(null);
const sortOrderActCost = ref(null);
const paginatedActCostData = ref<any>(null);
const listActualPeriod = ref<List[]>([]);
const listActualType = ref<List[]>([]);

const isReady = reactive({
    actualCost: false,
});

const actualCostTimeout = ref<ReturnType<typeof setTimeout> | null>(null);

const isInitialLoading = ref(true);

interface List {
    name: string;
    code: string;
}

currentDate.setDate(1);
currentDate.setDate(currentDate.getDate() - 1);
maxDate.value = currentDate;

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

const lastMaster = computed(() => page.props.lastMaster as any);
// console.log('Last Master Data Update:', lastMaster.value);

interface ActualPeriod {
    name: string;
    code: string;
}

watch(selectActualPeriod, (newValue) => {
    if (newValue) {
        const period = newValue.code;

        filtersActual.value.period.value = period;
    } else {
        // Reset filter jika Select dikosongkan
        filtersActual.value.period.value = null;
    }
});

const currentSelectionMode = computed(() => {
    return selectionModeType.value === 'range' ? 'range' : 'single';
});

const filtersActual = ref({
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    type_name: { value: null, matchMode: FilterMatchMode.EQUALS },
    'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
    period: { value: null as string | null, matchMode: FilterMatchMode.EQUALS },
});

async function exportCSV(type: string) {
    if (type === 'actualCost') {
        const currentPeriod = filtersActCost.value.period.value;

        try {
            // 1. Ambil data lengkap dari endpoint JSON Anda
            const response = await axios.get(route('report.actual.export'), {
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
            link.setAttribute('download', `ActualCost_${currentPeriod || 'All'}.csv`);
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

function showUpdateDialog(
    type: 'actualCost',
    // | 'opgin'
) {
    updateType.value = type;
    updateStatus.value = 'idle';

    // if (updateType.value === 'opgin') {
    //     nextTick(() => {
    //         tempOpex.value = opexDef.value;
    //         tempProgin.value = proginDef.value;
    //         updateConstDialog.value = true;
    //     });
    // } else {
    nextTick(() => {
        updateReportDialog.value = true;
    });
    // }
}

const validationErrors = ref({
    ac: '',
});

const getMonthShortName = (date: Date) => {
    return new Intl.DateTimeFormat('en-US', { month: 'short' }).format(date);
};

function confirmUpdate() {
    if (!updateType.value) return;
    let payload = {};
    let ACupdatePeriod: string | null = null;

    if (updateType.value === 'actualCost') {
        // Validasi: Pastikan nilai tidak kosong
        if (!monthRange.value) {
            // Hapus month.value yang tidak lagi digunakan
            validationErrors.value.ac = 'Period selection cannot be empty.';
            return;
        }

        if (selectionModeType.value === 'range') {
            // Mode Rentang
            const startMonthDate = monthRange.value[0];
            const endMonthDate = monthRange.value[1];

            if (!startMonthDate || !endMonthDate) {
                validationErrors.value.ac = 'Invalid month range selected.';
                return;
            }
            const monthName = getMonthShortName(endMonthDate);
            const yearShort = endMonthDate.getFullYear().toString();

            ACupdatePeriod = `YTD-${monthName}'${yearShort}`;

            payload = {
                startMonth: startMonthDate.getMonth() + 1,
                endMonth: endMonthDate.getMonth() + 1,
                year: endMonthDate.getFullYear(),

                isRange: true,
            };
        } else {
            const singleMonthDate = monthRange.value;
            const monthName = getMonthShortName(singleMonthDate);
            const yearShort = singleMonthDate.getFullYear().toString();

            ACupdatePeriod = `${monthName}'${yearShort}`;
            payload = {
                startMonth: singleMonthDate.getMonth() + 1,
                endMonth: singleMonthDate.getMonth() + 1,
                year: singleMonthDate.getFullYear(),
                isRange: false,
            };
        }
    }
    // else if (updateType.value === 'opgin') {}

    // if (Object.keys(payload).length === 0 && updateType.value === 'opgin') {
    // }

    updateStatus.value = 'updating';
    const type = updateType.value;

    const routes = {
        actualCost: 'report.actual.update',
        // opgin: 'report.actual.update-opgin',
    };

    const messages = {
        actualCost: 'Actual Cost',
        // opgin: 'OPEX / Profit Margin',
    };

    router.post(
        route(routes[type]),
        payload, // payload kini selalu terdefinisi
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                updateStatus.value = 'done';
                if (type === 'actualCost' && ACupdatePeriod) {
                    filtersActCost.value.period.value = ACupdatePeriod;
                    fetchActualPeriod();
                    loadLazyData(actualCostUrl.value, 'actualCost');
                }
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    group: 'br',
                    detail: `${messages[type]} updated successfully`,
                    life: 3000,
                });
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
        ac: '',
    };

    year.value = null;
    month.value = null;
    monthRange.value = null;
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

const fetchActualPeriod = async () => {
    try {
        const actualPeriod = await fetchDatas('/finacc/api/actual/list-period');

        if (Array.isArray(actualPeriod)) {
            listActualPeriod.value = actualPeriod.map((p: string) => ({ name: p, code: p }));
        } else {
            console.error('API /api/actual/list-period did not return an array:', actualPeriod);
            listActualPeriod.value = [];
        }
    } catch (error) {
        console.error('Failed to fetch material group list:', error);
        listActualPeriod.value = [];
    }
};

const fetchLatestPeriod = async () => {
    try {
        const latest = await fetchDatas('/finacc/api/actual/latest-period');
        if (latest) {
            filtersActCost.value.period.value = latest;
        }
    } catch (error) {
        console.error('Failed to fetch latest period:', error);
    }
};

const fetchActualType = async () => {
    try {
        const actualType = await fetchDatas('/finacc/api/actual/list-type');

        if (Array.isArray(actualType)) {
            listActualType.value = actualType.map((p: string) => ({ name: p, code: p }));
        } else {
            console.error('API /api/actual/list-type did not return an array:', actualType);
            listActualType.value = [];
        }
    } catch (error) {
        console.error('Failed to fetch material group list:', error);
        listActualType.value = [];
    }
};

const filtersActCost = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    period: { value: null, matchMode: FilterMatchMode.EQUALS },
    'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
    type: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const initFilters = () => {
    // Reset filters Stamat (karena ini yang digunakan oleh Tab 0)
    filtersActCost.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
        period: { value: null, matchMode: FilterMatchMode.EQUALS },
        'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
        type: { value: null, matchMode: FilterMatchMode.EQUALS },
    };
};

const controllers = {
    actualCost: null as AbortController | null,
};

const loadingStates = reactive({
    actualCost: false,
});

const loadLazyData = async (url: string, type: 'actualCost') => {
    if (controllers[type]) controllers[type].abort();
    controllers[type] = new AbortController();
    loadingStates[type] = true;

    // 1. Definisikan pemetaan berdasarkan tipe data
    const configMap = {
        actualCost: {
            filters: filtersActCost.value,
            page: currentActCostPage.value,
            descKey: 'bom.description',
            sortField: sortFieldActCost.value,
            sortOrder: sortOrderActCost.value,
            dataRef: paginatedActCostData,
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
    if (type === 'actualCost') {
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
        // console.log(`${url}?${params.toString()}`)

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

const onLazyLoadActCost = (event: any) => {
    const { first, rows, sortField, sortOrder } = event;

    const newPage = first / rows + 1;
    perPage.value = rows;

    if (newPage !== currentActCostPage.value) {
        currentActCostPage.value = newPage;
    }

    sortFieldActCost.value = sortField || null;
    sortOrderActCost.value = sortOrder || null;

    if (sortField !== null) {
        currentActCostPage.value = 1;
    }

    loadLazyData(actualCostUrl.value, 'actualCost');
};

watch(
    filtersActCost,
    () => {
        if (!isReady.actualCost) {
            isReady.actualCost = true;
            return;
        }
        if (actualCostTimeout.value) clearTimeout(actualCostTimeout.value);
        actualCostTimeout.value = setTimeout(() => {
            loadLazyData(actualCostUrl.value, 'actualCost');
        }, 500);
    },
    { deep: true },
);

onMounted(async () => {
    initFilters();
    try {
        await fetchActualType();
        await fetchActualPeriod();
        await fetchLatestPeriod();
        await loadLazyData(actualCostUrl.value, 'actualCost');
    } finally {
        isInitialLoading.value = false;
    }
});

const clearFilter = (type: 'actualCost') => {
    if (type === 'actualCost') {
        filtersActCost.value = {
            global: { value: null, matchMode: FilterMatchMode.CONTAINS },
            item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
            period: { value: null, matchMode: FilterMatchMode.EQUALS },
            'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
            type: { value: null, matchMode: FilterMatchMode.EQUALS },
        };
        currentActCostPage.value = 1;
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
    <Head title="Actual Cost" />
    <AppLayout>
        <div class="p-6">
            <div class="flex flex-col gap-1">
                <h2 class="mb-2 text-start text-3xl font-bold text-gray-900 dark:text-white">Actual Cost</h2>
                <p class="text-start text-gray-600 dark:text-gray-400">Generate Actual Cost Calculation</p>
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
                        <div class="mt-6 mb-2 font-semibold">Select Actual Price Period:</div>
                        <div class="flex space-x-4">
                            <div class="flex-1">
                                <label for="report-month" class="block text-sm font-medium text-muted-foreground"
                                    >Select month selection mode :
                                </label>

                                <div class="mb-3 flex space-x-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" v-model="selectionModeType" value="single" class="form-radio text-indigo-600" />
                                        <span class="ml-2 text-sm text-foreground">Single Month</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" v-model="selectionModeType" value="range" class="form-radio text-indigo-600" />
                                        <span class="ml-2 text-sm text-foreground">Ranged Months</span>
                                    </label>
                                </div>

                                <DatePicker
                                    v-model="monthRange"
                                    view="month"
                                    dateFormat="mm"
                                    showClear
                                    :selectionMode="currentSelectionMode"
                                    :minDate="minDate"
                                    :maxDate="maxDate"
                                    :placeholder="selectionModeType === 'range' ? 'Start Month - End Month' : 'Single Month'"
                                    :manualInput="false"
                                />
                            </div>
                        </div>
                        <p v-if="validationErrors.ac" class="mt-2 inline-block rounded bg-red-500 px-2 py-1 text-sm font-medium text-white">
                            {{ validationErrors.ac }}
                        </p>

                        <div>
                            <p class="mt-6 mb-2 font-bold">Make sure this data is up to date:</p>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse text-left">
                                    <thead>
                                        <tr>
                                            <th class="border-b border-gray-700 px-4 py-2 font-bold text-muted-foreground">Data</th>
                                            <th class="border-b border-gray-700 px-4 py-2 font-bold text-muted-foreground">Last Update</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="border-b border-gray-800 px-4 py-2">Actual Material Price</td>
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
                        <Tab value="0">Actual Cost</Tab>
                    </TabList>

                    <TabPanels>
                        <TabPanel value="0">
                            <section class="p-2">
                                <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <!-- Title -->
                                    <h2 class="text-3xl font-semibold text-gray-900 dark:text-white">Actual Cost</h2>
                                </div>
                                <!-- Last Update Info -->
                                <!-- <div class="mb-4 text-right text-gray-700 dark:text-gray-300">
                                    <div>
                                        Last Update :
                                        <span class="text-red-300">{{ lastUpdate[0] ? formatlastUpdate(lastUpdate[0]) : '-' }}</span>
                                    </div>
                                </div> -->

                                <DataTable
                                    :value="paginatedActCostData?.data || []"
                                    :lazy="true"
                                    :totalRecords="paginatedActCostData?.total || 0"
                                    :rows="perPage"
                                    @page="onLazyLoadActCost"
                                    @sort="onLazyLoadActCost"
                                    :first="(currentActCostPage - 1) * perPage"
                                    :paginator="true"
                                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                                    currentPageReportTemplate="Showing {first} to {last} from {totalRecords} data"
                                    responsiveLayout="scroll"
                                    :globalFilterFields="['item_code', 'type', 'description']"
                                    showGridlines
                                    :removableSort="true"
                                    v-model:filters="filtersActCost"
                                    filterDisplay="row"
                                    :loading="loadingStates.actualCost || isInitialLoading"
                                    ref="dtActCost"
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
                                                        @click="exportCSV('actualCost')"
                                                    />
                                                    <Button
                                                        v-if="auth?.user?.permissions?.includes('Update_Report')"
                                                        icon="pi pi-sync"
                                                        label=" Update Report?"
                                                        unstyled
                                                        class="w-full cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-cyan-700 sm:w-auto"
                                                        @click="showUpdateDialog('actualCost')"
                                                    />
                                                </div>

                                                <!-- OPEX and Profit Margin Section -->
                                                <!-- <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
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
                                                </div> -->
                                            </div>
                                            <div class="justify-end">
                                                <div class="flex justify-between space-x-2">
                                                    <Button
                                                        type="button"
                                                        icon="pi pi-filter-slash"
                                                        label=" Clear"
                                                        unstyled
                                                        class="w-full cursor-pointer rounded-xl bg-slate-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-slate-700 sm:w-auto"
                                                        @click="clearFilter('actualCost')"
                                                    />

                                                    <IconField>
                                                        <InputIcon>
                                                            <i class="pi pi-search" />
                                                        </InputIcon>
                                                        <InputText v-model="filtersActCost['global'].value" placeholder="Keyword Search" />
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
                                                    :options="listActualPeriod"
                                                    optionLabel="name"
                                                    optionValue="name"
                                                    placeholder="Actual Period"
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
                                                    :options="listActualType"
                                                    optionLabel="name"
                                                    optionValue="name"
                                                    filter
                                                    placeholder="Actual Type"
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

                                    <!-- <Column field="action" header="Action" :exportable="false" v-bind="tbStyle('fg')">
                                        <template #body="data">
                                            <div class="flex gap-2">
                                                <Button
                                                    v-tooltip="'Preview Product'"
                                                    icon="pi pi-eye"
                                                    severity="info"
                                                    rounded
                                                    text
                                                    @click="openPreviewTab(data.data.item_code, opexDef, proginDef, 'standardCost')"
                                                />
                                            </div>
                                        </template>
                                    </Column> -->
                                </DataTable>
                            </section>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </div>
    </AppLayout>
</template>
