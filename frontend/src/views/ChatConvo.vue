<template>

    <div class="layout">
        <SidebarStudent></SidebarStudent>

        <main>
            <HeaderStudent></HeaderStudent>

            <div class="main-container">
                <div class="title-container">
                    <h1 class="main-title">What's going on your mind?</h1>
                </div>

                <div class="main-buttons">
                    <button class="sugguestion-buttons">I’m feeling stressed, what would you recommend to calm down?</button>
                    <button class="sugguestion-buttons">I’m feeling stressed, what would you recommend to calm down?</button>
                    <button class="sugguestion-buttons">I’m feeling stressed, what would you recommend to calm down?</button>
                </div>
            </div>

            <SendChat></SendChat>
        </main>
    </div>
</template>

<script setup>
import SidebarStudent from '@/components/sidebar.vue';
import HeaderStudent from '@/components/header.vue';
import SendChat from '@/components/SendChat.vue';
</script>

<style scoped src="../assets/users/ChatConvo.css"></style>
