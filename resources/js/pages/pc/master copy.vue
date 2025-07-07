<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
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
import { computed, onMounted, reactive, ref } from 'vue';

const filters = ref({
    bp_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
});
const loading = ref(false);

const dtBP = ref();
const dtCT = ref();
const dtSQ = ref();
const dtWD = ref();
const toast = useToast();
const page = usePage();

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
    'Share Others/Finacc/ProcessCost/Business Partner(BP)/bp_master.csv',
    'Share Others/Finacc/ProcessCost/Cycle Time (CT)/ct_master.csv',
    'Share Others/Finacc/ProcessCost/Sales Quantity (SQ)/sq_master.csv',
    'Share Others/Finacc/ProcessCost/Wages Distribution (WD)/wd_master.csv',
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
const linePaintingTotal = computed(() => calculateTotal(['ced', 'topcoat', 'machining', 'shotPeening']));
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
const editType = ref<'ct' | 'sq' | 'wd' | null>(null);
const addType = ref<'bp' | null>(null);

const destroyType = ref<'ct' | 'sq' | 'bp' | null>(null);
const headerType = ref<any>({});
const showImportDialog = ref(false);
const importInProgress = ref(false);
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

function handleCSVImport(event: FileUploadUploaderEvent, type: 'bp' | 'ct' | 'sq' | 'wd') {
    let file: File | undefined;
    if (Array.isArray(event.files)) {
        file = event.files[0];
    } else if (event.files instanceof File) {
        file = event.files;
    }
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        console.error('CSRF token not found in meta tag!');
        return;
    }

    let $route = null;
    if (type === 'bp') {
        $route = 'bp.import';
    } else if (type === 'ct') {
        $route = 'ct.import';
    } else if (type === 'sq') {
        $route = 'sq.import';
    } else if (type === 'wd') {
        $route = 'wd.import';
    }

    // ✅ Mulai import: tampilkan dialog dan progress
    showImportDialog.value = true;
    importInProgress.value = true;

    router.post(route($route), formData, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.add({ severity: 'success', group: 'br', summary: 'Success', detail: 'CSV imported', life: 3000 });
            if (type === 'wd') {
                updateChartData();
            }
            // ✅ Import selesai
            importInProgress.value = false;
        },
        onError: () => {
            toast.add({ severity: 'error', group: 'br', summary: 'Error', detail: 'Import failed', life: 3000 });
            importInProgress.value = false;
        },
    });
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
</script>

<template>
    <Toast group="br" position="bottom-right" />

    <Head title="Process Cost" />
    <AppLayout>
        <div class="m-6">
            <div class="flex flex-col gap-1">
                <h2 class="mb-2 text-start text-3xl font-bold text-gray-900 dark:text-white">Process Cost Calculation</h2>
                <p class="text-start text-gray-600 dark:text-gray-400">Calculation for each process for all product</p>
            </div>
            <!-- Header Section -->
            <div class="mt-4 mb-8">
                <div class="relative mb-6 text-center">
                    <h1 class="relative z-10 inline-block bg-white px-4 text-2xl font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        Master Data Section
                    </h1>
                    <hr class="absolute top-1/2 left-0 z-0 w-full -translate-y-1/2 border-gray-300 dark:border-gray-600" />
                </div>

                <Dialog v-model:visible="showImportDialog" :closable="false" header="Importing CSV" modal class="w-[30rem]">
                    <div v-if="importInProgress">
                        <ProgressBar mode="indeterminate" style="height: 6px" />
                        <p class="mt-2 text-center">Importing, please wait...</p>
                    </div>
                    <div v-else class="text-center">
                        <p class="mb-3">Import complete!</p>
                        <Button label="Close" icon="pi pi-times" @click="showImportDialog = false" />
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
                                        editType = null;
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
                <Tabs value="0">
                    <TabList>
                        <Tab value="0">Business Partner</Tab>
                        <Tab value="1">Cycle Time</Tab>
                        <Tab value="2">Sales Quantity</Tab>
                        <Tab value="3">Wages Distribution</Tab>
                    </TabList>
                    <!-- Process Items Grid -->
                    <TabPanels>
                        <TabPanel value="0">
                            <section ref="bpSection" class="p-2">
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-3xl font-semibold hover:text-indigo-500">Business Partner</h2>
                                    <div class="flex gap-4">
                                        <div>
                                            <div>
                                                Last Update :
                                                <span class="text-red-300">{{ lastUpdate[0] ? formatlastUpdate(lastUpdate[0]) : '-' }}</span>
                                            </div>
                                            <div>
                                                Data source From : <span class="text-cyan-300">{{ dataSource[0] }}</span>
                                            </div>
                                        </div>

                                        <FileUpload
                                            mode="basic"
                                            chooseIcon="pi pi-upload"
                                            name="file"
                                            :auto="true"
                                            :customUpload="true"
                                            accept=".csv"
                                            chooseLabel="Import CSV"
                                            @uploader="(event) => handleCSVImport(event, 'bp')"
                                        />

                                        <Button
                                            icon="pi pi-users
"
                                            class="text-end"
                                            label="Add BP"
                                            @click="addData('bp')"
                                        />
                                        <Button icon="pi pi-download" class="text-end" label="Export" @click="exportCSV('bp')" />
                                    </div>
                                </div>

                                <DataTable
                                    :value="businessPartners"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filters"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['bp_code']"
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
                                    <Column field="bp_name" sortable header="BP Name" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>
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
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-3xl font-semibold hover:text-indigo-500">Cycle Time</h2>
                                    <div class="flex gap-4">
                                        <div>
                                            <div>
                                                Last Update :
                                                <span class="text-red-300">{{ lastUpdate[1] ? formatlastUpdate(lastUpdate[1]) : '-' }}</span>
                                            </div>
                                            <div>
                                                Data source From : <span class="text-cyan-300">{{ dataSource[1] }}</span>
                                            </div>
                                        </div>
                                        <FileUpload
                                            mode="basic"
                                            chooseIcon="pi pi-upload"
                                            name="file"
                                            :auto="true"
                                            :customUpload="true"
                                            accept=".csv"
                                            chooseLabel="Import CSV"
                                            @uploader="(event) => handleCSVImport(event, 'ct')"
                                        />
                                        <Button icon="pi pi-download" class="text-end" label="Export" @click="exportCSV('ct')" />
                                    </div>
                                </div>
                                <DataTable
                                    :value="cycleTimes"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filters"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['bp_code']"
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
                                                placeholder="Search Item code"
                                                class="w-full"
                                            /> </template
                                    ></Column>
                                    <Column field="size" header="Size" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="type" header="Type" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="blanking" header="Blanking" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="blanking_eff" header="Blanking Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="spinDisc" header="Spin Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="spinDisc_eff" header="Spin Disc Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="autoDisc" header="Auto Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="autoDisc_eff" header="Auto Disc Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="manualDisc" header="Manual Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="manualDisc_eff" header="Manual Disc Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="c3_sn" header="C3/SN" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="c3_sn_eff" header="C3/SN Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="repairC3" header="Repair C3" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="repairC3_eff" header="Repair C3 Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="discLathe" header="Disc Lathe" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="discLathe_eff" header="Disc Lathe Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="rim1" header="Rim 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="rim1_eff" header="Rim 1 Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="rim2" header="Rim 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="rim2_eff" header="Rim 2 Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="rim2insp" header="Rim 2 Insp." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="rim2insp_eff" header="Rim 2 Insp. Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="rim3" header="Rim 3" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="rim3_eff" header="Rim 3 Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="coiler" header="Coiler" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="coiler_eff" header="Coiler Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="forming" header="Forming" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="forming_eff" header="Forming Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="assy1" header="Assy 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="assy1_eff" header="Assy 1 Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="assy2" header="Assy 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="assy2_eff" header="Assy 2 Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="machining" header="Machining" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="machining_eff" header="Machining Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="shotPeening" header="Shotpeening" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="shotPeening_eff" header="Shotpeening Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="ced" header="CED" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="ced_eff" header="CED Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="topcoat" header="Topcoat" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="topcoat_eff" header="Topcoat Eff." :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="packing_dom" header="Packing DOM" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="packing_exp" header="Packing EXP" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

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
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-3xl font-semibold hover:text-indigo-500">Sales Quantity</h2>
                                    <div class="flex gap-4">
                                        <div>
                                            <div>
                                                Last Update :
                                                <span class="text-red-300">{{ lastUpdate[2] ? formatlastUpdate(lastUpdate[2]) : '-' }}</span>
                                            </div>
                                            <div>
                                                Data source From : <span class="text-cyan-300">{{ dataSource[2] }}</span>
                                            </div>
                                        </div>
                                        <FileUpload
                                            mode="basic"
                                            chooseIcon="pi pi-upload"
                                            name="file"
                                            :auto="true"
                                            :customUpload="true"
                                            accept=".csv"
                                            chooseLabel="Import CSV"
                                            @uploader="(event) => handleCSVImport(event, 'sq')"
                                        />
                                        <Button icon="pi pi-download" class="text-end" label="Export" @click="exportCSV('ct')" />
                                    </div>
                                </div>
                                <DataTable
                                    :value="salesQuantity"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filters"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['bp_code', 'item_code']"
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
                                        sortable
                                        header="Business Partner Name"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
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

                                    <Column field="item.type" sortable header="Type" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>

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
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-3xl font-semibold hover:text-indigo-500">Wages Distribution</h2>
                                    <div class="flex gap-4">
                                        <div>
                                            <div>
                                                Last Update :
                                                <span class="text-red-300">{{ lastUpdate[3] ? formatlastUpdate(lastUpdate[3]) : '-' }}</span>
                                            </div>
                                            <div>
                                                Data source From : <span class="text-cyan-300">{{ dataSource[3] }}</span>
                                            </div>
                                        </div>
                                        <FileUpload
                                            mode="basic"
                                            chooseIcon="pi pi-upload"
                                            name="file"
                                            accept=".csv"
                                            :customUpload="true"
                                            :maxFileSize="1000000"
                                            @uploader="(event) => handleCSVImport(event, 'wd')"
                                            :auto="true"
                                            chooseLabel="Import CSV"
                                        />
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
                                                <Button label="Edit" class="w-full" severity="info" @click="editData(wagesDistribution, 'wd')" />
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
