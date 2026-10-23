<template>
    <!-- Hlavní obrazovky se spodním tab barem (Deník, Nastavení; později Kalendář, Statistiky).
         Při přepínání mezi nimi zůstává layout i tab bar a mění se jen stránka. -->
    <div>
        <!-- Vlastní obal stránky: přechod out-in jinak vloží novou stránku až za tab bar (špatná kotva) -->
        <div>
            <slot />
        </div>
        <AppTabBar />
    </div>
</template>
