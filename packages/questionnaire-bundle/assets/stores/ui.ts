import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useUIStore = defineStore('ui', () => {
  const sidebarOpen = ref(true);
  const darkMode = ref(false);
  const currentView = ref<'dashboard' | 'builder' | 'responses' | 'analytics'>('dashboard');
  const notifications = ref<Array<{
    id: string;
    type: 'success' | 'error' | 'warning' | 'info';
    title: string;
    message: string;
    timestamp: Date;
  }>>([]);

  const isDarkMode = computed(() => darkMode.value);

  function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value;
  }

  function toggleDarkMode() {
    darkMode.value = !darkMode.value;
    updateTheme();
  }

  function updateTheme() {
    if (darkMode.value) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
    localStorage.setItem('darkMode', darkMode.value.toString());
  }

  function initializeTheme() {
    const saved = localStorage.getItem('darkMode');
    if (saved !== null) {
      darkMode.value = saved === 'true';
    } else {
      darkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    updateTheme();
  }

  function setCurrentView(view: typeof currentView.value) {
    currentView.value = view;
  }

  function addNotification(
    type: 'success' | 'error' | 'warning' | 'info',
    title: string,
    message: string
  ) {
    const notification = {
      id: Date.now().toString(),
      type,
      title,
      message,
      timestamp: new Date()
    };
    
    notifications.value.unshift(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
      removeNotification(notification.id);
    }, 5000);
  }

  function removeNotification(id: string) {
    const index = notifications.value.findIndex(n => n.id === id);
    if (index !== -1) {
      notifications.value.splice(index, 1);
    }
  }

  return {
    // State
    sidebarOpen,
    darkMode,
    currentView,
    notifications,

    // Getters
    isDarkMode,

    // Actions
    toggleSidebar,
    toggleDarkMode,
    setCurrentView,
    addNotification,
    removeNotification,
    initializeTheme
  };
});