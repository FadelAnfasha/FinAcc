<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import axios from 'axios';
import dayjs from 'dayjs';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ColumnGroup from 'primevue/columngroup';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import FileUpload, { FileUploadUploaderEvent } from 'primevue/fileupload';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import ProgressBar from 'primevue/progressbar';
import Row from 'primevue/row';
import Select from 'primevue/select';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { computed, nextTick, onMounted, reactive, ref, Ref, watch } from 'vue';

const materialUrl = ref('/finacc/api/actual/get-material');
const bomUrl = ref('/finacc/api/actual/get-bom');
const salesUrl = ref('/finacc/api/actual/get-salesqty');

const dtAcmat = ref();
const dtBom = ref();
const dtAcsales = ref();
const paginatedAcmatData = ref<any>(null);
const paginatedBomData = ref<any>(null);
const paginatedAcsalesData = ref<any>(null);
const searchTimeout = ref<number | null>(null);
const listMaterialGroup = ref<List[]>([]);
const currentAcmatPage = ref(1);
const currentBomPage = ref(1);
const currentAcsalesPage = ref(1);
const listMaterialPeriod = ref<List[]>([]);

const perPage = ref(10);
const activeTab = ref<string | null>;

const sortFieldAcmat = ref(null);
const sortOrderAcmat = ref(null);
const sortFieldBom = ref(null);
const sortOrderBom = ref(null);
const sortFieldAcsales = ref(null);
const sortOrderAcsales = ref(null);

const toast = useToast();
const page = usePage();
const year = ref();

const showDialog = ref(false);
const dialogWidth = ref('40rem');
const editType = ref<'acmat' | 'consumable' | null>(null);
const destroyType = ref<'acmat' | 'consumable' | null>(null);
const headerType = ref<any>({});
const editedData = ref<any>({});
const destroyedData = ref<any>({});
const showImportDialog: Ref<boolean> = ref(false);
const importName = ref<any>({});
const selectedFile = ref<File | null>(null);
const importType = ref<'acmat' | 'bom' | 'acsales' | null>(null);
const notImported = ref(true);
const fileUploaderBom = ref<any>(null);
const fileUploaderAcmat = ref<any>(null);
const fileUploaderAcsales = ref<any>(null);

const uploadProgress = ref(0);
const isUploading = ref(false);
const loading = ref(false);
const userName = computed(() => page.props.auth?.user?.name ?? '');
const dataSource = [
    'Share Others/Finacc/Bill of Material/Actual Data/Actual Material Price/actualMat_master.csv',
    'Share Others/Finacc/Bill of Material/Actual Data/Actual Sales Quantity/actualSalesQty_master.csv',
];

const componentDialog = ref(false);
const componentData = ref([]);
const selectedFinishGood = ref(null);

const isReady = reactive({
    acmat: false,
    bom: false,
    acsales: false,
});

const acmatTimeout = ref<ReturnType<typeof setTimeout> | null>(null);
const bomTimeout = ref<ReturnType<typeof setTimeout> | null>(null);
const acsalesTimeout = ref<ReturnType<typeof setTimeout> | null>(null);

const isInitialLoading = ref(true);

const maxDate = ref(new Date());
const selectionModeType = ref('single');
const currentSelectionMode = computed(() => {
    return selectionModeType.value === 'range' ? 'range' : 'single';
});

const lastMaster = computed(() => {
    return page.props.lastUpdate as (string | null)[];
});

function formatlastUpdate(date: Date | string) {
    return dayjs(date).format('DD MMM YYYY HH:mm:ss');
}

interface List {
    name: string;
    code: string;
}

interface ImportResult {
    addedItems: string[];
    invalidItems: { item_code: string; price: string; description: string; reason: string }[];
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

const viewComponents = async (data: any) => {
    // data adalah object dari baris yang diklik (slotProps.data)
    const itemId = data.id;
    const componentUrl = '/finacc/api/actual/get-component';

    if (!itemId) {
        console.error('ID item tidak ditemukan.');
        return;
    }

    selectedFinishGood.value = data; // Simpan data FG yang dipilih
    loading.value = true; // Gunakan loading indicator

    try {
        // Tambahkan ID sebagai query parameter
        const url = `${componentUrl}?id=${itemId}`;
        // console.log('Fetching components from:', url);

        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const components = await response.json();

        componentData.value = components; // Simpan data komponen
        componentDialog.value = true; // Tampilkan dialog
    } catch (error) {
        console.error('Failed to fetch components:', error);
        // Tampilkan pesan error ke user jika perlu
    } finally {
        loading.value = false;
    }
};

function closeComponentDialog() {
    componentDialog.value = false;
    componentData.value = [];
    selectedFinishGood.value = null; // ✅ kosongkan dengan objek kosong
}

function handleCSVImport(event: FileUploadUploaderEvent, type: 'acmat' | 'bom' | 'acsales') {
    let file: File | undefined;

    if (Array.isArray(event.files)) {
        file = event.files[0];
    } else if (event.files instanceof File) {
        file = event.files;
    }

    if (!file) return;

    const expectedNames = {
        acmat: 'actualMat_master.csv',
        bom: 'bom_master.csv',
        acsales: 'actualSalesQty_master.csv',
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
            if (type === 'acmat') fileUploaderAcmat.value?.clear();
            if (type === 'bom') fileUploaderBom.value?.clear();
            if (type === 'acsales') fileUploaderAcsales.value?.clear();
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

function cancelCSVimport(type: 'acmat' | 'bom' | 'acsales') {
    showImportDialog.value = false;
    selectedFile.value = null;

    nextTick(() => {
        if (type === 'acmat') fileUploaderAcmat.value?.clear();
        if (type === 'bom') fileUploaderBom.value?.clear();
        if (type === 'acsales') fileUploaderAcsales.value?.clear();
    });
}

function confirmUpload(type: 'acmat' | 'bom' | 'acsales') {
    if (!selectedFile.value || !importType.value) return;
    const importYear = year.value ? new Date(year.value).getFullYear() : null;

    if (importType.value === 'acmat' && !importYear) {
        toast.add({
            severity: 'error',
            summary: 'Validation Error',
            detail: 'Please select the year for import.',
            life: 3000,
            group: 'br',
        });
        return;
    }

    const formData = new FormData();
    formData.append('file', selectedFile.value);
    if (importYear) {
        formData.append('period', importYear.toString());
    }
    const routes = {
        acmat: 'master.actual.import.material',
        acsales: 'master.actual.import.sales-quantity',
        bom: 'master.actual.import.bom',
    };

    isUploading.value = true;
    uploadProgress.value = 0;
    startPollingProgress(type);

    router.post(route(routes[importType.value]), formData, {
        preserveScroll: true,
        preserveState: true,

        onSuccess: () => {
            isUploading.value = false;
            selectedFile.value = null;
            toast.add({
                severity: 'success',
                summary: 'Import Success',
                detail: `${importName.value} imported successfully`,
                life: 3000,
                group: 'br',
            });
            if (type === 'acmat') {
                loadLazyData(materialUrl.value, 'acmat');
                filtersAcmat.value.period.value = importYear.toString();
                fetchMaterialPeriod();
            } else if (type === 'bom') {
                loadLazyData(bomUrl.value, 'bom');
            } else if (type === 'acsales') {
                loadLazyData(salesUrl.value, 'acsales');
            }
            nextTick(() => {
                if (type === 'acmat') fileUploaderAcmat.value?.clear();
                if (type === 'bom') fileUploaderBom.value?.clear();
                if (type === 'acsales') fileUploaderAcsales.value?.clear();
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
                if (type === 'acmat') fileUploaderAcmat.value?.clear();
                if (type === 'bom') fileUploaderBom.value?.clear();
                if (type === 'acsales') fileUploaderAcsales.value?.clear();
            });
        },
    });
}

function resetImportState() {
    uploadProgress.value = 0;
    selectedFile.value = null;
    notImported.value = true;
}

function startPollingProgress(type: 'acmat' | 'bom' | 'acsales') {
    uploadProgress.value = 0;

    const endpointMap = {
        acmat: '/finacc/actual/import-material-progress',
        acsales: '/finacc/actual/import-sales-quantity-progress',
        bom: '/finacc/actual/import-bom-progress',
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

function exportCSV(type: 'acmat' | 'bom' | 'acsales') {
    let $type = null;
    let $filename = null;
    if (type === 'acmat') {
        $type = dtAcmat.value;
        $filename = 'actual-mat';
    } else if (type === 'bom') {
        $type = dtBom.value;
        $filename = 'bom';
    } else if (type === 'acsales') {
        $type = dtAcsales.value;
        $filename = 'acsales';
    }
    if (!$type) return;

    const exportFilename = `${$filename}-${new Date().toISOString().slice(0, 10)}.csv`;

    $type.exportCSV({
        selectionOnly: false,
        filename: exportFilename,
    });
}

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

const importResult = computed(() => {
    const result = page.props.importResult as ImportResult;
    if (!result || typeof result !== 'object') {
        return {
            addedItems: [],
            invalidItems: [],
        };
    }

    const addedItems = (result.addedItems || []).map((item, index) => ({
        no: index + 1,
        item_code: item,
    }));

    const invalidItems = (result.invalidItems || []).map((item, index) => ({
        no: index + 1,
        item_code: item.item_code,
        price: item.price,
        description: item.description,
        reason: item.reason,
    }));

    return {
        addedItems: addedItems,
        invalidItems: invalidItems,
    };
});

const props = defineProps({
    auth: Object,
});

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

const fetchMaterialPeriod = async () => {
    try {
        const materialPeriod = await fetchDatas('/finacc/api/actual/list-material-period');
        if (Array.isArray(materialPeriod)) {
            listMaterialPeriod.value = materialPeriod.map((p: string) => ({ name: p, code: p }));
        } else {
            console.error('API /list-period did not return an array:', materialPeriod);
            listMaterialPeriod.value = [];
        }
    } catch (error) {
        console.error('Failed to fetch material group list:', error);
        listMaterialPeriod.value = [];
    }
};

const filtersAcmat = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    item_code: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    'bom.description': { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    period: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const filtersAcsales = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    item_code: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    'bom.description': { value: null, matchMode: FilterMatchMode.STARTS_WITH },
});

const filtersBom = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    item_code: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    description: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
});

const initFilters = () => {
    filtersAcmat.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        item_code: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
        'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
        period: { value: null, matchMode: FilterMatchMode.EQUALS },
    };

    filtersAcsales.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        item_code: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
        'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
    };

    filtersBom.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        item_code: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
        description: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    };
};

const controllers = {
    acmat: null as AbortController | null,
    bom: null as AbortController | null,
    acsales: null as AbortController | null,
};

const loadingStates = reactive({
    acmat: false,
    bom: false,
    acsales: false,
});

const loadLazyData = async (url: string, type: 'acmat' | 'bom' | 'acsales') => {
    if (controllers[type]) controllers[type].abort();
    controllers[type] = new AbortController();
    loadingStates[type] = true;

    const configMap = {
        acmat: {
            filters: filtersAcmat.value,
            page: currentAcmatPage.value,
            descKey: 'bom.description',
            sortField: sortFieldAcmat.value,
            sortOrder: sortOrderAcmat.value,
            dataRef: paginatedAcmatData,
        },
        bom: {
            filters: filtersBom.value,
            page: currentBomPage.value,
            descKey: 'description',
            sortField: sortFieldBom.value,
            sortOrder: sortOrderBom.value,
            dataRef: paginatedBomData,
        },
        acsales: {
            filters: filtersAcsales.value,
            page: currentBomPage.value,
            descKey: 'bom.description',
            sortField: sortFieldAcsales.value,
            sortOrder: sortOrderAcsales.value,
            dataRef: paginatedAcsalesData,
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

    if (type === 'acmat') {
        const materialPeriod = (currentFilters as any).period?.value;
        if (materialPeriod) params.append('material_period', materialPeriod);
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

const onLazyLoadAcmat = (event: any) => {
    const { first, rows, sortField, sortOrder } = event;

    const newPage = first / rows + 1;
    perPage.value = rows;

    if (newPage !== currentAcmatPage.value) {
        currentAcmatPage.value = newPage;
    }

    sortFieldAcmat.value = sortField || null;
    sortOrderAcmat.value = sortOrder || null;

    if (sortField !== null) {
        currentAcmatPage.value = 1;
    }

    loadLazyData(materialUrl.value, 'acmat');
};

const onLazyLoadAcsales = (event: any) => {
    const { first, rows, sortField, sortOrder } = event;

    const newPage = first / rows + 1;
    perPage.value = rows;

    if (newPage !== currentAcsalesPage.value) {
        currentAcsalesPage.value = newPage;
    }

    sortFieldAcsales.value = sortField || null;
    sortOrderAcsales.value = sortOrder || null;

    if (sortField !== null) {
        currentAcsalesPage.value = 1;
    }

    loadLazyData(materialUrl.value, 'acsales');
};

const onLazyLoadBom = (event: any) => {
    const { first, rows, sortField, sortOrder } = event;

    const newPage = first / rows + 1;
    perPage.value = rows;

    if (newPage !== currentBomPage.value) {
        currentBomPage.value = newPage;
    }

    sortFieldBom.value = sortField || null;
    sortOrderBom.value = sortOrder || null;

    if (sortField !== null) {
        currentBomPage.value = 1;
    }

    loadLazyData(bomUrl.value, 'bom');
};

watch(
    filtersAcmat,
    () => {
        if (!isReady.acmat) {
            isReady.acmat = true;
            return;
        }
        if (acmatTimeout.value) clearTimeout(acmatTimeout.value);
        acmatTimeout.value = setTimeout(() => {
            loadLazyData(materialUrl.value, 'acmat');
        }, 500);
    },
    { deep: true },
);

watch(
    filtersAcsales,
    () => {
        if (!isReady.acsales) {
            isReady.acsales = true;
            return;
        }
        if (acsalesTimeout.value) clearTimeout(acsalesTimeout.value);
        acsalesTimeout.value = setTimeout(() => {
            loadLazyData(salesUrl.value, 'acsales');
        }, 500);
    },
    { deep: true },
);

watch(
    filtersBom,
    () => {
        if (!isReady.bom) {
            isReady.bom = true;
            return;
        }
        if (bomTimeout.value) clearTimeout(bomTimeout.value);
        bomTimeout.value = setTimeout(() => {
            loadLazyData(bomUrl.value, 'bom');
        }, 500);
    },
    { deep: true },
);

onMounted(async () => {
    initFilters();
    try {
        await Promise.all([
            fetchMaterialPeriod(),
            loadLazyData(materialUrl.value, 'acmat'),
            loadLazyData(salesUrl.value, 'acsales'),
            loadLazyData(bomUrl.value, 'bom'),
        ]);
    } finally {
        isInitialLoading.value = false;
    }
});

const clearFilter = (type: 'acmat' | 'bom' | 'acsales') => {
    if (type === 'acmat') {
        filtersAcmat.value = {
            global: { value: null, matchMode: 'contains' },
            item_code: { value: null, matchMode: 'startsWith' },
            period: { value: null, matchMode: 'equals' },
            'bom.description': { value: null, matchMode: 'contains' },
        };
        currentAcmatPage.value = 1;
    }
    if (type === 'acsales') {
        filtersAcsales.value = {
            global: { value: null, matchMode: 'contains' },
            item_code: { value: null, matchMode: 'startsWith' },
            // period: { value: null, matchMode: 'equals' },
            'bom.description': { value: null, matchMode: 'contains' },
        };
        currentAcsalesPage.value = 1;
    }
};
</script>

<template>
    <Toast group="br" position="bottom-right" />

    <Head title="Actual Master Data" />

    <AppLayout>
        <div class="m-6">
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
                    class="w-[30rem]"
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
                            Hi <span class="text-red-400">{{ userName }}</span
                            >,
                        </p>
                        <div v-if="importType === 'acmat'">
                            <div class="mt-6 mb-2 font-semibold">Select Actual Material Price Period:</div>
                            <div class="flex space-x-4">
                                <div class="flex-1">
                                    <DatePicker
                                        v-model="year"
                                        view="year"
                                        dateFormat="yy"
                                        :showClear="true"
                                        :selectionMode="currentSelectionMode"
                                        :maxDate="maxDate"
                                        placeholder="Select year"
                                    />
                                </div>
                            </div>
                        </div>

                        <p>
                            Are you sure you want to import
                            <strong class="text-blue-500">{{ importName }}</strong
                            >?
                        </p>

                        <div class="flex justify-end gap-3 pt-4">
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
                            Hi <span class="text-red-400">{{ userName }}</span
                            >,
                        </p>

                        <p>
                            Import
                            <strong class="text-green-500">Finish</strong>, it's safe to close window.
                        </p>

                        <div v-if="importResult.invalidItems.length > 0">
                            <p><span class="text-xl font-semibold text-orange-400">Failed</span> to import:</p>
                            <table class="w-full border-collapse text-left">
                                <thead>
                                    <tr>
                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Item Code</th>
                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Description</th>

                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Menggunakan template v-if dan v-for -->
                                    <template v-if="importResult.invalidItems.length > 0">
                                        <tr v-for="item in importResult.invalidItems" :key="item.no">
                                            <td class="border-b border-gray-800 px-4 py-2">
                                                {{ item.item_code }}
                                            </td>
                                            <td class="border-b border-gray-800 px-4 py-2">
                                                {{ item.description }}
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

                <Dialog v-model:visible="componentDialog" :header="`Components of `" modal class="w-[60rem]" :closable="false">
                    <DataTable :value="componentData" responsiveLayout="scroll">
                        <div class="mr-2 mb-2 flex justify-end">
                            <Button label="Close" severity="warn" @click="closeComponentDialog" />
                        </div>
                        <Column header="#">
                            <template #body="{ index }">
                                {{ index + 1 }}
                            </template>
                        </Column>
                        <Column field="item_code" header="Item Code" style="white-space: nowrap" />
                        <Column field="description" header="Description" />
                        <Column field="uom" header="Unit of Material" />
                        <Column field="quantity" header="Quantity" />
                        <Column field="warehouse" header="Warehouse" />
                        <Column field="depth" header="Depth" />
                        <Column field="bom_type" header="Bom Type" />
                    </DataTable>
                </Dialog>
            </div>

            <div class="mx-26 mb-26">
                <Tabs v-model="activeTab">
                    <TabList>
                        <Tab value="0">Actual Material Price</Tab>
                        <Tab value="1">Actual Sales Quantity</Tab>
                        <Tab value="2">Actual Bill of Material</Tab>
                    </TabList>

                    <TabPanels>
                        <TabPanel value="0">
                            <section ref="acmat" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 md:mb-0 dark:text-white">Actual Material Price</h2>

                                    <div class="text-right text-gray-700 dark:text-gray-300">
                                        <div>
                                            Data source From :
                                            <span class="text-cyan-400">{{ dataSource[0] }}</span>
                                        </div>
                                        <div>
                                            Last Update :
                                            <span class="text-red-300">{{ lastMaster[0] ? formatlastUpdate(lastMaster[0]) : '-' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <DataTable
                                    :value="paginatedAcmatData?.data || []"
                                    :lazy="true"
                                    :totalRecords="paginatedAcmatData?.total || 0"
                                    :rows="perPage"
                                    @page="onLazyLoadAcmat"
                                    @sort="onLazyLoadAcmat"
                                    :first="(currentAcmatPage - 1) * perPage"
                                    :paginator="true"
                                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                                    currentPageReportTemplate="Showing {first} to {last} from {totalRecords} data"
                                    responsiveLayout="scroll"
                                    :globalFilterFields="['item_code', 'description.name', 'period']"
                                    showGridlines
                                    :removableSort="true"
                                    v-model:filters="filtersAcmat"
                                    filterDisplay="row"
                                    :loading="loadingStates.acmat || isInitialLoading"
                                    ref="dtAcmat"
                                >
                                    <template #header>
                                        <div class="flex justify-between">
                                            <div class="flex justify-start space-x-2">
                                                <Button
                                                    icon="pi pi-download"
                                                    label=" Export Report"
                                                    unstyled
                                                    class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-orange-700 sm:w-auto"
                                                    @click="exportCSV('acmat')"
                                                />
                                                <FileUpload
                                                    v-if="auth?.user?.permissions?.includes('Update_MasterData')"
                                                    ref="fileUploaderBP"
                                                    mode="basic"
                                                    name="file"
                                                    :customUpload="true"
                                                    accept=".csv"
                                                    chooseLabel=" Import CSV"
                                                    chooseIcon="pi pi-upload"
                                                    @select="(event) => handleCSVImport(event, 'acmat')"
                                                    class="w-full cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-cyan-700 sm:w-auto"
                                                />
                                            </div>
                                            <div class="justify-end">
                                                <div class="flex justify-between space-x-2">
                                                    <Button
                                                        type="button"
                                                        icon="pi pi-filter-slash"
                                                        label=" Clear"
                                                        unstyled
                                                        class="w-full cursor-pointer rounded-xl bg-slate-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-slate-700 sm:w-auto"
                                                        @click="clearFilter('acmat')"
                                                    />

                                                    <IconField>
                                                        <InputIcon>
                                                            <i class="pi pi-search" />
                                                        </InputIcon>
                                                        <InputText v-model="filtersAcmat['global'].value" placeholder="Keyword Search" />
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
                                            <Column header="Item Code" :rowspan="2" sortable v-bind="tbStyle('main')"></Column>
                                            <Column header="Description" :rowspan="2" sortable v-bind="tbStyle('main')"></Column>
                                            <Column header="Period" :rowspan="2" sortable v-bind="tbStyle('main')"></Column>
                                            <Column header="January" :colspan="3" v-bind="tbStyle('rm')"></Column>
                                            <Column header="February" :colspan="3" v-bind="tbStyle('pr')"></Column>
                                            <Column header="March" :colspan="3" v-bind="tbStyle('wip')"></Column>
                                            <Column header="April" :colspan="3" v-bind="tbStyle('fg')"></Column>
                                            <Column header="May" :colspan="3" v-bind="tbStyle('rm')"></Column>
                                            <Column header="June" :colspan="3" v-bind="tbStyle('pr')"></Column>
                                            <Column header="July" :colspan="3" v-bind="tbStyle('wip')"></Column>
                                            <Column header="August" :colspan="3" v-bind="tbStyle('fg')"></Column>
                                            <Column header="September" :colspan="3" v-bind="tbStyle('rm')"></Column>
                                            <Column header="October" :colspan="3" v-bind="tbStyle('pr')"></Column>
                                            <Column header="November" :colspan="3" v-bind="tbStyle('wip')"></Column>
                                            <Column header="December" :colspan="3" v-bind="tbStyle('fg')"></Column>
                                        </Row>

                                        <Row>
                                            <Column field="jan_amount" sortable header="Amount" v-bind="tbStyle('rm')"></Column>
                                            <Column field="jan_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                            <Column field="jan_price" sortable header="Price" v-bind="tbStyle('rm')"></Column>

                                            <Column field="feb_amount" sortable header="Amount" v-bind="tbStyle('pr')"></Column>
                                            <Column field="feb_qty" sortable header="Qty" v-bind="tbStyle('pr')"></Column>
                                            <Column field="feb_price" sortable header="Price" v-bind="tbStyle('pr')"></Column>

                                            <Column field="mar_amount" sortable header="Amount" v-bind="tbStyle('wip')"></Column>
                                            <Column field="mar_qty" sortable header="Qty" v-bind="tbStyle('wip')"></Column>
                                            <Column field="mar_price" sortable header="Price" v-bind="tbStyle('wip')"></Column>

                                            <Column field="apr_amount" sortable header="Amount" v-bind="tbStyle('fg')"></Column>
                                            <Column field="apr_qty" sortable header="Qty" v-bind="tbStyle('fg')"></Column>
                                            <Column field="apr_price" sortable header="Price" v-bind="tbStyle('fg')"></Column>

                                            <Column field="may_amount" sortable header="Amount" v-bind="tbStyle('rm')"></Column>
                                            <Column field="may_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                            <Column field="may_price" sortable header="Price" v-bind="tbStyle('rm')"></Column>

                                            <Column field="jun_amount" sortable header="Amount" v-bind="tbStyle('pr')"></Column>
                                            <Column field="jun_qty" sortable header="Qty" v-bind="tbStyle('pr')"></Column>
                                            <Column field="jun_price" sortable header="Price" v-bind="tbStyle('pr')"></Column>

                                            <Column field="jul_amount" sortable header="Amount" v-bind="tbStyle('wip')"></Column>
                                            <Column field="jul_qty" sortable header="Qty" v-bind="tbStyle('wip')"></Column>
                                            <Column field="jul_price" sortable header="Price" v-bind="tbStyle('wip')"></Column>

                                            <Column field="aug_amount" sortable header="Amount" v-bind="tbStyle('fg')"></Column>
                                            <Column field="aug_qty" sortable header="Qty" v-bind="tbStyle('fg')"></Column>
                                            <Column field="aug_price" sortable header="Price" v-bind="tbStyle('fg')"></Column>

                                            <Column field="sep_amount" sortable header="Amount" v-bind="tbStyle('rm')"></Column>
                                            <Column field="sep_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                            <Column field="sep_price" sortable header="Price" v-bind="tbStyle('rm')"></Column>

                                            <Column field="oct_amount" sortable header="Amount" v-bind="tbStyle('pr')"></Column>
                                            <Column field="oct_qty" sortable header="Qty" v-bind="tbStyle('pr')"></Column>
                                            <Column field="oct_price" sortable header="Price" v-bind="tbStyle('pr')"></Column>

                                            <Column field="nov_amount" sortable header="Amount" v-bind="tbStyle('wip')"></Column>
                                            <Column field="nov_qty" sortable header="Qty" v-bind="tbStyle('wip')"></Column>
                                            <Column field="nov_price" sortable header="Price" v-bind="tbStyle('wip')"></Column>

                                            <Column field="dec_amount" sortable header="Amount" v-bind="tbStyle('fg')"></Column>
                                            <Column field="dec_qty" sortable header="Qty" v-bind="tbStyle('fg')"></Column>
                                            <Column field="dec_price" sortable header="Price" v-bind="tbStyle('fg')"></Column>
                                        </Row>
                                    </ColumnGroup>

                                    <Column
                                        resizableColumns
                                        columnResizeMode="fit"
                                        field="item_code"
                                        header="Material Code"
                                        filter
                                        sortable
                                        :showFilterMenu="false"
                                        v-bind="tbStyle('main')"
                                    >
                                        <template #body="{ data }">
                                            {{ data.item_code }}
                                        </template>
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                type="text"
                                                placeholder="Search by code"
                                            />
                                        </template>
                                    </Column>

                                    <Column
                                        resizableColumns
                                        columnResizeMode="fit"
                                        field="bom.description"
                                        header="Description"
                                        :showFilterMenu="false"
                                        v-bind="tbStyle('main')"
                                    >
                                        <template #body="{ data }">
                                            {{ data.bom?.description ?? '-' }}
                                        </template>

                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search Description"
                                                class="w-full"
                                            />
                                        </template>
                                    </Column>

                                    <Column
                                        resizableColumns
                                        columnResizeMode="fit"
                                        field="period"
                                        header="Period"
                                        sortable
                                        v-bind="tbStyle('main')"
                                        :showFilterMenu="false"
                                    >
                                        <template #filter="{ filterModel, filterCallback }">
                                            <div class="flex justify-center">
                                                <Select
                                                    v-model="filterModel.value"
                                                    :options="listMaterialPeriod"
                                                    optionLabel="name"
                                                    optionValue="name"
                                                    placeholder="Period"
                                                    class="w-full"
                                                    :showClear="true"
                                                    @change="filterCallback()"
                                                />
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="jan_amount" :exportable="false" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.jan_amount) }}
                                        </template>
                                    </Column>
                                    <Column field="jan_qty" :exportable="false" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.jan_qty).toLocaleString('id-ID') }}
                                        </template> </Column
                                    ><Column field="jan_price" :exportable="false" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.jan_price) }}
                                        </template>
                                    </Column>

                                    <Column field="feb_amount" :exportable="false" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.feb_amount) }}
                                        </template>
                                    </Column>
                                    <Column field="feb_qty" :exportable="false" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.feb_qty).toLocaleString('id-ID') }}
                                        </template> </Column
                                    ><Column field="feb_price" :exportable="false" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.feb_price) }}
                                        </template>
                                    </Column>

                                    <Column field="mar_amount" :exportable="false" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.mar_amount) }}
                                        </template>
                                    </Column>
                                    <Column field="mar_qty" :exportable="false" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.mar_qty).toLocaleString('id-ID') }}
                                        </template> </Column
                                    ><Column field="mar_price" :exportable="false" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.mar_price) }}
                                        </template>
                                    </Column>

                                    <Column field="apr_amount" :exportable="false" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.apr_amount) }}
                                        </template>
                                    </Column>
                                    <Column field="apr_qty" :exportable="false" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.apr_qty).toLocaleString('id-ID') }}
                                        </template> </Column
                                    ><Column field="apr_price" :exportable="false" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.apr_price) }}
                                        </template>
                                    </Column>

                                    <Column field="may_amount" :exportable="false" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.may_amount) }}
                                        </template>
                                    </Column>
                                    <Column field="may_qty" :exportable="false" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.may_qty).toLocaleString('id-ID') }}
                                        </template> </Column
                                    ><Column field="may_price" :exportable="false" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.may_price) }}
                                        </template>
                                    </Column>

                                    <Column field="jun_amount" :exportable="false" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.jun_amount) }}
                                        </template>
                                    </Column>
                                    <Column field="jun_qty" :exportable="false" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.jun_qty).toLocaleString('id-ID') }}
                                        </template> </Column
                                    ><Column field="jun_price" :exportable="false" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.jun_price) }}
                                        </template>
                                    </Column>

                                    <Column field="jul_amount" :exportable="false" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.jul_amount) }}
                                        </template>
                                    </Column>
                                    <Column field="jul_qty" :exportable="false" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.jul_qty).toLocaleString('id-ID') }}
                                        </template> </Column
                                    ><Column field="jul_price" :exportable="false" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.jul_price) }}
                                        </template>
                                    </Column>

                                    <Column field="aug_amount" :exportable="false" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.aug_amount) }}
                                        </template>
                                    </Column>
                                    <Column field="aug_qty" :exportable="false" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.aug_qty).toLocaleString('id-ID') }}
                                        </template> </Column
                                    ><Column field="aug_price" :exportable="false" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.aug_price) }}
                                        </template>
                                    </Column>

                                    <Column field="sep_amount" :exportable="false" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.sep_amount) }}
                                        </template>
                                    </Column>
                                    <Column field="sep_qty" :exportable="false" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.sep_qty).toLocaleString('id-ID') }}
                                        </template> </Column
                                    ><Column field="sep_price" :exportable="false" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.sep_price) }}
                                        </template>
                                    </Column>

                                    <Column field="oct_amount" :exportable="false" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.oct_amount) }}
                                        </template>
                                    </Column>
                                    <Column field="oct_qty" :exportable="false" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.oct_qty).toLocaleString('id-ID') }}
                                        </template> </Column
                                    ><Column field="oct_price" :exportable="false" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.oct_price) }}
                                        </template>
                                    </Column>

                                    <Column field="nov_amount" :exportable="false" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.nov_amount) }}
                                        </template>
                                    </Column>
                                    <Column field="nov_qty" :exportable="false" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.nov_qty).toLocaleString('id-ID') }}
                                        </template> </Column
                                    ><Column field="nov_price" :exportable="false" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.nov_price) }}
                                        </template>
                                    </Column>

                                    <Column field="dec_amount" :exportable="false" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.dec_amount) }}
                                        </template>
                                    </Column>
                                    <Column field="dec_qty" :exportable="false" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.dec_qty).toLocaleString('id-ID') }}
                                        </template> </Column
                                    ><Column field="dec_price" :exportable="false" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.dec_price) }}
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="1">
                            <section ref="acmat" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 md:mb-0 dark:text-white">Sales Quantity / Month</h2>

                                    <div class="text-right text-gray-700 dark:text-gray-300">
                                        <div>
                                            Data source From :
                                            <span class="text-cyan-400">{{ dataSource[1] }}</span>
                                        </div>
                                        <div>
                                            Last Update :
                                            <span class="text-red-300">{{ lastMaster[1] ? formatlastUpdate(lastMaster[1]) : '-' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <DataTable
                                    :value="paginatedAcsalesData?.data || []"
                                    :lazy="true"
                                    :totalRecords="paginatedAcsalesData?.total || 0"
                                    :rows="perPage"
                                    @page="onLazyLoadAcsales"
                                    @sort="onLazyLoadAcsales"
                                    :first="(currentAcsalesPage - 1) * perPage"
                                    :paginator="true"
                                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                                    currentPageReportTemplate="Showing {first} to {last} from {totalRecords} data"
                                    responsiveLayout="scroll"
                                    :globalFilterFields="['item_code', 'description.name', 'period']"
                                    showGridlines
                                    :removableSort="true"
                                    v-model:filters="filtersAcsales"
                                    filterDisplay="row"
                                    :loading="loadingStates.acsales || isInitialLoading"
                                    ref="dtAcsales"
                                >
                                    <template #header>
                                        <div class="flex justify-between">
                                            <div class="flex justify-start space-x-2">
                                                <Button
                                                    icon="pi pi-download"
                                                    label=" Export Report"
                                                    unstyled
                                                    class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-orange-700 sm:w-auto"
                                                    @click="exportCSV('acsales')"
                                                />
                                                <FileUpload
                                                    v-if="auth?.user?.permissions?.includes('Update_MasterData')"
                                                    ref="fileUploaderBP"
                                                    mode="basic"
                                                    name="file"
                                                    :customUpload="true"
                                                    accept=".csv"
                                                    chooseLabel=" Import CSV"
                                                    chooseIcon="pi pi-upload"
                                                    @select="(event) => handleCSVImport(event, 'acsales')"
                                                    class="w-full cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-cyan-700 sm:w-auto"
                                                />
                                            </div>
                                            <div class="justify-end">
                                                <div class="flex justify-between space-x-2">
                                                    <Button
                                                        type="button"
                                                        icon="pi pi-filter-slash"
                                                        label=" Clear"
                                                        unstyled
                                                        class="w-full cursor-pointer rounded-xl bg-slate-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-slate-700 sm:w-auto"
                                                        @click="clearFilter('acsales')"
                                                    />

                                                    <IconField>
                                                        <InputIcon>
                                                            <i class="pi pi-search" />
                                                        </InputIcon>
                                                        <InputText v-model="filtersAcsales['global'].value" placeholder="Keyword Search" />
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
                                        resizableColumns
                                        columnResizeMode="fit"
                                        field="item_code"
                                        header="Material Code"
                                        filter
                                        sortable
                                        :showFilterMenu="false"
                                        v-bind="tbStyle('main')"
                                    >
                                        <template #body="{ data }">
                                            {{ data.item_code }}
                                        </template>
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                type="text"
                                                placeholder="Search by code"
                                            />
                                        </template>
                                    </Column>

                                    <Column
                                        resizableColumns
                                        columnResizeMode="fit"
                                        field="bom.description"
                                        header="Description"
                                        :showFilterMenu="false"
                                        v-bind="tbStyle('main')"
                                    >
                                        <template #body="{ data }">
                                            {{ data.bom?.description ?? '-' }}
                                        </template>

                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search Description"
                                                class="w-full"
                                            />
                                        </template>
                                    </Column>

                                    <!-- <Column
                                        resizableColumns
                                        columnResizeMode="fit"
                                        field="period"
                                        header="Period"
                                        sortable
                                        v-bind="tbStyle('main')"
                                        :showFilterMenu="false"
                                    >
                                        <template #filter="{ filterModel, filterCallback }">
                                            <div class="flex justify-center">
                                                <Select
                                                    v-model="filterModel.value"
                                                    :options="listSalesPeriod"
                                                    optionLabel="name"
                                                    optionValue="name"
                                                    placeholder="Period"
                                                    class="w-full"
                                                    :showClear="true"
                                                    @change="filterCallback()"
                                                />
                                            </div>
                                        </template>
                                    </Column> -->
                                    <Column field="jan_qty" header="Jan Qty" sortable :exportable="false" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.jan_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="feb_qty" header="Feb Qty" sortable :exportable="false" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.feb_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="mar_qty" header="Mar Qty" sortable :exportable="false" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.mar_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="apr_qty" header="Apr Qty" sortable :exportable="false" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.apr_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="may_qty" header="May Qty" sortable :exportable="false" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.may_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="jun_qty" header="Jun Qty" sortable :exportable="false" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.jun_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="jul_qty" header="Jul Qty" sortable :exportable="false" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.jul_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="aug_qty" header="Aug Qty" sortable :exportable="false" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.aug_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="sep_qty" header="Sep Qty" sortable :exportable="false" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.sep_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="oct_qty" header="Oct Qty" sortable :exportable="false" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.oct_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="nov_qty" header="Nov Qty" sortable :exportable="false" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.nov_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="dec_qty" header="Dec Qty" sortable :exportable="false" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.dec_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="2">
                            <section ref="bom" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 md:mb-0 dark:text-white">Actual Bill of Material</h2>

                                    <div class="text-right text-gray-700 dark:text-gray-300">
                                        <div>
                                            Data source From :
                                            <span class="text-cyan-400">{{ dataSource[1] }}</span>
                                        </div>
                                        <div>
                                            Last Update :
                                            <span class="text-red-300">{{ lastMaster[2] ? formatlastUpdate(lastMaster[2]) : '-' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <DataTable
                                    :value="paginatedBomData?.data || []"
                                    :lazy="true"
                                    :totalRecords="paginatedBomData?.total || 0"
                                    :rows="perPage"
                                    @page="onLazyLoadBom"
                                    @sort="onLazyLoadBom"
                                    :first="(currentBomPage - 1) * perPage"
                                    :paginator="true"
                                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                                    currentPageReportTemplate="Showing {first} to {last} from {totalRecords} data"
                                    responsiveLayout="scroll"
                                    :globalFilterFields="['item_code', 'description']"
                                    showGridlines
                                    :removableSort="true"
                                    v-model:filters="filtersBom"
                                    filterDisplay="row"
                                    :loading="loadingStates.bom || isInitialLoading"
                                    ref="dtBom"
                                >
                                    <template #header>
                                        <div class="flex justify-between">
                                            <div class="flex justify-start space-x-2">
                                                <Button
                                                    icon="pi pi-download"
                                                    label=" Export Report"
                                                    unstyled
                                                    class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-orange-700 sm:w-auto"
                                                    @click="exportCSV('bom')"
                                                />
                                                <FileUpload
                                                    v-if="auth?.user?.permissions?.includes('Update_MasterData')"
                                                    ref="fileUploaderBP"
                                                    mode="basic"
                                                    name="file"
                                                    :customUpload="true"
                                                    accept=".csv"
                                                    chooseLabel=" Import CSV"
                                                    chooseIcon="pi pi-upload"
                                                    @select="(event) => handleCSVImport(event, 'bom')"
                                                    class="w-full cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-cyan-700 sm:w-auto"
                                                />
                                            </div>
                                            <div class="justify-end">
                                                <div class="flex justify-between space-x-2">
                                                    <Button
                                                        type="button"
                                                        icon="pi pi-filter-slash"
                                                        label=" Clear"
                                                        unstyled
                                                        class="w-full cursor-pointer rounded-xl bg-slate-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-slate-700 sm:w-auto"
                                                        @click="clearFilter('bom')"
                                                    />

                                                    <IconField>
                                                        <InputIcon>
                                                            <i class="pi pi-search" />
                                                        </InputIcon>
                                                        <InputText v-model="filtersBom['global'].value" placeholder="Keyword Search" />
                                                    </IconField>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <template #empty>
                                        <div v-if="!loading && !isInitialLoading" class="flex justify-center p-4">No data found.</div>
                                        <div v-else class="flex justify-center p-4">Loading data, please wait...</div>
                                    </template>

                                    <Column field="item_code" header="Item Code" sortable filter :showFilterMenu="false" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ data.item_code }}
                                        </template>
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                type="text"
                                                placeholder="Search by code"
                                            />
                                        </template>
                                    </Column>

                                    <Column field="description" header="Name" sortable filter :showFilterMenu="false" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ data.description ?? '-' }}
                                        </template>
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                type="text"
                                                placeholder="Search by Name"
                                            />
                                        </template>
                                    </Column>

                                    <Column field="depth" header="Depth" filter :showFilterMenu="false" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ data.depth }}
                                        </template>
                                    </Column>

                                    <Column field="action" header="Action" :exportable="false" v-bind="tbStyle('rm')"
                                        ><template #body="slotProps">
                                            <Button
                                                v-tooltip="'View Component'"
                                                icon="pi pi-eye"
                                                severity="info"
                                                rounded
                                                text
                                                @click="viewComponents(slotProps.data)"
                                            /> </template
                                    ></Column>
                                </DataTable>
                            </section>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </div>
    </AppLayout>
</template>
