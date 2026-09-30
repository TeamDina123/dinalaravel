<template>
    <div class="workout-cover">
        <label class="d-block mb-0">
            <span class="d-block text-muted mb-2">Cover image (optional)</span>
            <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp"
                class="form-control" @change="selectImage">
        </label>
        <small class="text-muted d-block mt-1">JPG, PNG or WebP, up to 5 MB.</small>
        <p v-if="error" class="text-danger small mt-1 mb-0" role="alert">{{ error }}</p>
        <div v-if="preview || existingImage" class="d-flex align-items-center flex-wrap gap-3 mt-2">
            <img :src="preview || existingImage" alt="Workout cover preview" class="cover-preview">
            <div v-if="modelValue" class="cover-selection">
                <p class="small mb-1 text-break">{{ modelValue.name }}</p>
                <button type="button" class="scnd_btn brds-2 px-3 py-1" @click="clearSelection">
                    {{ existingImage ? 'Undo change' : 'Remove selected image' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        modelValue: { default: null },
        existingImage: { type: String, default: '' },
    },
    emits: ['update:modelValue'],
    data() {
        return { preview: '', error: '' };
    },
    watch: {
        modelValue: {
            immediate: true,
            handler(file) {
                if (this.preview) URL.revokeObjectURL(this.preview);
                this.preview = file ? URL.createObjectURL(file) : '';
            },
        },
    },
    beforeUnmount() {
        if (this.preview) URL.revokeObjectURL(this.preview);
    },
    methods: {
        selectImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                this.error = 'Please choose a JPG, PNG or WebP image.';
            } else if (file.size > 5 * 1024 * 1024) {
                this.error = 'The cover image must be 5 MB or smaller.';
            } else {
                this.error = '';
                this.$emit('update:modelValue', file);
            }
            if (this.error) event.target.value = '';
        },
        clearSelection() {
            this.error = '';
            this.$refs.fileInput.value = '';
            this.$emit('update:modelValue', null);
        },
    },
};
</script>

<style scoped>
.workout-cover { min-width: 0; }
.cover-preview {
    width: 140px;
    height: 90px;
    object-fit: contain;
    background: #f3f3f3;
    border: 1px solid #ddd;
    border-radius: 8px;
}
.cover-selection { min-width: 0; max-width: 100%; }
</style>
