<script setup>
import { onMounted, ref, computed, isProxy, toRaw } from 'vue'
import { useSemestreStore, useMatieresStore } from '@stores'
import SelectWeek from '@/components/SelectWeek.vue'
import api from '@helpers/axios.js'
import Card from '@components/components/Card.vue'
import { formatDateCourt, jourDate } from '@helpers/date.js'
import { getPersonnelsDepartementService } from '@requests/user_services/personnelService.js'

const groupData = ref([])

const timeSlots = ref(['8h00', '9h30', '11h00', '12h30', '14h00', '15h30', '17h00'])
const days = ref()

const selectedSemester = ref('')
const selectedProfessor = ref('')
const selectedCourse = ref('')
const selectedGroup = ref('')
const availableCourses = ref({})
const restrictedSlots = ref([])
const currentWeek = ref(null)
const selectedHighlightType = ref('course') // 'course' or 'professor'
let departementId = null

const matieresStore = useMatieresStore()
const semestresStore = useSemestreStore()
const coursesToReplace = ref([])
const constraints = ref({})
const size = ref(0)
const semestres = ref([])
const personnels = ref([])

const isModalOpen = ref(false)
const modalCourse = ref({
  matiere: '',
  professor: '',
  day: '',
  time: '',
  room: '',
  id: ''
})

const handleWeekUpdate = (week) => {
  currentWeek.value = week
  loadWeek()
}

onMounted(async () => {
  departementId = localStorage.getItem('departement')
  try {
    if (currentWeek.value === null) {
      const response = await api.get('/api/structure_calendriers')
      currentWeek.value = response.data['member'][0]
    }

    await semestresStore.getSemestresByDepartement(departementId)
    semestres.value = semestresStore.semestres['member']

    Object.values(semestres.value).forEach((semestre) => {
      size.value += semestre.nbGroupesTp
      // pour chaque nbTP faire un tableau de groupe
      groupData.value[semestre.id] = []
      for(let i = 0; i < semestre.nbGroupesTp; i++) {
        groupData.value[semestre.id].push(String.fromCharCode(65 + i))
      }
    })

    if (currentWeek.value !== null) {
      _getSemaines(currentWeek.value.semaineFormation)
      _getCours(currentWeek.value.semaineFormation)

      personnels.value = await getPersonnelsDepartementService(departementId)
      await matieresStore.getMatieres()
      const response = await api.get(`/api/edt/personnels-contraintes/${currentWeek.value.semaineFormation}`)
      constraints.value = await response.data
    }
  } catch (error) {
    console.error('Error loading JSON:', error)
  }
})

const isSidebarOpen = ref(false)

const openModal = (course) => {
  modalCourse.value = { ...course }
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
}

const saveRoom = () => {
  // Find the course in placedCourses and update the room
  const courseKey = `${modalCourse.value.day}_${modalCourse.value.time}_${modalCourse.value.group}_${modalCourse.value.groupIndex}`
  placedCourses.value[courseKey].room = modalCourse.value.room

  // mise à jour de la salle dans l'API
  fetch(baseUrl + '/update-room/' + modalCourse.value.id, { //todo: ajouter semestre
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      room: modalCourse.value.room
    })
  })

  closeModal()
}

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}
const placedCourses = ref({})

const filteredCourses = computed(() => {
  if (availableCourses.value.length === 0) {
    return []
  }

  return Object.values(availableCourses.value).filter((course) => {
    return (
        (selectedSemester.value === '' || course.group === selectedSemester.value) &&
        (selectedProfessor.value === '' || course.professor === selectedProfessor.value) &&
        (selectedCourse.value === '' || course.matiere === selectedCourse.value) &&
        (selectedGroup.value === '' || course.groupIndex === parseInt(selectedGroup.value))
    )
  })
})

const displayCourse = (course) => {
  if (course.blocked === true) {
    return course.name
  }
  return `${course.matiere} <br> ${course.professor} <br>`
}

const groupeSize = (course) => {
  switch (course.type) {
    case 'CM':
      return 8//groupData.value[semestre.id] //todo: doit dépendre du CM ou ajouter une entrée/propriété dans la base de données
    case 'TD':
      return 2
    case 'TP':
      return 1
    default:
      return 1
  }
}
const displayCourseListe = (course) => {
  let groupe = course.type + ' ' + course.libGroupe

  return `${course.libModule.trim()} <br> ${course.libPersonnel.trim()} <br> ${groupe.trim()}`
}

const resetFilters = () => {
  selectedSemester.value = ''
  selectedProfessor.value = ''
  selectedCourse.value = ''
  selectedGroup.value = ''
}

const _getSemaines = async (currentWeek) => {
  const data = await api.get(`/api/structure_calendriers?semaineFormation=${currentWeek}`).then((res) =>
      res.data['member'][0]
  )
  restrictedSlots.value = data.edtCreneauxInterditsSemaines
  //parcours les jours de data et les transforme en objet
  days.value = Object.keys(data.jours).map((key) => {
    return { day: jourDate(data.jours[key]), dateFr: formatDateCourt(data.jours[key]) }
  })

  placedCourses.value = await api.get(`/api/edt_events?semaineFormation=${currentWeek}&aPlacer=false`).then((res) =>
      res.data
  )

  Object.keys(placedCourses.value).forEach(async (key) => {
    const course = await placedCourses.value[key]
    if (course.blocked === false) {
      mergeCells(course.day, course.time, course.group, course.groupIndex, groupeSize(course))
    }
  })

  applyRestrictions()
}

const _getCours = async (numSemaine) => {
  const res = await api.get(`/api/edt_events?semaineFormation=${numSemaine}&aPlacer=true`)
  availableCourses.value = await res.data['member']
  console.log(availableCourses.value)
}

const loadWeek = async () => {
  try {
    if (currentWeek.value === undefined) {
      return
    }
    verifyAndResetGrid() // on remet la grille en état
    await _getSemaines(currentWeek.value.semaineFormation)
    await _getCours(currentWeek.value.semaineFormation)
  } catch (error) {
    console.error('Error loading JSON:', error)
  }
}

const verifyAndResetGrid = () => {
  // pour chaque cours placés on supprime pour remettre la grille en état avant le changement de semestre
  Object.keys(placedCourses.value).forEach((key) => {
    const course = placedCourses.value[key]
    if (course.blocked === false) {
      removeCourse(
          course.day,
          course.time,
          course.group,
          course.groupIndex,
          groupeSize(course),
          true
      )
    }
  })
}

const loadPreviousWeek = () => {
  if (currentWeek.value > 1) {
    currentWeek.value -= 1
    loadWeek()
  }
}

const loadNextWeek = () => {
  currentWeek.value += 1//todo: currentWeel est un objet
  loadWeek()
}

const onDragStart = (event, course, source, originSlot) => {
  event.dataTransfer.setData('courseId', course.id)
  event.dataTransfer.setData('source', source) // Set the source of the drag
  event.dataTransfer.setData('originSlot', originSlot) // Set the origin slot

  highlightValidCells(course)
  event.target.addEventListener('dragend', clearHighlight, { once: true })
}

const onDrop = (event, day, time, semestre, groupNumber) => {
  const courseId = event.dataTransfer.getData('courseId')
  const source = event.dataTransfer.getData('source') // Get the source of the drag

  if (source === 'availableCourses') {
    handleDropFromAvailableCourses(courseId, day, time, semestre, groupNumber)
  } else if (source === 'grid') {
    const originSlot = event.dataTransfer.getData('originSlot') // Get the origin slot
    handleDropFromGrid(courseId, day, time, semestre, groupNumber, originSlot)
  }
  // clearHighlight()
}

const handleDropFromAvailableCourses = (courseId, day, time, semestre, groupNumber) => {
  const courseIndex = availableCourses.value.findIndex((c) => c.id == courseId)
  const course = availableCourses.value[courseIndex]

  if (course && course.group === semestre && course.groupIndex === groupNumber) {
    const groupSpan = groupeSize(course)

    if (groupNumber <= groupData.value[semestre].length - groupSpan + 1) {
      mergeCells(day, time, semestre, groupNumber, groupSpan)
      course.time = time
      course.day = day
      course.blocked = false
      course.room = 'A définir'

      const response = fetch(baseUrl + '/place-course', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          time: time,
          day: day,
          id: course.id,
          week: currentWeek.value
        })
      }).then((res) => res.json())

      response.then((data) => {
        course.id = data.id
      })

      placedCourses.value[`${day}_${time}_${semestre}_${groupNumber}`] = course
      availableCourses.value.splice(courseIndex, 1)
    }
  }
}

const handleDropFromGrid = (courseId, day, time, semestre, groupNumber, originSlot) => {
  const course = placedCourses.value[originSlot]

  if (course && course.group === semestre && course.groupIndex === groupNumber) {
    const groupSpan = groupeSize(course)

    if (groupNumber <= groupData.value[semestre].length - groupSpan + 1) {
      removeCourse(
          course.day,
          course.time,
          course.group,
          course.groupIndex,
          groupeSize(course),
          true
      )
      mergeCells(day, time, semestre, groupNumber, groupSpan)
      course.time = time
      course.day = day
      course.blocked = false
      course.room = 'A définir'

      const response = fetch(baseUrl + '/place-course', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          time: time,
          day: day,
          id: course.id,
          week: currentWeek.value
        })
      }).then((res) => res.json())

      response.then((data) => {
        course.id = data.id
      })

      delete placedCourses.value[originSlot]
      placedCourses.value[`${day}_${time}_${semestre}_${groupNumber}`] = course
    }
  }
}

const mergeCells = (day, time, semestre, groupNumber, groupSpan) => {
  const cellSelector = `[data-key="${day}_${time}_${semestre}_${groupNumber}"]`
  const cell = document.querySelector(cellSelector)
  if (cell) {
    cell.style.gridColumn = `span ${groupSpan}`
    cell.style.width = `${50 * groupSpan}px`
    // Remove the extra cells that are merged
    for (let i = 1; i < groupSpan; i++) {
      const extraCellSelector = `[data-key="${day}_${time}_${semestre}_${groupNumber + i}"]`
      const extraCell = document.querySelector(extraCellSelector)
      if (extraCell) {
        extraCell.remove()
      }
    }
  }
}

const onDropToReplace = (event) => {
  const courseId = event.dataTransfer.getData('courseId')
  const courseIndex = availableCourses.value.findIndex((c) => c.id == courseId)
  const course = availableCourses.value[courseIndex]

  if (course) {
    course.originalWeek = currentWeek.value
    coursesToReplace.value.push(course)
    availableCourses.value.splice(courseIndex, 1)
  }
}

const removeCourse = (day, time, semestre, groupNumber, groupSpan, changeSemaine = false) => {
  const courseKey = `${day}_${time}_${semestre}_${groupNumber}`
  const course = placedCourses.value[courseKey]
  const currentCell = document.querySelector(`[data-key="${courseKey}"]`)

  if (course) {
    course.time = null
    course.day = null
    currentCell.style = ''
    currentCell.classList.remove('highlight-same-course')

    // Remove the course from all associated cells and add empty cells back
    for (let i = 0; i < groupSpan; i++) {
      delete placedCourses.value[`${day}_${time}_${semestre}_${groupNumber + i}`]
    }
    // Recreate the missing cells
    for (let i = 1; i < groupSpan; i++) {
      const cellKey = `${day}_${time}_${semestre}_${groupNumber + i}`
      const cell = currentCell.cloneNode(false)
      cell.setAttribute('data-key', cellKey)
      const parent = currentCell.parentNode
      parent.insertBefore(cell, currentCell.nextSibling)
    }

    if (!changeSemaine) {
      //mise à jour de la base de données
      fetch(baseUrl + '/remove-course/' + course.id, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          week: currentWeek.value
        })
      })
      course.id = null
      availableCourses.value.push(course)
    }
  }
}

const applyRestrictions = () => {
  // blcoage du créneau de 12h30, tous les jours, pour tous les groupes
  days.value.forEach((day) => {
    // blocage du créneau de 12h30
    Object.keys(groupData.value).forEach((semester) => {
      groupData.value[semester].forEach((groupNumber) => {
        blockSlot(day.day, '12h30', semester, groupToInt(groupNumber), 'Pause')
      })
    })
  })

  Object.keys(restrictedSlots.value).forEach((key) => {
    restrictedSlots.value[key].forEach((slot) => {
      const { type, slot: timeSlot, semester, days, groups, period, motif } = slot
      days.forEach((day) => {
        if (type === 'generic') {
          // dans ce cas tous les semestres, tous les groupes
          Object.keys(groupData.value).forEach((semester) => {
            groupData.value[semester].forEach((groupNumber) => {
              blockSlot(day, timeSlot, semester, groupToInt(groupNumber), motif)
            })
          })
        } else if (type === 'semester') {
          // dans ce cas tous les groupes d'un semestre
          groupData.value[semester].forEach((groupNumber) => {
            blockSlot(day, timeSlot, semester, groupToInt(groupNumber), motif)
          })
        } else if (type === 'group') {
          groups.forEach((groupNumber) => {
            blockSlot(day, timeSlot, semester, groupToInt(groupNumber), motif)
          })
        } else if (type === 'half-day' || type === 'full-day') {
          const times =
              type === 'half-day'
                  ? period === 'morning'
                      ? ['8h00', '9h30', '11h00']
                      : ['14h00', '15h30', '17h00']
                  : ['8h00', '9h30', '11h00', '14h00', '15h30', '17h00']

          if (key === 'all') {
            times.forEach((time) => {
              Object.keys(groupData.value).forEach((semester) => {
                groupData.value[semester].forEach((groupNumber) => {
                  blockSlot(day, time, semester, groupToInt(groupNumber), motif)
                })
              })
            })
          } else {
            times.forEach((time) => {
              groupData.value[key].forEach((groupNumber) => {
                blockSlot(day, time, key, groupToInt(groupNumber), motif)
              })
            })
          }
        }
      })
    })
  })
}

const blockSlot = (day, time, semester, groupNumber, motif = null) => {
  const cellKey = `${day}_${time}_${semester}_${groupNumber}`
  placedCourses.value[cellKey] = { name: motif ?? 'blocked', color: '#ffcccc', blocked: true }
}

const isProfessorAvailable = (professor, day, time) => {
  return !Object.values(placedCourses.value).some(
      (course) => course.professor === professor && course.time === time && course.day === day
  )
}

const highlightValidCells = (course) => {
  const group = course.groupe
  const groupIndex = course.indexGroupe
  const groupCount = groupeSize(course)
  const professor = course.personnel

  const professorConstraints = constraints.value[professor] || { mandatory: [], optional: [] }

  days.value.forEach((day) => {
    timeSlots.value.forEach((time) => {
      const isMandatory = professorConstraints.mandatory.some(
          (constraint) => constraint.day === day.day && constraint.time === time
      )
      const isOptional = professorConstraints.optional.some(
          (constraint) => constraint.day === day.day && constraint.time === time
      )
      const isAvailable = isProfessorAvailable(professor, day.day, time)

      for (let i = 0; i < groupCount; i++) {
        const cellKey = `${day.day}_${time}_${group}_${groupIndex + i}`
        console.log(cellKey)
        const cell = document.querySelector(`[data-key="${cellKey}"]`)

        if (cell && !placedCourses.value[cellKey]) {
          if (isMandatory) {
            cell.classList.add('highlight-mandatory')
          } else if (isOptional) {
            cell.classList.add('highlight-optional')
          } else if (isAvailable) {
            cell.classList.add('highlight')
          }
        }
      }
    })
  })
}

// const highlightValidCells = (course) => {
//   const { group, groupIndex, groupCount, professor } = course
//   days.value.forEach((day) => {
//     timeSlots.value.forEach((time) => {
//       if (isProfessorAvailable(professor, day.day, time)) {
//         for (let i = 0; i < groupCount; i++) {
//           const cellKey = `${day.day}_${time}_${group}_${groupIndex + i}`
//           const cell = document.querySelector(`[data-key="${cellKey}"]`)
//           if (cell && !placedCourses.value[cellKey]) {
//             cell.classList.add('highlight')
//           }
//         }
//       }
//     })
//   })
// }

const clearHighlight = () => {
  const highlightedCells = document.querySelectorAll('.highlight')
  highlightedCells.forEach((cell) => {
    cell.classList.remove('highlight')
  })

  const highlightedMandatoryCells = document.querySelectorAll('.highlight-mandatory')
  highlightedMandatoryCells.forEach((cell) => {
    cell.classList.remove('highlight-mandatory')
  })

  const highlightedOptionalCells = document.querySelectorAll('.highlight-optional')
  highlightedOptionalCells.forEach((cell) => {
    cell.classList.remove('highlight-optional')
  })
}

const assignRoomsAutomatically = () => {
  const rooms = ['Room A', 'Room B', 'Room C', 'Room D'] // Example room list
  const assignedRooms = {} // Map to track assigned rooms for each time slot

  Object.keys(placedCourses.value).forEach((key) => {
    const course = placedCourses.value[key]
    if (course && !course.room) {
      const timeSlot = `${course.day}_${course.time}`
      if (!assignedRooms[timeSlot]) {
        assignedRooms[timeSlot] = new Set()
      }

      // Find an available room
      let roomAssigned = false
      for (let room of rooms) {
        if (!assignedRooms[timeSlot].has(room)) {
          course.room = room
          assignedRooms[timeSlot].add(room)
          roomAssigned = true
          break
        }
      }

      // If no room is available, you can handle it as needed (e.g., log an error)
      if (!roomAssigned) {
        console.error(`No available room for course ${course.matiere} at ${timeSlot}`)
      }
    }
  })
}

const highlightSameCourses = (day, time, semestre, groupNumber) => {
  const courseKey = `${day}_${time}_${semestre}_${groupNumber}`
  const course = placedCourses.value[courseKey]

  if (course) {
    const highlightValue =
        selectedHighlightType.value === 'course' ? course.matiere : course.professor
    Object.keys(placedCourses.value).forEach((key) => {
      if (
          (selectedHighlightType.value === 'course' &&
              placedCourses.value[key].matiere === highlightValue) ||
          (selectedHighlightType.value === 'professor' &&
              placedCourses.value[key].professor === highlightValue)
      ) {
        const cell = document.querySelector(`[data-key="${key}"]`)
        if (cell) {
          cell.classList.add('highlight-same-course')
        }
      }
    })
  }
}

const clearSameCoursesHighlight = (day, time, semestre, groupNumber) => {
  const courseKey = `${day}_${time}_${semestre}_${groupNumber}`
  const course = placedCourses.value[courseKey]

  if (course) {
    const highlightValue =
        selectedHighlightType.value === 'course' ? course.matiere : course.professor
    Object.keys(placedCourses.value).forEach((key) => {
      if (
          (selectedHighlightType.value === 'course' &&
              placedCourses.value[key].matiere === highlightValue) ||
          (selectedHighlightType.value === 'professor' &&
              placedCourses.value[key].professor === highlightValue)
      ) {
        const cell = document.querySelector(`[data-key="${key}"]`)
        if (cell) {
          cell.classList.remove('highlight-same-course')
        }
      }
    })
  }
}

const groupToInt = (group) => {
  // convert group letter to number
  return group.charCodeAt(0) - 64
}

const getTitleCard = () => {
  return currentWeek.value ? 'Emploi du temps semaine ' + currentWeek.value.semaineFormation+ ' (' + formatDateCourt(currentWeek.value.dateLundi)+ ')' : 'Chargement...'
}
</script>

<template>
  <Card
      v-if="currentWeek"
    :title="getTitleCard()"
  >
    <div class="row">
        <div class="col-6">
          <Button @click="assignRoomsAutomatically" severity="info" class="mt-1 mb-1">Assign Rooms Automatically</Button>
        </div>
        <div class="col-6">
          <label>
            <input type="radio" value="course" v-model="selectedHighlightType"/>
            Par matière
          </label>
          <label>
            <input type="radio" value="professor" v-model="selectedHighlightType"/>
            Par professeur
          </label>
        </div>
        <div class="flex flex-wrap gap-4 justify-center mb-2 mt-1">
          <Button class="btn btn-primary d-block" @click="loadPreviousWeek">Semaine précédente</Button>
          <SelectWeek @update:selectedWeek="handleWeekUpdate" :current-week="currentWeek" />
          <Button class="btn btn-primary d-block" @click="loadNextWeek">Semaine suivante</Button>
        </div>

        <div class="col-12">
          <div class="grid-container" v-for="day in days" :key="day.day">
            <div class="grid-day">{{ day.day }} {{ day.dateFr }}</div>
            <!-- Header Row: Semesters -->
            <div class="grid-header grid-time">Heure</div>
            <div
                v-for="semestre in semestres"
                :key="semestre.id"
                class="grid-header"
                :style="{ gridColumn: `span ${semestre.nbGroupesTp}` }"
            >
              {{ semestre.libelle }}
            </div>

            <!-- Second Row: Group Headers -->
            <div class="grid-time"></div>
            <template v-for="semestre in semestres" :key="'group-' + semestre.id">
              <div
                  v-for="group in groupData[semestre.id]"
                  :key="semestre + group"
                  class="grid-header"
              >
                {{ group }}
              </div>
            </template>

            <!-- Time Slots and Group Cells -->
            <template v-for="time in timeSlots" :key="time">
              <div class="grid-time">{{ time }}</div>
              <template v-for="semestre in semestres" :key="'time-' + semestre.libelle">
                <div
                    v-for="group in groupData[semestre.id]"
                    :key="time + semestre.libelle + group"
                    class="grid-cell"
                    :style="{
                backgroundColor: placedCourses[
                  `${day.day}_${time}_${semestre.libelle}_${groupToInt(group)}`
                ]
                  ? placedCourses[`${day.day}_${time}_${semestre.libelle}_${groupToInt(group)}`].color
                  : ''
              }"
                    @drop="onDrop($event, day.day, time, semestre.libelle, groupToInt(group))"
                    @mouseover="highlightSameCourses(day.day, time, semestre.libelle, groupToInt(group))"
                    @mouseout="clearSameCoursesHighlight(day.day, time, semestre.libelle, groupToInt(group))"
                    @dragover.prevent
                    :data-key="day.day + '_' + time + '_' + semestre.libelle + '_' + groupToInt(group)"
                    draggable="true"
                    @dragstart="
                onDragStart(
                  $event,
                  placedCourses[`${day.day}_${time}_${semestre.libelle}_${groupToInt(group)}`],
                  'grid',
                  `${day.day}_${time}_${semestre.libelle}_${groupToInt(group)}`
                )
              "
                    @dragend="clearHighlight"
                >
              <span v-if="placedCourses[`${day.day}_${time}_${semestre.libelle}_${groupToInt(group)}`]">
                <span
                    v-html="
                    displayCourse(
                      placedCourses[`${day.day}_${time}_${semestre.libelle}_${groupToInt(group)}`]
                    )
                  "
                ></span>
                <span
                    v-if="
                    placedCourses[`${day.day}_${time}_${semestre.libelle}_${groupToInt(group)}`]
                      .blocked === false
                  "
                    @click="
                    openModal(
                      placedCourses[`${day.day}_${time}_${semestre.libelle}_${groupToInt(group)}`]
                    )
                  "
                >
                  -{{
                    placedCourses[`${day.day}_${time}_${semestre.libelle}_${groupToInt(group)}`].room
                  }}-
                </span>
                <button
                    v-if="
                    placedCourses[`${day.day}_${time}_${semestre.libelle}_${groupToInt(group)}`]
                      .blocked === false
                  "
                    class="remove-btn"
                    @click="
                    removeCourse(
                      day.day,
                      time,
                      semestre.libelle,
                      groupToInt(group),
                      groupeSize(placedCourses[`${day.day}_${time}_${semestre.libelle}_${groupToInt(group)}`])
                    )
                  "
                >
                  x
                </button>
              </span>
                </div>
              </template>
            </template>
          </div>
        </div>
      </div>
  </Card>
  <!-- Sidebar Toggle Button -->
  <nav class="sidebar-toggle" @click="toggleSidebar">
    Cours
    <span v-if="!isSidebarOpen">◀ ({{ filteredCourses.length }})</span>
    <span v-else>▶</span>
  </nav>

  <!-- Collapsible Sidebar -->
  <div :class="['sidebar', { 'sidebar-open': isSidebarOpen }]">
    <div class="row">
      <div class="col-6">
        <select v-model="selectedSemester">
          <option value="">Semestre</option>
          <option
              v-for="semestre in semestres"
              :value="semestre.id"
              :key="semestre.id"
          >Semestre {{ semestre.libelle}}</option>
        </select>
      </div>
      <div class="col-6">
        <select v-model="selectedProfessor">
          <option value="">Professeur</option>
          <option

              v-for="professor in personnels"
              :key="professor.initiales"
              :value="professor.initiales"
          >
            {{ professor.initiales }}
          </option>
        </select>
      </div>
      <div class="col-6">
        <select v-model="selectedCourse">
          <option value="">Cours</option>
          <option
              :value="matiere.code"
              v-for="matiere in matieresStore.matieres"
              :key="matiere.code"
          >
            {{ matiere.code }}
          </option>
        </select>
      </div>
      <div class="col-6">
        <select v-model="selectedGroup">
          <option value="">Groupe</option>
          <option
              v-for="group in groupData[selectedSemester]"
              :value="group">
            {{ group }}
          </option>

        </select>
      </div>
      <div class="col-6"></div>
      <div class="col-6">
        <Button @click="resetFilters" severity="warn">Eff. filtres</Button>
      </div>
    </div>
    <div class="list-group grid-container-available">
      <div
          v-for="course in filteredCourses"
          :key="course.id"
          class="list-group-item grid-item-available"
          :style="{
          gridColumn: `span ${groupeSize(course)}`,
          backgroundColor: course.couleur,
          cursor: 'move'
        }"
          draggable="true"
          @dragstart="onDragStart($event, course, 'availableCourses', '')"
      >
        <span v-html="displayCourseListe(course)" class="course-available"></span>
      </div>
    </div>
  </div>

  <div class="col-12">
    <h2>Cours à replacer ({{ coursesToReplace.length }})</h2>
    <div class="list-group grid-container-replace" @dragover.prevent @drop="onDropToReplace">
      <div
          v-for="course in coursesToReplace"
          :key="course.id"
          class="list-group-item grid-item-replace"
          :style="{ backgroundColor: course.couleur }"
      >
        <span v-html="displayCourseListe(course)" class="course-replace"></span>
        <span>Semaine d'origine: {{ course.originalWeek }}</span>
      </div>
    </div>
  </div>

  <!-- Modal for editing room -->
  <div v-if="isModalOpen" class="modal">
    <div class="modal-content">
      <span class="close" @click="closeModal">&times;</span>
      <h2>Modifier la salle</h2>
      {{ modalCourse.id }}
      <p><strong>Cours:</strong> {{ modalCourse.matiere }}</p>
      <p><strong>Professeur:</strong> {{ modalCourse.professor }}</p>
      <p><strong>Créneau:</strong> {{ modalCourse.day }} {{ modalCourse.time }}</p>
      <label for="room">Salle:</label>
      <input type="text" v-model="modalCourse.room" id="room"/>
      <button @click="saveRoom">Enregistrer</button>
    </div>
  </div>
</template>

<style scoped>
.grid-container {
  display: grid;
  grid-template-columns: 100px repeat(v-bind(size), 1fr);
  gap: 0;
  width: 100%;
  border: 1px solid #000;
}

.grid-day {
  grid-column: span v-bind(size+1);
  background-color: #e19797;
  text-align: center;
  font-weight: bold;
}

.grid-header {
  background-color: #f2f2f2;
  text-align: center;
  padding: 8px;
  font-weight: bold;
  border: 1px solid #000;
  grid-column: span 1;
}

.grid-time {
  text-align: center;
  padding: 8px;
  background-color: #f9f9f9;
  border: 1px solid #000;
  grid-column: span 1;
}

.grid-cell {
  text-align: center;
  font-size: 9px;
  width: 50px;
  padding: 2px;
  border: 1px solid #000;
  background-color: #fff;
  grid-column: span 1;
}

.grid-cell.highlight {
  background-color: #d1e7dd;
}

.grid-cell.highlight-same-course {
  background-color: #ffeb3b !important; /* Highlight color */
}

.course-available {
  display: block;
  padding: 8px;
}

.grid-container-available {
  display: grid;
  grid-template-columns: repeat(8, 1fr); /* Ajustez le nombre de colonnes selon vos besoins */
  gap: 3px;
}

.grid-item-available {
  padding: 2px;
  font-size: 9px;
  border: 1px solid #000;
  background-color: #fff;
  text-align: center;
}

.remove-btn {
  background: none;
  border: none;
  color: red;
  cursor: pointer;
  font-size: 16px;
  margin-left: 8px;
}

.row {
  position: relative;
}

.sidebar-toggle {
  position: fixed;
  top: 50%;
  width: 80px;
  right: 0;
  transform: translateY(-50%);
  background-color: #007bff;
  color: white;
  padding: 10px;
  cursor: pointer;
  z-index: 1000;
}

.sidebar {
  position: fixed;
  top: 0;
  right: -300px;
  width: 300px;
  height: 100%;
  background-color: #f8f9fa;
  box-shadow: -2px 0 5px rgba(0, 0, 0, 0.5);
  transition: right 0.3s ease;
  z-index: 999;
}

.sidebar-open {
  right: 0;
}

.list-group-item {
  padding: 10px;
  border-bottom: 1px solid #ddd;
}

.grid-container-replace {
  display: grid;
  grid-template-columns: repeat(8, 1fr); /* Adjust the number of columns as needed */
  gap: 3px;
  min-height: 50px;
}

.grid-item-replace {
  padding: 2px;
  font-size: 9px;
  border: 1px solid #000;
  background-color: #fff;
  text-align: center;
}

.course-replace {
  display: block;
  padding: 8px;
}

.grid-cell.highlight-mandatory {
  background-color: #ffcccc; /* Rouge pour les contraintes obligatoires */
}

.grid-cell.highlight-optional {
  background-color: #ffffcc; /* Jaune pour les contraintes facultatives */
}

.modal {
  display: block;
  position: fixed;
  z-index: 1;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgb(0, 0, 0);
  background-color: rgba(0, 0, 0, 0.4);
}

.modal-content {
  background-color: #fefefe;
  margin: 15% auto;
  padding: 20px;
  border: 1px solid #888;
  width: 80%;
}

.close {
  color: #aaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
}

.close:hover,
.close:focus {
  color: black;
  text-decoration: none;
  cursor: pointer;
}
</style>
