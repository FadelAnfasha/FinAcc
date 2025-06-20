<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { Permission, Role, User } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Chip from 'primevue/chip';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';

const toast = useToast();
const page = usePage();

// Dialog states

// ===========================
// ==== Methods for Roles ====
// ===========================
const roles = ref<Role[]>([...(page.props.roles as Role[])]);
const roleDialog = ref(false);

const roleForm = ref<{
    id: number | null;
    name: string;
    permissions: any[];
}>({
    id: null,
    name: '',
    permissions: [],
});

const openRoleDialog = (role: Role | null = null) => {
    if (role) {
        roleForm.value = {
            id: role.id,
            name: role.name,
            permissions: [...role.permissions],
        };
    } else {
        roleForm.value = {
            id: null,
            name: '',
            permissions: [],
        };
    }
    roleDialog.value = true;
};

// Hanya Front End
const deleteRole = (role: Role) => {
    if (!confirm(`Are you sure you want to delete role "${role.name}"?`)) return;

    router.visit(`/admin/roles/${role.id}`, {
        method: 'delete',
        onSuccess: () => {
            // Hapus dari UI
            roles.value = roles.value.filter((r) => r.id !== role.id);
            toast.add({
                severity: 'success',
                summary: 'Deleted',
                detail: 'Role deleted successfully',
                life: 3000,
            });
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to delete role',
                life: 4000,
            });
            console.error(errors);
        },
    });
};

const saveRole = () => {
    const roleName = roleForm.value.name.trim();

    if (!roleName) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Role name is required',
            life: 3000,
        });
        return;
    }

    const isUpdate = !!roleForm.value.id;
    const routeUrl = isUpdate ? `/admin/roles/${roleForm.value.id}` : '/admin/roles';

    const method = isUpdate ? 'put' : 'post';

    router.visit(routeUrl, {
        method: method,
        data: {
            name: roleForm.value.name,
            permissions: roleForm.value.permissions,
        },
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: isUpdate ? 'Updated' : 'Created',
                detail: `Role ${isUpdate ? 'updated' : 'created'} successfully`,
                life: 3000,
            });
            roleDialog.value = false; // Tutup dialog
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: Object.values(errors).join(', '),
                life: 4000,
            });
        },
    });
};

// =================================
// ==== Methods for Permissions ====
// =================================
const permissions = ref<Permission[]>([...(page.props.permissions as Permission[])]);
const permissionDialog = ref(false);
const permissionForm = ref<{
    id: number | null;
    name: string;
}>({
    id: null,
    name: '',
});

const openPermissionDialog = (permission: Permission | null = null) => {
    if (permission) {
        permissionForm.value = {
            id: permission.id,
            name: permission.name,
        };
    } else {
        permissionForm.value = {
            id: null,
            name: '',
        };
    }
    permissionDialog.value = true;
};

// Hanya Front End
const deletePermission = (permission: Permission) => {
    if (confirm(`Are you sure you want to delete permission "${permission.name}"?`)) {
        permissions.value = permissions.value.filter((p) => p.id !== permission.id);
        toast.add({ severity: 'success', summary: 'Success', detail: 'Permission deleted successfully', life: 3000 });
    }
};

// Hanya Front End
const savePermission = () => {
    if (permissionForm.value.name.trim() === '') {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Permission name is required', life: 3000 });
        return;
    }

    if (permissionForm.value.id) {
        // Update existing permission
        const index = permissions.value.findIndex((p) => p.id === permissionForm.value.id);
        if (index !== -1) {
            permissions.value[index] = { ...permissionForm.value };
        }
        toast.add({ severity: 'success', summary: 'Success', detail: 'Permission updated successfully', life: 3000 });
    } else {
        // Create new permission
        const newPermission = {
            id: Math.max(...permissions.value.map((r) => r.id).filter((id) => id !== null)) + 1,
            name: permissionForm.value.name,
        };
        permissions.value.push(newPermission);
        toast.add({ severity: 'success', summary: 'Success', detail: 'Permission created successfully', life: 3000 });
    }

    permissionDialog.value = false;
};

// =================================
// ==== Methods for Permissions ====
// =================================
const users = ref<User[]>([...(page.props.users as User[])]);
const userRoleDialog = ref(false);

const userRoleForm = ref({
    userId: null,
    roleId: null,
});

const selectedUser = ref<User | null>(null);
const selectedRole = ref<Role | null>(null);

// Methods for User Role Assignment
const openUserRoleDialog = (user: User | null = null) => {
    selectedUser.value = user;
    selectedRole.value = user ? (roles.value.find((r) => r.name === user.role) ?? null) : null;
    userRoleDialog.value = true;
};

const assignRole = () => {
    if (!selectedRole.value) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Please select a role', life: 3000 });
        return;
    }

    if (!selectedUser.value) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No user selected', life: 3000 });
        return;
    }
    if (selectedUser.value) {
        const userIndex = users.value.findIndex((u) => u.id === selectedUser.value!.id);
        if (userIndex !== -1) {
            users.value[userIndex].role = selectedRole.value.name;
        }
    }

    toast.add({ severity: 'success', summary: 'Success', detail: 'Role assigned successfully', life: 3000 });
    userRoleDialog.value = false;
};

const getRoleTagClass = (roleName: string) => {
    const base = 'inline-block text-center w-30 text-white text-sm font-medium px-3 py-1 rounded-full hover:text-black cursor-pointer';

    switch (roleName) {
        case 'Admin':
            return `${base} bg-red-500 hover:bg-red-700`;
        case 'Reviewer':
            return `${base} bg-rose-500 hover:bg-rose-700`;
        case 'User':
            return `${base} bg-emerald-500 hover:bg-emerald-700`;
        default:
            return `${base} bg-gray-500 hover:bg-gray-700`;
    }
};
</script>

<template>
    <Head title="Administrator" />
    <AppLayout>
        <div class="p-6">
            <div class="mb-4">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Administrator Panel</h1>
                <p class="text-gray-600 dark:text-gray-400">Manage roles, permissions, and user assignments</p>
            </div>

            <Tabs value="0">
                <TabList>
                    <Tab value="0">Roles Management</Tab>
                    <Tab value="1">Permission Management</Tab>
                    <Tab value="2">User Role Assignment</Tab>
                </TabList>
                <TabPanels>
                    <!-- Roles Tab -->
                    <TabPanel header="Roles Management" value="0">
                        <Card>
                            <template #header>
                                <div class="flex items-center justify-between p-4">
                                    <h2 class="text-xl font-semibold">Roles</h2>
                                    <Button label="Add Role" icon="pi pi-plus" @click="openRoleDialog()" class="p-button-success" />
                                </div>
                            </template>
                            <template #content>
                                <DataTable :value="roles" responsiveLayout="scroll">
                                    <Column field="name" header="Role Name" sortable></Column>
                                    <Column header="Permissions">
                                        <template #body="slotProps">
                                            <div class="flex flex-wrap gap-1">
                                                <Chip
                                                    v-for="permission in slotProps.data.permissions"
                                                    :key="permission"
                                                    :label="permission"
                                                    class="text-xs"
                                                />
                                            </div>
                                        </template>
                                    </Column>
                                    <Column header="Actions">
                                        <template #body="slotProps">
                                            <div class="flex gap-2">
                                                <Button
                                                    icon="pi pi-pencil"
                                                    class="p-button-rounded p-button-text p-button-warning"
                                                    @click="openRoleDialog(slotProps.data)"
                                                    v-tooltip="'Edit'"
                                                />
                                                <Button
                                                    icon="pi pi-trash"
                                                    class="p-button-rounded p-button-text p-button-danger"
                                                    @click="deleteRole(slotProps.data)"
                                                    v-tooltip="'Delete'"
                                                />
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </template>
                        </Card>
                    </TabPanel>

                    <!-- Permissions Management -->
                    <TabPanel header="Permissions Management" value="1">
                        <Card>
                            <template #header>
                                <div class="flex items-center justify-between p-4">
                                    <h2 class="text-xl font-semibold">Permissions</h2>
                                    <Button label="Add Permission" icon="pi pi-plus" @click="openPermissionDialog()" class="p-button-success" />
                                </div>
                            </template>
                            <template #content>
                                <DataTable :value="permissions" responsiveLayout="scroll">
                                    <Column field="name" header="Permission Name" sortable></Column>
                                    <Column header="Actions">
                                        <template #body="slotProps">
                                            <div class="flex gap-2">
                                                <Button
                                                    icon="pi pi-pencil"
                                                    class="p-button-rounded p-button-text p-button-warning"
                                                    @click="openPermissionDialog(slotProps.data)"
                                                    v-tooltip="'Edit'"
                                                />
                                                <Button
                                                    icon="pi pi-trash"
                                                    class="p-button-rounded p-button-text p-button-danger"
                                                    @click="deletePermission(slotProps.data)"
                                                    v-tooltip="'Delete'"
                                                />
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </template>
                        </Card>
                    </TabPanel>

                    <!-- User Role Assignment Tab -->
                    <TabPanel header="User Role Assignment" value="2">
                        <Card>
                            <template #header>
                                <div class="p-4">
                                    <h2 class="text-xl font-semibold">Users & Roles</h2>
                                    <p class="text-gray-600 dark:text-gray-400">Assign roles to users</p>
                                </div>
                            </template>
                            <template #content>
                                <DataTable :value="users" responsiveLayout="scroll">
                                    <Column field="name" header="Name" sortable></Column>
                                    <Column field="npk" header="NPK" sortable></Column>
                                    <Column header="Current Role">
                                        <template #body="slotProps">
                                            <Tag :value="slotProps.data.role" :class="getRoleTagClass(slotProps.data.role)" unstyled />
                                        </template>
                                    </Column>
                                    <Column header="Actions">
                                        <template #body="slotProps">
                                            <Button
                                                label="Assign Role"
                                                icon="pi pi-user-edit"
                                                class="p-button-rounded p-button-text"
                                                @click="openUserRoleDialog(slotProps.data)"
                                            />
                                        </template>
                                    </Column>
                                </DataTable>
                            </template>
                        </Card>
                    </TabPanel>
                </TabPanels>
            </Tabs>

            <!-- Role Dialog -->
            <Dialog v-model:visible="roleDialog" :style="{ width: '450px' }" header="Role Details" :modal="true">
                <div class="flex flex-col gap-4">
                    <div>
                        <label for="roleName" class="mb-2 block text-sm font-medium">Role Name</label>
                        <InputText id="roleName" v-model="roleForm.name" class="w-full" placeholder="Enter role name" />
                    </div>
                    <div>
                        <label for="rolePermissions" class="mb-2 block text-sm font-medium">Permissions</label>
                        <MultiSelect
                            v-model="roleForm.permissions"
                            :options="permissions as any[]"
                            optionLabel="name"
                            optionValue="name"
                            placeholder="Select permissions"
                            class="w-full"
                        />
                    </div>
                </div>
                <template #footer>
                    <Button label="Cancel" icon="pi pi-times" class="p-button-text" @click="roleDialog = false" />
                    <Button label="Save" icon="pi pi-check" class="p-button-text" @click="saveRole" />
                </template>
            </Dialog>

            <!-- Permission Dialog -->
            <Dialog v-model:visible="permissionDialog" :style="{ width: '450px' }" header="Permission Details" :modal="true">
                <div class="flex flex-col gap-4">
                    <div>
                        <label for="permissionName" class="mb-2 block text-sm font-medium">Permission Name</label>
                        <InputText id="permissionName" v-model="permissionForm.name" class="w-full" placeholder="Enter permission name" />
                    </div>
                </div>
                <template #footer>
                    <Button label="Cancel" icon="pi pi-times" class="p-button-text" @click="permissionDialog = false" />
                    <Button label="Save" icon="pi pi-check" class="p-button-text" @click="savePermission" />
                </template>
            </Dialog>

            <!-- User Role Assignment Dialog -->
            <Dialog v-model:visible="userRoleDialog" :style="{ width: '450px' }" header="Assign Role" :modal="true">
                <div class="flex flex-col gap-4" v-if="selectedUser">
                    <div>
                        <label class="mb-2 block text-sm font-medium">User</label>
                        <p class="text-lg font-semibold">{{ selectedUser.name }} ({{ selectedUser.npk }})</p>
                    </div>
                    <div>
                        <label for="userRole" class="mb-2 block text-sm font-medium">Select Role</label>
                        <Dropdown v-model="selectedRole" :options="roles" optionLabel="name" placeholder="Select a role" class="w-full" />
                    </div>
                </div>
                <template #footer>
                    <Button label="Cancel" icon="pi pi-times" class="p-button-text" @click="userRoleDialog = false" />
                    <Button label="Assign" icon="pi pi-check" class="p-button-text" @click="assignRole" />
                </template>
            </Dialog>

            <Toast />
        </div>
    </AppLayout>
</template>
