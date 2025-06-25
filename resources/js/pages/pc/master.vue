<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import FileUpload, { FileUploadUploaderEvent } from 'primevue/fileupload';
import Panel from 'primevue/panel';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref } from 'vue';

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

const updateChartData = () => {
    const wd = page.props.wagesDistribution as Record<string, string>;

    chartDataTotal.value = {
        labels: ['Line Disc', 'Line Rim', 'Line Sidering', 'Line Assy', 'Line Painting', 'Line Packing'],
        datasets: [
            {
                label: 'Total Wages Distribution',
                backgroundColor: ['#42A5F5', '#66BB6A', '#FFA726', '#AB47BC', '#FF7043', '#26A69A'],
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
                backgroundColor: ['#42A5F5', '#66BB6A', '#FFA726', '#AB47BC', '#FF7043'],
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
                backgroundColor: ['#42A5F5', '#66BB6A', '#FFA726'],
                data: [parseFloat(wd.rim1), parseFloat(wd.rim2), parseFloat(wd.rim3)],
            },
        ],
    };
    chartDataSidering.value = {
        labels: ['Coiler', 'Forming'],
        datasets: [
            {
                label: 'Total Wages Distribution',
                backgroundColor: ['#42A5F5', '#66BB6A'],
                data: [parseFloat(wd.coiler), parseFloat(wd.forming)],
            },
        ],
    };
    chartDataAssy.value = {
        labels: ['Assy 1', 'Assy 2', 'Machining', 'ShotPeening'],
        datasets: [
            {
                label: 'Total Wages Distribution',
                backgroundColor: ['#42A5F5', '#66BB6A', '#FFA726', '#AB47BC'],
                data: [parseFloat(wd.assy1), parseFloat(wd.assy2), parseFloat(wd.machining), parseFloat(wd.shotPeening)],
            },
        ],
    };
    chartDataPainting.value = {
        labels: ['CED', 'Top Coat'],
        datasets: [
            {
                label: 'Total Wages Distribution',
                backgroundColor: ['#42A5F5', '#66BB6A'],
                data: [parseFloat(wd.ced), parseFloat(wd.topcoat)],
            },
        ],
    };
    chartDataPacking.value = {
        labels: ['Packing Domestic', 'Packing Export'],
        datasets: [
            {
                label: 'Total Wages Distribution',
                backgroundColor: ['#42A5F5', '#66BB6A'],
                data: [parseFloat(wd.packing_dom), parseFloat(wd.packing_exp)],
            },
        ],
    };
};

const headerStyle = { backgroundColor: '#758596', color: 'white' };
const bodyStyle = { backgroundColor: '#c8cccc', color: 'black' };

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
        $route = 'bps.import';
    } else if (type === 'ct') {
        $route = 'ct.import';
    } else if (type === 'sq') {
        $route = 'sq.import';
    } else if (type === 'wd') {
        $route = 'wd.import';
    }

    router.post(route($route), formData, {
        preserveScroll: true,
        preserveState: true,
        // nama prop yang harus di-refresh
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Success', detail: 'CSV imported', life: 3000 });
            updateChartData();
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

function editBP(bp: any) {
    console.log('Edit', bp);
    // buka dialog edit atau redirect, tergantung desain kamu
}

function deleteBP(bp: any) {
    if (confirm(`Hapus ${bp.bp_code}?`)) {
        router.delete(route('bps.destroy', bp.id), {
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Deleted', detail: 'Data berhasil dihapus' });
            },
        });
    }
}
</script>

<template>
    <Head title="Process Cost" />
    <AppLayout>
        <!-- <template>
            <Dialog v-model:visible="showResultDialog" header="Import Result" modal class="w-[40rem]">
                <div v-if="importResults.success" class="mb-4 text-green-600">
                    {{ importResults.success }}
                </div>

                <div class="mb-2" v-if="importResults.added.length">
                    <strong class="text-green-700">Ditambahkan:</strong>
                    <ul class="list-disc pl-4">
                        <li v-for="item in importResults.added" :key="'added-' + item">{{ item }}</li>
                    </ul>
                </div>

                <div class="mb-2" v-if="importResults.updated.length">
                    <strong class="text-yellow-700">Diperbarui:</strong>
                    <ul class="list-disc pl-4">
                        <li v-for="item in importResults.updated" :key="'updated-' + item">{{ item }}</li>
                    </ul>
                </div>

                <div class="mb-2" v-if="importResults.invalid.length">
                    <strong class="text-red-700">Gagal:</strong>
                    <ul class="list-disc pl-4">
                        <li v-for="item in importResults.invalid" :key="'invalid-' + item">{{ item }}</li>
                    </ul>
                </div>

                <template #footer>
                    <Button label="Tutup" icon="pi pi-times" @click="showResultDialog = false" />
                </template>
            </Dialog>
        </template> -->
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
                                <h2 class="mb-4 text-3xl font-semibold hover:text-indigo-500">Business Partner</h2>

                                <DataTable
                                    :value="businessPartners"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    removableSort
                                    class="text-md"
                                    filterDisplay="header"
                                    ref="dtBP"
                                >
                                    <template #header>
                                        <div class="flex items-center justify-between">
                                            <FileUpload
                                                mode="basic"
                                                name="file"
                                                :auto="true"
                                                :customUpload="true"
                                                accept=".csv"
                                                chooseLabel="Import CSV"
                                                @uploader="(event) => handleCSVImport(event, 'bp')"
                                            />

                                            <Button icon="pi pi-download" class="text-end" label="Export" @click="exportCSV('bp')" />
                                        </div>
                                    </template>
                                    <Column field="bp_code" sortable header="BP Code" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>
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

                                    <Column field="action" header="Action" :exportable="false" :headerStyle="headerStyle" :bodyStyle="bodyStyle"
                                        ><template #body="slotProps">
                                            <div class="flex gap-2">
                                                <Button icon="pi pi-pencil" severity="warning" rounded text @click="editBP(slotProps.data)" />
                                                <Button icon="pi pi-trash" severity="danger" rounded text @click="deleteBP(slotProps.data)" />
                                            </div> </template
                                    ></Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="1">
                            <section ref="ctSection" class="p-2">
                                <h2 class="mb-4 text-3xl font-semibold hover:text-indigo-500">Cycle Time</h2>
                                <DataTable
                                    :value="cycleTimes"
                                    tableStyle="min-width: 50rem"
                                    :rows="10"
                                    paginator
                                    removableSort
                                    class="text-md"
                                    filterDisplay="header"
                                    ref="dtCT"
                                >
                                    <template #header>
                                        <div class="flex items-center justify-between">
                                            <FileUpload
                                                mode="basic"
                                                name="file"
                                                :auto="true"
                                                :customUpload="true"
                                                accept=".csv"
                                                chooseLabel="Import CSV"
                                                @uploader="(event) => handleCSVImport(event, 'ct')"
                                            />
                                            <Button icon="pi pi-download" class="text-end" label="Export" @click="exportCSV('ct')" />
                                        </div>
                                    </template>

                                    <Column field="item_code" header="Item Code" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
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
                                    <Column field="action" header="Action" :exportable="false" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="slotProps">
                                            <div class="flex gap-2">
                                                <Button icon="pi pi-pencil" severity="warning" rounded text @click="editBP(slotProps.data)" />
                                                <Button icon="pi pi-trash" severity="danger" rounded text @click="deleteBP(slotProps.data)" />
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="2">
                            <section ref="sqSection" class="p-2">
                                <h2 class="mb-4 text-3xl font-semibold hover:text-indigo-500">Sales Quantity</h2>
                                <DataTable
                                    :value="salesQuantity"
                                    tableStyle="500px"
                                    :rows="10"
                                    paginator
                                    removableSort
                                    class="text-md"
                                    filterDisplay="header"
                                    ref="dtSQ"
                                >
                                    <template #header>
                                        <div class="flex items-center justify-between">
                                            <FileUpload
                                                mode="basic"
                                                name="file"
                                                :auto="true"
                                                :customUpload="true"
                                                accept=".csv"
                                                chooseLabel="Import CSV"
                                                @uploader="(event) => handleCSVImport(event, 'sq')"
                                            />
                                            <Button icon="pi pi-download" class="text-end" label="Export" @click="exportCSV('ct')" />
                                        </div>
                                    </template>
                                    <Column field="no" sortable header="No" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>
                                    <Column
                                        field="bp_code"
                                        sortable
                                        header="Business Partner Code"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    ></Column>
                                    <Column
                                        field="bp.bp_name"
                                        sortable
                                        header="Business Partner Name"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    ></Column>

                                    <Column field="item_code" sortable header="Item Code" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>
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
                                                <Button icon="pi pi-pencil" severity="warning" rounded text @click="editBP(slotProps.data)" />
                                                <Button icon="pi pi-trash" severity="danger" rounded text @click="deleteBP(slotProps.data)" />
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="3">
                            <section ref="wdSection" class="p-2">
                                <h2 class="mb-4 text-3xl font-semibold hover:text-indigo-500">Wages Distribution</h2>

                                <Panel>
                                    <div class="flex items-start">
                                        <FileUpload
                                            mode="basic"
                                            name="file"
                                            accept=".csv"
                                            :customUpload="true"
                                            :maxFileSize="1000000"
                                            @uploader="(event) => handleCSVImport(event, 'wd')"
                                            :auto="true"
                                            chooseLabel="Import CSV"
                                        />
                                    </div>
                                    <div>
                                        <Card class="w-full">
                                            <template #title class="text-centre text-lg font-bold text-white">Total</template>
                                            <template #subtitle> {{ lineTotals.toLocaleString('id-ID') }}</template>
                                            <template #content>
                                                <Chart type="bar" :data="chartDataTotal" :options="chartOptions" class="h-[20rem]" />
                                            </template>
                                            <template #footer>
                                                <Button label="Edit" class="w-full" severity="info" />
                                            </template>
                                        </Card>
                                    </div>
                                    <div class="mt-8 flex items-center justify-between">
                                        <Card style="width: 25rem">
                                            <template #title>Line Disc</template>
                                            <template #subtitle> {{ lineDiscTotal.toLocaleString('id-ID') }}</template>
                                            <template #content>
                                                <Chart type="bar" :data="chartDataDisc" :options="chartOptions" class="h-[20rem]" />
                                            </template>
                                            <template #footer>
                                                <Button label="Edit" class="w-full" severity="info" />
                                            </template>
                                        </Card>

                                        <Card style="width: 25rem">
                                            <template #title>Line Rim</template>
                                            <template #subtitle> {{ lineRimTotal.toLocaleString('id-ID') }}</template>
                                            <template #content>
                                                <Chart type="bar" :data="chartDataRim" :options="chartOptions" class="h-[20rem]" />
                                            </template>
                                            <template #footer>
                                                <Button label="Edit" class="w-full" severity="info" />
                                            </template>
                                        </Card>

                                        <Card style="width: 25rem">
                                            <template #title>Line Sidering</template>
                                            <template #subtitle> {{ lineSideringTotal.toLocaleString('id-ID') }}</template>
                                            <template #content>
                                                <Chart type="bar" :data="chartDataSidering" :options="chartOptions" class="h-[20rem]" />
                                            </template>
                                            <template #footer>
                                                <Button label="Edit" class="w-full" severity="info" />
                                            </template>
                                        </Card>
                                    </div>
                                    <div class="mt-8 flex items-center justify-between">
                                        <Card style="width: 25rem">
                                            <template #title>Line Assy</template>
                                            <template #subtitle> {{ lineAssyTotal.toLocaleString('id-ID') }}</template>
                                            <template #content>
                                                <Chart type="bar" :data="chartDataAssy" :options="chartOptions" class="h-[20rem]" />
                                            </template>
                                            <template #footer>
                                                <Button label="Edit" class="w-full" severity="info" />
                                            </template>
                                        </Card>

                                        <Card style="width: 25rem">
                                            <template #title>Line Painting</template>
                                            <template #subtitle> {{ linePaintingTotal.toLocaleString('id-ID') }}</template>
                                            <template #content>
                                                <Chart type="bar" :data="chartDataPainting" :options="chartOptions" class="h-[20rem]" />
                                            </template>
                                            <template #footer>
                                                <Button label="Edit" class="w-full" severity="info" />
                                            </template>
                                        </Card>

                                        <Card style="width: 25rem">
                                            <template #title>Line Packaging</template>
                                            <template #subtitle> {{ linePackingTotal.toLocaleString('id-ID') }}</template>
                                            <template #content>
                                                <Chart type="bar" :data="chartDataPacking" :options="chartOptions" class="h-[20rem]" />
                                            </template>
                                            <template #footer>
                                                <Button label="Edit" class="w-full" severity="info" />
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
