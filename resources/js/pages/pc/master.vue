<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import FileUpload, { FileUploadUploaderEvent } from 'primevue/fileupload';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';

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
    })),
);

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

    const exportFilename = `business-partners-${new Date().toISOString().slice(0, 10)}.csv`;

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
        <div class="m-32">
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

                    <Column field="updated_at_formatted" sortable header="Updated at" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
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

            <section ref="ctSection" class="p-2">
                <h2 class="mb-4 text-3xl font-semibold hover:text-indigo-500">Cycle Time</h2>
                <DataTable :value="cycleTimes" tableStyle="500px" paginator removableSort class="text-md" filterDisplay="header" ref="dtCT">
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

                    <Column header="C3/SN" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                        <template #body="slotProps">
                            {{ slotProps.data['C3/SN'] }}
                        </template>
                    </Column>

                    <Column header="C3/SN Eff" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                        <template #body="slotProps">
                            {{ slotProps.data['C3/SN_eff'] }}
                        </template>
                    </Column>

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

                    <Column field="action" header="Action" :exportable="false" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                        <template #body="slotProps">
                            <div class="flex gap-2">
                                <Button icon="pi pi-pencil" severity="warning" rounded text @click="editBP(slotProps.data)" />
                                <Button icon="pi pi-trash" severity="danger" rounded text @click="deleteBP(slotProps.data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
                <pre>{{ cycleTimes[0] }}</pre>
            </section>

            <!-- <section ref="sqSection" class="p-2">
                <h2 class="mb-4 text-3xl font-semibold hover:text-indigo-500">Sales Quantity</h2>
                <DataTable tableStyle="min-width: 50rem" paginator removableSort :row="10" class="text-sm" filterDisplay="row">
                    <Column field="no" sortable header="No" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                    <Column field="item_code" sortable header="Item Code" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                    <Column field="size" sortable header="Size" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                    <Column field="type" sortable header="Type" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                    <Column field="updated_at" sortable header="Updated at" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                    <Column field="action" sortable header="Action" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                </DataTable>
            </section> -->

            <!-- <section ref="wdSection" class="p-2">
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
