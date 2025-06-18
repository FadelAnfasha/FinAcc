<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import { useToast } from 'primevue/usetoast';
import { computed, ref, watchEffect } from 'vue';

const page = usePage();
const toast = useToast();
const flash = page.props.flash as { success?: string };

watchEffect(() => {
    const flash = page.props.flash as { success?: string } | undefined;

    if (flash?.success) {
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: flash.success,
            life: 3000,
        });
    }
});

// Define proper types
interface RequestItem {
    id: number;
    name: string;
    priority: string;
    input_date: string;
    description: string;
    status: string;
    attachment: File;
}

const acceptRequest = (id: number) => {
    router.post(`/rfs/${id}/accept`);
};

const rejectRequest = (id: number) => {
    router.post(`/rfs/${id}/reject`);
};

const executeRequest = (id: number) => {
    router.post(`/rfs/${id}/execute`);
};

const finishRequest = (id: number) => {
    router.post(`/rfs/${id}/finish`);
};

const props = defineProps({
    services: Array,
    auth: Object,
});

const filters = ref({
    name: { value: null, matchMode: 'contains' },
    priority: { value: null, matchMode: 'equals' },
    input_date: { value: null, matchMode: 'contains' },
    description: { value: null, matchMode: 'contains' },
    status: { value: null, matchMode: 'equals' },
});

// Data with proper typing and added status field
const items = computed(() => page.props.services as RequestItem[]);

const priorities = ['All', 'low', 'medium', 'high', 'urgent'];
const statuses = ['All', 'wait_for_review', 'accepted', 'in_progress', 'finish', 'rejected'];

function getPriorityClass(priority: string): string | undefined {
    switch (priority) {
        case 'All':
            return 'secondary';
        case 'low':
            return 'bg-purple-400 text-purple-800';
        case 'medium':
            return 'bg-blue-300 text-blue-800';
        case 'high':
            return 'bg-orange-400 text-orange-800';
        case 'urgent':
            return 'bg-red-400 text-red-800';
        case 'All':
            return 'bg-gray-200 text-red-800';
        default:
            return undefined;
    }
}

function getStatusClass(status: string): string {
    switch (status) {
        case 'wait_for_review':
            return 'bg-yellow-300 text-yellow-800';
        case 'accepted':
            return 'bg-blue-400 text-blue-800';
        case 'in_progress':
            return 'bg-purple-300 text-purple-800';
        case 'finish':
            return 'bg-green-300 text-green-800';
        case 'rejected':
            return 'bg-red-400 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

function statusLabel(status: string): string {
    const map: Record<string, string> = {
        wait_for_review: 'Wait for Review',
        accepted: 'Accepted',
        in_progress: 'In Progress',
        finish: 'Finish',
        rejected: 'Rejected',
    };
    return map[status] || status;
}

function capitalize(text: string): string {
    return text.charAt(0).toUpperCase() + text.slice(1);
}

// Type assertion for user with npk property
interface UserWithNPK {
    name?: string;
    npk?: string;
    [key: string]: any;
}

// Form data with proper typing
const form = useForm({
    name: (page.props.auth.user as UserWithNPK)?.name || '',
    npk: (page.props.auth.user as UserWithNPK)?.npk || '',
    priority: 'medium',
    input_date: new Date().toISOString().split('T')[0],
    description: '',
    status: 'wait_for_review',
    attachment: null as File | null,
});

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFileName = ref('');
const loading = ref(false);

// Ref for create form section
const createFormSection = ref<HTMLElement | null>(null);
const dataSection = ref<HTMLElement | null>(null);

// Auto scroll to create form
const scrollToCreateForm = () => {
    if (createFormSection.value) {
        createFormSection.value.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    }
};

const scrollTodataSection = () => {
    if (dataSection.value) {
        dataSection.value.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    }
};

// Handle file selection with proper typing
const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.attachment = file;
        selectedFileName.value = file.name;
    }
};

// Submit form
const submitForm = () => {
    form.post('/rfs', {
        forceFormData: true, // WAJIB untuk upload file
        onStart: () => {
            form.clearErrors();
        },
        onSuccess: () => {
            form.reset();
            selectedFileName.value = '';
            if (fileInput.value) {
                fileInput.value = null;
            }
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
        },
    });
};

// Reset form
const resetForm = () => {
    form.reset();
    selectedFileName.value = '';
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};
</script>

<template>
    <Head title="RFS" />
    <AppLayout>
        <!-- Data Table Section -->
        <section ref="dataSection" class="mx-24 my-4">
            <div class="mb-8 flex items-center justify-between">
                <h2 class="text-3xl font-semibold">Request Data</h2>
                <Button
                    label="Create"
                    severity="info"
                    v-if="auth?.user?.role === 'User'"
                    icon="pi pi-plus"
                    variant="outlined"
                    rounded
                    @click="scrollToCreateForm"
                />
            </div>

            <DataTable
                v-model:filters="filters"
                :value="items"
                :customSort="true"
                removableSort
                paginator
                :rows="10"
                dataKey="id"
                filterDisplay="row"
                :loading="loading"
                :globalFilterFields="['name', 'priority', 'input_date', 'description', 'status']"
                tableStyle="min-width: 50rem"
                class="text-sm"
                sortField="input_date"
                :sortOrder="-1"
            >
                <Column field="name" header="Name" :showFilterMenu="false" sortable style="width: 20%">
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText v-model="filterModel.value" @input="filterCallback()" placeholder="Search name" />
                    </template>
                </Column>

                <Column field="priority" header="Priority" :sortable="true" :showFilterMenu="false" style="width: 20%" class="justify-items-center">
                    <!-- Body Badge -->
                    <template #body="{ data }">
                        <span
                            :class="getPriorityClass(data.priority)"
                            class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                        >
                            {{ capitalize(data.priority) }}
                        </span>
                    </template>

                    <!-- Filter -->
                    <template #filter="{ filterModel, filterCallback }">
                        <div class="flex justify-center">
                            <Select
                                v-model="filterModel.value"
                                :options="priorities"
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
                                    <span v-if="!value || value === 'All'" class="w-full text-center text-gray-500"> Select priority </span>
                                    <span
                                        v-else
                                        :class="getPriorityClass(value)"
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
                                        :class="getPriorityClass(option)"
                                        class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                    >
                                        {{ capitalize(option) }}
                                    </span>
                                </template>
                            </Select>
                        </div>
                    </template>
                </Column>

                <Column field="input_date" header="Request Date" :showFilterMenu="false" sortable style="width: 20%">
                    <template #filter="{ filterModel, filterCallback }">
                        <DatePicker
                            :modelValue="filterModel.value ? new Date(filterModel.value + 'T00:00:00') : null"
                            @update:modelValue="
                                (val) => {
                                    if (val instanceof Date) {
                                        const formatted =
                                            val.getFullYear() +
                                            '-' +
                                            String(val.getMonth() + 1).padStart(2, '0') +
                                            '-' +
                                            String(val.getDate()).padStart(2, '0');
                                        filterModel.value = formatted;
                                    } else {
                                        filterModel.value = null;
                                    }
                                    filterCallback();
                                    console.log('modelValue bound to DatePicker:', filterModel.value, typeof filterModel.value);
                                }
                            "
                            dateFormat="yy-mm-dd"
                            placeholder="yy-mm-dd"
                            :maxDate="new Date()"
                            showIcon
                        />
                    </template>
                </Column>

                <Column field="description" header="Req. Description" :showFilterMenu="false" sortable style="width: 20%">
                    <template #body="{ data }">
                        <div class="max-w-[200px] truncate" :title="data.description">
                            {{ data.description }}
                        </div>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText v-model="filterModel.value" @input="filterCallback()" placeholder="Search desc" />
                    </template>
                </Column>

                <!-- Status Column -->
                <Column field="status" header="Status" :showFilterMenu="false" sortable style="width: 20%">
                    <template #body="{ data }">
                        <span
                            :class="getStatusClass(data.status)"
                            class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                        >
                            {{ statusLabel(data.status) }}
                        </span>
                    </template>

                    <template #filter="{ filterModel, filterCallback }">
                        <Select
                            v-model="filterModel.value"
                            :options="statuses"
                            placeholder="Select status"
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
                            <!-- Tampilan nilai yang dipilih -->
                            <template #value="{ value }">
                                <span v-if="!value || value === 'All'" class="w-full text-center text-gray-500"> Select status </span>
                                <span
                                    v-else
                                    :class="getStatusClass(value)"
                                    class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                >
                                    {{ statusLabel(value) }}
                                </span>
                            </template>

                            <!-- Tampilan opsi dropdown -->
                            <template #option="{ option }">
                                <span
                                    v-if="option === 'All'"
                                    class="inline-block w-full rounded-full bg-gray-100 px-2 py-1 text-center text-xs font-semibold text-gray-800"
                                >
                                    All
                                </span>
                                <span
                                    v-else
                                    :class="getStatusClass(option)"
                                    class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                >
                                    {{ statusLabel(option) }}
                                </span>
                            </template>
                        </Select>
                    </template>
                </Column>

                <Column field="attachment" header="Attachment" :showFilterMenu="false" style="width: 20%">
                    <template #body="{ data }">
                        <a
                            v-if="data.attachment"
                            :href="`/storage/${data.attachment}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 rounded bg-blue-500 px-3 py-1 text-xs font-semibold text-white hover:bg-blue-600"
                        >
                            <i class="pi pi-external-link" /> View
                        </a>
                        <span v-else class="text-xs text-gray-400">No Attachment</span>
                    </template>
                </Column>

                <Column header="Action" v-if="auth?.user?.role === 'Reviewer' || auth?.user?.role === 'Admin'" style="width: 20%">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <!-- Reviewer actions -->
                            <template v-if="auth?.user?.role === 'Reviewer'">
                                <button
                                    v-if="data.status === 'wait_for_review'"
                                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-green-400 px-3 py-1 text-xs font-semibold text-black hover:bg-green-600 hover:text-white"
                                    @click="acceptRequest(data.id)"
                                >
                                    <i class="pi pi-check" /> Accept
                                </button>

                                <button
                                    v-if="data.status === 'wait_for_review'"
                                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-red-400 px-3 py-1 text-xs font-semibold text-black hover:bg-red-600 hover:text-white"
                                    @click="rejectRequest(data.id)"
                                >
                                    <i class="pi pi-times" /> Reject
                                </button>
                                <i class="pi pi-spin pi-cog" style="color: orange" v-if="data.status === 'in_progress'"></i>
                            </template>

                            <!-- Admin actions -->
                            <template v-if="auth?.user?.role === 'Admin'">
                                <button
                                    v-if="data.status === 'accepted'"
                                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-orange-400 px-3 py-1 text-xs font-semibold text-black hover:bg-orange-600 hover:text-white"
                                    @click="executeRequest(data.id)"
                                >
                                    <i class="pi pi-cog pi-spin" /> Execute
                                </button>
                                <button
                                    v-if="data.status === 'in_progress'"
                                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-orange-400 px-3 py-1 text-xs font-semibold text-black hover:bg-orange-600 hover:text-white"
                                    @click="finishRequest(data.id)"
                                >
                                    <i class="pi pi-check-square" /> Finish
                                </button>
                            </template>

                            <!-- Status icons -->
                            <i class="pi pi-check-circle" style="color: green" v-if="data.status === 'finish'"></i>
                            <i class="pi pi-times-circle" style="color: red" v-if="data.status === 'rejected'"></i>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </section>

        <!-- Create Form Section -->
        <section ref="createFormSection" v-if="auth?.user?.role === 'User'" class="mx-24 my-8 scroll-mt-8">
            <div class="mb-8 flex items-center justify-between">
                <h2 class="text-3xl font-semibold">Create Form</h2>
                <Button label="See Data" severity="warn" icon="pi pi-eye" variant="outlined" rounded @click="scrollTodataSection" />
            </div>

            <div class="p-6">
                <div class="mx-auto max-w-2xl">
                    <!-- Header -->
                    <div class="mb-8">
                        <h1 class="mb-2 text-2xl font-bold text-foreground">Request for Service</h1>
                        <p class="text-muted-foreground">Fill this form below to request a service</p>
                    </div>

                    <!-- Form -->
                    <div class="rounded-lg border border-border bg-card p-6 shadow-sm">
                        <form @submit.prevent="submitForm" class="space-y-6" enctype="multipart/form-data">
                            <!-- Nama (Read-only) -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-card-foreground">Name :</label>
                                <input
                                    type="text"
                                    v-model="form.name"
                                    class="w-full cursor-not-allowed rounded-md border border-border bg-muted px-3 py-2 text-card-foreground"
                                    readonly
                                    disabled
                                />
                            </div>

                            <!-- NPK (Read-only) -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-card-foreground">NPK :</label>
                                <input
                                    type="text"
                                    v-model="form.npk"
                                    class="w-full cursor-not-allowed rounded-md border border-border bg-muted px-3 py-2 text-card-foreground"
                                    readonly
                                    disabled
                                />
                            </div>

                            <!-- Priority -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-card-foreground">Priority :</label>
                                <select
                                    v-model="form.priority"
                                    class="w-full rounded-md border border-border bg-background px-3 py-2 text-card-foreground focus:ring-2 focus:ring-ring focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.priority }"
                                >
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                                <div v-if="form.errors.priority" class="text-sm text-red-500">{{ form.errors.priority }}</div>
                            </div>

                            <!-- Tanggal Input (Read-only) -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-card-foreground">Submit Date :</label>
                                <input
                                    type="date"
                                    v-model="form.input_date"
                                    class="w-full cursor-not-allowed rounded-md border border-border bg-muted px-3 py-2 text-card-foreground"
                                    readonly
                                    disabled
                                />
                            </div>

                            <!-- Detail Keperluan -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-card-foreground">Requirement Details :</label>
                                <textarea
                                    v-model="form.description"
                                    rows="5"
                                    class="w-full resize-none rounded-md border border-border bg-background px-3 py-2 text-card-foreground focus:ring-2 focus:ring-ring focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.description }"
                                    placeholder="Describe the details of your service needs or requests..."
                                    required
                                ></textarea>
                                <div v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</div>
                            </div>

                            <!-- Lampiran -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-card-foreground">Attachment :</label>
                                <div class="flex items-center space-x-3">
                                    <input
                                        type="file"
                                        ref="fileInput"
                                        @change="handleFileSelect"
                                        class="hidden"
                                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xlsx,.xls"
                                    />
                                    <button
                                        type="button"
                                        @click="fileInput?.click()"
                                        class="rounded-md border border-border bg-secondary px-4 py-2 text-secondary-foreground transition-colors hover:bg-secondary/80"
                                    >
                                        Choose File
                                    </button>
                                    <span v-if="selectedFileName" class="text-sm text-muted-foreground">{{ selectedFileName }}</span>
                                    <span v-else class="text-sm text-muted-foreground">No files selected</span>
                                </div>
                                <p class="text-xs text-muted-foreground">Supported formats: PDF, DOC, DOCX, JPG, PNG, XLSX, XLS (Max: 10MB)</p>
                                <div v-if="form.errors.attachment" class="text-sm text-red-500">{{ form.errors.attachment }}</div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex justify-end space-x-3 border-t border-border pt-4">
                                <button
                                    type="button"
                                    @click="resetForm"
                                    class="rounded-md border border-border px-6 py-2 text-muted-foreground transition-colors hover:bg-muted"
                                >
                                    Reset
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="rounded-md bg-primary px-6 py-2 text-primary-foreground transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <span v-if="form.processing">Submitting...</span>
                                    <span v-else>Submit Request</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Status Messages -->
                    <div v-if="form.recentlySuccessful" class="mt-4 rounded-md border border-green-200 bg-green-50 p-4">
                        <p class="text-green-800">Request submitted successfully!</p>
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
