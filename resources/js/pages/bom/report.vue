<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import dayjs from 'dayjs';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ColumnGroup from 'primevue/columngroup';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Row from 'primevue/row';
import Select from 'primevue/select';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import { useToast } from 'primevue/usetoast';
import { computed, nextTick, ref, watch } from 'vue';

const toast = useToast();
const page = usePage();
const dtBOM = ref();
const dtSC = ref();
const dtAC = ref();
const dtDIFF = ref();
const loading = ref(false);
const year = ref();
const month = ref();

const sc = computed(() =>
    (page.props.sc as any[]).map((sc, index) => {
        const typeChar: string = sc.item_code?.charAt(3) ?? '';
        const typeMap: Record<string, string> = {
            D: 'Disc',
            N: 'Sidering',
            W: 'Wheel',
            R: 'Rim',
        };
        const type_name = typeMap[typeChar] ?? sc.item_code;

        return {
            ...sc,
            no: index + 1,
            type_name,
        };
    }),
);

const ac = computed(() =>
    (page.props.ac as any[]).map((ac, index) => {
        const typeChar: string = ac.item_code?.charAt(3) ?? '';
        const typeMap: Record<string, string> = {
            D: 'Disc',
            N: 'Sidering',
            W: 'Wheel',
            R: 'Rim',
        };
        const type_name = typeMap[typeChar] ?? ac.item_code;

        return {
            ...ac,
            no: index + 1,
            type_name,
        };
    }),
);

const dc = computed(() =>
    (page.props.dc as any[]).map((dc, index) => ({
        ...dc,
        no: index + 1,
    })),
);

const makePeriod = (year: number, month: number) => {
    // Buat objek Date untuk mendapatkan nama bulan singkat
    const date = new Date(year, month - 1);
    const monthName = date.toLocaleString('en-US', { month: 'short' });

    // Ambil dua digit terakhir dari tahun
    const yearDigits = String(year).slice(-2);

    // Gabungkan dengan tanda hubung secara manual
    return `${monthName}-${yearDigits}`;
};

const combinedData = computed(() => {
    const sc = (page.props.sc || []) as any[];
    const ac = (page.props.ac || []) as any[];
    const dc = (page.props.dc || []) as any[];

    if (!Array.isArray(sc) || !Array.isArray(ac) || !Array.isArray(dc)) {
        return [];
    }

    // Fungsi untuk buat period dari tahun & bulan

    const standardCostMap = new Map();
    sc.forEach((item) => {
        const key = `${item.report_year}-${item.report_month}-${item.item_code}`;
        standardCostMap.set(key, item);
    });

    const actualCostMap = new Map();
    ac.forEach((item) => {
        const key = `${item.report_year}-${item.report_month}-${item.item_code}`;
        actualCostMap.set(key, item);
    });

    const combined = dc.map((dcItem) => {
        const standardKey = `${dcItem.standard_year}-${dcItem.standard_month}-${dcItem.item_code}`;
        const actualKey = `${dcItem.actual_year}-${dcItem.actual_month}-${dcItem.item_code}`;

        return {
            item_code: dcItem.item_code,

            // Cost data
            standard_cost: standardCostMap.get(standardKey) || { total_raw_material: 0, total_process: 0, total: 0 },
            actual_cost: actualCostMap.get(actualKey) || { total_raw_material: 0, total_process: 0, total: 0 },
            difference_cost: dcItem,

            // Period string
            standardPeriod: makePeriod(dcItem.standard_year, dcItem.standard_month),
            actualPeriod: makePeriod(dcItem.actual_year, dcItem.actual_month),
        };
    });

    return combined.sort((a, b) => a.item_code.localeCompare(b.item_code));
});

interface StandardPeriod {
    name: string;
    code: string;
}

interface ActualPeriod {
    name: string;
    code: string;
}

const selectStandardPeriod = ref<StandardPeriod | null>(null); // Inisialisasi dengan tipe data yang benar
const selectActualPeriod = ref<ActualPeriod | null>(null); // Inisialisasi dengan tipe data yang benar

// Watcher untuk menghubungkan Select ke filters
watch(selectStandardPeriod, (newValue) => {
    if (newValue) {
        // Pisahkan string 'code' menjadi tahun dan bulan
        const [year, month] = newValue.code.split('-').map(Number);

        // Perbarui filters
        filtersStandard.value.report_year.value = year;
        filtersStandard.value.report_month.value = month;
        filtersDifference.value.standardPeriod.value = makePeriod(year, month);
    } else {
        // Reset filter jika Select dikosongkan
        filtersStandard.value.report_year.value = null;
        filtersStandard.value.report_month.value = null;
        filtersDifference.value.standardPeriod.value = null;
    }
});

watch(selectActualPeriod, (newValue) => {
    if (newValue) {
        // Pisahkan string 'code' menjadi tahun dan bulan
        const [year, month] = newValue.code.split('-').map(Number);

        // Perbarui filters
        filtersActual.value.report_year.value = year;
        filtersActual.value.report_month.value = month;
        filtersDifference.value.actualPeriod.value = makePeriod(year, month);
    } else {
        // Reset filter jika Select dikosongkan
        filtersActual.value.report_year.value = null;
        filtersActual.value.report_month.value = null;
        filtersDifference.value.actualPeriod.value = null;
    }
});

const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const listStandardPeriod = computed(() => {
    // 1. Ambil semua kombinasi tahun dan bulan yang tersedia
    const periods = (page.props.sc as any[]).map((item) => ({
        year: item.report_year,
        month: item.report_month,
    }));

    const uniquePeriodsMap = new Map<string, { year: number; month: number }>();
    periods.forEach((p) => {
        const key = `${p.year}-${p.month}`;
        uniquePeriodsMap.set(key, p);
    });

    const uniquePeriods = Array.from(uniquePeriodsMap.values()).sort((a, b) => {
        if (a.year !== b.year) {
            return a.year - b.year;
        }
        return a.month - b.month;
    });

    return uniquePeriods.map((p) => {
        const formattedYear = String(p.year).slice(2);
        const formattedMonth = monthNames[p.month - 1];

        return {
            name: `${formattedMonth}-${formattedYear}`,
            code: `${p.year}-${p.month}`, // Code yang unik untuk identifikasi
        };
    });
});

const listActualPeriod = computed(() => {
    // 1. Ambil semua kombinasi tahun dan bulan yang tersedia
    const periods = (page.props.ac as any[]).map((item) => ({
        year: item.report_year,
        month: item.report_month,
    }));

    const uniquePeriodsMap = new Map<string, { year: number; month: number }>();
    periods.forEach((p) => {
        const key = `${p.year}-${p.month}`;
        uniquePeriodsMap.set(key, p);
    });

    const uniquePeriods = Array.from(uniquePeriodsMap.values()).sort((a, b) => {
        if (a.year !== b.year) {
            return a.year - b.year;
        }
        return a.month - b.month;
    });

    return uniquePeriods.map((p) => {
        const formattedYear = String(p.year).slice(2);
        const formattedMonth = monthNames[p.month - 1];

        return {
            name: `${formattedMonth}-${formattedYear}`,
            code: `${p.year}-${p.month}`, // Code yang unik untuk identifikasi
        };
    });
});

const filtersStandard = ref({
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    type_name: { value: null, matchMode: FilterMatchMode.EQUALS },
    'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
    report_year: { value: null as number | null, matchMode: FilterMatchMode.EQUALS },
    report_month: { value: null as number | null, matchMode: FilterMatchMode.EQUALS },
});

const filtersActual = ref({
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    type_name: { value: null, matchMode: FilterMatchMode.EQUALS },
    'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
    report_year: { value: null as number | null, matchMode: FilterMatchMode.EQUALS },
    report_month: { value: null as number | null, matchMode: FilterMatchMode.EQUALS },
});

const filtersDifference = ref({
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    type_name: { value: null, matchMode: FilterMatchMode.EQUALS },
    'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
    standardPeriod: { value: null as string | null, matchMode: FilterMatchMode.EQUALS },
    actualPeriod: { value: null as string | null, matchMode: FilterMatchMode.EQUALS },

    standard_year: { value: null as number | null, matchMode: FilterMatchMode.EQUALS },
    standard_month: { value: null as number | null, matchMode: FilterMatchMode.EQUALS },
    actual_year: { value: null as number | null, matchMode: FilterMatchMode.EQUALS },
    actual_month: { value: null as number | null, matchMode: FilterMatchMode.EQUALS },
});

const getMonthName = (monthNumber: number): string => {
    if (typeof monthNumber !== 'number' || monthNumber < 1 || monthNumber > 12) {
        return '-';
    }
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    // Kurangi 1 karena array berbasis 0
    return monthNames[monthNumber - 1];
};

// const selectedStandardYear = ref<Date | null>(null);
// const selectedStandardMonth = ref<Date | null>(null);
// const selectedActualYear = ref<Date | null>(null);
// const selectedActualMonth = ref<Date | null>(null);

// watch(selectedStandardYear, (newValue: Date | null) => {
//     if (newValue instanceof Date) {
//         // This check is now valid because newValue can be a Date
//         filters.value.report_year.value = newValue.getFullYear();
//     } else {
//         filters.value.report_year.value = null;
//     }
// });

// watch(selectedStandardMonth, (newValue: Date | null) => {
//     if (newValue instanceof Date) {
//         // .getMonth() mengembalikan nilai 0-11.
//         // Jika data Anda 1-12, tambahkan +1.
//         filters.value.report_month.value = newValue.getMonth() + 1;
//     } else {
//         filters.value.report_month.value = null;
//     }
// });

// watch(selectedActualYear, (newValue: Date | null) => {
//     if (newValue instanceof Date) {
//         // This check is now valid because newValue can be a Date
//         filters.value.report_year.value = newValue.getFullYear();
//     } else {
//         filters.value.report_year.value = null;
//     }
// });

// watch(selectedActualMonth, (newValue: Date | null) => {
//     if (newValue instanceof Date) {
//         // .getMonth() mengembalikan nilai 0-11.
//         // Jika data Anda 1-12, tambahkan +1.
//         filters.value.report_month.value = newValue.getMonth() + 1;
//     } else {
//         filters.value.report_month.value = null;
//     }
// });

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

const type = ['All', 'Disc', 'Sidering', 'Wheel'];

function getTypeClass(priority: string): string | undefined {
    switch (priority) {
        case 'All':
            return 'secondary';
        case 'Disc':
            return 'bg-purple-400 text-purple-800';
        case 'Sidering':
            return 'bg-blue-300 text-blue-800';
        case 'Wheel':
            return 'bg-orange-400 text-orange-800';
        default:
            return undefined;
    }
}

function capitalize(text: string): string {
    return text.charAt(0).toUpperCase() + text.slice(1);
}

function exportCSV(type: 'BOM' | 'standardCost' | 'actualCost' | 'diffCost') {
    if (type === 'BOM' && dtBOM.value) {
        const exportFilename = `Bill-of-Material-${new Date().toISOString().slice(0, 10)}.csv`;
        dtBOM.value.exportCSV({ selectionOnly: false, filename: exportFilename });
    } else if (type === 'standardCost' && dtSC.value) {
        const exportFilename = `StandardCost-${new Date().toISOString().slice(0, 10)}.csv`;
        dtSC.value.exportCSV({ selectionOnly: false, filename: exportFilename });
    } else if (type === 'actualCost' && dtAC.value) {
        const exportFilename = `ActualCost-${new Date().toISOString().slice(0, 10)}.csv`;
        dtAC.value.exportCSV({ selectionOnly: false, filename: exportFilename });
    } else if (type === 'diffCost' && dtAC.value) {
        const exportFilename = `DiffCost-${new Date().toISOString().slice(0, 10)}.csv`;
        dtDIFF.value.exportCSV({ selectionOnly: false, filename: exportFilename });
    }
}

const lastUpdate = computed(() => {
    const SC_update = ((page.props.sc as any[]) ?? []).map((SC) => new Date(SC.updated_at));
    const Max_SCUpdate = SC_update.length ? new Date(Math.max(...SC_update.map((d) => d.getTime()))) : null;

    const AC_update = ((page.props.ac as any[]) ?? []).map((AC) => new Date(AC.updated_at));
    const Max_ACUpdate = AC_update.length ? new Date(Math.max(...AC_update.map((d) => d.getTime()))) : null;

    return [Max_SCUpdate, Max_ACUpdate];
});

const lastMaster = computed(() => page.props.lastMaster as any);

function formatlastUpdate(date: Date | string) {
    return dayjs(date).format('DD MMM YYYY HH:mm:ss');
}

const updateReportDialog = ref(false);
const updateConstDialog = ref(false);
type UpdateStatus = 'idle' | 'updating' | 'done';
const updateStatus = ref<UpdateStatus>('idle');
const userName = computed(() => page.props.auth?.user?.name ?? '');
const updateType = ref<'standardCost' | 'actualCost' | 'diffCost' | 'opgin' | null>(null);

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

function showUpdateDialog(type: 'standardCost' | 'actualCost' | 'opgin' | 'diffCost') {
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

function confirmUpdate() {
    if (!updateType.value) return;

    let payload = {}; // Inisialisasi payload di awal

    if (updateType.value !== 'diffCost') {
        if (!year.value || !month.value) {
            toast.add({
                severity: 'warn',
                summary: 'Peringatan',
                group: 'br',
                detail: 'Silakan pilih tahun dan bulan terlebih dahulu',
                life: 3000,
            });
            return;
        }

        // Isi payload untuk tipe selain 'diffCost'
        payload = {
            year: year.value.getFullYear(),
            month: month.value.getMonth() + 1,
        };
    } else if (updateType.value === 'diffCost') {
        // Logika validasi dan payload untuk 'diffCost'
        if (!selectStandardPeriod.value || !selectActualPeriod.value) {
            toast.add({
                severity: 'warn',
                summary: 'Peringatan',
                group: 'br',
                detail: 'Silakan pilih periode standard dan actual terlebih dahulu',
                life: 3000,
            });
            return;
        }

        const [standardYear, standardMonth] = selectStandardPeriod.value.code.split('-');
        const [actualYear, actualMonth] = selectActualPeriod.value.code.split('-');

        payload = {
            standard_year: standardYear,
            standard_month: standardMonth,
            actual_year: actualYear,
            actual_month: actualMonth,
        };
    }

    updateStatus.value = 'updating';
    const type = updateType.value;

    const routes = {
        standardCost: 'bom.updateSC',
        actualCost: 'bom.updateAC',
        diffCost: 'bom.updateDC',
        opgin: 'bom.updateOpGin',
    };

    const messages = {
        standardCost: 'Standard Cost',
        actualCost: 'Actual Cost',
        diffCost: 'Difference Cost',
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
}

function openPreviewTab(item_code: string, opex: number, progin: number, previewType: string) {
    let previewUrl;

    // Memeriksa nilai `previewType` untuk menentukan route yang benar
    if (previewType === 'actualCost') {
        previewUrl = route('preview.ac', {
            item_code: item_code,
            opex: opex,
            progin: progin,
        });
    } else {
        // Default ke 'standardCost' jika tidak ada atau tidak cocok
        previewUrl = route('preview.sc', {
            item_code: item_code,
            opex: opex,
            progin: progin,
        });
    }

    // Buka URL di tab baru
    window.open(previewUrl, '_blank');
}

let opexDef = ref(6);
let proginDef = ref(5);
const tempOpex = ref<number | null>(null);
const tempProgin = ref<number | null>(null);

const maxDate = ref(new Date());
</script>

<template>
    <Head title="Bill of Material" />
    <AppLayout>
        <div class="p-6">
            <div class="flex flex-col gap-1">
                <h2 class="mb-2 text-start text-3xl font-bold text-gray-900 dark:text-white">Bill of Material</h2>
                <p class="text-start text-gray-600 dark:text-gray-400">Calculating Bill of Material</p>
            </div>

            <div class="mb-8">
                <div class="relative mb-6 text-center">
                    <h1 class="relative z-10 inline-block bg-white px-4 text-2xl font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
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
                        <div v-if="updateType !== 'diffCost'" class="mt-6 mb-2 font-semibold">Select Period:</div>
                        <div v-if="updateType !== 'diffCost'" class="flex space-x-4">
                            <div class="flex-1">
                                <label for="report-month" class="block text-sm font-medium text-gray-400">Month</label>
                                <DatePicker v-model="month" view="month" dateFormat="mm" :maxDate="maxDate" />
                            </div>
                            <div class="flex-1">
                                <label for="report-year" class="block text-sm font-medium text-gray-400">Year</label>
                                <DatePicker v-model="year" view="year" dateFormat="yy" :maxDate="maxDate" />
                            </div>
                        </div>

                        <div v-if="updateType === 'diffCost'" class="mt-6 mb-2 font-semibold">Select Report Period:</div>
                        <div v-if="updateType === 'diffCost'" class="flex flex-col space-y-4 md:flex-row md:space-y-0 md:space-x-4">
                            <div class="flex-1">
                                <label for="standard-month" class="block text-sm font-medium text-gray-400">Standard Period</label>
                                <Select
                                    v-model="selectStandardPeriod"
                                    :options="listStandardPeriod"
                                    optionLabel="name"
                                    placeholder="Select a period"
                                    class="w-64"
                                />
                            </div>
                            <div class="flex-1">
                                <label for="standard-month" class="block text-sm font-medium text-gray-400">Actual Period</label>
                                <Select
                                    v-model="selectActualPeriod"
                                    :options="listActualPeriod"
                                    optionLabel="name"
                                    placeholder="Select a period"
                                    class="w-64"
                                />
                            </div>
                        </div>

                        <p v-if="updateType !== 'diffCost'" class="mt-6 mb-2 font-semibold">Make sure this data is up to date:</p>
                        <div v-if="updateType !== 'diffCost'" class="overflow-x-auto">
                            <table v-if="updateType === 'standardCost' || 'actualCost'" class="w-full border-collapse text-left">
                                <thead>
                                    <tr>
                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Data</th>
                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Last Update</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="updateType === 'standardCost'">
                                        <td class="border-b border-gray-800 px-4 py-2">Standard Material Price</td>
                                        <td class="border-b border-gray-800 px-4 py-2">
                                            <span class="text-red-300">{{ lastMaster[0] ? formatlastUpdate(lastMaster[0]) : '-' }}</span>
                                        </td>
                                    </tr>
                                    <tr v-if="updateType === 'actualCost'">
                                        <td class="border-b border-gray-800 px-4 py-2">Actual Material Price</td>
                                        <td class="border-b border-gray-800 px-4 py-2">
                                            <span class="text-red-300">{{ lastMaster[1] ? formatlastUpdate(lastMaster[1]) : '-' }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border-b border-gray-800 px-4 py-2">Valve Price</td>
                                        <td class="border-b border-gray-800 px-4 py-2">
                                            <span class="text-red-300">{{ lastMaster[2] ? formatlastUpdate(lastMaster[2]) : '-' }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border-b border-gray-800 px-4 py-2">Bill of Material</td>
                                        <td class="border-b border-gray-800 px-4 py-2">
                                            <span class="text-red-300">{{ lastMaster[3] ? formatlastUpdate(lastMaster[3]) : '-' }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border-b border-gray-800 px-4 py-2">Process Cost</td>
                                        <td class="border-b border-gray-800 px-4 py-2">
                                            <span class="text-red-300">{{ lastMaster[4] ? formatlastUpdate(lastMaster[4]) : '-' }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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
                <Tabs value="0">
                    <TabList>
                        <Tab value="0">BOM</Tab>
                        <Tab value="1">Standard Cost</Tab>
                        <Tab value="2">Actual Cost</Tab>
                        <Tab value="3">Difference</Tab>
                    </TabList>

                    <TabPanels>
                        <TabPanel value="0">
                            <section class="p-2">
                                <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <!-- Title -->
                                    <h2 class="text-3xl font-semibold text-gray-900 dark:text-white">Bill Of Material</h2>

                                    <!-- Main Controls Container -->
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                                        <!-- Export and Update Report Buttons -->
                                        <div class="flex flex-col gap-2 sm:flex-row sm:gap-4">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export Report"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-orange-700 sm:w-auto"
                                                @click="exportCSV('BOM')"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Last Update Info -->
                                <div class="mb-4 text-right text-gray-700 dark:text-gray-300">
                                    <div>
                                        Last Update :
                                        <span class="text-red-300">{{ lastMaster[3] ? formatlastUpdate(lastMaster[3]) : '-' }}</span>
                                    </div>
                                </div>

                                <DataTable
                                    :value="sc"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    :rowsPerPageOptions="[5, 10, 20, 50]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filtersStandard"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code', 'type_name', 'description', 'report_year', 'report_month']"
                                    class="text-md"
                                    ref="dtBOM"
                                >
                                    <Column field="no" sortable header="#" :showFilterMenu="true" v-bind="tbStyle('main')"></Column>

                                    <Column field="item_code" header="Item Code" :showFilterMenu="false" sortable v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search item code"
                                                class="w-full"
                                            />
                                        </template>
                                    </Column>

                                    <Column field="type_name" :showFilterMenu="false" sortable header="Type" v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <div class="flex justify-center">
                                                <Select
                                                    v-model="filterModel.value"
                                                    :options="type"
                                                    placeholder="Select priority"
                                                    class="w-40"
                                                    @change="
                                                        () => {
                                                            if (filterModel.value === 'All') {
                                                                filterModel.value = null;
                                                            }
                                                            filterCallback();
                                                        }
                                                    "
                                                >
                                                    <!-- Selected value -->
                                                    <template #value="{ value }">
                                                        <span v-if="!value || value === 'All'" class="w-full text-center text-gray-500">
                                                            Select priority
                                                        </span>
                                                        <span
                                                            v-else
                                                            :class="getTypeClass(value)"
                                                            class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                                        >
                                                            {{ capitalize(value) }}
                                                        </span>
                                                    </template>

                                                    <!-- Dropdown options -->
                                                    <template #option="{ option }">
                                                        <span
                                                            v-if="option === 'All'"
                                                            class="inline-block w-full rounded-full bg-gray-100 px-2 py-1 text-center text-xs font-semibold text-gray-800"
                                                        >
                                                            All
                                                        </span>
                                                        <span
                                                            v-else
                                                            :class="getTypeClass(option)"
                                                            class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                                        >
                                                            {{ capitalize(option) }}
                                                        </span>
                                                    </template>
                                                </Select>
                                            </div>
                                        </template></Column
                                    >

                                    <Column field="bom.description" header="Name" :showFilterMenu="false" sortable v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search description"
                                                class="w-full"
                                            />
                                        </template>
                                        <template #body="{ data }">
                                            {{ data.bom ? data.bom.description : 'N/A' }}
                                        </template>
                                    </Column>

                                    <Column field="disc_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="disc_code" sortable header="Disc" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.disc_code || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="rim_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="rim_code" sortable header="Rim" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.rim_code || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="sidering_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="sidering_code" sortable header="Sidering" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.sidering_code || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_disc" sortable header="Pr Disc" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_disc || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_rim" sortable header="Pr Rim" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_rim || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_sidering" sortable header="Pr Sidering" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_sidering || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_assy" sortable header="Pr Assy" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_assy || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_cedW" sortable header="Pr CED W" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_cedW || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_cedSR" sortable header="Pr CED SR" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_cedSR || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_tcW" sortable header="Pr Topcoat W" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_tcW || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_tcSR" sortable header="Pr tcSR" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_tcSR || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_disc" sortable header="WiP Disc" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_disc || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_rim" sortable header="WiP Rim" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_rim || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_sidering" sortable header="WiP Sidering" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_sidering || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_assy" sortable header="WiP Assy" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_assy || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedW" sortable header="WiP CED W" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_cedW || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedSR" sortable header="WiP CED SR" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_cedSR || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcW" sortable header="WiP Topcoat W" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_tcW || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcSR" sortable header="WiP Topcoat SR" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_tcSR || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_valve" sortable header="WiP Valve" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_valve || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="action" header="Action" :exportable="false" v-bind="tbStyle('fg')">
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
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="1">
                            <section class="p-2">
                                <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <!-- Title -->
                                    <h2 class="text-3xl font-semibold text-gray-900 dark:text-white">Standard Cost</h2>

                                    <!-- Main Controls Container -->
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                                        <!-- Export and Update Report Buttons -->
                                        <div class="flex flex-col gap-2 sm:flex-row sm:gap-4">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export Report"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-orange-700 sm:w-auto"
                                                @click="exportCSV('standardCost')"
                                            />
                                            <Button
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

                                        <!-- Select -->
                                        <div class="flex gap-2">
                                            <div class="flex-1">
                                                <label for="report-period" class="block py-2 text-sm font-medium text-gray-400"
                                                    >Select Period :</label
                                                >
                                                <Select
                                                    v-model="selectStandardPeriod"
                                                    :options="listStandardPeriod"
                                                    optionLabel="name"
                                                    placeholder="Select a period"
                                                    class="w-64"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Last Update Info -->
                                <div class="mb-4 text-right text-gray-700 dark:text-gray-300">
                                    <div>
                                        Last Update :
                                        <span class="text-red-300">{{ lastUpdate[0] ? formatlastUpdate(lastUpdate[0]) : '-' }}</span>
                                    </div>
                                </div>

                                <DataTable
                                    :value="sc"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    :rowsPerPageOptions="[5, 10, 20, 50]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filtersStandard"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code', 'type_name', 'description', 'report_year', 'report_month']"
                                    class="text-md"
                                    ref="dtSC"
                                >
                                    <Column field="no" sortable header="#" :showFilterMenu="true" v-bind="tbStyle('main')"></Column>

                                    <Column field="item_code" header="Item Code" :showFilterMenu="false" sortable v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search item code"
                                                class="w-full"
                                            />
                                        </template>
                                    </Column>

                                    <Column field="type_name" :showFilterMenu="false" sortable header="Type" v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <div class="flex justify-center">
                                                <Select
                                                    v-model="filterModel.value"
                                                    :options="type"
                                                    placeholder="Select priority"
                                                    class="w-40"
                                                    @change="
                                                        () => {
                                                            if (filterModel.value === 'All') {
                                                                filterModel.value = null;
                                                            }
                                                            filterCallback();
                                                        }
                                                    "
                                                >
                                                    <!-- Selected value -->
                                                    <template #value="{ value }">
                                                        <span v-if="!value || value === 'All'" class="w-full text-center text-gray-500">
                                                            Select priority
                                                        </span>
                                                        <span
                                                            v-else
                                                            :class="getTypeClass(value)"
                                                            class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                                        >
                                                            {{ capitalize(value) }}
                                                        </span>
                                                    </template>

                                                    <!-- Dropdown options -->
                                                    <template #option="{ option }">
                                                        <span
                                                            v-if="option === 'All'"
                                                            class="inline-block w-full rounded-full bg-gray-100 px-2 py-1 text-center text-xs font-semibold text-gray-800"
                                                        >
                                                            All
                                                        </span>
                                                        <span
                                                            v-else
                                                            :class="getTypeClass(option)"
                                                            class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                                        >
                                                            {{ capitalize(option) }}
                                                        </span>
                                                    </template>
                                                </Select>
                                            </div>
                                        </template></Column
                                    >

                                    <Column field="bom.description" header="Name" :showFilterMenu="false" sortable v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search description"
                                                class="w-full"
                                            />
                                        </template>
                                        <template #body="{ data }">
                                            {{ data.bom ? data.bom.description : 'N/A' }}
                                        </template>
                                    </Column>

                                    <Column field="disc_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="disc_code" sortable header="Disc" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.disc_code || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="disc_price" sortable header="Price" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.disc_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="rim_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="rim_code" sortable header="Rim" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.rim_code || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="rim_price" sortable header="Price" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.rim_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="sidering_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>

                                    <Column field="sidering_code" sortable header="Sidering" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.sidering_code || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="sidering_price" sortable header="Price" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.sidering_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_disc" sortable header="Pr Disc" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_disc || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_disc_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_disc_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_rim" sortable header="Pr Rim" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_rim || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_rim_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_rim_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_sidering" sortable header="Pr Sidering" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_sidering || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_sidering_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_sidering_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_assy" sortable header="Pr Assy" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_assy || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_assy_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_assy_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_cedW" sortable header="Pr CED W" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_cedW || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_cedW_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_cedW_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_cedSR" sortable header="Pr CED SR" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_cedSR || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_cedSR_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_cedSR_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_tcW" sortable header="Pr Topcoat W" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_tcW || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_tcW_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_tcW_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_tcSR" sortable header="Pr tcSR" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_tcSR || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_tcSR_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_tcSR_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pack_price" sortable header="Packing Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pack_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_disc" sortable header="WiP Disc" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_disc || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_disc_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_disc_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_rim" sortable header="WiP Rim" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_rim || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_rim_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_rim_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_sidering" sortable header="WiP Sidering" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_sidering || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_sidering_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_sidering_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_assy" sortable header="WiP Assy" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_assy || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_assy_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_assy_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedW" sortable header="WiP CED W" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_cedW || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedW_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_cedW_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedSR" sortable header="WiP CED SR" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_cedSR || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedSR_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_cedSR_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcW" sortable header="WiP Topcoat W" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_tcW || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcW_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_tcW_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcSR" sortable header="WiP Topcoat SR" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_tcSR || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcSR_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_tcSR_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_valve" sortable header="WiP Valve" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_valve || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_valve_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_valve_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_raw_material" sortable header="Total Raw Material" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.total_raw_material).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_process" sortable header="Total Process" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.total_process).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total" sortable header="Total" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.total).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="report_month" sortable header="Month" v-bind="tbStyle('fg')">
                                        <template #body="slotProps">
                                            {{ getMonthName(slotProps.data.report_month) }}
                                        </template>
                                    </Column>

                                    <Column field="report_year" sortable header="Year" v-bind="tbStyle('fg')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.report_year || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="action" header="Action" :exportable="false" v-bind="tbStyle('fg')">
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
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="2">
                            <section class="p-2">
                                <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <!-- Title -->
                                    <h2 class="text-3xl font-semibold text-gray-900 dark:text-white">Actual Cost</h2>

                                    <!-- Main Controls Container -->
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                                        <!-- Export and Update Report Buttons -->
                                        <div class="flex flex-col gap-2 sm:flex-row sm:gap-4">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export Report"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-orange-700 sm:w-auto"
                                                @click="exportCSV('actualCost')"
                                            />
                                            <Button
                                                icon="pi pi-sync"
                                                label=" Update Report?"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-cyan-700 sm:w-auto"
                                                @click="showUpdateDialog('actualCost')"
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

                                        <!-- Date Pickers -->
                                        <div class="flex gap-2">
                                            <div class="flex-1">
                                                <label for="report-period" class="block py-2 text-sm font-medium text-gray-400"
                                                    >Select Period :</label
                                                >
                                                <Select
                                                    v-model="selectActualPeriod"
                                                    :options="listActualPeriod"
                                                    optionLabel="name"
                                                    placeholder="Select a period"
                                                    class="w-64"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Last Update Info -->
                                <div class="mb-4 text-right text-gray-700 dark:text-gray-300">
                                    <div>
                                        Last Update :
                                        <span class="text-red-300">{{ lastUpdate[1] ? formatlastUpdate(lastUpdate[1]) : '-' }}</span>
                                    </div>
                                </div>

                                <DataTable
                                    :value="ac"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    :rowsPerPageOptions="[5, 10, 20, 50]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filtersActual"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code', 'type_name', 'description', 'report_year', 'report_month']"
                                    class="text-md"
                                    ref="dtAC"
                                >
                                    <Column field="no" sortable header="#" :showFilterMenu="true" v-bind="tbStyle('main')"></Column>

                                    <Column field="item_code" header="Item Code" :showFilterMenu="false" sortable v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search item code"
                                                class="w-full"
                                            />
                                        </template>
                                    </Column>

                                    <Column field="type_name" :showFilterMenu="false" sortable header="Type" v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <div class="flex justify-center">
                                                <Select
                                                    v-model="filterModel.value"
                                                    :options="type"
                                                    placeholder="Select priority"
                                                    class="w-40"
                                                    @change="
                                                        () => {
                                                            if (filterModel.value === 'All') {
                                                                filterModel.value = null;
                                                            }
                                                            filterCallback();
                                                        }
                                                    "
                                                >
                                                    <!-- Selected value -->
                                                    <template #value="{ value }">
                                                        <span v-if="!value || value === 'All'" class="w-full text-center text-gray-500">
                                                            Select priority
                                                        </span>
                                                        <span
                                                            v-else
                                                            :class="getTypeClass(value)"
                                                            class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                                        >
                                                            {{ capitalize(value) }}
                                                        </span>
                                                    </template>

                                                    <!-- Dropdown options -->
                                                    <template #option="{ option }">
                                                        <span
                                                            v-if="option === 'All'"
                                                            class="inline-block w-full rounded-full bg-gray-100 px-2 py-1 text-center text-xs font-semibold text-gray-800"
                                                        >
                                                            All
                                                        </span>
                                                        <span
                                                            v-else
                                                            :class="getTypeClass(option)"
                                                            class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                                        >
                                                            {{ capitalize(option) }}
                                                        </span>
                                                    </template>
                                                </Select>
                                            </div>
                                        </template></Column
                                    >

                                    <Column field="bom.description" header="Name" :showFilterMenu="false" sortable v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search description"
                                                class="w-full"
                                            />
                                        </template>
                                        <template #body="{ data }">
                                            {{ data.bom ? data.bom.description : 'N/A' }}
                                        </template>
                                    </Column>

                                    <Column field="disc_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="disc_code" sortable header="Disc" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.disc_code || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="disc_price" sortable header="Price" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.disc_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="rim_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="rim_code" sortable header="Rim" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.rim_code || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="rim_price" sortable header="Price" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.rim_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="sidering_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="sidering_code" sortable header="Sidering" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.sidering_code || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="sidering_price" sortable header="Price" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.sidering_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_disc" sortable header="Pr Disc" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_disc || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_disc_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_disc_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_rim" sortable header="Pr Rim" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_rim || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="pr_rim_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_rim_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_sidering" sortable header="Pr Sidering" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_sidering || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="pr_sidering_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_sidering_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_assy" sortable header="Pr Assy" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_assy || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="pr_assy_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_assy_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_cedW" sortable header="Pr CED W" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_cedW || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="pr_cedW_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_cedW_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_cedSR" sortable header="Pr CED SR" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_cedSR || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="pr_cedSR_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_cedSR_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_tcW" sortable header="Pr Topcoat W" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_tcW || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="pr_tcW_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_tcW_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_tcSR" sortable header="Pr tcSR" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_tcSR || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="pr_tcSR_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_tcSR_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pack_price" sortable header="Packing Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pack_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_disc" sortable header="WiP Disc" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_disc || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_disc_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_disc_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_rim" sortable header="WiP Rim" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_rim || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_rim_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_rim_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_sidering" sortable header="WiP Sidering" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_sidering || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_sidering_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_sidering_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_assy" sortable header="WiP Assy" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_assy || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_assy_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_assy_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedW" sortable header="WiP CED W" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_cedW || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_cedW_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_cedW_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedSR" sortable header="WiP CED SR" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_cedSR || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_cedSR_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_cedSR_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcW" sortable header="WiP Topcoat W" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_tcW || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_tcW_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_tcW_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcSR" sortable header="WiP Topcoat SR" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_tcSR || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_tcSR_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_tcSR_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_valve" sortable header="WiP Valve" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_valve || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_valve_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_valve_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_raw_material" sortable header="Total Raw Material" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.total_raw_material).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_process" sortable header="Total Process" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.total_process).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total" sortable header="Total" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.total).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="report_month" sortable header="Month" v-bind="tbStyle('fg')">
                                        <template #body="slotProps">
                                            {{ getMonthName(slotProps.data.report_month) }}
                                        </template>
                                    </Column>

                                    <Column field="report_year" sortable header="Year" v-bind="tbStyle('fg')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.report_year || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="action" header="Action" :exportable="false" v-bind="tbStyle('fg')">
                                        <template #body="data">
                                            <div class="flex gap-2">
                                                <Button
                                                    v-tooltip="'Preview Product'"
                                                    icon="pi pi-eye"
                                                    severity="info"
                                                    rounded
                                                    text
                                                    @click="openPreviewTab(data.data.item_code, opexDef, proginDef, 'actualCost')"
                                                />
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="3">
                            <section class="p-2">
                                <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <!-- Title -->
                                    <h2 class="text-3xl font-semibold text-gray-900 dark:text-white">Difference</h2>

                                    <!-- Main Controls Container -->
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                                        <!-- Export and Update Report Buttons -->
                                        <div class="flex flex-col gap-2 sm:flex-row sm:gap-4">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export Report"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-orange-700 sm:w-auto"
                                                @click="exportCSV('diffCost')"
                                            />
                                            <Button
                                                icon="pi pi-sync"
                                                label=" Calcuate Difference?"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-cyan-700 sm:w-auto"
                                                @click="showUpdateDialog('diffCost')"
                                            />
                                        </div>

                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                                            <!-- Date Pickers -->
                                            <div class="flex gap-2">
                                                <div class="flex-1">
                                                    <label for="report-period" class="block py-2 text-sm font-medium text-gray-400"
                                                        >Standard Period :</label
                                                    >
                                                    <Select
                                                        v-model="selectStandardPeriod"
                                                        :options="listStandardPeriod"
                                                        optionLabel="name"
                                                        placeholder="Select a period"
                                                        class="w-64"
                                                    />
                                                </div>
                                            </div>

                                            <!-- Date Pickers -->
                                            <div class="flex gap-2">
                                                <div class="flex-1">
                                                    <label for="report-period" class="block py-2 text-sm font-medium text-gray-400"
                                                        >Actual Period :</label
                                                    >
                                                    <Select
                                                        v-model="selectActualPeriod"
                                                        :options="listActualPeriod"
                                                        optionLabel="name"
                                                        placeholder="Select a period"
                                                        class="w-64"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <DataTable
                                    :value="combinedData"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    :rowsPerPageOptions="[5, 10, 20, 50]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filtersDifference"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code']"
                                    class="text-md"
                                    ref="dtDIFF"
                                >
                                    <ColumnGroup type="header">
                                        <Row>
                                            <Column field="no" header="#" :rowspan="2" sortable v-bind="tbStyle('main')"></Column>
                                            <Column field="item_code" header="Item Code" :rowspan="2" sortable v-bind="tbStyle('main')"></Column>
                                            <Column
                                                field="standardPeriod"
                                                header="Standard Period"
                                                :rowspan="2"
                                                sortable
                                                v-bind="tbStyle('main')"
                                            ></Column>
                                            <Column
                                                field="actualPeriod"
                                                header="Actual Period"
                                                :rowspan="2"
                                                sortable
                                                v-bind="tbStyle('main')"
                                            ></Column>

                                            <Column header="Standard Cost" :colspan="3" v-bind="tbStyle('rm')"></Column>
                                            <Column header="Actual Cost" :colspan="3" v-bind="tbStyle('pr')"></Column>
                                            <Column header="Difference Cost" :colspan="3" v-bind="tbStyle('fg')"></Column>
                                        </Row>
                                        <Row>
                                            <Column
                                                field="standard_cost.total_raw_material"
                                                sortable
                                                header="Total Raw Material"
                                                v-bind="tbStyle('rm')"
                                            ></Column>
                                            <Column
                                                field="standard_cost.total_process"
                                                sortable
                                                header="Total Process"
                                                v-bind="tbStyle('rm')"
                                            ></Column>
                                            <Column field="standard_cost.total" sortable header="Total" v-bind="tbStyle('rm')"></Column>
                                            <Column
                                                field="actual_cost.total_raw_material"
                                                sortable
                                                header="Total Raw Material"
                                                v-bind="tbStyle('pr')"
                                            ></Column>
                                            <Column field="actual_cost.total_process" sortable header="Total Process" v-bind="tbStyle('pr')"></Column>
                                            <Column field="actual_cost.total" sortable header="Total" v-bind="tbStyle('pr')"></Column>
                                            <Column
                                                field="difference_cost.total_raw_material"
                                                sortable
                                                header="Total Raw Material"
                                                v-bind="tbStyle('fg')"
                                            ></Column>
                                            <Column
                                                field="difference_cost.total_process"
                                                sortable
                                                header="Total Process"
                                                v-bind="tbStyle('fg')"
                                            ></Column>
                                            <Column field="difference_cost.total" sortable header="Total" v-bind="tbStyle('fg')"></Column>
                                        </Row>
                                    </ColumnGroup>

                                    <Column field="no" v-bind="tbStyle('main')"></Column>
                                    <Column field="item_code" v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search item code"
                                                class="w-full"
                                            />
                                        </template>
                                    </Column>
                                    <Column field="standardPeriod" v-bind="tbStyle('main')" />
                                    <Column field="actualPeriod" v-bind="tbStyle('main')" />

                                    <Column field="standard_cost.total_raw_material" sortable v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.standard_cost.total_raw_material).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="standard_cost.total_process" sortable v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.standard_cost.total_process).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="standard_cost.total" sortable v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.standard_cost.total).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="actual_cost.total_raw_material" sortable v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.actual_cost.total_raw_material).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="actual_cost.total_process" sortable v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.actual_cost.total_process).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="actual_cost.total" sortable v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.actual_cost.total).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="difference_cost.total_raw_material" sortable v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            <span
                                                :class="{
                                                    'text-red-500': data.difference_cost.total_raw_material < 0,
                                                    'text-green-500': data.difference_cost.total_raw_material > 0,
                                                }"
                                            >
                                                {{ Number(data.difference_cost.total_raw_material).toLocaleString('id-ID') }}
                                            </span>
                                        </template>
                                    </Column>
                                    <Column field="difference_cost.total_process" sortable v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            <span
                                                :class="{
                                                    'text-red-500': data.difference_cost.total_process < 0,
                                                    'text-green-500': data.difference_cost.total_raw_material > 0,
                                                }"
                                            >
                                                {{ Number(data.difference_cost.total_process).toLocaleString('id-ID') }}
                                            </span>
                                        </template>
                                    </Column>
                                    <Column field="difference_cost.total" sortable v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            <span
                                                :class="{
                                                    'text-red-500': data.difference_cost.total < 0,
                                                    'text-green-500': data.difference_cost.total_raw_material > 0,
                                                }"
                                            >
                                                {{ Number(data.difference_cost.total).toLocaleString('id-ID') }}
                                            </span>
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
