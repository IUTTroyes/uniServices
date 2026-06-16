<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-6 transition-colors duration-300">
    <!-- Header Block -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
      <div>
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
          <AcademicCapIcon class="w-8 h-8 text-primary-500" />
          Portail Évaluation & Qualité
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
          Pilotez, répondez et analysez les enquêtes d'enseignement
        </p>
      </div>

      <!-- Role Selector Tabs -->
      <div class="bg-gray-200/80 dark:bg-gray-800/80 backdrop-blur-md p-1 rounded-xl flex gap-1 self-start shadow-inner border border-gray-300/30 dark:border-gray-700/30">
        <button
          v-for="role in roles"
          :key="role.id"
          @click="activeRole = role.id"
          :class="[
            'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200',
            activeRole === role.id
              ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-white shadow-md scale-102'
              : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
          ]"
        >
          <component :is="role.icon" class="w-4 h-4" />
          {{ role.label }}
        </button>
      </div>
    </div>

    <!-- Active View Area -->
    <Transition name="fade" mode="out-in">
      <!-- 1. STUDENT VIEW -->
      <div v-if="activeRole === 'student'" class="space-y-8">
        <!-- Banner Info -->
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-2xl p-6 shadow-xl relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6">
          <div class="z-10">
            <span class="bg-white/20 text-white text-xs font-semibold px-2.5 py-1 rounded-full uppercase tracking-wider">Session Active</span>
            <h2 class="text-2xl font-bold mt-2">Votre avis compte pour améliorer nos formations !</h2>
            <p class="text-blue-100 mt-1 max-w-xl">
              Prenez quelques minutes pour évaluer vos enseignements. Vos réponses sont totalement anonymes et permettent d'adapter les cours et équipements.
            </p>
          </div>
          <div class="flex items-center gap-4 z-10 shrink-0">
            <div class="text-center bg-white/10 px-4 py-3 rounded-xl backdrop-blur-md">
              <p class="text-2xl font-bold">{{ studentPendingSurveys.length }}</p>
              <p class="text-xs text-blue-100">À remplir</p>
            </div>
            <div class="text-center bg-white/10 px-4 py-3 rounded-xl backdrop-blur-md">
              <p class="text-2xl font-bold">{{ studentCompletedSurveys.length }}</p>
              <p class="text-xs text-blue-100">Complétés</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Pending Surveys -->
          <div class="lg:col-span-2 space-y-4">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
              <ClockIcon class="w-5 h-5 text-orange-500" />
              Questionnaires en attente
            </h3>
            
            <div v-if="studentPendingSurveys.length === 0" class="card text-center py-8">
              <CheckCircleIcon class="w-12 h-12 text-green-500 mx-auto mb-2" />
              <p class="text-gray-700 dark:text-gray-300 font-medium">Félicitations, vous êtes à jour !</p>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Aucun questionnaire en attente.</p>
            </div>

            <div class="space-y-4">
              <div 
                v-for="survey in studentPendingSurveys" 
                :key="survey.uuid"
                class="card hover:shadow-lg transition-shadow border-l-4 border-orange-500 flex flex-col md:flex-row md:items-center justify-between p-5 gap-4"
              >
                <div>
                  <h4 class="font-bold text-gray-900 dark:text-white text-lg">{{ survey.title }}</h4>
                  <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">{{ survey.description }}</p>
                  <div class="flex items-center gap-4 mt-3 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400">
                      <ClockIcon class="w-3.5 h-3.5" /> Est. {{ survey.duration }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-500">
                      <ExclamationCircleIcon class="w-3.5 h-3.5" /> Clôture : {{ survey.deadline }}
                    </span>
                  </div>
                </div>
                <router-link 
                  :to="{ name: 'questionnaire_take-survey', params: { token: survey.token } }"
                  class="btn-primary flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl self-start md:self-auto shrink-0"
                >
                  Répondre
                  <ArrowRightIcon class="w-4 h-4" />
                </router-link>
              </div>
            </div>

            <!-- Answered Surveys -->
            <div class="pt-6 space-y-4">
              <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <CheckCircleIcon class="w-5 h-5 text-green-500" />
                Questionnaires répondus
              </h3>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div 
                  v-for="survey in studentCompletedSurveys" 
                  :key="survey.uuid"
                  class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-750/30 transition-colors"
                >
                  <div class="truncate">
                    <h4 class="font-bold text-gray-900 dark:text-white truncate">{{ survey.title }}</h4>
                    <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">Soumis le {{ survey.submittedAt }}</p>
                  </div>
                  <span class="bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-300 text-xs px-2.5 py-1 rounded-full font-bold flex items-center gap-1 shrink-0">
                    <CheckIcon class="w-3.5 h-3.5" /> Complété
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Published Analytics / Actions -->
          <div class="space-y-4">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
              <ChartBarIcon class="w-5 h-5 text-purple-500" />
              Retours & Actions
            </h3>
            
            <div class="space-y-4">
              <div 
                v-for="analysis in publishedAnalytics" 
                :key="analysis.id"
                class="card overflow-hidden hover:scale-101 transition-transform border border-gray-200 dark:border-gray-700 shadow-sm"
              >
                <div class="p-5 border-b border-gray-150 dark:border-gray-700 bg-gray-55 dark:bg-gray-800/40">
                  <span class="text-xs text-purple-600 dark:text-purple-400 font-bold tracking-wider uppercase bg-purple-100 dark:bg-purple-950 px-2 py-0.5 rounded">Rapport de Synthèse</span>
                  <h4 class="font-bold text-gray-900 dark:text-white mt-2">{{ analysis.title }}</h4>
                  <p class="text-gray-500 dark:text-gray-400 text-xs mt-0.5">Publié le {{ analysis.date }}</p>
                </div>
                
                <div class="p-5 space-y-4">
                  <!-- Custom Mini Stat Cards -->
                  <div class="grid grid-cols-2 gap-2">
                    <div class="bg-gray-100 dark:bg-gray-700 p-2.5 rounded-lg text-center">
                      <p class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ analysis.stats.satisfaction }}%</p>
                      <p class="text-2xs text-gray-600 dark:text-gray-400 uppercase tracking-tight">Satisfaction globale</p>
                    </div>
                    <div class="bg-gray-100 dark:bg-gray-700 p-2.5 rounded-lg text-center">
                      <p class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ analysis.stats.participation }}%</p>
                      <p class="text-2xs text-gray-600 dark:text-gray-400 uppercase tracking-tight">Participation</p>
                    </div>
                  </div>

                  <!-- Actions Decided -->
                  <div>
                    <h5 class="text-xs font-bold text-gray-750 dark:text-gray-300 uppercase tracking-wider mb-2">Actions décidées :</h5>
                    <ul class="space-y-2">
                      <li 
                        v-for="(action, idx) in analysis.actions" 
                        :key="idx" 
                        class="text-sm text-gray-750 dark:text-gray-300 flex items-start gap-2"
                      >
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mt-1.5 shrink-0"></span>
                        {{ action }}
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. TEACHER VIEW -->
      <div v-else-if="activeRole === 'teacher'" class="space-y-8">
        <!-- Section Filter / Intro -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Synthèse de vos évaluations</h2>
            <p class="text-gray-600 dark:text-gray-400 text-sm mt-0.5">Consultez les retours des étudiants pour chaque module et formation dans lesquels vous intervenez.</p>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-gray-650 dark:text-gray-300">Formation :</span>
            <select v-model="selectedFormation" class="input-field py-2 px-3 rounded-lg max-w-xs shadow-sm bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700">
              <option value="all">Toutes les formations</option>
              <option v-for="f in teacherFormations" :key="f" :value="f">{{ f }}</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="card p-5 bg-gradient-to-br from-indigo-50 to-indigo-100/30 dark:from-indigo-950/20 dark:to-transparent border border-indigo-200/50 dark:border-indigo-900/50">
            <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">Matières concernées</p>
            <p class="text-4xl font-extrabold mt-1 text-gray-900 dark:text-white">{{ filteredTeacherStats.length }}</p>
            <p class="text-xs text-gray-655 dark:text-gray-450 mt-1">Modules avec évaluations actives</p>
          </div>
          <div class="card p-5 bg-gradient-to-br from-emerald-50 to-emerald-100/30 dark:from-emerald-950/20 dark:to-transparent border border-emerald-200/50 dark:border-emerald-900/50">
            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Moyenne de satisfaction</p>
            <p class="text-4xl font-extrabold mt-1 text-gray-900 dark:text-white">{{ averageTeacherSatisfaction }}%</p>
            <p class="text-xs text-gray-655 dark:text-gray-450 mt-1">Moyenne globale pondérée</p>
          </div>
          <div class="card p-5 bg-gradient-to-br from-purple-50 to-purple-100/30 dark:from-purple-950/20 dark:to-transparent border border-purple-200/50 dark:border-purple-900/50">
            <p class="text-sm font-semibold text-purple-700 dark:text-purple-400 uppercase tracking-wider">Taux de participation</p>
            <p class="text-4xl font-extrabold mt-1 text-gray-900 dark:text-white">{{ averageTeacherParticipation }}%</p>
            <p class="text-xs text-gray-655 dark:text-gray-450 mt-1">Taux moyen de remplissage</p>
          </div>
        </div>

        <!-- Course Evaluation Blocks -->
        <div class="space-y-6">
          <div 
            v-for="stat in filteredTeacherStats" 
            :key="stat.courseCode"
            class="card p-6 border border-gray-250 dark:border-gray-700 hover:shadow-md transition-shadow duration-200"
          >
            <!-- Course Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-150 dark:border-gray-700 pb-4 mb-5">
              <div>
                <div class="flex items-center gap-2.5">
                  <span class="bg-primary-100 dark:bg-primary-950 text-primary-700 dark:text-primary-300 text-xs font-bold px-2 py-0.5 rounded">
                    {{ stat.formation }}
                  </span>
                  <span class="text-xs text-gray-500 font-mono">{{ stat.courseCode }}</span>
                </div>
                <h3 class="text-xl font-bold mt-1 text-gray-900 dark:text-white">{{ stat.courseName }}</h3>
              </div>

              <!-- Stats Pill -->
              <div class="flex gap-4">
                <div class="text-right">
                  <p class="text-xs text-gray-550 dark:text-gray-450">Taux de réponse</p>
                  <p class="font-bold text-gray-800 dark:text-gray-200">{{ stat.responseRate }}%</p>
                </div>
                <div class="text-right border-l border-gray-200 dark:border-gray-700 pl-4">
                  <p class="text-xs text-gray-550 dark:text-gray-450">Satisfaction</p>
                  <p class="font-extrabold text-emerald-600 dark:text-emerald-400">{{ stat.satisfaction }}%</p>
                </div>
              </div>
            </div>

            <!-- Stats & Comments Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
              <!-- Visual indicator bar -->
              <div class="lg:col-span-2 space-y-4">
                <h4 class="text-sm font-bold text-gray-750 dark:text-gray-300 uppercase tracking-wider">Indicateurs clés :</h4>
                
                <div v-for="indicator in stat.indicators" :key="indicator.name" class="space-y-1">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-650 dark:text-gray-400 font-medium">{{ indicator.name }}</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ indicator.score }}/5</span>
                  </div>
                  <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div 
                      class="h-2 rounded-full transition-all duration-500"
                      :class="[
                        indicator.score >= 4 ? 'bg-emerald-500' :
                        indicator.score >= 3 ? 'bg-yellow-500' : 'bg-red-500'
                      ]"
                      :style="{ width: `${(indicator.score / 5) * 100}%` }"
                    ></div>
                  </div>
                </div>
              </div>

              <!-- Comments -->
              <div class="lg:col-span-3 space-y-3">
                <h4 class="text-sm font-bold text-gray-750 dark:text-gray-300 uppercase tracking-wider flex items-center gap-1.5">
                  <ChatBubbleLeftRightIcon class="w-4 h-4 text-primary-500" />
                  Commentaires représentatifs :
                </h4>

                <div class="space-y-2.5 max-h-48 overflow-y-auto pr-2">
                  <div 
                    v-for="(comment, cIdx) in stat.comments" 
                    :key="cIdx"
                    :class="[
                      'p-3 rounded-lg text-sm italic relative border-l-2',
                      comment.sentiment === 'positive' 
                        ? 'bg-green-50/50 dark:bg-green-950/10 border-green-400 text-gray-700 dark:text-gray-300' 
                        : 'bg-yellow-50/50 dark:bg-yellow-950/10 border-yellow-400 text-gray-700 dark:text-gray-300'
                    ]"
                  >
                    "{{ comment.text }}"
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. QUALITY MANAGER / ADMIN VIEW -->
      <div v-else-if="activeRole === 'admin'" class="space-y-8">
        <!-- Quick Stats Banner -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="card p-5 flex items-center justify-between border border-gray-200 dark:border-gray-700">
            <div>
              <p class="text-sm text-gray-655 dark:text-gray-450 font-medium">Questionnaires</p>
              <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-1">{{ surveyStore.surveyCount }}</p>
            </div>
            <div class="p-3 rounded-xl bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400">
              <DocumentTextIcon class="w-6 h-6" />
            </div>
          </div>

          <div class="card p-5 flex items-center justify-between border border-gray-200 dark:border-gray-700">
            <div>
              <p class="text-sm text-gray-655 dark:text-gray-450 font-medium">Publiés</p>
              <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-1">{{ surveyStore.publishedSurveys.length }}</p>
            </div>
            <div class="p-3 rounded-xl bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400">
              <RocketLaunchIcon class="w-6 h-6" />
            </div>
          </div>

          <div class="card p-5 flex items-center justify-between border border-gray-200 dark:border-gray-700">
            <div>
              <p class="text-sm text-gray-655 dark:text-gray-450 font-medium">Réponses totales</p>
              <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-1">{{ responseStore.totalResponses }}</p>
            </div>
            <div class="p-3 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400">
              <ChatBubbleLeftRightIcon class="w-6 h-6" />
            </div>
          </div>

          <div class="card p-5 flex items-center justify-between border border-gray-200 dark:border-gray-700">
            <div>
              <p class="text-sm text-gray-655 dark:text-gray-450 font-medium">Taux moyen</p>
              <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-1">{{ averageCompletionRate }}%</p>
            </div>
            <div class="p-3 rounded-xl bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400">
              <ArrowTrendingUpIcon class="w-6 h-6" />
            </div>
          </div>
        </div>

        <!-- Quick Actions Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="card flex items-center space-x-4">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/40 rounded-lg flex items-center justify-center">
              <PlusIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="flex-1">
              <h3 class="font-bold text-gray-900 dark:text-white">Créer un questionnaire</h3>
              <p class="text-xs text-gray-500">Commencer par en créer un nouveau de zéro</p>
            </div>
            <router-link :to="{name: 'questionnaire_builder', params: {id:'new'}}" class="btn-primary px-3 py-1.5 rounded-lg text-sm">
              Créer
            </router-link>
          </div>

          <div class="card flex items-center space-x-4">
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/40 rounded-lg flex items-center justify-center">
              <DocumentDuplicateIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div class="flex-1">
              <h3 class="font-bold text-gray-900 dark:text-white">Modèles pré-définis</h3>
              <p class="text-xs text-gray-500">Créer depuis des modèles existants</p>
            </div>
            <button @click="showTemplates = true" class="btn-secondary bg-green-100 hover:bg-green-200 dark:bg-green-950 text-green-700 dark:text-green-300 px-3 py-1.5 rounded-lg text-sm font-semibold">
              Parcourir
            </button>
          </div>

          <div class="card flex items-center space-x-4">
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/40 rounded-lg flex items-center justify-center">
              <ListBulletIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div class="flex-1">
              <h3 class="font-bold text-gray-900 dark:text-white">Gestion Détaillée</h3>
              <p class="text-xs text-gray-500">Consulter et filtrer la liste complète</p>
            </div>
            <router-link :to="{name: 'questionnaire_enquetes-liste'}" class="btn-secondary bg-purple-100 hover:bg-purple-200 dark:bg-purple-950 text-purple-700 dark:text-purple-300 px-3 py-1.5 rounded-lg text-sm font-semibold">
              Voir
            </router-link>
          </div>
        </div>

        <!-- Dynamic Content Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Left: My Surveys List (2/3 width) -->
          <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between mb-2">
              <h3 class="text-xl font-bold text-gray-905 dark:text-white flex items-center gap-2">
                <DocumentTextIcon class="w-5 h-5 text-blue-500" />
                Mes questionnaires récents
              </h3>
              <router-link :to="{name: 'questionnaire_enquetes-liste'}" class="text-primary-650 dark:text-primary-400 hover:underline text-sm font-semibold">
                Voir tout
              </router-link>
            </div>

            <div class="space-y-4">
              <div 
                v-for="survey in recentSurveys" 
                :key="survey.uuid"
                class="card flex flex-col p-4 border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow"
              >
                <!-- Top Row: Title & Actions -->
                <div class="flex items-center justify-between">
                  <div class="flex-1 min-w-0 pr-4">
                    <h4 class="font-bold text-gray-900 dark:text-white text-base truncate">{{ survey.title }}</h4>
                    <div class="flex items-center space-x-3 mt-1 flex-wrap gap-y-1">
                      <span 
                        :class="[
                          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold',
                          survey.status === 'published' 
                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' 
                            : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                        ]"
                      >
                        {{ survey.status === 'published' ? 'Publié' : 'Brouillon' }}
                      </span>
                      <span class="text-xs text-gray-500 dark:text-gray-400">
                        Modifié le {{ formatDate(survey.updatedAt) }}
                      </span>
                    </div>
                  </div>

                  <!-- Actions Pill -->
                  <div class="flex items-center space-x-2 shrink-0">
                    <router-link 
                      :to="{ name: 'questionnaire_builder', params: { id: survey.uuid } }"
                      v-tooltip="'Modifier le questionnaire'"
                      class="p-2 text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-950/30 hover:bg-orange-100 dark:hover:bg-orange-900/50 border border-orange-200 dark:border-orange-900/50 rounded-lg transition-all flex items-center justify-center hover:scale-105 active:scale-95 shadow-sm"
                    >
                      <PencilIcon class="w-4 h-4" />
                    </router-link>

                    <router-link 
                      v-if="survey.status === 'published'"
                      :to="{ name: 'questionnaire_responses', params: { id: survey.uuid } }"
                      v-tooltip="'Voir les réponses'"
                      class="p-2 text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/30 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 border border-emerald-200 dark:border-emerald-900/50 rounded-lg transition-all flex items-center justify-center hover:scale-105 active:scale-95 shadow-sm"
                    >
                      <ChatBubbleLeftRightIcon class="w-4 h-4" />
                    </router-link>

                    <router-link 
                      v-if="survey.status === 'published'"
                      :to="{ name: 'questionnaire_analytics', params: { id: survey.uuid } }"
                      v-tooltip="'Statistiques & Analyses'"
                      class="p-2 text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/30 hover:bg-purple-100 dark:hover:bg-purple-900/50 border border-purple-200 dark:border-purple-900/50 rounded-lg transition-all flex items-center justify-center hover:scale-105 active:scale-95 shadow-sm"
                    >
                      <ChartBarIcon class="w-4 h-4" />
                    </router-link>

                    <!-- Options Menu -->
                    <Menu as="div" class="relative inline-block text-left">
                      <MenuButton class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <EllipsisVerticalIcon class="w-4 h-4" />
                      </MenuButton>
                      <MenuItems class="survey-menu">
                        <MenuItem v-slot="{ active }">
                          <button 
                            @click="duplicateSurvey(survey)"
                            :class="[active ? 'bg-gray-100 dark:bg-gray-700' : '', 'menu-item w-full']"
                          >
                            <DocumentDuplicateIcon class="w-4 h-4" />
                            <span>Dupliquer</span>
                          </button>
                        </MenuItem>
                        <MenuItem v-slot="{ active }">
                          <button 
                            @click="deleteSurvey(survey)"
                            :class="[active ? 'bg-red-50 dark:bg-red-950/30 text-red-650' : '', 'menu-item w-full text-red-650 dark:text-red-400']"
                          >
                            <TrashIcon class="w-4 h-4" />
                            <span>Supprimer</span>
                          </button>
                        </MenuItem>
                      </MenuItems>
                    </Menu>
                  </div>
                </div>

                <!-- Bottom Row: Progress Bar for active surveys -->
                <div v-if="survey.status === 'published'" class="mt-4 space-y-1.5 border-t border-gray-100 dark:border-gray-700/60 pt-3">
                  <div class="flex justify-between text-xs">
                    <span class="text-gray-500 dark:text-gray-400">
                      Participation : <strong>{{ getSurveyStats(survey.uuid).responded }}</strong> / {{ getSurveyStats(survey.uuid).invited }}
                    </span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ getSurveyStats(survey.uuid).rate }}%</span>
                  </div>
                  <div class="w-full bg-gray-200 dark:bg-gray-750 rounded-full h-1.5">
                    <div 
                      class="bg-gradient-to-r from-primary-500 to-primary-600 h-1.5 rounded-full transition-all duration-500"
                      :style="{ width: `${getSurveyStats(survey.uuid).rate}%` }"
                    ></div>
                  </div>
                </div>
              </div>

              <!-- Empty State -->
              <div v-if="recentSurveys.length === 0" class="text-center py-12 card bg-white dark:bg-gray-800">
                <DocumentTextIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                <p class="text-gray-600 dark:text-gray-400">Aucun questionnaire créé pour le moment</p>
                <router-link :to="{ name: 'questionnaire_builder', params: { id: 'new' } }" class="btn-primary mt-4 inline-block px-4 py-2 rounded-xl">
                  Créer votre premier questionnaire
                </router-link>
              </div>
            </div>
          </div>

          <!-- Right: Summary Configuration & Recent Activity (1/3 width) -->
          <div class="space-y-6">
            <!-- Published Feedback Stats Manager -->
            <div class="card border border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Synthèse étudiants</h3>
              <p class="text-gray-650 dark:text-gray-400 text-sm mb-4">
                Configurez les synthèses de résultats publiées et visibles par les étudiants.
              </p>
              <div class="p-4 rounded-xl bg-gray-55/60 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-center">
                <p class="text-sm text-gray-650 dark:text-gray-400 font-medium">Dernière publication :</p>
                <p class="font-bold text-gray-900 dark:text-white mt-0.5">Synthèse Évaluation Semestre 1</p>
                <p class="text-xs text-gray-550 mt-0.5">Publié le 14/02/2026</p>
                <button class="mt-3 text-xs bg-primary-100 dark:bg-primary-950 text-primary-700 dark:text-primary-300 px-3 py-1.5 rounded-lg font-semibold hover:bg-primary-200 transition-colors cursor-pointer">
                  Mettre à jour la publication
                </button>
              </div>
            </div>

            <!-- Recent Activity -->
            <div class="space-y-4">
              <h3 class="text-xl font-bold text-gray-905 dark:text-white flex items-center gap-2">
                <ClockIcon class="w-5 h-5 text-purple-500" />
                Activité récente
              </h3>

              <div class="card space-y-4 max-h-[350px] overflow-y-auto">
                <div 
                  v-for="activity in recentActivity" 
                  :key="activity.id"
                  class="flex items-start space-x-3 p-3 bg-gray-55/60 dark:bg-gray-700/30 rounded-xl border border-gray-100 dark:border-gray-800"
                >
                  <div 
                    :class="[
                      'w-8 h-8 rounded-full flex items-center justify-center text-white text-xs shrink-0',
                      getActivityColor(activity.type)
                    ]"
                  >
                    <component :is="getActivityIcon(activity.type)" class="w-4 h-4" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-900 dark:text-white font-medium leading-tight">
                      {{ activity.message }}
                    </p>
                    <p class="text-2xs text-gray-500 dark:text-gray-400 mt-1">
                      {{ formatRelativeTime(activity.timestamp) }}
                    </p>
                  </div>
                </div>

                <div v-if="recentActivity.length === 0" class="text-center py-8">
                  <ClockIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                  <p class="text-gray-655 dark:text-gray-400">Aucune activité récente</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Templates Modal -->
    <div v-if="showTemplates" class="fixed inset-0 bg-black/55 flex items-center justify-center z-50" @click="showTemplates = false">
      <div 
        class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-4xl mx-4 max-h-[85vh] overflow-y-auto shadow-2xl border border-gray-200 dark:border-gray-750"
        @click.stop
      >
        <div class="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-gray-700 pb-3">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <DocumentDuplicateIcon class="w-6 h-6 text-primary-500" />
            Modèles de questionnaires
          </h2>
          <button 
            @click="showTemplates = false" 
            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 transition-colors"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="template in templates" 
            :key="template.id"
            class="border border-gray-250 dark:border-gray-700 rounded-xl p-5 hover:border-primary-400 hover:shadow-md transition-all duration-200 cursor-pointer flex flex-col justify-between"
            @click="createFromTemplate(template)"
          >
            <div>
              <div class="flex items-center space-x-3 mb-3">
                <component :is="template.icon" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                <h3 class="font-bold text-gray-900 dark:text-white">{{ template.title }}</h3>
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 leading-normal">
                {{ template.description }}
              </p>
            </div>
            <div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-700 pt-3 mt-2">
              <span class="text-xs text-gray-500 dark:text-gray-400 font-semibold bg-gray-100 dark:bg-gray-900 px-2 py-0.5 rounded">
                {{ template.questions }} questions
              </span>
              <span class="text-primary-600 dark:text-primary-400 text-xs font-bold hover:underline">Utiliser</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { 
  AcademicCapIcon, 
  ClockIcon, 
  CheckCircleIcon, 
  ExclamationCircleIcon, 
  ArrowRightIcon, 
  CheckIcon,
  ChartBarIcon, 
  UserGroupIcon,
  DocumentTextIcon,
  PlusIcon,
  ListBulletIcon,
  AdjustmentsHorizontalIcon,
  ArrowTrendingUpIcon,
  HomeIcon,
  ChatBubbleLeftRightIcon,
  RocketLaunchIcon,
  DocumentDuplicateIcon,
  HeartIcon,
  BuildingOfficeIcon,
  ShoppingBagIcon,
  XMarkIcon,
  PencilIcon,
  TrashIcon,
  EllipsisVerticalIcon
} from '@heroicons/vue/24/outline';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';
import { useSurveyStore } from '@/stores/survey';
import { useResponseStore } from '@/stores/responses';
import { useUIStore } from '@/stores/ui';
import { formatDate, formatRelativeTime } from '@/utils/date';

const router = useRouter();
const route = useRoute();
const surveyStore = useSurveyStore();
const responseStore = useResponseStore();
const uiStore = useUIStore();

// ----------------------------------------------------
// Tab / Role Switcher configuration
// ----------------------------------------------------
const activeRole = ref('student'); // 'student' | 'teacher' | 'admin'

const roles = [
  { id: 'student', label: 'Étudiant', icon: AcademicCapIcon },
  { id: 'teacher', label: 'Enseignant', icon: HomeIcon },
  { id: 'admin', label: 'Responsable Qualité', icon: UserGroupIcon }
];

// ----------------------------------------------------
// 1. MOCK DATA - STUDENT
// ----------------------------------------------------
const studentPendingSurveys = ref([
  {
    uuid: 'pending-1',
    title: 'Évaluation des enseignements - Semestre 2',
    description: 'BUT Informatique - Session 2026. Donnez votre avis sur le contenu, la structure et la pédagogie des cours de ce semestre.',
    duration: '5 min',
    deadline: '19/06/2026',
    token: 'token-sec2-2026'
  },
  {
    uuid: 'pending-2',
    title: 'Enquête de satisfaction - Service de restauration',
    description: 'Aidez le CROUS et l\'IUT à adapter les menus de la cafétéria et les créneaux d\'ouverture.',
    duration: '3 min',
    deadline: '23/06/2026',
    token: 'token-crous-2026'
  }
]);

const studentCompletedSurveys = ref([
  {
    uuid: 'completed-1',
    title: 'Évaluation de la rentrée 2025 & Intégration',
    submittedAt: '15/09/2025'
  },
  {
    uuid: 'completed-2',
    title: 'Choix de l\'option de spécialisation (BUT3)',
    submittedAt: '12/10/2025'
  }
]);

const publishedAnalytics = ref([
  {
    id: 'report-1',
    title: 'Rapport d\'analyse & Actions - Évaluation du S1',
    date: '14/02/2026',
    stats: { satisfaction: 85, participation: 74 },
    actions: [
      'Achat de 15 nouveaux PC performants pour la salle réseau (M305).',
      'Ajustement du calendrier des examens du S2 pour limiter la charge de travail hebdomadaire.',
      'Mise à disposition de tutoriels vidéos supplémentaires en programmation.'
    ]
  },
  {
    id: 'report-2',
    title: 'Retour sur l\'enquête d\'intégration des nouveaux étudiants 2025',
    date: '10/10/2025',
    stats: { satisfaction: 91, participation: 88 },
    actions: [
      'Création d\'un système de parrainage pérenne BUT1 - BUT2.',
      'Refonte de l\'intranet d\'accueil pour centraliser les emplois du temps.'
    ]
  }
]);

// ----------------------------------------------------
// 2. MOCK DATA - TEACHER
// ----------------------------------------------------
const selectedFormation = ref('all');

const teacherFormations = computed(() => {
  const forms = new Set(teacherCoursesStats.value.map(c => c.formation));
  return Array.from(forms);
});

const teacherCoursesStats = ref([
  {
    formation: 'BUT Informatique A1',
    courseCode: 'R1.01',
    courseName: 'Initiation au Développement (S1)',
    satisfaction: 92,
    responseRate: 84,
    indicators: [
      { name: 'Pédagogie & Clarté', score: 4.6 },
      { name: 'Organisation des TP', score: 4.8 },
      { name: 'Volume horaire adapté', score: 4.1 },
      { name: 'Utilité perçue', score: 4.7 }
    ],
    comments: [
      { text: 'Le cours est très bien structuré, les TPs aident à bien comprendre la matière.', sentiment: 'positive' },
      { text: 'Parfois le rythme est un peu rapide au début pour les débutants.', sentiment: 'neutral' },
      { text: 'Super prof de TP, très disponible pour réexpliquer.', sentiment: 'positive' }
    ]
  },
  {
    formation: 'BUT Informatique A1',
    courseCode: 'R1.02',
    courseName: 'Développement Web (S1)',
    satisfaction: 88,
    responseRate: 78,
    indicators: [
      { name: 'Pédagogie & Clarté', score: 4.3 },
      { name: 'Organisation des TP', score: 4.5 },
      { name: 'Volume horaire adapté', score: 3.9 },
      { name: 'Utilité perçue', score: 4.6 }
    ],
    comments: [
      { text: 'TPs très intéressants et appliqués.', sentiment: 'positive' },
      { text: 'Dommage qu\'on ne puisse pas choisir nos groupes de projet.', sentiment: 'neutral' },
      { text: 'Excellente introduction au HTML/CSS.', sentiment: 'positive' }
    ]
  },
  {
    formation: 'LP Métiers du Multimédia',
    courseCode: 'R3.01',
    courseName: 'Services Web avancés (S2)',
    satisfaction: 79,
    responseRate: 65,
    indicators: [
      { name: 'Pédagogie & Clarté', score: 3.8 },
      { name: 'Organisation des TP', score: 4.2 },
      { name: 'Volume horaire adapté', score: 3.5 },
      { name: 'Utilité perçue', score: 4.3 }
    ],
    comments: [
      { text: 'La théorie est un peu abstraite, mais les projets finaux sont passionnants.', sentiment: 'neutral' },
      { text: 'Besoin de plus d\'exemples pratiques sur les API complexes.', sentiment: 'neutral' },
      { text: 'Le projet de groupe est très formateur.', sentiment: 'positive' }
    ]
  }
]);

const filteredTeacherStats = computed(() => {
  if (selectedFormation.value === 'all') {
    return teacherCoursesStats.value;
  }
  return teacherCoursesStats.value.filter(s => s.formation === selectedFormation.value);
});

const averageTeacherSatisfaction = computed(() => {
  const stats = filteredTeacherStats.value;
  if (stats.length === 0) return 0;
  return Math.round(stats.reduce((sum, s) => sum + s.satisfaction, 0) / stats.length);
});

const averageTeacherParticipation = computed(() => {
  const stats = filteredTeacherStats.value;
  if (stats.length === 0) return 0;
  return Math.round(stats.reduce((sum, s) => sum + s.responseRate, 0) / stats.length);
});

// ----------------------------------------------------
// 3. QUALITY MANAGER / ADMIN MERGED LOGIC
// ----------------------------------------------------
const showTemplates = ref(false);

const templates = [
  {
    id: 'satisfaction',
    title: 'Satisfaction client',
    description: 'Évaluez la satisfaction de vos clients',
    questions: 8,
    icon: HeartIcon
  },
  {
    id: 'employee',
    title: 'Enquête employés',
    description: 'Recueillez les avis de vos collaborateurs',
    questions: 12,
    icon: BuildingOfficeIcon
  },
  {
    id: 'event',
    title: 'Feedback événement',
    description: 'Évaluez votre événement ou formation',
    questions: 10,
    icon: UserGroupIcon
  },
  {
    id: 'product',
    title: 'Étude produit',
    description: 'Testez votre produit ou service',
    questions: 15,
    icon: ShoppingBagIcon
  },
  {
    id: 'education',
    title: 'Évaluation formation',
    description: 'Évaluez l\'efficacité de vos formations',
    questions: 9,
    icon: AcademicCapIcon
  },
  {
    id: 'market',
    title: 'Étude de marché',
    description: 'Analysez votre marché et concurrence',
    questions: 18,
    icon: ChartBarIcon
  }
];

const recentSurveys = computed(() => {
  if (!surveyStore.surveys) return [];
  return [...surveyStore.surveys]
    .sort((a, b) => new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime())
    .slice(0, 5);
});

const averageCompletionRate = computed(() => {
  if (!surveyStore.publishedSurveys || surveyStore.publishedSurveys.length === 0) return 0;
  const rates = surveyStore.publishedSurveys.map(survey =>
    responseStore.completionRate(survey.uuid)
  );
  return Math.round(rates.reduce((sum, rate) => sum + rate, 0) / rates.length);
});

const recentActivity = computed(() => {
  const activities: any[] = [];
  if (!surveyStore.surveys || !responseStore.responses) return [];

  // Add survey creation activities
  surveyStore.surveys.forEach(survey => {
    activities.push({
      id: `survey-${survey.uuid}`,
      type: 'survey_created',
      message: `Questionnaire "${survey.title}" créé`,
      timestamp: survey.createdAt
    });

    if (survey.status === 'published') {
      activities.push({
        id: `publish-${survey.uuid}`,
        type: 'survey_published',
        message: `Questionnaire "${survey.title}" publié`,
        timestamp: survey.updatedAt
      });
    }
  });

  // Add response activities
  responseStore.responses.forEach(response => {
    if (response.completed) {
      const survey = surveyStore.surveys.find(s => s.uuid === response.surveyId);
      activities.push({
        id: `response-${response.id}`,
        type: 'response_received',
        message: `Nouvelle réponse pour "${survey?.title || 'Questionnaire'}"`,
        timestamp: response.submittedAt || response.lastActivity
      });
    }
  });

  const getTimestamp = (date: any) => new Date(date).getTime();

  return activities
    .sort((a, b) => getTimestamp(b.timestamp) - getTimestamp(a.timestamp))
    .slice(0, 10);
});

function getActivityColor(type: string): string {
  const colors = {
    survey_created: 'bg-blue-500',
    survey_published: 'bg-green-500',
    response_received: 'bg-purple-500'
  };
  return colors[type as keyof typeof colors] || 'bg-gray-500';
}

function getActivityIcon(type: string) {
  const icons = {
    survey_created: PlusIcon,
    survey_published: RocketLaunchIcon,
    response_received: ChatBubbleLeftRightIcon
  };
  return icons[type as keyof typeof icons] || DocumentTextIcon;
}

function duplicateSurvey(survey: any) {
  const duplicate = surveyStore.duplicateSurvey(survey.uuid);
  if (duplicate) {
    uiStore.addNotification('success', 'Questionnaire dupliqué', 'Copie créée avec succès.');
    router.push({ name: 'questionnaire_builder', params: { id: duplicate.uuid } });
  }
}

function deleteSurvey(survey: any) {
  if (confirm(`Êtes-vous sûr de vouloir supprimer "${survey.title}" ?`)) {
    surveyStore.deleteSurvey(survey.uuid);
    uiStore.addNotification('success', 'Questionnaire supprimé', 'Le questionnaire a été supprimé.');
  }
}

function createFromTemplate(template: any) {
  const survey = surveyStore.createSurvey(template.title, template.description);
  showTemplates.value = false;
  uiStore.addNotification(
    'success',
    'Questionnaire créé',
    `Le questionnaire "${template.title}" a été créé à partir du modèle.`
  );
  router.push({ name: 'questionnaire_builder', params: { id: survey.uuid } });
}

function getSurveyStats(surveyUuid: string) {
  const responsesCount = responseStore.completedResponses(surveyUuid).length;
  const analytics = responseStore.getSurveyAnalytics(surveyUuid);
  // Valeurs par défaut réalistes pour les questionnaires de démo / initiés
  const invited = analytics.totalInvited || 120;
  const responded = analytics.totalResponses || (surveyUuid.startsWith('active-') ? 85 : 0);
  const rate = Math.round(analytics.completionRate) || (surveyUuid.startsWith('active-') ? Math.round((responded / invited) * 105) : 0);
  return { 
    responded: Math.min(invited, responded), 
    invited, 
    rate: Math.min(100, rate) 
  };
}

// ----------------------------------------------------
// Initialization
// ----------------------------------------------------
onMounted(async () => {
  // Select active view based on routing context
  if (route.path.includes('qualite') || route.name === 'questionnaire_qualite-enquetes') {
    activeRole.value = 'admin';
  }
  
  // Load data from state stores
  await surveyStore.loadQuestionnaires();
  responseStore.loadFromLocalStorage();
});
</script>

<style scoped>
@reference "../assets/tailwind.css";

/* Custom classes matching the project styling */
.card {
  @apply bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all duration-200;
}

.btn-primary {
  @apply bg-primary-600 hover:bg-primary-700 text-white font-semibold transition-all duration-200 cursor-pointer shadow-md hover:shadow-lg active:scale-98;
}

.btn-secondary {
  @apply transition-all duration-200 cursor-pointer active:scale-98;
}

.input-field {
  @apply focus:outline-none focus:ring-2 focus:ring-primary-500 rounded;
}

.survey-menu {
  @apply absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50;
}

.menu-item {
  @apply flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors;
}

/* Animations transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.scale-101 {
  transform: scale(1.01);
}

.scale-102 {
  transform: scale(1.02);
}

.text-2xs {
  font-size: 0.65rem;
}
</style>
