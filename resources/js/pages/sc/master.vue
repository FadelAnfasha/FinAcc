<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import axios from 'axios';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import FileUpload, { FileUploadUploaderEvent } from 'primevue/fileupload';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import ProgressBar from 'primevue/progressbar';
import Select from 'primevue/select';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { computed, nextTick, onMounted, reactive, ref, Ref, watch } from 'vue';

const materialUrl = ref('/finacc/api/standard/get-material');
const bomUrl = ref('/finacc/api/standard/get-bom');
const consumableUrl = ref('/finacc/api/standard/get-consumable');

const dtStamat = ref();
const dtBom = ref();
const dtConsumable = ref();
const paginatedStamatData = ref<any>(null);
const paginatedBomData = ref<any>(null);
const paginatedConsumableData = ref<any>(null);
const currentPage = ref(1);
const listMaterialGroup = ref<List[]>([]);
const currentStamatPage = ref(1);
const currentBomPage = ref(1);
const currentConsumablePage = ref(1);

const perPage = ref(10);
const activeTab = ref<string | null>;

const sortFieldStamat = ref(null);
const sortOrderStamat = ref(null);
const sortFieldBom = ref(null);
const sortOrderBom = ref(null);
const sortFieldConsumable = ref(null);
const sortOrderConsumable = ref(null);

const toast = useToast();
const page = usePage();
const showDialog = ref(false);
const dialogWidth = ref('40rem');
const editType = ref<'stamat' | 'consumable' | null>(null);
const destroyType = ref<'stamat' | 'consumable' | null>(null);
const headerType = ref<any>({});
const editedData = ref<any>({});
const destroyedData = ref<any>({});
const showImportDialog: Ref<boolean> = ref(false);
const importName = ref<any>({});
const selectedFile = ref<File | null>(null);
const importType = ref<'stamat' | 'bom' | 'consumable' | null>(null);
const notImported = ref(true);
const fileUploaderBom = ref<any>(null);
const fileUploaderStamat = ref<any>(null);
const fileUploaderConsumable = ref<any>(null);

const uploadProgress = ref(0);
const isUploading = ref(false);
const loading = ref(false);
const userName = computed(() => page.props.auth?.user?.name ?? '');
const dataSource = [
    'Share Others/Finacc/Bill of Material/Standard Data/Standard Material Price/standardMat_master.csv',
    'Share Others/Finacc/Bill of Material/Standard Data/Bill of Material (BOM)/bom_master.csv',
    'Share Others/Finacc/Bill of Material/Standard Data/Consumable/consumable_master.csv',
];
const componentDialog = ref(false);
const componentData = ref([]);
const selectedFinishGood = ref(null);

const isReady = reactive({
    stamat: false,
    bom: false,
    consumable: false,
});
const stamatTimeout = ref<ReturnType<typeof setTimeout> | null>(null);
const bomTimeout = ref<ReturnType<typeof setTimeout> | null>(null);
const consumableTimeout = ref<ReturnType<typeof setTimeout> | null>(null);
const isInitialLoading = ref(true);

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

function editData(data: any, type: 'stamat' | 'consumable') {
    editedData.value = { ...data };
    editType.value = type;
    headerType.value = 'Edit data';
    // Atur lebar berdasarkan type
    if (type === 'stamat') {
        dialogWidth.value = '40rem';
    } else if (type === 'consumable') {
        dialogWidth.value = '40rem';
    }
    showDialog.value = true;
}

function handleSave() {
    const type = editType.value;
    const item_code = editedData.value.item_code;

    // Validasi awal
    if (!type || !item_code) return;

    // Tentukan route berdasarkan tipe
    const routeName = type === 'consumable' ? 'master.standard.update.consumable' : 'master.standard.update.material';

    router.put(route(routeName, item_code), editedData.value, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                group: 'br',
                detail: `Data ${item_code} updated successfully`,
                life: 3000,
            });

            // Logika khusus untuk 'stamat' (jika ada)
            if (type === 'stamat') {
                loadLazyData(materialUrl.value, 'stamat');
            } else if (type === 'consumable') {
                loadLazyData(consumableUrl.value, 'consumable');
            }

            showDialog.value = false;
            editType.value = null;
        },
        onError: () => {
            toast.add({
                severity: 'warn',
                summary: 'Error',
                group: 'br',
                // Catatan: Di kode asli Anda tertulis 'Failed to delete',
                // saya ubah ke 'update' agar sesuai dengan fungsinya.
                detail: `Failed to update data with ${item_code}`,
                life: 3000,
            });
            editType.value = null;
        },
    });
}

function destroyData(data: any, type: 'stamat' | 'consumable') {
    destroyedData.value = { ...data };
    destroyType.value = type;
    headerType.value = 'Delete data';
    if (type === 'stamat') {
        dialogWidth.value = '40rem';
    } else if (type === 'consumable') {
        dialogWidth.value = '40rem';
    }
    showDialog.value = true;
}

function handleDestroy() {
    const type = destroyType.value;
    const item_code = destroyedData.value.item_code;

    // 1. Validasi awal
    if (!type || !item_code) return;

    const routeName = type === 'stamat' ? 'master.standard.destroy.material' : 'master.standard.destroy.consumable';

    router.delete(route(routeName, item_code), {
        data: destroyedData.value,
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.add({
                severity: 'error', // Anda menggunakan 'error' untuk warna merah (biasa untuk delete)
                summary: 'Success',
                detail: `Data ${item_code} deleted successfully`,
                group: 'br',
                life: 3000,
            });

            // 3. Logika khusus untuk 'stamat'
            if (type === 'stamat') {
                loadLazyData(materialUrl.value, 'stamat');
            } else if (type === 'consumable') {
                loadLazyData(consumableUrl.value, 'consumable');
            }

            showDialog.value = false;
            destroyType.value = null;
        },
        onError: () => {
            toast.add({
                severity: 'warn',
                summary: 'Error',
                group: 'br',
                detail: `Failed to delete this ${item_code} data`,
                life: 3000,
            });
            destroyType.value = null;
        },
    });
}

const viewComponents = async (data: any) => {
    // data adalah object dari baris yang diklik (slotProps.data)
    const itemId = data.id;
    const componentUrl = '/finacc/api/standard/get-component';

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

function handleCSVImport(event: FileUploadUploaderEvent, type: 'stamat' | 'bom' | 'consumable') {
    let file: File | undefined;

    if (Array.isArray(event.files)) {
        file = event.files[0];
    } else if (event.files instanceof File) {
        file = event.files;
    }

    if (!file) return;

    const expectedNames = {
        stamat: 'standardMat_master.csv',
        bom: 'bom_master.csv',
        consumable: 'consumable_master.csv',
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
            if (type === 'stamat') fileUploaderStamat.value?.clear();
            if (type === 'bom') fileUploaderBom.value?.clear();
            if (type === 'consumable') fileUploaderConsumable.value?.clear();
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

function cancelCSVimport(type: 'stamat' | 'bom' | 'consumable') {
    showImportDialog.value = false;
    selectedFile.value = null;

    nextTick(() => {
        if (type === 'stamat') fileUploaderStamat.value?.clear();
        if (type === 'bom') fileUploaderBom.value?.clear();
        if (type === 'consumable') fileUploaderConsumable.value?.clear();
    });
}

function confirmUpload(type: 'stamat' | 'bom' | 'consumable') {
    if (!selectedFile.value || !importType.value) return;

    const formData = new FormData();
    formData.append('file', selectedFile.value);

    const routes = {
        stamat: 'master.standard.import.material',
        bom: 'master.standard.import.bom',
        consumable: 'master.standard.import.consumable',
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
            if (type === 'stamat') {
                loadLazyData(materialUrl.value, 'stamat');
            } else if (type === 'bom') {
                loadLazyData(bomUrl.value, 'bom');
            } else if (type === 'consumable') {
                loadLazyData(consumableUrl.value, 'consumable');
            }
            nextTick(() => {
                if (type === 'stamat') fileUploaderStamat.value?.clear();
                if (type === 'bom') fileUploaderBom.value?.clear();
                if (type === 'consumable') fileUploaderConsumable.value?.clear();
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
                if (type === 'stamat') fileUploaderStamat.value?.clear();
                if (type === 'bom') fileUploaderBom.value?.clear();
                if (type === 'consumable') fileUploaderConsumable.value?.clear();
            });
        },
    });
}

function resetImportState() {
    uploadProgress.value = 0;
    selectedFile.value = null;
    notImported.value = true;
}

function startPollingProgress(type: 'stamat' | 'bom' | 'consumable') {
    uploadProgress.value = 0;

    const endpointMap = {
        stamat: '/finacc/standard/import-material-progress',
        bom: '/finacc/standard/import-bom-progress',
        consumable: '/finacc/standard/import-consumable-progress',
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

async function exportCSV(type: 'stamat' | 'bom' | 'consumable') {
    let $filename = '';
    let $dtComponent = null;

    if (type === 'stamat') {
        $filename = 'standard-mat';
        $dtComponent = dtStamat.value;
    } else if (type === 'bom') {
        $filename = 'bom';
        $dtComponent = dtBom.value;
    } else if (type === 'consumable') {
        $filename = 'consumable';
        $dtComponent = dtConsumable.value;
    }

    const exportFilename = `${$filename}-${new Date().toISOString().slice(0, 10)}.csv`;

    if (type === 'stamat') {
        try {
            const response = await axios.get(route('master.standard.get.all.material'));
            const allData = response.data;

            if (allData && allData.length > 0) {
                downloadCSV(allData, exportFilename);
            }
        } catch (error) {
            console.error('Export failed', error);
        } finally {
        }
        return;
    }

    if ($dtComponent) {
        $dtComponent.exportCSV({
            selectionOnly: false,
            filename: exportFilename,
        });
    }
}

function downloadCSV(data: any[], filename: string) {
    if (data.length === 0) return;

    const headers = Object.keys(data[0]).join(',');

    const rows = data
        .map((obj) =>
            Object.values(obj)
                .map((val) => `"${val ?? ''}"`)
                .join(','),
        )
        .join('\n');

    const csvContent = `${headers}\n${rows}`;
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');

    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function formatDate(dateStr: string): string {
    const date = new Date(dateStr);
    const yy = String(date.getFullYear());
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yy}-${mm}-${dd}`;
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

const fetchMaterialGroup = async () => {
    try {
        const materialGroup = await fetchDatas('/finacc/api/standard/list-material-group');

        if (Array.isArray(materialGroup)) {
            listMaterialGroup.value = materialGroup.map((p: string) => ({ name: p, code: p }));
        } else {
            console.error('API /list-group-material did not return an array:', materialGroup);
            listMaterialGroup.value = [];
        }
    } catch (error) {
        console.error('Failed to fetch material group list:', error);
        listMaterialGroup.value = [];
    }
};

const filtersStamat = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    item_code: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    'bom.description': { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    item_group: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const filtersBom = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    item_code: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    description: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
});

const filtersConsumable = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    item_code: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    'bom.description': { value: null, matchMode: FilterMatchMode.STARTS_WITH },
});

const initFilters = () => {
    // Reset filters Stamat (karena ini yang digunakan oleh Tab 0)
    filtersStamat.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        item_code: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
        'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
        item_group: { value: null, matchMode: FilterMatchMode.EQUALS },
    };

    // Opsional: Reset filters Bom juga
    filtersBom.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        item_code: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
        description: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    };

    filtersConsumable.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        item_code: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
        'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};

const controllers = {
    stamat: null as AbortController | null,
    bom: null as AbortController | null,
    consumable: null as AbortController | null,
};

const loadingStates = reactive({
    stamat: false,
    bom: false,
    consumable: false,
});

const loadLazyData = async (url: string, type: 'stamat' | 'bom' | 'consumable') => {
    if (controllers[type]) controllers[type].abort();
    controllers[type] = new AbortController();

    loadingStates[type] = true;

    const configMap = {
        stamat: {
            filters: filtersStamat.value,
            page: currentStamatPage.value,
            descKey: 'bom.description',
            sortField: sortFieldStamat.value,
            sortOrder: sortOrderStamat.value,
            dataRef: paginatedStamatData,
        },
        bom: {
            filters: filtersBom.value,
            page: currentBomPage.value,
            descKey: 'description',
            sortField: sortFieldBom.value,
            sortOrder: sortOrderBom.value,
            dataRef: paginatedBomData,
        },
        consumable: {
            filters: filtersConsumable.value,
            page: currentConsumablePage.value,
            descKey: 'bom.description',
            sortField: sortFieldConsumable.value,
            sortOrder: sortOrderConsumable.value,
            dataRef: paginatedConsumableData,
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

    if (type === 'stamat') {
        const itemGroupValue = (currentFilters as any).item_group?.value;
        if (itemGroupValue) params.append('item_group_filter', itemGroupValue);
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

const onLazyLoadStamat = (event: any) => {
    const { first, rows, sortField, sortOrder } = event;

    const newPage = first / rows + 1;
    perPage.value = rows;

    if (newPage !== currentStamatPage.value) {
        currentStamatPage.value = newPage;
    }

    sortFieldStamat.value = sortField || null;
    sortOrderStamat.value = sortOrder || null;

    if (sortField !== null) {
        currentStamatPage.value = 1;
    }

    loadLazyData(materialUrl.value, 'stamat');
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

const onLazyLoadConsumable = (event: any) => {
    const { first, rows, sortField, sortOrder } = event;

    const newPage = first / rows + 1;
    perPage.value = rows;

    if (newPage !== currentConsumablePage.value) {
        currentConsumablePage.value = newPage;
    }

    sortFieldConsumable.value = sortField || null;
    sortOrderConsumable.value = sortOrder || null;

    if (sortField !== null) {
        currentConsumablePage.value = 1;
    }

    loadLazyData(consumableUrl.value, 'consumable');
};

watch(
    filtersStamat,
    () => {
        if (!isReady.stamat) {
            isReady.stamat = true;
            return;
        }

        if (stamatTimeout.value) clearTimeout(stamatTimeout.value);
        stamatTimeout.value = setTimeout(() => {
            loadLazyData(materialUrl.value, 'stamat');
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

watch(
    filtersConsumable,
    () => {
        if (!isReady.consumable) {
            isReady.consumable = true;
            return;
        }

        if (consumableTimeout.value) clearTimeout(consumableTimeout.value);
        consumableTimeout.value = setTimeout(() => {
            loadLazyData(consumableUrl.value, 'consumable');
        }, 500);
    },
    { deep: true },
);

onMounted(async () => {
    initFilters();
    try {
        await Promise.all([
            fetchMaterialGroup(),
            loadLazyData(materialUrl.value, 'stamat'),
            loadLazyData(bomUrl.value, 'bom'),
            loadLazyData(consumableUrl.value, 'consumable'),
        ]);
    } finally {
        isInitialLoading.value = false;
    }
});

const clearFilter = (type: 'stamat' | 'bom' | 'consumable') => {
    if (type === 'stamat') {
        filtersStamat.value = {
            global: { value: null, matchMode: 'contains' },
            item_code: { value: null, matchMode: 'startsWith' },
            item_group: { value: null, matchMode: 'equals' },
            'bom.description': { value: null, matchMode: 'contains' },
        };
        currentStamatPage.value = 1;
    } else if (type === 'bom') {
        filtersBom.value = {
            global: { value: null, matchMode: 'contains' },
            item_code: { value: null, matchMode: 'startsWith' },
            description: { value: null, matchMode: 'contains' },
        };
        currentBomPage.value = 1;
    } else if (type === 'consumable') {
        filtersConsumable.value = {
            global: { value: null, matchMode: 'contains' },
            item_code: { value: null, matchMode: 'startsWith' },
            'bom.description': { value: null, matchMode: 'contains' },
        };
        currentConsumablePage.value = 1;
    }
};
</script>

<template>
    <Toast group="br" position="bottom-right" />

    <Head title="Standard Master Data" />

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

                <Dialog v-model:visible="showDialog" :header="headerType" modal :style="{ width: dialogWidth }" :closable="false">
                    <div v-if="editType === 'stamat' || editType === 'consumable'" class="space-y-6">
                        <div class="mb-4 flex items-center gap-4">
                            <label for="item_code" class="w-24 font-semibold">Material Code</label>
                            <InputText id="item_code" class="flex-auto" v-model="editedData.item_code" autocomplete="off" disabled />
                        </div>

                        <div class="mb-4 flex items-center gap-4">
                            <label for="description" class="w-24 font-semibold">Description</label>
                            <InputText id="description" class="flex-auto" :value="editedData.bom?.description || '-'" autocomplete="off" disabled />
                        </div>

                        <div v-if="editType === 'stamat'" class="mb-4 flex items-center gap-4">
                            <label for="item_group" class="w-24 font-semibold">Group</label>
                            <Select
                                id="item_group"
                                v-model="editedData.item_group"
                                :options="listMaterialGroup"
                                optionLabel="name"
                                optionValue="code"
                                placeholder="Select Group"
                                class="w-full md:w-56"
                            />
                        </div>

                        <div class="mb-4 flex items-center gap-4">
                            <label for="price" class="w-24 font-semibold">Price</label>
                            <InputNumber
                                id="price"
                                v-model="editedData.price"
                                class="flex-auto"
                                inputId="currency-indonesia"
                                mode="currency"
                                currency="IDR"
                                locale="id-ID"
                                :maxFractionDigits="2"
                                :min="0"
                                autocomplete="off"
                            />
                        </div>

                        <div class="mt-6 flex justify-end gap-2">
                            <Button
                                type="button"
                                label="Cancel"
                                severity="secondary"
                                @click="
                                    showDialog = false;
                                    editType = null;
                                "
                            />
                            <Button type="button" label="Save" @click="handleSave" />
                        </div>
                    </div>

                    <div v-if="destroyType === 'stamat' || destroyType === 'consumable'" class="space-y-6">
                        <span>
                            Are you sure want to delete
                            <span class="font-bold">
                                {{ destroyType === 'stamat' ? 'Material' : 'Consumable' }}
                            </span>
                            data with item code
                            <span class="font-semibold text-red-600">{{ destroyedData.item_code }}</span>
                            and description
                            <span class="font-semibold text-red-600">{{ destroyedData.bom?.description || '-' }}</span
                            >?
                        </span>

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
                            />
                            <Button type="button" label="Delete" severity="danger" @click="handleDestroy" />
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
                        <Tab value="0">Standard Material Price</Tab>
                        <Tab value="1">Bill of Material</Tab>
                        <Tab value="2">Consumable</Tab>
                    </TabList>

                    <TabPanels>
                        <TabPanel value="0">
                            <section ref="stamat" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 md:mb-0 dark:text-white">Standard Material Price</h2>

                                    <div class="text-right text-gray-700 dark:text-gray-300">
                                        <div>
                                            Data source From :
                                            <span class="text-cyan-400">{{ dataSource[0] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <DataTable
                                    :value="paginatedStamatData?.data || []"
                                    :lazy="true"
                                    :totalRecords="paginatedStamatData?.total || 0"
                                    @page="onLazyLoadStamat"
                                    @sort="onLazyLoadStamat"
                                    :first="(currentStamatPage - 1) * perPage"
                                    :paginator="true"
                                    :rows="perPage"
                                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                                    currentPageReportTemplate="Showing {first} to {last} from {totalRecords} data"
                                    responsiveLayout="scroll"
                                    :globalFilterFields="['item_code', 'description.name', 'item_group']"
                                    showGridlines
                                    :removableSort="true"
                                    v-model:filters="filtersStamat"
                                    filterDisplay="row"
                                    :loading="loadingStates.stamat || isInitialLoading"
                                    ref="dtStamat"
                                >
                                    <template #header>
                                        <div class="flex justify-between">
                                            <div class="flex justify-start space-x-2">
                                                <Button
                                                    icon="pi pi-download"
                                                    label=" Export Report"
                                                    unstyled
                                                    class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-orange-700 sm:w-auto"
                                                    @click="exportCSV('stamat')"
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
                                                    @select="(event) => handleCSVImport(event, 'stamat')"
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
                                                        @click="clearFilter('stamat')"
                                                    />

                                                    <IconField>
                                                        <InputIcon>
                                                            <i class="pi pi-search" />
                                                        </InputIcon>
                                                        <InputText v-model="filtersStamat['global'].value" placeholder="Keyword Search" />
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
                                        v-bind="tbStyle('rm')"
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
                                        sortable
                                        :showFilterMenu="false"
                                        v-bind="tbStyle('rm')"
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
                                        field="item_group"
                                        header="Group"
                                        sortable
                                        v-bind="tbStyle('rm')"
                                        :showFilterMenu="false"
                                    >
                                        <template #filter="{ filterModel, filterCallback }">
                                            <div class="flex justify-center">
                                                <Select
                                                    v-model="filterModel.value"
                                                    :options="listMaterialGroup"
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

                                    <Column
                                        resizableColumns
                                        columnResizeMode="fit"
                                        field="price"
                                        header="Standard Price"
                                        sortable
                                        v-bind="tbStyle('rm')"
                                    >
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.price) }}
                                        </template>
                                    </Column>

                                    <!-- <Column
                                        resizableColumns
                                        columnResizeMode="fit"
                                        field="created_at_formatted"
                                        header="Added at"
                                        v-bind="tbStyle('rm')"
                                    >
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.created_at) }}
                                        </template>
                                    </Column> -->

                                    <Column
                                        resizableColumns
                                        columnResizeMode="fit"
                                        field="updated_at_formatted"
                                        header="Updated at"
                                        v-bind="tbStyle('rm')"
                                    >
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.updated_at) }}
                                        </template>
                                    </Column>

                                    <Column
                                        resizableColumns
                                        columnResizeMode="fit"
                                        field="action"
                                        header="Action"
                                        :exportable="false"
                                        v-bind="tbStyle('rm')"
                                        ><template #body="slotProps">
                                            <div class="flex gap-2">
                                                <Button
                                                    icon="pi pi-pencil"
                                                    severity="warning"
                                                    rounded
                                                    text
                                                    @click="editData(slotProps.data, 'stamat')"
                                                />
                                                <Button
                                                    icon="pi pi-trash"
                                                    severity="danger"
                                                    rounded
                                                    text
                                                    @click="destroyData(slotProps.data, 'stamat')"
                                                />
                                            </div> </template
                                    ></Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="1">
                            <section ref="bom" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 md:mb-0 dark:text-white">Standard Bill of Material</h2>

                                    <div class="text-right text-gray-700 dark:text-gray-300">
                                        <!-- <div>
                                            Last Update :
                                            <span class="text-red-300">{{ lastUpdate[0] ? formatlastUpdate(lastUpdate[0]) : '-' }}</span>
                                        </div> -->
                                        <div>
                                            Data source From :
                                            <span class="text-cyan-400">{{ dataSource[1] }}</span>
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
                                                <!-- <Button
                                                    icon="pi pi-download"
                                                    label=" Export Report"
                                                    unstyled
                                                    class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-orange-700 sm:w-auto"
                                                    @click="exportCSV('bom')"
                                                /> -->
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

                        <TabPanel value="2">
                            <section ref="consumable" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 md:mb-0 dark:text-white">Standard Consumable Price</h2>

                                    <div class="text-right text-gray-700 dark:text-gray-300">
                                        <!-- <div>
                                            Last Update :
                                            <span class="text-red-300">{{ lastUpdate[0] ? formatlastUpdate(lastUpdate[0]) : '-' }}</span>
                                        </div> -->
                                        <div>
                                            Data source From :
                                            <span class="text-cyan-400">{{ dataSource[2] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <DataTable
                                    :value="paginatedConsumableData?.data || []"
                                    :lazy="true"
                                    :totalRecords="paginatedConsumableData?.total || 0"
                                    :rows="perPage"
                                    @page="onLazyLoadConsumable"
                                    @sort="onLazyLoadConsumable"
                                    :first="(currentConsumablePage - 1) * perPage"
                                    :paginator="true"
                                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                                    currentPageReportTemplate="Showing {first} to {last} from {totalRecords} data"
                                    responsiveLayout="scroll"
                                    :globalFilterFields="['item_code', 'description.name']"
                                    showGridlines
                                    :removableSort="true"
                                    v-model:filters="filtersConsumable"
                                    filterDisplay="row"
                                    :loading="loadingStates.consumable || isInitialLoading"
                                    ref="dtConsumable"
                                >
                                    <template #header>
                                        <div class="flex justify-between">
                                            <div class="flex justify-start space-x-2">
                                                <!-- <Button
                                                    icon="pi pi-download"
                                                    label=" Export Report"
                                                    unstyled
                                                    class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-orange-700 sm:w-auto"
                                                    @click="exportCSV('consumable')"
                                                /> -->
                                                <FileUpload
                                                    v-if="auth?.user?.permissions?.includes('Update_MasterData')"
                                                    ref="fileUploaderBP"
                                                    mode="basic"
                                                    name="file"
                                                    :customUpload="true"
                                                    accept=".csv"
                                                    chooseLabel=" Import CSV"
                                                    chooseIcon="pi pi-upload"
                                                    @select="(event) => handleCSVImport(event, 'consumable')"
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
                                                        @click="clearFilter('consumable')"
                                                    />

                                                    <IconField>
                                                        <InputIcon>
                                                            <i class="pi pi-search" />
                                                        </InputIcon>
                                                        <InputText v-model="filtersConsumable['global'].value" placeholder="Keyword Search" />
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

                                    <Column
                                        resizableColumns
                                        columnResizeMode="fit"
                                        field="bom.description"
                                        header="Description"
                                        :showFilterMenu="false"
                                        v-bind="tbStyle('rm')"
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
                                        field="price"
                                        header="Standard Price"
                                        sortable
                                        v-bind="tbStyle('rm')"
                                    >
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.price) }}
                                        </template>
                                    </Column>

                                    <Column
                                        resizableColumns
                                        columnResizeMode="fit"
                                        field="action"
                                        header="Action"
                                        :exportable="false"
                                        v-bind="tbStyle('rm')"
                                        ><template #body="slotProps">
                                            <div class="flex gap-2">
                                                <Button
                                                    icon="pi pi-pencil"
                                                    severity="warning"
                                                    rounded
                                                    text
                                                    @click="editData(slotProps.data, 'consumable')"
                                                />
                                                <Button
                                                    icon="pi pi-trash"
                                                    severity="danger"
                                                    rounded
                                                    text
                                                    @click="destroyData(slotProps.data, 'consumable')"
                                                />
                                            </div> </template
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
