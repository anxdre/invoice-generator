<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { Button } from "@/Components/ui/button";
import axios from "axios";
import { toast } from "vue-sonner";

defineProps<{
    mustVerifyEmail?: Boolean;
    status?: String;
}>();

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    img_url: user.img_url,
    img_file: undefined
});

const submit = () => {
    form.transform((data) => {
        return {
            name: data.name,
            email: data.email,
            img_url: data.img_url, // hasil upload
        };
    });

    form.patch(route('profile.update'));
};

const handleFile = (e) => {
    form.img_file = e.target.files[0];

    // preview sebelum upload
    form.img_url = URL.createObjectURL( form.img_file);
};

const uploadImage = async () => {
    if (!form.img_file) {
        toast.warning('Foto belum dipilih')
        return;
    }

    const formData = new FormData();
    formData.append("image", form.img_file);

    try {
        const res = await axios.post(route('picture.upload'), formData, {
            headers: { "Content-Type": "multipart/form-data" }
        });

        // pakai URL dari server
        form.img_url = res.data.url;

        console.log("Foto profil:", res.data.url);
    } catch (err) {
        toast.error('gambar gagal di upload')
    }
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Profile Information</h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information and email address.
            </p>
        </header>

        <div>
            <img
                :src="form.img_url"
                alt="Foto Profil"
                style="aspect-ratio: 1/1;width: 200px; border:1px solid #ccc"
            />
            <div class="border mt-2 p-2">
                <input type="file" @change="handleFile">
                <Button type="button" class="bg-black text-white" @click="uploadImage">Upload Foto Profil</Button>
            </div>
        </div>

        <form @submit.prevent="submit()" class="mt-6 space-y-6">
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="text-sm mt-2 text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 font-medium text-sm text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
