<script setup lang="ts">
import { useEditor, EditorContent } from "@tiptap/vue-3"
import StarterKit from "@tiptap/starter-kit"
import Underline from "@tiptap/extension-underline"
import {
    Bold,
    Italic,
    Underline as UnderlineIcon,
    Strikethrough,
    Heading1,
    Heading2,
    Heading3,
    List,
    ListOrdered,
    Quote,
    Undo2,
    Redo2,
    RemoveFormatting,
} from "lucide-vue-next"
import { onBeforeUnmount, watch } from "vue"

const props = defineProps<{ modelValue?: string }>()
const emit = defineEmits<{ (e: "update:modelValue", value: string): void }>()

const editor = useEditor({
    content: props.modelValue || "",
    extensions: [
        StarterKit,
        Underline,
    ],
    onUpdate: ({ editor }) => {
        emit("update:modelValue", editor.getHTML())
    },
    editorProps: {
        attributes: {
            class: "focus:outline-none",
        },
    },
})

watch(() => props.modelValue, (val) => {
    if (editor.value && val !== editor.value.getHTML()) {
        editor.value.commands.setContent(val || "", false)
    }
})

onBeforeUnmount(() => {
    editor.value?.destroy()
})

function isActive(name: string, attributes?: object) {
    return !!editor.value?.isActive(name, attributes)
}

function run(command: string, attributes?: object) {
    const args = attributes ? [attributes] : []
    editor.value?.chain().focus()[command](...args).run()
}
</script>

<template>
    <div class="border border-gray-300 rounded-md overflow-hidden bg-white">
        <div class="flex flex-wrap gap-0.5 p-1.5 border-b border-gray-200 bg-gray-50">
            <button type="button" class="p-1.5 rounded hover:bg-gray-200 disabled:opacity-40"
                    :class="{ 'bg-gray-300': isActive('bold') }"
                    title="Bold" @mousedown.prevent @click="run('toggleBold')">
                <Bold class="size-4"/>
            </button>
            <button type="button" class="p-1.5 rounded hover:bg-gray-200 disabled:opacity-40"
                    :class="{ 'bg-gray-300': isActive('italic') }"
                    title="Italic" @mousedown.prevent @click="run('toggleItalic')">
                <Italic class="size-4"/>
            </button>
            <button type="button" class="p-1.5 rounded hover:bg-gray-200 disabled:opacity-40"
                    :class="{ 'bg-gray-300': isActive('underline') }"
                    title="Underline" @mousedown.prevent @click="run('toggleUnderline')">
                <UnderlineIcon class="size-4"/>
            </button>
            <button type="button" class="p-1.5 rounded hover:bg-gray-200 disabled:opacity-40"
                    :class="{ 'bg-gray-300': isActive('strike') }"
                    title="Strikethrough" @mousedown.prevent @click="run('toggleStrike')">
                <Strikethrough class="size-4"/>
            </button>
            <span class="w-px bg-gray-300 mx-1"></span>
            <button type="button" class="p-1.5 rounded hover:bg-gray-200 disabled:opacity-40"
                    :class="{ 'bg-gray-300': isActive('heading', { level: 1 }) }"
                    title="Heading 1" @mousedown.prevent @click="run('toggleHeading', { level: 1 })">
                <Heading1 class="size-4"/>
            </button>
            <button type="button" class="p-1.5 rounded hover:bg-gray-200 disabled:opacity-40"
                    :class="{ 'bg-gray-300': isActive('heading', { level: 2 }) }"
                    title="Heading 2" @mousedown.prevent @click="run('toggleHeading', { level: 2 })">
                <Heading2 class="size-4"/>
            </button>
            <button type="button" class="p-1.5 rounded hover:bg-gray-200 disabled:opacity-40"
                    :class="{ 'bg-gray-300': isActive('heading', { level: 3 }) }"
                    title="Heading 3" @mousedown.prevent @click="run('toggleHeading', { level: 3 })">
                <Heading3 class="size-4"/>
            </button>
            <span class="w-px bg-gray-300 mx-1"></span>
            <button type="button" class="p-1.5 rounded hover:bg-gray-200 disabled:opacity-40"
                    :class="{ 'bg-gray-300': isActive('bulletList') }"
                    title="Bullet List" @mousedown.prevent @click="run('toggleBulletList')">
                <List class="size-4"/>
            </button>
            <button type="button" class="p-1.5 rounded hover:bg-gray-200 disabled:opacity-40"
                    :class="{ 'bg-gray-300': isActive('orderedList') }"
                    title="Numbered List" @mousedown.prevent @click="run('toggleOrderedList')">
                <ListOrdered class="size-4"/>
            </button>
            <button type="button" class="p-1.5 rounded hover:bg-gray-200 disabled:opacity-40"
                    :class="{ 'bg-gray-300': isActive('blockquote') }"
                    title="Quote" @mousedown.prevent @click="run('toggleBlockquote')">
                <Quote class="size-4"/>
            </button>
            <span class="w-px bg-gray-300 mx-1"></span>
            <button type="button" class="p-1.5 rounded hover:bg-gray-200 disabled:opacity-40"
                    title="Clear Formatting" @mousedown.prevent @click="run('unsetAllMarks')">
                <RemoveFormatting class="size-4"/>
            </button>
            <button type="button" class="p-1.5 rounded hover:bg-gray-200 disabled:opacity-40"
                    title="Undo" @mousedown.prevent @click="run('undo')">
                <Undo2 class="size-4"/>
            </button>
            <button type="button" class="p-1.5 rounded hover:bg-gray-200 disabled:opacity-40"
                    title="Redo" @mousedown.prevent @click="run('redo')">
                <Redo2 class="size-4"/>
            </button>
        </div>
        <EditorContent :editor="editor" class="rich-text p-3 min-h-28 text-sm"/>
    </div>
</template>

<style scoped>
.rich-text :deep(.ProseMirror) {
    outline: none;
    min-height: 7rem;
    line-height: 1.6;
}
.rich-text :deep(.ProseMirror p) {
    margin: 0.5em 0;
}
.rich-text :deep(.ProseMirror ul),
.rich-text :deep(.ProseMirror ol) {
    padding-left: 1.5rem;
    margin: 0.5em 0;
}
.rich-text :deep(.ProseMirror blockquote) {
    border-left: 3px solid #d1d5db;
    padding-left: 0.75rem;
    color: #4b5563;
    margin: 0.5em 0;
}
.rich-text :deep(.ProseMirror h1) {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0.5em 0;
}
.rich-text :deep(.ProseMirror h2) {
    font-size: 1.25rem;
    font-weight: bold;
    margin: 0.5em 0;
}
.rich-text :deep(.ProseMirror h3) {
    font-size: 1.1rem;
    font-weight: bold;
    margin: 0.5em 0;
}
.rich-text :deep(.ProseMirror u) {
    text-decoration: underline;
}
</style>
