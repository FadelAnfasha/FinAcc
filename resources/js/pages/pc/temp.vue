<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Process Cost" />

    <AppLayout>
        <div class="p-6">
            <div class="flex flex-col gap-1">
                <h2 class="mb-2 text-start text-3xl font-bold text-gray-900 dark:text-white">Process Cost Calculation</h2>
                <p class="text-start text-gray-600 dark:text-gray-400">Calculation for each process for all product</p>
            </div>
            <!-- Header Section -->
            <div class="mb-8">
                <div class="relative mb-6 text-center">
                    <h1 class="relative z-10 inline-block bg-white px-4 text-2xl font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        Master Data Section
                    </h1>
                    <hr class="absolute top-1/2 left-0 z-0 w-full -translate-y-1/2 border-gray-300 dark:border-gray-600" />
                </div>
            </div>

            <!-- Process Items Grid -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <!-- Business Partner -->
                <a
                    href="/bps"
                    class="group relative flex h-full items-center justify-start overflow-hidden rounded-lg border-2 border-green-200 bg-white p-4 transition-all duration-300 hover:border-green-400 hover:bg-green-50 dark:border-green-700 dark:bg-gray-800 dark:hover:border-green-500 dark:hover:bg-green-900/20"
                    title="Manage Business Partners data (CA0002, CA0003, etc)"
                >
                    <div class="mr-4 flex-shrink-0">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-blue-600">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <span
                        class="font-medium text-gray-800 transition-colors group-hover:text-green-700 dark:text-gray-200 dark:group-hover:text-green-300"
                    >
                        1. Business Partner
                    </span>
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-green-500/0 to-green-500/5 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                    ></div>
                </a>

                <!-- Cycle Time -->
                <a
                    href="/cycletimes"
                    class="group relative flex h-full items-center justify-start overflow-hidden rounded-lg border-2 border-green-200 bg-white p-4 transition-all duration-300 hover:border-green-400 hover:bg-green-50 dark:border-green-700 dark:bg-gray-800 dark:hover:border-green-500 dark:hover:bg-green-900/20"
                    title="Store Cycle Time data of each product (F15W01, F15W02, etc)"
                >
                    <div class="mr-4 flex-shrink-0">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-purple-500 to-purple-600">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <span
                        class="font-medium text-gray-800 transition-colors group-hover:text-green-700 dark:text-gray-200 dark:group-hover:text-green-300"
                    >
                        2. Cycle Time
                    </span>
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-green-500/0 to-green-500/5 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                    ></div>
                </a>

                <!-- Sales Quantity -->
                <a
                    href="/salesqtys"
                    class="group relative flex h-full items-center justify-start overflow-hidden rounded-lg border-2 border-green-200 bg-white p-4 transition-all duration-300 hover:border-green-400 hover:bg-green-50 dark:border-green-700 dark:bg-gray-800 dark:hover:border-green-500 dark:hover:bg-green-900/20"
                    title="Store Sales Quantity data of each product and business partner that order the product"
                >
                    <div class="mr-4 flex-shrink-0">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-orange-600">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                        </div>
                    </div>
                    <span
                        class="font-medium text-gray-800 transition-colors group-hover:text-green-700 dark:text-gray-200 dark:group-hover:text-green-300"
                    >
                        3. Sales Quantity
                    </span>
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-green-500/0 to-green-500/5 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                    ></div>
                </a>

                <!-- Wages Distribution -->
                <a
                    href="/wds"
                    class="group relative flex h-full items-center justify-start overflow-hidden rounded-lg border-2 border-green-200 bg-white p-4 transition-all duration-300 hover:border-green-400 hover:bg-green-50 dark:border-green-700 dark:bg-gray-800 dark:hover:border-green-500 dark:hover:bg-green-900/20"
                    title="Manage wage distribution"
                >
                    <div class="mr-4 flex-shrink-0">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-green-500 to-green-600">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <span
                        class="font-medium text-gray-800 transition-colors group-hover:text-green-700 dark:text-gray-200 dark:group-hover:text-green-300"
                    >
                        4. Wages Distribution
                    </span>
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-green-500/0 to-green-500/5 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                    ></div>
                </a>
            </div>
        </div>

        <div class="p-6">
            <!-- Header Section -->
            <div class="mb-8">
                <div class="relative mb-6 text-center">
                    <h1 class="relative z-10 inline-block bg-white px-4 text-2xl font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        Report Section
                    </h1>
                    <hr class="absolute top-1/2 left-0 z-0 w-full -translate-y-1/2 border-gray-300 dark:border-gray-600" />
                </div>
            </div>

            <!-- Process Items Grid -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <!-- Cycle Time x Sales Quantity -->
                <a
                    href="/bps"
                    class="group relative flex h-full items-center justify-start overflow-hidden rounded-lg border-2 border-green-200 bg-white p-4 transition-all duration-300 hover:border-green-400 hover:bg-green-50 dark:border-green-700 dark:bg-gray-800 dark:hover:border-green-500 dark:hover:bg-green-900/20"
                    title="Calculate cycle time x sales quantity"
                >
                    <div class="mr-4 flex-shrink-0">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-blue-600">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <span
                        class="font-medium text-gray-800 transition-colors group-hover:text-green-700 dark:text-gray-200 dark:group-hover:text-green-300"
                    >
                        1. Cycle Time x Sales Quantity
                    </span>
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-green-500/0 to-green-500/5 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                    ></div>
                </a>

                <!-- Base Cost -->
                <a
                    href="/bps"
                    class="group relative flex h-full items-center justify-start overflow-hidden rounded-lg border-2 border-green-200 bg-white p-4 transition-all duration-300 hover:border-green-400 hover:bg-green-50 dark:border-green-700 dark:bg-gray-800 dark:hover:border-green-500 dark:hover:bg-green-900/20"
                    title="Review base cost analysis"
                >
                    <div class="mr-4 flex-shrink-0">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-orange-600">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"
                                />
                            </svg>
                        </div>
                    </div>
                    <span
                        class="font-medium text-gray-800 transition-colors group-hover:text-green-700 dark:text-gray-200 dark:group-hover:text-green-300"
                    >
                        2. Base Cost
                    </span>
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-green-500/0 to-green-500/5 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                    ></div>
                </a>

                <!-- Cost per Process -->
                <a
                    href="/bps"
                    class="group relative flex h-full items-center justify-start overflow-hidden rounded-lg border-2 border-green-200 bg-white p-4 transition-all duration-300 hover:border-green-400 hover:bg-green-50 dark:border-green-700 dark:bg-gray-800 dark:hover:border-green-500 dark:hover:bg-green-900/20"
                    title="Calculate cost per process"
                >
                    <div class="mr-4 flex-shrink-0">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-purple-500 to-purple-600">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                    </div>
                    <span
                        class="font-medium text-gray-800 transition-colors group-hover:text-green-700 dark:text-gray-200 dark:group-hover:text-green-300"
                    >
                        3. Cost per Process
                    </span>
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-green-500/0 to-green-500/5 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                    ></div>
                </a>

                <!-- Process Cost -->
                <a
                    href="/bps"
                    class="group relative flex h-full items-center justify-start overflow-hidden rounded-lg border-2 border-green-200 bg-white p-4 transition-all duration-300 hover:border-green-400 hover:bg-green-50 dark:border-green-700 dark:bg-gray-800 dark:hover:border-green-500 dark:hover:bg-green-900/20"
                    title="Generate process cost reports"
                >
                    <div class="mr-4 flex-shrink-0">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-green-500 to-green-600">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H4a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                        </div>
                    </div>
                    <span
                        class="font-medium text-gray-800 transition-colors group-hover:text-green-700 dark:text-gray-200 dark:group-hover:text-green-300"
                    >
                        4. Process Cost
                    </span>
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-green-500/0 to-green-500/5 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                    ></div>
                </a>
            </div>
        </div>
    </AppLayout>
</template>
