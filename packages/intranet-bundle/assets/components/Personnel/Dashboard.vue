<script setup>
const emploiDuTemps = [
{heure: "08:00", type: "TD", cours: "Informatique S2", salle: "Salle B204", color: "blue"},
{heure: "10:00", type: "Cours", cours: "Réseaux", salle: "Amphi A", color: "green"},
{heure: "14:00", type: "TP", cours: "Linux", salle: "Salle Info 3", color: "purple"}
];

const actionsUrgentes = [
{icon: "pi pi-file", titre: "3 copies à corriger", detail: "Date limite : 22 mai 2025", cta: "Corriger", color: "red"},
{icon: "pi pi-pencil", titre: "Notes à saisir", detail: "Avant jeudi 18h", cta: "Saisir les notes", color: "orange"},
{icon: "pi pi-user", titre: "2 absences à justifier", detail: "En attente de justificatif", cta: "Voir les absences", color: "yellow"}
];

const groupes = [
{nom: "BUT1-A", etudiants: "24 étudiants"},
{nom: "BUT2-B", etudiants: "22 étudiants"},
{nom: "LP-Dev", etudiants: "18 étudiants"}
];

const avancementNotes = [
{nom: "BUT S2", matieres: "Réseaux, Info, Maths", valeur: 75},
{nom: "BUT S3", matieres: "Bases de données, Web", valeur: 50},
{nom: "LP Développement", matieres: "Projets, DevOps", valeur: 100}
];

const documentsRecents = [
{icon: "pi pi-file-pdf", titre: "Sujet TP Réseaux – VLAN", date: "14/05/2025"},
{icon: "pi pi-file-pdf", titre: "Correction DS Linux", date: "13/05/2025"},
{icon: "pi pi-file", titre: "Support de cours – TCP/IP", date: "12/05/2025"},
{icon: "pi pi-file", titre: "Énoncé Projet DevOps", date: "10/05/2025"},
{icon: "pi pi-file", titre: "Grille d'évaluation BUT S2", date: "08/05/2025"}
];

const actualites = [
{
    titre: "Portes ouvertes 2025",
    texte: "L'IUT ouvre ses portes le samedi 24 mai. Venez rencontrer nos équipes et étudiants !"
},
{
    titre: "Soutenances de projets",
    texte: "Découvrez le planning des soutenances de fin d'année."
},
{
    titre: "Enquête formation",
    texte: "Votre avis compte ! Répondez à l'enquête sur les formations avant le 31 mai."
}
];
</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-6 xl:flex-row">
            <div class="mb-0! flex-1 rounded-xl border border-surface-200 bg-surface-0 p-5">
                <div class="flex items-center gap-2">
                    <i class="pi pi-calendar text-violet-600"></i>
                    <div class="m-0 text-md! font-bold">Aujourd'hui</div>
                </div>
                <p class="mb-4 mt-0 text-color-secondary text-sm">Jeudi 15 mai 2025</p>
                
                <div class="mb-4 border-t border-surface-200">
                    <div
                    v-for="item in emploiDuTemps"
                    :key="`${item.heure}-${item.cours}`"
                    class="flex flex-wrap items-center gap-3 border-b border-surface-200 py-3"
                    >
                    <div class="w-16 font-bold text-violet-600">{{ item.heure }}</div>
                    <span
                    class="rounded-lg px-2.5 py-1 text-xs font-semibold"
                    :class="{
                        'bg-blue-100 text-blue-700': item.color === 'blue',
                        'bg-green-100 text-green-700': item.color === 'green',
                        'bg-violet-100 text-violet-700': item.color === 'purple'
                    }"
                    >
                    {{ item.type }}
                </span>
                <div class="min-w-48 flex-1 font-bold">{{ item.cours }}</div>
                <div class="flex items-center gap-1.5 text-color-secondary">
                    <i class="pi pi-map-marker"></i>
                    {{ item.salle }}
                </div>
            </div>
        </div>
        
        <button
        class="flex w-full items-center justify-center gap-2 rounded-lg border border-violet-300 bg-transparent px-4 py-2.5 font-semibold text-violet-700"
        type="button"
        >
        <i class="pi pi-calendar"></i>
        Voir l'emploi du temps
    </button>
</div>

<div class="mb-0! w-full rounded-xl border border-surface-200 bg-surface-0 p-5 xl:w-[34%] xl:min-w-88">
    <div class="mb-3 flex items-center gap-2">
        <i class="pi pi-bolt text-violet-600"></i>
        <div class="m-0 text-md! font-bold">Actions urgentes</div>
    </div>
    
    <div class="flex flex-col gap-2.5">
        <div
        v-for="action in actionsUrgentes"
        :key="action.titre"
        class="flex items-center justify-between gap-3 rounded-lg border p-2.5"
        :class="{
            'border-red-200 bg-red-50': action.color === 'red',
            'border-orange-200 bg-orange-50': action.color === 'orange',
            'border-yellow-200 bg-yellow-50': action.color === 'yellow'
        }"
        >
        <div class="flex items-center gap-3">
            <div
            class="flex h-8.5 w-8.5 items-center justify-center rounded-lg"
            :class="{
                'bg-red-100 text-red-700': action.color === 'red',
                'bg-orange-100 text-orange-700': action.color === 'orange',
                'bg-yellow-100 text-yellow-800': action.color === 'yellow'
            }"
            >
            <i :class="action.icon"></i>
        </div>
        <div>
            <p class="m-0 font-bold">{{ action.titre }}</p>
            <p class="mt-1 text-color-secondary">{{ action.detail }}</p>
        </div>
    </div>
    <button
    class="rounded-lg border border-violet-300 bg-transparent px-3 py-1.5 text-sm font-semibold text-violet-700"
    type="button"
    >
    {{ action.cta }}
</button>
</div>
</div>
</div>
</div>

<div class="flex flex-wrap gap-6">
    <div class="mb-0! min-w-[18rem] flex-1 rounded-xl border border-surface-200 bg-surface-0 p-5">
        <div class="mb-3 flex items-center gap-2">
            <i class="pi pi-users text-violet-600"></i>
            <div class="m-0 text-md! font-bold">Mes groupes</div>
        </div>
        
        <div class="flex flex-col gap-2.5">
            <div
            v-for="groupe in groupes"
            :key="groupe.nom"
            class="flex items-center justify-between rounded-lg border border-surface-200 px-3 py-2.5"
            >
            <div class="flex items-center gap-2.5">
                <div class="flex h-7.5 w-7.5 items-center justify-center rounded-full bg-violet-100 text-violet-700">
                    <i class="pi pi-users"></i>
                </div>
                <div>
                    <p class="m-0 font-bold">{{ groupe.nom }}</p>
                    <p class="mt-1 text-color-secondary">{{ groupe.etudiants }}</p>
                </div>
            </div>
            <i class="pi pi-angle-right text-color-secondary"></i>
        </div>
    </div>
    
    <button class="mt-3 inline-flex items-center gap-1.5 border-none bg-transparent p-0 font-bold text-violet-700" type="button">
        Tous mes groupes <i class="pi pi-arrow-right"></i>
    </button>
</div>

<div class="mb-0! min-w-[18rem] flex-1 rounded-xl border border-surface-200 bg-surface-0 p-5">
    <div class="mb-3 flex items-center gap-2">
        <i class="pi pi-chart-bar text-violet-600"></i>
        <div class="m-0 text-md! font-bold">Avancement des notes</div>
    </div>
    
    <div class="flex flex-col gap-3">
        <div v-for="item in avancementNotes" :key="item.nom">
            <div class="mb-1 flex items-center justify-between">
                <p class="m-0 font-bold">{{ item.nom }}</p>
                <p class="m-0 font-bold">{{ item.valeur }}%</p>
            </div>
            <p class="mt-1 text-color-secondary">{{ item.matieres }}</p>
            <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-surface-200">
                <div class="h-full rounded-full bg-violet-600" :style="{width: `${item.valeur}%`}"></div>
            </div>
        </div>
    </div>
    
    <button class="mt-3 inline-flex items-center gap-1.5 border-none bg-transparent p-0 font-bold text-violet-700" type="button">
        Voir le détail par UE <i class="pi pi-arrow-right"></i>
    </button>
</div>

<div class="mb-0! min-w-[18rem] flex-1 rounded-xl border border-surface-200 bg-surface-0 p-5">
    <div class="mb-3 flex items-center gap-2">
        <i class="pi pi-file text-violet-600"></i>
        <div class="m-0 text-md! font-bold">Documents récents</div>
    </div>
    
    <div class="flex flex-col gap-2.5">
        <div
        v-for="doc in documentsRecents"
        :key="doc.titre"
        class="flex items-center justify-between rounded-lg border border-surface-200 px-3 py-2.5"
        >
        <div class="flex items-center gap-2.5">
            <i :class="[doc.icon, 'text-red-400']"></i>
            <p class="m-0">{{ doc.titre }}</p>
        </div>
        <p class="m-0 text-sm text-color-secondary">{{ doc.date }}</p>
    </div>
</div>

<button class="mt-3 inline-flex items-center gap-1.5 border-none bg-transparent p-0 font-bold text-violet-700" type="button">
    Voir tous les documents <i class="pi pi-arrow-right"></i>
</button>
</div>
</div>

<div class="mb-0! rounded-xl border border-surface-200 bg-surface-0 p-5">
    <div class="mb-3 flex items-center gap-2">
        <i class="pi pi-megaphone text-violet-600"></i>
        <div class="m-0 text-md! font-bold">Actualités IUT</div>
    </div>
    
    <div class="flex flex-wrap gap-6">
        <div
        v-for="news in actualites"
        :key="news.titre"
        class="min-w-[16rem] flex-1 overflow-hidden rounded-lg border border-surface-200"
        >
        <div class="h-24 bg-linear-to-br from-violet-100 to-violet-50"></div>
        <div class="p-3">
            <p class="m-0 font-bold">{{ news.titre }}</p>
            <p class="mt-1 text-color-secondary">{{ news.texte }}</p>
            <button
            class="mt-3 rounded-lg border border-violet-300 bg-transparent px-3 py-1.5 text-sm font-semibold text-violet-700"
            type="button"
            >
            Lire
        </button>
    </div>
</div>
</div>
</div>
</div>
</template>
