import { defineStore } from 'pinia';

export const useUserStore = defineStore('user', {
  state: () => ({
    user: null,
    currentBranch: null,
  }),
  actions: {
    setUser(user) {
      this.user = user;
      this.currentBranch = user.current_branch ?? null;
    },
    loadUserFromStorage() {
      const user = localStorage.getItem('user');
      const currentBranch = localStorage.getItem('currentBranch');

      if (user) {
        this.user = JSON.parse(user);
        this.currentBranch = currentBranch ? parseInt(currentBranch) : null;
      }
    },
    clearUser() {
      this.user = null;
      this.currentBranch = null;
      localStorage.removeItem('user');
      localStorage.removeItem('currentBranch');
    }
  },
  persist: {
    enabled: true,
    strategies: [
      {
        key: 'user',
        storage: localStorage,
        paths: ['user', 'currentBranch'],
      },
    ],
  },
});
