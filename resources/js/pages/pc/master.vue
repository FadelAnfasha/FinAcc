<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import FileUpload, { FileUploadUploaderEvent } from 'primevue/fileupload';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref } from 'vue';

const dt = ref();
const toast = useToast();

interface FlashMessage {
    success?: string;
    addedItems?: string[];
    updatedItems?: string[];
    invalidItems?: string[];
}
const page = usePage();
const flash = page.props.flash as FlashMessage;

const showImportResult = ref(false);
const addedItems = ref<string[]>([]);
const updatedItems = ref<string[]>([]);
const invalidItems = ref<string[]>([]);

const businessPartners = computed(() =>
    (page.props.businessPartners as any[]).map((bp, index) => ({
        ...bp,
        no: index + 1,
        created_at_formatted: formatDate(bp.created_at),
        updated_at_formatted: formatDate(bp.updated_at),
    })),
);

const headerStyle = { backgroundColor: '#758596', color: 'white' };

onMounted(() => {
    const flash = page.props.flash as {
        addedItems?: string[];
        updatedItems?: string[];
        invalidItems?: string[];
    };

    if (flash?.addedItems?.length || flash?.updatedItems?.length || flash?.invalidItems?.length) {
        addedItems.value = flash.addedItems ?? [];
        updatedItems.value = flash.updatedItems ?? [];
        invalidItems.value = flash.invalidItems ?? [];

        showImportResult.value = true;
    }
});

function handleCSVImport(event: FileUploadUploaderEvent) {
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

    router.post(route('bps.import'), formData, {
        preserveScroll: true,
    });
}

function exportCSV() {
    const table = dt.value;
    if (!table) return;

    const exportFilename = `business-partners-${new Date().toISOString().slice(0, 10)}.csv`;

    table.exportCSV({
        selectionOnly: false,
        filename: exportFilename,
    });
}

const showResultDialog = ref(false);

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
        <template>
            <!-- <Dialog v-model:visible="showResultDialog" header="Import Result" modal class="w-[40rem]">
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
            </Dialog> -->
        </template>
        <div class="p-6">
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

            <!-- Process Items Grid -->
            <section ref="bpSection" class="p-2">
                <h2 class="mb-4 text-3xl font-semibold hover:text-indigo-500">Business Partner</h2>
                <DataTable
                    :value="businessPartners"
                    tableStyle="min-width: 50rem"
                    paginator
                    :rows="10"
                    removableSort
                    class="text-sm"
                    filterDisplay="row"
                    ref="dt"
                >
                    <template #header>
                        <div class="flex items-center justify-between">
                            <!-- <Button icon="pi pi-upload" class="text-start" label="Import" @click="importCSV($event)" /> -->
                            <FileUpload
                                mode="basic"
                                name="file"
                                :auto="true"
                                :customUpload="true"
                                accept=".csv"
                                chooseLabel="Import CSV"
                                @uploader="handleCSVImport"
                            />

                            <Button icon="pi pi-download" class="text-end" label="Export" @click="exportCSV()" />
                        </div>
                    </template>
                    <Column field="bp_code" sortable header="BP Code" :headerStyle="headerStyle"></Column>
                    <Column field="bp_name" sortable header="BP Name" :headerStyle="headerStyle"></Column>
                    <Column field="created_at_formatted" sortable header="Added at" :headerStyle="headerStyle">
                        <template #body="slotProps">
                            {{ formatDate(slotProps.data.created_at) }}
                        </template>
                    </Column>

                    <Column field="updated_at_formatted" sortable header="Updated at" :headerStyle="headerStyle">
                        <template #body="slotProps">
                            {{ formatDate(slotProps.data.updated_at) }}
                        </template>
                    </Column>

                    <Column field="action" header="Action" :exportable="false" :headerStyle="headerStyle"
                        ><template #body="slotProps">
                            <div class="flex gap-2">
                                <Button icon="pi pi-pencil" severity="warning" rounded text @click="editBP(slotProps.data)" />
                                <Button icon="pi pi-trash" severity="danger" rounded text @click="deleteBP(slotProps.data)" />
                            </div> </template
                    ></Column>
                </DataTable>
            </section>

            <!-- <section ref="ctSection" class="p-2">
                <h2 class="mb-4 text-3xl font-semibold hover:text-indigo-500">Cycle Time</h2>
                <DataTable tableStyle="min-width: 50rem" paginator removableSort :row="10" class="text-sm" filterDisplay="row">
                    <Column field="no" sortable header="No" :headerStyle="{ backgroundColor: '#f58453', color: 'black' }"></Column>
                    <Column field="item_code" sortable header="Item Code" :headerStyle="{ backgroundColor: '#f58453', color: 'black' }"></Column>
                    <Column field="size" sortable header="Size" :headerStyle="{ backgroundColor: '#f58453', color: 'black' }"></Column>
                    <Column field="type" sortable header="Type" :headerStyle="{ backgroundColor: '#f58453', color: 'black' }"></Column>
                    <Column field="updated_at" sortable header="Updated at" :headerStyle="{ backgroundColor: '#f58453', color: 'black' }"></Column>
                    <Column field="action" sortable header="Action" :headerStyle="{ backgroundColor: '#f58453', color: 'black' }"></Column>
                </DataTable>
            </section>

            <section ref="sqSection" class="p-2">
                <h2 class="mb-4 text-3xl font-semibold hover:text-indigo-500">Sales Quantity</h2>
                <DataTable tableStyle="min-width: 50rem" paginator removableSort :row="10" class="text-sm" filterDisplay="row">
                    <Column field="no" sortable header="No" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                    <Column field="item_code" sortable header="Item Code" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                    <Column field="size" sortable header="Size" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                    <Column field="type" sortable header="Type" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                    <Column field="updated_at" sortable header="Updated at" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                    <Column field="action" sortable header="Action" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                </DataTable>
            </section>

            <section ref="wdSection" class="p-2">
                <h2 class="mb-4 text-3xl font-semibold hover:text-indigo-500">Wages Distribution</h2>
                <DataTable tableStyle="min-width: 50rem" paginator removableSort :row="10" class="text-sm" filterDisplay="row">
                    <Column field="no" sortable header="No" :headerStyle="{ backgroundColor: '#b66eff', color: 'black' }"></Column>
                    <Column field="item_code" sortable header="Item Code" :headerStyle="{ backgroundColor: '#b66eff', color: 'black' }"></Column>
                    <Column field="size" sortable header="Size" :headerStyle="{ backgroundColor: '#b66eff', color: 'black' }"></Column>
                    <Column field="type" sortable header="Type" :headerStyle="{ backgroundColor: '#b66eff', color: 'black' }"></Column>
                    <Column field="updated_at" sortable header="Updated at" :headerStyle="{ backgroundColor: '#b66eff', color: 'black' }"></Column>
                    <Column field="action" sortable header="Action" :headerStyle="{ backgroundColor: '#b66eff', color: 'black' }"></Column>
                </DataTable>
            </section> -->
        </div>
    </AppLayout>
</template>
