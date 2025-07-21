<script setup lang="ts">
// resources/js/Pages/bom/preview.vue

import { usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue'; // Impor 'onMounted'

const page = usePage();

// Ambil data yang dikirim dari controller
const previewData = computed(() => page.props.previewData as any);
const reportData = computed(() => page.props.reportData as any);
const bomCategories = computed(() => {
    return reportData.value?.categories || [];
});

console.log(previewData.value);
console.log(bomCategories.value);

const formattedReportDate = computed(() => {
    // reportData.updated_at akan berisi string seperti "2025-07-21T02:58:55.000000Z"
    const dateString = previewData.value?.updated_at;

    if (dateString) {
        const date = new Date(dateString);
        const options: Intl.DateTimeFormatOptions = { day: '2-digit', month: 'long', year: 'numeric' };
        return date.toLocaleDateString('id-ID', options);
    }
    return 'N/A'; // Default jika tanggal tidak ada
});

const formatNumber = (value: number | string | null | undefined): string => {
    if (value === null || value === undefined || value === '') {
        return '';
    }
    // Jika nilai sudah dalam string terformat (misal "134.129"), langsung kembalikan
    // Ini penting karena number_format di PHP sudah mengkonversi ke string
    if (typeof value === 'string' && (value.includes('.') || value.includes(','))) {
        return value;
    }
    // Jika masih number atau string tanpa format, coba format ulang
    const numValue = typeof value === 'string' ? parseFloat(value.replace(/\./g, '').replace(/,/g, '.')) : value;
    if (isNaN(numValue)) {
        return '';
    }
    return numValue.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
};

onMounted(() => {
    window.print();
});
</script>

<template>
    <div class="border bg-white p-4 text-slate-900">
        <img src="/storage/images/topy.png" width="200px" class="mb-2" />
        <table class="text-xs">
            <thead>
                <tr>
                    <th class="border whitespace-nowrap" rowspan="2">
                        ITEM CODE <br />
                        {{ previewData.item_code }}
                    </th>
                    <th class="border p-2 whitespace-nowrap" rowspan="2" style="min-width: 200px">
                        TYPE <br />
                        {{ previewData?.bom?.description || 'N/A' }}
                    </th>

                    <th class="border p-2 text-center" :colspan="4 * 3">
                        STANDARD COST 2025 <br />
                        {{ formattedReportDate }}
                    </th>
                </tr>
                <tr>
                    <template v-for="i in 4" :key="i">
                        <th class="border p-2 text-center whitespace-nowrap">Qty</th>
                        <th class="border p-2 text-center whitespace-nowrap">Price</th>
                        <th class="border p-2 text-center whitespace-nowrap">Total</th>
                    </template>
                </tr>
            </thead>
            <tbody>
                <template v-for="category in bomCategories" :key="category.name">
                    <template v-if="category.name === 'Assembly'">
                        <template v-for="item in category.items" :key="item.item_code">
                            <tr v-if="item.level === 0">
                                <td class="border p-2 whitespace-nowrap">
                                    {{ item.item_code }}
                                </td>
                                <td class="border p-2 whitespace-nowrap">
                                    {{ item.description }}
                                </td>
                                <template v-for="(period, pIndex) in item.periods_data" :key="pIndex">
                                    <td class="border p-2 text-right whitespace-nowrap">{{ period.qty }}</td>
                                    <td class="border p-2 text-right whitespace-nowrap">{{ period.price }}</td>
                                    <td class="border p-2 text-right whitespace-nowrap">{{ period.total }}</td>
                                </template>
                            </tr>

                            <template v-if="item.children && item.children.length">
                                <tr v-for="child in item.children" :key="child.item_code">
                                    <td class="border p-2 pl-8 whitespace-nowrap">
                                        <span class="inline-block w-8">{{ child.type }}</span>
                                        {{ child.item_code }}
                                    </td>
                                    <td class="border p-2 whitespace-nowrap">
                                        {{ child.description }}
                                        <span v-if="child.wip_info">{{ child.wip_info }}</span>
                                    </td>
                                    <template v-for="(period, pIndex) in child.periods_data" :key="pIndex">
                                        <td class="border p-2 text-right whitespace-nowrap">{{ period.qty }}</td>
                                        <td class="border p-2 text-right whitespace-nowrap">{{ period.price }}</td>
                                        <td class="border p-2 text-right whitespace-nowrap">{{ period.total }}</td>
                                    </template>
                                </tr>
                            </template>
                        </template>
                    </template>
                    <template v-if="category.name === 'Process'">
                        <template v-for="item in category.items" :key="item.item_code">
                            <tr v-if="item.level === 0">
                                <td class="border p-2 whitespace-nowrap">
                                    {{ item.item_code }}
                                </td>
                                <td class="border p-2 whitespace-nowrap">
                                    {{ item.description }}
                                </td>
                                <template v-for="(period, pIndex) in item.periods_data" :key="pIndex">
                                    <td class="border p-2 text-right whitespace-nowrap">{{ period.qty }}</td>
                                    <td class="border p-2 text-right whitespace-nowrap">{{ period.price }}</td>
                                    <td class="border p-2 text-right whitespace-nowrap">{{ period.total }}</td>
                                </template>
                            </tr>

                            <template v-if="item.children && item.children.length">
                                <tr v-for="child in item.children" :key="child.item_code">
                                    <td class="border p-2 pl-8 whitespace-nowrap">
                                        <span class="inline-block w-8">{{ child.type }}</span>
                                        {{ child.item_code }}
                                    </td>
                                    <td class="border p-2 whitespace-nowrap">
                                        {{ child.description }}
                                        <span v-if="child.wip_info">{{ child.wip_info }}</span>
                                    </td>
                                    <template v-for="(period, pIndex) in child.periods_data" :key="pIndex">
                                        <td class="border p-2 text-right whitespace-nowrap">{{ period.qty }}</td>
                                        <td class="border p-2 text-right whitespace-nowrap">{{ period.price }}</td>
                                        <td class="border p-2 text-right whitespace-nowrap">{{ period.total }}</td>
                                    </template>
                                </tr>
                            </template>
                        </template>
                    </template>
                </template>
            </tbody>
        </table>
    </div>
</template>
