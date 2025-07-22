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
    setTimeout(() => {
        window.print();
    }, 500);
});
</script>

<style>
/* CSS khusus untuk print */
@media print {
    @page {
        size: landscape; /* Mengatur orientasi halaman menjadi landscape */
        margin: 0.5cm; /* Mengurangi margin halaman default */
    }

    /* Memaksa background color dan teks dicetak */
    thead,
    tfoot,
    .bg-gray-200,
    .text-orange-500 {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Warna header */
    thead {
        background-color: #0891b2 !important; /* cyan-600 */
        color: white !important;
    }

    /* Warna footer */
    tfoot {
        background-color: #a7f0ed !important; /* cyan-200 */
        color: #1a202c !important;
    }

    /* Warna border agar terlihat jelas saat dicetak */
    table,
    th,
    td {
        border-color: #000 !important;
        border-width: 1px !important; /* Pastikan border selalu 1px */
        border-style: solid !important;
    }

    /* Untuk memastikan warna background pada baris kategori (misal bg-gray-200) juga dicetak */
    .bg-gray-200 {
        background-color: #e5e7eb !important; /* gray-200 */
    }

    /* Jika ada teks warna orange pada cycle time */
    .text-orange-500 {
        color: #f97316 !important; /* orange-500 */
    }

    /* ================================================= */
    /* ATURAN UNTUK MENCAPAI "FIT IN ONE PAGE" SEBISA MUNGKIN */
    /* ================================================= */

    /* Mengurangi ukuran font keseluruhan untuk tabel */
    .w-full.text-xs.text-slate-900 {
        font-size: 8pt !important; /* Mengurangi dari text-xs (12px/0.75rem) menjadi 8pt */
    }

    /* Mengurangi padding pada semua sel tabel */
    th,
    td {
        padding: 2px 4px !important; /* Mengurangi padding p-2 (8px) menjadi lebih kecil */
        line-height: 1.2; /* Mengurangi spasi antar baris */
    }

    /* Mengatur tabel agar lebar kolom lebih terkontrol */
    table {
        table-layout: fixed !important; /* Memaksa lebar kolom agar sesuai konten */
        width: 100% !important; /* Pastikan tabel mengisi lebar yang tersedia */
    }

    /* Memastikan teks panjang pecah baris di dalam sel */
    td,
    th {
        word-wrap: break-word !important; /* Untuk teks yang sangat panjang */
        word-break: break-all !important; /* Alternatif/pelengkap break-word */
        white-space: normal !important; /* Membatalkan whitespace-nowrap Tailwind */
    }

    /* Menyesuaikan min-width kolom TYPE agar lebih fleksibel tapi tetap terbaca */
    th:nth-child(2),
    td:nth-child(2) {
        /* Target kolom TYPE (Description) */
        min-width: 150px !important; /* Sedikit lebih kecil dari 200px */
        max-width: 250px !important; /* Batasi lebar maksimum */
    }

    /* ================================================= */
    /* ATURAN UNTUK LAYOUT HEADER (Gambar dan Department) */
    /* ================================================= */
    .header-container {
        display: flex !important; /* Menggunakan flexbox untuk tata letak */
        align-items: flex-start !important; /* Menyelaraskan item ke bagian atas (agar gambar dan teks mulai dari atas) */
        justify-content: space-between !important; /* Memisahkan gambar ke kiri dan teks ke kanan */
        width: 100% !important; /* Pastikan container mengisi lebar penuh */
        margin-bottom: 8px !important; /* Tailwind's mb-2 */
    }

    .header-container img {
        display: block !important;
        visibility: visible !important;
        width: 200px !important; /* Lebar gambar Anda */
        height: auto !important;
        margin-right: 20px !important; /* Ruang antara gambar dan area kanan */
    }

    .department-title {
        flex-grow: 1 !important; /* Memungkinkan div ini mengambil sisa ruang */
        text-align: center !important; /* Menengahkan teks di dalam div ini */
        position: absolute !important; /* Memposisikan secara absolut */
        /* Kita perlu mengetahui posisi kolom 'STANDARD COST 2025' untuk menempatkannya dengan tepat.
           Ini biasanya memerlukan penyesuaian manual berdasarkan lebar kolom.
           Misalnya, jika Anda ingin menengahkan di atas colspan 12, kita bisa menempatkan di ~25% dari kiri.
           Nilai 'left' di bawah ini adalah estimasi dan mungkin perlu disesuaikan.
           Anda harus menginspeksi di Print Preview untuk menemukan nilai 'left' yang tepat. */
        left: 300px !important; /* Mulai setelah gambar (200px) + margin kiri */
        right: 0 !important; /* Memanjang ke kanan */
        width: calc(100% - 300px) !important; /* Lebar yang tersisa setelah gambar dan margin kiri */
    }

    .department-title h2 {
        font-size: 16pt !important; /* Ukuran font lebih besar untuk judul utama */
        font-weight: bold !important;
        line-height: 1.2 !important; /* Mengurangi spasi antar baris */
        margin-bottom: 0 !important; /* Pastikan tidak ada margin bawah yang berlebihan */
        color: #333 !important; /* Warna teks yang jelas untuk print */
    }

    .department-title h3 {
        font-size: 12pt !important; /* Ukuran font untuk 'Department' */
        font-weight: normal !important;
        line-height: 1.2 !important;
        margin-top: 0 !important; /* Pastikan tidak ada margin atas yang berlebihan */
        color: #333 !important;
    }

    /* Menghilangkan margin atau padding yang tidak diinginkan dari body */
    body {
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>

<template>
    <div class="bg-white">
        <div class="header-container mb-2">
            <img src="/storage/images/topy.png" width="200px" />
            <div class="department-title">
                <h2>Finance & Accounting</h2>
                <h3>Department</h3>
            </div>
        </div>
        <table class="w-full text-xs text-slate-900">
            <thead class="bg-cyan-600">
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
                    <template v-if="category.name === 'FinishGood'">
                        <template v-for="item in category.items" :key="item.item_code">
                            <tr v-if="item.level === 0">
                                <td class="border p-2 whitespace-nowrap">{{ item.item_code }}</td>
                                <td class="border p-2 whitespace-nowrap">{{ item.description }}</td>
                                <template v-for="(period, pIndex) in item.periods_data" :key="pIndex">
                                    <td class="border p-2 text-right whitespace-nowrap">{{ period.qty }}</td>
                                    <td class="border p-2 text-right whitespace-nowrap">{{ period.price }}</td>
                                    <td class="border p-2 text-right whitespace-nowrap">{{ period.total }}</td>
                                </template>
                            </tr>

                            <template v-if="item.children && item.children.length">
                                <tr v-for="child in item.children" :key="child.description">
                                    <td class="border p-2 pl-8 whitespace-nowrap">
                                        <span class="inline-block w-8">{{ child.type }}</span>
                                    </td>
                                    <td class="border p-2 whitespace-nowrap">
                                        {{ child.description }}
                                        <span v-if="child.wip_info">{{ child.wip_info }}</span>

                                        <div
                                            v-if="child.type === 'PR' && child.lines && child.lines.length > 0"
                                            class="mt-1 ml-2 text-orange-500 italic"
                                        >
                                            <div v-for="(line, lineIndex) in child.lines" :key="lineIndex" class="flex justify-between">
                                                <div>
                                                    <span class="inline-block w-20">{{ line.name }}</span>
                                                </div>
                                                <span v-if="line.cycle_time">{{ line.cycle_time }}</span>
                                            </div>
                                        </div>
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

                    <template v-if="category.name === 'Topcoat'">
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

                                        <div
                                            v-if="child.type === 'PR' && child.lines && child.lines.length > 0"
                                            class="mt-1 ml-2 text-sm text-orange-500 italic"
                                        >
                                            <div v-for="(line, lineIndex) in child.lines" :key="lineIndex" class="flex justify-between">
                                                <div>
                                                    <span class="inline-block w-20">{{ line.name }}</span>
                                                    <span class="ml-2">{{ line.percentage }}</span>
                                                </div>
                                                <span v-if="line.cycle_time">{{ line.cycle_time }}</span>
                                            </div>
                                        </div>
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

                    <template v-if="category.name === 'CED'">
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

                                        <div
                                            v-if="child.type === 'PR' && child.lines && child.lines.length > 0"
                                            class="mt-1 ml-2 text-sm text-orange-500 italic"
                                        >
                                            <div v-for="(line, lineIndex) in child.lines" :key="lineIndex" class="flex justify-between">
                                                <div>
                                                    <span class="inline-block w-20">{{ line.name }}</span>
                                                    <span class="ml-2">{{ line.percentage }}</span>
                                                </div>
                                                <span v-if="line.cycle_time">{{ line.cycle_time }}</span>
                                            </div>
                                        </div>
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

                                        <div
                                            v-if="child.type === 'PR' && child.lines && child.lines.length > 0"
                                            class="mt-1 ml-2 text-sm text-orange-500 italic"
                                        >
                                            <div v-for="(line, lineIndex) in child.lines" :key="lineIndex" class="flex justify-between">
                                                <div>
                                                    <span class="inline-block w-20">{{ line.name }}</span>
                                                    <span class="ml-2">{{ line.percentage }}</span>
                                                </div>
                                                <span v-if="line.cycle_time">{{ line.cycle_time }}</span>
                                            </div>
                                        </div>
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

                                        <div
                                            v-if="child.type === 'PR' && child.lines && child.lines.length > 0"
                                            class="mt-1 ml-2 text-sm text-orange-500 italic"
                                        >
                                            <div v-for="(line, lineIndex) in child.lines" :key="lineIndex" class="flex justify-between">
                                                <div>
                                                    <span class="inline-block w-20">{{ line.name }}</span>
                                                    <span class="ml-2">{{ line.percentage }}</span>
                                                </div>
                                                <span v-if="line.cycle_time">{{ line.cycle_time }}</span>
                                            </div>
                                        </div>
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
            <tfoot class="bg-cyan-200">
                <tr>
                    <td class="border p-2 text-right font-bold">Total Cycle Time:</td>
                    <td class="border p-2 text-right font-bold">{{ totalCT }}</td>
                    <td colspan="11" class="border p-2 text-left font-bold">Total Manufacturing Cost (Total dari FG):</td>
                    <td class="border p-2 text-right font-bold">{{ mfgCost }}</td>
                </tr>
                <tr></tr>
            </tfoot>
        </table>
    </div>
</template>
