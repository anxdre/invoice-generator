<script setup lang="ts">

import { CardContent, CardFooter, CardHeader, CardTitle } from "@/Components/ui/card";
import { ArrowLeft } from "lucide-vue-next";
import { Button } from "@/Components/ui/button";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "../../Components/ui/card/Card.vue";
import { Head } from "@inertiajs/vue3";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
</script>

<template>
    <Head title="Detail Invoice"/>

    <AuthenticatedLayout class="relative">
        <template #header>
            <div class="inline-flex gap-4 items-center">
                <Button class="hover:bg-gray-200" variant="outline">
                    <ArrowLeft class="size-5"/>
                </Button>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New Invoice</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-4 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>
                            <div class="w-full inline-flex justify-between items-center">
                                <span>Logo</span>
                                <div class="flex flex-col gap-2">
                                    <div
                                        class="w-full mt-6 mr-6">
                                        <p class="font-bold text-3xl">INVOICE</p>
                                    </div>
                                    <span>Invoice Number :</span>
                                    <span>Date :</span>
                                </div>
                            </div>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="overflow-scroll">
                        <div class=" outline-dashed h-1 rounded-full mx-6"></div>
                        <div class="flex flex-col w-full mt-4">
                            <div class="inline-flex w-full justify-between items-center">
                                <div class="flex flex-col w-1/3 px-4">
                                    <div class=" bg-black">
                                        <p class="font-bold text-xl text-center text-white">From</p>
                                    </div>
                                    <div class="px-2">
                                        <p class="font-medium text-lg">{{ $attrs.auth.user.name }}</p>
                                        <p>{{ $attrs.auth.user.address }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col w-1/3 px-4">
                                    <div class=" bg-black">
                                        <p class="font-bold text-xl text-center text-white">Billed To</p>
                                    </div>
                                    <div class="px-2">
                                        <p class="font-medium text-lg">{{ $attrs.auth.user.name }}</p>
                                        <p>{{ $attrs.auth.user.address }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col w-full mt-12 px-4">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow class="bg-black">
                                        <TableHead class="w-[100px] text-white">
                                            No
                                        </TableHead>
                                        <TableHead class="text-white">Name</TableHead>
                                        <TableHead class="text-white">Code</TableHead>
                                        <TableHead class="text-white">Price</TableHead>
                                        <TableHead class="text-white text-center">
                                            Amount
                                        </TableHead>
                                        <TableHead class="text-white text-center">
                                            Total
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow>
                                        <TableCell class="text-center font-medium">
                                            1
                                        </TableCell>
                                        <TableCell>
                                            dasdasd
                                        </TableCell>
                                        <TableCell>INV001</TableCell>
                                        <TableCell>$250.00</TableCell>
                                        <TableCell class="text-center">
                                            1
                                        </TableCell>
                                        <TableCell class="justify-center gap-2 inline-flex w-full ">
                                            $250.00
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                    <CardFooter>
                <span
                    class="text-sm font-light">*This invoice generated by system, Manual signature is not necessary</span>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
