<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import axios from 'axios';
import dayjs from 'dayjs';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import FileUpload, { FileUploadUploaderEvent } from 'primevue/fileupload';
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
import { computed, nextTick, ref, Ref, watch } from 'vue';

const dtSTAMAT = ref();
const dtACMAT = ref();
const dtBOM = ref();
const dtPACK = ref();
const dtPROC = ref();
const toast = useToast();
const page = usePage();

const filters = ref({
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    description: { value: null, matchMode: FilterMatchMode.CONTAINS },
});
const loading = ref(false);

const standardMaterial = computed(() =>
    ((page.props.standardMaterial as any[]) ?? []).map((standardmat, index) => ({
        ...standardmat,
        no: index + 1,
        created_at_formatted: formatDate(standardmat.created_at),
        updated_at_formatted: formatDate(standardmat.updated_at),
    })),
);

const actualMaterial = computed(() =>
    ((page.props.actualMaterial as any[]) ?? []).map((actualmat, index) => ({
        ...actualmat,
        no: index + 1,
        created_at_formatted: formatDate(actualmat.created_at),
        updated_at_formatted: formatDate(actualmat.updated_at),
    })),
);

const billOfMaterials = computed(() =>
    ((page.props.billOfMaterials as any[]) ?? []).map((bom, index) => ({
        ...bom,
        no: index + 1,
        created_at_formatted: formatDate(bom.created_at),
        updated_at_formatted: formatDate(bom.updated_at),
    })),
);

const valves = computed(() =>
    ((page.props.valve as any[]) ?? []).map((valves, index) => ({
        ...valves,
        no: index + 1,
        created_at_formatted: formatDate(valves.created_at),
        updated_at_formatted: formatDate(valves.updated_at),
    })),
);

const type = computed(() => page.props.type as string | undefined);
const getDefaultActiveTab = (): string | null => {
    // Ambil nilai dari computed property 'type'
    const currentType = type.value;

    switch (currentType) {
        // Jika Controller mengirimkan type: 'billOfMaterials' atau 'bom'
        case 'billOfMaterials':
        case 'bom':
            return '0';

        // Jika Controller mengirimkan type: 'standardMaterial'
        case 'standardMaterial':
            return '1';

        // Jika Controller mengirimkan type: 'actualMaterial'
        case 'actualMaterial':
            return '2';

        default:
            // Jika 'type' tidak terdefinisi atau tidak cocok dengan kasus di atas
            return null;
    }
};

const activeTab = ref<string | null>(getDefaultActiveTab());
const userName = computed(() => page.props.auth?.user?.name ?? '');

interface ComponentItem {
    item_code: string;
    description: string;
    // Tambahkan field lain jika diperlukan
}

const lastUpdate = computed(() => {
    const stamat_update = ((page.props.standardMaterial as any[]) ?? []).map((stamat) => new Date(stamat.updated_at));
    const Max_stamatUpdate = stamat_update.length ? new Date(Math.max(...stamat_update.map((d) => d.getTime()))) : null;

    const acmat_update = ((page.props.standardMaterial as any[]) ?? []).map((acmat) => new Date(acmat.updated_at));
    const Max_acmatUpdate = acmat_update.length ? new Date(Math.max(...acmat_update.map((d) => d.getTime()))) : null;

    const valve_update = ((page.props.valve as any[]) ?? []).map((valve) => new Date(valve.updated_at));
    const Max_valveUpdate = valve_update.length ? new Date(Math.max(...valve_update.map((d) => d.getTime()))) : null;

    const bom_update = ((page.props.billOfMaterials as any[]) ?? []).map((bom) => new Date(bom.updated_at));
    const Max_bomUpdate = bom_update.length ? new Date(Math.max(...bom_update.map((d) => d.getTime()))) : null;

    return [Max_stamatUpdate, Max_acmatUpdate, Max_valveUpdate, Max_bomUpdate];
});

const dataSource = [
    'Share Others/Finacc/Bill of Material/Standard Material Price/standardMat_master.csv',
    'Share Others/Finacc/Bill of Material/Actual Material Price/actualMat_master.csv',
    'Share Others/Finacc/Bill of Material/Valve Price (VP)/vp_master.csv',
    'Share Others/Finacc/Bill of Material/Bill of Material (BOM)/bom_master.csv',
];

function formatlastUpdate(date: Date | string) {
    return dayjs(date).format('DD MMM YYYY HH:mm:ss');
}

const componentItems = ref((page.props.component as ComponentItem[]) ?? []);
const showComponent = ref(componentItems.value.length > 0);

interface FinishGood {
    item_code?: string;
    [key: string]: any;
}

const finishGood = ref<FinishGood>(page.props.finish_good ?? {});

watch(
    () => page.props.component,
    (newVal) => {
        componentItems.value = (newVal as ComponentItem[]) ?? [];
        showComponent.value = componentItems.value.length > 0;

        finishGood.value = page.props.finish_good ?? {};
    },
    { immediate: true },
);

function closeComponentDialog() {
    showComponent.value = false;
    componentItems.value = [];
    finishGood.value = {}; // ✅ kosongkan dengan objek kosong
}

const headerStyle = { backgroundColor: '#758596', color: 'white' };
const bodyStyle = { backgroundColor: '#c8cccc', color: 'black' };

const showDialog = ref(false);
const dialogWidth = ref('40rem');
const editType = ref<'stamat' | 'acmat' | 'pack' | 'proc' | 'valve' | null>(null);
const destroyType = ref<'stamat' | 'acmat' | 'pack' | 'proc' | 'bom' | 'valve' | null>(null);
const headerType = ref<any>({});
const editedData = ref<any>({});
const destroyedData = ref<any>({});

const groups = ref([
    { name: 'Raw Material', code: 'RAW MATERIAL' },
    { name: 'Sparepart & Tools', code: 'SPAREPARTS AND TOOLS' },
]);

const manufacturer = ref([
    { name: 'M TL', code: 'M TL' },
    { name: 'M Single', code: 'M Single' },
    { name: 'M Double', code: 'M Double' },
    { name: 'L WT', code: 'L WT' },
    { name: '- No Manufacturer -', code: '- No Manufacturer -' },
]);

const showImportDialog: Ref<boolean> = ref(false);
const importName = ref<any>({});
const selectedFile = ref<File | null>(null);
const importType = ref<'stamat' | 'acmat' | 'pack' | 'proc' | 'valve' | 'bom' | null>(null);
const notImported = ref(true);
const fileUploaderSTAMAT = ref<any>(null);
const fileUploaderACMAT = ref<any>(null);
const fileUploaderPACK = ref<any>(null);
const fileUploaderVALVE = ref<any>(null);
const fileUploaderPROC = ref<any>(null);
const fileUploaderBOM = ref<any>(null);
const uploadProgress = ref(0);
const isUploading = ref(false);

function handleCSVImport(event: FileUploadUploaderEvent, type: 'stamat' | 'acmat' | 'pack' | 'proc' | 'valve' | 'bom') {
    let file: File | undefined;

    if (Array.isArray(event.files)) {
        file = event.files[0];
    } else if (event.files instanceof File) {
        file = event.files;
    }

    if (!file) return;

    const expectedNames = {
        stamat: 'standardMat_master.csv',
        acmat: 'actualMat_master.csv',
        pack: 'pack_master.csv',
        proc: 'proc_master.csv',
        valve: 'valve_master.csv',
        bom: 'bom_master.csv',
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
            if (type === 'stamat') fileUploaderSTAMAT.value?.clear();
            if (type === 'acmat') fileUploaderACMAT.value?.clear();
            if (type === 'pack') fileUploaderPACK.value?.clear();
            if (type === 'valve') fileUploaderVALVE.value?.clear();
            if (type === 'proc') fileUploaderPROC.value?.clear();
            if (type === 'bom') fileUploaderBOM.value?.clear();
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

function cancelCSVimport(type: 'stamat' | 'acmat' | 'pack' | 'proc' | 'valve' | 'bom') {
    showImportDialog.value = false;
    selectedFile.value = null;

    nextTick(() => {
        if (type === 'stamat') fileUploaderSTAMAT.value?.clear();
        if (type === 'acmat') fileUploaderACMAT.value?.clear();
        if (type === 'pack') fileUploaderPACK.value?.clear();
        if (type === 'valve') fileUploaderVALVE.value?.clear();
        if (type === 'proc') fileUploaderPROC.value?.clear();
        if (type === 'bom') fileUploaderBOM.value?.clear();
    });
}

function confirmUpload(type: 'stamat' | 'acmat' | 'pack' | 'proc' | 'valve' | 'bom') {
    if (!selectedFile.value || !importType.value) return;

    const formData = new FormData();
    formData.append('file', selectedFile.value);

    const routes = {
        stamat: 'stamat.import',
        acmat: 'acmat.import',
        pack: 'pack.import',
        proc: 'proc.import',
        valve: 'valve.import',
        bom: 'bom.import',
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
            selectedFile.value = null;

            nextTick(() => {
                if (type === 'stamat') fileUploaderSTAMAT.value?.clear();
                if (type === 'acmat') fileUploaderACMAT.value?.clear();
                if (type === 'pack') fileUploaderPACK.value?.clear();
                if (type === 'valve') fileUploaderVALVE.value?.clear();
                if (type === 'proc') fileUploaderPROC.value?.clear();
                if (type === 'bom') fileUploaderBOM.value?.clear();
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
                if (type === 'stamat') fileUploaderSTAMAT.value?.clear();
                if (type === 'acmat') fileUploaderACMAT.value?.clear();
                if (type === 'pack') fileUploaderPACK.value?.clear();
                if (type === 'valve') fileUploaderVALVE.value?.clear();
                if (type === 'proc') fileUploaderPROC.value?.clear();
                if (type === 'bom') fileUploaderBOM.value?.clear();
            });
        },
    });
}

function resetImportState() {
    uploadProgress.value = 0;
    selectedFile.value = null;
    notImported.value = true;
}

function startPollingProgress(type: 'stamat' | 'acmat' | 'pack' | 'proc' | 'valve' | 'bom') {
    uploadProgress.value = 0;

    const endpointMap = {
        stamat: '/finacc/standardMat/import-progress',
        acmat: '/finacc/actualMat/import-progress',
        pack: '/finacc/pack/import-progress',
        proc: '/finacc/proc/import-progress',
        valve: '/finacc/valve/import-progress',
        bom: '/finacc/bom/import-progress',
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

function exportCSV(type: 'stamat' | 'acmat' | 'bom' | 'pack' | 'proc' | 'valve') {
    let $type = null;
    let $filename = null;
    if (type === 'stamat') {
        $type = dtSTAMAT.value;
        $filename = 'standard-mat';
    } else if (type === 'acmat') {
        $type = dtACMAT.value;
        $filename = 'actual-mat';
    } else if (type === 'pack') {
        $type = dtPACK.value;
        $filename = 'pack';
    } else if (type === 'proc') {
        $type = dtPROC.value;
        $filename = 'proc';
    } else if (type === 'bom') {
        $type = dtBOM.value;
        $filename = 'cycle-times';

        if (!$type) return;

        const exportFilename = `${$filename}-${new Date().toISOString().slice(0, 10)}.csv`;

        $type.exportCSV({
            selectionOnly: false,
            filename: exportFilename,
        });
    }
}

function formatDate(dateStr: string): string {
    const date = new Date(dateStr);
    const yy = String(date.getFullYear());
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yy}-${mm}-${dd}`;
}

function editData(data: any, type: 'stamat' | 'acmat' | 'pack' | 'proc' | 'valve') {
    editedData.value = { ...data };
    editType.value = type;
    headerType.value = 'Edit data';
    // Atur lebar berdasarkan type
    if (type === 'stamat') {
        dialogWidth.value = '40rem';
    } else if (type === 'acmat') {
        dialogWidth.value = '40rem';
    } else if (type === 'pack') {
        dialogWidth.value = '40rem';
    } else if (type === 'proc') {
        dialogWidth.value = '40rem';
    } else if (type === 'valve') {
        dialogWidth.value = '40rem';
    }
    showDialog.value = true;
}

function handleSave() {
    if (editType.value === 'stamat') {
        const item_code = editedData.value.item_code;
        if (!item_code) return;

        router.put(route('stamat.update', item_code), editedData.value, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    group: 'br',
                    detail: `Data ${editedData.value.item_code} updated successfully`,
                    life: 3000,
                });
                showDialog.value = false;
            },
            onError: () => {
                toast.add({
                    severity: 'warn',
                    summary: 'Error',
                    group: 'br',
                    detail: `Failed to delete data with ${editedData.value.item_code}`,
                    life: 3000,
                });
            },
        });
    } else if (editType.value === 'acmat') {
        const item_code = editedData.value.item_code;
        if (!item_code) return;

        router.put(route('acmat.update', item_code), editedData.value, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    group: 'br',
                    detail: `Data ${editedData.value.item_code} updated successfully`,
                    life: 3000,
                });
                showDialog.value = false;
            },
            onError: () => {
                toast.add({
                    severity: 'warn',
                    summary: 'Error',
                    group: 'br',
                    detail: `Failed to delete data with ${editedData.value.item_code}`,
                    life: 3000,
                });
            },
        });
    } else if (editType.value === 'pack') {
        const item_code = editedData.value.item_code;
        if (!item_code) return;

        router.put(route('pack.update', item_code), editedData.value, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    group: 'br',
                    detail: `Data ${editedData.value.item_code} updated successfully`,
                    life: 3000,
                });
                showDialog.value = false;
            },
            onError: () => {
                toast.add({
                    severity: 'warn',
                    summary: 'Error',
                    group: 'br',
                    detail: `Failed to delete data with ${editedData.value.item_code}`,
                    life: 3000,
                });
            },
        });
    } else if (editType.value === 'proc') {
        const item_code = editedData.value.item_code;
        if (!item_code) return;

        router.put(route('proc.update', item_code), editedData.value, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    group: 'br',
                    detail: `Data ${editedData.value.item_code} updated successfully`,
                    life: 3000,
                });
                showDialog.value = false;
            },
            onError: () => {
                toast.add({
                    severity: 'warn',
                    summary: 'Error',
                    group: 'br',
                    detail: `Failed to delete data with ${editedData.value.item_code}`,
                    life: 3000,
                });
            },
        });
    } else if (editType.value === 'valve') {
        const item_code = editedData.value.item_code;
        if (!item_code) return;

        router.put(route('valve.update', item_code), editedData.value, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    group: 'br',
                    detail: `Data ${editedData.value.item_code} updated successfully`,
                    life: 3000,
                });
                showDialog.value = false;
            },
            onError: () => {
                toast.add({
                    severity: 'warn',
                    summary: 'Error',
                    group: 'br',
                    detail: `Failed to delete data with ${editedData.value.item_code}`,
                    life: 3000,
                });
            },
        });
    }
}

function destroyData(data: any, type: 'stamat' | 'acmat' | 'pack' | 'proc' | 'bom' | 'valve') {
    destroyedData.value = { ...data };
    destroyType.value = type;
    headerType.value = 'Delete data';
    if (type === 'stamat') {
        dialogWidth.value = '40rem';
    } else if (type === 'acmat') {
        dialogWidth.value = '40rem';
    } else if (type === 'pack') {
        dialogWidth.value = '40rem';
    }
    if (type === 'proc') {
        dialogWidth.value = '40rem';
    }

    showDialog.value = true;
}

function handleDestroy() {
    if (destroyType.value === 'stamat') {
        const item_code = destroyedData.value.item_code;
        if (!item_code) return;

        router.delete(route('stamat.destroy', item_code), {
            data: destroyedData.value,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Success',
                    detail: `Data ${destroyedData.value.item_code} deleted successfully`,
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
                    detail: `Failed to delete this  ${destroyedData.value.item_code} data`,
                    life: 3000,
                });
            },
        });
    }
    if (destroyType.value === 'acmat') {
        const item_code = destroyedData.value.item_code;
        if (!item_code) return;

        router.delete(route('acmat.destroy', item_code), {
            data: destroyedData.value,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Success',
                    detail: `Data ${destroyedData.value.item_code} deleted successfully`,
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
                    detail: `Failed to delete this  ${destroyedData.value.item_code} data`,
                    life: 3000,
                });
            },
        });
    } else if (destroyType.value === 'pack') {
        const item_code = destroyedData.value.item_code;
        if (!item_code) return;

        router.delete(route('pack.destroy', item_code), {
            data: destroyedData.value,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Success',
                    detail: `Data ${destroyedData.value.item_code} deleted successfully`,
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
                    detail: `Failed to delete this  ${destroyedData.value.item_code} data`,
                    life: 3000,
                });
            },
        });
    } else if (destroyType.value === 'proc') {
        const item_code = destroyedData.value.item_code;
        if (!item_code) return;

        router.delete(route('proc.destroy', item_code), {
            data: destroyedData.value,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Success',
                    detail: `Data ${destroyedData.value.item_code} deleted successfully`,
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
                    detail: `Failed to delete this  ${destroyedData.value.item_code} data`,
                    life: 3000,
                });
            },
        });
    } else if (destroyType.value === 'valve') {
        const item_code = destroyedData.value.item_code;
        if (!item_code) return;

        router.delete(route('valve.destroy', item_code), {
            data: destroyedData.value,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Success',
                    detail: `Data ${destroyedData.value.item_code} deleted successfully`,
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
                    detail: `Failed to delete this  ${destroyedData.value.item_code} data`,
                    life: 3000,
                });
            },
        });
    }
}

function viewComponents(bom: any) {
    // buka dialog untuk melihat komponen BOM
    router.get(
        route('bom.components', bom.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
}

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};
interface ImportResult {
    addedItems: string[];
    invalidItems: { item_code: string; price: string; description: string; reason: string }[];
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
        item_code: item, // Menyimpan nilai string bp_code ke properti bp_code
    }));

    // Perbaikan untuk invalidItems:
    // Setiap item adalah objek { 'bp_code': '...', 'reason': '...' }
    // Kita perlu mengambil properti bp_code dan reason dari setiap objek
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
</script>

<template>
    <Toast group="br" position="bottom-right" />

    <Head title="Bill of Material" />
    <AppLayout>
        <div class="m-6">
            <div class="flex flex-col gap-1">
                <h2 class="mb-2 text-start text-3xl font-bold text-gray-900 dark:text-white">Bill of Material</h2>
                <p class="text-start text-gray-600 dark:text-gray-400">Calculating Bill of Material</p>
            </div>

            <!-- Header Section -->
            <div class="mt-4 mb-8">
                <div class="relative mb-6 text-center">
                    <h1 class="relative z-10 inline-block bg-white px-4 text-2xl font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
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
                                        <th
                                            v-if="importType === 'stamat' || importType === 'valve'"
                                            class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400"
                                        >
                                            Item Code
                                        </th>
                                        <th v-if="importType === 'stamat'" class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">
                                            Description
                                        </th>
                                        <th v-if="importType === 'valve'" class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">
                                            Price
                                        </th>

                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Menggunakan template v-if dan v-for -->
                                    <template v-if="importResult.invalidItems.length > 0">
                                        <tr v-for="item in importResult.invalidItems" :key="item.no">
                                            <td
                                                v-if="importType === 'stamat' || 'acmat' || importType === 'valve'"
                                                class="border-b border-gray-800 px-4 py-2"
                                            >
                                                {{ item.item_code }}
                                            </td>
                                            <td v-if="importType === 'stamat' || 'acmat'" class="border-b border-gray-800 px-4 py-2">
                                                {{ item.description }}
                                            </td>
                                            <td v-if="importType === 'valve'" class="border-b border-gray-800 px-4 py-2">
                                                {{ item.price }}
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

                <Dialog v-model:visible="showComponent" :header="`Components of ${finishGood.item_code}`" modal class="w-[60rem]" :closable="false">
                    <DataTable :value="componentItems" responsiveLayout="scroll">
                        <div class="mr-2 mb-2 flex justify-end">
                            <Button label="Close" severity="warn" @click="closeComponentDialog" />
                        </div>
                        <Column header="#" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                            <template #body="{ index }">
                                {{ index + 1 }}
                            </template>
                        </Column>
                        <Column field="item_code" header="Item Code" style="white-space: nowrap" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="description" header="Description" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="uom" header="Unit of Material" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="quantity" header="Quantity" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="warehouse" header="Warehouse" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="depth" header="Depth" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="bom_type" header="Bom Type" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                    </DataTable>
                </Dialog>

                <Dialog v-model:visible="showDialog" :header="headerType" modal :style="{ width: dialogWidth }" :closable="false">
                    <div v-if="editType === 'stamat' || editType === 'acmat'" class="space-y-6">
                        <div class="mb-4 flex items-center gap-4">
                            <label for="item_code" class="w-24 font-semibold">Material Code</label>
                            <InputText id="item_code" class="flex-auto" v-model="editedData.item_code" autocomplete="off" :disabled="true" />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="description" class="w-24 font-semibold">Description</label>
                            <InputText
                                id="description"
                                class="flex-auto"
                                :value="editedData.bom?.description || '-'"
                                autocomplete="off"
                                :disabled="true"
                            />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="in_stock" class="w-24 font-semibold">In Stock</label>
                            <InputNumber
                                id="in_stock"
                                class="flex-auto"
                                inputId="integeronly"
                                :min="0"
                                v-model="editedData.in_stock"
                                autocomplete="off"
                            />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="item_group" class="w-24 font-semibold">Group</label>
                            <Select v-model="editedData.item_group" :options="groups" optionLabel="name" optionValue="code" class="w-full md:w-56" />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="price" class="w-24 font-semibold">Price</label>
                            <InputNumber
                                id="price"
                                class="flex-auto"
                                inputId="currency-indonesia"
                                mode="currency"
                                currency="IDR"
                                locale="id-ID"
                                :maxFractionDigits="2"
                                v-model="editedData.price"
                                autocomplete="off"
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

                    <div v-if="editType === 'pack'" class="space-y-6">
                        <div class="mb-4 flex items-center gap-4">
                            <label for="item_code" class="w-24 font-semibold">Item Code</label>
                            <InputText id="item_code" class="flex-auto" v-model="editedData.item_code" autocomplete="off" :disabled="true" />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="price" class="w-24 font-semibold">Price</label>
                            <InputNumber
                                id="price"
                                class="flex-auto"
                                inputId="currency-indonesia"
                                mode="currency"
                                currency="IDR"
                                locale="id-ID"
                                :maxFractionDigits="2"
                                v-model="editedData.price"
                                autocomplete="off"
                                :min="0"
                            />
                        </div>

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

                    <div v-if="editType === 'valve'" class="space-y-6">
                        <div class="mb-4 flex items-center gap-4">
                            <label for="item_code" class="w-24 font-semibold">Item Code</label>
                            <InputText id="item_code" class="flex-auto" v-model="editedData.item_code" autocomplete="off" :disabled="true" />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="price" class="w-24 font-semibold">Price</label>
                            <InputNumber
                                id="price"
                                class="flex-auto"
                                inputId="currency-indonesia"
                                mode="currency"
                                currency="IDR"
                                locale="id-ID"
                                :maxFractionDigits="2"
                                v-model="editedData.price"
                                autocomplete="off"
                                :min="0"
                            />
                        </div>

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

                    <div v-if="editType === 'proc'" class="space-y-6">
                        <div class="mb-4 flex items-center gap-4">
                            <label for="item_code" class="w-24 font-semibold">Item Code</label>
                            <InputText id="item_code" class="flex-auto" v-model="editedData.item_code" autocomplete="off" :disabled="true" />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="description" class="w-24 font-semibold">Description</label>
                            <InputText id="description" class="flex-auto" v-model="editedData.description" autocomplete="off" :disabled="true" />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="price" class="w-24 font-semibold">Price</label>
                            <InputNumber
                                id="price"
                                class="flex-auto"
                                inputId="currency-indonesia"
                                mode="currency"
                                currency="IDR"
                                locale="id-ID"
                                :maxFractionDigits="2"
                                v-model="editedData.price"
                                autocomplete="off"
                                :min="0"
                            />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="manufacturer" class="w-24 font-semibold">Manufacturer</label>
                            <Select
                                v-model="editedData.manufacturer"
                                :options="manufacturer"
                                optionLabel="name"
                                optionValue="code"
                                class="w-full md:w-56"
                            />
                        </div>

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

                    <div v-if="destroyType === 'stamat' || destroyType === 'acmat'" class="space-y-6">
                        <span>
                            Are you sure want to delete Material data with item code
                            <span class="font-semibold text-red-600">{{ destroyedData.item_code }} </span> and description
                            <span class="font-semibold text-red-600">{{ destroyedData.bom?.description || '-' }}</span> ?
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

                    <div v-if="destroyType === 'pack'" class="space-y-6">
                        <span>
                            Are you sure want to delete Packing data with item code
                            <span class="font-semibold text-red-600">{{ destroyedData.item_code }} </span> and description
                            <span class="font-semibold text-red-600">{{ destroyedData.bom?.description || '-' }}</span> ?
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

                    <div v-if="destroyType === 'valve'" class="space-y-6">
                        <span>
                            Are you sure want to delete Valve data with item code
                            <span class="font-semibold text-red-600">{{ destroyedData.item_code }} </span> and description
                            <span class="font-semibold text-red-600">{{ destroyedData.bom?.description || '-' }}</span> ?
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

                    <div v-if="destroyType === 'proc'" class="space-y-6">
                        <span>
                            Are you sure want to delete Process data with item code
                            <span class="font-semibold text-red-600">{{ destroyedData.item_code }} </span> and description
                            <span class="font-semibold text-red-600">{{ destroyedData.description || '-' }}</span> ?
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
                <Tabs :value="activeTab ?? '0'">
                    <TabList>
                        <Tab v-if="type === 'bom'" value="0">Bill of Material</Tab>
                        <Tab v-if="type === 'standardMaterial'" value="1">Standard Material Price</Tab>
                        <Tab v-if="type === 'actualMaterial'" value="2">Actual Material Price</Tab>
                        <Tab v-if="type === 'bom'" value="3">Valve</Tab>
                    </TabList>

                    <TabPanels>
                        <TabPanel v-if="type === 'bom'" value="0">
                            <section ref="bomSection" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 md:mb-0 dark:text-white">Bill of Material</h2>

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
                                            @select="(event) => handleCSVImport(event, 'bom')"
                                            class="w-full sm:w-auto"
                                        />
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
                                <DataTable
                                    :value="billOfMaterials"
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
                                    :globalFilterFields="['item_code', 'description']"
                                    class="text-md"
                                    ref="dtBOM"
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
                                                placeholder="Search item code"
                                                class="w-full"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="description"
                                        header="Name"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search description"
                                                class="w-full"
                                            />
                                        </template>
                                        <template #body="{ data }">
                                            {{ data ? data.description : 'N/A' }}
                                        </template>
                                    </Column>
                                    <Column field="uom" header="Unit of Material" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="quantity" header="Quantity" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="warehouse" header="Warehouse" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="depth" header="Depth" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="bom_type" header="BOM Type" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

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
                                                <Button
                                                    v-tooltip="'View Component'"
                                                    icon="pi pi-eye"
                                                    severity="info"
                                                    rounded
                                                    text
                                                    @click="viewComponents(slotProps.data)"
                                                />
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel v-if="type === 'standardMaterial'" value="1">
                            <section ref="matSection" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 md:mb-0 dark:text-white">Standard Material Price</h2>

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
                                            @select="(event) => handleCSVImport(event, 'stamat')"
                                            class="w-full sm:w-auto"
                                        />

                                        <div class="flex w-full flex-col items-center gap-4 sm:w-auto sm:flex-row">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 sm:w-28"
                                                @click="exportCSV('stamat')"
                                            />
                                        </div>
                                    </div>

                                    <div class="text-right text-gray-700 dark:text-gray-300">
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
                                    :value="standardMaterial"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    :rowsPerPageOptions="[10, 20, 50, 100]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filters"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code']"
                                    class="text-md"
                                    ref="dtMAT"
                                >
                                    <Column
                                        field="item_code"
                                        header="Material Code"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search item code"
                                                class="w-full"
                                            /> </template
                                    ></Column>

                                    <Column field="description" sortable header="Description" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ data?.bom?.description ?? '-' }}
                                        </template>
                                    </Column>

                                    <Column field="in_stock" sortable header="In Stock" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>
                                    <Column field="item_group" sortable header="Group" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>

                                    <Column field="price" header="Standard Price" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.price) }}
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

                                    <Column field="action" header="Action" :exportable="false" :headerStyle="headerStyle" :bodyStyle="bodyStyle"
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

                        <TabPanel v-if="type === 'actualMaterial'" value="2">
                            <section ref="matSection" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 md:mb-0 dark:text-white">Actual Material Price</h2>

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
                                            @select="(event) => handleCSVImport(event, 'acmat')"
                                            class="w-full sm:w-auto"
                                        />

                                        <div class="flex w-full flex-col items-center gap-4 sm:w-auto sm:flex-row">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 sm:w-28"
                                                @click="exportCSV('acmat')"
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
                                    :value="actualMaterial"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    :rowsPerPageOptions="[10, 20, 50, 100]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filters"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code']"
                                    class="text-md"
                                    ref="dtMAT"
                                >
                                    <Column
                                        field="item_code"
                                        header="Material Code"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search item code"
                                                class="w-full"
                                            /> </template
                                    ></Column>
                                    <Column field="description" sortable header="Description" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ data?.bom?.description ?? '-' }}
                                        </template>
                                    </Column>

                                    <Column field="price" header="Actual Price" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.price) }}
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

                        <TabPanel v-if="type === 'bom'" value="3">
                            <section ref="packSection" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 md:mb-0 dark:text-white">Valve Price</h2>

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
                                            @select="(event) => handleCSVImport(event, 'valve')"
                                            class="w-full sm:w-auto"
                                        />

                                        <div class="flex w-full flex-col items-center gap-4 sm:w-auto sm:flex-row">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 sm:w-28"
                                                @click="exportCSV('valve')"
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
                                    :value="valves"
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
                                    :globalFilterFields="['item_code']"
                                    class="text-md"
                                    ref="dtVP"
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
                                                placeholder="Search item code"
                                                class="w-full"
                                            /> </template
                                    ></Column>

                                    <Column field="price" header="Price" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.price) }}
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

                                    <Column field="action" header="Action" :exportable="false" :headerStyle="headerStyle" :bodyStyle="bodyStyle"
                                        ><template #body="slotProps">
                                            <div class="flex gap-2">
                                                <Button
                                                    icon="pi pi-pencil"
                                                    severity="warning"
                                                    rounded
                                                    text
                                                    @click="editData(slotProps.data, 'valve')"
                                                />
                                                <Button
                                                    icon="pi pi-trash"
                                                    severity="danger"
                                                    rounded
                                                    text
                                                    @click="destroyData(slotProps.data, 'valve')"
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
