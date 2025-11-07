<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import dayjs from 'dayjs';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ColumnGroup from 'primevue/columngroup';
import DataTable from 'primevue/datatable';
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
const dtDIFF = ref();
const dtDCxSQ = ref();
const loading = ref(false);
const year = ref();
const month = ref();
const tempOpex = ref<number | null>(null);
const tempProgin = ref<number | null>(null);
const monthRange = ref(null);
const selectStandardPeriod = ref<StandardPeriod | null>(null);
const selectActualPeriod = ref<ActualPeriod | null>(null);

const selectDifferencePeriod = ref<DifferencePeriod | null>(null);
const selectSalesPeriod = ref<SalesPeriod | null>(null);
const selectDCxSQPeriod = ref<DCxSQPeriod | null>(null);
const updateReportDialog = ref(false);
const updateConstDialog = ref(false);
type UpdateStatus = 'idle' | 'updating' | 'done';
const updateStatus = ref<UpdateStatus>('idle');
const updateType = ref<'diffCost' | 'dcXsq' | null>(null);
const activeTabValue = ref('0');
const userName = computed(() => page.props.auth?.user?.name ?? '');

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

const dc = computed(() =>
    (page.props.dc as any[]).map((dc, index) => ({
        ...dc,
        no: index + 1,
    })),
);

const combinedData = computed(() => {
    const sc = (page.props.sc || []) as any[];
    const ac = (page.props.ac || []) as any[];
    const dc = (page.props.dc || []) as any[];

    if (!Array.isArray(sc) || !Array.isArray(ac) || !Array.isArray(dc)) {
        return [];
    }

    // Fungsi untuk mengekstrak TAHUN (YYYY) dari string periode (misal: "YTD-Jul'2025")
    const extractYear = (periodString: string) => {
        if (!periodString) return '';
        const parts = String(periodString).split("'");
        if (parts.length > 1) {
            return parts[1].trim();
        }
        return String(periodString).trim();
    };

    const cleanItemCode = (value: string) => {
        return value ? String(value).trim() : '';
    };

    // --- Pembuatan Map Standard Cost (Kunci: TAHUN-ItemCode) ---
    const standardCostMap = new Map();
    sc.forEach((item) => {
        // item.period (SC) HANYA BERUPA TAHUN (mis: '2025')
        const key = `${cleanItemCode(item.period)}-${cleanItemCode(item.item_code)}`;
        standardCostMap.set(key, item);
    });

    // --- Pembuatan Map Actual Cost (Kunci: PERIODE PENUH-ItemCode) ---
    const actualCostMap = new Map();
    ac.forEach((item) => {
        // item.period (AC) BERUPA PERIODE PENUH (mis: 'YTD-Sep'2025')
        const key = `${cleanItemCode(item.period)}-${cleanItemCode(item.item_code)}`;
        actualCostMap.set(key, item);
    });

    const sortedDc = [...dc].sort((a, b) => cleanItemCode(a.item_code).localeCompare(cleanItemCode(b.item_code)));

    const combined = sortedDc.map((dcItem, index) => {
        // PERIODE DC (Penuh, mis: 'YTD-Jul'2025')
        const period = dcItem.period;
        const itemCode = cleanItemCode(dcItem.item_code);

        // 1. KUNCI STANDARD COST: Ambil Tahun dari periode DC
        const yearFromDCPeriod = extractYear(period); // -> '2025'
        const standardKey = `${yearFromDCPeriod}-${itemCode}`;

        // 2. KUNCI ACTUAL COST: Gunakan periode DC yang PENUH
        const actualKey = `${cleanItemCode(period)}-${itemCode}`;

        // Ambil data cost dari Map
        const standardCostItem = standardCostMap.get(standardKey);
        const actualCostItem = actualCostMap.get(actualKey); // Mencari periode penuh

        const defaultCost = { total_raw_material: 0, total_process: 0, total: 0 };
        const descriptionFromBom = dcItem.bom ? dcItem.bom.description : '-';

        return {
            no: index + 1,
            item_code: dcItem.item_code,
            description: descriptionFromBom,
            period: dcItem.period,
            standard_cost: standardCostItem || defaultCost,
            actual_cost: actualCostItem || defaultCost,

            difference_cost: dcItem,
        };
    });

    return combined.sort((a, b) => cleanItemCode(a.item_code).localeCompare(cleanItemCode(b.item_code)));
});

const dcxsq = computed(() =>
    (page.props.dcxsq as any[]).map((dcxsq, index) => ({
        ...dcxsq,
        no: index + 1,
    })),
);

const dcTotalRawMaterial = computed(() => {
    const periodFilter = filtersDifference.value?.period;
    const selectedPeriod = periodFilter ? periodFilter.value : '';

    if (!selectedPeriod || selectedPeriod === '') {
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

    filteredData.forEach((item) => {
        const value = Number(item.total_raw_material || 0);
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
    const periodFilter = filtersDifference.value?.period;
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

    const rawData = dc.value || [];
    let filteredData = rawData;
    let total = 0;

    // 1. Logika Pemfilteran: (Sekarang hanya berjalan jika selectedPeriod sudah terisi)
    // Filter data mentah berdasarkan periode yang dipilih
    filteredData = rawData.filter((item) => item.period === selectedPeriod);

    // 2. Logika Penjumlahan:
    filteredData.forEach((item) => {
        const value = Number(item.total_process || 0);
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
    const periodFilter = filtersDifference.value?.period;
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

    const rawData = dc.value || [];
    let filteredData = rawData;
    let total = 0;

    // 1. Logika Pemfilteran: (Sekarang hanya berjalan jika selectedPeriod sudah terisi)
    // Filter data mentah berdasarkan periode yang dipilih
    filteredData = rawData.filter((item) => item.period === selectedPeriod);

    // 2. Logika Penjumlahan:
    filteredData.forEach((item) => {
        const value = Number(item.total || 0);
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
        const value = Number(item.total_raw_material || 0);
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
        const value = Number(item.total_process || 0);
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
        const value = Number(item.total || 0);
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

const listDifferencePeriod = computed(() => {
    const allPeriods = ((page.props.dc as any[]) ?? []).map((item) => item.period);
    const uniquePeriods = Array.from(new Set(allPeriods)).filter((p) => p !== null && p !== undefined) as string[];

    // Pindahkan monthNames ke sini agar dideklarasikan di scope yang sama dengan parsePeriodToDate
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    const parsePeriodToDate = (periodString: string) => {
        // Hapus 'YTD-' jika ada, dan bersihkan spasi awal/akhir
        const cleanedPeriod = periodString.replace('YTD-', '').trim();

        // Pemecahan string menjadi [Bulan (ex: 'Sep'), Tahun (ex: '2025')]
        const parts = cleanedPeriod.split("'");

        if (parts.length < 2) {
            // Tangani kasus di mana string bukan format MM'YYYY
            return new Date(0);
        }

        // --- Perbaikan Kritis: Trim setiap bagian ---
        const monthYearPart = parts[0].trim(); // Ambil 'Sep' dan pastikan tidak ada spasi
        const yearPart = parts[1].trim(); // Ambil '2025'

        const monthIndex = monthNames.findIndex((m) => m === monthYearPart);
        const year = parseInt(yearPart, 10);

        if (monthIndex === -1 || isNaN(year)) {
            console.warn(`Gagal mengurai periode: ${periodString} | Month Part: ${monthYearPart} | Year: ${year}`);
            return new Date(0);
        }

        return new Date(year, monthIndex, 1);
    };

    uniquePeriods.sort((a, b) => {
        const dateA = parsePeriodToDate(a);
        const dateB = parsePeriodToDate(b);

        // Debugging: Cek apakah Date berhasil diuraikan
        if (dateA.getTime() === 0 || dateB.getTime() === 0) {
            console.error(`Pengurutan gagal untuk: ${a} atau ${b}`);
        }

        // === PERUBAHAN: Pengurutan Ascending (Terlama di atas) ===
        if (dateA > dateB) return 1; // Jika A lebih BARU, letakkan A setelah B (+1)
        if (dateA < dateB) return -1; // Jika A lebih LAMA, letakkan A sebelum B (-1)
        return 0;
    });

    // Petakan ke format Select options
    return uniquePeriods.map((periodName) => {
        return {
            name: periodName,
            code: periodName,
        };
    });
});

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

const listDCxSQ = computed(() => {
    const data = (page.props.dcxsq as any[]) || [];
    if (data.length === 0) {
        return [];
    }
    const uniqueNames = new Set<string>();
    const result: { name: string; code: string }[] = [];
    data.forEach((item) => {
        const name = item.period;
        if (name && !uniqueNames.has(name)) {
            uniqueNames.add(name);
            result.push({
                name: name,
                code: name,
            });
        }
    });
    result.sort((a, b) => {
        return a.name.localeCompare(b.name);
    });
    return result;
});

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
});

const filtersDCxSQ = ref({
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
    period: { value: null as string | null, matchMode: FilterMatchMode.EQUALS },
    sales_month: { value: null as number | null, matchMode: FilterMatchMode.EQUALS },
});

function exportCSV(type: 'diffCost' | 'dcXsq') {
    if (type === 'diffCost' && dtDIFF.value) {
        const exportFilename = `DiffCost-${new Date().toISOString().slice(0, 10)}.csv`;
        dtDIFF.value.exportCSV({ selectionOnly: false, filename: exportFilename });
    } else if (type === 'dcXsq' && dtDCxSQ.value) {
        const exportFilename = `DiffCost x Sales Quantity-${new Date().toISOString().slice(0, 10)}.csv`;
        dtDCxSQ.value.exportCSV({ selectionOnly: false, filename: exportFilename });
    }
}

function formatlastUpdate(date: Date | string) {
    return dayjs(date).format('DD MMM YYYY HH:mm:ss');
}

function showUpdateDialog(type: 'diffCost' | 'dcXsq') {
    updateType.value = type;
    updateStatus.value = 'idle';

    nextTick(() => {
        updateReportDialog.value = true;
    });
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
                        <Tab value="0">Difference</Tab>
                        <Tab value="1">Difference x Quantity</Tab>
                    </TabList>

                    <TabPanels>
                        <TabPanel value="0">
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
                                                v-if="auth?.user?.permissions?.includes('Update_Report')"
                                                icon="pi pi-sync"
                                                label=" Calcuate Difference?"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-cyan-700 sm:w-auto"
                                                @click="showUpdateDialog('diffCost')"
                                            />
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
                                    columnResizeMode="fit"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filtersDifference"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code', 'description', 'period']"
                                    class="text-md"
                                    ref="dtDIFF"
                                >
                                    <ColumnGroup type="header">
                                        <Row>
                                            <Column field="no" header="#" :rowspan="2" sortable v-bind="tbStyle('main')"></Column>
                                            <Column field="item_code" header="Item Code" :rowspan="2" sortable v-bind="tbStyle('main')"></Column>
                                            <Column field="description" header="Description" :rowspan="2" sortable v-bind="tbStyle('main')"></Column>

                                            <Column field="period" header="Period" :rowspan="2" sortable v-bind="tbStyle('main')"></Column>

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
                                    <Column field="description" v-bind="tbStyle('main')" :showFilterMenu="false">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search Description"
                                                class="w-full"
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
                                        <template #footer>
                                            <span :class="dcTotalRawMaterial.class">
                                                <strong>{{ dcTotalRawMaterial.value }}</strong>
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
                                        <template #footer>
                                            <span :class="dcTotalProcess.class">
                                                <strong>{{ dcTotalProcess.value }}</strong>
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
                                        <template #footer>
                                            <span :class="dcTotalofTotal.class">
                                                <strong>{{ dcTotalofTotal.value }}</strong>
                                            </span>
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="1">
                            <section class="p-2">
                                <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <!-- Title -->
                                    <h2 class="text-3xl font-semibold text-gray-900 dark:text-white">Difference x Sales Quantity</h2>

                                    <!-- Main Controls Container -->
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                                        <!-- Export and Update Report Buttons -->
                                        <div class="flex flex-col gap-2 sm:flex-row sm:gap-4">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export Report"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-orange-700 sm:w-auto"
                                                @click="exportCSV('dcXsq')"
                                            />
                                            <Button
                                                v-if="auth?.user?.permissions?.includes('Update_Report')"
                                                icon="pi pi-sync"
                                                label=" Calcuate Difference?"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-cyan-700 sm:w-auto"
                                                @click="showUpdateDialog('dcXsq')"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <DataTable
                                    :value="dcxsq"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    :rowsPerPageOptions="[5, 10, 20, 50]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filtersDCxSQ"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code', 'period']"
                                    class="text-md"
                                    ref="dtDCxSQ"
                                >
                                    <Column field="no" header="No" sortable v-bind="tbStyle('main')"></Column>
                                    <Column field="item_code" header="Item Code" sortable v-bind="tbStyle('main')" :showFilterMenu="false">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search item code"
                                                class="w-full"
                                            />
                                        </template>
                                    </Column>
                                    <Column field="bom.description" header="Name" :showFilterMenu="false" sortable v-bind="tbStyle('main')" frozen>
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

                                    <Column
                                        field="period"
                                        header="Difference Cost x Actual Sales Month"
                                        sortable
                                        v-bind="tbStyle('main')"
                                        :showFilterMenu="false"
                                    >
                                        <template #filter="{ filterModel, filterCallback }">
                                            <div class="flex justify-center">
                                                <Select
                                                    v-model="filterModel.value"
                                                    :options="listDCxSQ"
                                                    optionLabel="name"
                                                    optionValue="code"
                                                    placeholder="Select a period"
                                                    :showClear="true"
                                                    class="w-full"
                                                    @change="filterCallback()"
                                                >
                                                </Select>
                                            </div>
                                        </template>
                                    </Column>
                                    <Column field="quantity" header="Quantity" sortable v-bind="tbStyle('main')" />
                                    <Column field="total_raw_material" header="Total Raw Material" sortable v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            <span
                                                :class="{
                                                    'text-red-500': data.total_raw_material < 0,
                                                    'text-green-500': data.total_raw_material > 0,
                                                }"
                                            >
                                                {{ Number(data.total_raw_material).toLocaleString('id-ID') }}
                                            </span>
                                        </template>

                                        <template #footer>
                                            <span :class="dcxsqTotalRawMaterial.class">
                                                <strong>{{ dcxsqTotalRawMaterial.value }}</strong>
                                            </span>
                                        </template>
                                    </Column>
                                    <Column field="total_process" header="Total Process" sortable v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            <span
                                                :class="{
                                                    'text-red-500': data.total_process < 0,
                                                    'text-green-500': data.total_process > 0,
                                                }"
                                            >
                                                {{ Number(data.total_process).toLocaleString('id-ID') }}
                                            </span>
                                        </template>

                                        <template #footer>
                                            <span :class="dcxsqTotalProcess.class">
                                                <strong>{{ dcxsqTotalProcess.value }}</strong>
                                            </span>
                                        </template>
                                    </Column>
                                    <Column field="total" header="Total" sortable v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            <span
                                                :class="{
                                                    'text-red-500': data.total < 0,
                                                    'text-green-500': data.total > 0,
                                                }"
                                            >
                                                {{ Number(data.total).toLocaleString('id-ID') }}
                                            </span>
                                        </template>

                                        <template #footer>
                                            <span :class="dcxsqTotalofTotal.class">
                                                <strong>{{ dcxsqTotalofTotal.value }}</strong>
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
