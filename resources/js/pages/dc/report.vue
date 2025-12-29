<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ColumnGroup from 'primevue/columngroup';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';

import axios from 'axios';
import Row from 'primevue/row';
import Select from 'primevue/select';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import { useToast } from 'primevue/usetoast';
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';

const toast = useToast();
const page = usePage();
const dtDIFF = ref();
const loading = ref(false);
const year = ref();
const month = ref();
const tempOpex = ref<number | null>(null);
const tempProgin = ref<number | null>(null);
const monthRange = ref(null);
const dataDetail = ref<any>(null);
const headerName = ref('');
const selectStandardPeriod = ref<StandardPeriod | null>(null);
const selectActualPeriod = ref<ActualPeriod | null>(null);

const selectDifferencePeriod = ref<DifferencePeriod | null>(null);
const selectSalesPeriod = ref<SalesPeriod | null>(null);
const selectDCxSQPeriod = ref<DCxSQPeriod | null>(null);
const updateReportDialog = ref(false);
const detailDialog = ref(false);
type UpdateStatus = 'idle' | 'updating' | 'done';
const updateStatus = ref<UpdateStatus>('idle');
const updateType = ref<'diffCost' | 'dcXsq' | null>(null);
const detailType = ref<'diffCost' | 'dcXsq' | null>(null);
const activeTabValue = ref('0');
const userName = computed(() => page.props.auth?.user?.name ?? '');

const differenceCostUrl = ref('/finacc/api/difference/get-difference-cost');
const perPage = ref(10);
const currentDiffCostPage = ref(1);
const sortOrderDiffCost = ref(null);
const sortFieldDiffCost = ref(null);
const paginatedDiffCostData = ref<any>(null);
const searchTimeout = ref<number | null>(null);
const listDiffPeriod = ref<List[]>([]);
const listDiffRemark = ref<List[]>([]);
const listDiffTotal = ref<List[]>([]);

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

const props = defineProps({
    auth: Object,
});

// Data Diff Cost
const dc = computed(() =>
    (page.props.dc as any[]).map((dc, index) => ({
        ...dc,
        no: index + 1,
    })),
);

// Data Diff Cost x Sales Quantity
const dcxsq = computed(() =>
    (page.props.dcxsq as any[]).map((dcxsq, index) => ({
        ...dcxsq,
        no: index + 1,
    })),
);

async function exportCSV(type: string) {
    if (type === 'diffCost') {
        const currentPeriod = filtersDiffCost.value.period.value;

        try {
            // 1. Ambil data lengkap dari endpoint JSON Anda
            const response = await axios.get(route('report.difference.export'), {
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
            link.setAttribute('download', `DifferenceCost_${currentPeriod || 'All'}.csv`);
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

const dcTotalRawMaterial = computed(() => {
    const periodFilterDiff = filtersDifference.value?.period;
    const periodFilterDCxSQ = filtersDCxSQ.value?.period;

    const cleanPeriodValue = (periodValue: any) => {
        if (!periodValue) return null;
        return String(periodValue).split(' / ')[0].trim();
    };

    const selectedPeriodDiff = periodFilterDiff ? cleanPeriodValue(periodFilterDiff.value) : null;
    const selectedPeriodDCxSQ = periodFilterDCxSQ ? cleanPeriodValue(periodFilterDCxSQ.value) : null;

    const selectedPeriod = selectedPeriodDCxSQ || selectedPeriodDiff;

    if (!selectedPeriod) {
        return {
            value: 'Select Period First',
            class: { 'text-gray-500': true },
            isPlaceholder: true,
        };
    }
    // =======================================================

    const rawData = dc.value || [];
    let filteredData = rawData;
    let total = 0;

    filteredData = rawData.filter((item) => item.period === selectedPeriod);
    // 2. Logika Penjumlahan:

    filteredData.forEach((item) => {
        const value = Number(item.difference_cost.total_raw_material || 0);
        total += value;
    });

    const formattedTotal = Number(total).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });

    const textColorClass = {
        'text-red-500': total < 0,
        'text-green-500': total > 0,
        'text-orange-900': total === 0,
    };

    return {
        value: formattedTotal,
        class: textColorClass,
        isPlaceholder: false,
    };
});

const dcTotalProcess = computed(() => {
    const periodFilterDiff = filtersDifference.value?.period;
    const periodFilterDCxSQ = filtersDCxSQ.value?.period;

    const cleanPeriodValue = (periodValue: any) => {
        if (!periodValue) return null;
        return String(periodValue).split(' / ')[0].trim();
    };

    const selectedPeriodDiff = periodFilterDiff ? cleanPeriodValue(periodFilterDiff.value) : null;
    const selectedPeriodDCxSQ = periodFilterDCxSQ ? cleanPeriodValue(periodFilterDCxSQ.value) : null;

    const selectedPeriod = selectedPeriodDCxSQ || selectedPeriodDiff;

    if (!selectedPeriod) {
        return {
            value: 'Select Period First',
            class: { 'text-gray-500': true },
            isPlaceholder: true,
        };
    }
    // =======================================================

    const rawData = dc.value || [];
    let filteredData = rawData;
    let total = 0;

    // 1. Logika Pemfilteran: (Sekarang hanya berjalan jika selectedPeriod sudah terisi)
    // Filter data mentah berdasarkan periode yang dipilih
    filteredData = rawData.filter((item) => item.period === selectedPeriod);

    // 2. Logika Penjumlahan:
    filteredData.forEach((item) => {
        const value = Number(item.difference_cost.total_process || 0);
        total += value;
    });

    // 3. Format dan Klasifikasi Warna
    const formattedTotal = Number(total).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });

    const textColorClass = {
        'text-red-500': total < 0,
        'text-green-500': total > 0,
        'text-orange-500': total === 0,
    };

    return {
        value: formattedTotal,
        class: textColorClass,
        isPlaceholder: false,
    };
});

const dcTotalofTotal = computed(() => {
    const periodFilterDiff = filtersDifference.value?.period;
    const periodFilterDCxSQ = filtersDCxSQ.value?.period;

    const cleanPeriodValue = (periodValue: any) => {
        if (!periodValue) return null;
        return String(periodValue).split(' / ')[0].trim();
    };

    const selectedPeriodDiff = periodFilterDiff ? cleanPeriodValue(periodFilterDiff.value) : null;
    const selectedPeriodDCxSQ = periodFilterDCxSQ ? cleanPeriodValue(periodFilterDCxSQ.value) : null;

    const selectedPeriod = selectedPeriodDCxSQ || selectedPeriodDiff;

    if (!selectedPeriod) {
        return {
            value: 'Select Period First',
            class: { 'text-gray-500': true },
            isPlaceholder: true,
        };
    }
    // =======================================================

    const rawData = dc.value || [];
    let filteredData = rawData;
    let total = 0;

    // 1. Logika Pemfilteran: (Sekarang hanya berjalan jika selectedPeriod sudah terisi)
    // Filter data mentah berdasarkan periode yang dipilih
    filteredData = rawData.filter((item) => item.period === selectedPeriod);

    // 2. Logika Penjumlahan:
    filteredData.forEach((item) => {
        const value = Number(item.difference_cost.total || 0);
        total += value;
    });

    // 3. Format dan Klasifikasi Warna
    const formattedTotal = Number(total).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });

    const textColorClass = {
        'text-red-500': total < 0,
        'text-green-500': total > 0,
        'text-orange-500': total === 0,
    };

    return {
        value: formattedTotal,
        class: textColorClass,
        isPlaceholder: false,
    };
});

const dcxsqTotalRawMaterial = computed(() => {
    const periodFilter = filtersDCxSQ.value?.period;
    const selectedPeriod = periodFilter ? periodFilter.value : '';

    if (!selectedPeriod || selectedPeriod === '') {
        return {
            value: 'Select Period First',
            class: { 'text-gray-500': true },
            isPlaceholder: true,
        };
    }
    // =======================================================

    const rawData = dcxsq.value || [];
    let filteredData = rawData;
    let total = 0;

    filteredData = rawData.filter((item) => item.period === selectedPeriod);
    filteredData.forEach((item) => {
        const value = Number(item.dcxsq.total_raw_material || 0);
        total += value;
    });

    const formattedTotal = Number(total).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });

    const textColorClass = {
        'text-red-500': total < 0,
        'text-green-500': total > 0,
        'text-orange-900': total === 0,
    };

    return {
        value: formattedTotal,
        class: textColorClass,
        isPlaceholder: false,
    };
});

const dcxsqTotalProcess = computed(() => {
    const periodFilter = filtersDCxSQ.value?.period;
    const selectedPeriod = periodFilter ? periodFilter.value : '';

    // =======================================================
    // ⭐️ PEMERIKSAAN UTAMA: JIKA FILTER KOSONG, KEMBALIKAN PESAN
    // =======================================================
    if (!selectedPeriod || selectedPeriod === '') {
        return {
            value: 'Select Period First', // Pesan khusus
            class: { 'text-gray-500': true }, // Warna abu-abu untuk pesan
            isPlaceholder: true, // Flag untuk digunakan di template
        };
    }
    // =======================================================

    const rawData = dcxsq.value || [];
    let filteredData = rawData;
    let total = 0;

    // 1. Logika Pemfilteran: (Sekarang hanya berjalan jika selectedPeriod sudah terisi)
    // Filter data mentah berdasarkan periode yang dipilih
    filteredData = rawData.filter((item) => item.period === selectedPeriod);

    // 2. Logika Penjumlahan:
    filteredData.forEach((item) => {
        const value = Number(item.dcxsq.total_process || 0);
        total += value;
    });

    // 3. Format dan Klasifikasi Warna
    const formattedTotal = Number(total).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });

    const textColorClass = {
        'text-red-500': total < 0,
        'text-green-500': total > 0,
        'text-orange-500': total === 0,
    };

    return {
        value: formattedTotal,
        class: textColorClass,
        isPlaceholder: false,
    };
});

const dcxsqTotalofTotal = computed(() => {
    const periodFilter = filtersDCxSQ.value?.period;
    const selectedPeriod = periodFilter ? periodFilter.value : '';

    if (!selectedPeriod || selectedPeriod === '') {
        return {
            value: 'Select Period First', // Pesan khusus
            class: { 'text-gray-500': true }, // Warna abu-abu untuk pesan
            isPlaceholder: true, // Flag untuk digunakan di template
        };
    }

    const rawData = dcxsq.value || [];
    let filteredData = rawData;
    let total = 0;

    filteredData = rawData.filter((item) => item.period === selectedPeriod);

    filteredData.forEach((item) => {
        const value = Number(item.dcxsq.total || 0);
        total += value;
    });

    // 3. Format dan Klasifikasi Warna
    const formattedTotal = Number(total).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });

    const textColorClass = {
        'text-red-500': total < 0,
        'text-green-500': total > 0,
        'text-orange-500': total === 0,
    };

    return {
        value: formattedTotal,
        class: textColorClass,
        isPlaceholder: false,
    };
});

const listStandardPeriod = computed(() =>
    (page.props.scPeriod as string[]).map((period, index) => ({
        code: period,
        name: period,
        no: index + 1,
    })),
);

const listActualPeriod = computed(() =>
    (page.props.acPeriod as string[]).map((period, index) => ({
        code: period,
        name: period,
        no: index + 1,
    })),
);

const listDifferencePeriod = computed(() =>
    (page.props.dcPeriod as string[]).map((period, index) => ({
        code: period,
        name: period,
        no: index + 1,
    })),
);

const listDifferenceRemark = computed(() =>
    (page.props.dcRemark as string[]).map((remark, index) => ({
        code: remark,
        name: remark,
        no: index + 1,
    })),
);

const listSalesMonth = computed(() => {
    const data = (page.props.actual_sales as any[]) || [];
    if (data.length === 0) {
        return []; // Tidak ada data sama sekali
    }

    // Daftar semua kolom kuantitas bulanan
    const monthFields = [
        'jan_qty',
        'feb_qty',
        'mar_qty',
        'apr_qty',
        'may_qty',
        'jun_qty',
        'jul_qty',
        'aug_qty',
        'sep_qty',
        'oct_qty',
        'nov_qty',
        'dec_qty',
    ];

    // Objek yang akan melacak apakah bulan memiliki kuantitas > 0
    const monthHasData: { [key: string]: boolean } = {
        Jan: false,
        Feb: false,
        Mar: false,
        Apr: false,
        May: false,
        Jun: false,
        Jul: false,
        Aug: false,
        Sep: false,
        Oct: false,
        Nov: false,
        Dec: false,
    };

    // Peta dari field (jan_qty) ke nama bulan (Jan)
    const fieldToMonthName: { [key: string]: string } = {
        jan_qty: 'Jan',
        feb_qty: 'Feb',
        mar_qty: 'Mar',
        apr_qty: 'Apr',
        may_qty: 'May',
        jun_qty: 'Jun',
        jul_qty: 'Jul',
        aug_qty: 'Aug',
        sep_qty: 'Sep',
        oct_qty: 'Oct',
        nov_qty: 'Nov',
        dec_qty: 'Dec',
    };

    // Iterasi melalui setiap baris data
    data.forEach((item) => {
        // Iterasi melalui setiap kolom kuantitas
        monthFields.forEach((field) => {
            const qty = Number(item[field] || 0); // Ambil nilai kuantitas, pastikan itu angka (atau 0)

            if (qty > 0) {
                const monthName = fieldToMonthName[field];
                monthHasData[monthName] = true; // Set bulan ini memiliki data > 0
            }
        });
    });

    // 2. Buat Array Output Bulan yang Tersedia
    const result = [];
    let foundNonZero = false;

    // Kita harus memproses dari Desember ke Januari untuk menemukan batas terakhir
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    // Cari bulan terakhir yang memiliki data (dari belakang)
    let lastAvailableIndex = -1;
    for (let i = monthNames.length - 1; i >= 0; i--) {
        const month = monthNames[i];
        if (monthHasData[month]) {
            lastAvailableIndex = i;
            break;
        }
    }

    // Jika tidak ada data > 0 sama sekali, kembalikan array kosong
    if (lastAvailableIndex === -1) {
        return [];
    }

    // Buat daftar bulan dari Januari hingga bulan terakhir yang tersedia
    for (let i = 0; i <= lastAvailableIndex; i++) {
        const month = monthNames[i];
        const code = monthFields[i];

        result.push({
            name: month,
            // Anda bisa menggunakan kode numerik atau string lain yang unik untuk backend
            code: code,
        });
    }

    return result;
});

const listDCxSQ = computed(() =>
    (page.props.dcxsqPeriod as string[]).map((period, index) => ({
        code: period,
        name: period,
        no: index + 1,
    })),
);

const listQuantity = computed(() => {
    // 1. Ambil nilai unik (seperti sebelumnya)
    const quantitiesWithDuplicates = dcxsq.value.map((item) => item.dcxsq.quantity);
    const uniqueQuantities = [...new Set(quantitiesWithDuplicates)];

    // 2. Map ke array objek { label: '0', value: 0 }
    return uniqueQuantities
        .sort((a, b) => a - b)
        .map((qty) => ({
            label: String(qty), // Label yang dilihat pengguna (sebagai string)
            value: qty, // Nilai aktual yang digunakan untuk filter (sebagai number)
        }));
});

const componentDetails = computed(() => [
    {
        name: 'DISC',
        std_code: dataDetail.value.sc_disc_code,
        std_price: dataDetail.value.sc_disc_price,
        act_code: dataDetail.value.ac_disc_code,
        act_price: dataDetail.value.ac_disc_price,
        diff: dataDetail.value.diff_disc,
    },
    {
        name: 'RIM',
        std_code: dataDetail.value.sc_rim_code,
        std_price: dataDetail.value.sc_rim_price,
        act_code: dataDetail.value.ac_rim_code,
        act_price: dataDetail.value.ac_rim_price,
        diff: dataDetail.value.diff_rim,
    },
    {
        name: 'SIDERING',
        std_code: dataDetail.value.sc_sidering_code,
        std_price: dataDetail.value.sc_sidering_price,
        act_code: dataDetail.value.ac_sidering_code,
        act_price: dataDetail.value.ac_sidering_price,
        diff: dataDetail.value.diff_sidering,
    },
]);

interface StandardPeriod {
    name: string;
    code: string;
}

interface ActualPeriod {
    name: string;
    code: string;
}

interface DifferencePeriod {
    name: string;
    code: string;
}

interface SalesPeriod {
    name: string;
    code: string;
}

interface DCxSQPeriod {
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

watch(selectActualPeriod, (newValue) => {
    if (newValue) {
        const period = newValue.code;

        filtersActual.value.period.value = period;
    } else {
        // Reset filter jika Select dikosongkan
        filtersActual.value.period.value = null;
    }
});

// Watcher buat ngehubungin Select ke filters
watch(selectDifferencePeriod, (newValue) => {
    if (newValue) {
        filtersDifference.value.period.value = newValue.code;
    } else {
        filtersDifference.value.period.value = null;
    }
});

watch(selectDCxSQPeriod, (newValue) => {
    if (newValue) {
        // Nilai code di sini diharapkan adalah string yang mewakili seluruh periode DCxSQ
        filtersDCxSQ.value.period.value = newValue.code;
    } else {
        filtersDCxSQ.value.period.value = null;
    }
});

const filtersStandard = ref({
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    type_name: { value: null, matchMode: FilterMatchMode.EQUALS },
    'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
    period: { value: null as string | null, matchMode: FilterMatchMode.EQUALS },
});

const filtersActual = ref({
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    type_name: { value: null, matchMode: FilterMatchMode.EQUALS },
    'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
    period: { value: null as string | null, matchMode: FilterMatchMode.EQUALS },
});

const filtersDifference = ref({
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    description: { value: null, matchMode: FilterMatchMode.CONTAINS },
    period: { value: null as string | null, matchMode: FilterMatchMode.EQUALS },
    'difference_cost.remark': { value: null as string | null, matchMode: FilterMatchMode.EQUALS },
});

const filtersDCxSQ = ref({
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    description: { value: null, matchMode: FilterMatchMode.CONTAINS },
    period: { value: null as string | null, matchMode: FilterMatchMode.EQUALS },
    'dcxsq.quantity': {
        value: [] as number[] | null,
        matchMode: FilterMatchMode.IN,
    },
    'difference_cost.remark': { value: null as string | null, matchMode: FilterMatchMode.EQUALS },
});

function showUpdateDialog(type: 'diffCost' | 'dcXsq') {
    updateType.value = type;
    updateStatus.value = 'idle';

    nextTick(() => {
        updateReportDialog.value = true;
    });
}

function showDetailDialog(data: any, type: 'dcXsq' | 'diffCost') {
    detailDialog.value = true;
    detailType.value = type;
    dataDetail.value = data;
    console.log(data);
    headerName.value = 'Detail of : ' + data.item_code;
}

const validationErrors = ref({
    sac: '',
    diffCost: '',
    dcXsq: '',
});

function confirmUpdate() {
    if (!updateType.value) return;
    let payload = {};
    let DCupdatePeriod: string | null = null;
    let DCxSQupdatePeriod: string | null = null;

    if (updateType.value === 'diffCost') {
        // Logika validasi dan payload untuk 'diffCost'
        if (!selectStandardPeriod.value || !selectActualPeriod.value) {
            validationErrors.value.diffCost = 'Standard Period and Actual Period cannot be empty.';
            return;
        }

        const standardPeriod = selectStandardPeriod.value.code;
        const actualPeriod = selectActualPeriod.value.code;
        DCupdatePeriod = selectActualPeriod.value.code.toString();

        payload = {
            standard_period: standardPeriod,
            actual_period: actualPeriod,
        };
    } else if (updateType.value === 'dcXsq') {
        // Logika validasi dan payload untuk 'diffCost'
        if (!selectDifferencePeriod.value || !selectSalesPeriod.value) {
            validationErrors.value.dcXsq = 'Different Cost Period and Sales Month Period cannot be empty.';
            return;
        }
        const differencePeriod = selectDifferencePeriod.value.code;
        const salesPeriod = selectSalesPeriod.value.code;

        DCxSQupdatePeriod = `${differencePeriod} / ${selectSalesPeriod.value.name}`;
        payload = {
            period: differencePeriod,
            sales_period: salesPeriod,
        };
    }

    updateStatus.value = 'updating';
    const type = updateType.value;

    const routes = {
        diffCost: 'dc.updateDC',
        dcXsq: 'dc.updateDCxSQ',
    };

    const messages = {
        diffCost: 'Difference Cost',
        dcXsq: 'Difference x Quantity',
    };

    router.post(
        route(routes[type]),
        payload, // payload kini selalu terdefinisi
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                updateStatus.value = 'done';
                if (type === 'diffCost' && DCupdatePeriod) {
                    // Buat objek ActualPeriod baru untuk memicu 'watch'
                    selectDifferencePeriod.value = {
                        code: DCupdatePeriod,
                        name: DCupdatePeriod,
                    };
                    filtersDiffCost.value.period.value = DCupdatePeriod;
                    loadLazyData(differenceCostUrl.value, 'diffCost');
                } else if (type === 'dcXsq' && DCxSQupdatePeriod) {
                    // Buat objek ActualPeriod baru untuk memicu 'watch'
                    selectDCxSQPeriod.value = {
                        code: DCxSQupdatePeriod,
                        name: DCxSQupdatePeriod,
                    };
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
    detailDialog.value = false;
    updateStatus.value = 'idle';
    updateType.value = null;
    validationErrors.value = {
        sac: '',
        diffCost: '',
        dcXsq: '',
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

const fetchDiffPeriod = async () => {
    try {
        const differencePeriod = await fetchDatas('/finacc/api/difference/list-period');

        if (Array.isArray(differencePeriod)) {
            listDiffPeriod.value = differencePeriod.map((p: string) => ({ name: p, code: p }));
        } else {
            console.error('API /api/difference/list-period did not return an array:', differencePeriod);
            listDiffPeriod.value = [];
        }
    } catch (error) {
        console.error('Failed to fetch material group list:', error);
        listDiffPeriod.value = [];
    }
};

const fetchLatestPeriod = async () => {
    try {
        const latest = await fetchDatas('/finacc/api/difference/latest-period');
        if (latest) {
            filtersDiffCost.value.period.value = latest;
        }
    } catch (error) {
        console.error('Failed to fetch latest period:', error);
    }
};

const fetchDiffRemark = async () => {
    try {
        const differenceRemark = await fetchDatas('/finacc/api/difference/list-remark');

        if (Array.isArray(differenceRemark)) {
            listDiffRemark.value = differenceRemark.map((p: string) => ({ name: p, code: p }));
        } else {
            console.error('API /api/difference/list-remark did not return an array:', differenceRemark);
            listDiffRemark.value = [];
        }
    } catch (error) {
        console.error('Failed to fetch material group list:', error);
        listDiffRemark.value = [];
    }
};

const fetchDiffTotal = async () => {
    try {
        const differenceTotal = await fetchDatas('/finacc/api/difference/get-total');

        if (Array.isArray(differenceTotal)) {
            listDiffTotal.value = differenceTotal.map((p: string) => ({ name: p, code: p }));
        } else {
            console.error('API /api/difference/get-total did not return an array:', differenceTotal);
            listDiffTotal.value = [];
        }
    } catch (error) {
        console.error('Failed to fetch material group list:', error);
        listDiffTotal.value = [];
    }
};

const filtersDiffCost = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    period: { value: null, matchMode: FilterMatchMode.EQUALS },
    description: { value: null, matchMode: FilterMatchMode.CONTAINS },
    remark: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const initFilters = () => {
    // Reset filters Stamat (karena ini yang digunakan oleh Tab 0)
    filtersDiffCost.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
        period: { value: null, matchMode: FilterMatchMode.EQUALS },
        description: { value: null, matchMode: FilterMatchMode.CONTAINS },
        remark: { value: null, matchMode: FilterMatchMode.EQUALS },
    };
};

const controllers = {
    diffCost: null as AbortController | null,
};

const loadingStates = reactive({
    diffCost: false,
});

const loadLazyData = async (url: string, type: 'diffCost') => {
    if (controllers[type]) controllers[type].abort();
    controllers[type] = new AbortController();

    loadingStates[type] = true;

    const configMap = {
        diffCost: {
            filters: filtersDiffCost.value,
            page: currentDiffCostPage.value,
            descKey: 'description',
            sortField: sortFieldDiffCost.value,
            sortOrder: sortOrderDiffCost.value,
            dataRef: paginatedDiffCostData,
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
        remark_filter: (currentFilters as any).remark?.value || '',
    });

    if (config.sortField && config.sortOrder) {
        params.append('sort_field', config.sortField);
        params.append('sort_order', config.sortOrder === 1 ? 'asc' : 'desc');
    }

    if (type === 'diffCost') {
        const itemPeriod = (currentFilters as any).period?.value;
        if (itemPeriod) params.append('period_filter', itemPeriod);
    }

    try {
        const response = await fetch(`${url}?${params.toString()}`, {
            signal: controllers[type].signal,
        });
        console.log(`${url}?${params.toString()}`);

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

const onLazyLoadDiffCost = (event: any) => {
    const { first, rows, sortField, sortOrder } = event;

    const newPage = Math.floor(first / rows) + 1;
    perPage.value = rows;

    if (sortFieldDiffCost.value !== sortField) {
        currentDiffCostPage.value = 1;
    } else {
        currentDiffCostPage.value = newPage;
    }

    sortFieldDiffCost.value = sortField || null;
    sortOrderDiffCost.value = sortOrder || null;

    loadLazyData(differenceCostUrl.value, 'diffCost');
};

watch(
    filtersDiffCost,
    () => {
        if (!isReady.standardCost) {
            isReady.standardCost = true;
            return;
        }

        if (standardCostTimeout.value) clearTimeout(standardCostTimeout.value);
        standardCostTimeout.value = setTimeout(() => {
            loadLazyData(differenceCostUrl.value, 'diffCost');
        }, 500);
    },
    { deep: true },
);

onMounted(async () => {
    initFilters();
    try {
        await fetchDiffPeriod();
        await fetchDiffRemark();
        await fetchLatestPeriod();
        await loadLazyData(differenceCostUrl.value, 'diffCost');
    } finally {
        isInitialLoading.value = false;
    }
});

const clearFilter = (type: 'diffCost') => {
    if (type === 'diffCost') {
        filtersDiffCost.value = {
            global: { value: null, matchMode: FilterMatchMode.CONTAINS },
            item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
            period: { value: null, matchMode: FilterMatchMode.EQUALS },
            description: { value: null, matchMode: FilterMatchMode.CONTAINS },
            remark: { value: null, matchMode: FilterMatchMode.EQUALS },
        };
        currentDiffCostPage.value = 1;
    }
};
</script>

<template>
    <Head title="Difference Cost Calculation" />
    <AppLayout>
        <div class="p-6">
            <div class="flex flex-col gap-1">
                <h2 class="mb-2 text-start text-3xl font-bold text-gray-900 dark:text-white">Difference Cost</h2>
                <p class="text-start text-gray-600 dark:text-gray-400">Generate Difference Cost calculation with actual sales quantity.</p>
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

                        <div v-if="updateType === 'diffCost'">
                            <div class="mt-6 mb-2 font-semibold">Select Report Period:</div>
                            <div class="flex flex-col space-y-4 md:flex-row md:space-y-0 md:space-x-4">
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
                            <p v-if="validationErrors.diffCost" class="mt-2 inline-block rounded bg-red-500 px-2 py-1 text-sm font-medium text-white">
                                {{ validationErrors.diffCost }}
                            </p>
                        </div>

                        <div v-if="updateType === 'dcXsq'">
                            <div class="mt-6 mb-2 font-semibold">Select Report Period:</div>
                            <div class="flex flex-col space-y-4 md:flex-row md:space-y-0 md:space-x-4">
                                <div class="flex-1">
                                    <label for="dcPeriod" class="block text-sm font-medium text-gray-400">Difference Cost Period</label>
                                    <Select
                                        v-model="selectDifferencePeriod"
                                        :options="listDifferencePeriod"
                                        optionLabel="name"
                                        placeholder="Select a period"
                                        class="w-64"
                                    />
                                </div>
                                <div class="flex-1">
                                    <label for="monthPeriod" class="block text-sm font-medium text-gray-400">Month Period</label>
                                    <Select
                                        v-model="selectSalesPeriod"
                                        :options="listSalesMonth"
                                        optionLabel="name"
                                        placeholder="Select sales period"
                                        class="w-64"
                                    />
                                </div>
                            </div>

                            <p v-if="validationErrors.dcXsq" class="mt-2 inline-block rounded bg-red-500 px-2 py-1 text-sm font-medium text-white">
                                {{ validationErrors.dcXsq }}
                            </p>
                        </div>

                        <div>
                            <p v-if="updateType === 'diffCost'" class="mt-6 mb-2 inline-block rounded-full bg-red-400 p-2 font-bold text-white">
                                Make sure Standard & Actual Cost data are updated!
                            </p>
                            <p v-if="updateType === 'dcXsq'" class="mt-6 mb-2 inline-block rounded-full bg-red-400 p-2 font-bold text-white">
                                Make sure Difference Cost data are updated!
                            </p>
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

            <Dialog v-model:visible="detailDialog" :header="headerName" modal :style="{ width: '100rem' }" :closable="false" @hide="closeDialog">
                <div class="space-y-4 rounded-xl bg-white p-2 shadow-lg">
                    <div class="mt-1 text-lg text-gray-500 italic">
                        <p class="text-lg text-gray-600">
                            Description : <span class="font-medium text-gray-700">{{ dataDetail.description }}</span>
                        </p>

                        <p class="text-lg text-gray-600">
                            Remark : <span class="font-medium text-gray-700">{{ dataDetail.remark }}</span>
                        </p>

                        <p class="text-lg text-gray-600">
                            Period : <span class="font-medium text-gray-700">{{ dataDetail.period }}</span>
                        </p>
                    </div>

                    <div class="grid grid-cols-4 gap-x-4 overflow-hidden rounded-lg border border-gray-200">
                        <div class="col-span-1 bg-gray-300 p-3 font-extrabold text-slate-700">Material</div>
                        <div class="col-span-1 border-l border-gray-200 bg-gray-300 p-3 text-center font-extrabold text-slate-700">
                            Standard (SC) <span class="text-sm text-orange-600">({{ dataDetail.sc_period }})</span>
                        </div>
                        <div class="col-span-1 border-l border-gray-200 bg-gray-300 p-3 text-center font-extrabold text-slate-700">
                            Actual (AC) <span class="text-sm text-orange-600">({{ dataDetail.ac_period }})</span>
                        </div>
                        <div class="col-span-1 border-l border-gray-200 bg-gray-300 p-3 text-center font-extrabold text-slate-700">
                            Difference <span class="text-sm text-orange-600">({{ dataDetail.period }})</span>
                        </div>

                        <template v-for="(item, index) in componentDetails" :key="index">
                            <div class="border-t border-gray-200 p-3 font-medium text-gray-800">{{ item.name }}</div>

                            <div class="border-t border-l border-gray-200 p-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-gray-500">
                                        {{ item.std_code ? item.std_code : '-' }}
                                    </span>
                                    <span class="text-base font-bold text-gray-900">
                                        {{ item.std_price ? Number(item.std_price).toLocaleString('id-ID') : '-' }}
                                    </span>
                                </div>
                            </div>

                            <div class="border-t border-l border-gray-200 p-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-gray-500">
                                        {{ item.act_code ? item.act_code : '-' }}
                                    </span>
                                    <span class="text-base font-bold text-gray-900">
                                        {{ item.act_price ? Number(item.act_price).toLocaleString('id-ID') : '-' }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="border-t border-l border-gray-200 p-3 text-right font-bold"
                                :class="item.diff < 0 ? 'bg-red-50 text-red-600' : item.diff > 0 ? 'bg-green-50 text-green-600' : 'text-gray-600'"
                            >
                                {{ item.diff ? Number(item.diff).toLocaleString('id-ID') : '-' }}
                            </div>
                        </template>

                        <div class="col-span-1 border-t border-gray-300 bg-yellow-200 p-3 font-extrabold text-slate-900">TOTAL RAW MATERIAL</div>
                        <div class="border-t border-l border-gray-300 bg-yellow-200 p-3 text-right font-extrabold">
                            {{ dataDetail.sc_total_raw_material ? Number(dataDetail.sc_total_raw_material).toLocaleString('id-ID') : '-' }}
                        </div>
                        <div class="border-t border-l border-gray-300 bg-yellow-200 p-3 text-right font-extrabold">
                            {{ dataDetail.ac_total_raw_material ? Number(dataDetail.ac_total_raw_material).toLocaleString('id-ID') : '-' }}
                        </div>
                        <div
                            class="border-t border-l border-gray-300 bg-yellow-200 p-3 text-right font-extrabold"
                            :class="dataDetail.total_raw_material < 0 ? 'text-red-700' : 'text-green-700'"
                        >
                            {{ dataDetail.total_raw_material ? Number(dataDetail.total_raw_material).toLocaleString('id-ID') : '-' }}
                        </div>

                        <div class="col-span-1 border-t border-gray-300 bg-green-200 p-3 font-extrabold text-slate-900">TOTAL PROCESS</div>
                        <div class="border-t border-l border-gray-300 bg-green-200 p-3 text-right font-extrabold">
                            {{ dataDetail.sc_total_process ? Number(dataDetail.sc_total_process).toLocaleString('id-ID') : '-' }}
                        </div>

                        <div class="border-t border-l border-gray-300 bg-green-200 p-3 text-right font-extrabold">
                            {{ dataDetail.ac_total_process ? Number(dataDetail.ac_total_process).toLocaleString('id-ID') : '-' }}
                        </div>

                        <div
                            class="border-t border-l border-gray-300 bg-green-200 p-3 text-right font-extrabold"
                            :class="dataDetail.total_process < 0 ? 'text-red-700' : 'text-green-700'"
                        >
                            {{ dataDetail.total_process ? Number(dataDetail.total_process).toLocaleString('id-ID') : '-' }}
                        </div>

                        <div class="col-span-1 border-t border-gray-300 bg-blue-200 p-3 font-extrabold text-slate-900">TOTAL</div>

                        <div class="border-t border-l border-gray-300 bg-blue-200 p-3 text-right font-extrabold">
                            {{ dataDetail.sc_total ? Number(dataDetail.sc_total).toLocaleString('id-ID') : '-' }}
                        </div>

                        <div class="border-t border-l border-gray-300 bg-blue-200 p-3 text-right font-extrabold">
                            {{ dataDetail.ac_total ? Number(dataDetail.ac_total).toLocaleString('id-ID') : '-' }}
                        </div>

                        <div
                            class="border-t border-l border-gray-300 bg-blue-200 p-3 text-right font-extrabold"
                            :class="dataDetail.total < 0 ? 'text-red-700' : 'text-green-700'"
                        >
                            {{ dataDetail.total ? Number(dataDetail.total).toLocaleString('id-ID') : '-' }}
                        </div>

                        <div class="col-span-3 border-t border-gray-300 bg-lime-200 p-3 font-extrabold text-slate-900">QUANTITY</div>
                        <div class="border-t border-l border-gray-300 bg-lime-200 p-3 text-right font-extrabold">
                            {{ dataDetail.quantity ? Number(dataDetail.quantity).toLocaleString('id-ID') : '-' }}
                        </div>

                        <div class="col-span-3 border-t border-gray-300 bg-orange-200 p-3 font-extrabold text-slate-900">TOTAL * QUANTITY</div>

                        <div
                            class="border-t border-l border-gray-300 bg-orange-200 p-3 text-right font-extrabold"
                            :class="dataDetail.qty_x_total_raw_material < 0 ? 'text-red-700' : 'text-green-700'"
                        >
                            {{ dataDetail.qty_x_total_raw_material ? Number(dataDetail.qty_x_total_raw_material).toLocaleString('id-ID') : '-' }}
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <Button label="Close" icon="pi pi-times-circle" class="p-button-danger p-button-rounded" @click="closeDialog" />
                    </div>
                </div>
            </Dialog>

            <div class="mx-26 mb-26">
                <Tabs v-model="activeTabValue">
                    <TabList>
                        <Tab value="0">Difference</Tab>
                        <!-- <Tab value="1">Difference x Quantity</Tab> -->
                    </TabList>

                    <TabPanels>
                        <TabPanel value="0">
                            <section class="p-2">
                                <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <!-- Title -->
                                    <h2 class="text-3xl font-semibold text-gray-900 dark:text-white">Difference</h2>
                                </div>

                                <DataTable
                                    :value="paginatedDiffCostData?.data || []"
                                    :lazy="true"
                                    :totalRecords="paginatedDiffCostData?.total || 0"
                                    :rows="perPage"
                                    @page="onLazyLoadDiffCost"
                                    @sort="onLazyLoadDiffCost"
                                    :first="(currentDiffCostPage - 1) * perPage"
                                    :paginator="true"
                                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                                    currentPageReportTemplate="Showing {first} to {last} from {totalRecords} data"
                                    responsiveLayout="scroll"
                                    :globalFilterFields="['item_code', 'description', 'description', 'remark', 'period']"
                                    showGridlines
                                    :removableSort="true"
                                    v-model:filters="filtersDiffCost"
                                    filterDisplay="row"
                                    :loading="loadingStates.diffCost || isInitialLoading"
                                    ref="dtDIFF"
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
                                                        @click="exportCSV('diffCost')"
                                                    />
                                                    <Button
                                                        v-if="auth?.user?.permissions?.includes('Update_Report')"
                                                        icon="pi pi-sync"
                                                        label=" Calcuate Difference?"
                                                        unstyled
                                                        class="w-full cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-cyan-700 sm:w-auto"
                                                        @click="showUpdateDialog('diffCost')"
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
                                                        @click="clearFilter('diffCost')"
                                                    />

                                                    <IconField>
                                                        <InputIcon>
                                                            <i class="pi pi-search" />
                                                        </InputIcon>
                                                        <InputText v-model="filtersDiffCost['global'].value" placeholder="Keyword Search" />
                                                    </IconField>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <template #empty>
                                        <div v-if="!loading && !isInitialLoading" class="flex justify-center p-4">No data found.</div>
                                        <div v-else class="flex justify-center p-4">Loading data, please wait...</div>
                                    </template>

                                    <ColumnGroup type="header">
                                        <Row>
                                            <Column
                                                field="item_code"
                                                header="Item Code"
                                                :rowspan="2"
                                                sortable
                                                class="text-center"
                                                headerClass="justify-content-center"
                                                v-bind="tbStyle('main')"
                                            ></Column>
                                            <Column field="description" header="Description" :rowspan="2" sortable v-bind="tbStyle('main')"></Column>
                                            <Column field="period" header="Period" :rowspan="2" sortable v-bind="tbStyle('main')"></Column>
                                            <Column field="quantity" header="Qty" :rowspan="2" sortable v-bind="tbStyle('main')"></Column>
                                            <Column header="Standard Cost" :colspan="3" v-bind="tbStyle('rm')"></Column>
                                            <Column header="Actual Cost" :colspan="3" v-bind="tbStyle('pr')"></Column>
                                            <Column header="Difference Cost" :colspan="3" v-bind="tbStyle('fg')"></Column>
                                            <Column header="x Quantity" :colspan="3" v-bind="tbStyle('wip')"> </Column>
                                            <Column
                                                field="difference_cost.remark"
                                                header="Remark"
                                                sortable
                                                :rowspan="2"
                                                v-bind="tbStyle('main')"
                                            ></Column>
                                            <Column header="Action" :rowspan="2" v-bind="tbStyle('main')"></Column>
                                        </Row>

                                        <Row>
                                            <Column
                                                field="sc_total_raw_material"
                                                sortable
                                                header="Total Raw Material"
                                                v-bind="tbStyle('rm')"
                                            ></Column>
                                            <Column field="sc_total_process" sortable header="Total Process" v-bind="tbStyle('rm')"></Column>
                                            <Column field="sc_total" sortable header="Total" v-bind="tbStyle('rm')"></Column>
                                            <Column
                                                field="ac_total_raw_material"
                                                sortable
                                                header="Total Raw Material"
                                                v-bind="tbStyle('pr')"
                                            ></Column>
                                            <Column field="ac_total_process" sortable header="Total Process" v-bind="tbStyle('pr')"></Column>
                                            <Column field="ac_total" sortable header="Total" v-bind="tbStyle('pr')"></Column>
                                            <Column
                                                field="dc_total_raw_material"
                                                sortable
                                                header="Total Raw Material"
                                                v-bind="tbStyle('fg')"
                                            ></Column>
                                            <Column field="dc_total_process" sortable header="Total Process" v-bind="tbStyle('fg')"></Column>
                                            <Column field="dc_total" sortable header="Total" v-bind="tbStyle('fg')"></Column>
                                            <Column
                                                field="dcxsq_total_raw_material"
                                                sortable
                                                header="Total Raw Material"
                                                v-bind="tbStyle('wip')"
                                            ></Column>
                                            <Column field="dcxsq_total_process" sortable header="Total Process" v-bind="tbStyle('wip')"></Column>
                                            <Column field="dcxsq_total" sortable header="Total" v-bind="tbStyle('wip')"></Column>
                                        </Row>
                                    </ColumnGroup>

                                    <Column field="item_code" v-bind="tbStyle('main')" :showFilterMenu="false">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search item code"
                                                class="w-full"
                                            />
                                        </template>
                                    </Column>

                                    <Column field="description" :showFilterMenu="false" sortable v-bind="tbStyle('main')">
                                        <template #body="{ data }">
                                            <div class="flex w-full">
                                                {{ data.description ? data.description : 'N/A' }}
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

                                    <Column field="period" header="Period" sortable v-bind="tbStyle('main')" :showFilterMenu="false">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <div class="flex justify-center">
                                                <Select
                                                    v-model="filterModel.value"
                                                    :options="listDifferencePeriod"
                                                    optionLabel="name"
                                                    optionValue="name"
                                                    placeholder="Difference Period"
                                                    class="w-full"
                                                    :showClear="true"
                                                    @change="filterCallback()"
                                                />
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="quantity" header="Qty" sortable v-bind="tbStyle('main')" :showFilterMenu="false"> </Column>

                                    <Column field="sc_total_raw_material" :showFilterMenu="false" sortable v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            <div class="flex w-full md:w-20">
                                                {{ Number(data.sc_total_raw_material ? data.sc_total_raw_material : '0').toLocaleString('id-ID') }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="sc_total_process" :showFilterMenu="false" sortable v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            <div class="flex w-full">
                                                {{ Number(data.sc_total_process ? data.sc_total_process : '0').toLocaleString('id-ID') }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="sc_total" :showFilterMenu="false" sortable v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            <div class="flex w-full">
                                                {{ Number(data.sc ? data.sc_total : '0').toLocaleString('id-ID') }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="ac_total_raw_material" :showFilterMenu="false" sortable v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            <div class="flex w-full">
                                                {{ Number(data.ac_total_raw_material ? data.ac_total_raw_material : '0').toLocaleString('id-ID') }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="ac_total_process" :showFilterMenu="false" sortable v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            <div class="flex w-full">
                                                {{ Number(data.ac_total_process ? data.ac_total_process : '0').toLocaleString('id-ID') }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="ac_total" :showFilterMenu="false" sortable v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            <div class="flex w-full">
                                                {{ Number(data.ac_total ? data.ac_total : '0').toLocaleString('id-ID') }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="total_raw_material" :showFilterMenu="false" sortable v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            <div class="flex w-full">
                                                {{ Number(data.total_raw_material ? data.total_raw_material : '0').toLocaleString('id-ID') }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="total_process" :showFilterMenu="false" sortable v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            <div class="flex w-full">
                                                {{ Number(data.total_process ? data.total_process : '0').toLocaleString('id-ID') }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="total" :showFilterMenu="false" sortable v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            <div class="flex w-full">
                                                {{ Number(data.total ? data.total : '0').toLocaleString('id-ID') }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="qty_x_total_raw_material" :showFilterMenu="false" sortable v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            <div class="flex w-full">
                                                {{
                                                    Number(data.qty_x_total_raw_material ? data.qty_x_total_raw_material : '0').toLocaleString(
                                                        'id-ID',
                                                    )
                                                }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="qty_x_total_process" :showFilterMenu="false" sortable v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            <div class="flex w-full">
                                                {{ Number(data.qty_x_total_process ? data.qty_x_total_process : '0').toLocaleString('id-ID') }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="qty_x_total" :showFilterMenu="false" sortable v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            <div class="flex w-full">
                                                {{ Number(data.qty_x_total ? data.qty_x_total : '0').toLocaleString('id-ID') }}
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="remark" header="Remark" sortable v-bind="tbStyle('main')" :showFilterMenu="false"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <div class="flex justify-center">
                                                <MultiSelect
                                                    v-model="filterModel.value"
                                                    display="chip"
                                                    :options="listDifferenceRemark"
                                                    optionLabel="name"
                                                    optionValue="name"
                                                    filter
                                                    placeholder="Remark Type"
                                                    class="w-full md:w-40"
                                                    :showClear="true"
                                                    @change="filterCallback()"
                                                />
                                            </div> </template
                                    ></Column>

                                    <Column header="Action" v-bind="tbStyle('main')">
                                        <template #body="{ data }">
                                            <Button type="button" icon="pi pi-search" rounded @click="showDetailDialog(data, 'diffCost')"></Button>
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
