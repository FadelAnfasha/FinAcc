<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import axios from 'axios';
import dayjs from 'dayjs';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import FileUpload, { FileUploadUploaderEvent } from 'primevue/fileupload';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Panel from 'primevue/panel';
import ProgressBar from 'primevue/progressbar';
import Select from 'primevue/select';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { computed, nextTick, onMounted, reactive, ref } from 'vue';

const filterBP = ref({
    bp_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    bp_name: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const filterCT = ref({
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    type: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const filterSQ = ref({
    bp_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    'bp.bp_name': { value: null, matchMode: FilterMatchMode.CONTAINS },
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    'item.type': { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const loading = ref(false);

const dtBP = ref();
const dtCT = ref();
const dtSQ = ref();
const dtWD = ref();
const toast = useToast();
const page = usePage();
const activeTabValue = ref('0');

const businessPartners = computed(() =>
    (page.props.businessPartners as any[]).map((bp, index) => ({
        ...bp,
        no: index + 1,
        created_at_formatted: formatDate(bp.created_at),
        updated_at_formatted: formatDate(bp.updated_at),
    })),
);

const cycleTimes = computed(() =>
    (page.props.cycleTimes as any[]).map((ct, index) => ({
        ...ct,
        no: index + 1,
        created_at_formatted: formatDate(ct.created_at),
        updated_at_formatted: formatDate(ct.updated_at),
    })),
);

const salesQuantity = computed(() =>
    (page.props.salesQuantities as any[]).map((sq, index) => ({
        ...sq,
        no: index + 1,
        created_at_formatted: formatDate(sq.created_at),
        updated_at_formatted: formatDate(sq.updated_at),
    })),
);

const wagesDistribution = page.props.wagesDistribution as Record<string, string>;

interface ImportResult {
    addedItems: string[];
    invalidItems: { bp_code: string; bp_name: string; item_code: string; quantity: string; description: string; reason: string }[];
}

const importResult = computed(() => {
    // Memberikan petunjuk tipe data kepada page.props.importResult
    const result = page.props.importResult as ImportResult;

    // Menambahkan pemeriksaan tipe data untuk memastikan `result` adalah objek
    if (!result || typeof result !== 'object') {
        return {
            addedItems: [],
            invalidItems: [],
        };
    }

    // Perbaikan untuk addedItems:
    // Mengambil item (yang berupa string bp_code) dari array
    const addedItems = (result.addedItems || []).map((item, index) => ({
        no: index + 1,
        bp_code: item, // Menyimpan nilai string bp_code ke properti bp_code
    }));

    // Perbaikan untuk invalidItems:
    // Setiap item adalah objek { 'bp_code': '...', 'reason': '...' }
    // Kita perlu mengambil properti bp_code dan reason dari setiap objek
    const invalidItems = (result.invalidItems || []).map((item, index) => ({
        no: index + 1,
        bp_code: item.bp_code,
        bp_name: item.bp_name,
        item_code: item.item_code,
        quantity: item.quantity,
        description: item.description,
        reason: item.reason,
    }));

    return {
        addedItems: addedItems,
        invalidItems: invalidItems,
    };
});

const lastUpdate = computed(() => {
    // Business Partners
    const bp_update = ((page.props.businessPartners as any[]) ?? []).map((bp) => new Date(bp.updated_at));
    const Max_bpUpdate = bp_update.length ? new Date(Math.max(...bp_update.map((d) => d.getTime()))) : null;

    // Cycle Times
    const ct_update = ((page.props.cycleTimes as any[]) ?? []).map((ct) => new Date(ct.updated_at));
    const Max_ctUpdate = ct_update.length ? new Date(Math.max(...ct_update.map((d) => d.getTime()))) : null;

    // Sales Quantities
    const sq_update = ((page.props.salesQuantities as any[]) ?? []).map((sq) => new Date(sq.updated_at));
    const Max_sqUpdate = sq_update.length ? new Date(Math.max(...sq_update.map((d) => d.getTime()))) : null;

    // Wages Distribution
    const wagesDistribution = page.props.wagesDistribution as { updated_at?: string } | null;
    const Max_wdUpdate = wagesDistribution?.updated_at ? new Date(wagesDistribution.updated_at) : null;

    return [Max_bpUpdate, Max_ctUpdate, Max_sqUpdate, Max_wdUpdate];
});

const dataSource = [
    'Share Others/Finacc/Process Cost/Business Partner(BP)/bp_master.csv',
    'Share Others/Finacc/Process Cost/Cycle Time (CT)/ct_master.csv',
    'Share Others/Finacc/Process Cost/Sales Quantity (SQ)/sq_master.csv',
    'Share Others/Finacc/Process Cost/Wages Distribution (WD)/wd_master.csv',
];

function formatlastUpdate(date: Date | string) {
    return dayjs(date).format('DD MMM YYYY HH:mm:ss');
}

function getCSSVar(name: string) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}

// Ref untuk Chart Data dan Options
const chartOptions = ref({});
const chartDataDisc = ref({});
const chartDataRim = ref({});
const chartDataSidering = ref({});
const chartDataAssy = ref({});
const chartDataPainting = ref({});
const chartDataPacking = ref({});
const chartDataTotal = ref({});

onMounted(() => {
    chartDataDisc.value = getChartData('Line Disc', ['blanking', 'spinDisc', 'autoDisc', 'manualDisc', 'discLathe'], '--p-cyan-500');
    chartDataRim.value = getChartData('Line Rim', ['rim1', 'rim2', 'rim3'], '--p-orange-500');
    chartDataSidering.value = getChartData('Line Sidering', ['coiler', 'forming'], '--p-green-500');
    chartDataAssy.value = getChartData('Line Assy', ['assy1', 'assy2', 'machining', 'shotPeening'], '--p-purple-500');
    chartDataPainting.value = getChartData('Line Painting', ['ced', 'topcoat'], '--p-red-500');
    chartDataPacking.value = getChartData('Line Packing', ['packing_dom', 'packing_exp'], '--p-blue-500');

    chartOptions.value = getChartOptions();
});

const lineDiscTotal = computed(() => calculateTotal(['blanking', 'spinDisc', 'autoDisc', 'manualDisc', 'discLathe']));
const lineRimTotal = computed(() => calculateTotal(['rim1', 'rim2', 'rim3']));
const lineSideringTotal = computed(() => calculateTotal(['coiler', 'forming']));
const lineAssyTotal = computed(() => calculateTotal(['assy1', 'assy2', 'machining', 'shotPeening']));
const linePaintingTotal = computed(() => calculateTotal(['ced', 'topcoat']));
const linePackingTotal = computed(() => calculateTotal(['packing_dom', 'packing_exp']));
const lineTotals = computed(
    () => lineDiscTotal.value + lineRimTotal.value + lineSideringTotal.value + lineAssyTotal.value + linePaintingTotal.value + linePackingTotal.value,
);

const getChartData = (label: string, keys: string[], color: string) => {
    const documentStyle = getComputedStyle(document.documentElement);
    const wd = (page.props.wagesDistribution || {}) as Record<string, string>;

    return {
        labels: keys,
        datasets: [
            {
                label,
                backgroundColor: documentStyle.getPropertyValue(color),
                data: keys.map((key) => parseFloat(wd[key] || '0')),
            },
        ],
    };
};

const getChartOptions = () => {
    const documentStyle = getComputedStyle(document.documentElement);
    const textColor = documentStyle.getPropertyValue('--p-text-color');
    const textColorSecondary = documentStyle.getPropertyValue('--p-text-muted-color');
    const surfaceBorder = documentStyle.getPropertyValue('--p-content-border-color');

    return {
        indexAxis: 'y',
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: textColor,
                },
            },
        },
        scales: {
            x: {
                ticks: {
                    color: textColorSecondary,
                },
                grid: {
                    color: surfaceBorder,
                },
            },
            y: {
                ticks: {
                    color: textColorSecondary,
                },
                grid: {
                    color: surfaceBorder,
                },
            },
        },
    };
};

const calculateTotal = (keys: string[]) => {
    const wd = (page.props.wagesDistribution || {}) as Record<string, string>;
    return keys.reduce((sum, key) => sum + parseFloat(wd[key] || '0'), 0);
};

chartDataTotal.value = {
    labels: ['Line Disc', 'Line Rim', 'Line Sidering', 'Line Assy', 'Line Painting', 'Line Packing'],
    datasets: [
        {
            label: 'Total Wages Distribution',
            backgroundColor: [
                getComputedStyle(document.documentElement).getPropertyValue('--p-cyan-500'),
                getComputedStyle(document.documentElement).getPropertyValue('--p-orange-500'),
                getComputedStyle(document.documentElement).getPropertyValue('--p-green-500'),
                getComputedStyle(document.documentElement).getPropertyValue('--p-purple-500'),
                getComputedStyle(document.documentElement).getPropertyValue('--p-red-500'),
                getComputedStyle(document.documentElement).getPropertyValue('--p-blue-500'),
            ],
            data: [
                lineDiscTotal.value,
                lineRimTotal.value,
                lineSideringTotal.value,
                lineAssyTotal.value,
                linePaintingTotal.value,
                linePackingTotal.value,
            ],
        },
    ],
};

const cyan500 = getCSSVar('--p-cyan-500');
const orange500 = getCSSVar('--p-orange-500');
const green500 = getCSSVar('--p-green-500');
const purple500 = getCSSVar('--p-purple-500');
const red500 = getCSSVar('--p-red-500');
const blue500 = getCSSVar('--p-blue-500');

const updateChartData = () => {
    const wd = page.props.wagesDistribution as Record<string, string>;

    chartDataTotal.value = {
        labels: ['Line Disc', 'Line Rim', 'Line Sidering', 'Line Assy', 'Line Painting', 'Line Packing'],
        datasets: [
            {
                label: 'Total Wages Distribution',
                backgroundColor: [cyan500, orange500, green500, purple500, red500, blue500],

                data: [
                    parseFloat(wd.blanking) +
                        parseFloat(wd.spinDisc) +
                        parseFloat(wd.autoDisc) +
                        parseFloat(wd.manualDisc) +
                        parseFloat(wd.discLathe),
                    parseFloat(wd.rim1) + parseFloat(wd.rim2) + parseFloat(wd.rim3),
                    parseFloat(wd.coiler) + parseFloat(wd.forming),
                    parseFloat(wd.assy1) + parseFloat(wd.assy2) + parseFloat(wd.machining) + parseFloat(wd.shotPeening),
                    parseFloat(wd.ced) + parseFloat(wd.topcoat),
                    parseFloat(wd.packing_dom) + parseFloat(wd.packing_exp),
                ],
            },
        ],
    };
    chartDataDisc.value = {
        labels: ['Blanking', 'Spinning Disc', 'Disc Auto', 'Manual Disc', 'Disc Lathe'],
        datasets: [
            {
                label: 'Total Wages Distribution',
                backgroundColor: [cyan500, cyan500, cyan500, cyan500, cyan500],

                data: [
                    parseFloat(wd.blanking),
                    parseFloat(wd.spinDisc),
                    parseFloat(wd.autoDisc),
                    parseFloat(wd.manualDisc),
                    parseFloat(wd.discLathe),
                ],
            },
        ],
    };
    chartDataRim.value = {
        labels: ['Rim 1', 'Rim 2', 'Rim3 '],
        datasets: [
            {
                label: 'Total Wages Distribution',
                backgroundColor: [orange500, orange500],

                data: [parseFloat(wd.rim1), parseFloat(wd.rim2), parseFloat(wd.rim3)],
            },
        ],
    };
    chartDataSidering.value = {
        labels: ['Coiler', 'Forming'],
        datasets: [
            {
                label: 'Total Wages Distribution',
                backgroundColor: [green500, green500],
                data: [parseFloat(wd.coiler), parseFloat(wd.forming)],
            },
        ],
    };
    chartDataAssy.value = {
        labels: ['Assy 1', 'Assy 2', 'Machining', 'ShotPeening'],
        datasets: [
            {
                label: 'Total Wages Distribution',
                backgroundColor: [purple500, purple500, purple500, purple500],

                data: [parseFloat(wd.assy1), parseFloat(wd.assy2), parseFloat(wd.machining), parseFloat(wd.shotPeening)],
            },
        ],
    };
    chartDataPainting.value = {
        labels: ['CED', 'Top Coat'],
        datasets: [
            {
                label: 'Total Wages Distribution',
                backgroundColor: [red500, red500],
                data: [parseFloat(wd.ced), parseFloat(wd.topcoat)],
            },
        ],
    };
    chartDataPacking.value = {
        labels: ['Packing Domestic', 'Packing Export'],
        datasets: [
            {
                label: 'Total Wages Distribution',
                backgroundColor: [blue500, blue500],
                data: [parseFloat(wd.packing_dom), parseFloat(wd.packing_exp)],
            },
        ],
    };
};

const headerStyle = { backgroundColor: '#758596', color: 'white' };
const bodyStyle = { backgroundColor: '#c8cccc', color: 'black' };

const showDialog = ref(false);
const dialogWidth = ref('40rem');
const editType = ref<'sq' | 'wd' | null>(null);
const addType = ref<'bp' | null>(null);

const destroyType = ref<'ct' | 'sq' | 'bp' | null>(null);
const headerType = ref<any>({});
const editedData = ref<any>({});
const destroyedData = ref<any>({});

const form = reactive({
    company_type: '',
    bp_name: '',
});

const company_type = ref([
    { name: 'Commanditaire Vennootschap', code: 'CV' },
    { name: 'Perseroan Terbatas', code: 'PT' },
]);

const showImportDialog = ref(false);
const importName = ref<any>({});
const selectedFile = ref<File | null>(null);
const importType = ref<'bp' | 'ct' | 'sq' | 'wd' | null>(null);
const fileUploaderBP = ref<any>(null);
const fileUploaderCT = ref<any>(null);
const fileUploaderSQ = ref<any>(null);
const fileUploaderWD = ref<any>(null);
const uploadProgress = ref(0);
const notImported = ref(true);
const isUploading = ref(false);
const userName = computed(() => page.props.auth?.user?.name ?? '');

function handleCSVImport(event: FileUploadUploaderEvent, type: 'bp' | 'ct' | 'sq' | 'wd') {
    let file: File | undefined;

    if (Array.isArray(event.files)) {
        file = event.files[0];
    } else if (event.files instanceof File) {
        file = event.files;
    }

    if (!file) return;

    const expectedNames = {
        bp: 'bp_master.csv',
        ct: 'ct_master.csv',
        sq: 'sq_master.csv',
        wd: 'wd_master.csv',
    };

    const expectedFileName = expectedNames[type];

    if (file.name !== expectedFileName) {
        toast.add({
            severity: 'error',
            summary: 'File name missmatch!',
            detail: `⚠️ Expected: ${expectedFileName}, but got: ${file.name}`,
            life: 4000,
            group: 'br',
        });
        selectedFile.value = null;

        nextTick(() => {
            if (type === 'bp') fileUploaderBP.value?.clear();
            if (type === 'ct') fileUploaderCT.value?.clear();
            if (type === 'sq') fileUploaderSQ.value?.clear();
            if (type === 'wd') fileUploaderWD.value?.clear();
        });

        return;
    }

    selectedFile.value = file;
    importType.value = type;
    importName.value = file.name;

    nextTick(() => {
        showImportDialog.value = true;
    });
}

function cancelCSVimport(type: 'bp' | 'ct' | 'sq' | 'wd') {
    showImportDialog.value = false;
    selectedFile.value = null;

    nextTick(() => {
        if (type === 'bp') fileUploaderBP.value?.clear();
        if (type === 'ct') fileUploaderCT.value?.clear();
        if (type === 'sq') fileUploaderSQ.value?.clear();
        if (type === 'wd') fileUploaderWD.value?.clear();
    });
}

function confirmUpload(type: 'bp' | 'ct' | 'sq' | 'wd') {
    if (!selectedFile.value || !importType.value) return;

    const formData = new FormData();
    formData.append('file', selectedFile.value);

    const routes = {
        bp: 'bp.import',
        ct: 'ct.import',
        sq: 'sq.import',
        wd: 'wd.import',
    };

    isUploading.value = true;
    uploadProgress.value = 0;
    startPollingProgress(type);

    router.post(route(routes[importType.value]), formData, {
        preserveScroll: true,
        preserveState: true,

        onSuccess: () => {
            isUploading.value = false;

            toast.add({
                severity: 'success',
                summary: 'Import Success',
                detail: `${importName.value} imported successfully`,
                life: 3000,
                group: 'br',
            });

            if (type == 'wd') {
                updateChartData();
            }
            selectedFile.value = null;

            nextTick(() => {
                if (type === 'bp') fileUploaderBP.value?.clear();
                if (type === 'ct') fileUploaderCT.value?.clear();
                if (type === 'sq') fileUploaderSQ.value?.clear();
                if (type === 'wd') fileUploaderWD.value?.clear();
            });
        },

        onError: () => {
            isUploading.value = false;
            showImportDialog.value = false;

            toast.add({
                severity: 'error',
                summary: 'Import Failed',
                detail: 'There was an error importing the CSV',
                life: 3000,
                group: 'br',
            });

            selectedFile.value = null;

            nextTick(() => {
                if (type === 'bp') fileUploaderBP.value?.clear();
                if (type === 'ct') fileUploaderCT.value?.clear();
                if (type === 'sq') fileUploaderSQ.value?.clear();
                if (type === 'wd') fileUploaderWD.value?.clear();
            });
        },
    });
}

function resetImportState() {
    uploadProgress.value = 0;
    selectedFile.value = null;
    notImported.value = true;
}

function startPollingProgress(type: 'bp' | 'ct' | 'sq' | 'wd') {
    uploadProgress.value = 0;

    const endpointMap = {
        bp: '/finacc/bp/import-progress',
        ct: '/finacc/ct/import-progress',
        sq: '/finacc/sq/import-progress',
        wd: '/finacc/wd/import-progress',
    };

    const interval = setInterval(async () => {
        try {
            const res = await axios.get(endpointMap[type]);
            uploadProgress.value = res.data.progress;

            if (uploadProgress.value >= 100) {
                clearInterval(interval);
                notImported.value = false; // ✅ sekarang bisa ganti tombol menjadi "Close"
            }
        } catch (err) {
            console.error(`Error polling ${type} import progress:`, err);
            clearInterval(interval);
        }
    }, 1000);
}

function exportCSV(type: 'bp' | 'ct' | 'sq' | 'wd') {
    let $type = null;
    let $filename = null;
    if (type === 'bp') {
        $type = dtBP.value;
        $filename = 'business-partners';
    } else if (type === 'ct') {
        $type = dtCT.value;
        $filename = 'cycle-times';
    } else if (type === 'sq') {
        $type = dtSQ.value;
        $filename = 'sales-quantity';
    } else if (type === 'wd') {
        $type = dtWD.value;
        $filename = 'wages-distribution';
    }

    if (!$type) return;

    const exportFilename = `${$filename}-${new Date().toISOString().slice(0, 10)}.csv`;

    $type.exportCSV({
        selectionOnly: false,
        filename: exportFilename,
    });
}

function formatDate(dateStr: string): string {
    const date = new Date(dateStr);
    const yy = String(date.getFullYear());
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yy}-${mm}-${dd}`;
}

function editData(data: any, type: 'sq' | 'wd') {
    editedData.value = { ...data }; // clone untuk menjaga reaktivitas
    editType.value = type;
    headerType.value = 'Edit data';
    // Atur lebar berdasarkan type
    if (type === 'sq') {
        dialogWidth.value = '40rem';
    } else if (type === 'wd') {
        dialogWidth.value = '80rem';
    }
    showDialog.value = true;
}

function addData(type: 'bp') {
    headerType.value = 'Add data';
    addType.value = type;
    // Atur lebar berdasarkan type
    if (type === 'bp') {
        dialogWidth.value = '40rem';
    }
    showDialog.value = true;
}

function handleSave() {
    if (editType.value === 'sq') {
        const id = editedData.value.id;
        if (!id) return;

        router.put(route('sq.update', id), editedData.value, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    group: 'br',
                    detail: `Data ${editedData.value.bp_code} and ${editedData.value.item_code} updated successfully`,
                    life: 3000,
                });
                showDialog.value = false;
            },
            onError: () => {
                toast.add({
                    severity: 'warn',
                    summary: 'Error',
                    group: 'br',
                    detail: `Failed to delete data with ${editedData.value.bp_code} and ${editedData.value.item_code}`,
                    life: 3000,
                });
            },
        });
    } else if (editType.value === 'wd') {
        router.put(route('wd.update'), editedData.value, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({
                    severity: 'warn',
                    summary: 'Success',
                    group: 'br',
                    detail: 'Wages Distribution data updated successfully',
                    life: 3000,
                });
                updateChartData();
                showDialog.value = false;
            },
            onError: () => {
                toast.add({
                    severity: 'warn',
                    summary: 'Error',
                    group: 'br',
                    detail: `Failed to update Wages Distribution data`,
                    life: 3000,
                });
            },
        });
    }
}

function handleAdd() {
    if (addType.value === 'bp') {
        router.post(
            route('bp.store'),
            {
                company_type: form.company_type,
                bp_name: form.bp_name,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        group: 'br',
                        detail: `Data ${form.company_type}.${form.bp_name} stored successfully`,
                        life: 3000,
                    });
                    showDialog.value = false;
                },
                onError: () => {
                    toast.add({
                        severity: 'warn',
                        summary: 'Error',
                        group: 'br',
                        detail: `Failed to stored ${form.company_type}.${form.bp_name} data`,
                        life: 3000,
                    });
                },
            },
        );
    }
}

function destroyData(data: any, type: 'sq') {
    destroyedData.value = { ...data };
    destroyType.value = type;
    headerType.value = 'Delete data';
    if (type === 'sq') {
        dialogWidth.value = '40rem';
    }
    showDialog.value = true;
}

function handleDestroy() {
    if (destroyType.value === 'sq') {
        const bp_code = destroyedData.value.bp_code;
        if (!bp_code) return;

        router.delete(route('sq.destroy', bp_code), {
            data: destroyedData.value,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Success',
                    detail: `Data ${destroyedData.value.bp_code} and ${destroyedData.value.item_code} deleted successfully`,
                    group: 'br',
                    life: 3000,
                });
                showDialog.value = false;
            },
            onError: () => {
                toast.add({
                    severity: 'warn',
                    summary: 'Error',
                    group: 'br',
                    detail: `Failed to delete this  ${destroyedData.value.bp_code} data`,
                    life: 3000,
                });
            },
        });
    }
}

const props = defineProps({
    auth: Object,
});
</script>

<template>
    <Toast group="br" position="bottom-right" />

    <Head title="Process Cost" />
    <AppLayout>
        <div class="m-6">
            <div class="flex flex-col gap-1">
                <h2 class="mb-2 text-start text-3xl font-bold text-gray-900 dark:text-white">Process Cost Calculation</h2>
                <p class="text-start text-gray-600 dark:text-gray-400">Unit cost product calculation.</p>
            </div>
            <!-- Header Section -->
            <div class="mt-4 mb-8">
                <div class="relative mb-6 text-center">
                    <h1 class="relative z-1 inline-block bg-white px-4 text-2xl font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        Master Data Section
                    </h1>
                    <hr class="absolute top-1/2 left-0 z-0 w-full -translate-y-1/2 border-gray-300 dark:border-gray-600" />
                </div>

                <Dialog
                    v-model:visible="showImportDialog"
                    header="Import Confirmation"
                    modal
                    class="w-[40rem]"
                    :closable="false"
                    @hide="resetImportState"
                >
                    <Transition name="fade" class="mb-3">
                        <div v-if="uploadProgress > 0" class="pt-2">
                            <span>Progress : </span>
                            <ProgressBar :value="uploadProgress" showValue />
                        </div>
                    </Transition>

                    <div class="space-y-4" v-if="notImported">
                        <p>
                            Hi <span class="font-semibold text-red-400">{{ userName }}</span
                            >,
                        </p>

                        <p>
                            Are you sure you want to import
                            <strong class="text-blue-500">{{ importName }}</strong
                            >?
                        </p>

                        <div class="overflow-x-auto" v-if="importType === 'sq'">
                            <p class="mt-6 mb-2 font-semibold">Make sure this data is up to date:</p>
                            <table class="w-full border-collapse text-left">
                                <thead>
                                    <tr>
                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Data</th>
                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Last Update</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="border-b border-gray-800 px-4 py-2">Business Partner</td>
                                        <td class="border-b border-gray-800 px-4 py-2">
                                            <span class="text-red-300">{{ lastUpdate[0] ? formatlastUpdate(lastUpdate[0]) : 'Empty' }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border-b border-gray-800 px-4 py-2">Cycle Time</td>
                                        <td class="border-b border-gray-800 px-4 py-2">
                                            <span class="text-red-300">{{ lastUpdate[1] ? formatlastUpdate(lastUpdate[1]) : 'Empty' }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-end gap-3 pt-6">
                            <Button
                                label=" Cancel"
                                icon="pi pi-times"
                                unstyled
                                class="w-28 cursor-pointer rounded-xl bg-red-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-red-700"
                                :disabled="isUploading"
                                @click="cancelCSVimport(importType!)"
                            />
                            <Button
                                label=" Yes, Import"
                                icon="pi pi-check"
                                unstyled
                                class="w-40 cursor-pointer rounded-xl bg-emerald-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-emerald-700"
                                :loading="isUploading"
                                @click="() => confirmUpload(importType!)"
                            />
                        </div>
                    </div>

                    <div class="space-y-4" v-if="!notImported">
                        <p>
                            Hi <span class="font-semibold text-red-400">{{ userName }}</span
                            >,
                        </p>

                        <p>
                            Import
                            <strong class="text-green-500">Finished</strong>, it's safe to close window.
                        </p>

                        <div v-if="importResult.invalidItems.length > 0">
                            <p><span class="text-xl font-semibold text-orange-400">Failed</span> to import:</p>
                            <table class="w-full border-collapse text-left">
                                <thead>
                                    <tr>
                                        <th
                                            v-if="importType === 'bp' || importType === 'sq'"
                                            class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400"
                                        >
                                            BP Code
                                        </th>
                                        <th v-if="importType === 'bp'" class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">
                                            BP Name
                                        </th>
                                        <th
                                            v-if="importType === 'ct' || importType === 'sq'"
                                            class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400"
                                        >
                                            Item Code
                                        </th>
                                        <th v-if="importType === 'ct'" class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">
                                            Description
                                        </th>
                                        <th v-if="importType === 'sq'" class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">
                                            Quantity
                                        </th>

                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Menggunakan template v-if dan v-for -->
                                    <template v-if="importResult.invalidItems.length > 0">
                                        <tr v-for="item in importResult.invalidItems" :key="item.no">
                                            <td v-if="importType === 'bp' || importType === 'sq'" class="border-b border-gray-800 px-4 py-2">
                                                {{ item.bp_code }}
                                            </td>
                                            <td v-if="importType === 'bp'" class="border-b border-gray-800 px-4 py-2">
                                                {{ item.bp_name }}
                                            </td>
                                            <td v-if="importType === 'ct' || importType === 'sq'" class="border-b border-gray-800 px-4 py-2">
                                                {{ item.item_code }}
                                            </td>
                                            <td v-if="importType === 'ct'" class="border-b border-gray-800 px-4 py-2">
                                                {{ item.description }}
                                            </td>
                                            <td v-if="importType === 'sq'" class="border-b border-gray-800 px-4 py-2">
                                                {{ item.quantity }}
                                            </td>
                                            <td class="border-b border-gray-800 px-4 py-2">
                                                {{ item.reason }}
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="2" class="border-b border-gray-800 px-4 py-2 text-center text-gray-500">
                                            There are no invalid items.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <Button
                                label=" Close"
                                icon="pi pi-times"
                                unstyled
                                class="w-28 cursor-pointer rounded-xl bg-red-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-red-700"
                                :disabled="isUploading"
                                @click="
                                    () => {
                                        showImportDialog = false;
                                        resetImportState();
                                    }
                                "
                            />
                        </div>
                    </div>
                </Dialog>

                <Dialog v-model:visible="showDialog" :header="headerType" modal :style="{ width: dialogWidth }" :closable="false">
                    <div v-if="addType === 'bp'" class="space-y-6">
                        <div class="mb-4 flex items-center gap-4">
                            <label for="company_type" class="w-24 font-semibold">Company Type</label>
                            <Select
                                v-model="form.company_type"
                                :options="company_type"
                                optionLabel="name"
                                optionValue="code"
                                class="w-full md:w-56"
                            />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="bp_name" class="w-24 font-semibold">BP Name</label>
                            <InputText v-model="form.bp_name" id="bp_name" class="flex-auto" autocomplete="off" />
                        </div>

                        <!-- Action buttons -->
                        <div class="mt-6 flex justify-end gap-2">
                            <Button
                                type="button"
                                label="Cancel"
                                severity="secondary"
                                @click="
                                    () => {
                                        showDialog = false;
                                        addType = null;
                                    }
                                "
                            ></Button>
                            <Button type="button" label="Add" @click="handleAdd()"></Button>
                        </div>
                    </div>

                    <div v-if="editType === 'sq'" class="space-y-6">
                        <div class="mb-4 flex items-center gap-4">
                            <label for="bp_code" class="w-24 font-semibold">BP Code</label>
                            <InputText id="bp_code" class="flex-auto" v-model="editedData.bp_code" autocomplete="off" :disabled="true" />
                        </div>
                        <div class="gap4 mb-4 flex items-center gap-4">
                            <label for="bp_name" class="w-24 font-semibold">BP Name</label>
                            <InputText id="bp_name" class="flex-auto" v-model="editedData.bp.bp_name" autocomplete="off" :disabled="true" />
                        </div>

                        <div class="gap4 mb-4 flex items-center gap-4">
                            <label for="item_code" class="w-24 font-semibold">Item Code</label>
                            <InputText id="item_code" class="flex-auto" v-model="editedData.item_code" autocomplete="off" :disabled="true" />
                        </div>

                        <div class="gap4 mb-4 flex items-center gap-4">
                            <label for="bp_name" class="w-24 font-semibold">BP Name</label>
                            <InputText id="bp_name" class="flex-auto" v-model="editedData.item.type" autocomplete="off" :disabled="true" />
                        </div>
                        <div class="gap4 mb-4 flex items-center gap-4">
                            <label for="quantity" class="w-24 font-semibold">Order Quantity</label>
                            <InputNumber
                                id="quantity"
                                class="flex-auto"
                                inputId="locale-user"
                                :maxFractionDigits="2"
                                autocomplete="off"
                                v-model="editedData.quantity"
                                :min="0"
                            />
                        </div>
                        <!-- Action buttons -->
                        <div class="mt-6 flex justify-end gap-2">
                            <Button
                                type="button"
                                label="Cancel"
                                severity="secondary"
                                @click="
                                    () => {
                                        showDialog = false;
                                        editType = null;
                                    }
                                "
                            ></Button>
                            <Button type="button" label="Save" @click="handleSave()"></Button>
                        </div>
                    </div>

                    <div v-if="editType === 'wd'" class="space-y-6">
                        <!-- Line Disc Section -->
                        <div class="rounded-lg border bg-cyan-500 p-4">
                            <h3 class="mb-4 border-b pb-2 text-lg font-semibold text-slate-900">Line Disc</h3>
                            <div class="mb-4 grid grid-cols-2 gap-4">
                                <div class="flex items-center gap-3">
                                    <label for="blanking" class="w-32 font-medium text-gray-900">Blanking</label>
                                    <InputNumber
                                        id="blanking"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.blanking"
                                        :min="0"
                                    />
                                </div>
                                <div class="flex items-center gap-3">
                                    <label for="spinDisc" class="w-32 font-medium text-gray-900">Spinning Disc</label>
                                    <InputNumber
                                        id="spinDisc"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.spinDisc"
                                        :min="0"
                                    />
                                </div>
                            </div>
                            <div class="mb-4 grid grid-cols-2 gap-4">
                                <div class="flex items-center gap-3">
                                    <label for="autoDisc" class="w-32 font-medium text-gray-900">Auto Disc</label>
                                    <InputNumber
                                        id="autoDisc"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.autoDisc"
                                        :min="0"
                                    />
                                </div>
                                <div class="flex items-center gap-3">
                                    <label for="manualDisc" class="w-32 font-medium text-gray-900">Manual Disc</label>
                                    <InputNumber
                                        id="manualDisc"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.manualDisc"
                                        :min="0"
                                    />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex items-center gap-3">
                                    <label for="discLathe" class="w-32 font-medium text-gray-900">Disc Lathe</label>
                                    <InputNumber
                                        id="discLathe"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.discLathe"
                                        :min="0"
                                    />
                                </div>
                                <div></div>
                                <!-- Empty div for alignment -->
                            </div>
                        </div>

                        <!-- Rim Section -->
                        <div class="rounded-lg border bg-orange-500 p-4">
                            <h3 class="mb-4 border-b pb-2 text-lg font-semibold text-slate-900">Rim</h3>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="flex items-center gap-3">
                                    <label for="rim1" class="w-20 font-medium text-gray-900">Rim 1</label>
                                    <InputNumber
                                        id="rim1"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.rim1"
                                        :min="0"
                                    />
                                </div>
                                <div class="flex items-center gap-3">
                                    <label for="rim2" class="w-20 font-medium text-gray-900">Rim 2</label>
                                    <InputNumber
                                        id="rim2"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.rim2"
                                        :min="0"
                                    />
                                </div>
                                <div class="flex items-center gap-3">
                                    <label for="rim3" class="w-20 font-medium text-gray-900">Rim 3</label>
                                    <InputNumber
                                        id="rim3"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.rim3"
                                        :min="0"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Sidering Section -->
                        <div class="rounded-lg border bg-green-500 p-4">
                            <h3 class="mb-4 border-b pb-2 text-lg font-semibold text-slate-900">Sidering</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex items-center gap-3">
                                    <label for="coiler" class="w-32 font-medium text-gray-900">Coiler</label>
                                    <InputNumber
                                        id="coiler"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.coiler"
                                        :min="0"
                                    />
                                </div>
                                <div class="flex items-center gap-3">
                                    <label for="forming" class="w-32 font-medium text-gray-900">Forming</label>
                                    <InputNumber
                                        id="forming"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.forming"
                                        :min="0"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Assy Section -->
                        <div class="rounded-lg border bg-purple-500 p-4">
                            <h3 class="mb-4 border-b pb-2 text-lg font-semibold text-slate-900">Assembly</h3>
                            <div class="mb-4 grid grid-cols-2 gap-4">
                                <div class="flex items-center gap-3">
                                    <label for="assy1" class="w-32 font-medium text-gray-900">Assy 1</label>
                                    <InputNumber
                                        id="assy1"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.assy1"
                                        :min="0"
                                    />
                                </div>
                                <div class="flex items-center gap-3">
                                    <label for="assy2" class="w-32 font-medium text-gray-900">Assy 2</label>
                                    <InputNumber
                                        id="assy2"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.assy2"
                                        :min="0"
                                    />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex items-center gap-3">
                                    <label for="machining" class="w-32 font-medium text-gray-900">Machining</label>
                                    <InputNumber
                                        id="machining"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.machining"
                                        :min="0"
                                    />
                                </div>
                                <div class="flex items-center gap-3">
                                    <label for="shotPeening" class="w-32 font-medium text-gray-900">Shot Peening</label>
                                    <InputNumber
                                        id="shotPeening"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.shotPeening"
                                        :min="0"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Painting Section -->
                        <div class="rounded-lg border bg-red-500 p-4">
                            <h3 class="mb-4 border-b pb-2 text-lg font-semibold text-slate-900">Painting</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex items-center gap-3">
                                    <label for="ced" class="w-32 font-medium text-gray-900">CED</label>
                                    <InputNumber
                                        id="ced"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.ced"
                                        :min="0"
                                    />
                                </div>
                                <div class="flex items-center gap-3">
                                    <label for="topcoat" class="w-32 font-medium text-gray-900">Top Coat</label>
                                    <InputNumber
                                        id="topcoat"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.topcoat"
                                        :min="0"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Packaging Section -->
                        <div class="rounded-lg border bg-blue-500 p-4">
                            <h3 class="mb-4 border-b pb-2 text-lg font-semibold text-slate-900">Packaging</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex items-center gap-3">
                                    <label for="packing_dom" class="w-32 font-medium text-gray-900">Packing Domestic</label>
                                    <InputNumber
                                        id="packing_dom"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.packing_dom"
                                        :min="0"
                                    />
                                </div>
                                <div class="flex items-center gap-3">
                                    <label for="packing_exp" class="w-32 font-medium text-gray-900">Packing Export</label>
                                    <InputNumber
                                        id="packing_exp"
                                        class="flex-1"
                                        inputId="locale-user"
                                        :maxFractionDigits="2"
                                        autocomplete="off"
                                        v-model="editedData.packing_exp"
                                        :min="0"
                                    />
                                </div>
                            </div>
                        </div>
                        <!-- Action buttons -->
                        <div class="mt-6 flex justify-end gap-2">
                            <Button
                                type="button"
                                label="Cancel"
                                severity="secondary"
                                @click="
                                    () => {
                                        showDialog = false;
                                        editType = null;
                                    }
                                "
                            ></Button>
                            <Button type="button" label="Save" @click="handleSave()"></Button>
                        </div>
                    </div>

                    <div v-if="destroyType === 'sq'" class="space-y-6">
                        <span>
                            Are you sure want to delete Sales Order data with BP Name
                            <span class="font-semibold text-red-600">{{ destroyedData.bp.bp_name }} - ({{ destroyedData.bp_code }})</span> and Item
                            <span class="font-semibold text-red-600">{{ destroyedData.item_code }}({{ destroyedData.item.type }})</span>?
                        </span>
                        <!-- Action buttons -->
                        <div class="mt-6 flex justify-end gap-2">
                            <Button
                                type="button"
                                label="Cancel"
                                severity="secondary"
                                @click="
                                    () => {
                                        showDialog = false;
                                        destroyType = null;
                                    }
                                "
                            ></Button>
                            <Button type="button" label="Delete" severity="danger" @click="handleDestroy()"></Button>
                        </div>
                    </div>
                </Dialog>
            </div>

            <div class="mx-26 mb-26">
                <Tabs v-model="activeTabValue">
                    <TabList>
                        <Tab value="0">Business Partner</Tab>
                        <Tab value="1">Cycle Time</Tab>
                        <Tab value="2">Sales Quantity</Tab>
                        <Tab value="3">Production Cost</Tab>
                    </TabList>
                    <!-- Process Items Grid -->
                    <TabPanels>
                        <TabPanel value="0">
                            <section ref="bpSection" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-slate-900 md:mb-0 dark:text-white">Business Partner</h2>
                                    <div class="mb-4 flex flex-col items-center gap-4 md:mb-0">
                                        <FileUpload
                                            v-if="auth?.user?.permissions?.includes('Update_MasterData')"
                                            ref="fileUploaderBP"
                                            mode="basic"
                                            name="file"
                                            :customUpload="true"
                                            accept=".csv"
                                            chooseLabel="Import CSV"
                                            chooseIcon="pi pi-upload"
                                            @select="(event) => handleCSVImport(event, 'bp')"
                                            class="w-full sm:w-auto"
                                        />

                                        <div class="flex w-full flex-col items-center gap-4 sm:w-auto sm:flex-row">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 sm:w-28"
                                                @click="exportCSV('bp')"
                                            />
                                            <Button
                                                v-if="auth?.user?.permissions?.includes('Update_MasterData')"
                                                icon="pi pi-users"
                                                label=" Add BP"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900 sm:w-28"
                                                @click="addData('bp')"
                                            />
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <div>
                                            Last Update :
                                            <span class="text-red-300">{{ lastUpdate[0] ? formatlastUpdate(lastUpdate[0]) : '-' }}</span>
                                        </div>
                                        <div>
                                            Data source From :
                                            <span class="text-cyan-400">{{ dataSource[0] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <DataTable
                                    :value="businessPartners"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    :rowsPerPageOptions="[10, 20, 50, 100]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filterBP"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['bp_code', 'bp_name']"
                                    class="text-md"
                                    ref="dtBP"
                                >
                                    <Column
                                        field="bp_code"
                                        header="BP Code"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search BP code"
                                                class="w-full"
                                            /> </template
                                    ></Column>

                                    <Column
                                        field="bp_name"
                                        header="Customer"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search Customer"
                                                class="w-full"
                                            /> </template
                                    ></Column>

                                    <Column field="created_at_formatted" sortable header="Added at" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.created_at) }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="updated_at_formatted"
                                        sortable
                                        header="Updated at"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.updated_at) }}
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="1">
                            <section ref="ctSection" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 hover:text-indigo-500 md:mb-0 dark:text-white">
                                        Cycle Time
                                    </h2>

                                    <div class="mb-4 flex flex-col items-center gap-4 md:mb-0">
                                        <FileUpload
                                            v-if="auth?.user?.permissions?.includes('Update_MasterData')"
                                            ref="fileUploaderBP"
                                            mode="basic"
                                            name="file"
                                            :customUpload="true"
                                            accept=".csv"
                                            chooseLabel="Import CSV"
                                            chooseIcon="pi pi-upload"
                                            @select="(event) => handleCSVImport(event, 'ct')"
                                            class="w-full sm:w-auto"
                                        />

                                        <div class="flex w-full flex-col items-center gap-4 sm:w-auto sm:flex-row">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 sm:w-28"
                                                @click="exportCSV('ct')"
                                            />
                                        </div>
                                    </div>

                                    <div class="text-right text-gray-700 dark:text-gray-300">
                                        <div>
                                            Last Update :
                                            <span class="text-red-300">{{ lastUpdate[1] ? formatlastUpdate(lastUpdate[1]) : '-' }}</span>
                                        </div>
                                        <div>
                                            Data source From :
                                            <span class="text-cyan-400">{{ dataSource[1] }}</span>
                                        </div>
                                    </div>
                                </div>
                                <DataTable
                                    :value="cycleTimes"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    :rowsPerPageOptions="[10, 20, 50, 100]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filterCT"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['bp_code', 'type']"
                                    class="text-md"
                                    ref="dtCT"
                                >
                                    <Column
                                        field="item_code"
                                        header="Item Code"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search Item Code"
                                                class="w-full"
                                            /> </template
                                    ></Column>
                                    <Column field="size" header="Size" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column
                                        field="type"
                                        header="Name"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search Description"
                                                class="w-full"
                                            /> </template
                                    ></Column>

                                    <Column field="blanking" header="Blanking" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.blanking || Number(data.blanking) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.blanking))
                                                        ? Number(data.blanking)
                                                        : Number(data.blanking).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.blanking)
                                                          : (Math.ceil(Number(data.blanking) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="blanking_eff" header="Blanking Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.blanking_eff || Number(data.blanking_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.blanking_eff) * 100) % 1 === 0
                                                        ? (Number(data.blanking_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.blanking_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="spinDisc" header="Spinning Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.spinDisc || Number(data.spinDisc) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.spinDisc))
                                                        ? Number(data.spinDisc)
                                                        : Number(data.spinDisc).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.spinDisc)
                                                          : (Math.ceil(Number(data.spinDisc) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="spinDisc_eff" header="Disc Spinning Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.spinDisc_eff || Number(data.spinDisc_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.spinDisc_eff) * 100) % 1 === 0
                                                        ? (Number(data.spinDisc_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.spinDisc_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="autoDisc" header="Disc Auto" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.autoDisc || Number(data.autoDisc) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.autoDisc))
                                                        ? Number(data.autoDisc)
                                                        : Number(data.autoDisc).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.autoDisc)
                                                          : (Math.ceil(Number(data.autoDisc) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="autoDisc_eff" header="Disc Auto Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.autoDisc_eff || Number(data.autoDisc_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.autoDisc_eff) * 100) % 1 === 0
                                                        ? (Number(data.autoDisc_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.autoDisc_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="manualDisc" header="Disc Manual" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.manualDisc || Number(data.manualDisc) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.manualDisc))
                                                        ? Number(data.manualDisc)
                                                        : Number(data.manualDisc).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.manualDisc)
                                                          : (Math.ceil(Number(data.manualDisc) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="manualDisc_eff" header="Disc Manual Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.manualDisc_eff || Number(data.manualDisc_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.manualDisc_eff) * 100) % 1 === 0
                                                        ? (Number(data.manualDisc_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.manualDisc_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="c3_sn" header="C3/SN" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.c3_sn || Number(data.c3_sn) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.c3_sn))
                                                        ? Number(data.c3_sn)
                                                        : Number(data.c3_sn).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.c3_sn)
                                                          : (Math.ceil(Number(data.c3_sn) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="c3_sn_eff" header="C3/SN Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.c3_sn_eff || Number(data.c3_sn_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.c3_sn_eff) * 100) % 1 === 0
                                                        ? (Number(data.c3_sn_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.c3_sn_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="repairC3" header="Repair C3" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.repairC3 || Number(data.repairC3) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.repairC3))
                                                        ? Number(data.repairC3)
                                                        : Number(data.repairC3).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.repairC3)
                                                          : (Math.ceil(Number(data.repairC3) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="repairC3_eff" header="Repair C3 Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.repairC3_eff || Number(data.repairC3_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.repairC3_eff) * 100) % 1 === 0
                                                        ? (Number(data.repairC3_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.repairC3_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="discLathe" header="Disc Lathe" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.discLathe || Number(data.discLathe) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.discLathe))
                                                        ? Number(data.discLathe)
                                                        : Number(data.discLathe).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.discLathe)
                                                          : (Math.ceil(Number(data.discLathe) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="discLathe_eff" header="Disc Lathe Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.discLathe_eff || Number(data.discLathe_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.discLathe_eff) * 100) % 1 === 0
                                                        ? (Number(data.discLathe_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.discLathe_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="rim1" header="Rim1 " :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.rim1 || Number(data.rim1) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.rim1))
                                                        ? Number(data.rim1)
                                                        : Number(data.rim1).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.rim1)
                                                          : (Math.ceil(Number(data.rim1) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="rim1_eff" header="Rim 1 Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.rim1_eff || Number(data.rim1_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.rim1_eff) * 100) % 1 === 0
                                                        ? (Number(data.rim1_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.rim1_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="rim2" header="Rim 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.rim2 || Number(data.rim2) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.rim2))
                                                        ? Number(data.rim2)
                                                        : Number(data.rim2).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.rim2)
                                                          : (Math.ceil(Number(data.rim2) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="rim2_eff" header="Rim 2 Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.rim2_eff || Number(data.rim2_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.rim2_eff) * 100) % 1 === 0
                                                        ? (Number(data.rim2_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.rim2_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="rim2insp" header="Rim 2 Insp" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.rim2insp || Number(data.rim2insp) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.rim2insp))
                                                        ? Number(data.rim2insp)
                                                        : Number(data.rim2insp).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.rim2insp)
                                                          : (Math.ceil(Number(data.rim2insp) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="rim2insp_eff" header="Rim 2 Insp Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.rim2insp_eff || Number(data.rim2insp_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.rim2insp_eff) * 100) % 1 === 0
                                                        ? (Number(data.rim2insp_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.rim2insp_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="rim3" header="Rim 3" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.rim3 || Number(data.rim3) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.rim3))
                                                        ? Number(data.rim3)
                                                        : Number(data.rim3).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.rim3)
                                                          : (Math.ceil(Number(data.rim3) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="rim3_eff" header="Rim 3 Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.rim3_eff || Number(data.rim3_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.rim3_eff) * 100) % 1 === 0
                                                        ? (Number(data.rim3_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.rim3_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="coiler" header="Coiler" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.coiler || Number(data.coiler) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.coiler))
                                                        ? Number(data.coiler)
                                                        : Number(data.coiler).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.coiler)
                                                          : (Math.ceil(Number(data.coiler) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="coiler_eff" header="Coiler Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.coiler_eff || Number(data.coiler_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.coiler_eff) * 100) % 1 === 0
                                                        ? (Number(data.coiler_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.coiler_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="forming" header="Forming" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.forming || Number(data.forming) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.forming))
                                                        ? Number(data.forming)
                                                        : Number(data.forming).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.forming)
                                                          : (Math.ceil(Number(data.forming) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="forming_eff" header="Forming Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.forming_eff || Number(data.forming_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.forming_eff) * 100) % 1 === 0
                                                        ? (Number(data.forming_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.forming_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="assy1" header="Assy 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.assy1 || Number(data.assy1) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.assy1))
                                                        ? Number(data.assy1)
                                                        : Number(data.assy1).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.assy1)
                                                          : (Math.ceil(Number(data.assy1) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="assy1_eff" header="Assy 1 Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.assy1_eff || Number(data.assy1_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.assy1_eff) * 100) % 1 === 0
                                                        ? (Number(data.assy1_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.assy1_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="assy2" header="Assy 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.assy2 || Number(data.assy2) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.assy2))
                                                        ? Number(data.assy2)
                                                        : Number(data.assy2).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.assy2)
                                                          : (Math.ceil(Number(data.assy2) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="assy2_eff" header="Assy 2 Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.assy2_eff || Number(data.assy2_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.assy2_eff) * 100) % 1 === 0
                                                        ? (Number(data.assy2_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.assy2_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="machining" header="Machining" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.machining || Number(data.machining) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.machining))
                                                        ? Number(data.machining)
                                                        : Number(data.machining).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.machining)
                                                          : (Math.ceil(Number(data.machining) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="machining_eff" header="Machining Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.machining_eff || Number(data.machining_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.machining_eff) * 100) % 1 === 0
                                                        ? (Number(data.machining_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.machining_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="shotPeening" header="Shotpeening" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.shotPeening || Number(data.shotPeening) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.shotPeening))
                                                        ? Number(data.shotPeening)
                                                        : Number(data.shotPeening).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.shotPeening)
                                                          : (Math.ceil(Number(data.shotPeening) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="shotPeening_eff" header="Shotpeening Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.shotPeening_eff || Number(data.shotPeening_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.shotPeening_eff) * 100) % 1 === 0
                                                        ? (Number(data.shotPeening_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.shotPeening_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="ced" header="CED" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.ced || Number(data.ced) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.ced))
                                                        ? Number(data.ced)
                                                        : Number(data.ced).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.ced)
                                                          : (Math.ceil(Number(data.ced) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="ced_eff" header="CED Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.ced_eff || Number(data.ced_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.ced_eff) * 100) % 1 === 0
                                                        ? (Number(data.ced_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.ced_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="topcoat" header="Topcoat" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.topcoat || Number(data.topcoat) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.topcoat))
                                                        ? Number(data.topcoat)
                                                        : Number(data.topcoat).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.topcoat)
                                                          : (Math.ceil(Number(data.topcoat) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="topcoat_eff" header="Topcoat Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.topcoat_eff || Number(data.topcoat_eff) === 0"> 0% </template>
                                            <template v-else>
                                                {{
                                                    (Number(data.topcoat_eff) * 100) % 1 === 0
                                                        ? (Number(data.topcoat_eff) * 100).toFixed(0) + '%'
                                                        : (Number(data.topcoat_eff) * 100).toFixed(1) + '%'
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="packing_dom" header="Packing Domestic" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.packing_dom || Number(data.packing_dom) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.packing_dom))
                                                        ? Number(data.packing_dom)
                                                        : Number(data.packing_dom).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.packing_dom)
                                                          : (Math.ceil(Number(data.packing_dom) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="packing_exp" header="Packing Export" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            <template v-if="!data.packing_exp || Number(data.packing_exp) === 0"> - </template>
                                            <template v-else>
                                                {{
                                                    Number.isInteger(Number(data.packing_exp))
                                                        ? Number(data.packing_exp)
                                                        : Number(data.packing_exp).toString().split('.')[1]?.length <= 2
                                                          ? Number(data.packing_exp)
                                                          : (Math.ceil(Number(data.packing_exp) * 100) / 100).toFixed(2)
                                                }}
                                            </template>
                                        </template>
                                    </Column>

                                    <Column field="created_at_formatted" sortable header="Added at" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.created_at) }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="updated_at_formatted"
                                        sortable
                                        header="Updated at"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.updated_at) }}
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="2">
                            <section ref="sqSection" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 hover:text-indigo-500 md:mb-0 dark:text-white">
                                        Sales Quantity
                                    </h2>

                                    <div class="mb-4 flex flex-col items-center gap-4 md:mb-0">
                                        <FileUpload
                                            v-if="auth?.user?.permissions?.includes('Update_MasterData')"
                                            ref="fileUploaderBP"
                                            mode="basic"
                                            name="file"
                                            :customUpload="true"
                                            accept=".csv"
                                            chooseLabel="Import CSV"
                                            chooseIcon="pi pi-upload"
                                            @select="(event) => handleCSVImport(event, 'sq')"
                                            class="w-full sm:w-auto"
                                        />

                                        <div class="flex w-full flex-col items-center gap-4 sm:w-auto sm:flex-row">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 sm:w-28"
                                                @click="exportCSV('sq')"
                                            />
                                        </div>
                                    </div>

                                    <div class="text-right text-gray-700 dark:text-gray-300">
                                        <div>
                                            Last Update :
                                            <span class="text-red-300">{{ lastUpdate[2] ? formatlastUpdate(lastUpdate[2]) : '-' }}</span>
                                        </div>
                                        <div>
                                            Data source From :
                                            <span class="text-cyan-400">{{ dataSource[2] }}</span>
                                        </div>
                                    </div>
                                </div>
                                <DataTable
                                    :value="salesQuantity"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    :rowsPerPageOptions="[10, 20, 50, 100]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filterSQ"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['bp_code', 'bp.bp_name', 'item_code', 'item.type']"
                                    class="text-md"
                                    ref="dtSQ"
                                >
                                    <Column field="no" sortable header="No" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>

                                    <Column
                                        field="bp_code"
                                        header="BP Code"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search BP code"
                                                class="w-full"
                                            /> </template
                                    ></Column>

                                    <Column
                                        field="bp.bp_name"
                                        header="Customer"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search Customer"
                                                class="w-full"
                                            /> </template
                                    ></Column>

                                    <Column
                                        field="item_code"
                                        header="Item Code"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search Item code"
                                                class="w-full"
                                            />
                                        </template>
                                    </Column>

                                    <Column
                                        field="item.type"
                                        header="Name"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search Name"
                                                class="w-full"
                                            />
                                        </template>
                                    </Column>

                                    <Column field="item.type" sortable header="Type" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ data.item?.type ? data.item.type : '-' }}
                                        </template>
                                    </Column>

                                    <Column field="quantity" sortable header="Quantity" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>
                                    <Column field="created_at_formatted" sortable header="Added at" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.created_at) }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="updated_at_formatted"
                                        sortable
                                        header="Updated at"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.updated_at) }}
                                        </template>
                                    </Column>

                                    <Column field="action" header="Action" :exportable="false" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="slotProps">
                                            <div class="flex gap-2">
                                                <Button icon="pi pi-pencil" severity="warning" rounded text @click="editData(slotProps.data, 'sq')" />
                                                <Button
                                                    icon="pi pi-trash"
                                                    severity="danger"
                                                    rounded
                                                    text
                                                    @click="destroyData(slotProps.data, 'sq')"
                                                />
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="3">
                            <section ref="wdSection" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 hover:text-indigo-500 md:mb-0 dark:text-white">
                                        Production Cost
                                    </h2>

                                    <div class="mb-4 flex flex-col items-center gap-4 md:mb-0">
                                        <FileUpload
                                            v-if="auth?.user?.permissions?.includes('Update_MasterData')"
                                            ref="fileUploaderBP"
                                            mode="basic"
                                            name="file"
                                            :customUpload="true"
                                            accept=".csv"
                                            chooseLabel="Import CSV"
                                            chooseIcon="pi pi-upload"
                                            @select="(event) => handleCSVImport(event, 'wd')"
                                            class="w-full sm:w-auto"
                                        />

                                        <!-- <div class="flex w-full flex-col items-center gap-4 sm:w-auto sm:flex-row">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 sm:w-28"
                                                @click="exportCSV('wd')"
                                            />
                                        </div> -->
                                    </div>

                                    <div class="text-right text-gray-700 dark:text-gray-300">
                                        <div>
                                            Last Update :
                                            <span class="text-red-300">{{ lastUpdate[3] ? formatlastUpdate(lastUpdate[3]) : '-' }}</span>
                                        </div>
                                        <div>
                                            Data source From :
                                            <span class="text-cyan-400">{{ dataSource[3] }}</span>
                                        </div>
                                    </div>
                                </div>
                                <Panel>
                                    <div>
                                        <Card class="w-full">
                                            <template #title>
                                                <div class="rounded bg-slate-200 py-2 text-center text-lg font-bold text-gray-900">
                                                    Total : {{ lineTotals.toLocaleString('id-ID') }}
                                                </div>
                                            </template>

                                            <template #content>
                                                <Chart type="bar" :data="chartDataTotal" :options="chartOptions" class="h-[20rem]" />
                                            </template>

                                            <template #footer>
                                                <Button
                                                    v-if="auth?.user?.permissions?.includes('Update_MasterData')"
                                                    label="Edit"
                                                    class="w-full"
                                                    severity="info"
                                                    @click="editData(wagesDistribution, 'wd')"
                                                />
                                            </template>
                                        </Card>
                                    </div>

                                    <div class="mt-8 flex items-center justify-between">
                                        <Card style="width: 25rem">
                                            <template #title>
                                                <div class="rounded bg-cyan-500 py-2 text-center text-lg font-bold text-gray-900 text-white">
                                                    Line Disc : {{ lineDiscTotal.toLocaleString('id-ID') }}
                                                </div>
                                            </template>
                                            <template #content>
                                                <Chart type="bar" :data="chartDataDisc" :options="chartOptions" class="h-[20rem]" />
                                            </template>
                                        </Card>

                                        <Card style="width: 25rem">
                                            <template #title>
                                                <div class="rounded bg-orange-500 py-2 text-center text-lg font-bold text-gray-900 text-white">
                                                    Line Rim : {{ lineRimTotal.toLocaleString('id-ID') }}
                                                </div>
                                            </template>
                                            <template #content>
                                                <Chart type="bar" :data="chartDataRim" :options="chartOptions" class="h-[20rem]" />
                                            </template>
                                        </Card>

                                        <Card style="width: 25rem">
                                            <template #title>
                                                <div class="rounded bg-green-500 py-2 text-center text-lg font-bold text-gray-900 text-white">
                                                    Line Sidering : {{ lineSideringTotal.toLocaleString('id-ID') }}
                                                </div>
                                            </template>
                                            <template #content>
                                                <Chart type="bar" :data="chartDataSidering" :options="chartOptions" class="h-[20rem]" />
                                            </template>
                                        </Card>
                                    </div>

                                    <div class="mt-8 flex items-center justify-between">
                                        <Card style="width: 25rem">
                                            <template #title>
                                                <div class="rounded bg-purple-500 py-2 text-center text-lg font-bold text-gray-900 text-white">
                                                    Line Assy : {{ lineAssyTotal.toLocaleString('id-ID') }}
                                                </div>
                                            </template>
                                            <template #content>
                                                <Chart type="bar" :data="chartDataAssy" :options="chartOptions" class="h-[20rem]" />
                                            </template>
                                        </Card>

                                        <Card style="width: 25rem">
                                            <template #title>
                                                <div class="rounded bg-red-500 py-2 text-center text-lg font-bold text-gray-900 text-white">
                                                    Line Painting : {{ linePaintingTotal.toLocaleString('id-ID') }}
                                                </div>
                                            </template>
                                            <template #content>
                                                <Chart type="bar" :data="chartDataPainting" :options="chartOptions" class="h-[20rem]" />
                                            </template>
                                        </Card>

                                        <Card style="width: 25rem">
                                            <template #title>
                                                <div class="rounded bg-blue-500 py-2 text-center text-lg font-bold text-gray-900 text-white">
                                                    Line Packaging : {{ linePackingTotal.toLocaleString('id-ID') }}
                                                </div>
                                            </template>
                                            <template #content>
                                                <Chart type="bar" :data="chartDataPacking" :options="chartOptions" class="h-[20rem]" />
                                            </template>
                                        </Card>
                                    </div>
                                </Panel>
                            </section>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </div>
    </AppLayout>
</template>
