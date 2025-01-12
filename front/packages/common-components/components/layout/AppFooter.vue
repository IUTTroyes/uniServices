<script setup>
import { useVersion } from '@helpers/useVersion.js';

const { version } = useVersion();

</script>

<template>
    <div class="layout-footer">
        <div class="layout-footer-left">
            <span>&copy; 2023 UniServices</span>
        </div>
        <div class="layout-footer-right">
            <span>Version {{ version }}</span>
        </div>
    </div>
</template>
