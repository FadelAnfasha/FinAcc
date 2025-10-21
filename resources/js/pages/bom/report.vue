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
const dtDCxSQ = ref();
const loading = ref(false);
const year = ref();
const month = ref();
let opexDef = ref(6);
let proginDef = ref(5);
const tempOpex = ref<number | null>(null);
const tempProgin = ref<number | null>(null);
const checked = ref(false);
const monthRange = ref(null);
const selectStandardPeriod = ref<StandardPeriod | null>(null);
const selectActualPeriod = ref<ActualPeriod | null>(null);
const selectDifferencePeriod = ref<DifferencePeriod | null>(null);
const selectSalesPeriod = ref<SalesPeriod | null>(null);
const selectDCxSQPeriod = ref<DCxSQPeriod | null>(null);
const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const type = ['All', 'Disc', 'Sidering', 'Wheel'];
const updateReportDialog = ref(false);
const updateConstDialog = ref(false);
type UpdateStatus = 'idle' | 'updating' | 'done';
const updateStatus = ref<UpdateStatus>('idle');
const userName = computed(() => page.props.auth?.user?.name ?? '');
const updateType = ref<'standardCost' | 'actualCost' | 'diffCost' | 'opgin' | 'dcXsq' | null>(null);
const maxDate = ref(new Date());
const selectionModeType = ref('single');

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

const props = defineProps({
    auth: Object,
});

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

const acxsq = computed(() =>
    (page.props.actual_sales as any[]).map((acxsq, index) => ({
        ...acxsq,
        no: index + 1,
    })),
);

const dcxsq = computed(() =>
    (page.props.dcxsq as any[]).map((dcxsq, index) => ({
        ...dcxsq,
        no: index + 1,
    })),
);

const bom = computed(() =>
    (page.props.bom as any[]).map((bom, index) => {
        const typeChar: string = bom.item_code?.charAt(3) ?? '';
        const typeMap: Record<string, string> = {
            D: 'Disc',
            N: 'Sidering',
            W: 'Wheel',
            R: 'Rim',
        };
        const type_name = typeMap[typeChar] ?? bom.item_code;

        return {
            ...bom,
            no: index + 1,
            type_name,
        };
    }),
);

const combinedData = computed(() => {
    const sc = (page.props.sc || []) as any[];
    const ac = (page.props.ac || []) as any[];
    const dc = (page.props.dc || []) as any[];

    if (!Array.isArray(sc) || !Array.isArray(ac) || !Array.isArray(dc)) {
        return [];
    }

    // Fungsi untuk mengekstrak TAHUN (YYYY) dari string periode (misal: "YTM-Jul'2025")
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
        // item.period (AC) BERUPA PERIODE PENUH (mis: 'YTM-Sep'2025')
        const key = `${cleanItemCode(item.period)}-${cleanItemCode(item.item_code)}`;
        actualCostMap.set(key, item);
    });

    const sortedDc = [...dc].sort((a, b) => cleanItemCode(a.item_code).localeCompare(cleanItemCode(b.item_code)));

    const combined = sortedDc.map((dcItem, index) => {
        // PERIODE DC (Penuh, mis: 'YTM-Jul'2025')
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

const listStandardPeriod = computed(() => {
    const allYears = ((page.props.sc as any[]) ?? []).map((item) => item.period);
    const uniqueYears = Array.from(new Set(allYears)) as number[];
    uniqueYears.sort((a, b) => b - a);
    return uniqueYears.map((year) => {
        const formattedYear = String(year);
        return {
            name: formattedYear,
            code: formattedYear,
        };
    });
});

const listActualPeriod = computed(() => {
    const allPeriods = ((page.props.ac as any[]) ?? []).map((item) => item.period);

    const uniquePeriods = Array.from(new Set(allPeriods)).filter((p) => p !== null && p !== undefined) as string[];

    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    const parsePeriodToDate = (periodString: string) => {
        // Hapus 'YTM-' jika ada, dan bersihkan spasi
        const cleanedPeriod = periodString.replace('YTM-', '').trim();

        // Pisahkan menjadi [Bulan, Tahun] (ex: "Sep'2025" -> ['Sep', '2025'])
        const parts = cleanedPeriod.split("'");

        if (parts.length < 2) {
            // Tangani string yang tidak valid
            return new Date(0);
        }

        const monthYearPart = parts[0].trim();
        const yearPart = parts[1].trim();

        const monthIndex = monthNames.findIndex((m) => m === monthYearPart);
        const year = parseInt(yearPart, 10);

        if (monthIndex === -1 || isNaN(year)) {
            // Jika parsing gagal, kembalikan nilai tanggal default
            return new Date(0);
        }

        // Kembalikan objek Date (tanggal 1 bulan tersebut)
        return new Date(year, monthIndex, 1);
    };

    uniquePeriods.sort((a, b) => {
        const dateA = parsePeriodToDate(a);
        const dateB = parsePeriodToDate(b);

        if (dateA > dateB) return 1;
        if (dateA < dateB) return -1;
        return 0;
    });

    // 4. Petakan ke format Select options
    return uniquePeriods.map((periodName) => {
        return {
            name: periodName,
            code: periodName,
        };
    });
});

const listDifferencePeriod = computed(() => {
    const allPeriods = ((page.props.dc as any[]) ?? []).map((item) => item.period);
    const uniquePeriods = Array.from(new Set(allPeriods)).filter((p) => p !== null && p !== undefined) as string[];

    // Pindahkan monthNames ke sini agar dideklarasikan di scope yang sama dengan parsePeriodToDate
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    const parsePeriodToDate = (periodString: string) => {
        // Hapus 'YTM-' jika ada, dan bersihkan spasi awal/akhir
        const cleanedPeriod = periodString.replace('YTM-', '').trim();

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

const listSalesMonths = computed(() => {
    const data = (page.props.dcxsq as any[]) || [];
    if (data.length === 0) return ['All'];

    const uniqueMonths = new Set<string>();

    // Asumsikan kolom sales_month sudah berisi string bulan (misal: "Jan")
    data.forEach((item) => {
        if (item.sales_month) {
            uniqueMonths.add(item.sales_month);
        }
    });

    // Tambahkan opsi 'All' dan sorting standar (alfabetis)
    const sortedMonths = Array.from(uniqueMonths).sort();
    return ['All', ...sortedMonths];
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

// Watcher buat ngehubungin Select ke filters
watch(selectStandardPeriod, (newValue) => {
    if (newValue) {
        const year = newValue.code;

        filtersStandard.value.period.value = year;
        filtersDifference.value.period.value = year;
    } else {
        // Reset filter jika Select dikosongkan
        filtersStandard.value.period.value = null;
        filtersDifference.value.period.value = null;
    }
});

watch(selectActualPeriod, (newValue) => {
    if (newValue) {
        const period = newValue.code;

        filtersActual.value.period.value = period;
        filtersDifference.value.period.value = period;
    } else {
        // Reset filter jika Select dikosongkan
        filtersActual.value.period.value = null;
        filtersDifference.value.period.value = null;
    }
});

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

watch(selectionModeType, (newMode) => {
    // Reset nilai monthRange agar tidak ada data rentang yang tersisa jika beralih ke 'single'
    monthRange.value = null;
});

const currentSelectionMode = computed(() => {
    return selectionModeType.value === 'range' ? 'range' : 'single';
});

const filtersBOM = ref({
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    type_name: { value: null, matchMode: FilterMatchMode.EQUALS },
    description: { value: null, matchMode: FilterMatchMode.CONTAINS },
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

function exportCSV(type: 'BOM' | 'standardCost' | 'actualCost' | 'diffCost' | 'dcXsq') {
    if (type === 'BOM' && dtBOM.value) {
        const exportFilename = `Bill-of-Material-${new Date().toISOString().slice(0, 10)}.csv`;
        dtBOM.value.exportCSV({ selectionOnly: false, filename: exportFilename });
    } else if (type === 'standardCost' && dtSC.value) {
        const exportFilename = `StandardCost-${new Date().toISOString().slice(0, 10)}.csv`;
        dtSC.value.exportCSV({ selectionOnly: false, filename: exportFilename });
    } else if (type === 'actualCost' && dtAC.value) {
        const exportFilename = `ActualCost-${new Date().toISOString().slice(0, 10)}.csv`;
        dtAC.value.exportCSV({ selectionOnly: false, filename: exportFilename });
    } else if (type === 'diffCost' && dtDIFF.value) {
        const exportFilename = `DiffCost-${new Date().toISOString().slice(0, 10)}.csv`;
        dtDIFF.value.exportCSV({ selectionOnly: false, filename: exportFilename });
    } else if (type === 'dcXsq' && dtDCxSQ.value) {
        const exportFilename = `DiffCost x Sales Quantity-${new Date().toISOString().slice(0, 10)}.csv`;
        dtDCxSQ.value.exportCSV({ selectionOnly: false, filename: exportFilename });
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

function showUpdateDialog(type: 'standardCost' | 'actualCost' | 'opgin' | 'diffCost' | 'dcXsq') {
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
    sac: '',
    diffCost: '',
    dcXsq: '',
});

function confirmUpdate() {
    if (!updateType.value) return;
    let payload = {};
    if (updateType.value === 'standardCost') {
        if (!year.value) {
            validationErrors.value.sac = 'Year cannot be empty.';
            return;
        }

        payload = {
            year: year.value.getFullYear(),
        };
    } else if (updateType.value === 'actualCost') {
        // Validasi: Pastikan nilai tidak kosong
        if (!monthRange.value) {
            // Hapus month.value yang tidak lagi digunakan
            validationErrors.value.sac = 'Period selection cannot be empty.';
            return;
        }

        if (selectionModeType.value === 'range') {
            // Mode Rentang
            const startMonthDate = monthRange.value[0];
            const endMonthDate = monthRange.value[1];

            if (!startMonthDate || !endMonthDate) {
                validationErrors.value.sac = 'Invalid month range selected.';
                return;
            }
            payload = {
                startMonth: startMonthDate.getMonth() + 1,
                endMonth: endMonthDate.getMonth() + 1,
                year: endMonthDate.getFullYear(),

                isRange: true,
            };
        } else {
            const singleMonthDate = monthRange.value;

            payload = {
                startMonth: singleMonthDate.getMonth() + 1,
                endMonth: singleMonthDate.getMonth() + 1,
                year: singleMonthDate.getFullYear(),
                isRange: false,
            };
        }
    } else if (updateType.value === 'diffCost') {
        // Logika validasi dan payload untuk 'diffCost'
        if (!selectStandardPeriod.value || !selectActualPeriod.value) {
            validationErrors.value.diffCost = 'Standard Period and Actual Period cannot be empty.';
            return;
        }

        const standardPeriod = selectStandardPeriod.value.code;
        const actualPeriod = selectActualPeriod.value.code;
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
        payload = {
            period: differencePeriod,
            sales_period: salesPeriod,
        };
    } else if (updateType.value === 'opgin') {
    }

    if (Object.keys(payload).length === 0 && updateType.value === 'opgin') {
    }

    updateStatus.value = 'updating';
    const type = updateType.value;

    const routes = {
        standardCost: 'bom.updateSC',
        actualCost: 'bom.updateAC',
        diffCost: 'bom.updateDC',
        opgin: 'bom.updateOpGin',
        dcXsq: 'bom.updateDCxSQ',
    };

    const messages = {
        standardCost: 'Standard Cost',
        actualCost: 'Actual Cost',
        diffCost: 'Difference Cost',
        opgin: 'OPEX / Profit Margin',
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
    year.value = null;
    month.value = null;
    monthRange.value = null;
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

    selectStandardPeriod.value = null;
    selectActualPeriod.value = null;
    monthRange.value = null;
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
</script>

<template>
    <Head title="Standard Cost" />
    <AppLayout>
        <div class="p-6">
            <div class="flex flex-col gap-1">
                <h2 class="mb-2 text-start text-3xl font-bold text-gray-900 dark:text-white">Standard Cost</h2>
                <p class="text-start text-gray-600 dark:text-gray-400">Generate Standard Cost & Calculate Difference Cost</p>
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
                        <div v-if="updateType !== 'diffCost' && updateType !== 'dcXsq'">
                            <div v-if="updateType === 'actualCost'" class="mt-6 mb-2 font-semibold">Select Actual Price Period:</div>
                            <div class="flex space-x-4">
                                <div v-if="updateType === 'actualCost'" class="flex-1">
                                    <label for="report-month" class="block text-sm font-medium text-gray-400">Select month selection mode : </label>

                                    <div class="mb-3 flex space-x-4">
                                        <label class="inline-flex items-center">
                                            <input type="radio" v-model="selectionModeType" value="single" class="form-radio text-indigo-600" />
                                            <span class="ml-2 text-sm text-gray-700">Single Month</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" v-model="selectionModeType" value="range" class="form-radio text-indigo-600" />
                                            <span class="ml-2 text-sm text-gray-700">Ranged Months</span>
                                        </label>
                                    </div>

                                    <DatePicker
                                        v-model="monthRange"
                                        view="month"
                                        dateFormat="mm/yy"
                                        :selectionMode="currentSelectionMode"
                                        :maxDate="maxDate"
                                        :placeholder="selectionModeType === 'range' ? 'Start Month - End Month' : 'Single Month'"
                                    />
                                </div>
                                <div v-if="updateType === 'standardCost'" class="flex-1">
                                    <DatePicker
                                        v-model="year"
                                        view="year"
                                        dateFormat="yy"
                                        :selectionMode="currentSelectionMode"
                                        :maxDate="maxDate"
                                        placeholder="Select year"
                                    />
                                </div>
                            </div>
                            <p v-if="validationErrors.sac" class="mt-2 inline-block rounded bg-red-500 px-2 py-1 text-sm font-medium text-white">
                                {{ validationErrors.sac }}
                            </p>
                        </div>

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
                                    <label for="dcPeriod" class="block text-sm font-medium text-gray-400">Standard Cost Period</label>
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

                        <div v-if="updateType !== 'diffCost' && updateType !== 'dcXsq'">
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
                        <Tab value="4">Difference x Quantity</Tab>
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
                                    :value="bom"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    :rowsPerPageOptions="[5, 10, 20, 50]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filtersBOM"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code', 'type_name', 'description']"
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

                                    <Column field="description" header="Name" :showFilterMenu="false" sortable v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search description"
                                                class="w-full"
                                            />
                                        </template>
                                        <template #body="{ data }">
                                            {{ data.description ?? 'N/A' }}
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
                                    :globalFilterFields="['item_code', 'type_name', 'description', 'period']"
                                    class="text-md"
                                    ref="dtSC"
                                >
                                    <Column field="no" sortable header="#" :showFilterMenu="true" v-bind="tbStyle('main')"></Column>
                                    <Column field="period" header="Period" sortable v-bind="tbStyle('main')" :showFilterMenu="false">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <div class="flex flex-col">
                                                <Select
                                                    v-model="selectStandardPeriod"
                                                    :options="listStandardPeriod"
                                                    optionLabel="name"
                                                    placeholder="Select a period"
                                                    class="w-full"
                                                />
                                            </div>
                                        </template>
                                    </Column>
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
                                    <Column field="period" sortable header="Period" v-bind="tbStyle('main')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.period || '-' }}
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
                                                v-if="auth?.user?.permissions?.includes('Update_Report')"
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
                                    <Column field="period" header="Period" sortable :showFilterMenu="false" v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <div class="flex flex-col">
                                                <Select
                                                    v-model="selectActualPeriod"
                                                    :options="listActualPeriod"
                                                    optionLabel="name"
                                                    placeholder="Select a period"
                                                    class="w-full"
                                                />
                                            </div>
                                        </template>
                                    </Column>
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

                                    <Column field="timeRange" sortable header="Time Range" v-bind="tbStyle('fg')" />

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

                        <TabPanel value="4">
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
                                    :globalFilterFields="['item_code', 'difference_period']"
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
