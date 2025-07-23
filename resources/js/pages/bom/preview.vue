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

const totalCT = computed(() => page.props.totalCT as any);
const mfgCost = computed(() => page.props.mfgCost as any);
const opexCost = computed(() => page.props.opexCost as any);
const totalCost = computed(() => page.props.totalCost as any);
const margin = computed(() => page.props.margin as any);
const sellingPrice = computed(() => page.props.sellingPrice as any);
const total_rm = computed(() => page.props.total_rm as any);
const total_process = computed(() => page.props.total_pr as any);

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

onMounted(() => {
    setTimeout(() => {
        window.print();
    }, 500);
});
</script>

<style>
@media print {
    @page {
        size: landscape;
        margin: 10mm; /* Menggunakan mm agar lebih presisi */
    }

    .bom-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5pt; /* Ini adalah font dasar untuk tabel */
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .bom-table th,
    .bom-table td {
        border: 1px solid #000 !important;
        padding: 2px !important; /* Mengurangi padding sedikit lagi */
        vertical-align: top !important;
        box-sizing: border-box !important;
        background-color: transparent !important;
        line-height: 1.1; /* Tambahkan line-height untuk kontrol spasi baris */
    }

    .bom-table thead th {
        background-color: #0891b2 !important;
        color: white !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        font-size: 9pt; /* Ukuran font header */
    }

    .bom-table tfoot td {
        background-color: #a7f3d0 !important;
        color: #1a202c !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        font-size: 9pt; /* Ukuran font footer */
    }

    .bom-table tfoot tr:first-child td {
        border-top: 1px solid #000 !important;
    }

    .bom-table tfoot td {
        border: 1px solid #000 !important;
    }

    /* Penyesuaian lebar kolom - Ini adalah area krusial */
    /* Total lebar kolom harus <= lebar halaman minus margin */
    /* A4 Landscape (297mm) - 20mm margin = 277mm = ~1047px @96dpi */
    /* Anda harus sangat agresif di sini. */

    .bom-table .codeCol {
        width: 85px; /* Sedikit lebih kecil dari 100px */
        min-width: 85px;
        max-width: 85px;
        font-size: 9.5px; /* Ukuran font default untuk codeCol, akan ditimpa oleh main-item-code-cell */
    }
    .bom-table .typeCol {
        width: 170px; /* Lebih kecil dari 190px */
        min-width: 170px;
        max-width: 170px;
        font-size: 9.5px;
    }
    .bom-table .stCol {
        font-size: 10px; /* Ukuran font untuk header standard cost */
    }
    .bom-table .col-qty {
        width: 35px; /* Sangat kecil */
        min-width: 35px;
        max-width: 35px;
        font-size: 9.5px;
    }
    .bom-table .col-price {
        width: 60px; /* Lebih kecil */
        min-width: 60px;
        max-width: 60px;
        font-size: 9.5px;
    }
    .bom-table .col-total {
        width: 75px; /* Lebih kecil */
        min-width: 75px;
        max-width: 75px;
        font-size: 9.5px;
    }

    /* Font size untuk body tabel (qty, price, total) */
    .bdy-qty,
    .bdy-price,
    .bdy-total {
        font-size: 9px !important; /* Ubah ke 9px */
        text-align: right !important;
    }

    /* Font size untuk item code (anak) dan description di tbody */
    /* Penting: Pastikan ini tidak menargetkan .main-item-code-cell */
    .bom-table tbody td:first-child:not(.main-item-code-cell), /* Menambahkan :not() */
    .bom-table tbody td:nth-child(2),
    .bom-table tbody td:nth-child(3) {
        font-size: 9px !important; /* Ubah ke 9px */
    }

    /* Child lines adjustments */
    .bom-table .w-20 {
        width: 30px !important; /* Lebih kecil lagi */
    }
    .bom-table .text-orange-500 {
        font-size: 8.5px !important; /* Lebih kecil lagi */
    }

    /* ====== KUNCI SOLUSI FONT-SIZE LEVEL 0 ITEM CODE ====== */
    /* Tempatkan ini di bagian paling akhir dari `@media print` */
    /* Ini akan memastikan properti ini memiliki prioritas tertinggi */
    .bom-table .main-item-code-cell {
        font-size: 9px !important; /* Sesuaikan ukuran font yang Anda inginkan, bisa 14px, 16px, dst. */
        font-weight: bold !important;
        /* Width sudah diatur oleh .codeCol, tapi bisa ditimpa jika perlu */
        width: 85px !important; /* Pastikan konsisten dengan .codeCol atau atur ulang di sini */
        line-height: 1.2 !important; /* Sesuaikan jika teks terlalu rapat */
    }

    /* Footer Section */
    .footer-section {
        margin-top: 10px !important;
        width: 100%;
        padding: 0 4mm !important;
        box-sizing: border-box;
    }

    .note-and-signatures-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        width: 100%;
    }

    .notes-section {
        flex: 1;
        font-size: 8pt !important; /* Sesuaikan font notes */
        color: #555;
        padding-right: 10px;
    }
    .notes-section p {
        margin: 0;
        line-height: 1.1;
    }

    .signatures-cost-details {
        display: flex;
        justify-content: flex-end;
        align-items: flex-end;
        gap: 10px; /* Kurangi gap */
        flex-shrink: 0;
    }

    .signatures-wrapper {
        display: flex;
        gap: 8px; /* Kurangi gap */
        align-items: flex-end;
    }

    .signature-box {
        text-align: center;
        flex-shrink: 0;
        width: 100px !important; /* Kurangi lebar box tanda tangan */
        height: 80px !important; /* Kurangi tinggi box tanda tangan */
        border: 1px solid #000 !important;
        padding: 2px !important; /* Kurangi padding */
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: center;
        box-sizing: border-box;
        font-size: 7pt !important; /* Kecilkan font di dalam box */
    }

    .signature-box p {
        margin: 0;
        line-height: 1.1;
        color: #333;
    }

    .signature-name {
        font-size: 7.5pt !important; /* Kecilkan font nama tanda tangan */
        line-height: 1.1 !important;
    }

    .cost-details-box {
        flex-shrink: 0;
        font-size: 9pt !important; /* Sesuaikan font cost details */
        color: #333;
        line-height: 1.2 !important;
    }

    .cost-details-box table {
        width: auto;
        border-collapse: collapse;
    }
    .cost-details-box table tr td {
        border: none !important;
        padding: 1px 2px !important; /* Kurangi padding */
        vertical-align: top;
    }
    .cost-details-box table tr td:first-child {
        text-align: left;
        padding-right: 5px !important; /* Kurangi padding */
    }
    .cost-details-box table tr td:last-child {
        text-align: right;
    }
}
</style>

<template>
    <div class="bg-white">
        <table class="bom-table w-full text-slate-900">
            <thead class="bg-cyan-600 font-bold">
                <tr>
                    <th class="codeCol border p-1" rowspan="2">
                        ITEM CODE <br />
                        {{ previewData.item_code }}
                    </th>
                    <th class="typeCol border p-1" rowspan="2" colspan="3">
                        TYPE <br />
                        {{ previewData?.bom?.description || 'N/A' }}
                    </th>

                    <th class="stCol border p-1 text-center" :colspan="4 * 3">
                        STANDARD COST 2025 <br />
                        {{ formattedReportDate }}
                    </th>
                </tr>
                <tr>
                    <template v-for="i in 4" :key="i">
                        <th class="col-qty border p-1 text-center">Qty</th>
                        <th class="col-price border p-1 text-center">Price</th>
                        <th class="col-total border p-1 text-center">Total</th>
                    </template>
                </tr>
            </thead>
            <tbody>
                <template v-for="category in bomCategories" :key="category.name">
                    <template v-if="category.name === 'FinishGood'">
                        <template v-for="item in category.items" :key="item.item_code">
                            <tr v-if="item.level === 0">
                                <td :rowspan="1 + (item.children ? item.children.length : 0)" class="codeCol main-item-code-cell relative border p-1">
                                    {{ item.item_code }}
                                </td>
                                <td class="typeCol border p-1" colspan="3">
                                    {{ item.description }}
                                </td>
                                <template v-for="(period, pIndex) in item.periods_data" :key="pIndex">
                                    <td class="bdy-qty col-qty border p-1 text-right">{{ period.qty }}</td>
                                    <td class="bdy-price col-price border p-1 text-right">{{ period.price }}</td>
                                    <td class="bdy-total col-total border p-1 text-right">{{ period.total }}</td>
                                </template>
                            </tr>

                            <template v-if="item.children && item.children.length">
                                <tr v-for="child in item.children" :key="child.item_code">
                                    <td class="border p-1">{{ child.type }}</td>
                                    <td class="typeCol border p-1" colspan="2">
                                        {{ child.description }}
                                        <span v-if="child.wip_info">{{ child.wip_info }}</span>
                                        <div v-if="child.type === 'PR' && child.lines && child.lines.length > 0" class="mt-1 text-orange-500 italic">
                                            <div v-for="(line, lineIndex) in child.lines" :key="lineIndex" class="flex justify-between">
                                                <div>
                                                    <span class="inline-block w-20">{{ line.name }}</span>
                                                    <span class="ml-6">{{ line.percentage }}</span>
                                                </div>
                                                <span v-if="line.cycle_time">{{ line.cycle_time }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <template v-for="(period, pIndex) in child.periods_data" :key="pIndex">
                                        <td class="bdy-qty col-qty border p-1 text-center">{{ period.qty }}</td>
                                        <td class="bdy-price col-price border p-1 text-center">{{ period.price }}</td>
                                        <td class="bdy-total col-total border p-1 text-center">{{ period.total }}</td>
                                    </template>
                                </tr>
                            </template>
                        </template>
                    </template>

                    <template v-if="category.name === 'Topcoat'">
                        <template v-for="item in category.items" :key="item.item_code">
                            <tr v-if="item.level === 0">
                                <td :rowspan="1 + (item.children ? item.children.length : 0)" class="codeCol main-item-code-cell relative border p-1">
                                    {{ item.item_code }}
                                </td>
                                <td class="typeCol border p-1" colspan="3">
                                    {{ item.description }}
                                </td>
                                <template v-for="(period, pIndex) in item.periods_data" :key="pIndex">
                                    <td class="bdy-qty col-qty border p-1 text-right">{{ period.qty }}</td>
                                    <td class="bdy-price col-price border p-1 text-right">{{ period.price }}</td>
                                    <td class="bdy-total col-total border p-1 text-right">{{ period.total }}</td>
                                </template>
                            </tr>

                            <template v-if="item.children && item.children.length">
                                <tr v-for="child in item.children" :key="child.item_code">
                                    <td class="border p-1">{{ child.type }}</td>
                                    <td class="border p-1 whitespace-nowrap">{{ child.item_code }}</td>
                                    <td class="typeCol border p-1">
                                        {{ child.description }}
                                        <span v-if="child.wip_info">{{ child.wip_info }}</span>
                                        <div
                                            v-if="child.type === 'PR' && child.lines && child.lines.length > 0"
                                            class="mt-1 ml-2 text-orange-500 italic"
                                        >
                                            <div v-for="(line, lineIndex) in child.lines" :key="lineIndex" class="flex justify-between">
                                                <div>
                                                    <span class="inline-block w-20">{{ line.name }}</span>
                                                    <span class="ml-6">{{ line.percentage }}</span>
                                                </div>
                                                <span v-if="line.cycle_time">{{ line.cycle_time }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <template v-for="(period, pIndex) in child.periods_data" :key="pIndex">
                                        <td class="bdy-qty col-qty border p-1 text-center">{{ period.qty }}</td>
                                        <td class="bdy-price col-price border p-1 text-center">{{ period.price }}</td>
                                        <td class="bdy-total col-total border p-1 text-center">{{ period.total }}</td>
                                    </template>
                                </tr>
                            </template>
                        </template>
                    </template>

                    <template v-if="category.name === 'CED'">
                        <template v-for="item in category.items" :key="item.item_code">
                            <tr v-if="item.level === 0">
                                <td :rowspan="1 + (item.children ? item.children.length : 0)" class="codeCol main-item-code-cell relative border p-1">
                                    {{ item.item_code }}
                                </td>
                                <td class="typeCol border p-1" colspan="3">
                                    {{ item.description }}
                                </td>
                                <template v-for="(period, pIndex) in item.periods_data" :key="pIndex">
                                    <td class="bdy-qty col-qty border p-1 text-right">{{ period.qty }}</td>
                                    <td class="bdy-price col-price border p-1 text-right">{{ period.price }}</td>
                                    <td class="bdy-total col-total border p-1 text-right">{{ period.total }}</td>
                                </template>
                            </tr>

                            <template v-if="item.children && item.children.length">
                                <tr v-for="child in item.children" :key="child.item_code">
                                    <td class="border p-1">{{ child.type }}</td>
                                    <td class="border p-1 whitespace-nowrap">{{ child.item_code }}</td>
                                    <td class="typeCol border p-1">
                                        {{ child.description }}
                                        <span v-if="child.wip_info">{{ child.wip_info }}</span>
                                        <div
                                            v-if="child.type === 'PR' && child.lines && child.lines.length > 0"
                                            class="mt-1 ml-2 text-orange-500 italic"
                                        >
                                            <div v-for="(line, lineIndex) in child.lines" :key="lineIndex" class="flex justify-between">
                                                <div>
                                                    <span class="inline-block w-20">{{ line.name }}</span>
                                                    <span class="ml-6">{{ line.percentage }}</span>
                                                </div>
                                                <span v-if="line.cycle_time">{{ line.cycle_time }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <template v-for="(period, pIndex) in child.periods_data" :key="pIndex">
                                        <td class="bdy-qty col-qty border p-1 text-center">{{ period.qty }}</td>
                                        <td class="bdy-price col-price border p-1 text-center">{{ period.price }}</td>
                                        <td class="bdy-total col-total border p-1 text-center">{{ period.total }}</td>
                                    </template>
                                </tr>
                            </template>
                        </template>
                    </template>

                    <template v-if="category.name === 'Assembly'">
                        <template v-for="item in category.items" :key="item.item_code">
                            <tr v-if="item.level === 0">
                                <td :rowspan="1 + (item.children ? item.children.length : 0)" class="codeCol main-item-code-cell relative border p-1">
                                    {{ item.item_code }}
                                </td>
                                <td class="typeCol border p-1" colspan="3">
                                    {{ item.description }}
                                </td>
                                <template v-for="(period, pIndex) in item.periods_data" :key="pIndex">
                                    <td class="bdy-qty col-qty border p-1 text-right">{{ period.qty }}</td>
                                    <td class="bdy-price col-price border p-1 text-right">{{ period.price }}</td>
                                    <td class="bdy-total col-total border p-1 text-right">{{ period.total }}</td>
                                </template>
                            </tr>

                            <template v-if="item.children && item.children.length">
                                <tr v-for="child in item.children" :key="child.item_code">
                                    <td class="border p-1">{{ child.type }}</td>
                                    <td class="border p-1 whitespace-nowrap">{{ child.item_code }}</td>
                                    <td class="typeCol border p-1">
                                        {{ child.description }}
                                        <span v-if="child.wip_info">{{ child.wip_info }}</span>
                                        <div
                                            v-if="child.type === 'PR' && child.lines && child.lines.length > 0"
                                            class="mt-1 ml-2 text-orange-500 italic"
                                        >
                                            <div v-for="(line, lineIndex) in child.lines" :key="lineIndex" class="flex justify-between">
                                                <div>
                                                    <span class="inline-block w-20">{{ line.name }}</span>
                                                    <span class="ml-6">{{ line.percentage }}</span>
                                                </div>
                                                <span v-if="line.cycle_time">{{ line.cycle_time }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <template v-for="(period, pIndex) in child.periods_data" :key="pIndex">
                                        <td class="bdy-qty col-qty border p-1 text-center">{{ period.qty }}</td>
                                        <td class="bdy-price col-price border p-1 text-center">{{ period.price }}</td>
                                        <td class="bdy-total col-total border p-1 text-center">{{ period.total }}</td>
                                    </template>
                                </tr>
                            </template>
                        </template>
                    </template>

                    <template v-if="category.name === 'Process'">
                        <template v-for="item in category.items" :key="item.item_code">
                            <tr v-if="item.level === 0">
                                <td :rowspan="1 + (item.children ? item.children.length : 0)" class="codeCol main-item-code-cell relative border p-1">
                                    {{ item.item_code }}
                                </td>
                                <td class="typeCol border p-1" colspan="3">
                                    {{ item.description }}
                                </td>
                                <template v-for="(period, pIndex) in item.periods_data" :key="pIndex">
                                    <td class="bdy-qty col-qty border p-1 text-right">{{ period.qty }}</td>
                                    <td class="bdy-price col-price border p-1 text-right">{{ period.price }}</td>
                                    <td class="bdy-total col-total border p-1 text-right">{{ period.total }}</td>
                                </template>
                            </tr>

                            <template v-if="item.children && item.children.length">
                                <tr v-for="child in item.children" :key="child.item_code">
                                    <td class="border p-1">{{ child.type }}</td>
                                    <td class="border p-1 whitespace-nowrap">{{ child.item_code }}</td>
                                    <td class="typeCol border p-1">
                                        {{ child.description }}
                                        <span v-if="child.wip_info">{{ child.wip_info }}</span>
                                        <div
                                            v-if="child.type === 'PR' && child.lines && child.lines.length > 0"
                                            class="mt-1 ml-2 text-orange-500 italic"
                                        >
                                            <div v-for="(line, lineIndex) in child.lines" :key="lineIndex" class="flex justify-between">
                                                <div>
                                                    <span class="inline-block w-20">{{ line.name }}</span>
                                                    <span class="ml-6">{{ line.percentage }}</span>
                                                </div>
                                                <span v-if="line.cycle_time">{{ line.cycle_time }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <template v-for="(period, pIndex) in child.periods_data" :key="pIndex">
                                        <td class="bdy-qty col-qty border p-1 text-center">{{ period.qty }}</td>
                                        <td class="bdy-price col-price border p-1 text-center">{{ period.price }}</td>
                                        <td class="bdy-total col-total border p-1 text-center">{{ period.total }}</td>
                                    </template>
                                </tr>
                            </template>
                        </template>
                    </template>
                </template>
            </tbody>
            <tfoot class="bg-cyan-200">
                <tr>
                    <td class="border p-1 text-left font-bold">Total Cycle Time:</td>
                    <td class="border p-1 text-right font-bold" colspan="3">{{ totalCT }}</td>
                    <td colspan="11" class="border p-1 text-left font-bold">Total Manufacturing Cost (Total dari FG):</td>
                    <td class="border p-1 text-right font-bold">{{ mfgCost }}</td>
                </tr>
            </tfoot>
        </table>
        <div class="footer-section">
            <div class="note-and-signatures-wrapper">
                <div class="notes-section">
                    <div class="mb-2">
                        <p>
                            Total Raw Material : <span>{{ total_rm }}</span>
                        </p>
                        <p>
                            Total Process : <span>{{ total_process }}</span>
                        </p>
                    </div>
                    <p><strong>Note:</strong></p>
                    <p>
                        <em
                            >This amount is included all inefficiencies, since actual production conditions (TPP Target from PPIC) are applied on
                            it</em
                        ><br />
                        <em>To be able to compete with market prices, the cost of 10-25% may be deducted from process cost</em>
                    </p>
                </div>

                <div class="signatures-cost-details">
                    <div class="signatures-wrapper">
                        <div class="signature-box">
                            <p>Prepared,</p>
                            <p class="signature-name">(.....................................)</p>
                        </div>
                        <div class="signature-box">
                            <p>Approved,</p>
                            <p class="signature-name">(.....................................)</p>
                        </div>
                    </div>
                    <div class="cost-details-box text-right">
                        <table>
                            <tr>
                                <td>Mfg Cost</td>
                                <td>{{ mfgCost }}</td>
                            </tr>
                            <tr>
                                <td>6.0% OPEX</td>
                                <td>{{ opexCost }}</td>
                            </tr>
                            <tr>
                                <td>Total Cost</td>
                                <td>{{ totalCost }}</td>
                            </tr>
                            <tr>
                                <td>5.0% Profit Margin</td>
                                <td>{{ margin }}</td>
                            </tr>
                            <tr>
                                <td>Selling Price</td>
                                <td>{{ sellingPrice }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
