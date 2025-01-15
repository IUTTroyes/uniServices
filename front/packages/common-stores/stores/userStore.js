import { defineStore } from 'pinia';
import api from '@helpers/axios';
import { getEtudiantScolariteActif } from "@requests";

export const useUsersStore = defineStore('users', {
    state: () => ({
        token: localStorage.getItem('token'),
        payload: localStorage.getItem('token') ? JSON.parse(atob(localStorage.getItem('token').split('.')[1])) : {},
        userId: localStorage.getItem('token') ? JSON.parse(atob(localStorage.getItem('token').split('.')[1])).userId : null,
        userType: localStorage.getItem('token') ? JSON.parse(atob(localStorage.getItem('token').split('.')[1])).type : null,
        applications: [],
        user: [],
        userPhoto: [],
        departements: [],
        departementDefaut: {},
        departementPersonnelDefaut: {},
        departementsNotDefaut: {},
        departementsPersonnelNotDefaut: {},
        statuts: [],
        scolariteActif: {}
    }),
    actions: {
        async getUser() {
            if (this.user && this.user.length > 0) {
                console.log('getUser called but user already present');
                console.log(this.user);
                // Les données de l'utilisateur sont déjà présentes, pas besoin de refaire l'appel API
                return;
            }
            try {
                console.log('getUser called');
                const response = await api.get(`/api/${this.userType}/${this.userId}`);
                this.userPhoto = `http://localhost:3001/intranet/src/assets/photos_etudiants/${response.data.photoName}`;
                this.user = response.data;
                this.applications = response.data.applications;

                if (this.userType === 'personnels') {
                    this.departements = response.data.structureDepartementPersonnels;

                    if (!this.departements.find(departement => departement.defaut)) {
                        const postResponse = await api.post(`/api/structure_departement_personnels/${this.departements[0].id}/change_departement`, {}, {
                            headers: { 'Content-Type': 'application/ld+json' }
                        });
                        this.departements = postResponse.data;
                    }

                    this.departementPersonnelDefaut = this.departements.find(departement => departement.defaut);
                    this.departementDefaut = this.departementPersonnelDefaut.departement;
                    localStorage.setItem('departement', this.departementDefaut.id);
                    this.departementsPersonnelNotDefaut = this.departements.filter(departement => !departement.defaut);
                    this.departementsNotDefaut = this.departementsPersonnelNotDefaut.map(departement => departement.departement);
                }

                if (this.userType === 'etudiants') {
                    console.log(this.user);
                    const scolariteActifData = await getEtudiantScolariteActif(this.userId);
                    this.scolariteActif = scolariteActifData[0];
                    this.departementDefaut = this.scolariteActif.departement;
                    this.user.departement = this.departementDefaut;
                }
            } catch (error) {
                console.error('Error fetching user:', error);
            }
        },
        async getStatuts() {
            try {
                const response = await api.get(`/api/statuts`);
                this.statuts = response.data;
            } catch (error) {
                console.error('Error fetching statuts:', error);
            }
        },
        async changeDepartement(departementId) {
            try {
                const departementPersonnelId = this.departements.find(departement => departement.departement.id === departementId).id;
                const response = await api.post(`/api/structure_departement_personnels/${departementPersonnelId}/change_departement`, {}, {
                    headers: { 'Content-Type': 'application/ld+json' }
                });
                this.departements = response.data;

                this.departementPersonnelDefaut = this.departements.find(departement => departement.defaut);
                this.departementDefaut = this.departementPersonnelDefaut.departement;
                localStorage.setItem('departement', this.departementDefaut.id);
                this.departementsPersonnelNotDefaut = this.departements.filter(departement => !departement.defaut);
                this.departementsNotDefaut = this.departementsPersonnelNotDefaut.map(departement => departement.departement);
            } catch (error) {
                console.error('Error changing department:', error);
            }
        },
        async updateUser(data) {
            if (!Array.isArray(data.domaines)) {
                data.domaines = data.domaines.split(',');
            }
            if (data.structureDepartementPersonnels) {
                data.structureDepartementPersonnels = data.structureDepartementPersonnels.map(departement => `/api/structure_departement_personnels/${departement.id}`);
            }
            data.photoName = data.photoName.substring(data.photoName.lastIndexOf('/') + 1);
            console.log(data.photoName);

            try {
                const response = await api.patch(`/api/${this.userType}/${this.userId}`, data, {
                    headers: { 'Content-Type': 'application/merge-patch+json' }
                });
                this.user = response.data;
            } catch (error) {
                console.error('Error updating user:', error);
            }
        }
    }
});
